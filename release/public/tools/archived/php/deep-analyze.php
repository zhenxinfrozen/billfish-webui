<?php
/**
 * 深度分析 Billfish 预览图片映射机制
 */

require_once 'config.php';

echo "=== Billfish 预览图片映射深度分析 ===\n\n";

// 1. 分析数据库结构（如果可能的话）
$dbPath = BILLFISH_PATH . '\.bf\billfish.db';

if (file_exists($dbPath)) {
    echo "1. 尝试分析数据库结构...\n";
    
    // 使用 sqlite3 命令行工具（如果可用）
    $output = shell_exec('sqlite3 "' . $dbPath . '" ".tables"');
    if ($output) {
        echo "数据库表: " . trim($output) . "\n";
        
        // 尝试查看一些表的结构
        $tables = array_filter(explode("\n", trim($output)));
        foreach ($tables as $table) {
            $table = trim($table);
            if (!empty($table)) {
                echo "\n表 $table 的结构:\n";
                $schema = shell_exec('sqlite3 "' . $dbPath . '" ".schema ' . $table . '"');
                if ($schema) {
                    echo $schema . "\n";
                }
                
                // 查看前几条记录
                echo "前3条记录:\n";
                $records = shell_exec('sqlite3 "' . $dbPath . '" "SELECT * FROM ' . $table . ' LIMIT 3"');
                if ($records) {
                    echo $records . "\n";
                }
            }
        }
    } else {
        echo "无法直接读取数据库，可能需要 SQLite 命令行工具\n";
    }
}

// 2. 分析预览文件的数字ID规律
echo "\n2. 分析预览文件的数字ID规律...\n";
$previewDir = BILLFISH_PATH . '\.bf\.preview';
$previewFiles = [];

// 收集所有预览文件的数字ID
for ($i = 0; $i < 256; $i++) {
    $subDir = sprintf('%02x', $i);
    $path = $previewDir . '\\' . $subDir;
    
    if (is_dir($path)) {
        $files = glob($path . '\\*.small.webp');
        foreach ($files as $file) {
            $filename = basename($file, '.small.webp');
            if (is_numeric($filename)) {
                $previewFiles[] = [
                    'id' => intval($filename),
                    'hex_dir' => $subDir,
                    'file' => $file
                ];
            }
        }
    }
}

// 按ID排序
usort($previewFiles, function($a, $b) {
    return $a['id'] - $b['id'];
});

echo "预览文件ID统计:\n";
echo "总数: " . count($previewFiles) . "\n";
echo "ID范围: " . $previewFiles[0]['id'] . " - " . end($previewFiles)['id'] . "\n";
echo "前10个ID: ";
for ($i = 0; $i < min(10, count($previewFiles)); $i++) {
    echo $previewFiles[$i]['id'] . " ";
}
echo "\n";

// 3. 分析具体文件的映射关系
echo "\n3. 分析具体文件的映射关系...\n";
$testFiles = [
    'animation-clips\begin-01.mp4',
    'animation-clips\dragonfire.mp4', 
    'animation-clips\shooting-01.mp4',
    'comic-anim\blender-fluids-all-0001-0468.mp4'
];

foreach ($testFiles as $relativePath) {
    $fullPath = BILLFISH_PATH . '\\' . $relativePath;
    if (file_exists($fullPath)) {
        echo "\n文件: $relativePath\n";
        echo "  文件大小: " . filesize($fullPath) . " bytes\n";
        echo "  修改时间: " . date('Y-m-d H:i:s', filemtime($fullPath)) . "\n";
        
        // 尝试各种可能的映射方法
        $methods = [
            'inode' => fileinode($fullPath),
            'size_mod' => filesize($fullPath) % 1000,
            'time_mod' => filemtime($fullPath) % 1000,
            'crc32_file' => crc32(file_get_contents($fullPath, false, null, 0, 1024)), // 前1KB的CRC32
            'sequence' => array_search($relativePath, $testFiles) + 1
        ];
        
        foreach ($methods as $method => $value) {
            echo "  $method: $value\n";
            
            // 检查这个值是否对应某个预览文件ID
            foreach ($previewFiles as $pf) {
                if ($pf['id'] == $value || $pf['id'] == abs($value) % 1000) {
                    echo "    -> 可能匹配预览ID: " . $pf['id'] . " (在目录 " . $pf['hex_dir'] . ")\n";
                }
            }
        }
    }
}

// 4. 检查是否有顺序规律
echo "\n4. 检查是否有文件创建顺序规律...\n";
$allFiles = [];
$directories = ['animation-clips', 'comic-anim', 'storyboard', 'test-blender', 'test-ex', 'test-videos'];

foreach ($directories as $dir) {
    $dirPath = BILLFISH_PATH . '\\' . $dir;
    if (is_dir($dirPath)) {
        $files = glob($dirPath . '\*.*');
        foreach ($files as $file) {
            $allFiles[] = [
                'path' => $file,
                'relative' => str_replace(BILLFISH_PATH . '\\', '', $file),
                'mtime' => filemtime($file),
                'size' => filesize($file)
            ];
        }
    }
}

// 按修改时间排序
usort($allFiles, function($a, $b) {
    return $a['mtime'] - $b['mtime'];
});

echo "文件按时间排序（前10个）:\n";
for ($i = 0; $i < min(10, count($allFiles)); $i++) {
    $file = $allFiles[$i];
    echo ($i + 1) . ". " . basename($file['path']) . " (" . date('Y-m-d H:i:s', $file['mtime']) . ")\n";
    
    // 检查序号是否对应预览ID
    $sequenceId = $i + 1;
    foreach ($previewFiles as $pf) {
        if ($pf['id'] == $sequenceId) {
            echo "   -> 可能对应预览ID: $sequenceId\n";
            break;
        }
    }
}

echo "\n=== 建议 ===\n";
echo "1. Billfish 很可能使用数据库存储文件ID和预览图片ID的映射关系\n";
echo "2. 预览图片的数字ID可能是递增分配的，不是基于文件名哈希\n";
echo "3. 需要读取数据库中的映射表才能准确匹配\n";
echo "4. 当前的哈希方法只是一个近似匹配，可能出现错误的预览图片\n";
?>