<?php
/**
 * Billfish Web Manager 后台配置（示例）
 *
 * 首次使用：把本文件复制/改名为 config.local.php，并填写密码哈希。
 * 生成哈希：php -r 'echo password_hash("你的密码", PASSWORD_DEFAULT);'
 * config.local.php 已在 .gitignore 中，不会随仓库外发。
 *
 * 纯 PHP + 文件，无数据库；建议仅在内网 / Tailscale / 反代鉴权后暴露。
 */
return [
    // 登录账户名；留空则退化为仅密码登录
    'admin_user'    => '',
    // password_hash(..., PASSWORD_DEFAULT) 结果；留空则后台提示如何生成
    'password_hash' => '',
    // 会话名（独立于前台）与登录有效期（秒）
    'session_name' => 'BFADMIN_SID',
    'login_ttl'    => 7 * 24 * 3600,
    // 同一 IP 连续失败达到上限后锁定秒数（防暴力破解）
    'login_max_attempts' => 8,
    'login_lock_seconds' => 15 * 60,
    // 写文件前自动备份目录
    'backup_dir'   => __DIR__ . '/backups',
    // 单次允许保留的备份份数（超出清理最旧）
    'backup_keep'  => 40,
];
