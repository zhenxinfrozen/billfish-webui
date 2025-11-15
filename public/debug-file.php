<?php
require_once 'config.php';
require_once 'includes/BillfishManagerV3.php';

$manager = new BillfishManagerV3(BILLFISH_PATH);
$id = $_GET['id'] ?? '2';

echo "<h2>文件服务诊断 - ID: $id</h2>";

$file = $manager->getFileById($id);
if (!$file) {
    echo "<p style='color:red'>❌ 文件未找到</p>";
    exit;
}

echo "<h3>📊 文件信息</h3>";
echo "<table border='1'>";
foreach ($file as $key => $value) {
    echo "<tr><td><strong>$key</strong></td><td>$value</td></tr>";
}
echo "</table>";

echo "<h3>📁 路径检查</h3>";
$fullPath = $file['full_path'];
echo "<p><strong>完整路径:</strong> $fullPath</p>";
echo "<p><strong>文件存在:</strong> " . (file_exists($fullPath) ? '✅ YES' : '❌ NO') . "</p>";

if (file_exists($fullPath)) {
    echo "<p><strong>文件大小:</strong> " . filesize($fullPath) . " bytes</p>";
    echo "<p><strong>文件可读:</strong> " . (is_readable($fullPath) ? '✅ YES' : '❌ NO') . "</p>";
}

echo "<h3>🌐 URL测试</h3>";
echo "<p><a href='file-serve.php?id=$id' target='_blank'>🔗 测试file-serve.php</a></p>";
echo "<p><a href='view.php?id=$id' target='_blank'>🔗 测试view.php</a></p>";

if ($file['has_preview']) {
    echo "<p><a href='{$file['preview_url']}' target='_blank'>🔗 测试预览图</a></p>";
}

echo "<h3>🎬 HTML5视频测试</h3>";
echo "<video controls width='400'>";
echo "<source src='file-serve.php?id=$id' type='video/{$file['extension']}'>";
echo "您的浏览器不支持视频播放。";
echo "</video>";
?>