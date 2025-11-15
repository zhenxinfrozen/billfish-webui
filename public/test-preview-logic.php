<?php
require_once 'config.php';

// 测试preview.php的逻辑
$fileId = 364;
$hexFolder = sprintf("%02x", $fileId % 256);
$basePath = BILLFISH_PATH . '/.bf/.preview/' . $hexFolder . '/' . $fileId;

echo "=== Preview.php 缩略图选择测试 ===\n\n";
echo "文件ID: {$fileId}\n";
echo "Hex文件夹: {$hexFolder}\n";
echo "基础路径: {$basePath}\n\n";

echo "检查文件存在性:\n";

$files = [
    '.cover.png' => $basePath . '.cover.png',
    '.cover.webp' => $basePath . '.cover.webp', 
    '.small.webp' => $basePath . '.small.webp',
    '.hd.webp' => $basePath . '.hd.webp'
];

$selectedFile = null;
foreach ($files as $name => $path) {
    $exists = file_exists($path);
    $size = $exists ? filesize($path) : 0;
    $modified = $exists ? date('Y-m-d H:i:s', filemtime($path)) : '';
    
    echo "  {$name}: " . ($exists ? "✅ 存在" : "❌ 不存在");
    if ($exists) {
        echo " ({$size} bytes, {$modified})";
        if ($selectedFile === null) {
            $selectedFile = $path;
            echo " ← 🎯 将被选择";
        }
    }
    echo "\n";
}

echo "\n最终选择的文件: " . ($selectedFile ? basename($selectedFile) : "无") . "\n";

// 模拟preview.php的选择逻辑
$fullPath = null;
if (file_exists($basePath . '.cover.png')) {
    $fullPath = $basePath . '.cover.png';
} elseif (file_exists($basePath . '.cover.webp')) {
    $fullPath = $basePath . '.cover.webp';
} elseif (file_exists($basePath . '.small.webp')) {
    $fullPath = $basePath . '.small.webp';
} elseif (file_exists($basePath . '.hd.webp')) {
    $fullPath = $basePath . '.hd.webp';
}

echo "\nPreview.php将返回: " . ($fullPath ? basename($fullPath) : "无文件") . "\n";

if ($fullPath) {
    $mime = 'image/webp';
    if (strpos($fullPath, '.png') !== false) {
        $mime = 'image/png';
    }
    echo "Content-Type: {$mime}\n";
}
?>