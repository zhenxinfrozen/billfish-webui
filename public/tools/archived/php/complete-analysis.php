<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 完整重新分析 - 包含所有目录
$billfishPath = 'd:\\VS CODE\\rzxme-billfish\\publish\\assets\\viedeos\\rzxme-billfish';
$previewDir = $billfishPath . '\\.bf\\.preview';

echo "<h1>完整目录结构分析</h1>\n";
echo "<style>body { font-family: monospace; font-size: 12px; }</style>\n";

// 递归收集所有视频文件，保持完整的目录结构信息
function collectAllVideos($dir, &$videoFiles, $extensions, $basePath, $depth = 0) {
    if (!is_dir($dir)) return;
    
    $items = scandir($dir);
    sort($items); // 按文件名排序
    
    foreach ($items as $item) {
        if ($item === '.' || $item === '..' || $item === '.bf') continue;
        
        $fullPath = $dir . '\\' . $item;
        $relativePath = str_replace($basePath . '\\', '', $fullPath);
        
        if (is_dir($fullPath)) {
            echo str_repeat('  ', $depth) . "📁 $relativePath/<br>\n";
            collectAllVideos($fullPath, $videoFiles, $extensions, $basePath, $depth + 1);
        } else {
            $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
            if (in_array($ext, $extensions)) {
                $videoFiles[] = [
                    'full_path' => $fullPath,
                    'relative_path' => $relativePath,
                    'basename' => basename($fullPath),
                    'dirname' => dirname($relativePath),
                    'depth' => $depth,
                    'mtime' => filemtime($fullPath),
                    'size' => filesize($fullPath)
                ];
                echo str_repeat('  ', $depth) . "📄 $relativePath<br>\n";
            }
        }
    }
}

echo "<h2>完整目录和文件结构：</h2>\n";
$videoFiles = [];
$extensions = ['mp4', 'mkv', 'webm', 'avi', 'mov'];
collectAllVideos($billfishPath, $videoFiles, $extensions, $billfishPath);

echo "<br><strong>总计文件数：" . count($videoFiles) . "</strong><br>\n";

// 按不同方式排序，尝试找到正确的顺序
echo "<h2>不同排序方式测试：</h2>\n";

// 方式1：按目录深度，然后按路径
$sorted1 = $videoFiles;
usort($sorted1, function($a, $b) {
    // 先按目录路径排序
    $dirCompare = strcmp($a['dirname'], $b['dirname']);
    if ($dirCompare !== 0) return $dirCompare;
    
    // 同目录内按文件名排序
    return strcmp($a['basename'], $b['basename']);
});

echo "<h3>方式1 - 按目录路径+文件名：</h3>\n";
for ($i = 0; $i < min(20, count($sorted1)); $i++) {
    echo sprintf("%d: %s<br>\n", $i + 1, $sorted1[$i]['relative_path']);
}

// 方式2：按字母顺序，但目录优先
$sorted2 = $videoFiles;
usort($sorted2, function($a, $b) {
    return strcmp($a['relative_path'], $b['relative_path']);
});

echo "<h3>方式2 - 完全按相对路径字母顺序：</h3>\n";
for ($i = 0; $i < min(20, count($sorted2)); $i++) {
    echo sprintf("%d: %s<br>\n", $i + 1, $sorted2[$i]['relative_path']);
}

// 分析当前界面显示的test-ex目录映射
echo "<h2>分析test-ex目录（据说全对）：</h2>\n";
$testExFiles = array_filter($videoFiles, function($f) {
    return strpos($f['relative_path'], 'test-ex\\') === 0;
});

usort($testExFiles, function($a, $b) {
    return strcmp($a['basename'], $b['basename']);
});

echo "test-ex 目录文件数：" . count($testExFiles) . "<br>\n";
foreach (array_slice($testExFiles, 0, 10) as $i => $file) {
    echo sprintf("test-ex %d: %s<br>\n", $i + 1, $file['basename']);
}

// 获取预览文件信息
$previewIds = [];
for ($i = 0; $i < 256; $i++) {
    $subDir = sprintf('%02x', $i);
    $subPath = $previewDir . '\\' . $subDir;
    
    if (is_dir($subPath)) {
        $files = glob($subPath . '\\*.small.webp');
        foreach ($files as $file) {
            if (preg_match('/(\d+)\.small\.webp$/', basename($file), $matches)) {
                $id = intval($matches[1]);
                $previewIds[$id] = str_replace($billfishPath, '', $file);
            }
        }
    }
}

ksort($previewIds, SORT_NUMERIC);
echo "<br><strong>预览文件ID范围：" . min(array_keys($previewIds)) . " - " . max(array_keys($previewIds)) . "</strong><br>\n";

// 找到test-ex在整体排序中的位置
echo "<h2>找到test-ex在排序中的起始位置：</h2>\n";

foreach ([$sorted1, $sorted2] as $sortMethod => $sortedFiles) {
    echo "<h3>排序方式" . ($sortMethod + 1) . "中test-ex的位置：</h3>\n";
    
    $testExStart = -1;
    foreach ($sortedFiles as $index => $file) {
        if (strpos($file['relative_path'], 'test-ex\\') === 0) {
            $testExStart = $index;
            break;
        }
    }
    
    if ($testExStart >= 0) {
        echo "test-ex 开始位置：第 " . ($testExStart + 1) . " 个文件<br>\n";
        echo "对应的预览ID应该是：" . array_keys($previewIds)[$testExStart] . "<br>\n";
        
        // 显示test-ex前后的文件
        echo "test-ex前的5个文件：<br>\n";
        for ($i = max(0, $testExStart - 5); $i < $testExStart; $i++) {
            echo sprintf("  %d: %s<br>\n", $i + 1, $sortedFiles[$i]['relative_path']);
        }
        
        echo "test-ex的前10个文件：<br>\n";
        for ($i = $testExStart; $i < min(count($sortedFiles), $testExStart + 10); $i++) {
            if (strpos($sortedFiles[$i]['relative_path'], 'test-ex\\') === 0) {
                $expectedPreviewId = array_keys($previewIds)[$i];
                echo sprintf("  %d: %s -> 预览ID %d<br>\n", $i + 1, $sortedFiles[$i]['relative_path'], $expectedPreviewId);
            }
        }
    }
}

echo "<h2>分析完成</h2>\n";
?>