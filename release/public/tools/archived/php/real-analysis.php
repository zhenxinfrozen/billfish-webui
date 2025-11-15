<?php
/**
 * 深度分析真实的 Billfish 映射关系
 * 通过尝试读取数据库来找到真正的映射
 */

require_once 'config.php';

echo "=== 深度分析 Billfish 真实映射关系 ===\n\n";

// 1. 尝试使用 PHP 的 SQLite 扩展
$dbPath = BILLFISH_PATH . '\.bf\billfish.db';

echo "1. 尝试读取 Billfish 数据库...\n";
echo "数据库路径: $dbPath\n";
echo "数据库大小: " . formatFileSize(filesize($dbPath)) . "\n";

// 检查 PHP 扩展
echo "\nPHP 扩展检查:\n";
echo "PDO: " . (class_exists('PDO') ? '✅' : '❌') . "\n";
echo "PDO drivers: " . implode(', ', PDO::getAvailableDrivers()) . "\n";
echo "SQLite3: " . (class_exists('SQLite3') ? '✅' : '❌') . "\n";

// 尝试不同的方法读取数据库
$methods = [];

// 方法1: PDO
if (class_exists('PDO') && in_array('sqlite', PDO::getAvailableDrivers())) {
    try {
        $pdo = new PDO("sqlite:$dbPath");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $methods['PDO'] = $pdo;
        echo "PDO 连接成功\n";
    } catch (Exception $e) {
        echo "PDO 连接失败: " . $e->getMessage() . "\n";
    }
}

// 方法2: SQLite3
if (class_exists('SQLite3')) {
    try {
        $sqlite3 = new SQLite3($dbPath);
        $methods['SQLite3'] = $sqlite3;
        echo "SQLite3 连接成功\n";
    } catch (Exception $e) {
        echo "SQLite3 连接失败: " . $e->getMessage() . "\n";
    }
}

// 如果成功连接，分析表结构
foreach ($methods as $method => $db) {
    echo "\n=== 使用 $method 分析数据库 ===\n";
    
    try {
        if ($method === 'PDO') {
            // 获取所有表
            $stmt = $db->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            echo "发现表: " . implode(', ', $tables) . "\n";
            
            // 分析每个表
            foreach ($tables as $table) {
                echo "\n--- 表: $table ---\n";
                
                // 获取表结构
                $stmt = $db->query("PRAGMA table_info($table)");
                $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo "列: ";
                foreach ($columns as $col) {
                    echo $col['name'] . '(' . $col['type'] . ') ';
                }
                echo "\n";
                
                // 获取记录数
                $stmt = $db->query("SELECT COUNT(*) as count FROM `$table`");
                $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                echo "记录数: $count\n";
                
                // 如果记录数合理，显示前几条
                if ($count > 0 && $count <= 1000) {
                    echo "前3条记录:\n";
                    $stmt = $db->query("SELECT * FROM `$table` LIMIT 3");
                    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    foreach ($rows as $i => $row) {
                        echo "  记录 " . ($i + 1) . ":\n";
                        foreach ($row as $col => $val) {
                            $displayVal = strlen($val) > 100 ? substr($val, 0, 100) . '...' : $val;
                            echo "    $col: $displayVal\n";
                        }
                    }
                }
            }
            
        } elseif ($method === 'SQLite3') {
            // SQLite3 方法
            $result = $db->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
            $tables = [];
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $tables[] = $row['name'];
            }
            
            echo "发现表: " . implode(', ', $tables) . "\n";
            
            foreach ($tables as $table) {
                echo "\n--- 表: $table ---\n";
                
                // 获取表结构
                $result = $db->query("PRAGMA table_info($table)");
                $columns = [];
                while ($col = $result->fetchArray(SQLITE3_ASSOC)) {
                    $columns[] = $col['name'] . '(' . $col['type'] . ')';
                }
                echo "列: " . implode(', ', $columns) . "\n";
                
                // 获取记录数
                $result = $db->query("SELECT COUNT(*) as count FROM `$table`");
                $count = $result->fetchArray(SQLITE3_ASSOC)['count'];
                echo "记录数: $count\n";
                
                // 显示前几条记录
                if ($count > 0 && $count <= 1000) {
                    echo "前2条记录:\n";
                    $result = $db->query("SELECT * FROM `$table` LIMIT 2");
                    $i = 1;
                    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                        echo "  记录 $i:\n";
                        foreach ($row as $col => $val) {
                            $displayVal = strlen($val) > 100 ? substr($val, 0, 100) . '...' : $val;
                            echo "    $col: $displayVal\n";
                        }
                        $i++;
                    }
                }
            }
        }
        
    } catch (Exception $e) {
        echo "分析失败: " . $e->getMessage() . "\n";
    }
}

// 2. 分析文件名模式
echo "\n\n=== 分析文件名模式 ===\n";

$testFiles = [
    'animation-clips\begin-01.mp4',
    'animation-clips\dragonfire.mp4',
    'animation-clips\shooting-01.mp4',
    'animation-clips\xxx06.mp4'
];

foreach ($testFiles as $file) {
    $fullPath = BILLFISH_PATH . '\\' . $file;
    if (file_exists($fullPath)) {
        echo "\n文件: $file\n";
        echo "  完整路径: $fullPath\n";
        echo "  大小: " . filesize($fullPath) . " bytes\n";
        echo "  修改时间: " . date('Y-m-d H:i:s', filemtime($fullPath)) . "\n";
        echo "  创建时间: " . date('Y-m-d H:i:s', filectime($fullPath)) . "\n";
        
        // 计算各种可能的哈希
        $hashes = [
            'MD5_path' => md5($fullPath),
            'MD5_relative' => md5($file),
            'MD5_filename' => md5(basename($file)),
            'SHA1_path' => sha1($fullPath),
            'CRC32_path' => sprintf('%08x', crc32($fullPath)),
            'filesize_mod' => filesize($fullPath) % 1000,
            'mtime_mod' => filemtime($fullPath) % 1000,
        ];
        
        foreach ($hashes as $method => $hash) {
            echo "  $method: $hash\n";
        }
    }
}

// 3. 检查是否有其他配置文件
echo "\n\n=== 检查其他配置文件 ===\n";

$configFiles = [
    BILLFISH_PATH . '\.bf\.ui_config\lib_info.json',
    BILLFISH_PATH . '\.bf\.ui_config\library.ini',
    BILLFISH_PATH . '\.bf\summary_v2.db'
];

foreach ($configFiles as $configFile) {
    if (file_exists($configFile)) {
        echo "\n配置文件: " . basename($configFile) . "\n";
        echo "  大小: " . formatFileSize(filesize($configFile)) . "\n";
        
        if (pathinfo($configFile, PATHINFO_EXTENSION) === 'json') {
            $content = file_get_contents($configFile);
            echo "  内容预览: " . substr($content, 0, 200) . "...\n";
        }
    }
}

function formatFileSize($bytes) {
    if ($bytes >= 1048576) {
        return round($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return round($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' B';
    }
}

echo "\n=== 分析完成 ===\n";
echo "需要找到真正的文件ID到预览ID的映射关系！\n";
?>