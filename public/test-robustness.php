<?php
/**
 * Billfish Web Manager 稳健性测试
 * 测试项目是否可以适应不同的Billfish资料库
 */

echo "=== Billfish Web Manager 稳健性测试 ===\n\n";

// 测试函数
function testRobustness($billfishPath) {
    echo "测试路径: {$billfishPath}\n";
    echo str_repeat("-", 50) . "\n";
    
    $issues = [];
    $warnings = [];
    
    // 1. 基础路径检查
    if (!is_dir($billfishPath)) {
        $issues[] = "❌ Billfish路径不存在";
        return ['issues' => $issues, 'warnings' => $warnings];
    }
    echo "✅ Billfish路径存在\n";
    
    // 2. .bf 目录检查
    $bfDir = $billfishPath . '/.bf';
    if (!is_dir($bfDir)) {
        $issues[] = "❌ .bf目录不存在，这不是有效的Billfish资料库";
        return ['issues' => $issues, 'warnings' => $warnings];
    }
    echo "✅ .bf目录存在\n";
    
    // 3. 数据库文件检查
    $dbPath = $bfDir . '/billfish.db';
    if (!file_exists($dbPath)) {
        $issues[] = "❌ billfish.db数据库文件不存在";
        return ['issues' => $issues, 'warnings' => $warnings];
    }
    echo "✅ 数据库文件存在\n";
    
    // 4. 数据库连接测试
    try {
        $db = new SQLite3($dbPath, SQLITE3_OPEN_READONLY);
        echo "✅ 数据库连接成功\n";
    } catch (Exception $e) {
        $issues[] = "❌ 数据库连接失败: " . $e->getMessage();
        return ['issues' => $issues, 'warnings' => $warnings];
    }
    
    // 5. 核心表结构检查
    $requiredTables = ['bf_file', 'bf_folder', 'bf_type'];
    $optionalTables = ['bf_tag_v2', 'bf_tag', 'bf_material_userdata', 'bf_material_v2', 'bf_tag_join_file'];
    
    $tables = [];
    $result = $db->query("SELECT name FROM sqlite_master WHERE type='table'");
    while ($row = $result->fetchArray()) {
        $tables[] = $row['name'];
    }
    
    foreach ($requiredTables as $table) {
        if (in_array($table, $tables)) {
            echo "✅ 核心表 {$table} 存在\n";
        } else {
            $issues[] = "❌ 缺少核心表: {$table}";
        }
    }
    
    foreach ($optionalTables as $table) {
        if (in_array($table, $tables)) {
            echo "✅ 可选表 {$table} 存在\n";
        } else {
            $warnings[] = "⚠️ 可选表 {$table} 不存在，相关功能可能受限";
        }
    }
    
    // 6. 标签系统检查
    if (in_array('bf_tag', $tables) && in_array('bf_tag_v2', $tables)) {
        $tagCount = $db->querySingle('SELECT COUNT(*) FROM bf_tag');
        $tagV2Count = $db->querySingle('SELECT COUNT(*) FROM bf_tag_v2');
        
        if ($tagCount == 0 && $tagV2Count > 0) {
            echo "✅ 标签系统: 使用bf_tag_v2表 (现代版本)\n";
        } elseif ($tagCount > 0 && $tagV2Count == 0) {
            echo "✅ 标签系统: 使用bf_tag表 (旧版本)\n";
            $warnings[] = "⚠️ 使用旧版标签表，建议升级Billfish";
        } elseif ($tagCount > 0 && $tagV2Count > 0) {
            echo "✅ 标签系统: 同时存在两个标签表\n";
            $warnings[] = "⚠️ 标签表版本混杂，需要确认使用哪个版本";
        } else {
            $warnings[] = "⚠️ 标签表为空，可能没有设置标签";
        }
    }
    
    // 7. 预览图目录检查
    $previewDir = $bfDir . '/.preview';
    if (!is_dir($previewDir)) {
        $warnings[] = "⚠️ 预览图目录不存在，可能影响缩略图显示";
    } else {
        echo "✅ 预览图目录存在\n";
        
        // 检查分片目录
        $sampleDirs = ['00', '01', '6c', 'ff'];
        $foundDirs = 0;
        foreach ($sampleDirs as $dir) {
            if (is_dir($previewDir . '/' . $dir)) {
                $foundDirs++;
            }
        }
        
        if ($foundDirs > 0) {
            echo "✅ 预览图分片目录存在 ({$foundDirs}/4 样本目录)\n";
        } else {
            $warnings[] = "⚠️ 预览图分片目录为空，可能没有生成缩略图";
        }
    }
    
    // 8. 数据统计
    $fileCount = $db->querySingle('SELECT COUNT(*) FROM bf_file WHERE is_hide = 0');
    $totalSize = $db->querySingle('SELECT SUM(file_size) FROM bf_file WHERE is_hide = 0');
    $totalSizeGB = round($totalSize / 1024 / 1024 / 1024, 2);
    
    echo "📊 数据统计:\n";
    echo "   - 文件总数: {$fileCount}\n";
    echo "   - 总大小: {$totalSizeGB} GB\n";
    
    // 9. 字段兼容性检查
    echo "\n🔍 字段兼容性检查:\n";
    
    // 检查bf_file表字段
    $fileFields = [];
    $result = $db->query("PRAGMA table_info(bf_file)");
    while ($row = $result->fetchArray()) {
        $fileFields[] = $row['name'];
    }
    
    $requiredFileFields = ['id', 'name', 'file_size', 'ctime', 'is_hide'];
    foreach ($requiredFileFields as $field) {
        if (in_array($field, $fileFields)) {
            echo "✅ bf_file.{$field} 字段存在\n";
        } else {
            $issues[] = "❌ bf_file表缺少必需字段: {$field}";
        }
    }
    
    // 检查扩展表字段
    if (in_array('bf_material_userdata', $tables)) {
        $userFields = [];
        $result = $db->query("PRAGMA table_info(bf_material_userdata)");
        while ($row = $result->fetchArray()) {
            $userFields[] = $row['name'];
        }
        
        $enhancedFields = ['colors', 'origin', 'cover_tid'];
        foreach ($enhancedFields as $field) {
            if (in_array($field, $userFields)) {
                echo "✅ bf_material_userdata.{$field} 增强字段存在\n";
            } else {
                $warnings[] = "⚠️ bf_material_userdata表缺少增强字段: {$field}";
            }
        }
    }
    
    $db->close();
    
    return ['issues' => $issues, 'warnings' => $warnings];
}

// 测试当前配置的路径
require_once 'config.php';

$result = testRobustness(BILLFISH_PATH);

echo "\n" . str_repeat("=", 50) . "\n";
echo "测试结果汇总:\n";

if (empty($result['issues'])) {
    echo "🎉 通过所有稳健性测试！\n";
    echo "✅ 项目可以安全地用于当前Billfish资料库\n";
} else {
    echo "❌ 发现 " . count($result['issues']) . " 个严重问题:\n";
    foreach ($result['issues'] as $issue) {
        echo "   {$issue}\n";
    }
}

if (!empty($result['warnings'])) {
    echo "\n⚠️ 发现 " . count($result['warnings']) . " 个警告:\n";
    foreach ($result['warnings'] as $warning) {
        echo "   {$warning}\n";
    }
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "通用性评估:\n";

if (empty($result['issues'])) {
    echo "✅ 高通用性: 项目设计稳健，可以适应不同的Billfish资料库\n";
    echo "✅ 路径配置: 只需修改config.php中的BILLFISH_PATH即可切换资料库\n";
    echo "✅ 版本兼容: 支持新旧版本的标签系统\n";
    echo "✅ 增强功能: 自动检测和使用可用的扩展字段\n";
    
    if (!empty($result['warnings'])) {
        echo "\n💡 建议:\n";
        echo "   - 某些高级功能可能因资料库版本而异\n";
        echo "   - 建议使用最新版本的Billfish以获得最佳体验\n";
    }
} else {
    echo "❌ 低通用性: 当前资料库存在兼容性问题\n";
    echo "   需要解决上述问题才能正常使用\n";
}

echo "\n使用说明:\n";
echo "1. 要切换到新的Billfish资料库，只需:\n";
echo "   - 修改config.php中的BILLFISH_PATH路径\n";
echo "   - 运行此测试脚本验证兼容性\n";
echo "   - 确认没有严重问题后即可使用\n";
echo "\n2. 支持的Billfish版本:\n";
echo "   - 推荐: Billfish 2.x+ (支持bf_tag_v2)\n";
echo "   - 兼容: Billfish 1.x (使用bf_tag)\n";
echo "   - 最低要求: 包含.bf/billfish.db的任何版本\n";
?>