<?php
// 文件监听器 - 检测 Billfish 数据库变化
$billfishPath = 'd:\\VS CODE\\rzxme-billfish\\publish\\assets\\viedeos\\rzxme-billfish';
$dbPath = $billfishPath . '\\.bf\\billfish.db';
$watchFile = dirname(__FILE__) . '/last_check.txt';

// 读取上次检查时间
$lastCheck = 0;
if (file_exists($watchFile)) {
    $lastCheck = intval(file_get_contents($watchFile));
}

$currentTime = time();
$dbTime = file_exists($dbPath) ? filemtime($dbPath) : 0;

$response = [
    'needs_update' => false,
    'last_check' => $lastCheck,
    'current_time' => $currentTime,
    'db_time' => $dbTime,
    'db_time_formatted' => date('Y-m-d H:i:s', $dbTime),
    'time_since_check' => $currentTime - $lastCheck,
    'time_since_db_update' => $currentTime - $dbTime
];

// 如果数据库在上次检查后有更新
if ($dbTime > $lastCheck) {
    $response['needs_update'] = true;
    
    // 更新检查时间
    file_put_contents($watchFile, $currentTime);
}

header('Content-Type: application/json');
echo json_encode($response);
?>