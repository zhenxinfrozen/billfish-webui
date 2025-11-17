<?php
/**
 * 系统基础检查工具 - v0.1.4
 * 专注于环境验证、连接测试和基础配置检查
 */

require_once '../../config.php';

$currentPage = 'tools-ui.php';
$pageTitle = '系统基础检查';

// 执行检查
$checks = [
    'php_extensions' => checkPHPExtensions(),
    'database' => checkDatabase(),
    'preview_folder' => checkPreviewFolder(),
    'permissions' => checkPermissions()
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
    $previewPath = BILLFISH_PATH . '/.bf/.preview';
    if (!is_dir($previewPath)) {
        return [
            'status' => 'danger',
            'message' => '预览文件夹不存在',
            'details' => $previewPath
        ];
    }
    
    // 只检查目录存在性，不遍历文件
    return [
        'status' => 'success',
        'message' => "预览文件夹正常",
        'details' => "路径: {$previewPath}"
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
        BILLFISH_PATH . '/.bf/.preview'
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


include '../../includes/header.php';
?>

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

    <div class="container mt-4" style="padding-top: 70px;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1><i class="fas fa-stethoscope"></i> 系统基础检查</h1>
                <p class="text-muted">验证运行环境、扩展和基础配置</p>
            </div>
            <div>
                <a href="/tools-ui.php" class="btn btn-outline-secondary me-2">
                    <i class="fas fa-arrow-left"></i> 返回工具中心
                </a>
                <button onclick="location.reload()" class="btn btn-primary">
                    <i class="fas fa-sync"></i> 重新检查
                </button>
            </div>
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

        <!-- 说明信息 -->
        <div class="alert alert-info mt-4">
            <h5><i class="fas fa-info-circle"></i> 关于基础检查</h5>
            <ul class="mb-0">
                <li><strong>PHP扩展:</strong> 检查必需的PHP扩展是否已加载</li>
                <li><strong>数据库连接:</strong> 测试SQLite数据库连接是否正常</li>
                <li><strong>预览文件夹:</strong> 验证预览图目录是否存在</li>
                <li><strong>文件权限:</strong> 检查关键路径的读取权限</li>
            </ul>
            <hr>
            <p class="mb-0">
                <i class="fas fa-lightbulb"></i> 
                需要查看详细的资源库统计和数据完整性？请使用 
                <a href="database-health.php" class="alert-link">数据库健康报告</a>
            </p>
        </div>
    </div>

<?php include '../../includes/footer.php'; ?>
