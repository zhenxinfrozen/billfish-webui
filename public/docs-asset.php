<?php
/**
 * 文档静态资源安全服务
 * 用于在 public 目录下访问仓库中的文档图片资源
 */

$repoRoot = realpath(__DIR__ . '/..');
$path = $_GET['path'] ?? '';

if (!$repoRoot || $path === '') {
    http_response_code(400);
    exit('Invalid request');
}

$decodedPath = urldecode($path);
if (preg_match('/(^[A-Za-z]:)|(^\\/)|(^\\\\)|\.\./', $decodedPath)) {
    http_response_code(400);
    exit('Invalid path');
}

$target = realpath($repoRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $decodedPath));
if (!$target || strpos($target, $repoRoot) !== 0 || !is_file($target)) {
    http_response_code(404);
    exit('Not found');
}

$ext = strtolower(pathinfo($target, PATHINFO_EXTENSION));
$mimeTypes = [
    'png' => 'image/png',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'gif' => 'image/gif',
    'webp' => 'image/webp',
    'bmp' => 'image/bmp',
    'svg' => 'image/svg+xml',
    'avif' => 'image/avif'
];

if (!isset($mimeTypes[$ext])) {
    http_response_code(403);
    exit('Unsupported file type');
}

header('Content-Type: ' . $mimeTypes[$ext]);
header('Content-Length: ' . filesize($target));
header('Cache-Control: public, max-age=3600');
readfile($target);
