<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/auth.php';

$user_id = get_current_user_id();
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: index.php'); exit; }

$pdo = get_pdo();

// Verificar si la tarea requiere documentos
$stmt = $pdo->prepare('SELECT requires_docs, doc_plan_prueba, doc_plan_produccion, doc_control_objeto, doc_politica_respaldo FROM tasks WHERE id = ? AND user_id = ?');
$stmt->execute([$id, $user_id]);
$task = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$task) {
  header('Location: index.php'); 
  exit;
}

// Si requiere documentos, verificar que todos estén completos
if ($task['requires_docs']) {
  $all_docs_complete = $task['doc_plan_prueba'] && 
                       $task['doc_plan_produccion'] && 
                       $task['doc_control_objeto'] && 
                       $task['doc_politica_respaldo'];
  
  if (!$all_docs_complete) {
    // Redirigir con mensaje de error
    header('Location: edit.php?id=' . $id . '&error=docs_incompletos');
    exit;
  }
}

// Si todo está bien, marcar como desplegado
$stmt = $pdo->prepare('UPDATE tasks SET deployed = 1 WHERE id = ? AND user_id = ?');
$stmt->execute([$id, $user_id]);

header('Location: index.php'); exit;
