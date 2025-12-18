<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../src/db.php';
require_once __DIR__ . '/../../../src/auth.php';
require_once __DIR__ . '/../../../services/TaskService.php';
require_once __DIR__ . '/../../../services/GamificationService.php';

// Verificar permiso de actualización
require_permission('tasks', 'update');

if (!isset($_GET['id'])) {
    header('Location: ../../index.php');
    exit;
}

$pdo = get_pdo();
$user_id = get_current_user_id();
$task_id = intval($_GET['id']);

// Verificar que la tarea pertenece al usuario
$task = getTaskById($pdo, $task_id, $user_id);

if (!$task) {
    header('Location: ../../index.php?error=task_not_found');
    exit;
}

// Verificar que la tarea no requiere documentos (es tarea de proyecto)
if ($task['requires_docs']) {
    header('Location: ../../index.php?error=requires_production');
    exit;
}

// Verificar que no esté ya completada
if ($task['deployed']) {
    header('Location: ../../index.php?error=already_completed');
    exit;
}

// Completar tarea usando servicio
$result = completeTask($pdo, $user_id, $task_id, $task);

if ($result['success']) {
    header('Location: ../../index.php?success=task_completed&points=' . $result['points']);
} else {
    header('Location: ../../index.php?error=database_error');
}
exit;
