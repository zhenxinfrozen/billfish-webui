<?php
/**
 * Billfish Web Manager 配置文件
 */

// 获取版本信息
function getVersion() {
    // 【重要】发布版本时，请手动更新此版本号
    // 例如发布 v0.2.0 时，将下面改为 'v0.2.0'
    $staticVersion = 'v0.1.0';
    
    // 开发环境：如果在Git仓库中，显示动态版本信息
    if (function_exists('exec') && is_dir(__DIR__ . '/../.git')) {
        $output = [];
        $returnCode = 0;
        
        // 获取最近的标签和提交信息
        exec('git describe --tags --always 2>nul', $output, $returnCode);
        if ($returnCode === 0 && !empty($output)) {
            $version = trim($output[0]);
            return $version; // 返回如: v0.1.0 或 v0.1.0-5-ga3b2c1d
        }
    }
    
    // 生产环境：使用静态版本号
    return $staticVersion;
}

// 版本信息
define('BILLFISH_WEB_VERSION', getVersion());
define('BILLFISH_WEB_BUILD_DATE', date('Y-m-d'));

// Billfish 资源库路径
define('BILLFISH_PATH', 'D:/VS CODE/rzxme-billfish/demo-billfish');

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