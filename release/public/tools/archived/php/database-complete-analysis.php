<?php
/**
 * Billfish 数据库完整分析工具
 * 目标：读取 billfish.db 和 summary_v2.db，建立准确的映射关系
 */

// 数据库文件路径
$dbPath = 'd:\VS CODE\rzxme-billfish\publish\assets\viedeos\rzxme-billfish\.bf\billfish.db';
$summaryDbPath = 'd:\VS CODE\rzxme-billfish\publish\assets\viedeos\rzxme-billfish\.bf\summary_v2.db';

echo "=== Billfish 数据库完整分析 ===\n\n";

// 检查文件存在
if (!file_exists($dbPath)) {
    die("错误：找不到 billfish.db 文件\n");
}

if (!file_exists($summaryDbPath)) {
    die("错误：找不到 summary_v2.db 文件\n");
}

echo "✓ 数据库文件存在\n";
echo "  - billfish.db: " . round(filesize($dbPath) / 1024 / 1024, 2) . " MB\n";
echo "  - summary_v2.db: " . round(filesize($summaryDbPath) / 1024, 2) . " KB\n\n";

// 尝试使用 SQLite3 扩展
if (class_exists('SQLite3')) {
    echo "✓ 找到 SQLite3 扩展\n\n";
    
    try {
        $db = new SQLite3($dbPath);
        echo "=== 成功连接到 billfish.db ===\n\n";
        
        // 获取所有表
        echo "--- 数据库表列表 ---\n";
        $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
        $tableList = [];
        while ($row = $tables->fetchArray(SQLITE3_ASSOC)) {
            $tableList[] = $row['name'];
            echo "  - " . $row['name'] . "\n";
        }
        echo "\n";
        
        // 分析关键表结构
        foreach ($tableList as $table) {
            if (in_array($table, ['bf_material_v2', 'bf_file', 'bf_tag', 'bf_material_tag', 'bf_material_userdata'])) {
                echo "--- 表结构: $table ---\n";
                $result = $db->query("PRAGMA table_info($table)");
                while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                    echo sprintf("  %-20s %-15s %s\n", 
                        $row['name'], 
                        $row['type'],
                        $row['notnull'] ? 'NOT NULL' : ''
                    );
                }
                echo "\n";
            }
        }
        
        // 获取材料数量
        $result = $db->querySingle("SELECT COUNT(*) FROM bf_material_v2");
        echo "总材料数: $result\n\n";
        
        // 获取示例数据
        echo "--- bf_material_v2 示例数据（前5条）---\n";
        $result = $db->query("SELECT id, name, preview_tid, size, width, height, duration, ext FROM bf_material_v2 LIMIT 5");
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            echo "ID: {$row['id']}\n";
            echo "  名称: {$row['name']}\n";
            echo "  preview_tid: {$row['preview_tid']}\n";
            echo "  扩展名: {$row['ext']}\n";
            echo "  尺寸: {$row['width']}x{$row['height']}\n";
            echo "  时长: {$row['duration']}秒\n";
            echo "  大小: " . round($row['size'] / 1024 / 1024, 2) . " MB\n";
            echo "\n";
        }
        
        // 获取标签数据
        echo "--- 标签列表 ---\n";
        $result = $db->query("SELECT id, name, color FROM bf_tag ORDER BY id");
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            echo "  [{$row['id']}] {$row['name']} (颜色: {$row['color']})\n";
        }
        echo "\n";
        
        // 获取文件表数据
        echo "--- bf_file 示例数据（前5条）---\n";
        $result = $db->query("SELECT id, name, path, size FROM bf_file LIMIT 5");
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            echo "ID: {$row['id']}\n";
            echo "  名称: {$row['name']}\n";
            echo "  路径: {$row['path']}\n";
            echo "  大小: " . round($row['size'] / 1024 / 1024, 2) . " MB\n\n";
        }
        
        // 关键：获取材料和预览图的映射关系
        echo "=== 关键发现：材料与预览图映射 ===\n";
        $result = $db->query("
            SELECT 
                m.id as material_id,
                m.name as material_name,
                m.preview_tid,
                m.ext,
                f.name as file_name,
                f.path as file_path
            FROM bf_material_v2 m
            LEFT JOIN bf_file f ON m.id = f.id
            LIMIT 10
        ");
        
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            echo "材料ID: {$row['material_id']}\n";
            echo "  材料名: {$row['material_name']}\n";
            echo "  预览TID: {$row['preview_tid']}\n";
            echo "  文件名: {$row['file_name']}\n";
            echo "  文件路径: {$row['file_path']}\n";
            echo "  预览图路径应该是: .bf/.preview/" . substr(dechex($row['preview_tid']), 0, 2) . "/" . $row['preview_tid'] . ".small.webp\n";
            echo "\n";
        }
        
        // 生成完整映射
        echo "=== 生成完整的正确映射 ===\n";
        $mappingData = [];
        $result = $db->query("
            SELECT 
                m.id,
                m.name,
                m.preview_tid,
                m.ext,
                f.path
            FROM bf_material_v2 m
            LEFT JOIN bf_file f ON m.id = f.id
            WHERE m.preview_tid IS NOT NULL AND m.preview_tid > 0
        ");
        
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $videoPath = $row['path'];
            $previewTid = $row['preview_tid'];
            
            // 生成预览图路径
            $tidHex = dechex($previewTid);
            $folder = str_pad(substr($tidHex, 0, 2), 2, '0', STR_PAD_LEFT);
            $previewPath = "\\\.bf\\\.preview\\{$folder}\\{$previewTid}.small.webp";
            
            $mappingData[$videoPath] = [
                'preview_tid' => $previewTid,
                'preview_path' => $previewPath,
                'material_id' => $row['id'],
                'name' => $row['name'],
                'ext' => $row['ext']
            ];
        }
        
        echo "成功生成 " . count($mappingData) . " 条映射记录\n\n";
        
        // 保存映射到 JSON
        $jsonPath = __DIR__ . '/database-driven-mapping.json';
        file_put_contents($jsonPath, json_encode($mappingData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        echo "✓ 映射已保存到: $jsonPath\n\n";
        
        // 获取用户数据（标签、评分等）
        echo "=== 材料用户数据（标签、评分、备注）===\n";
        $result = $db->query("
            SELECT 
                mu.material_id,
                mu.annotation,
                mu.star,
                GROUP_CONCAT(t.name) as tags
            FROM bf_material_userdata mu
            LEFT JOIN bf_material_tag mt ON mu.material_id = mt.material_id
            LEFT JOIN bf_tag t ON mt.tag_id = t.id
            GROUP BY mu.material_id
            LIMIT 5
        ");
        
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            echo "材料ID: {$row['material_id']}\n";
            echo "  评分: " . ($row['star'] ?? '未评分') . "\n";
            echo "  标签: " . ($row['tags'] ?? '无') . "\n";
            echo "  备注: " . ($row['annotation'] ?? '无') . "\n\n";
        }
        
        $db->close();
        
    } catch (Exception $e) {
        echo "错误：" . $e->getMessage() . "\n";
    }
    
} elseif (extension_loaded('pdo_sqlite')) {
    echo "✓ 找到 PDO SQLite 扩展\n\n";
    
    try {
        $db = new PDO('sqlite:' . $dbPath);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "=== 成功连接到 billfish.db ===\n\n";
        
        // 类似的分析逻辑...
        
    } catch (PDOException $e) {
        echo "错误：" . $e->getMessage() . "\n";
    }
    
} else {
    echo "❌ 没有找到 SQLite 扩展\n";
    echo "   请安装 SQLite3 或 PDO SQLite 扩展\n";
    echo "\n解决方案：\n";
    echo "1. 检查 php.ini 文件\n";
    echo "2. 启用以下扩展之一：\n";
    echo "   - extension=sqlite3\n";
    echo "   - extension=pdo_sqlite\n";
    echo "3. 重启 Web 服务器\n";
}

echo "\n=== 分析完成 ===\n";
?>