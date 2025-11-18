<?php
/**
 * Billfish Web Manager 配置文件
 */

// 获取版本信息
function getVersion() {
    // 优先使用固定版本号（发布时设置）
    $staticVersion = 'v0.1.0'; // 发布时更新此值
    
    // 如果在Git仓库中，尝试获取动态版本
    if (function_exists('exec') && is_dir(__DIR__ . '/../.git')) {
        $output = [];
        $returnCode = 0;
        
        // 方法1: 尝试获取最近的标签
        exec('git describe --tags --always 2>nul', $output, $returnCode);
        if ($returnCode === 0 && !empty($output)) {
            $version = trim($output[0]);
            // 如果有标签，使用标签；否则使用短commit hash
            return $version;
        }
        
        // 方法2: 获取分支名（清理heads/前缀）
        $output = [];
        exec('git rev-parse --abbrev-ref HEAD 2>nul', $output, $returnCode);
        if ($returnCode === 0 && !empty($output)) {
            $branch = trim($output[0]);
            // 移除 "heads/" 前缀（如果存在）
            $branch = preg_replace('#^heads/#', '', $branch);
            return 'dev-' . $branch;
        }
    }
    
    // 回退到静态版本号
    return $staticVersion;
}

// 版本信息
define('BILLFISH_WEB_VERSION', getVersion());
define('BILLFISH_WEB_BUILD_DATE', date('Y-m-d'));

// Billfish 资源库路径
define('BILLFISH_PATH', __DIR__ . '/assets/viedeos/rzxme-billfish');

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