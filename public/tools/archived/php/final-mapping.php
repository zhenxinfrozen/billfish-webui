<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 基于完整分析结果重新生成正确映射
$billfishPath = 'd:\\VS CODE\\rzxme-billfish\\publish\\assets\\viedeos\\rzxme-billfish';
$previewDir = $billfishPath . '\\.bf\\.preview';

echo "<h1>生成最终正确映射</h1>\n";
echo "<style>body { font-family: monospace; font-size: 12px; }</style>\n";

// 完整收集所有视频文件
function collectAllVideosFinal($dir, &$videoFiles, $extensions, $basePath) {
    if (!is_dir($dir)) return;
    
    $items = scandir($dir);
    sort($items); // 按文件名排序
    
    foreach ($items as $item) {
        if ($item === '.' || $item === '..' || $item === '.bf') continue;
        
        $fullPath = $dir . '\\' . $item;
        $relativePath = str_replace($basePath . '\\', '', $fullPath);
        
        if (is_dir($fullPath)) {
            collectAllVideosFinal($fullPath, $videoFiles, $extensions, $basePath);
        } else {
            $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
            if (in_array($ext, $extensions)) {
                $videoFiles[] = [
                    'full_path' => $fullPath,
                    'relative_path' => $relativePath,
                    'basename' => basename($fullPath),
                    'dirname' => dirname($relativePath),
                    'mtime' => filemtime($fullPath),
                    'size' => filesize($fullPath)
                ];
            }
        }
    }
}

$videoFiles = [];
$extensions = ['mp4', 'mkv', 'webm', 'avi', 'mov'];
collectAllVideosFinal($billfishPath, $videoFiles, $extensions, $billfishPath);

// 按正确顺序排序：目录路径 + 文件名
usort($videoFiles, function($a, $b) {
    // 先按目录路径排序
    $dirCompare = strcmp($a['dirname'], $b['dirname']);
    if ($dirCompare !== 0) return $dirCompare;
    
    // 同目录内按文件名排序
    return strcmp($a['basename'], $b['basename']);
});

echo "总计视频文件：" . count($videoFiles) . "<br>\n";

// 收集预览文件 ID
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
$previewIdList = array_keys($previewIds);

echo "总计预览文件：" . count($previewIds) . "<br>\n";

// 构建最终映射
$finalMapping = [];

for ($i = 0; $i < count($videoFiles) && $i < count($previewIdList); $i++) {
    $video = $videoFiles[$i];
    $previewId = $previewIdList[$i];
    $previewPath = $previewIds[$previewId];
    
    $finalMapping[$video['relative_path']] = [
        'preview_id' => $previewId,
        'preview_path' => $previewPath,
        'video_size' => $video['size'],
        'index' => $i,
        'dirname' => $video['dirname']
    ];
}

// 保存最终映射
$mappingFile = 'preview-mapping-final.json';
file_put_contents($mappingFile, json_encode($finalMapping, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "<br>最终映射已保存到 {$mappingFile}<br>\n";

// 验证test-ex目录的映射（应该全对）
echo "<h2>验证test-ex目录映射（应该正确）：</h2>\n";
$testExFiles = array_filter($finalMapping, function($mapping) {
    return strpos($mapping['dirname'], 'test-ex') === 0;
});

foreach ($testExFiles as $relativePath => $mapping) {
    echo sprintf("%s -> 预览ID: %d<br>\n", 
        basename($relativePath), 
        $mapping['preview_id']
    );
}

// 验证其他目录的开始位置
echo "<h2>各目录在映射中的起始位置：</h2>\n";
$directories = [];
foreach ($finalMapping as $relativePath => $mapping) {
    $dir = $mapping['dirname'];
    if (!isset($directories[$dir])) {
        $directories[$dir] = [
            'start_index' => $mapping['index'],
            'start_preview_id' => $mapping['preview_id'],
            'first_file' => basename($relativePath)
        ];
    }
}

foreach ($directories as $dir => $info) {
    echo sprintf("%s: 索引 %d, 预览ID %d, 首文件: %s<br>\n", 
        $dir, 
        $info['start_index'] + 1, 
        $info['start_preview_id'], 
        $info['first_file']
    );
}

echo "<h2>完成</h2>\n";
echo "最终映射已生成，请更新 BillfishManager.php 使用 preview-mapping-final.json<br>\n";
?>