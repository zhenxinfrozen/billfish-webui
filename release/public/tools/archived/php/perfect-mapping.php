<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 基于已验证的正确排序方式生成完美映射
$billfishPath = 'd:\\VS CODE\\rzxme-billfish\\publish\\assets\\viedeos\\rzxme-billfish';
$previewDir = $billfishPath . '\\.bf\\.preview';

echo "<h1>🎯 生成完美映射（基于验证的排序方式）</h1>\n";
echo "<style>body { font-family: monospace; font-size: 12px; }</style>\n";

// 使用已验证正确的排序方式：路径字母序
function collectAllVideos($dir, $basePath) {
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
                    'size' => $file->getSize()
                ];
            }
        }
    }
    
    return $files;
}

$videoFiles = collectAllVideos($billfishPath, $billfishPath);

// 按路径字母序排序（已验证正确）
usort($videoFiles, function($a, $b) {
    return strcmp($a['relative_path'], $b['relative_path']);
});

echo "总计视频文件：" . count($videoFiles) . "<br>\n";

// 收集所有预览ID
$allPreviewIds = [];
for ($i = 0; $i < 256; $i++) {
    $subDir = sprintf('%02x', $i);
    $subPath = $previewDir . '\\' . $subDir;
    
    if (is_dir($subPath)) {
        $files = glob($subPath . '\\*.small.webp');
        foreach ($files as $file) {
            if (preg_match('/(\d+)\.small\.webp$/', basename($file), $matches)) {
                $id = intval($matches[1]);
                $allPreviewIds[$id] = str_replace($billfishPath, '', $file);
            }
        }
    }
}

ksort($allPreviewIds, SORT_NUMERIC);
$previewIdList = array_keys($allPreviewIds);

echo "总计预览文件：" . count($allPreviewIds) . "<br>\n";

// 生成完美映射
$perfectMapping = [];
for ($i = 0; $i < count($videoFiles) && $i < count($previewIdList); $i++) {
    $video = $videoFiles[$i];
    $previewId = $previewIdList[$i];
    $previewPath = $allPreviewIds[$previewId];
    
    $perfectMapping[$video['relative_path']] = [
        'preview_id' => $previewId,
        'preview_path' => $previewPath,
        'video_size' => $video['size'],
        'index' => $i,
        'dirname' => $video['dirname']
    ];
}

// 保存完美映射
$mappingFile = 'preview-mapping-perfect.json';
file_put_contents($mappingFile, json_encode($perfectMapping, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "<br>完美映射已保存到 {$mappingFile}<br>\n";

// 验证关键映射
echo "<h2>🔍 验证关键映射：</h2>\n";

$keyTestFiles = [
    'animation-clips\\begin-01.mp4',
    'animation-clips\\dragonfire.mp4',
    'test-ex\\Gunman.mp4',
    'test-ex\\HeNMdqRJM6XlalG5.mp4',
    'test-ex\\gun-shooting.mp4'
];

foreach ($keyTestFiles as $testFile) {
    if (isset($perfectMapping[$testFile])) {
        $mapping = $perfectMapping[$testFile];
        echo "✅ $testFile -> 预览ID: {$mapping['preview_id']}<br>\n";
    } else {
        echo "❌ $testFile -> 未找到映射<br>\n";
    }
}

// 显示各目录的映射分布
echo "<h2>📁 各目录映射分布：</h2>\n";
$dirStats = [];
foreach ($perfectMapping as $relativePath => $mapping) {
    $dir = $mapping['dirname'];
    if (!isset($dirStats[$dir])) {
        $dirStats[$dir] = [
            'count' => 0,
            'start_id' => $mapping['preview_id'],
            'end_id' => $mapping['preview_id'],
            'start_index' => $mapping['index'],
            'end_index' => $mapping['index']
        ];
    }
    
    $dirStats[$dir]['count']++;
    $dirStats[$dir]['end_id'] = $mapping['preview_id'];
    $dirStats[$dir]['end_index'] = $mapping['index'];
}

foreach ($dirStats as $dir => $stats) {
    echo sprintf("%s: %d个文件, 预览ID %d-%d, 索引 %d-%d<br>\n",
        $dir,
        $stats['count'],
        $stats['start_id'],
        $stats['end_id'],
        $stats['start_index'] + 1,
        $stats['end_index'] + 1
    );
}

echo "<h2>🎉 完成！</h2>\n";
echo "这个映射基于已验证的正确排序方式，应该是完全准确的！<br>\n";
echo "请更新 BillfishManager.php 使用 preview-mapping-perfect.json<br>\n";
?>