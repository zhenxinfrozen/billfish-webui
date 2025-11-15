<?php
/**
 * 预览图检查工具
 */

require_once '../../config.php';

$sqlite_available = class_exists('SQLite3');
$error_message = null;
$files = [];
$total = 0;
$withPreview = 0;
$missing = 0;
$coverage = 0;

if (!$sqlite_available) {
    $error_message = 'SQLite3 扩展未启用。请在 php.ini 中启用 extension=sqlite3';
} else {
    try {
        $db = new SQLite3(BILLFISH_PATH . '/.bf/billfish.db');

        $checkAll = isset($_GET['check_all']);
        $limit = $checkAll ? 1000 : 50;

        // 获取文件列表
        $result = $db->query("
            SELECT f.id, f.name, t.name as type_name 
            FROM bf_file f 
            LEFT JOIN bf_type t ON f.tid = t.id 
            LIMIT {$limit}
        ");
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $hexFolder = substr(str_pad(dechex($row['id']), 4, '0', STR_PAD_LEFT), -2);
            $previewPath = BILLFISH_PATH . "/.bf/.preview/{$hexFolder}/{$row['id']}.small.webp";
            
            $files[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'type' => $row['type_name'] ?? pathinfo($row['name'], PATHINFO_EXTENSION),
                'hex_folder' => $hexFolder,
                'preview_exists' => file_exists($previewPath),
                'preview_path' => $previewPath
            ];
        }

        // 统计
        $total = count($files);
        $withPreview = count(array_filter($files, fn($f) => $f['preview_exists']));
        $missing = $total - $withPreview;
        $coverage = $total > 0 ? round(($withPreview / $total) * 100, 1) : 0;
    } catch (Exception $e) {
        $error_message = '数据库错误: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>预览图检查工具 - Billfish Web Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            text-align: center;
            padding: 20px;
            border-radius: 8px;
        }
        .file-row {
            padding: 10px;
            border-bottom: 1px solid #dee2e6;
        }
        .file-row:hover {
            background: #f8f9fa;
        }
        .preview-thumb {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #dee2e6;
        }
        .preview-thumb:hover {
            transform: scale(2);
            z-index: 1000;
            position: relative;
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
        }
        .file-info {
            font-size: 14px;
        }
        .badge {
            font-size: 11px;
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark bg-dark">
        <div class="container-fluid">
            <span class="navbar-brand"><i class="fas fa-images"></i> 预览图检查工具</span>
            <a href="../../tools-ui.php" class="btn btn-sm btn-outline-light">
                <i class="fas fa-arrow-left"></i> 返回
            </a>
        </div>
    </nav>

    <div class="container my-4">
        <?php if ($error_message): ?>
        <!-- 错误提示 -->
        <div class="alert alert-danger">
            <h4><i class="fas fa-exclamation-triangle"></i> 错误</h4>
            <p><?= htmlspecialchars($error_message) ?></p>
            <hr>
            <p class="mb-0"><strong>解决方法:</strong></p>
            <ol>
                <li>打开 php.ini 文件</li>
                <li>找到 <code>;extension=sqlite3</code> 这一行</li>
                <li>删除前面的分号 <code>;</code> 使其变为 <code>extension=sqlite3</code></li>
                <li>重启PHP服务器</li>
            </ol>
        </div>
        <?php else: ?>
        <!-- 统计信息 -->
        <div class="stats-grid">
            <div class="stat-card bg-primary text-white">
                <h3><?= number_format($total) ?></h3>
                <p class="mb-0">检查文件数</p>
            </div>
            <div class="stat-card bg-success text-white">
                <h3><?= number_format($withPreview) ?></h3>
                <p class="mb-0">预览图存在</p>
            </div>
            <div class="stat-card bg-danger text-white">
                <h3><?= number_format($missing) ?></h3>
                <p class="mb-0">预览图缺失</p>
            </div>
            <div class="stat-card bg-info text-white">
                <h3><?= $coverage ?>%</h3>
                <p class="mb-0">覆盖率</p>
            </div>
        </div>

        <!-- 操作按钮 -->
        <div class="mb-3">
            <?php if (!$checkAll): ?>
            <a href="?check_all=1" class="btn btn-primary">
                <i class="fas fa-search"></i> 检查更多文件 (1000个)
            </a>
            <?php else: ?>
            <a href="?" class="btn btn-secondary">
                <i class="fas fa-undo"></i> 返回快速检查 (50个)
            </a>
            <?php endif; ?>
            
            <button class="btn btn-outline-secondary" onclick="filterFiles('missing')">
                <i class="fas fa-filter"></i> 只显示缺失
            </button>
            <button class="btn btn-outline-secondary" onclick="filterFiles('exists')">
                <i class="fas fa-filter"></i> 只显示存在
            </button>
            <button class="btn btn-outline-secondary" onclick="filterFiles('all')">
                <i class="fas fa-list"></i> 显示全部
            </button>
        </div>

        <!-- 文件列表 -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">文件列表</h5>
            </div>
            <div class="card-body p-0" style="max-height: 600px; overflow-y: auto;">
                <?php foreach ($files as $file): ?>
                <div class="file-row d-flex align-items-center justify-content-between" 
                     data-status="<?= $file['preview_exists'] ? 'exists' : 'missing' ?>">
                    <div class="d-flex align-items-center flex-grow-1">
                        <!-- 预览图 -->
                        <div class="me-3">
                            <?php if ($file['preview_exists']): ?>
                            <img src="../../preview.php?path=.preview/<?= $file['hex_folder'] ?>/<?= $file['id'] ?>.small.webp" 
                                 class="preview-thumb" 
                                 alt="预览图">
                            <?php else: ?>
                            <div class="preview-thumb bg-secondary d-flex align-items-center justify-content-center">
                                <i class="fas fa-times text-white"></i>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- 文件信息 -->
                        <div class="flex-grow-1 file-info">
                            <div class="mb-1">
                                <strong><?= htmlspecialchars($file['name']) ?></strong>
                                <span class="badge bg-secondary ms-2"><?= strtoupper($file['type']) ?></span>
                            </div>
                            <small class="text-muted">
                                ID: <code><?= $file['id'] ?></code> · 
                                十六进制文件夹: <code><?= $file['hex_folder'] ?></code>
                            </small>
                        </div>
                        
                        <!-- 状态 -->
                        <div>
                            <?php if ($file['preview_exists']): ?>
                            <span class="badge bg-success">
                                <i class="fas fa-check"></i> 预览图存在
                            </span>
                            <?php else: ?>
                            <span class="badge bg-danger">
                                <i class="fas fa-times"></i> 预览图缺失
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 说明 -->
        <div class="alert alert-info mt-4">
            <h6><i class="fas fa-info-circle"></i> 预览图路径规则</h6>
            <p class="mb-0">
                预览图路径 = <code>.preview/{hex_folder}/{file_id}.small.webp</code><br>
                其中 <code>hex_folder</code> 是文件ID的十六进制表示的后两位
            </p>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function filterFiles(type) {
            const rows = document.querySelectorAll('.file-row');
            rows.forEach(row => {
                if (type === 'all') {
                    row.style.display = 'flex';
                } else {
                    row.style.display = row.dataset.status === type ? 'flex' : 'none';
                }
            });
        }
    </script>
</body>
</html>
