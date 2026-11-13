<?php
/**
 * 后台 - 网站设置（复用 SharedAdmin site-settings 模板）。
 * 配置落盘 public/includes/site_settings.php，图标上传到 public/assets/images/。
 */
require_once __DIR__ . '/bootstrap.php';
require __DIR__ . '/ui.php';
admin_require_login();

$settingsFile = PUBLIC_DIR . '/includes/site_settings.php';

// 模板字段默认值（与 site-settings.php 对齐，缺字段时先补齐，避免被模板示例值污染）
$defaults = [
    'site_name'        => 'Billfish Web Manager',
    'tagline'          => '素材资源库',
    'site_description' => '浏览、预览与维护 Billfish 素材资源',
    'site_icon'        => '/assets/images/logo.webp',
    'primary_color'    => '#007bff',
    'header_bg'        => 'rgba(255,255,255,0.5)',
    'brand_color'      => '#007bff',
    'nav_text_color'   => '#495057',
    'show_version'     => true,
    'version_text'     => '',
    'use_git_branch'   => true,
    'git_branch_cache' => '',
];

$site_settings = is_file($settingsFile) ? (require $settingsFile) : [];
$site_settings = array_merge($defaults, is_array($site_settings) ? $site_settings : []);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_csrf_check();
}

// 保存回调：合并后原子写回配置文件（带时间戳备份）
$site_settings_save_cb = function (array $new) use ($settingsFile, $site_settings): bool {
    $merged = array_merge($site_settings, $new);
    $lines = [
        "<?php",
        "// 站点品牌与外观配置（后台「网站设置」自动生成，勿删除模板引用字段）",
        "",
        "return [",
    ];
    foreach ($merged as $key => $value) {
        if (is_bool($value)) {
            $lines[] = "    '$key' => " . ($value ? 'true' : 'false') . ",";
        } elseif (is_int($value)) {
            $lines[] = "    '$key' => $value,";
        } else {
            $lines[] = "    '$key' => '" . addslashes((string)$value) . "',";
        }
    }
    $lines[] = "];";
    $lines[] = "";
    return admin_save_with_backup($settingsFile, implode("\n", $lines));
};

// Git 分支检测（main/master 不显示）
$detectBranch = function (string $root): ?string {
    $head = $root . '/.git/HEAD';
    if (!is_file($head)) return null;
    $content = trim((string)file_get_contents($head));
    if (strpos($content, 'ref: refs/heads/') === 0) {
        $branch = substr($content, strlen('ref: refs/heads/'));
        return in_array($branch, ['main', 'master'], true) ? null : $branch;
    }
    return null;
};
$git_branch_display = $detectBranch(ROOT);
$git_branch_cache   = (string)($site_settings['git_branch_cache'] ?? '');

$icon_upload_dir        = PUBLIC_DIR . '/assets/images';
$icon_upload_url_prefix = '/assets/images/';
$csrf_token             = admin_csrf_token();

\SharedAdmin\AdminCore::renderHeader([
    'title'        => '网站设置',
    'subtitle'      => '自定义站点名称、图标、配色与版本显示',
    'current_page' => 'settings',
]);

require ROOT . '/shared-admin/src/views/pages/site-settings.php';

\SharedAdmin\AdminCore::renderFooter();
