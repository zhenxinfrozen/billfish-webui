<?php
/**
 * 后台 - 标准模板与组件展厅（复用 SharedAdmin 内置 template-new 页面）。
 */
require_once __DIR__ . '/bootstrap.php';
require __DIR__ . '/ui.php';
admin_require_login();

\SharedAdmin\AdminCore::handleBuiltinPage('template-new');
