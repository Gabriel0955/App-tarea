<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../services/ProjectService.php';

header('Content-Type: application/json');

$projectService = new ProjectService($pdo);
$userId = $_SESSION['user_id'];

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'create_project':
            $result = $projectService->createProject($userId, $_POST);
            echo json_encode($result);
            break;
            
        case 'update_project':
            $projectId = intval($_POST['project_id']);
            $result = $projectService->updateProject($projectId, $userId, $_POST);
            echo json_encode($result);
            break;
            
        case 'delete':
            $projectId = intval($_POST['project_id']);
            $result = $projectService->deleteProject($projectId, $userId);
            echo json_encode($result);
            break;
            
        case 'create_task':
            $projectId = intval($_POST['project_id']);
            $result = $projectService->createProjectTask($projectId, $userId, $_POST);
            echo json_encode($result);
            break;
            
        case 'complete_task':
            $taskId = intval($_POST['task_id']);
            $hours = floatval($_POST['hours'] ?? 0);
            $result = $projectService->completeProjectTask($taskId, $userId, $hours);
            echo json_encode($result);
            break;
            
        case 'update_task':
            $taskId = intval($_POST['task_id']);
            $result = $projectService->updateProjectTask($taskId, $userId, $_POST);
            echo json_encode($result);
            break;
            
        case 'delete_task':
            $taskId = intval($_POST['task_id']);
            $result = $projectService->deleteProjectTask($taskId, $userId);
            echo json_encode($result);
            break;
            
        case 'deploy':
            $projectId = intval($_POST['project_id']);
            $result = $projectService->deployProject($projectId, $userId);
            echo json_encode($result);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Acción no válida']);
    }
} catch (Exception $e) {
    error_log("Error en project_api: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error del servidor']);
}
