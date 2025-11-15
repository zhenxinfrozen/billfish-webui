<?php
/**
 * 系统状态检查工具
 */

require_once '../../config.php';

// 执行检查
$checks = [
    'database' => checkDatabase(),
    'preview_folder' => checkPreviewFolder(),
    'materials_folder' => checkMaterialsFolder(),
    'php_extensions' => checkPHPExtensions(),
    'permissions' => checkPermissions(),
    'preview_coverage' => checkPreviewCoverage()
];

// 计算总体状态
$totalChecks = count($checks);
$passedChecks = count(array_filter($checks, fn($c) => $c['status'] === 'success'));
$overallStatus = $passedChecks === $totalChecks ? 'success' : ($passedChecks > 0 ? 'warning' : 'danger');

function checkDatabase() {
    if (!class_exists('SQLite3')) {
        return [
            'status' => 'danger',
            'message' => 'SQLite3 扩展未启用',
            'details' => '请在 php.ini 中启用 extension=sqlite3'
        ];
    }
    
    try {
        $db = new SQLite3(BILLFISH_PATH . '/.bf/billfish.db');
        $result = $db->querySingle("SELECT COUNT(*) FROM bf_file");
        return [
            'status' => 'success',
            'message' => "数据库连接正常,共 {$result} 个文件",
            'details' => "SQLite 版本: " . SQLite3::version()['versionString']
        ];
    } catch (Exception $e) {
        return [
            'status' => 'danger',
            'message' => '数据库连接失败',
            'details' => $e->getMessage()
        ];
    }
}

function checkPreviewFolder() {
    $previewPath = BILLFISH_PATH . '/.preview';
    if (!is_dir($previewPath)) {
        return [
            'status' => 'danger',
            'message' => '预览文件夹不存在',
            'details' => $previewPath
        ];
    }
    
    $folders = glob($previewPath . '/*', GLOB_ONLYDIR);
    return [
        'status' => 'success',
        'message' => "预览文件夹正常,包含 " . count($folders) . " 个子目录",
        'details' => "路径: {$previewPath}"
    ];
}

function checkMaterialsFolder() {
    $materialsPath = BILLFISH_PATH . '/materials';
    if (!is_dir($materialsPath)) {
        return [
            'status' => 'warning',
            'message' => '素材文件夹不存在',
            'details' => $materialsPath
        ];
    }
    
    $size = 0;
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($materialsPath));
    $count = 0;
    foreach ($files as $file) {
        if ($file->isFile()) {
            $size += $file->getSize();
            $count++;
        }
    }
    
    return [
        'status' => 'success',
        'message' => "素材文件夹正常,共 {$count} 个文件",
        'details' => "总大小: " . formatBytes($size)
    ];
}

function checkPHPExtensions() {
    $required = ['sqlite3', 'json', 'mbstring'];
    $missing = [];
    
    foreach ($required as $ext) {
        if (!extension_loaded($ext)) {
            $missing[] = $ext;
        }
    }
    
    if (empty($missing)) {
        return [
            'status' => 'success',
            'message' => '所有必需的 PHP 扩展已加载',
            'details' => implode(', ', $required)
        ];
    }
    
    return [
        'status' => 'danger',
        'message' => '缺少必需的 PHP 扩展',
        'details' => '缺少: ' . implode(', ', $missing)
    ];
}

function checkPermissions() {
    $paths = [
        BILLFISH_PATH . '/.bf/billfish.db',
        BILLFISH_PATH . '/.preview'
    ];
    
    $issues = [];
    foreach ($paths as $path) {
        if (!is_readable($path)) {
            $issues[] = basename($path) . ' 不可读';
        }
    }
    
    if (empty($issues)) {
        return [
            'status' => 'success',
            'message' => '文件权限正常',
            'details' => '所有必需路径可访问'
        ];
    }
    
    return [
        'status' => 'warning',
        'message' => '部分权限问题',
        'details' => implode(', ', $issues)
    ];
}

function checkPreviewCoverage() {
    if (!class_exists('SQLite3')) {
        return [
            'status' => 'warning',
            'message' => '无法检查预览图覆盖率',
            'details' => 'SQLite3 扩展未启用'
        ];
    }
    
    try {
        $db = new SQLite3(BILLFISH_PATH . '/.bf/billfish.db');
        $total = $db->querySingle("SELECT COUNT(*) FROM bf_file");
        $withPreview = 0;
        
        $result = $db->query("SELECT id FROM bf_file LIMIT 100");
        $sampleSize = 0;
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $sampleSize++;
            $hexFolder = substr(str_pad(dechex($row['id']), 4, '0', STR_PAD_LEFT), -2);
            $previewPath = BILLFISH_PATH . "/.bf/.preview/{$hexFolder}/{$row['id']}.small.webp";
            if (file_exists($previewPath)) {
                $withPreview++;
            }
        }
        
        $coverage = $sampleSize > 0 ? round(($withPreview / $sampleSize) * 100, 1) : 0;
        
        return [
            'status' => $coverage > 90 ? 'success' : ($coverage > 50 ? 'warning' : 'danger'),
            'message' => "预览图覆盖率: {$coverage}% (抽样 {$sampleSize} 个文件)",
            'details' => "预计总覆盖: " . round($total * $coverage / 100) . " / {$total} 个文件"
        ];
    } catch (Exception $e) {
        return [
            'status' => 'danger',
            'message' => '检查预览图覆盖率失败',
            'details' => $e->getMessage()
        ];
    }
}

function formatBytes($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系统状态检查 - Billfish Web Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .check-card {
            transition: transform 0.2s;
        }
        .check-card:hover {
            transform: translateY(-3px);
        }
        .status-icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        .overall-status {
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container my-5">
        <div class="text-center mb-4">
            <h1><i class="fas fa-heartbeat"></i> 系统状态检查</h1>
            <p class="text-muted">检查 Billfish Web Manager 系统健康状况</p>
        </div>

        <!-- 总体状态 -->
        <div class="overall-status bg-<?= $overallStatus ?> text-white text-center">
            <h2>
                <?php if ($overallStatus === 'success'): ?>
                    <i class="fas fa-check-circle"></i> 系统运行正常
                <?php elseif ($overallStatus === 'warning'): ?>
                    <i class="fas fa-exclamation-triangle"></i> 部分功能可能受限
                <?php else: ?>
                    <i class="fas fa-times-circle"></i> 系统存在严重问题
                <?php endif; ?>
            </h2>
            <p class="mb-0">通过 <?= $passedChecks ?> / <?= $totalChecks ?> 项检查</p>
        </div>

        <!-- 检查项目 -->
        <div class="row">
            <?php foreach ($checks as $name => $check): ?>
            <div class="col-md-6 mb-4">
                <div class="card check-card h-100">
                    <div class="card-body">
                        <div class="status-icon text-<?= $check['status'] ?>">
                            <?php if ($check['status'] === 'success'): ?>
                                <i class="fas fa-check-circle"></i>
                            <?php elseif ($check['status'] === 'warning'): ?>
                                <i class="fas fa-exclamation-triangle"></i>
                            <?php else: ?>
                                <i class="fas fa-times-circle"></i>
                            <?php endif; ?>
                        </div>
                        <h5 class="card-title"><?= ucfirst(str_replace('_', ' ', $name)) ?></h5>
                        <p class="card-text"><?= htmlspecialchars($check['message']) ?></p>
                        <small class="text-muted"><?= htmlspecialchars($check['details']) ?></small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- 操作按钮 -->
        <div class="text-center mt-4">
            <a href="../../tools-ui.php" class="btn btn-primary">
                <i class="fas fa-tools"></i> 返回工具中心
            </a>
            <button onclick="location.reload()" class="btn btn-secondary">
                <i class="fas fa-sync"></i> 重新检查
            </button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
