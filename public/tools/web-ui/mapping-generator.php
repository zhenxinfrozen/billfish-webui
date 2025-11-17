<?php
/**
 * 映射生成器 - Web可视化版本
 * 从Billfish数据库生成完整的文件-预览图映射
 */

require_once '../../config.php';

$currentPage = 'tools-ui.php';
$pageTitle = '映射生成器';

// 处理生成请求
$result = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generate') {
    try {
        set_time_limit(300); // 5分钟超时
        $result = generateMapping();
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

/**
 * 生成映射数据
 */
function generateMapping() {
    $dbPath = BILLFISH_PATH . '/.bf/billfish.db';
    $outputDir = __DIR__ . '/../../mapping-exports';
    
    if (!file_exists($dbPath)) {
        throw new Exception('数据库文件不存在: ' . $dbPath);
    }
    
    // 创建输出目录
    if (!is_dir($outputDir)) {
        mkdir($outputDir, 0755, true);
    }
    
    $db = new SQLite3($dbPath, SQLITE3_OPEN_READONLY);
    
    // 获取文件夹映射
    $foldersQuery = $db->query("SELECT id, name FROM bf_folder");
    $folders = [];
    while ($row = $foldersQuery->fetchArray(SQLITE3_ASSOC)) {
        $folders[$row['id']] = $row['name'];
    }
    
    // 获取所有文件
    $filesQuery = $db->query("
        SELECT 
            f.id, f.name, f.pid, m.w, m.h, f.file_size, f.mtime
        FROM bf_file f
        LEFT JOIN bf_material_v2 m ON f.id = m.file_id
        WHERE f.is_hide = 0
        ORDER BY f.pid, f.name
    ");
    
    $files = [];
    while ($row = $filesQuery->fetchArray(SQLITE3_ASSOC)) {
        $files[] = $row;
    }
    
    // 获取视频时长
    $durationsQuery = $db->query("SELECT file_id, duration FROM bf_material_video");
    $durations = [];
    while ($row = $durationsQuery->fetchArray(SQLITE3_ASSOC)) {
        $durations[$row['file_id']] = $row['duration'];
    }
    
    // 获取用户数据（评分、备注）
    $userdataQuery = $db->query("SELECT file_id, score, note FROM bf_material_userdata");
    $userdata = [];
    while ($row = $userdataQuery->fetchArray(SQLITE3_ASSOC)) {
        $userdata[$row['file_id']] = [
            'score' => $row['score'] ?? 0,
            'note' => $row['note'] ?? ''
        ];
    }
    
    // 获取标签
    $tagsQuery = $db->query("SELECT id, name, color FROM bf_tag_v2");
    $tagsDict = [];
    while ($row = $tagsQuery->fetchArray(SQLITE3_ASSOC)) {
        $tagsDict[$row['id']] = [
            'name' => $row['name'],
            'color' => $row['color']
        ];
    }
    
    $fileTagsQuery = $db->query("SELECT file_id, tag_id FROM bf_tag_join_file");
    $fileTags = [];
    while ($row = $fileTagsQuery->fetchArray(SQLITE3_ASSOC)) {
        $fileId = $row['file_id'];
        $tagId = $row['tag_id'];
        if (!isset($fileTags[$fileId])) {
            $fileTags[$fileId] = [];
        }
        if (isset($tagsDict[$tagId])) {
            $fileTags[$fileId][] = $tagsDict[$tagId];
        }
    }
    
    // 构建映射
    $mapping = [];
    $completeInfo = [];
    $previewExistsCount = 0;
    $bfDir = BILLFISH_PATH . '/.bf';
    
    foreach ($files as $file) {
        $fileId = $file['id'];
        $name = $file['name'];
        $folderId = $file['pid'];
        $width = $file['w'] ?? 0;
        $height = $file['h'] ?? 0;
        $fileSize = $file['file_size'] ?? 0;
        $mtime = $file['mtime'] ?? 0;
        
        $folderName = $folders[$folderId] ?? 'unknown';
        $videoPath = "/{$folderName}/{$name}";
        
        // 计算预览图路径
        $hexFolder = getHexFolder($fileId);
        $previewPath = ".preview/{$hexFolder}/{$fileId}.small.webp";
        
        // 检查预览图是否存在
        $fullPreviewPath = "{$bfDir}/{$previewPath}";
        $previewExists = file_exists($fullPreviewPath);
        if ($previewExists) {
            $previewExistsCount++;
        }
        
        $duration = $durations[$fileId] ?? 0;
        $userInfo = $userdata[$fileId] ?? ['score' => 0, 'note' => ''];
        $tags = $fileTags[$fileId] ?? [];
        
        $mapping[$videoPath] = [
            'file_id' => $fileId,
            'video_id' => $fileId,
            'preview_id' => $fileId,
            'video_name' => $name,
            'video_folder' => $folderName,
            'folder_id' => $folderId,
            'preview_path' => $previewPath,
            'preview_exists' => $previewExists,
            'preview_hex_folder' => $hexFolder,
            'video_size' => $fileSize,
            'width' => $width,
            'height' => $height,
            'duration' => $duration,
            'modified' => $mtime,
            'score' => $userInfo['score'],
            'note' => $userInfo['note'],
            'tags' => $tags
        ];
        
        $completeInfo[$name] = [
            'file_id' => $fileId,
            'folder' => $folderName,
            'size' => $fileSize,
            'width' => $width,
            'height' => $height,
            'duration' => $duration,
            'score' => $userInfo['score'],
            'note' => $userInfo['note'],
            'tags' => $tags,
            'preview_path' => $previewPath
        ];
    }
    
    // 保存文件
    $timestamp = date('Y-m-d_His');
    $mappingFile = "{$outputDir}/id_based_mapping_{$timestamp}.json";
    $infoFile = "{$outputDir}/complete_material_info_{$timestamp}.json";
    
    file_put_contents($mappingFile, json_encode($mapping, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    file_put_contents($infoFile, json_encode($completeInfo, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    
    // 统计信息
    $withTags = count(array_filter($mapping, fn($v) => !empty($v['tags'])));
    $withScore = count(array_filter($mapping, fn($v) => $v['score'] > 0));
    $withNote = count(array_filter($mapping, fn($v) => !empty($v['note'])));
    $matchRate = count($mapping) > 0 ? ($previewExistsCount / count($mapping) * 100) : 0;
    
    $db->close();
    
    return [
        'success' => true,
        'mapping_file' => basename($mappingFile),
        'info_file' => basename($infoFile),
        'stats' => [
            'total_files' => count($mapping),
            'preview_exists' => $previewExistsCount,
            'match_rate' => round($matchRate, 1),
            'with_tags' => $withTags,
            'with_score' => $withScore,
            'with_note' => $withNote,
            'folders' => count($folders)
        ],
        'samples' => array_slice($mapping, 0, 3, true)
    ];
}

/**
 * 计算十六进制文件夹名（取后两位）
 */
function getHexFolder($fileId) {
    $hex = dechex($fileId);
    $hex = str_pad($hex, 2, '0', STR_PAD_LEFT);
    return substr($hex, -2);
}

include '../../includes/header.php';
?>

<style>
    .stats-card {
        transition: transform 0.2s;
    }
    .stats-card:hover {
        transform: translateY(-3px);
    }
    .sample-code {
        background: #f8f9fa;
        border-left: 4px solid #007bff;
        padding: 15px;
        border-radius: 4px;
        font-family: 'Courier New', monospace;
        font-size: 0.9em;
        overflow-x: auto;
    }
    .stat-number {
        font-size: 2rem;
        font-weight: bold;
        color: #007bff;
    }
</style>

<div class="container mt-4" style="padding-top: 70px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1><i class="fas fa-sitemap"></i> 映射生成器</h1>
            <p class="text-muted">从Billfish数据库生成完整的文件-预览图映射JSON</p>
        </div>
        <a href="/tools-ui.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> 返回工具中心
        </a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <h5><i class="fas fa-exclamation-triangle"></i> 错误</h5>
            <p class="mb-0"><?= htmlspecialchars($error) ?></p>
        </div>
    <?php endif; ?>

    <?php if ($result): ?>
        <!-- 生成成功结果 -->
        <div class="alert alert-success">
            <h5><i class="fas fa-check-circle"></i> 映射生成成功！</h5>
            <p class="mb-0">文件已保存到 <code>public/mapping-exports/</code> 目录</p>
        </div>

        <!-- 统计卡片 -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <div class="stat-number"><?= number_format($result['stats']['total_files']) ?></div>
                        <p class="text-muted mb-0">总文件数</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <div class="stat-number text-success"><?= $result['stats']['match_rate'] ?>%</div>
                        <p class="text-muted mb-0">预览图覆盖率</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <div class="stat-number text-info"><?= number_format($result['stats']['with_tags']) ?></div>
                        <p class="text-muted mb-0">带标签文件</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <div class="stat-number text-warning"><?= number_format($result['stats']['folders']) ?></div>
                        <p class="text-muted mb-0">文件夹数</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- 详细统计 -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-bar"></i> 详细统计</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <ul class="list-unstyled">
                            <li><strong>预览图存在:</strong> <?= number_format($result['stats']['preview_exists']) ?> / <?= number_format($result['stats']['total_files']) ?></li>
                            <li><strong>带评分:</strong> <?= number_format($result['stats']['with_score']) ?></li>
                            <li><strong>带备注:</strong> <?= number_format($result['stats']['with_note']) ?></li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <ul class="list-unstyled">
                            <li><strong>映射文件:</strong> <code><?= htmlspecialchars($result['mapping_file']) ?></code></li>
                            <li><strong>信息文件:</strong> <code><?= htmlspecialchars($result['info_file']) ?></code></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- 映射示例 -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-code"></i> 映射示例（前3个）</h5>
            </div>
            <div class="card-body">
                <?php foreach ($result['samples'] as $path => $info): ?>
                    <div class="sample-code mb-3">
                        <div><strong><?= htmlspecialchars($path) ?></strong></div>
                        <div class="mt-2">
                            <div>文件ID: <?= $info['file_id'] ?></div>
                            <div>预览图: <?= htmlspecialchars($info['preview_path']) ?></div>
                            <div>十六进制文件夹: <?= $info['preview_hex_folder'] ?></div>
                            <div>预览图存在: <?= $info['preview_exists'] ? '✅ 是' : '❌ 否' ?></div>
                            <?php if ($info['score'] > 0): ?>
                                <div>评分: <?= $info['score'] ?> 星</div>
                            <?php endif; ?>
                            <?php if (!empty($info['tags'])): ?>
                                <div>标签: <?= implode(', ', array_column($info['tags'], 'name')) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php else: ?>
        <!-- 生成表单 -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-play"></i> 开始生成</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <h6><i class="fas fa-info-circle"></i> 关于映射生成器</h6>
                    <ul class="mb-0">
                        <li>从Billfish数据库读取所有文件信息</li>
                        <li>生成文件路径到预览图的完整映射关系</li>
                        <li>包含文件夹、尺寸、时长、标签、评分等元数据</li>
                        <li>验证预览图文件的实际存在性</li>
                        <li>输出两个JSON文件：路径映射和完整信息</li>
                    </ul>
                </div>

                <form method="POST" onsubmit="return confirm('确认生成映射？这可能需要几分钟时间。');">
                    <input type="hidden" name="action" value="generate">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-cogs"></i> 开始生成映射
                    </button>
                </form>

                <div class="mt-4">
                    <h6>输出文件说明：</h6>
                    <ul>
                        <li><code>id_based_mapping_[时间戳].json</code> - 以文件路径为键的映射</li>
                        <li><code>complete_material_info_[时间戳].json</code> - 以文件名为键的完整信息</li>
                    </ul>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include '../../includes/footer.php'; ?>
