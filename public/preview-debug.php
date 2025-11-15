<?php
require_once 'config.php';
require_once 'includes/BillfishManagerV3.php';

$manager = new BillfishManagerV3(BILLFISH_PATH);
$id = $_GET['id'] ?? '2';

$file = $manager->getFileById($id);

echo "<h2>预览图诊断 - ID: $id</h2>";
echo "<p>文件名: {$file['name']}</p>";

echo "<h3>预览图检查</h3>";
echo "<p>has_preview: " . ($file['has_preview'] ? '✅ YES' : '❌ NO') . "</p>";
echo "<p>preview_path: " . ($file['preview_path'] ?: '无') . "</p>";
echo "<p>preview_url: {$file['preview_url']}</p>";

if ($file['preview_path']) {
    echo "<p>预览图文件存在: " . (file_exists($file['preview_path']) ? '✅ YES' : '❌ NO') . "</p>";
}

// 手动计算预览图路径
$hexFolder = sprintf("%02x", $id % 256);
$manualPath = BILLFISH_PATH . "/.bf/.preview/{$hexFolder}/{$id}.small.webp";
echo "<h3>手动计算路径</h3>";
echo "<p>计算路径: $manualPath</p>";
echo "<p>文件存在: " . (file_exists($manualPath) ? '✅ YES' : '❌ NO') . "</p>";

// 检查预览图目录
$previewDir = BILLFISH_PATH . "/.bf/.preview/{$hexFolder}";
echo "<p>目录存在: " . (is_dir($previewDir) ? '✅ YES' : '❌ NO') . "</p>";

if (is_dir($previewDir)) {
    echo "<h3>目录内容</h3>";
    $files = scandir($previewDir);
    foreach ($files as $f) {
        if ($f !== '.' && $f !== '..') {
            echo "<p>- $f</p>";
        }
    }
}

echo "<h3>预览图测试</h3>";
if ($file['has_preview']) {
    echo "<img src='{$file['preview_url']}' style='max-width:300px' alt='预览图'>";
} else {
    echo "<p>无预览图</p>";
}
?>