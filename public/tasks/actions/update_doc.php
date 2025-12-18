<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../src/db.php';
require_once __DIR__ . '/../../../src/auth.php';
require_once __DIR__ . '/../../../services/TaskService.php';

// Verificar permiso de actualización
require_permission('tasks', 'update');

$user_id = get_current_user_id();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../index.php');
    exit;
}

$task_id = intval($_POST['task_id'] ?? 0);
if ($task_id <= 0) {
    header('Location: ../../index.php');
    exit;
}

$pdo = get_pdo();

// Verificar que la tarea existe usando servicio
$task = getTaskById($pdo, $task_id, $user_id);

if (!$task) {
    header('Location: ../../index.php');
    exit;
}

// Actualizar documentos
$documents = [
    'doc_plan_prueba' => isset($_POST['doc_plan_prueba']) ? 1 : 0,
    'doc_plan_produccion' => isset($_POST['doc_plan_produccion']) ? 1 : 0,
    'doc_control_objeto' => isset($_POST['doc_control_objeto']) ? 1 : 0,
    'doc_politica_respaldo' => isset($_POST['doc_politica_respaldo']) ? 1 : 0
];

// Actualizar usando servicio
updateTaskDocuments($pdo, $task_id, $user_id, $documents);

header('Location: ../../index.php');
exit;
