<?php
/**
 * 后台资料库配置 API（受保护）。
 * 与前台 /api/library-config.php 功能对等，但必须登录且写操作携带 CSRF。
 */
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../library-manager.php';

admin_require_login();

header('Content-Type: application/json; charset=utf-8');
// 后台同站访问，不开放跨域；如需 API 调试请走登录后的浏览器会话。

$action  = $_GET['action'] ?? $_POST['action'] ?? 'list';
$manager = new AdminLibraryManager();

function admin_read_json_body(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode((string)$raw, true);
    return is_array($data) ? $data : [];
}

switch ($action) {
    case 'list':
        echo json_encode($manager->listLibraries());
        break;

    case 'add':
        admin_csrf_check();
        echo json_encode($manager->addLibrary(admin_read_json_body()));
        break;

    case 'switch':
        admin_csrf_check();
        $body = admin_read_json_body();
        echo json_encode($manager->switchLibrary((string)($body['id'] ?? '')));
        break;

    case 'delete':
        admin_csrf_check();
        $body = admin_read_json_body();
        echo json_encode($manager->deleteLibrary((string)($body['id'] ?? '')));
        break;

    case 'scan_nas':
        admin_csrf_check();
        $body = admin_read_json_body();
        echo json_encode($manager->scanNAS((string)($body['path'] ?? '')));
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => '不支持的操作']);
        break;
}
