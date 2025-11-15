<?php
/**
 * 文件服务脚本 - 用于安全地提供文件访问
 */

require_once 'config.php';
require_once 'includes/BillfishManagerV3.php';

$manager = new BillfishManagerV3(BILLFISH_PATH);

$id = $_GET['id'] ?? '';
if (!$id) {
    http_response_code(404);
    exit('文件未找到');
}

$file = $manager->getFileById($id);
if (!$file) {
    http_response_code(404);
    exit('文件未找到');
}

$filePath = $file['full_path'];  // 使用完整路径而不是相对路径
if (!file_exists($filePath)) {
    http_response_code(404);
    exit('文件不存在');
}

// 设置正确的 MIME 类型
$extension = strtolower($file['extension']);
$mimeTypes = [
    'mp4' => 'video/mp4',
    'webm' => 'video/webm',
    'avi' => 'video/x-msvideo',
    'mov' => 'video/quicktime',
    'mkv' => 'video/x-matroska',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'webp' => 'image/webp',
    'bmp' => 'image/bmp'
];

$mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';

// 设置响应头
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($filePath));
header('Accept-Ranges: bytes');

// 支持范围请求（用于视频流）
if (isset($_SERVER['HTTP_RANGE'])) {
    $size = filesize($filePath);
    $ranges = [];
    
    if (preg_match('/bytes=\h*(\d+)-(\d*)[\D.*]?/i', $_SERVER['HTTP_RANGE'], $matches)) {
        $start = intval($matches[1]);
        $end = !empty($matches[2]) ? intval($matches[2]) : $size - 1;
        
        if ($start < $size && $end < $size) {
            header('HTTP/1.1 206 Partial Content');
            header('Content-Range: bytes ' . $start . '-' . $end . '/' . $size);
            header('Content-Length: ' . ($end - $start + 1));
            
            $file = fopen($filePath, 'rb');
            fseek($file, $start);
            echo fread($file, $end - $start + 1);
            fclose($file);
            exit;
        }
    }
}

// 直接输出文件
readfile($filePath);
?>