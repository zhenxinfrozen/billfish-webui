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

    <main class="home-page py-4">
        <div class="home-wrap">
            <section class="home-hero">
                <div class="home-hero-main">
                    <span class="home-kicker">素材管理面板</span>
                    <h1><i class="fas fa-fish"></i> Billfish Web Manager</h1>
                    <p>统一浏览、预览和维护你的素材资源，保持流程轻量且稳定。</p>
                </div>
                <div class="home-hero-actions">
                    <span class="hero-chip"><i class="fas fa-check-circle"></i> 核心模块在线</span>
                    <span class="hero-chip"><i class="fas fa-database"></i> 数据源已连接</span>
                    <span class="hero-chip"><i class="fas fa-clock"></i> 最近同步可用</span>
                </div>
            </section>

            <section class="home-stats">
                <div class="metric metric-a">
                    <span class="metric-label">总文件数</span>
                    <strong class="metric-value"><?= $stats['total_files'] ?></strong>
                    <i class="fas fa-file metric-icon"></i>
                </div>
                <div class="metric metric-b">
                    <span class="metric-label">视频文件</span>
                    <strong class="metric-value"><?= $stats['video_count'] ?></strong>
                    <i class="fas fa-video metric-icon"></i>
                </div>
                <div class="metric metric-c">
                    <span class="metric-label">总大小</span>
                    <strong class="metric-value"><?= $stats['total_size_gb'] ?> GB</strong>
                    <i class="fas fa-hdd metric-icon"></i>
                </div>
                <div class="metric metric-d">
                    <span class="metric-label">标签数量</span>
                    <strong class="metric-value"><?= $stats['tag_count'] ?></strong>
                    <i class="fas fa-tags metric-icon"></i>
                </div>
            </section>

            <section class="home-section">
                <header class="home-section-head">
                    <h2><i class="fas fa-clock"></i> 最近文件</h2>
                    <span>共 <?= count($recentFiles) ?> 项</span>
                </header>
                <div class="media-grid">
                    <?php foreach ($recentFiles as $file): ?>
                        <article class="media-tile">
                            <a href="view.php?id=<?= $file['id'] ?>" class="media-preview">
                                <img src="<?= htmlspecialchars($file['preview_url']) ?>"
                                     alt="<?= htmlspecialchars($file['name']) ?>">
                                <span class="media-overlay"><i class="fas fa-play-circle"></i> 预览</span>
                            </a>
                            <div class="media-info">
                                <h3 title="<?= htmlspecialchars($file['name']) ?>"><?= htmlspecialchars(substr($file['name'], 0, 22)) ?></h3>
                                <p><i class="fas fa-folder"></i> <?= htmlspecialchars($file['category']) ?></p>
                                <p><?= $file['size_mb'] ?> MB</p>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="home-section">
                <header class="home-section-head">
                    <h2><i class="fas fa-bolt"></i> 快速操作</h2>
                </header>
                <div class="quick-grid">
                    <a href="tools-ui.php" class="quick-item"><i class="fas fa-toolbox"></i><span>打开工具中心</span></a>
                    <a href="tools/web-ui/system-health-check.php" class="quick-item"><i class="fas fa-signal"></i><span>查看系统状态</span></a>
                    <a href="tools/web-ui/database-health.php" class="quick-item"><i class="fas fa-heartbeat"></i><span>数据库健康检查</span></a>
                    <a href="tools/web-ui/preview-checker.php" class="quick-item"><i class="fas fa-images"></i><span>预览图检查</span></a>
                </div>
            </section>
        </div>
    </main>

<style>
/* 首页统一样式 */
body {
    background: #f4f6fa;
}

.home-page {
    padding-top: 8px;
}

.home-wrap {
    width: min(1120px, 100% - 32px);
    margin: 0 auto;
    display: grid;
    gap: 18px;
}

.home-hero,
.home-section {
    background: #fff;
    border: 1px solid #e6ebf2;
    border-radius: 14px;
    padding: 18px;
    box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);
}

.home-hero {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: 16px;
    background: linear-gradient(120deg, #ffffff 0%, #f6f9ff 100%);
}

.home-kicker {
    display: inline-block;
    font-size: 12px;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: #64748b;
    margin-bottom: 6px;
}

.home-hero h1 {
    margin: 0 0 6px;
    font-size: 2rem;
    font-weight: 700;
    color: #0f172a;
}

.home-hero p {
    margin: 0;
    color: #475569;
}

.home-hero-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.hero-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 10px;
    border: 1px solid #dbeafe;
    border-radius: 999px;
    background: #eff6ff;
    color: #1e3a8a;
    font-size: 0.8rem;
    white-space: nowrap;
}

.hero-chip i {
    color: #2563eb;
}

.home-stats {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
}

.metric {
    position: relative;
    border-radius: 12px;
    padding: 14px;
    color: #fff;
    min-height: 102px;
}

.metric-a { background: linear-gradient(145deg, #1d4ed8 0%, #1e3a8a 100%); }
.metric-b { background: linear-gradient(145deg, #059669 0%, #065f46 100%); }
.metric-c { background: linear-gradient(145deg, #0891b2 0%, #0e7490 100%); }
.metric-d { background: linear-gradient(145deg, #d97706 0%, #b45309 100%); }

.metric-label {
    display: block;
    font-size: 0.82rem;
    opacity: 0.9;
}

.metric-value {
    display: block;
    font-size: 1.7rem;
    line-height: 1.2;
    margin-top: 4px;
}

.metric-icon {
    position: absolute;
    right: 12px;
    bottom: 12px;
    opacity: 0.28;
    font-size: 1.8rem;
}

.home-section-head {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    margin-bottom: 12px;
}

.home-section-head h2 {
    margin: 0;
    font-size: 1.25rem;
    color: #111827;
}

.home-section-head span {
    font-size: 0.9rem;
    color: #64748b;
}

.media-grid {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: 12px;
}

.media-tile {
    border: 1px solid #e6ebf2;
    border-radius: 10px;
    overflow: hidden;
    background: #fff;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.media-tile:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 20px rgba(15, 23, 42, 0.12);
}

.media-preview {
    display: block;
    position: relative;
    aspect-ratio: 16 / 9;
    overflow: hidden;
}

.media-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.25s ease;
}

.media-tile:hover .media-preview img {
    transform: scale(1.05);
}

.media-overlay {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    color: #fff;
    font-size: 0.9rem;
    background: rgba(2, 6, 23, 0.56);
    opacity: 0;
    transition: opacity 0.2s ease;
}

.media-tile:hover .media-overlay {
    opacity: 1;
}

.media-info {
    padding: 8px;
}

.media-info h3 {
    margin: 0 0 4px;
    font-size: 0.86rem;
    color: #0f172a;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.media-info p {
    margin: 0;
    font-size: 0.76rem;
    color: #64748b;
}

.quick-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 10px;
}

.quick-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px;
    border: 1px solid #e6ebf2;
    border-radius: 10px;
    color: #334155;
    text-decoration: none;
    background: #fafcff;
    transition: all 0.2s ease;
}

.quick-item:hover {
    border-color: #bfdbfe;
    color: #1d4ed8;
    background: #f0f7ff;
}

.quick-item i {
    color: #2563eb;
}

@media (max-width: 1024px) {
    .media-grid {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    .quick-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 768px) {
    .home-wrap {
        width: min(1120px, 100% - 20px);
        gap: 14px;
    }

    .home-hero {
        display: block;
    }

    .home-hero-actions {
        margin-top: 12px;
    }

    .home-stats {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .media-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
</style>

<?php include 'includes/footer.php'; ?>
