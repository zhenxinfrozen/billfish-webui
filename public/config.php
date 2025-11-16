<?php
/**
 * Billfish Web Manager 配置文件
 */

// 获取当前Git分支名称
function getCurrentGitBranch() {
    $branch = 'unknown';
    if (function_exists('exec')) {
        $output = [];
        $returnCode = 0;
        exec('git rev-parse --abbrev-ref HEAD 2>nul', $output, $returnCode);
        if ($returnCode === 0 && !empty($output)) {
            $branch = trim($output[0]);
        }
    }
    return $branch;
}

// 版本信息 - 动态读取Git分支
$currentBranch = getCurrentGitBranch();
define('BILLFISH_WEB_VERSION', 'Git-' . $currentBranch);
define('BILLFISH_WEB_BUILD_DATE', date('Y-m-d'));

// Billfish 资源库路径
define('BILLFISH_PATH', 'S:/OneDrive-irm/Bill-Eagle/Bill-Storyboard');

// 数据库路径
define('BILLFISH_DB', BILLFISH_PATH . '\.bf\billfish.db');
define('SUMMARY_DB', BILLFISH_PATH . '\.bf\summary_v2.db');

// 预览图片路径
define('PREVIEW_PATH', BILLFISH_PATH . '\.bf\.preview');

// Web 访问路径配置
define('WEB_PREVIEW_URL', 'preview.php?file=');

// 支持的文件类型
define('SUPPORTED_VIDEO_TYPES', ['mp4', 'webm', 'avi', 'mov', 'mkv']);
define('SUPPORTED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp']);

// 分页设置
define('FILES_PER_PAGE', 50);

// 错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 时区设置
date_default_timezone_set('Asia/Shanghai');
?>