<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../services/ProjectService.php';
require_once __DIR__ . '/../../src/db.php';
header('Content-Type: application/json');
$pdo = get_pdo();
$projectService = new ProjectService($pdo);
$userId = $_SESSION['user_id'];

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'create':
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $color = $_POST['color'] ?? '#1976d2';
        $icon = $_POST['icon'] ?? '📁';
        
        if (empty($name)) {
            header('Location: projects.php?error=name_required');
            exit;
        }
        
        $result = $projectService->createProject($userId, $name, $description, $color, $icon);
        
        if ($result['success']) {
            header('Location: projects.php?success=project_created');
        } else {
            header('Location: projects.php?error=create_failed');
        }
        exit;
        break;
        
    case 'update':
        $projectId = intval($_POST['project_id'] ?? 0);
        
        if ($projectId <= 0) {
            echo json_encode(['success' => false, 'error' => 'ID inválido']);
            exit;
        }
        
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'color' => $_POST['color'] ?? null,
            'icon' => $_POST['icon'] ?? null,
            'status' => $_POST['status'] ?? null
        ];
        
        // Eliminar campos vacíos
        $data = array_filter($data, function($value) {
            return $value !== null && $value !== '';
        });
        
        $result = $projectService->updateProject($projectId, $userId, $data);
        echo json_encode($result);
        break;
        
    case 'delete':
        $projectId = intval($_POST['project_id'] ?? 0);
        
        if ($projectId <= 0) {
            echo json_encode(['success' => false, 'error' => 'ID inválido']);
            exit;
        }
        
        $result = $projectService->deleteProject($projectId, $userId);
        echo json_encode($result);
        break;
        
    case 'complete':
        $projectId = intval($_POST['project_id'] ?? 0);
        
        if ($projectId <= 0) {
            echo json_encode(['success' => false, 'error' => 'ID inválido']);
            exit;
        }
        
        $result = $projectService->completeProject($projectId, $userId);
        echo json_encode($result);
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Acción no válida']);
}
