<?php
/**
 * Billfish 数据库分析工具
 */

require_once 'config.php';

echo "=== Billfish 数据库分析工具 ===\n\n";

$dbPath = BILLFISH_PATH . '\.bf\billfish.db';
$summaryDbPath = BILLFISH_PATH . '\.bf\summary_v2.db';

if (!file_exists($dbPath)) {
    echo "数据库文件不存在: $dbPath\n";
    exit(1);
}

// 检查 PHP SQLite 支持
if (!class_exists('SQLite3')) {
    echo "尝试使用 PDO SQLite...\n";
    
    if (!class_exists('PDO') || !in_array('sqlite', PDO::getAvailableDrivers())) {
        echo "PDO SQLite 不可用\n";
        exit(1);
    }
    
    try {
        $pdo = new PDO("sqlite:$dbPath");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        echo "1. 分析主数据库表结构...\n";
        $result = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
        $tables = $result->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($tables as $table) {
            echo "\n表: $table\n";
            echo str_repeat('-', 50) . "\n";
            
            // 获取表结构
            $result = $pdo->query("PRAGMA table_info($table)");
            $columns = $result->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($columns as $column) {
                echo sprintf("  %s: %s %s\n", 
                    $column['name'], 
                    $column['type'],
                    $column['pk'] ? '(主键)' : ''
                );
            }
            
            // 获取记录数
            $result = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
            $count = $result->fetch(PDO::FETCH_ASSOC)['count'];
            echo "  记录数: $count\n";
            
            // 显示前几条记录
            if ($count > 0 && $count < 1000) {
                echo "  示例数据:\n";
                $result = $pdo->query("SELECT * FROM `$table` LIMIT 3");
                $rows = $result->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($rows as $i => $row) {
                    echo "    记录 " . ($i + 1) . ":\n";
                    foreach ($row as $col => $val) {
                        $displayVal = strlen($val) > 50 ? substr($val, 0, 50) . '...' : $val;
                        echo "      $col: $displayVal\n";
                    }
                    echo "\n";
                }
            }
        }
        
        // 分析汇总数据库
        if (file_exists($summaryDbPath)) {
            echo "\n\n2. 分析汇总数据库...\n";
            $summaryPdo = new PDO("sqlite:$summaryDbPath");
            $summaryPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $result = $summaryPdo->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
            $summaryTables = $result->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($summaryTables as $table) {
                echo "\n汇总表: $table\n";
                echo str_repeat('-', 50) . "\n";
                
                $result = $summaryPdo->query("PRAGMA table_info($table)");
                $columns = $result->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($columns as $column) {
                    echo sprintf("  %s: %s\n", $column['name'], $column['type']);
                }
                
                $result = $summaryPdo->query("SELECT COUNT(*) as count FROM `$table`");
                $count = $result->fetch(PDO::FETCH_ASSOC)['count'];
                echo "  记录数: $count\n";
            }
        }
        
    } catch (Exception $e) {
        echo "数据库分析失败: " . $e->getMessage() . "\n";
    }
} else {
    // 使用 SQLite3 扩展
    try {
        $db = new SQLite3($dbPath);
        
        echo "1. 分析主数据库表结构...\n";
        $result = $db->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
        
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $table = $row['name'];
            echo "\n表: $table\n";
            echo str_repeat('-', 50) . "\n";
            
            // 获取表结构
            $schemaResult = $db->query("PRAGMA table_info($table)");
            while ($column = $schemaResult->fetchArray(SQLITE3_ASSOC)) {
                echo sprintf("  %s: %s %s\n", 
                    $column['name'], 
                    $column['type'],
                    $column['pk'] ? '(主键)' : ''
                );
            }
            
            // 获取记录数
            $countResult = $db->query("SELECT COUNT(*) as count FROM `$table`");
            $count = $countResult->fetchArray(SQLITE3_ASSOC)['count'];
            echo "  记录数: $count\n";
        }
        
        $db->close();
        
    } catch (Exception $e) {
        echo "SQLite3 分析失败: " . $e->getMessage() . "\n";
    }
}

echo "\n3. 分析配置文件...\n";
$configFiles = [
    BILLFISH_PATH . '\.bf\.ui_config\lib_info.json',
    BILLFISH_PATH . '\.bf\.ui_config\library.ini'
];

foreach ($configFiles as $configFile) {
    if (file_exists($configFile)) {
        echo "\n配置文件: " . basename($configFile) . "\n";
        echo str_repeat('-', 50) . "\n";
        
        if (pathinfo($configFile, PATHINFO_EXTENSION) === 'json') {
            $data = json_decode(file_get_contents($configFile), true);
            print_r($data);
        } else {
            $data = parse_ini_file($configFile, true);
            print_r($data);
        }
    }
}

echo "\n4. 分析目录结构...\n";
$directories = ['animation-clips', 'comic-anim', 'storyboard', 'test-blender', 'test-ex', 'test-videos'];

foreach ($directories as $dir) {
    $dirPath = BILLFISH_PATH . '\\' . $dir;
    if (is_dir($dirPath)) {
        $files = glob($dirPath . '\*.*');
        echo "$dir: " . count($files) . " 个文件\n";
        
        // 分析文件类型
        $extensions = [];
        foreach ($files as $file) {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $extensions[$ext] = ($extensions[$ext] ?? 0) + 1;
        }
        
        foreach ($extensions as $ext => $count) {
            echo "  .$ext: $count 个\n";
        }
        echo "\n";
    }
}

echo "\n5. 分析预览图片...\n";
$previewDir = BILLFISH_PATH . '\.bf\.preview';
if (is_dir($previewDir)) {
    $previewCount = 0;
    $totalSize = 0;
    
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($previewDir));
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'webp') {
            $previewCount++;
            $totalSize += $file->getSize();
        }
    }
    
    echo "预览图片总数: $previewCount\n";
    echo "预览图片总大小: " . formatFileSize($totalSize) . "\n";
} else {
    echo "预览目录不存在\n";
}

function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' B';
    }
}

echo "\n=== 分析完成 ===\n";
?>