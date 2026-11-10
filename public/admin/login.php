<?php
/**
 * 后台登录页（独立于 AdminCore 布局）。
 */
require_once __DIR__ . '/bootstrap.php';

if (admin_logged_in()) {
    header('Location: index.php');
    exit;
}

$err = '';
$locked = admin_login_locked_remaining();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($locked > 0) {
        $err = '尝试次数过多，请 ' . ceil($locked / 60) . ' 分钟后再试。';
    } else {
        $user = trim((string)($_POST['username'] ?? ''));
        $pw   = (string)($_POST['password'] ?? '');
        if (admin_verify_credentials($user, $pw)) {
            admin_clear_login_attempts();
            session_regenerate_id(true);
            $_SESSION['admin_ok'] = true;
            $_SESSION['admin_at'] = time();
            $_SESSION['admin_user'] = $user !== '' ? $user : ($GLOBALS['ADMIN_CFG']['admin_user'] ?: 'Admin');

            $goto = (string)($_POST['goto'] ?? '');
            if (!preg_match('/^[A-Za-z0-9_\-]+\.php$/', $goto) || $goto === 'login.php') {
                $goto = 'index.php';
            }
            header('Location: ' . $goto);
            exit;
        }
        admin_record_failed_login();
        $locked = admin_login_locked_remaining();
        $err = $locked > 0
            ? '账户或密码错误，已临时锁定 ' . ceil($locked / 60) . ' 分钟。'
            : '账户或密码错误。';
    }
}

$goto = preg_match('/^[A-Za-z0-9_\-]+\.php$/', (string)($_GET['goto'] ?? '')) ? (string)$_GET['goto'] : 'index.php';
$pwdEmpty = (string)($ADMIN_CFG['password_hash'] ?? '') === '';
$csrf = admin_csrf_token();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登录 - Billfish 后台</title>
    <link rel="icon" type="image/webp" href="/assets/images/logo.webp">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #1f2430; }
        .login-card { width: 100%; max-width: 380px; background: #fff; border-radius: 14px; padding: 32px 28px; box-shadow: 0 18px 50px rgba(0,0,0,.35); }
        .login-brand { font-weight: 800; font-size: 1.25rem; }
        .login-sub { color: #888; font-size: .85rem; margin-bottom: 24px; }
    </style>
</head>
<body>
<div class="login-card">
    <div class="text-center mb-3">
        <div class="login-brand d-flex align-items-center justify-content-center gap-2">
            <img src="/assets/images/logo.webp" alt="logo" style="height:32px;width:32px;border-radius:6px;">
            Billfish 后台
        </div>
        <div class="login-sub">素材资源库管理控制台</div>
    </div>

    <?php if ($pwdEmpty): ?>
        <div class="alert alert-warning small">
            尚未配置登录密码。请创建 <code>admin/config.local.php</code>，
            用 <code>php -r "echo password_hash('密码', PASSWORD_DEFAULT);"</code> 生成哈希后填入。
        </div>
    <?php endif; ?>

    <?php if ($err): ?><div class="alert alert-danger small"><?= h($err) ?></div><?php endif; ?>

    <form method="post" action="login.php">
        <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
        <input type="hidden" name="goto" value="<?= h($goto) ?>">
        <div class="mb-3">
            <label class="form-label small">账户</label>
            <input type="text" name="username" class="form-control" autocomplete="username" autofocus>
        </div>
        <div class="mb-3">
            <label class="form-label small">密码</label>
            <input type="password" name="password" class="form-control" autocomplete="current-password">
        </div>
        <button type="submit" class="btn btn-primary w-100">登录</button>
    </form>
    <div class="text-center mt-3">
        <a href="/index.php" class="small text-decoration-none"><i class="fas fa-arrow-left me-1"></i>返回前台</a>
    </div>
</div>
</body>
</html>
