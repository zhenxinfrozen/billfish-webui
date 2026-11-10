<?php
/**
 * 前台公共底部 - 基于 SharedLayout\SiteFooter 通用布局组件
 *
 * 页面契约：$extraJs（<script> 片段）在底部脚本区输出。
 */

require_once __DIR__ . '/../../shared-layout/src/SiteFooter.php';

use SharedLayout\SiteFooter;

// 站点品牌/图标配置（与头部同源，缺字段时兜底）
$siteSettingsFile = __DIR__ . '/site_settings.php';
$ss = is_file($siteSettingsFile) ? (require $siteSettingsFile) : [];
$brandText  = $ss['site_name'] ?? 'Billfish Web Manager';
$logoUrl    = $ss['site_icon'] ?? '/assets/images/logo.webp';
$logoRel    = parse_url($logoUrl, PHP_URL_PATH);
$logoPath   = $logoRel ? __DIR__ . '/..' . $logoRel : null;
$siteDesc   = $ss['site_description'] ?? '浏览、预览与维护 Billfish 素材资源';

$footerNav = [
    '首页'   => '/index.php',
    '浏览'   => '/browse.php',
    '文档'   => '/docs-ui.php',
    '工具'   => '/tools-ui.php',
    '管理后台' => '/admin/',
];

$footerVersion = defined('BILLFISH_WEB_VERSION') ? BILLFISH_WEB_VERSION : '0.1.0';
$footerBuild   = defined('BILLFISH_WEB_BUILD_DATE') ? BILLFISH_WEB_BUILD_DATE : date('Y-m-d');
?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/main.js"></script>
    <?php if (isset($extraJs)) echo $extraJs; ?>
<?php
echo SiteFooter::render([
    'brand'       => $brandText,
    'logo'        => $logoUrl,
    'logo_file'   => $logoPath,
    'description' => [$siteDesc, "版本 {$footerVersion} · 构建 {$footerBuild}"],
    'nav'         => $footerNav,
    'copyright'   => '© ' . date('Y') . ' rzx.me',
    'powered_by'  => 'Powered by Billfish Web Manager',
    'home_url'    => '/index.php',
    'assets_url'  => '/shared-layout/assets',
]);
?>
</body>
</html>
