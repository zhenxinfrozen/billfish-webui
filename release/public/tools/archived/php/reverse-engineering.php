<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 反向工程：从已知正确的test-ex映射推断规律
$billfishPath = 'd:\\VS CODE\\rzxme-billfish\\publish\\assets\\viedeos\\rzxme-billfish';

echo "<h1>反向工程映射规律</h1>\n";
echo "<style>body { font-family: monospace; font-size: 12px; }</style>\n";

// 已知test-ex目录映射正确，从这里开始推断
$knownCorrectMappings = [
    'test-ex\\Gunman.mp4' => 360,
    'test-ex\\HeNMdqRJM6XlalG5.mp4' => 362,
    'test-ex\\InslYLSFPcMk6L7i.mp4' => 364,
    'test-ex\\JMy0bqyHaScJUxxl.mp4' => 366,
    'test-ex\\JNQep4IYaQvHD67F.mp4' => 368,
    'test-ex\\KinVqa1GUtdm6Ir1.mp4' => 370,
    'test-ex\\gdwLfK5F02X7oVz1.mp4' => 372,
    'test-ex\\gun-shooting.mp4' => 374
];

echo "<h2>已知正确的映射（test-ex目录）：</h2>\n";
foreach ($knownCorrectMappings as $file => $previewId) {
    echo "$file -> $previewId<br>\n";
}

// 现在尝试找到这8个文件在不同排序中的位置
function getAllVideoFiles($dir, $basePath) {
    $files = [];
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $pathInfo = pathinfo($file->getPathname());
            $ext = strtolower($pathInfo['extension'] ?? '');
            
            if (in_array($ext, ['mp4', 'mkv', 'webm', 'avi', 'mov'])) {
                $fullPath = $file->getPathname();
                $relativePath = str_replace($basePath . '\\', '', $fullPath);
                
                $files[] = [
                    'relative_path' => $relativePath,
                    'basename' => $file->getBasename(),
                    'dirname' => dirname($relativePath),
                    'mtime' => $file->getMTime(),
                    'ctime' => $file->getCTime(),
                    'size' => $file->getSize()
                ];
            }
        }
    }
    
    return $files;
}

$allVideos = getAllVideoFiles($billfishPath, $billfishPath);

// 尝试多种排序方式，看哪种让test-ex文件出现在正确位置
$sortMethods = [
    '路径字母序' => function($a, $b) { return strcmp($a['relative_path'], $b['relative_path']); },
    '修改时间' => function($a, $b) { return $a['mtime'] - $b['mtime']; },
    '创建时间' => function($a, $b) { return $a['ctime'] - $b['ctime']; },
    '文件大小' => function($a, $b) { return $a['size'] - $b['size']; },
    '文件名长度' => function($a, $b) { return strlen($a['basename']) - strlen($b['basename']); },
    '目录+文件名' => function($a, $b) {
        $dirCmp = strcmp($a['dirname'], $b['dirname']);
        return $dirCmp != 0 ? $dirCmp : strcmp($a['basename'], $b['basename']);
    }
];

// 预览ID序列（按顺序）
$allPreviewIds = [];
$previewDir = $billfishPath . '\\.bf\\.preview';
for ($i = 0; $i < 256; $i++) {
    $subDir = sprintf('%02x', $i);
    $subPath = $previewDir . '\\' . $subDir;
    
    if (is_dir($subPath)) {
        $files = glob($subPath . '\\*.small.webp');
        foreach ($files as $file) {
            if (preg_match('/(\d+)\.small\.webp$/', basename($file), $matches)) {
                $allPreviewIds[] = intval($matches[1]);
            }
        }
    }
}
sort($allPreviewIds, SORT_NUMERIC);

echo "<h2>测试各种排序方式：</h2>\n";

foreach ($sortMethods as $methodName => $sortFunc) {
    $sortedVideos = $allVideos;
    usort($sortedVideos, $sortFunc);
    
    echo "<h3>$methodName 排序：</h3>\n";
    
    // 找到第一个test-ex文件的位置
    $firstTestExPos = -1;
    foreach ($sortedVideos as $index => $video) {
        if ($video['relative_path'] === 'test-ex\\Gunman.mp4') {
            $firstTestExPos = $index;
            break;
        }
    }
    
    if ($firstTestExPos >= 0) {
        $expectedPreviewId = $allPreviewIds[$firstTestExPos] ?? '无';
        $correctPreviewId = $knownCorrectMappings['test-ex\\Gunman.mp4'];
        
        echo "Gunman.mp4 位置：第" . ($firstTestExPos + 1) . "个文件<br>\n";
        echo "预期预览ID：$expectedPreviewId<br>\n";
        echo "正确预览ID：$correctPreviewId<br>\n";
        
        if ($expectedPreviewId == $correctPreviewId) {
            echo "<strong>✅ 匹配！这可能是正确的排序方式！</strong><br>\n";
            
            // 显示这种排序方式下test-ex的所有文件
            echo "验证其他test-ex文件：<br>\n";
            $testExFiles = array_slice($sortedVideos, $firstTestExPos, 8);
            foreach ($testExFiles as $i => $video) {
                if (strpos($video['relative_path'], 'test-ex\\') === 0) {
                    $pos = $firstTestExPos + $i;
                    $expectedId = $allPreviewIds[$pos] ?? '无';
                    $correctId = $knownCorrectMappings[$video['relative_path']] ?? '无';
                    
                    $match = ($expectedId == $correctId) ? '✅' : '❌';
                    echo "$match {$video['basename']} -> 预期:$expectedId, 正确:$correctId<br>\n";
                }
            }
        } else {
            echo "❌ 不匹配<br>\n";
        }
    } else {
        echo "❌ 未找到Gunman.mp4<br>\n";
    }
    
    echo "<br>\n";
}

echo "<h2>如果找到正确的排序方式，我们就能生成完美的映射！</h2>\n";
?>