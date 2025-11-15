<?php
require_once 'config.php';
require_once 'includes/BillfishManagerV3.php';

$manager = new BillfishManagerV3(BILLFISH_PATH);

echo "<h2>🎬 视频文件列表</h2>";

// 获取所有文件
$db = new SQLite3(BILLFISH_PATH . '/.bf/billfish.db');
$result = $db->query("
    SELECT f.id, f.name, f.file_size, fo.name as folder_name
    FROM bf_file f
    LEFT JOIN bf_folder fo ON f.pid = fo.id
    WHERE f.is_hide = 0 
    AND (f.name LIKE '%.mp4' OR f.name LIKE '%.webm' OR f.name LIKE '%.avi' OR f.name LIKE '%.mov')
    LIMIT 10
");

echo "<table border='1'>";
echo "<tr><th>ID</th><th>文件名</th><th>文件夹</th><th>大小</th><th>操作</th></tr>";

while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $size = round($row['file_size'] / 1024 / 1024, 2);
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$row['name']}</td>";
    echo "<td>{$row['folder_name']}</td>";
    echo "<td>{$size} MB</td>";
    echo "<td>";
    echo "<a href='debug-file.php?id={$row['id']}' target='_blank'>🔍 调试</a> | ";
    echo "<a href='view.php?id={$row['id']}' target='_blank'>👁️ 查看</a>";
    echo "</td>";
    echo "</tr>";
}

echo "</table>";
$db->close();
?>