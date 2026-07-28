<?php
/**
 * 兼容入口：旧 status.php 已迁移到工具中心的系统基础检查页面。
 */

header('Location: /tools/web-ui/system-health-check.php', true, 302);
exit;
