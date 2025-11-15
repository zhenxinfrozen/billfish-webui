<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 模式分析：寻找 Billfish 预览映射的真实模式
$billfishPath = 'd:\\VS CODE\\rzxme-billfish\\publish\\assets\\viedeos\\rzxme-billfish';
$previewDir = $billfishPath . '\\.bf\\.preview';

echo "<h1>Billfish 预览映射模式分析</h1>\n";
echo "<style>body { font-family: monospace; }</style>\n";

// 收集所有视频文件和预览文件的详细信息
function collectFileInfo($path) {
    $info = [];
    $info['path'] = $path;
    $info['basename'] = basename($path);
    $info['size'] = filesize($path);
    $info['mtime'] = filemtime($path);
    $info['ctime'] = filectime($path);
    $info['md5'] = md5_file($path);
    $info['dirname'] = dirname($path);
    return $info;
}

// 收集视频文件信息
echo "<h2>收集视频文件信息...</h2>\n";
$videoFiles = [];
$extensions = ['mp4', 'mkv', 'webm', 'avi', 'mov'];

function scanVideos($dir, &$videoFiles, $extensions) {
    if (!is_dir($dir)) return;
    
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        
        $fullPath = $dir . '\\' . $item;
        if (is_dir($fullPath)) {
            scanVideos($fullPath, $videoFiles, $extensions);
        } else {
            $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
            if (in_array($ext, $extensions)) {
                $videoFiles[] = collectFileInfo($fullPath);
            }
        }
    }
}

scanVideos($billfishPath, $videoFiles, $extensions);

echo "找到 " . count($videoFiles) . " 个视频文件<br>\n";

// 收集预览文件信息
echo "<h2>收集预览文件信息...</h2>\n";
$previewFiles = [];

for ($i = 0; $i < 256; $i++) {
    $subDir = sprintf('%02x', $i);
    $subPath = $previewDir . '\\' . $subDir;
    
    if (is_dir($subPath)) {
        $files = glob($subPath . '\\*.small.webp');
        foreach ($files as $file) {
            $previewFiles[] = collectFileInfo($file);
        }
    }
}

echo "找到 " . count($previewFiles) . " 个预览文件<br>\n";

// 分析时间模式
echo "<h2>时间模式分析</h2>\n";

// 按修改时间排序视频文件
usort($videoFiles, function($a, $b) {
    return $a['mtime'] - $b['mtime'];
});

// 按修改时间排序预览文件
usort($previewFiles, function($a, $b) {
    return $a['mtime'] - $b['mtime'];
});

echo "<h3>最早的10个视频文件（按修改时间）：</h3>\n";
for ($i = 0; $i < min(10, count($videoFiles)); $i++) {
    $file = $videoFiles[$i];
    echo sprintf("%s - %s - Size: %d<br>\n", 
        date('Y-m-d H:i:s', $file['mtime']),
        basename($file['path']),
        $file['size']
    );
}

echo "<h3>最早的10个预览文件（按修改时间）：</h3>\n";
for ($i = 0; $i < min(10, count($previewFiles)); $i++) {
    $file = $previewFiles[$i];
    echo sprintf("%s - %s - Size: %d<br>\n", 
        date('Y-m-d H:i:s', $file['mtime']),
        basename($file['path']),
        $file['size']
    );
}

// 尝试通过文件名模式找到映射
echo "<h2>文件名模式分析</h2>\n";

// 提取预览文件的数字ID
$previewIds = [];
foreach ($previewFiles as $file) {
    if (preg_match('/(\d+)\.small\.webp$/', basename($file['path']), $matches)) {
        $previewIds[$matches[1]] = $file;
    }
}

ksort($previewIds, SORT_NUMERIC);
echo "预览文件 ID 范围：" . min(array_keys($previewIds)) . " - " . max(array_keys($previewIds)) . "<br>\n";
echo "预览文件 ID 数量：" . count($previewIds) . "<br>\n";

// 分析 ID 分布
$idGaps = [];
$prevId = null;
foreach (array_keys($previewIds) as $id) {
    if ($prevId !== null) {
        $gap = $id - $prevId;
        if (!isset($idGaps[$gap])) $idGaps[$gap] = 0;
        $idGaps[$gap]++;
    }
    $prevId = $id;
}

echo "<h3>ID 间隔分布：</h3>\n";
arsort($idGaps);
foreach (array_slice($idGaps, 0, 10, true) as $gap => $count) {
    echo "间隔 $gap: $count 次<br>\n";
}

// 测试：按顺序对应
echo "<h2>顺序对应测试</h2>\n";
echo "假设视频文件按导入顺序对应预览 ID...<br>\n";

$sortedVideoFiles = $videoFiles; // 已经按时间排序
$sortedPreviewIds = array_keys($previewIds);
sort($sortedPreviewIds, SORT_NUMERIC);

echo "<h3>前10个可能的映射关系：</h3>\n";
for ($i = 0; $i < min(10, count($sortedVideoFiles), count($sortedPreviewIds)); $i++) {
    $video = $sortedVideoFiles[$i];
    $previewId = $sortedPreviewIds[$i];
    $previewFile = $previewIds[$previewId];
    
    echo sprintf("视频: %s (%s) -> 预览ID: %d (%s)<br>\n",
        basename($video['path']),
        date('Y-m-d H:i:s', $video['mtime']),
        $previewId,
        date('Y-m-d H:i:s', $previewFile['mtime'])
    );
}

// 文件大小分析
echo "<h2>文件大小相关性分析</h2>\n";

// 检查是否有大小相关的模式
echo "视频文件大小分布：<br>\n";
$videoSizes = array_map(function($f) { return $f['size']; }, $videoFiles);
sort($videoSizes);
echo "最小: " . min($videoSizes) . " bytes<br>\n";
echo "最大: " . max($videoSizes) . " bytes<br>\n";
echo "平均: " . round(array_sum($videoSizes) / count($videoSizes)) . " bytes<br>\n";

echo "<br>预览文件大小分布：<br>\n";
$previewSizes = array_map(function($f) { return $f['size']; }, $previewFiles);
sort($previewSizes);
echo "最小: " . min($previewSizes) . " bytes<br>\n";
echo "最大: " . max($previewSizes) . " bytes<br>\n";
echo "平均: " . round(array_sum($previewSizes) / count($previewSizes)) . " bytes<br>\n";

echo "<h2>分析完成</h2>\n";
echo "请检查上述模式，特别是时间顺序和 ID 分布，这可能揭示 Billfish 的映射逻辑。<br>\n";
?>