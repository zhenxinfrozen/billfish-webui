<?php
/**
 * 调试预览图片映射
 */

require_once 'config.php';
require_once 'includes/BillfishManager.php';

$manager = new BillfishManager(BILLFISH_PATH);

echo "=== 调试预览图片映射 ===\n\n";

// 获取一些文件进行测试
$directories = ['animation-clips', 'comic-anim', 'storyboard'];
$testFiles = [];

foreach ($directories as $dir) {
    $dirPath = BILLFISH_PATH . '\\' . $dir;
    if (is_dir($dirPath)) {
        $files = array_slice(glob($dirPath . '\*.mp4'), 0, 3); // 每个目录取3个文件
        foreach ($files as $file) {
            $testFiles[] = $file;
        }
    }
}

echo "测试文件列表:\n";
foreach ($testFiles as $i => $file) {
    echo ($i + 1) . ". " . basename($file) . "\n";
    echo "   路径: $file\n";
    
    // 尝试不同的哈希方法
    $methods = [
        'md5' => md5($file),
        'md5_basename' => md5(basename($file)),
        'md5_relative' => md5(str_replace(BILLFISH_PATH . '\\', '', $file)),
        'sha1' => sha1($file),
        'crc32' => dechex(crc32($file))
    ];
    
    foreach ($methods as $method => $hash) {
        $subDir = substr($hash, 0, 2);
        $previewPath = BILLFISH_PATH . '\.bf\.preview\\' . $subDir;
        
        if (is_dir($previewPath)) {
            $previewFiles = glob($previewPath . '\*.small.webp');
            if (!empty($previewFiles)) {
                echo "   $method ($hash) -> $subDir -> " . count($previewFiles) . " 个预览文件\n";
                foreach ($previewFiles as $pf) {
                    echo "     " . basename($pf) . "\n";
                }
            } else {
                echo "   $method ($hash) -> $subDir -> 无预览文件\n";
            }
        }
    }
    echo "\n";
}

// 分析现有预览文件的命名规律
echo "\n=== 分析现有预览文件命名规律 ===\n";
$previewDir = BILLFISH_PATH . '\.bf\.preview';
$previewPattern = [];

for ($i = 0; $i < 16; $i++) {
    $hex = dechex($i);
    $subDir = $previewDir . '\\' . $hex;
    if (is_dir($subDir)) {
        $files = glob($subDir . '\*.small.webp');
        foreach ($files as $file) {
            $filename = basename($file, '.small.webp');
            if (is_numeric($filename)) {
                $previewPattern[] = intval($filename);
            }
        }
    }
}

if (!empty($previewPattern)) {
    sort($previewPattern);
    echo "数字预览文件ID范围: " . min($previewPattern) . " - " . max($previewPattern) . "\n";
    echo "示例ID: " . implode(', ', array_slice($previewPattern, 0, 10)) . "\n";
}

echo "\n=== 检查 Billfish 数据库映射 ===\n";
// 如果数据库可用，可以尝试读取映射关系
$dbPath = BILLFISH_PATH . '\.bf\billfish.db';
if (file_exists($dbPath)) {
    echo "数据库文件存在，大小: " . formatFileSize(filesize($dbPath)) . "\n";
    
    // 尝试使用不同方法读取数据库
    if (extension_loaded('sqlite3')) {
        try {
            $db = new SQLite3($dbPath);
            $tables = [];
            $result = $db->query("SELECT name FROM sqlite_master WHERE type='table'");
            while ($row = $result->fetchArray()) {
                $tables[] = $row['name'];
            }
            echo "数据库表: " . implode(', ', $tables) . "\n";
            $db->close();
        } catch (Exception $e) {
            echo "SQLite3 读取失败: " . $e->getMessage() . "\n";
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
?>