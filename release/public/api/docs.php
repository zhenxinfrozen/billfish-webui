<?php
/**
 * 文档API端点
 */

require_once '../config.php';
require_once '../includes/DocumentManager.php';

header('Content-Type: application/json');

$docManager = new DocumentManager();
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'getSections':
            $sections = $docManager->getSections();
            echo json_encode($sections);
            break;

        case 'getDocument':
            $sectionId = $_GET['section'] ?? '';
            $fileName = $_GET['file'] ?? '';
            $document = $docManager->getDocument($sectionId, $fileName);
            
            if ($document) {
                // 渲染Markdown为HTML
                $document['html'] = $docManager->renderMarkdown($document['content']);
                echo json_encode($document);
            } else {
                http_response_code(404);
                echo json_encode(['error' => '文档未找到']);
            }
            break;

        case 'search':
            $query = $_GET['q'] ?? '';
            if (strlen($query) >= 2) {
                $results = $docManager->searchDocuments($query);
                echo json_encode(['results' => $results]);
            } else {
                echo json_encode(['results' => []]);
            }
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => '未知操作']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
