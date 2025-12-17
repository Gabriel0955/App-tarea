<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../services/TaskService.php';
require_once __DIR__ . '/../../services/GamificationService.php';

if (!isset($_GET['id'])) {
    header('Location: ../index.php');
    exit;
}

$pdo = get_pdo();
$user_id = get_current_user_id();
$task_id = intval($_GET['id']);

// Verificar que la tarea pertenece al usuario
$task = getTaskById($pdo, $task_id, $user_id);

if (!$task) {
    header('Location: ../index.php?error=task_not_found');
    exit;
}

// Verificar que la tarea no requiere documentos (es tarea de proyecto)
if ($task['requires_docs']) {
    header('Location: ../index.php?error=requires_production');
    exit;
}

// Verificar que no esté ya completada
if ($task['deployed']) {
    header('Location: ../index.php?error=already_completed');
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Marcar tarea como completada (deployed = 1)
    $stmt = $pdo->prepare('UPDATE tasks SET deployed = 1, deployed_at = NOW(), deployed_by = ? WHERE id = ? AND user_id = ?');
    $stmt->execute([$user_id, $task_id, $user_id]);
    
    // Otorgar puntos por completar tarea de proyecto
    $points = 10; // Puntos base por completar tarea
    
    // Bonus si se completa antes de la fecha límite
    if ($task['due_date'] && strtotime($task['due_date']) >= time()) {
        $points += 5; // +5 puntos por completar a tiempo
    }
    
    // Registrar puntos en historial
    $stmt = $pdo->prepare("
        INSERT INTO points_history (user_id, points, reason, reference_type, reference_id, created_at)
        VALUES (?, ?, ?, 'task_completed', ?, NOW())
    ");
    $stmt->execute([$user_id, $points, 'Tarea de proyecto completada', $task_id]);
    
    // Actualizar puntos totales del usuario y tareas completadas
    $stmt = $pdo->prepare("
        UPDATE user_stats 
        SET total_points = total_points + ?, 
            tasks_completed = tasks_completed + 1,
            last_activity_date = CURRENT_DATE
        WHERE user_id = ?
    ");
    $stmt->execute([$points, $user_id]);
    
    // Actualizar racha
    $stmt = $pdo->prepare("SELECT update_user_streak(?)");
    $stmt->execute([$user_id]);
    
    // Verificar logros
    $stmt = $pdo->prepare("SELECT check_and_unlock_achievements(?)");
    $stmt->execute([$user_id]);
    
    $pdo->commit();
    
    header('Location: ../index.php?success=task_completed&points=' . $points);
    exit;
    
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Error al completar tarea: " . $e->getMessage());
    header('Location: ../index.php?error=database_error');
    exit;
}
