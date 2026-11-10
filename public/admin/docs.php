<?php
/**
 * 后台 - 文档管理（只读副本，对应前台 /docs-admin.php）。
 * 文档增删通过在服务器 docs/ 目录落地 Markdown 文件完成，后台仅做扫描状态与归属检查。
 */
require_once __DIR__ . '/bootstrap.php';
require __DIR__ . '/ui.php';
admin_require_login();

require_once PUBLIC_DIR . '/includes/DocumentManager.php';

$docManager = new DocumentManager();
$sections   = $docManager->getSections();
$docsRoot   = realpath(DOCS_DIR);

$totalDocuments = 0;
$autoSections   = 0;
foreach ($sections as $section) {
    $totalDocuments += count($section['documents'] ?? []);
    if (isset($section['auto_discovered'])) $autoSections++;
}

admin_head('文档管理', 'docs', [
    ['title' => '前台文档中心', 'url' => '/docs-ui.php', 'icon' => 'external-link', 'target' => '_blank'],
]);
?>

<div class="row g-3 p-4 pb-0">
    <div class="col-md-4">
        <div class="content-card p-3 text-center">
            <div class="h3 mb-0 fw-bold"><?= count($sections) ?></div>
            <div class="text-secondary small">分类总数</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="content-card p-3 text-center">
            <div class="h3 mb-0 fw-bold"><?= $totalDocuments ?></div>
            <div class="text-secondary small">文档总数</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="content-card p-3 text-center">
            <div class="h3 mb-0 fw-bold"><?= $autoSections ?></div>
            <div class="text-secondary small">自动分类</div>
        </div>
    </div>
</div>

<div class="p-4">
    <div class="content-card p-4">
        <h3 class="h5 mb-3 fw-bold">分类与文档概览</h3>
        <?php foreach ($sections as $section): ?>
        <div class="section-block mb-4">
            <div class="d-flex justify-content-between align-items-baseline mb-2">
                <h4 class="mb-0 text-primary"><?= h($section['icon'] . ' ' . $section['name']) ?></h4>
                <span class="text-secondary small">
                    <?= count($section['documents'] ?? []) ?> 篇
                    <?php if (isset($section['auto_discovered'])): ?>
                        <span class="badge bg-success ms-1">自动发现</span>
                    <?php else: ?>
                        <span class="badge bg-primary ms-1">配置文件</span>
                    <?php endif; ?>
                </span>
            </div>

            <?php if (empty($section['documents'])): ?>
                <p class="text-secondary mb-0">暂无文档</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr><th>标题</th><th>文件名</th><th>来源目录</th><th>状态</th><th>操作</th></tr>
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
                            <td><strong><?= h($doc['title']) ?></strong></td>
                            <td><code><?= h($doc['file']) ?></code></td>
                            <td><code><?= h($sourceDir ?: ($section['id'] === 'project' ? '[root]' : $section['id'])) ?></code></td>
                            <td>
                                <?php if ($exists): ?>
                                    <span class="badge bg-success">存在</span>
                                    <small class="text-secondary ms-1"><?= number_format(filesize($filePath) / 1024, 1) ?> KB</small>
                                <?php else: ?>
                                    <span class="badge bg-danger">缺失</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="/docs-ui.php?section=<?= urlencode($section['id']) ?>&file=<?= urlencode($doc['file']) ?>"
                                   class="btn btn-sm btn-outline-primary" target="_blank">查看</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>

        <div class="alert alert-warning small mb-0">
            <i data-feather="info"></i>
            后台为只读视图：新增文档需在服务器 <code>docs/</code> 对应分类目录放入 <code>.md</code> 文件后刷新；配置文件定义的文档优先级高于同名自动发现项。
        </div>
    </div>
</div>

<?php admin_foot(); ?>
