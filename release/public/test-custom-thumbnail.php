<?php
require_once 'config.php';
require_once 'includes/BillfishManagerV3.php';

$manager = new BillfishManagerV3(BILLFISH_PATH);

echo "=== 自定义缩略图测试 ===\n\n";

$fileId = 364;
$file = $manager->getFileById($fileId);

echo "文件: {$file['name']}\n";
echo "预览图路径: {$file['preview_path']}\n";
echo "预览图URL: {$file['preview_url']}\n";
echo "有预览图: " . ($file['has_preview'] ? 'YES' : 'NO') . "\n";

if ($file['has_preview']) {
    $filename = basename($file['preview_path']);
    $size = filesize($file['preview_path']);
    $modified = date('Y-m-d H:i:s', filemtime($file['preview_path']));
    
    echo "\n预览图文件详情:\n";
    echo "  文件名: {$filename}\n";
    echo "  大小: {$size} bytes\n";
    echo "  修改时间: {$modified}\n";
    
    if (strpos($filename, '.cover.') !== false) {
        echo "  类型: ✅ 用户自定义缩略图\n";
    } else {
        echo "  类型: 📁 默认缩略图\n";
    }
}

// 检查预览图目录下所有相关文件
echo "\n预览图目录所有文件:\n";
$hexFolder = sprintf("%02x", $fileId % 256);
$previewDir = BILLFISH_PATH . "/.bf/.preview/{$hexFolder}/";
$files = glob($previewDir . $fileId . '*');

foreach ($files as $file) {
    $filename = basename($file);
    $size = filesize($file);
    $modified = date('Y-m-d H:i:s', filemtime($file));
    $type = '';
    
    if (strpos($filename, '.cover.') !== false) {
        $type = ' ← 🎯 用户自定义';
    } elseif (strpos($filename, '.small.') !== false) {
        $type = ' ← 📱 小尺寸';
    } elseif (strpos($filename, '.hd.') !== false) {
        $type = ' ← 🖥️ 高清';
    }
    
    echo "  {$filename} ({$size} bytes, {$modified}){$type}\n";
}
?>