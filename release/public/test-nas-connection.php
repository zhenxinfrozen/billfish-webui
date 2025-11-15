<?php
/**
 * NAS 网络存储连接测试工具
 * 测试对网络路径的访问能力
 */

echo "=== NAS 网络存储连接测试 ===\n\n";

function testNetworkPath($path) {
    echo "测试路径: {$path}\n";
    echo str_repeat("-", 50) . "\n";
    
    $issues = [];
    $warnings = [];
    
    // 1. 基本路径检查
    echo "🔍 检查网络路径访问...\n";
    if (!is_dir($path)) {
        $issues[] = "❌ 网络路径无法访问: {$path}";
        echo "   可能原因:\n";
        echo "   - NAS未启动或网络连接问题\n";
        echo "   - 路径映射不正确\n";
        echo "   - 权限不足\n";
        return ['issues' => $issues, 'warnings' => $warnings];
    }
    echo "✅ 网络路径可访问\n";
    
    // 2. 权限测试
    echo "\n🔐 检查读取权限...\n";
    if (!is_readable($path)) {
        $issues[] = "❌ 路径不可读取";
        return ['issues' => $issues, 'warnings' => $warnings];
    }
    echo "✅ 具有读取权限\n";
    
    // 3. Billfish结构检查
    echo "\n📁 检查Billfish结构...\n";
    $bfDirs = glob($path . '/*/\.bf', GLOB_ONLYDIR);
    
    if (empty($bfDirs)) {
        $warnings[] = "⚠️ 未发现Billfish资料库 (.bf目录)";
        echo "⚠️ 在 {$path} 下未发现.bf目录\n";
        echo "   请确认路径是否正确\n";
    } else {
        echo "✅ 发现 " . count($bfDirs) . " 个Billfish资料库:\n";
        foreach ($bfDirs as $bfDir) {
            $libPath = dirname($bfDir);
            $libName = basename($libPath);
            echo "   📚 {$libName}: {$libPath}\n";
            
            // 检查数据库文件
            $dbPath = $bfDir . '/billfish.db';
            if (file_exists($dbPath)) {
                echo "      ✅ 数据库文件存在\n";
                
                // 测试数据库连接
                try {
                    $db = new SQLite3($dbPath, SQLITE3_OPEN_READONLY);
                    $fileCount = $db->querySingle('SELECT COUNT(*) FROM bf_file WHERE is_hide = 0');
                    $db->close();
                    echo "      ✅ 数据库连接成功 ({$fileCount} 个文件)\n";
                } catch (Exception $e) {
                    $warnings[] = "⚠️ {$libName}: 数据库连接失败 - " . $e->getMessage();
                    echo "      ❌ 数据库连接失败\n";
                }
            } else {
                $warnings[] = "⚠️ {$libName}: 缺少billfish.db文件";
                echo "      ❌ 缺少数据库文件\n";
            }
        }
    }
    
    // 4. 性能测试
    echo "\n⚡ 网络性能测试...\n";
    $startTime = microtime(true);
    $files = scandir($path);
    $endTime = microtime(true);
    $duration = round(($endTime - $startTime) * 1000, 2);
    
    echo "   目录扫描耗时: {$duration}ms\n";
    if ($duration > 1000) {
        $warnings[] = "⚠️ 网络延迟较高，可能影响性能";
        echo "   ⚠️ 网络延迟较高\n";
    } else {
        echo "   ✅ 网络性能良好\n";
    }
    
    return ['issues' => $issues, 'warnings' => $warnings, 'libraries' => $bfDirs ?? []];
}

function generateNetworkConfig($nasPath, $selectedLibrary = null) {
    $configs = [];
    
    // 方案1: 直接网络路径
    $configs['direct'] = [
        'name' => '直接网络路径访问',
        'path' => $selectedLibrary ?: $nasPath,
        'pros' => [
            '配置简单',
            '实时访问',
            '无需同步'
        ],
        'cons' => [
            '依赖网络连接',
            '可能有延迟',
            '权限要求高'
        ],
        'config' => "define('BILLFISH_PATH', '{$selectedLibrary}');"
    ];
    
    // 方案2: 本地缓存
    $localPath = 'D:/Billfish_Cache';
    $configs['cache'] = [
        'name' => '本地缓存方案',
        'path' => $localPath,
        'pros' => [
            '访问速度快',
            '离线可用',
            '减少网络负载'
        ],
        'cons' => [
            '需要同步机制',
            '占用本地空间',
            '可能数据不同步'
        ],
        'config' => "define('BILLFISH_PATH', '{$localPath}');"
    ];
    
    // 方案3: VPS部署
    $configs['vps'] = [
        'name' => 'VPS远程部署',
        'path' => '/mnt/nas/billfish',
        'pros' => [
            '24/7可访问',
            '多用户共享',
            '集中管理'
        ],
        'cons' => [
            '需要VPS配置',
            '网络挂载复杂',
            '安全性考虑'
        ],
        'config' => "define('BILLFISH_PATH', '/mnt/nas/billfish');"
    ];
    
    return $configs;
}

// 主程序
$nasPath = 'S:/OneDrive-irm/Bill-Eagle';

echo "NAS路径: {$nasPath}\n";
echo str_repeat("=", 60) . "\n\n";

$result = testNetworkPath($nasPath);

echo "\n" . str_repeat("=", 60) . "\n";
echo "测试结果汇总:\n";

if (empty($result['issues'])) {
    echo "🎉 NAS连接测试通过！\n";
    
    if (!empty($result['libraries'])) {
        echo "\n📚 发现的Billfish资料库:\n";
        foreach ($result['libraries'] as $index => $bfDir) {
            $libPath = dirname($bfDir);
            $libName = basename($libPath);
            echo "  " . ($index + 1) . ". {$libName}\n";
            echo "     路径: {$libPath}\n";
        }
        
        // 生成配置建议
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "配置方案建议:\n\n";
        
        $selectedLib = !empty($result['libraries']) ? dirname($result['libraries'][0]) : $nasPath;
        $configs = generateNetworkConfig($nasPath, $selectedLib);
        
        foreach ($configs as $key => $config) {
            echo "📋 方案" . ($key === 'direct' ? '1' : ($key === 'cache' ? '2' : '3')) . ": {$config['name']}\n";
            echo "   路径: {$config['path']}\n";
            echo "   优点: " . implode(', ', $config['pros']) . "\n";
            echo "   缺点: " . implode(', ', $config['cons']) . "\n";
            echo "   配置: {$config['config']}\n\n";
        }
    }
} else {
    echo "❌ 发现 " . count($result['issues']) . " 个问题:\n";
    foreach ($result['issues'] as $issue) {
        echo "   {$issue}\n";
    }
}

if (!empty($result['warnings'])) {
    echo "\n⚠️ 警告事项:\n";
    foreach ($result['warnings'] as $warning) {
        echo "   {$warning}\n";
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "部署建议:\n\n";

echo "🖥️ 本地使用 (当前电脑):\n";
echo "   1. 确保NAS网络连接稳定\n";
echo "   2. 配置Windows网络驱动器映射\n";
echo "   3. 使用直接网络路径访问\n\n";

echo "🌐 VPS远程部署:\n";
echo "   1. 在VPS上安装CIFS/SMB客户端\n";
echo "   2. 挂载NAS到VPS文件系统\n";
echo "   3. 配置防火墙和安全访问\n";
echo "   4. 考虑VPN连接保证安全\n\n";

echo "⚡ 性能优化:\n";
echo "   1. 使用SSD缓存提升访问速度\n";
echo "   2. 配置适当的网络缓存\n";
echo "   3. 考虑数据库定期同步\n\n";

echo "🔐 安全建议:\n";
echo "   1. 配置只读访问权限\n";
echo "   2. 使用VPN保护网络传输\n";
echo "   3. 定期备份重要数据\n";
?>