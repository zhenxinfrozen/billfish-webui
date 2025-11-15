<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 全新方法：通过真实的 Billfish 数据库分析
$billfishPath = 'd:\\VS CODE\\rzxme-billfish\\publish\\assets\\viedeos\\rzxme-billfish';
$previewDir = $billfishPath . '\\.bf\\.preview';
$dbPath = $billfishPath . '\\.bf\\billfish.db';

echo "<h1>全新方法：深度分析 Billfish 映射</h1>\n";
echo "<style>body { font-family: monospace; font-size: 12px; }</style>\n";

// 方法1：分析预览文件的创建时间模式
echo "<h2>方法1：分析预览文件时间戳</h2>\n";

$previewFiles = [];
for ($i = 0; $i < 256; $i++) {
    $subDir = sprintf('%02x', $i);
    $subPath = $previewDir . '\\' . $subDir;
    
    if (is_dir($subPath)) {
        $files = glob($subPath . '\\*.small.webp');
        foreach ($files as $file) {
            if (preg_match('/(\d+)\.small\.webp$/', basename($file), $matches)) {
                $id = intval($matches[1]);
                $previewFiles[$id] = [
                    'path' => $file,
                    'mtime' => filemtime($file),
                    'ctime' => filectime($file),
                    'size' => filesize($file)
                ];
            }
        }
    }
}

ksort($previewFiles, SORT_NUMERIC);
echo "找到 " . count($previewFiles) . " 个预览文件<br>\n";

// 分析时间聚类
echo "<h3>预览文件创建时间分析：</h3>\n";
$timeGroups = [];
foreach ($previewFiles as $id => $info) {
    $timeKey = date('Y-m-d H:i', $info['mtime']);
    if (!isset($timeGroups[$timeKey])) {
        $timeGroups[$timeKey] = [];
    }
    $timeGroups[$timeKey][] = $id;
}

foreach ($timeGroups as $time => $ids) {
    echo sprintf("%s: %d 个文件 (ID: %s)<br>\n", 
        $time, 
        count($ids), 
        implode(', ', array_slice($ids, 0, 10)) . (count($ids) > 10 ? '...' : '')
    );
}

// 方法2：分析文件内容特征
echo "<h2>方法2：分析预览文件大小分布</h2>\n";

$sizeGroups = [];
foreach ($previewFiles as $id => $info) {
    $sizeRange = floor($info['size'] / 10000) * 10000; // 按10KB分组
    if (!isset($sizeGroups[$sizeRange])) {
        $sizeGroups[$sizeRange] = [];
    }
    $sizeGroups[$sizeRange][] = $id;
}

echo "<h3>按文件大小分组：</h3>\n";
ksort($sizeGroups);
foreach (array_slice($sizeGroups, 0, 10) as $sizeRange => $ids) {
    echo sprintf("%d-%d KB: %d 个文件<br>\n", 
        $sizeRange/1000, 
        ($sizeRange+9999)/1000, 
        count($ids)
    );
}

// 方法3：收集视频文件并尝试不同的排序方式
echo "<h2>方法3：尝试多种文件排序方式</h2>\n";

function scanAllVideos($dir, $basePath) {
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

$videoFiles = scanAllVideos($billfishPath, $billfishPath);
echo "找到 " . count($videoFiles) . " 个视频文件<br>\n";

// 尝试不同的排序方式
$sortMethods = [
    '按修改时间' => function($a, $b) { return $a['mtime'] - $b['mtime']; },
    '按创建时间' => function($a, $b) { return $a['ctime'] - $b['ctime']; },
    '按文件大小' => function($a, $b) { return $a['size'] - $b['size']; },
    '按路径字母序' => function($a, $b) { return strcmp($a['relative_path'], $b['relative_path']); },
    '按文件名字母序' => function($a, $b) { return strcmp($a['basename'], $b['basename']); }
];

foreach ($sortMethods as $methodName => $sortFunc) {
    $sortedFiles = $videoFiles;
    usort($sortedFiles, $sortFunc);
    
    echo "<h3>$methodName 排序前10个文件：</h3>\n";
    for ($i = 0; $i < min(10, count($sortedFiles)); $i++) {
        $file = $sortedFiles[$i];
        echo sprintf("%d: %s (时间: %s, 大小: %d)<br>\n", 
            $i + 1,
            $file['relative_path'],
            date('Y-m-d H:i:s', $file['mtime']),
            $file['size']
        );
    }
    echo "<br>\n";
}

// 方法4：查找可能的配置文件
echo "<h2>方法4：搜索 Billfish 配置文件</h2>\n";

$bfDir = $billfishPath . '\\.bf';
if (is_dir($bfDir)) {
    $configFiles = glob($bfDir . '\\*');
    echo "在 .bf 目录找到的文件：<br>\n";
    foreach ($configFiles as $file) {
        if (is_file($file)) {
            $size = filesize($file);
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            echo sprintf("%s (%s, %d bytes)<br>\n", 
                basename($file), 
                $ext, 
                $size
            );
            
            // 如果是小文件，可能是配置文件
            if ($size < 10000 && in_array($ext, ['json', 'txt', 'cfg', 'ini'])) {
                echo "内容预览：<br>\n";
                echo "<pre>" . htmlspecialchars(file_get_contents($file)) . "</pre><br>\n";
            }
        }
    }
}

// 方法5：尝试从文件名推断映射模式
echo "<h2>方法5：文件名模式分析</h2>\n";

// 检查是否有明显的文件名模式可以映射到预览ID
$previewIds = array_keys($previewFiles);
echo "预览ID范围：" . min($previewIds) . " - " . max($previewIds) . "<br>\n";
echo "预览ID间隔分析：<br>\n";

$gaps = [];
for ($i = 1; $i < count($previewIds); $i++) {
    $gap = $previewIds[$i] - $previewIds[$i-1];
    if (!isset($gaps[$gap])) $gaps[$gap] = 0;
    $gaps[$gap]++;
}

arsort($gaps);
foreach (array_slice($gaps, 0, 5, true) as $gap => $count) {
    echo "间隔 $gap: $count 次<br>\n";
}

echo "<h2>分析完成</h2>\n";
echo "请检查以上多种方法的结果，寻找可能的映射模式线索。<br>\n";
?>