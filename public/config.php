<?php
/**
 * Billfish Web Manager 配置文件
 */

// 获取版本信息（智能自适应）
function getVersion() {
    // 静态保底版本号（当完全没有 Git 环境时使用）
    $staticVersion = 'v0.1.0';

    // 如果没有 exec 执行权限或不存在 .git 目录，直接返回静态版本
    if (!function_exists('exec') || !is_dir(__DIR__ . '/../.git')) {
        return $staticVersion;
    }

    // 1. 优先获取当前分支名与 short commit id (如: v0.1.3-dda2c19 或 main-dda2c19)
    $branchOutput = [];
    $commitOutput = [];
    exec('git rev-parse --abbrev-ref HEAD 2>&1', $branchOutput, $branchCode);
    exec('git rev-parse --short HEAD 2>&1', $commitOutput, $commitCode);

    if ($branchCode === 0 && $commitCode === 0 && !empty($commitOutput) && strpos($commitOutput[0], 'fatal') === false) {
        $branch = trim($branchOutput[0] ?? 'main');
        $commit = trim($commitOutput[0]);
        return "{$branch}-{$commit}";
    }

    // 2. 退守方案：尝试获取 Git Tag 或提交 Hash (如: v0.1.0-5-ga3b2c1d 或 a3b2c1d)
    // 移除了 Windows 专用的 2>nul，使用跨平台的 2>&1
    $output = [];
    $returnCode = 0;
    exec('git describe --tags --always 2>&1', $output, $returnCode);
    if ($returnCode === 0 && !empty($output) && strpos($output[0], 'fatal') === false) {
        return trim($output[0]);
    }

    return $staticVersion;
}

// 版本信息
define('BILLFISH_WEB_VERSION', getVersion());
define('BILLFISH_WEB_BUILD_DATE', date('Y-m-d'));

// =========================================================================
// 路径与退守 (Fallback) 智能配置（自动兼容 Windows / Linux）
// =========================================================================

// 1. 动态获取项目根目录，并统一将反斜杠转换为正斜杠 '/'
$rootDir = str_replace('\\', '/', dirname(__DIR__));

// 2. 候选路径配置
// 路径 A：生产环境/特定挂载的外部大容量库路径（若不存在将自动降级退守）
$primaryPath = 'D:/VS CODE/rzxme-billfish/demo-billfish';

// 路径 B：保底/退守路径（项目内置的 demo 库，自动基于相对路径定位）
$fallbackPath = $rootDir . '/demo-billfish';

// 3. 智能路由与退守判定：优先检测主路径，不存在则自动回退至本地 demo 库
if (!empty($primaryPath) && is_dir($primaryPath) && file_exists(rtrim($primaryPath, '/\\') . '/.bf/billfish.db')) {
    define('BILLFISH_PATH', rtrim(str_replace('\\', '/', $primaryPath), '/'));
} else {
    define('BILLFISH_PATH', $fallbackPath);
}

// 4. 数据库与资源文件路径统一定义（全部使用正斜杠以保障 Linux/Win 双平台兼容）
define('BILLFISH_DB', BILLFISH_PATH . '/.bf/billfish.db');
define('SUMMARY_DB', BILLFISH_PATH . '/.bf/summary_v2.db');
define('PREVIEW_PATH', BILLFISH_PATH . '/.bf/.preview');

// =========================================================================

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
