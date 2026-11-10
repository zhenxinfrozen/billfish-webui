<?php
/**
 * 后台控制台仪表盘。
 */
require_once __DIR__ . '/bootstrap.php';
require __DIR__ . '/ui.php';
admin_require_login();

require_once PUBLIC_DIR . '/includes/BillfishManagerV3.php';
require_once PUBLIC_DIR . '/includes/ToolManager.php';
require_once PUBLIC_DIR . '/includes/DocumentManager.php';

// 当前资料库统计
$stats = ['total_files' => 0, 'video_count' => 0, 'total_size_gb' => 0, 'tag_count' => 0];
try {
    $manager = new BillfishManagerV3(BILLFISH_PATH);
    $stats   = array_merge($stats, $manager->getLibraryStats() ?: []);
} catch (Throwable $e) {
    $stats['error'] = $e->getMessage();
}

// 资料库配置数
$libs      = admin_read_json(LIBRARIES_FILE);
$libCount  = count($libs['libraries'] ?? []);

// 工具数
$toolMgr   = new ToolManager(PUBLIC_DIR . '/tools');
$toolCount = 0;
foreach ($toolMgr->getCategories() as $cat) {
    $toolCount += count($cat['tools'] ?? []);
}

// 文档数
$docMgr    = new DocumentManager();
$docCount  = 0;
foreach ($docMgr->getSections() as $section) {
    $docCount += count($section['documents'] ?? []);
}

$pwdEmpty = (string)($ADMIN_CFG['password_hash'] ?? '') === '';

admin_head('控制台', 'dashboard');
?>

<?php if ($pwdEmpty): ?>
<div class="alert alert-danger d-flex align-items-center gap-2 m-4 mb-0" role="alert">
    <i data-feather="alert-triangle"></i>
    <div>尚未配置后台登录密码。请创建 <code>admin/config.local.php</code> 并填入 <code>password_hash</code>，否则任何人都可访问后台。</div>
</div>
<?php endif; ?>

<div class="row g-3 p-4 pb-0">
    <div class="col-xl-3 col-md-6">
        <div class="content-card p-3 d-flex align-items-center gap-3 h-100">
            <div class="p-2 rounded bg-primary bg-opacity-10 text-primary"><i data-feather="folder"></i></div>
            <div>
                <div class="h3 mb-0 fw-bold"><?= (int)$stats['total_files'] ?></div>
                <div class="text-secondary small">当前库文件</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="content-card p-3 d-flex align-items-center gap-3 h-100">
            <div class="p-2 rounded bg-success bg-opacity-10 text-success"><i data-feather="film"></i></div>
            <div>
                <div class="h3 mb-0 fw-bold"><?= (int)$stats['video_count'] ?></div>
                <div class="text-secondary small">视频素材</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="content-card p-3 d-flex align-items-center gap-3 h-100">
            <div class="p-2 rounded bg-info bg-opacity-10 text-info"><i data-feather="database"></i></div>
            <div>
                <div class="h3 mb-0 fw-bold"><?= (int)$libCount ?></div>
                <div class="text-secondary small">已配置资料库</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="content-card p-3 d-flex align-items-center gap-3 h-100">
            <div class="p-2 rounded bg-warning bg-opacity-10 text-warning"><i data-feather="tag"></i></div>
            <div>
                <div class="h3 mb-0 fw-bold"><?= (int)$stats['tag_count'] ?></div>
                <div class="text-secondary small">标签数量</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 p-4">
    <div class="col-lg-8">
        <div class="content-card p-4">
            <h3 class="h5 mb-3 fw-bold d-flex align-items-center gap-2">
                <i data-feather="zap" class="text-warning"></i> 常用快捷操作
            </h3>
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-primary btn-sm d-inline-flex align-items-center gap-2" href="libraries.php">
                    <i data-feather="database"></i> 资料库配置
                </a>
                <a class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-2" href="docs.php">
                    <i data-feather="book-open"></i> 文档管理（<?= $docCount ?>）
                </a>
                <a class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-2" href="tools.php">
                    <i data-feather="tool"></i> 工具中心（<?= $toolCount ?>）
                </a>
                <a class="btn btn-outline-info btn-sm d-inline-flex align-items-center gap-2" href="system.php">
                    <i data-feather="info"></i> 系统信息
                </a>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="content-card p-4">
            <h3 class="h5 mb-3 fw-bold d-flex align-items-center gap-2">
                <i data-feather="shield" class="text-success"></i> 存储与备份说明
            </h3>
            <p class="text-secondary small mb-0 lh-base">
                本后台纯文件驱动，不使用数据库。资料库清单保存在 <code>libraries.json</code>；
                切换资料库改写 <code>config.php</code> 前，会自动在 <code>admin/backups/</code> 保留时间戳备份。
                前台仍保留一份公开的配置入口，后台为受登录与 CSRF 保护的受控副本。
            </p>
        </div>
    </div>
</div>

<?php admin_foot(); ?>
