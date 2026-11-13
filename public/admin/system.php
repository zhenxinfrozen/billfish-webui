<?php
/**
 * 后台 - 系统信息（复用 SharedAdmin 内置 system-info 页面）。
 */
require_once __DIR__ . '/bootstrap.php';
require __DIR__ . '/ui.php';
admin_require_login();

\SharedAdmin\AdminCore::handleBuiltinPage('system-info', [
    'title'        => '系统信息',
    'subtitle'     => '查看服务器与 PHP 运行环境指标',
]);
