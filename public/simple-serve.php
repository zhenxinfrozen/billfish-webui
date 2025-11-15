<?php
require_once 'config.php';
require_once 'includes/BillfishManagerV3.php';

$manager = new BillfishManagerV3(BILLFISH_PATH);

$id = $_GET['id'] ?? '';
if (!$id) {
    die('No ID provided');
}

echo "<h2>简化文件服务测试 - ID: $id</h2>";

$file = $manager->getFileById($id);
if (!$file) {
    die('File not found in database');
}

echo "<p>文件名: {$file['name']}</p>";
echo "<p>扩展名: {$file['extension']}</p>";
echo "<p>完整路径: {$file['full_path']}</p>";

if (!file_exists($file['full_path'])) {
    die("文件不存在: {$file['full_path']}");
}

echo "<p>文件存在: ✅</p>";
echo "<p>文件大小: " . filesize($file['full_path']) . " bytes</p>";

// 尝试简单的文件输出
if (isset($_GET['download'])) {
    header('Content-Type: video/' . $file['extension']);
    header('Content-Length: ' . filesize($file['full_path']));
    readfile($file['full_path']);
    exit;
}

echo "<p><a href='?id=$id&download=1'>📥 直接下载测试</a></p>";

echo "<h3>视频播放测试</h3>";
echo "<video controls width='400'>";
echo "<source src='?id=$id&download=1' type='video/{$file['extension']}'>";
echo "浏览器不支持视频播放";
echo "</video>";
?>