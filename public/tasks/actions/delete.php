<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../src/db.php';
require_once __DIR__ . '/../../../src/auth.php';
require_once __DIR__ . '/../../../services/TaskService.php';
require_once __DIR__ . '/../../../services/GamificationService.php';

// Verificar permiso de eliminación
require_permission('tasks', 'delete');
require_once __DIR__ . '/../../../services/GamificationService.php';

$user_id = get_current_user_id();
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: ../../index.php'); exit; }

$pdo = get_pdo();

// Obtener información de la tarea antes de eliminarla
$stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $user_id]);
$task = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$task) {
    header('Location: ../../index.php?error=task_not_found'); 
    exit;
}

// Si la tarea estaba completada, restar los puntos
if ($task['deployed']) {
    // Calcular puntos a restar (10 base + 5 bonus si tenía fecha límite y se completó antes)
    $points_to_deduct = 10;
    
    if (!empty($task['due_date']) && !empty($task['deployed_at'])) {
        $due_date = strtotime($task['due_date']);
        $deployed_date = strtotime($task['deployed_at']);
        if ($deployed_date <= $due_date) {
            $points_to_deduct += 5; // Bonus que también hay que restar
        }
    }
    
    // Restar puntos
    deductPoints($pdo, $user_id, $points_to_deduct, 
                 "Tarea eliminada: " . substr($task['title'], 0, 50), 
                 'task', $id);
}

// Eliminar la tarea
deleteTask($pdo, $id, $user_id);

header('Location: ../../index.php?success=task_deleted&points_deducted=' . ($points_to_deduct ?? 0)); 
exit;
