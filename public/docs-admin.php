<?php
/**
 * 文档管理页面 - 显示动态文档扫描状态
 */

require_once 'config.php';
require_once 'includes/DocumentManager.php';

$currentPage = 'docs-admin.php';
$pageTitle = '文档管理';

$docManager = new DocumentManager();
$sections = $docManager->getSections();
$docsRoot = realpath(__DIR__ . '/../docs');

$totalDocuments = 0;
$autoSections = 0;
foreach ($sections as $section) {
    $totalDocuments += count($section['documents'] ?? []);
    if (isset($section['auto_discovered'])) {
        $autoSections++;
    }
}

include 'includes/header.php';
?>

<main class="docs-admin-page py-4">
    <div class="docs-admin-wrap">
        <section class="admin-hero mb-3">
            <div>
                <h1><i class="fas fa-cogs"></i> 文档管理</h1>
                <p>统一管理文档分类、扫描状态与归属质量，确保文档中心结构稳定可维护。</p>
            </div>
            <div class="hero-actions">
                <a href="docs-ui.php" class="btn btn-primary btn-sm"><i class="fas fa-book"></i> 查看文档</a>
                <a href="docs-ui.php?q=README" class="btn btn-outline-secondary btn-sm"><i class="fas fa-search"></i> 快速检索</a>
            </div>
        </section>

        <section class="admin-metrics mb-3">
            <div class="metric-tile metric-blue">
                <span class="metric-label">分类总数</span>
                <strong class="metric-value"><?= count($sections) ?></strong>
            </div>
            <div class="metric-tile metric-cyan">
                <span class="metric-label">文档总数</span>
                <strong class="metric-value"><?= $totalDocuments ?></strong>
            </div>
            <div class="metric-tile metric-green">
                <span class="metric-label">自动分类</span>
                <strong class="metric-value"><?= $autoSections ?></strong>
            </div>
        </section>

        <div class="alert alert-info admin-alert mb-3">
            <i class="fas fa-info-circle"></i>
            <strong>动态文档功能已启用：</strong> 系统会自动扫描 docs 目录中的 Markdown 文件，并按分类规则归档。
        </div>

        <section class="admin-panel mb-3">
            <div class="panel-head">
                <h2><i class="fas fa-layer-group"></i> 分类概览</h2>
            </div>
            <div class="section-grid">
                <?php foreach ($sections as $section): ?>
                <article class="section-tile">
                    <div class="tile-top">
                        <h3><?= $section['icon'] ?> <?= htmlspecialchars($section['name']) ?></h3>
                        <?php if (isset($section['auto_discovered'])): ?>
                        <span class="badge bg-success">自动发现</span>
                        <?php else: ?>
                        <span class="badge bg-primary">配置文件</span>
                        <?php endif; ?>
                    </div>
                    <p><?= htmlspecialchars($section['description']) ?></p>
                    <div class="tile-meta"><strong><?= count($section['documents'] ?? []) ?></strong> 篇文档</div>
                </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="admin-panel mb-3">
            <div class="panel-head">
                <h2><i class="fas fa-list"></i> 详细文档列表</h2>
            </div>
            <div class="panel-body">
                <?php foreach ($sections as $section): ?>
                <div class="section-block">
                    <div class="section-block-head">
                        <h4><?= $section['icon'] ?> <?= htmlspecialchars($section['name']) ?></h4>
                        <span class="text-muted small"><?= count($section['documents'] ?? []) ?> 篇文档</span>
                    </div>

                    <?php if (empty($section['documents'])): ?>
                    <p class="text-muted mb-0">暂无文档</p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle admin-table">
                            <thead>
                                <tr>
                                    <th>文档标题</th>
                                    <th>文件名</th>
                                    <th>来源目录</th>
                                    <th>状态</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($section['documents'] as $doc): ?>
                                <?php
                                $sourceDir = $doc['source_dir'] ?? '';
                                if ($section['id'] === 'project') {
                                    $filePath = $docsRoot . DIRECTORY_SEPARATOR . $doc['file'];
                                } elseif (!empty($sourceDir)) {
                                    $filePath = $docsRoot . DIRECTORY_SEPARATOR . $sourceDir . DIRECTORY_SEPARATOR . $doc['file'];
                                } else {
                                    $filePath = $docsRoot . DIRECTORY_SEPARATOR . $section['id'] . DIRECTORY_SEPARATOR . $doc['file'];
                                }
                                $exists = $filePath && file_exists($filePath);
                                ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($doc['title']) ?></strong>
                                        <?php if (isset($doc['badge'])): ?>
                                        <span class="badge bg-<?= isset($doc['auto_discovered']) ? 'success' : 'info' ?> ms-1">
                                            <?= htmlspecialchars($doc['badge']) ?>
                                        </span>
                                        <?php endif; ?>
                                    </td>
                                    <td><code><?= htmlspecialchars($doc['file']) ?></code></td>
                                    <td><code><?= htmlspecialchars($sourceDir ?: ($section['id'] === 'project' ? '[root]' : $section['id'])) ?></code></td>
                                    <td>
                                        <?php if ($exists): ?>
                                        <span class="badge bg-success">存在</span>
                                        <small class="text-muted ms-1"><?= number_format(filesize($filePath) / 1024, 1) ?> KB</small>
                                        <?php else: ?>
                                        <span class="badge bg-danger">缺失</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="docs-ui.php?section=<?= urlencode($section['id']) ?>&file=<?= urlencode($doc['file']) ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i> 查看
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="admin-panel">
            <div class="panel-head">
                <h2><i class="fas fa-question-circle"></i> 如何添加新文档</h2>
            </div>
            <div class="panel-body">
                <ol class="mb-0">
                    <li>在 <code>docs/</code> 下选择目标分类目录（如 <code>development/</code>、<code>setup/</code>）。</li>
                    <li>将 <code>.md</code> 文件放入目录并保持文件名语义化。</li>
                    <li>刷新文档中心，系统会自动扫描并更新分类。</li>
                    <li>标题建议使用 H1，描述建议使用首段文本，便于自动提取。</li>
                </ol>
                <div class="alert alert-warning mt-3 mb-0">
                    <i class="fas fa-lightbulb"></i>
                    <strong>提示：</strong> 配置文件中定义的文档项优先级更高，会覆盖同名自动发现项。
                </div>
            </div>
        </section>
    </div>
</main>

<style>
.docs-admin-page {
    background: radial-gradient(circle at 10% -10%, rgba(59, 130, 246, 0.12), transparent 35%), #f4f7fb;
}

.docs-admin-wrap {
    width: min(1160px, 100% - 32px);
    margin: 0 auto;
    display: grid;
    gap: 14px;
}

.admin-hero,
.admin-panel {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
}

.admin-hero {
    padding: 18px 20px;
    display: flex;
    justify-content: space-between;
    align-items: end;
    gap: 16px;
}

.admin-hero h1 {
    margin: 0 0 6px;
    font-size: 1.9rem;
    color: #0f172a;
}

.admin-hero p {
    margin: 0;
    color: #64748b;
}

.hero-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.admin-metrics {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
}

.metric-tile {
    border-radius: 12px;
    padding: 14px 16px;
    color: #fff;
}

.metric-blue { background: linear-gradient(135deg, #1d4ed8 0%, #1e3a8a 100%); }
.metric-cyan { background: linear-gradient(135deg, #0891b2 0%, #0e7490 100%); }
.metric-green { background: linear-gradient(135deg, #059669 0%, #166534 100%); }

.metric-label {
    display: block;
    font-size: 0.82rem;
    opacity: 0.9;
}

.metric-value {
    display: block;
    font-size: 1.5rem;
    line-height: 1.2;
}

.admin-alert {
    border-radius: 12px;
    border: 1px solid #bcd9ff;
    background: linear-gradient(180deg, #ecf5ff 0%, #deefff 100%);
    color: #0f4a7d;
}

.panel-head {
    padding: 14px 16px;
    border-bottom: 1px solid #e8edf5;
    background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
}

.panel-head h2 {
    margin: 0;
    font-size: 1.06rem;
    font-weight: 700;
    color: #1e293b;
}

.panel-body {
    padding: 12px 16px 16px;
}

.section-grid {
    padding: 14px 16px 16px;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 12px;
}

.section-tile {
    border: 1px solid #e6edf7;
    border-radius: 12px;
    background: #f9fcff;
    padding: 12px 13px;
}

.tile-top {
    display: flex;
    justify-content: space-between;
    align-items: start;
    gap: 8px;
}

.section-tile h3 {
    font-size: 1rem;
    margin: 0;
    color: #0f172a;
}

.section-tile p {
    margin: 7px 0 8px;
    font-size: 0.88rem;
    color: #64748b;
}

.tile-meta {
    color: #334155;
    font-size: 0.88rem;
}

.section-block + .section-block {
    margin-top: 14px;
    border-top: 1px dashed #e5eaf3;
    padding-top: 14px;
}

.section-block-head {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    margin-bottom: 8px;
}

.section-block-head h4 {
    margin: 0;
    font-size: 0.98rem;
    color: #1d4ed8;
}

.admin-table thead th {
    white-space: nowrap;
    font-size: 0.82rem;
    color: #64748b;
    border-bottom-width: 1px;
}

.admin-table td {
    vertical-align: middle;
}

@media (max-width: 900px) {
    .admin-hero {
        align-items: start;
        flex-direction: column;
    }

    .admin-metrics {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 576px) {
    .docs-admin-wrap {
        width: min(1160px, 100% - 20px);
    }
}
</style>

<?php include 'includes/footer.php'; ?>
