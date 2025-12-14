<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/auth.php';

$user_id = get_current_user_id();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$task_id = intval($_POST['task_id'] ?? 0);
if ($task_id <= 0) {
    header('Location: index.php');
    exit;
}

$pdo = get_pdo();

// Obtener el estado actual de la tarea
$stmt = $pdo->prepare('SELECT doc_plan_prueba, doc_plan_produccion, doc_control_objeto, doc_politica_respaldo FROM tasks WHERE id = ? AND user_id = ?');
$stmt->execute([$task_id, $user_id]);
$current = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$current) {
    header('Location: index.php');
    exit;
}

// Actualizar cada documento (toggle: si estaba en 1 pasa a 0, si estaba en 0 pasa a 1)
$doc_plan_prueba = isset($_POST['doc_plan_prueba']) ? 1 : 0;
$doc_plan_produccion = isset($_POST['doc_plan_produccion']) ? 1 : 0;
$doc_control_objeto = isset($_POST['doc_control_objeto']) ? 1 : 0;
$doc_politica_respaldo = isset($_POST['doc_politica_respaldo']) ? 1 : 0;

// Actualizar solo el documento que cambió
$update_stmt = $pdo->prepare('UPDATE tasks SET doc_plan_prueba = ?, doc_plan_produccion = ?, doc_control_objeto = ?, doc_politica_respaldo = ? WHERE id = ? AND user_id = ?');
$update_stmt->execute([
    $doc_plan_prueba,
    $doc_plan_produccion,
    $doc_control_objeto,
    $doc_politica_respaldo,
    $task_id,
    $user_id
]);

header('Location: index.php');
exit;
