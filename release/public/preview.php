<?php
/**
 * 预览图片服务 - 直接查询SQLite数据库
 */

require_once 'config.php';

// 获取文件ID
$fileId = $_GET['id'] ?? '';
if (!$fileId || !is_numeric($fileId)) {
    http_response_code(404);
    exit('预览图片未找到');
}

// 连接数据库
$dbPath = BILLFISH_PATH . '/.bf/billfish.db';
if (!file_exists($dbPath)) {
    http_response_code(500);
    exit('数据库不存在');
}

try {
    $db = new SQLite3($dbPath, SQLITE3_OPEN_READONLY);
    
    // 获取文件信息和缩略图状态
    $fileQuery = "
        SELECT f.name, f.pid, m.thumb_tid
        FROM bf_file f
        LEFT JOIN bf_material_v2 m ON f.id = m.file_id
        WHERE f.id = ? AND f.is_hide = 0
    ";
    $stmt = $db->prepare($fileQuery);
    $stmt->bindValue(1, $fileId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $fileInfo = $result->fetchArray(SQLITE3_ASSOC);
    
    if (!$fileInfo) {
        http_response_code(404);
        exit('文件不存在');
    }
    
    $fullPath = null;
    
    // 根据thumb_tid决定使用缩略图还是原图
    if ($fileInfo['thumb_tid'] == 60) {
        // 有缩略图，使用Billfish的标准缩略图路径
        $hexFolder = sprintf("%02x", $fileId % 256);
        $basePath = BILLFISH_PATH . '/.bf/.preview/' . $hexFolder . '/' . $fileId;
        
        // 优先级: 自定义缩略图 > 默认缩略图
        if (file_exists($basePath . '.cover.png')) {
            $fullPath = $basePath . '.cover.png';
        } elseif (file_exists($basePath . '.cover.webp')) {
            $fullPath = $basePath . '.cover.webp';
        } elseif (file_exists($basePath . '.small.webp')) {
            $fullPath = $basePath . '.small.webp';
        } elseif (file_exists($basePath . '.hd.webp')) {
            $fullPath = $basePath . '.hd.webp';
        }
    } else {
        // thumb_tid = 0，没有缩略图，使用原图
        // 构建完整文件夹路径
        $folderPath = '';
        $currentId = $fileInfo['pid'];
        $pathParts = [];
        
        while ($currentId) {
            $folderQuery = "SELECT name, pid FROM bf_folder WHERE id = ?";
            $folderStmt = $db->prepare($folderQuery);
            $folderStmt->bindValue(1, $currentId, SQLITE3_INTEGER);
            $folderResult = $folderStmt->execute();
            $folderRow = $folderResult->fetchArray(SQLITE3_ASSOC);
            
            if (!$folderRow) break;
            
            array_unshift($pathParts, $folderRow['name']);
            $currentId = $folderRow['pid'];
        }
        
        $folderPath = implode('/', $pathParts);
        
        // 构建原始文件路径
        $originalPath = BILLFISH_PATH;
        if (!empty($folderPath)) {
            $originalPath .= '/' . $folderPath;
        }
        $originalPath .= '/' . $fileInfo['name'];
        
        if (file_exists($originalPath)) {
            $fullPath = $originalPath;
        }
    }
    
    // 关闭数据库
    $db->close();
    
    if (!$fullPath || !is_file($fullPath)) {
        http_response_code(404);
        exit('预览图片文件不存在');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    exit('数据库错误: ' . $e->getMessage());
}

// 设置正确的 MIME 类型
$extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
$mimeTypes = [
    'webp' => 'image/webp',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif'
];

$mimeType = $mimeTypes[$extension] ?? 'image/webp';

// 设置缓存头
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($fullPath));
header('Cache-Control: public, max-age=3600'); // 缓存1小时
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($fullPath)) . ' GMT');

// 输出文件
readfile($fullPath);
?>