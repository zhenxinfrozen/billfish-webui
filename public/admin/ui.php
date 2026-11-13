<?php
/**
 * 后台门面：初始化 SharedAdmin\AdminCore，提供 admin_head / admin_foot 助手。
 * 依赖 bootstrap.php 已先行 require。
 */
declare(strict_types=1);

require_once ROOT . '/shared-admin/src/AdminCore.php';

use SharedAdmin\AdminCore;

$adminMenu = [
    ['type' => 'section', 'title' => '概览'],
    ['key' => 'dashboard', 'title' => '控制台', 'icon' => 'home', 'url' => 'index.php'],

    ['type' => 'section', 'title' => '资源与内容'],
    ['key' => 'libraries', 'title' => '资料库配置', 'icon' => 'database', 'url' => 'libraries.php'],
    ['key' => 'docs',      'title' => '文档管理', 'icon' => 'book-open', 'url' => 'docs.php'],
    ['key' => 'tools',     'title' => '工具中心', 'icon' => 'tool', 'url' => 'tools.php'],

    ['type' => 'section', 'title' => '系统'],
    ['key' => 'settings',  'title' => '网站设置', 'icon' => 'settings', 'url' => 'settings.php'],
    ['key' => 'template-new', 'title' => '标准模板', 'icon' => 'layout', 'url' => 'template-new.php'],
    ['key' => 'system',    'title' => '系统信息', 'icon' => 'info', 'url' => 'system.php'],
];

AdminCore::init([
    'site_title'    => 'Billfish 后台',
    'site_subtitle' => '素材资源库管理控制台',
    'logo'          => '/assets/images/logo.webp',
    'logo_file'     => ROOT . '/public/assets/images/logo.webp',
    'assets_url'    => '/shared-admin/assets',
    'home_url'      => '/index.php',
    'logout_url'    => 'logout.php',
    'current_user'  => [
        'name' => $_SESSION['admin_user'] ?? 'Admin',
        'role' => 'Administrator',
        'email' => '',
    ],
    'menu' => $adminMenu,
]);

/**
 * 渲染后台头部。
 * @param string $title 页面标题
 * @param string $current 菜单 key
 * @param array  $actions 顶栏操作按钮 [{title,url,icon,class,target}]
 * @param array  $extraCss 额外 CSS 路径
 */
function admin_head(string $title, string $current, array $actions = [], array $extraCss = []): void
{
    $flash = admin_flash();
    $flashArr = null;
    if ($flash) {
        $flashArr = [
            'type' => ($flash['k'] === 'err') ? 'err' : (($flash['k'] === 'warn') ? 'warn' : 'ok'),
            'msg'  => $flash['m'],
        ];
    }
    AdminCore::renderHeader([
        'title'        => $title,
        'current_page' => $current,
        'actions'      => $actions,
        'extra_css'    => $extraCss,
        'flash'        => $flashArr,
    ]);
}

/**
 * 渲染后台底部。
 * @param array  $extraJs 额外 JS 路径
 * @param string $inlineJs 内联脚本（DOMContentLoaded 内执行）
 */
function admin_foot(array $extraJs = [], string $inlineJs = ''): void
{
    AdminCore::renderFooter([
        'extra_js' => $extraJs,
        'inline_js' => $inlineJs,
    ]);
}
