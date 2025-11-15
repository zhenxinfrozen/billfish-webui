<?php
/**
 * Billfish 资料库快速切换工具
 * 帮助用户安全地切换到不同的Billfish资料库
 */

echo "=== Billfish 资料库快速切换工具 ===\n\n";

function validateBillfishPath($path) {
    $path = rtrim($path, '\\/');
    
    if (!is_dir($path)) {
        return ['valid' => false, 'error' => '路径不存在'];
    }
    
    if (!is_dir($path . '/.bf')) {
        return ['valid' => false, 'error' => '不是有效的Billfish资料库（缺少.bf目录）'];
    }
    
    if (!file_exists($path . '/.bf/billfish.db')) {
        return ['valid' => false, 'error' => '缺少billfish.db数据库文件'];
    }
    
    try {
        $db = new SQLite3($path . '/.bf/billfish.db', SQLITE3_OPEN_READONLY);
        $tables = [];
        $result = $db->query("SELECT name FROM sqlite_master WHERE type='table'");
        while ($row = $result->fetchArray()) {
            $tables[] = $row['name'];
        }
        $db->close();
        
        $requiredTables = ['bf_file', 'bf_folder', 'bf_type'];
        foreach ($requiredTables as $table) {
            if (!in_array($table, $tables)) {
                return ['valid' => false, 'error' => "缺少必需的数据库表: {$table}"];
            }
        }
        
    } catch (Exception $e) {
        return ['valid' => false, 'error' => '数据库连接失败: ' . $e->getMessage()];
    }
    
    return ['valid' => true];
}

function updateConfig($newPath) {
    $configFile = 'config.php';
    
    if (!file_exists($configFile)) {
        return ['success' => false, 'error' => 'config.php文件不存在'];
    }
    
    $content = file_get_contents($configFile);
    if ($content === false) {
        return ['success' => false, 'error' => '无法读取config.php文件'];
    }
    
    // 备份原配置
    $backupFile = 'config.php.backup.' . date('Y-m-d-H-i-s');
    file_put_contents($backupFile, $content);
    
    // 更新BILLFISH_PATH
    $newPath = str_replace('\\', '\\\\', $newPath); // 转义反斜杠
    $pattern = "/define\('BILLFISH_PATH',\s*'[^']*'\);/";
    $replacement = "define('BILLFISH_PATH', '{$newPath}');";
    
    $newContent = preg_replace($pattern, $replacement, $content);
    
    if ($newContent === null || $newContent === $content) {
        return ['success' => false, 'error' => '无法更新配置文件'];
    }
    
    if (file_put_contents($configFile, $newContent) === false) {
        return ['success' => false, 'error' => '无法写入配置文件'];
    }
    
    return ['success' => true, 'backup' => $backupFile];
}

function getLibraryInfo($path) {
    try {
        $db = new SQLite3($path . '/.bf/billfish.db', SQLITE3_OPEN_READONLY);
        
        $fileCount = $db->querySingle('SELECT COUNT(*) FROM bf_file WHERE is_hide = 0');
        $totalSize = $db->querySingle('SELECT SUM(file_size) FROM bf_file WHERE is_hide = 0');
        $tagCount = $db->querySingle('SELECT COUNT(*) FROM bf_tag_v2');
        if ($tagCount == 0) {
            $tagCount = $db->querySingle('SELECT COUNT(*) FROM bf_tag');
        }
        
        $db->close();
        
        return [
            'files' => $fileCount,
            'size_gb' => round($totalSize / 1024 / 1024 / 1024, 2),
            'tags' => $tagCount
        ];
    } catch (Exception $e) {
        return null;
    }
}

// 主程序
if ($argc < 2) {
    echo "用法: php switch-library.php <新的Billfish资料库路径>\n";
    echo "\n示例:\n";
    echo "  php switch-library.php \"D:\\MyBillfish\\Library1\"\n";
    echo "  php switch-library.php \"/Users/username/Documents/Billfish\"\n";
    echo "\n当前配置:\n";
    
    if (file_exists('config.php')) {
        require_once 'config.php';
        echo "  路径: " . BILLFISH_PATH . "\n";
        $info = getLibraryInfo(BILLFISH_PATH);
        if ($info) {
            echo "  文件: {$info['files']} 个\n";
            echo "  大小: {$info['size_gb']} GB\n";
            echo "  标签: {$info['tags']} 个\n";
        }
    } else {
        echo "  配置文件不存在\n";
    }
    
    exit(1);
}

$newPath = $argv[1];

echo "目标路径: {$newPath}\n";
echo str_repeat("-", 50) . "\n";

// 1. 验证新路径
echo "🔍 验证新路径...\n";
$validation = validateBillfishPath($newPath);

if (!$validation['valid']) {
    echo "❌ 验证失败: {$validation['error']}\n";
    exit(1);
}

echo "✅ 路径验证通过\n";

// 2. 获取库信息
$info = getLibraryInfo($newPath);
if ($info) {
    echo "📊 库信息:\n";
    echo "   - 文件数量: {$info['files']}\n";
    echo "   - 总大小: {$info['size_gb']} GB\n";
    echo "   - 标签数量: {$info['tags']}\n";
} else {
    echo "⚠️ 无法获取库统计信息\n";
}

// 3. 更新配置
echo "\n🔧 更新配置文件...\n";
$updateResult = updateConfig($newPath);

if (!$updateResult['success']) {
    echo "❌ 配置更新失败: {$updateResult['error']}\n";
    exit(1);
}

echo "✅ 配置更新成功\n";
echo "📁 配置备份: {$updateResult['backup']}\n";

// 4. 验证更新结果
echo "\n🧪 验证更新结果...\n";
require_once 'config.php';

if (BILLFISH_PATH === $newPath) {
    echo "✅ 配置更新验证成功\n";
} else {
    echo "❌ 配置更新验证失败\n";
    echo "   期望: {$newPath}\n";
    echo "   实际: " . BILLFISH_PATH . "\n";
    exit(1);
}

// 5. 运行稳健性测试
echo "\n🔬 运行稳健性测试...\n";
if (file_exists('test-robustness.php')) {
    ob_start();
    include 'test-robustness.php';
    $output = ob_get_clean();
    
    if (strpos($output, '🎉 通过所有稳健性测试！') !== false) {
        echo "✅ 稳健性测试通过\n";
    } else {
        echo "⚠️ 稳健性测试有警告，请查看详细信息\n";
        echo "   运行 'php test-robustness.php' 查看完整报告\n";
    }
} else {
    echo "⚠️ 稳健性测试脚本不存在\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "🎉 切换完成！\n";
echo "\n下一步:\n";
echo "1. 打开 http://localhost:8000/ 验证Web界面\n";
echo "2. 运行 'php test-robustness.php' 查看详细兼容性报告\n";
echo "3. 如有问题，使用备份文件恢复: cp {$updateResult['backup']} config.php\n";
echo "\n📝 提示:\n";
echo "- 确保PHP服务器正在运行\n";
echo "- 如遇到权限问题，检查文件夹读取权限\n";
echo "- 不同版本的Billfish可能有不同的功能支持\n";
?>