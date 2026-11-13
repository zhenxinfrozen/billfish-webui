<?php
/**
 * 前台公共头部 - 基于 SharedLayout\SiteHeader 通用布局组件
 *
 * 页面契约（沿用旧版，业务页面无需改动）：
 *   $pageTitle   string  页面标题
 *   $currentPage string  当前页标识（预留，激活态由组件按 URL 自动判定）
 *   $extraCss    string  额外 CSS（<link> 片段），在 </head> 前输出
 *
 * 套件源码位于仓库根 shared-layout/，通过 public/shared-layout junction 暴露静态资源。
 */

require_once __DIR__ . '/../../shared-layout/src/SiteHeader.php';

use SharedLayout\SiteHeader;

// 站点品牌/图标配置（后台「网站设置」落盘，缺字段时用当前项目默认值兜底）
$siteSettingsFile = __DIR__ . '/site_settings.php';
$ss = is_file($siteSettingsFile) ? (require $siteSettingsFile) : [];
$brandText  = $ss['site_name'] ?? 'Billfish Web Manager';
$taglineText = $ss['tagline'] ?? '素材资源库';
$logoUrl    = $ss['site_icon'] ?? '/assets/images/logo.webp';
$logoRel    = parse_url($logoUrl, PHP_URL_PATH);
$logoPath   = $logoRel ? __DIR__ . '/..' . $logoRel : null;

// 前台导航（标签 => 路由）
$frontNav = [
    '首页'   => '/index.php',
    '浏览'   => '/browse.php',
    '文档'   => '/docs-ui.php',
    '文档管理' => '/docs-admin.php',
    '工具'   => '/tools-ui.php',
];

// 版本徽章（config.php 已通过 git 探测分支/commit，失败时退化为静态版本号）
$headerBadges = [];
if (defined('BILLFISH_WEB_VERSION')) {
    $headerBadges[] = BILLFISH_WEB_VERSION;
}

$headerTitle = isset($pageTitle) ? $pageTitle . ' - ' . $brandText : $brandText;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($headerTitle) ?></title>
    <link rel="icon" type="image/webp" href="/assets/images/logo.webp">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
    <?php if (isset($extraCss)) echo $extraCss; ?>
</head>
<body>
<?php
echo SiteHeader::render([
    'brand'      => $brandText,
    'tagline'    => $taglineText,
    'badges'     => $headerBadges,
    'logo'       => $logoUrl,
    'logo_file'  => $logoPath,
    'home_url'   => '/index.php',
    'nav'        => $frontNav,
    'assets_url' => '/shared-layout/assets',
]);
?>
