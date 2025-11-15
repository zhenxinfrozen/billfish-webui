<?php
/**
 * Billfish Web Manager - 主页
 */

require_once 'config.php';
require_once 'includes/BillfishManagerV3.php';

$currentPage = 'index.php';
$pageTitle = 'Billfish Web Manager';

try {
    $manager = new BillfishManagerV3(BILLFISH_PATH);
    $stats = $manager->getLibraryStats();
    $recentFiles = $manager->getRecentFiles(12);
} catch (Exception $e) {
    die("错误: " . $e->getMessage());
}

// 引入页头
include 'includes/header.php';
?>

    <div class="container mt-4">
        <h1><i class="fas fa-home"></i> Billfish Web Manager</h1>
        <p class="text-muted">基于 Billfish 的 Web 版资源管理器</p>

        <!-- 统计卡片 -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0">总文件数</h6>
                                <h2 class="mb-0"><?= $stats['total_files'] ?></h2>
                            </div>
                            <i class="fas fa-file fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0">视频文件</h6>
                                <h2 class="mb-0"><?= $stats['video_count'] ?></h2>
                            </div>
                            <i class="fas fa-video fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-info">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0">总大小</h6>
                                <h2 class="mb-0"><?= $stats['total_size_gb'] ?> GB</h2>
                            </div>
                            <i class="fas fa-hdd fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-warning">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0">标签数量</h6>
                                <h2 class="mb-0"><?= $stats['tag_count'] ?></h2>
                            </div>
                            <i class="fas fa-tags fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 最近文件 -->
        <h2><i class="fas fa-clock"></i> 最近文件</h2>
        <div class="row">
            <?php foreach ($recentFiles as $file): ?>
                <div class="col-md-2 mb-3">
                    <div class="card file-card">
                        <!-- 缩略图容器 (16:9比例) -->
                        <div class="position-relative" style="padding-top: 56.25%; overflow: hidden;">
                            <img src="<?= htmlspecialchars($file['preview_url']) ?>" 
                                 class="position-absolute top-0 start-0 w-100 h-100" 
                                 alt="<?= htmlspecialchars($file['name']) ?>"
                                 style="object-fit: cover;">
                            
                            <!-- Hover遮罩层 (只在缩略图上) -->
                            <a href="view.php?id=<?= $file['id'] ?>" 
                               class="file-card-overlay position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center text-decoration-none">
                                <div class="text-center">
                                    <i class="fas fa-play-circle text-white" style="font-size: 3rem; opacity: 0.9;"></i>
                                    <p class="text-white mt-2 mb-0 small">点击预览</p>
                                </div>
                            </a>
                        </div>
                        
                        <div class="card-body p-2">
                            <h6 class="card-title text-truncate" title="<?= htmlspecialchars($file['name']) ?>">
                                <?= htmlspecialchars(substr($file['name'], 0, 20)) ?>
                            </h6>
                            <p class="card-text small text-muted mb-1">
                                <i class="fas fa-folder"></i> <?= htmlspecialchars($file['category']) ?>
                            </p>
                            <p class="card-text small text-muted mb-0">
                                <?= $file['size_mb'] ?> MB
                            </p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- 快速操作 -->
        <div class="row mt-4">
            <div class="col-md-12">
                <h3><i class="fas fa-bolt"></i> 快速操作</h3>
                <div class="list-group">
                    <a href="browse.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-th"></i> 浏览所有文件
                    </a>
                    <a href="search.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-search"></i> 搜索文件
                    </a>
                    <a href="database-health.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-heartbeat"></i> 数据库健康检查
                    </a>
                    <a href="docs-ui.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-book"></i> 查看文档
                    </a>
                </div>
            </div>
        </div>
    </div>

<style>
/* 文件卡片整体效果 */
.file-card {
    transition: transform 0.2s, box-shadow 0.2s;
    border: 1px solid #dee2e6;
    border-radius: 0.25rem;
    overflow: hidden;
}

.file-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.15);
}

/* 遮罩层 - 只在缩略图区域 */
.file-card-overlay {
    background: rgba(0,0,0,0.7);
    opacity: 0;
    transition: opacity 0.3s ease;
    z-index: 10;
}

.file-card-overlay:hover {
    opacity: 1;
}
</style>

<?php include 'includes/footer.php'; ?>
