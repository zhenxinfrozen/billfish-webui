<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 基于创建时间的全新映射方法
$billfishPath = 'd:\\VS CODE\\rzxme-billfish\\publish\\assets\\viedeos\\rzxme-billfish';
$previewDir = $billfishPath . '\\.bf\\.preview';

echo "<h1>基于创建时间的全新映射</h1>\n";
echo "<style>body { font-family: monospace; font-size: 12px; }</style>\n";

// 收集所有视频文件
function collectVideosByCreationTime($dir, $basePath) {
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
                    'full_path' => $fullPath,
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

$videoFiles = collectVideosByCreationTime($billfishPath, $billfishPath);

// 按创建时间排序
usort($videoFiles, function($a, $b) {
    $timeDiff = $a['ctime'] - $b['ctime'];
    if ($timeDiff != 0) return $timeDiff;
    
    // 如果创建时间相同，按路径排序
    return strcmp($a['relative_path'], $b['relative_path']);
});

echo "按创建时间排序的视频文件：" . count($videoFiles) . " 个<br>\n";

// 收集预览文件
$previewFiles = [];
for ($i = 0; $i < 256; $i++) {
    $subDir = sprintf('%02x', $i);
    $subPath = $previewDir . '\\' . $subDir;
    
    if (is_dir($subPath)) {
        $files = glob($subPath . '\\*.small.webp');
        foreach ($files as $file) {
            if (preg_match('/(\d+)\.small\.webp$/', basename($file), $matches)) {
                $id = intval($matches[1]);
                $previewFiles[$id] = str_replace($billfishPath, '', $file);
            }
        }
    }
}

ksort($previewFiles, SORT_NUMERIC);
$previewIds = array_keys($previewFiles);

echo "找到预览文件：" . count($previewFiles) . " 个<br>\n";

// 显示前20个按创建时间排序的文件
echo "<h2>前20个文件（按创建时间）：</h2>\n";
for ($i = 0; $i < min(20, count($videoFiles)); $i++) {
    $file = $videoFiles[$i];
    $previewId = $previewIds[$i] ?? '无';
    echo sprintf("%d: %s (创建: %s) -> 预览ID: %s<br>\n", 
        $i + 1,
        $file['relative_path'],
        date('Y-m-d H:i:s', $file['ctime']),
        $previewId
    );
}

// 创建基于创建时间的映射
$ctimeMapping = [];
for ($i = 0; $i < count($videoFiles) && $i < count($previewIds); $i++) {
    $video = $videoFiles[$i];
    $previewId = $previewIds[$i];
    $previewPath = $previewFiles[$previewId];
    
    $ctimeMapping[$video['relative_path']] = [
        'preview_id' => $previewId,
        'preview_path' => $previewPath,
        'video_size' => $video['size'],
        'index' => $i,
        'ctime' => $video['ctime'],
        'dirname' => $video['dirname']
    ];
}

// 保存基于创建时间的映射
$mappingFile = 'preview-mapping-ctime.json';
file_put_contents($mappingFile, json_encode($ctimeMapping, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "<br>基于创建时间的映射已保存到 {$mappingFile}<br>\n";

// 验证 test-ex 目录在这个排序中的位置
echo "<h2>验证 test-ex 目录位置：</h2>\n";
$testExStart = -1;
foreach ($videoFiles as $index => $file) {
    if (strpos($file['relative_path'], 'test-ex\\') === 0) {
        $testExStart = $index;
        break;
    }
}

if ($testExStart >= 0) {
    echo "test-ex 开始位置：第 " . ($testExStart + 1) . " 个文件<br>\n";
    echo "对应预览ID：" . ($previewIds[$testExStart] ?? '无') . "<br>\n";
    
    echo "<h3>test-ex 目录文件映射：</h3>\n";
    for ($i = $testExStart; $i < count($videoFiles) && $i < $testExStart + 10; $i++) {
        if (strpos($videoFiles[$i]['relative_path'], 'test-ex\\') === 0) {
            $previewId = $previewIds[$i] ?? '无';
            echo sprintf("%s -> 预览ID: %s<br>\n", 
                basename($videoFiles[$i]['relative_path']), 
                $previewId
            );
        }
    }
}

// 分析不同目录在创建时间排序中的分布
echo "<h2>各目录在创建时间排序中的分布：</h2>\n";
$dirDistribution = [];
foreach ($videoFiles as $index => $file) {
    $dir = $file['dirname'];
    if (!isset($dirDistribution[$dir])) {
        $dirDistribution[$dir] = [];
    }
    $dirDistribution[$dir][] = $index + 1;
}

foreach ($dirDistribution as $dir => $positions) {
    echo sprintf("%s: 位置 %d-%d (%d个文件)<br>\n", 
        $dir, 
        min($positions), 
        max($positions), 
        count($positions)
    );
}

echo "<h2>完成</h2>\n";
echo "如果 test-ex 目录在这个排序中映射正确，那么这就是正确的方法！<br>\n";
?>