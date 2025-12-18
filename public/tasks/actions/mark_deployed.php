<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../src/db.php';
require_once __DIR__ . '/../../../src/auth.php';
require_once __DIR__ . '/../../../services/TaskService.php';
require_once __DIR__ . '/../../../services/GamificationService.php';

// Verificar permiso de actualización
require_permission('tasks', 'update');

$user_id = get_current_user_id();

// Manejar POST del modal con checklist
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) { header('Location: ../../index.php'); exit; }
    
    $pdo = get_pdo();
    
    // Verificar si la tarea puede desplegarse usando servicio
    if (!canTaskBeDeployed($pdo, $id, $user_id)) {
        header('Location: ../edit.php?id=' . $id . '&error=docs_incompletos');
        exit;
    }
    
    // Obtener datos del checklist y notas
    $checklist_backup = isset($_POST['checklist_backup']) ? 1 : 0;
    $checklist_tests = isset($_POST['checklist_tests']) ? 1 : 0;
    $checklist_docs = isset($_POST['checklist_docs']) ? 1 : 0;
    $checklist_team = isset($_POST['checklist_team']) ? 1 : 0;
    $deployment_duration = intval($_POST['deployment_duration'] ?? 0) ?: null;
    $deployment_notes = trim($_POST['deployment_notes'] ?? '');
    
    // Obtener información de la tarea
    $task_info = getTaskById($pdo, $id, $user_id);
    
    // Marcar como desplegado usando servicio
    $deployment_data = [
        'notes' => $deployment_notes,
        'duration' => $deployment_duration,
        'checklist_backup' => $checklist_backup,
        'checklist_tests' => $checklist_tests,
        'checklist_docs' => $checklist_docs,
        'checklist_team' => $checklist_team
    ];
    markTaskAsDeployed($pdo, $id, $user_id, $deployment_data);
    
    // Registrar en el historial
    $history_data = [
        'deployed' => 1,
        'deployed_at' => date('Y-m-d H:i:s'),
        'deployment_duration' => $deployment_duration,
        'deployment_notes' => $deployment_notes,
        'checklist' => [
            'backup' => $checklist_backup,
            'tests' => $checklist_tests,
            'docs' => $checklist_docs,
            'team' => $checklist_team
        ]
    ];
    addTaskHistory($pdo, $id, $user_id, 'deployed', [], $history_data);
    
    // Procesar gamificación usando servicio
    $gamification_result = processDeploymentWithGamification($pdo, $id, $user_id, $task_info);
    
    // Si se desbloquearon logros, guardar en sesión para mostrar notificación
    if (!empty($gamification_result['achievements'])) {
        $_SESSION['new_achievements'] = $gamification_result['achievements'];
    }
    
    header('Location: ../../index.php?deployed=success'); 
    exit;
}

// Manejar GET legacy (sin modal) - redirigir a index para usar modal
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: ../../index.php'); exit; }

header('Location: ../../index.php#deploy-' . $id); 
exit;
