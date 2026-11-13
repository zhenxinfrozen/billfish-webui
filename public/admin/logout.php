<?php
/**
 * 退出登录：清空会话并回到登录页。
 */
require_once __DIR__ . '/bootstrap.php';

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = admin_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'] ?? '', $p['secure'], $p['httponly']);
}
session_destroy();

header('Location: login.php');
exit;
