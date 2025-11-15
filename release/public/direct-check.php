<?php
// 直接检查文件和预览图
$basePath = 'd:\VS CODE\rzxme-billfish\publish\assets\viedeos\rzxme-billfish';

echo "<h2>直接文件系统检查</h2>";

// 检查ID=2的预览图
$fileId = 2;
$hexFolder = sprintf("%02x", $fileId % 256);
echo "<p>文件ID: $fileId</p>";
echo "<p>计算文件夹: $hexFolder</p>";

$previewDir = "$basePath/.bf/.preview/$hexFolder";
echo "<p>预览图目录: $previewDir</p>";
echo "<p>目录存在: " . (is_dir($previewDir) ? 'YES' : 'NO') . "</p>";

if (is_dir($previewDir)) {
    echo "<h3>目录内容:</h3>";
    $files = scandir($previewDir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $fullPath = "$previewDir/$file";
            $size = filesize($fullPath);
            echo "<p>- $file ($size bytes)</p>";
        }
    }
}

// 检查具体的预览图文件
$smallWebp = "$previewDir/$fileId.small.webp";
$hdWebp = "$previewDir/$fileId.hd.webp";

echo "<h3>预览图文件检查:</h3>";
echo "<p>Small: $smallWebp - " . (file_exists($smallWebp) ? 'EXISTS' : 'NOT FOUND') . "</p>";
echo "<p>HD: $hdWebp - " . (file_exists($hdWebp) ? 'EXISTS' : 'NOT FOUND') . "</p>";

// 检查视频文件
echo "<h3>视频文件检查:</h3>";
$videoFile = "$basePath/comic-anim/游戏门户.webm";
echo "<p>视频文件: $videoFile</p>";
echo "<p>文件存在: " . (file_exists($videoFile) ? 'EXISTS' : 'NOT FOUND') . "</p>";
if (file_exists($videoFile)) {
    echo "<p>文件大小: " . filesize($videoFile) . " bytes</p>";
}
?>