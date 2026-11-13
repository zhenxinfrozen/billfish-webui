<?php
/**
 * 后台 - 工具中心（只读副本，对应前台 /tools-ui.php）。
 * 后台仅展示工具清单与 Web 入口；命令行/Python 执行仍在前台工具页与服务器命令行完成。
 */
require_once __DIR__ . '/bootstrap.php';
require __DIR__ . '/ui.php';
admin_require_login();

require_once PUBLIC_DIR . '/includes/ToolManager.php';

$toolManager = new ToolManager(PUBLIC_DIR . '/tools');
$categories  = $toolManager->getCategories();
$stats       = $toolManager->getStats() ?: ['total' => 0, 'by_type' => []];

admin_head('工具中心', 'tools', [
    ['title' => '前台工具页', 'url' => '/tools-ui.php', 'icon' => 'external-link', 'target' => '_blank'],
]);

$typeIcon = fn(string $type): string => match ($type) {
    'python' => 'fab fa-python text-primary',
    'php'    => 'fab fa-php text-secondary',
    default  => 'fas fa-globe text-success',
};
?>

<div class="row g-3 p-4 pb-0">
    <div class="col-md-4">
        <div class="content-card p-3 text-center">
            <div class="h3 mb-0 fw-bold"><?= (int)($stats['total'] ?? 0) ?></div>
            <div class="text-secondary small">工具总数</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="content-card p-3 text-center">
            <div class="h3 mb-0 fw-bold"><?= (int)($stats['by_type']['python'] ?? 0) ?></div>
            <div class="text-secondary small">Python</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="content-card p-3 text-center">
            <div class="h3 mb-0 fw-bold"><?= (int)($stats['by_type']['web'] ?? 0) ?></div>
            <div class="text-secondary small">Web 工具</div>
        </div>
    </div>
</div>

<?php foreach ($categories as $category): ?>
    <?php if (($category['id'] ?? '') === 'archived') continue; ?>
<div class="p-4 pt-3">
    <div class="content-card p-4">
        <h3 class="h5 mb-1 fw-bold"><?= h(($category['icon'] ?? '') . ' ' . $category['name']) ?></h3>
        <p class="text-secondary small mb-3"><?= h($category['description'] ?? '') ?></p>
        <div class="row g-3">
            <?php foreach ($category['tools'] ?? [] as $tool): ?>
            <div class="col-md-6 col-lg-4">
                <div class="content-card p-3 h-100">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="<?= $typeIcon($tool['type'] ?? '') ?>"></i>
                        <strong><?= h($tool['name']) ?></strong>
                        <span class="badge bg-secondary ms-auto"><?= strtoupper(h($tool['type'] ?? '')) ?></span>
                    </div>
                    <p class="text-secondary small mb-2"><?= h($tool['description'] ?? '') ?></p>
                    <small class="text-secondary"><code><?= h($tool['file'] ?? '') ?></code></small>
                    <?php if (!empty($tool['web_ui'])): ?>
                    <div class="mt-2">
                        <a href="/tools/<?= h($tool['web_ui']) ?>" class="btn btn-outline-primary btn-sm" target="_blank">
                            <i data-feather="external-link"></i> 打开 Web 工具
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endforeach; ?>

<?php admin_foot(); ?>
