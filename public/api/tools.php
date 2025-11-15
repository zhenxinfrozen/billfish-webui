<?php
/**
 * 工具API端点
 */

require_once '../config.php';
require_once '../includes/ToolManager.php';

header('Content-Type: application/json');

$toolManager = new ToolManager();
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'getTool':
            $toolId = $_GET['id'] ?? '';
            $tool = $toolManager->getTool($toolId);
            if ($tool) {
                echo json_encode($tool);
            } else {
                http_response_code(404);
                echo json_encode(['error' => '工具未找到']);
            }
            break;

        case 'getSource':
            $toolId = $_GET['id'] ?? '';
            $tool = $toolManager->getTool($toolId);
            if ($tool && $tool['type'] === 'python') {
                $source = $toolManager->getToolSource($tool['file']);
                echo json_encode(['source' => $source]);
            } else {
                http_response_code(404);
                echo json_encode(['error' => '源代码未找到']);
            }
            break;

        case 'getArchived':
            $type = $_GET['type'] ?? 'php';
            $tools = $toolManager->getArchivedTools($type);
            echo json_encode(['tools' => $tools]);
            break;

        case 'execute':
            // 执行Python工具
            $toolId = $_POST['id'] ?? '';
            $args = $_POST['args'] ?? [];
            $tool = $toolManager->getTool($toolId);
            
            if ($tool && $tool['type'] === 'python') {
                $result = $toolManager->executePythonTool($tool['file'], $args);
                echo json_encode($result);
            } else {
                http_response_code(400);
                echo json_encode(['error' => '无法执行该工具']);
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
