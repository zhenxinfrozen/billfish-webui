<?php
/**
 * Billfish Web Manager 后台引导：路径、业务配置、登录态、CSRF、限流、通用读写助手。
 * 所有 admin/*.php 顶部：require_once __DIR__ . '/bootstrap.php';
 *
 * 鉴权 / 会话 / 备份范式沿用 hellboy_wiki 后台（单用户 password_hash + 会话 + 失败限流），
 * 业务路径按本项目 docroot=public/ 的结构重新绑定。
 */
declare(strict_types=1);
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

define('ADMIN_DIR', __DIR__);
define('PUBLIC_DIR', dirname(ADMIN_DIR));
define('ROOT', dirname(PUBLIC_DIR));

// ---- 业务配置（BILLFISH_PATH / 版本号等常量）----
require_once PUBLIC_DIR . '/config.php';

// ---- 业务数据文件路径 ----
define('LIBRARIES_FILE', PUBLIC_DIR . '/libraries.json');
define('TOOLS_CONFIG_FILE', PUBLIC_DIR . '/tools/config.json');
define('DOCS_DIR', ROOT . '/docs');

// ---- 后台私有配置（本地，不回灌 git）----
$cfgFile = ADMIN_DIR . '/config.local.php';
$ADMIN_CFG = is_file($cfgFile) ? (require $cfgFile) : (require ADMIN_DIR . '/config.example.php');
if (!is_array($ADMIN_CFG)) $ADMIN_CFG = [];
$ADMIN_CFG += [
    'admin_user'         => '',
    'password_hash'      => '',
    'session_name'       => 'BFADMIN_SID',
    'login_ttl'          => 7 * 24 * 3600,
    'login_max_attempts' => 8,
    'login_lock_seconds'  => 15 * 60,
    'backup_dir'         => ADMIN_DIR . '/backups',
    'backup_keep'        => 40,
];

define('BACKUP_DIR', $ADMIN_CFG['backup_dir']);

// ---- 会话 / 登录 ----
function admin_is_https(): bool {
    if (($_SERVER['HTTPS'] ?? '') && strtolower((string)$_SERVER['HTTPS']) !== 'off') return true;
    if (strcasecmp((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''), 'https') === 0) return true;
    if ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443) return true;
    return false;
}

function admin_cookie_params(): array {
    return ['httponly' => true, 'samesite' => 'Lax', 'path' => '/', 'secure' => admin_is_https()];
}

function admin_session_setup(array $cfg): void {
    session_name($cfg['session_name']);
    session_set_cookie_params(admin_cookie_params());
    session_start();
}
admin_session_setup($ADMIN_CFG);

function admin_logged_in(): bool {
    return !empty($_SESSION['admin_ok'])
        && (($_SESSION['admin_at'] ?? 0) + ($GLOBALS['ADMIN_CFG']['login_ttl'] ?? 604800)) > time();
}

function admin_login_page_refresh(): void {
    if (!admin_logged_in()) {
        session_regenerate_id(true);
    }
}

function admin_require_login(): void {
    if (!admin_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

// ---- CSRF ----
function admin_csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}

function admin_csrf_check(): void {
    if (!hash_equals(admin_csrf_token(), (string)($_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')))) {
        http_response_code(419);
        exit('CSRF token 无效，请返回重试。');
    }
}

// ---- 登录失败限流（按来源 IP，落盘系统临时目录，原子读写）----
function admin_client_ip(): string {
    return (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown');
}

function admin_attempt_file(): string {
    return rtrim(sys_get_temp_dir(), '/') . '/bf_admin_attempts_' . hash('sha256', admin_client_ip()) . '.json';
}

function admin_login_locked_remaining(): int {
    $max  = (int)($GLOBALS['ADMIN_CFG']['login_max_attempts'] ?? 8);
    $lock = (int)($GLOBALS['ADMIN_CFG']['login_lock_seconds'] ?? 900);
    $f = admin_attempt_file();
    if (!is_file($f)) return 0;
    $d = json_decode((string)@file_get_contents($f), true);
    $count = (int)($d['count'] ?? 0);
    $first = (int)($d['first'] ?? 0);
    if ($count < $max) return 0;
    $elapsed = time() - $first;
    if ($elapsed >= $lock) { @unlink($f); return 0; }
    return $lock - $elapsed;
}

function admin_record_failed_login(): void {
    $lock = (int)($GLOBALS['ADMIN_CFG']['login_lock_seconds'] ?? 900);
    $f = admin_attempt_file();
    $now = time();
    $d = is_file($f) ? (json_decode((string)@file_get_contents($f), true) ?: []) : [];
    $count = (int)($d['count'] ?? 0);
    $first = (int)($d['first'] ?? 0);
    if ($first === 0 || ($now - $first) >= $lock) { $count = 0; $first = $now; }
    $count++;
    $tmp = $f . '.tmp-' . bin2hex(random_bytes(4));
    @file_put_contents($tmp, json_encode(['count' => $count, 'first' => $first, 'last' => $now]));
    @rename($tmp, $f);
}

function admin_clear_login_attempts(): void { @unlink(admin_attempt_file()); }

function admin_verify_credentials(string $user, string $pw): bool {
    $cfg = $GLOBALS['ADMIN_CFG'];
    $hash = (string)($cfg['password_hash'] ?? '');
    if ($hash === '') return false;
    $wantUser = (string)($cfg['admin_user'] ?? '');
    if ($wantUser !== '' && !hash_equals($wantUser, $user)) return false;
    return password_verify($pw, $hash);
}

// ---- 输出 / 编码 ----
function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function admin_flash(?string $msg = null, string $kind = 'ok'): ?array {
    if ($msg !== null) { $_SESSION['flash'] = ['m' => $msg, 'k' => $kind]; return null; }
    $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f;
}

// ---- JSON 读写（带时间戳备份，UTF-8 不转义，原子落盘）----
function admin_read_json(string $path): array {
    if (!is_file($path)) return [];
    $j = json_decode((string)file_get_contents($path), true);
    return is_array($j) ? $j : [];
}

function admin_write_json(string $path, array $data): bool {
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_LINE_TERMINATORS);
    return $json !== false && admin_save_with_backup($path, $json . "\n");
}

function admin_save_with_backup(string $path, string $content): bool {
    if (!is_dir(dirname($path))) @mkdir(dirname($path), 0775, true);
    if (is_file($path)) admin_backup($path);
    $tmp = $path . '.tmp-' . bin2hex(random_bytes(4));
    if (file_put_contents($tmp, $content) === false) return false;
    return rename($tmp, $path);
}

function admin_backup(string $path): void {
    if (!is_file($path)) return;
    @mkdir(BACKUP_DIR, 0775, true);
    $rel = str_replace(DIRECTORY_SEPARATOR, '__', ltrim(str_replace(ROOT, '', $path), DIRECTORY_SEPARATOR));
    $stamp = date('Ymd-His');
    $dest = BACKUP_DIR . '/' . $rel . '.' . $stamp . '.bak';
    @copy($path, $dest);
    admin_backup_gc($rel);
}

function admin_backup_gc(string $relPrefix): void {
    $keep = (int)($GLOBALS['ADMIN_CFG']['backup_keep'] ?? 40);
    $list = glob(BACKUP_DIR . '/' . $relPrefix . '.*.bak') ?: [];
    usort($list, fn($a, $b) => filemtime($b) <=> filemtime($a));
    foreach (array_slice($list, $keep) as $old) @unlink($old);
}
