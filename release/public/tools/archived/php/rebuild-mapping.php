<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 精确重建映射 - 基于发现的模式
$billfishPath = 'd:\\VS CODE\\rzxme-billfish\\publish\\assets\\viedeos\\rzxme-billfish';
$previewDir = $billfishPath . '\\.bf\\.preview';

echo "<h1>精确重建 Billfish 映射</h1>\n";
echo "<style>body { font-family: monospace; font-size: 12px; }</style>\n";

// 收集所有视频文件（按正确顺序）
function collectVideos($dir, &$videoFiles, $extensions, $basePath) {
    if (!is_dir($dir)) return;
    
    $items = scandir($dir);
    // 按文件名排序，确保一致的顺序
    sort($items);
    
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        
        $fullPath = $dir . '\\' . $item;
        if (is_dir($fullPath)) {
            collectVideos($fullPath, $videoFiles, $extensions, $basePath);
        } else {
            $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
            if (in_array($ext, $extensions)) {
                $relativePath = str_replace($basePath . '\\', '', $fullPath);
                $videoFiles[] = [
                    'full_path' => $fullPath,
                    'relative_path' => $relativePath,
                    'basename' => basename($fullPath),
                    'mtime' => filemtime($fullPath),
                    'size' => filesize($fullPath)
                ];
            }
        }
    }
}

$videoFiles = [];
$extensions = ['mp4', 'mkv', 'webm', 'avi', 'mov'];
collectVideos($billfishPath, $videoFiles, $extensions, $billfishPath);

echo "收集到 " . count($videoFiles) . " 个视频文件<br>\n";

// 按多个条件排序，确保与 Billfish 的导入顺序一致
// 可能的排序方式：1) 目录结构 2) 文件名 3) 时间
usort($videoFiles, function($a, $b) {
    // 首先按目录排序
    $dirA = dirname($a['relative_path']);
    $dirB = dirname($b['relative_path']);
    
    $dirCompare = strcmp($dirA, $dirB);
    if ($dirCompare !== 0) {
        return $dirCompare;
    }
    
    // 同目录内按文件名排序
    return strcmp($a['basename'], $b['basename']);
});

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
echo "收集到 " . count($previewIds) . " 个预览文件<br>\n";

// 构建精确映射
$mapping = [];
$previewIdList = array_keys($previewIds);

echo "<h2>构建映射关系</h2>\n";

for ($i = 0; $i < count($videoFiles) && $i < count($previewIdList); $i++) {
    $video = $videoFiles[$i];
    $previewId = $previewIdList[$i];
    $previewPath = $previewIds[$previewId];
    
    $mapping[$video['relative_path']] = [
        'preview_id' => $previewId,
        'preview_path' => $previewPath,
        'video_size' => $video['size'],
        'index' => $i
    ];
    
    // 显示前 20 个映射关系用于验证
    if ($i < 20) {
        echo sprintf("映射 %d: %s -> ID %d<br>\n", 
            $i + 1, 
            $video['relative_path'], 
            $previewId
        );
    }
}

// 保存映射到文件
$mappingFile = 'preview-mapping-v2.json';
file_put_contents($mappingFile, json_encode($mapping, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "<br>映射已保存到 {$mappingFile}<br>\n";
echo "总计映射关系：" . count($mapping) . " 个<br>\n";

// 验证前几个特定文件
echo "<h2>验证特定文件映射</h2>\n";
$testFiles = [
    'animation-clips\\begin-01.mp4',
    'animation-clips\\dragonfire.mp4', 
    'animation-clips\\shooting-01.mp4',
    'animation-clips\\xxx06.mp4'
];

foreach ($testFiles as $testFile) {
    if (isset($mapping[$testFile])) {
        $info = $mapping[$testFile];
        echo sprintf("%s -> 预览ID: %d, 路径: %s<br>\n",
            $testFile,
            $info['preview_id'],
            $info['preview_path']
        );
    } else {
        echo "$testFile: 未找到映射<br>\n";
    }
}

echo "<h2>完成</h2>\n";
echo "新的映射文件已生成，请更新 BillfishManager.php 使用 preview-mapping-v2.json<br>\n";
?>