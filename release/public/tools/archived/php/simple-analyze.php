<?php
/**
 * Billfish 简化分析工具（不依赖数据库）
 */

require_once 'config.php';

echo "=== Billfish 文件系统分析工具 ===\n\n";

echo "1. 检查基本路径...\n";
echo "Billfish 路径: " . BILLFISH_PATH . "\n";

if (!is_dir(BILLFISH_PATH)) {
    echo "❌ Billfish 目录不存在\n";
    exit(1);
}

echo "✅ Billfish 目录存在\n";

// 检查 .bf 目录
$bfDir = BILLFISH_PATH . '\.bf';
if (is_dir($bfDir)) {
    echo "✅ .bf 数据目录存在\n";
    
    // 列出 .bf 目录内容
    echo "\n2. .bf 目录内容:\n";
    $bfContents = scandir($bfDir);
    foreach ($bfContents as $item) {
        if ($item !== '.' && $item !== '..') {
            $itemPath = $bfDir . '\\' . $item;
            if (is_dir($itemPath)) {
                echo "  📁 $item/\n";
            } else {
                $size = filesize($itemPath);
                echo "  📄 $item (" . formatFileSize($size) . ")\n";
            }
        }
    }
} else {
    echo "❌ .bf 数据目录不存在\n";
}

// 分析配置文件
echo "\n3. 分析配置文件...\n";
$libInfoPath = BILLFISH_PATH . '\.bf\.ui_config\lib_info.json';
$libraryIniPath = BILLFISH_PATH . '\.bf\.ui_config\library.ini';

if (file_exists($libInfoPath)) {
    echo "📄 lib_info.json:\n";
    $libInfo = json_decode(file_get_contents($libInfoPath), true);
    if ($libInfo) {
        foreach ($libInfo as $key => $value) {
            if (is_array($value)) {
                echo "  $key: " . json_encode($value) . "\n";
            } else {
                echo "  $key: $value\n";
            }
        }
    }
    echo "\n";
}

if (file_exists($libraryIniPath)) {
    echo "📄 library.ini:\n";
    $libraryInfo = parse_ini_file($libraryIniPath, true);
    if ($libraryInfo) {
        foreach ($libraryInfo as $section => $data) {
            echo "  [$section]\n";
            foreach ($data as $key => $value) {
                echo "    $key = $value\n";
            }
        }
    }
    echo "\n";
}

// 分析媒体目录
echo "4. 分析媒体目录...\n";
$directories = [
    'animation-clips' => '动画片段',
    'comic-anim' => '动漫动画', 
    'storyboard' => '故事板',
    'test-blender' => 'Blender测试',
    'test-ex' => '测试扩展',
    'test-videos' => '测试视频'
];

$totalFiles = 0;
$totalSize = 0;
$typeStats = [];

foreach ($directories as $dir => $desc) {
    $dirPath = BILLFISH_PATH . '\\' . $dir;
    if (is_dir($dirPath)) {
        echo "\n📁 $dir ($desc):\n";
        
        $files = glob($dirPath . '\*.*');
        $dirFiles = count($files);
        $dirSize = 0;
        
        // 统计文件类型
        $dirTypes = [];
        foreach ($files as $file) {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $size = filesize($file);
            
            $dirTypes[$ext] = ($dirTypes[$ext] ?? 0) + 1;
            $typeStats[$ext] = ($typeStats[$ext] ?? 0) + 1;
            $dirSize += $size;
            $totalSize += $size;
        }
        
        echo "  文件数量: $dirFiles\n";
        echo "  目录大小: " . formatFileSize($dirSize) . "\n";
        
        if (!empty($dirTypes)) {
            echo "  文件类型:\n";
            arsort($dirTypes);
            foreach ($dirTypes as $ext => $count) {
                echo "    .$ext: $count 个\n";
            }
        }
        
        $totalFiles += $dirFiles;
    } else {
        echo "\n❌ $dir 目录不存在\n";
    }
}

echo "\n5. 总体统计:\n";
echo "  总文件数: $totalFiles\n";
echo "  总大小: " . formatFileSize($totalSize) . "\n";

if (!empty($typeStats)) {
    echo "  文件类型分布:\n";
    arsort($typeStats);
    foreach ($typeStats as $ext => $count) {
        $percentage = round(($count / $totalFiles) * 100, 1);
        echo "    .$ext: $count 个 ($percentage%)\n";
    }
}

// 分析预览图片
echo "\n6. 分析预览图片...\n";
$previewDir = BILLFISH_PATH . '\.bf\.preview';
if (is_dir($previewDir)) {
    $previewCount = 0;
    $previewSize = 0;
    $previewTypes = [];
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($previewDir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $ext = strtolower($file->getExtension());
            $previewTypes[$ext] = ($previewTypes[$ext] ?? 0) + 1;
            $previewCount++;
            $previewSize += $file->getSize();
        }
    }
    
    echo "  预览文件总数: $previewCount\n";
    echo "  预览文件大小: " . formatFileSize($previewSize) . "\n";
    
    if (!empty($previewTypes)) {
        echo "  预览文件类型:\n";
        foreach ($previewTypes as $ext => $count) {
            echo "    .$ext: $count 个\n";
        }
    }
    
    // 计算预览覆盖率
    if ($totalFiles > 0) {
        $coverage = round(($previewCount / 2 / $totalFiles) * 100, 1); // 除以2因为有small和hd两种
        echo "  预览覆盖率: 约 $coverage%\n";
    }
} else {
    echo "❌ 预览目录不存在\n";
}

echo "\n7. 系统兼容性检查...\n";
echo "  PHP 版本: " . phpversion() . "\n";
echo "  SQLite3 扩展: " . (extension_loaded('sqlite3') ? '✅ 可用' : '❌ 不可用') . "\n";
echo "  PDO SQLite: " . (class_exists('PDO') && in_array('sqlite', PDO::getAvailableDrivers()) ? '✅ 可用' : '❌ 不可用') . "\n";
echo "  GD 扩展: " . (extension_loaded('gd') ? '✅ 可用' : '❌ 不可用') . "\n";

// 生成推荐配置
echo "\n8. 推荐配置:\n";
echo "根据分析结果，建议的配置:\n";
echo "- 支持的视频格式: " . implode(', ', SUPPORTED_VIDEO_TYPES) . "\n";
echo "- 支持的图片格式: " . implode(', ', SUPPORTED_IMAGE_TYPES) . "\n";
echo "- 每页显示文件数: " . FILES_PER_PAGE . "\n";

if ($totalFiles > FILES_PER_PAGE) {
    $pages = ceil($totalFiles / FILES_PER_PAGE);
    echo "- 预计页数: $pages 页\n";
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
echo "\n🚀 启动 Web 服务器:\n";
echo "  php -S localhost:8000\n";
echo "  然后访问: http://localhost:8000\n";
?>