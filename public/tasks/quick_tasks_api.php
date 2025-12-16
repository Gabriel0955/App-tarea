<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../services/QuickTaskService.php';
require_once __DIR__ . '/../../src/db.php';
header('Content-Type: application/json');
$pdo = get_pdo();
$quickTaskService = new QuickTaskService($pdo);
$userId = $_SESSION['user_id'];

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'create':
        $title = trim($_POST['title'] ?? '');
        $time = $_POST['time'] ?? null;
        $date = $_POST['date'] ?? date('Y-m-d');
        
        if (empty($title)) {
            echo json_encode(['success' => false, 'error' => 'El título es requerido']);
            exit;
        }
        
        $result = $quickTaskService->createQuickTask($userId, $title, '', $date, $time);
        echo json_encode($result);
        break;
        
    case 'complete':
        $taskId = intval($_POST['task_id'] ?? 0);
        
        if ($taskId <= 0) {
            echo json_encode(['success' => false, 'error' => 'ID de tarea inválido']);
            exit;
        }
        
        $result = $quickTaskService->completeQuickTask($taskId, $userId);
        echo json_encode($result);
        break;
        
    case 'uncomplete':
        $taskId = intval($_POST['task_id'] ?? 0);
        
        if ($taskId <= 0) {
            echo json_encode(['success' => false, 'error' => 'ID de tarea inválido']);
            exit;
        }
        
        $result = $quickTaskService->uncompleteQuickTask($taskId, $userId);
        echo json_encode($result);
        break;
        
    case 'delete':
        $taskId = intval($_POST['task_id'] ?? 0);
        
        if ($taskId <= 0) {
            echo json_encode(['success' => false, 'error' => 'ID de tarea inválido']);
            exit;
        }
        
        $result = $quickTaskService->deleteQuickTask($taskId, $userId);
        echo json_encode($result);
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Acción no válida']);
}
