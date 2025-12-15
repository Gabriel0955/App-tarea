<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/auth.php';

$user_id = get_current_user_id();

// Manejar POST del modal con checklist
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) { header('Location: index.php'); exit; }
    
    $pdo = get_pdo();
    
    // Verificar propiedad de la tarea
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
            header('Location: edit.php?id=' . $id . '&error=docs_incompletos');
            exit;
        }
    }
    
    // Obtener datos del checklist y notas
    $checklist_backup = isset($_POST['checklist_backup']) ? 1 : 0;
    $checklist_tests = isset($_POST['checklist_tests']) ? 1 : 0;
    $checklist_docs = isset($_POST['checklist_docs']) ? 1 : 0;
    $checklist_team = isset($_POST['checklist_team']) ? 1 : 0;
    $deployment_duration = intval($_POST['deployment_duration'] ?? 0) ?: null;
    $deployment_notes = trim($_POST['deployment_notes'] ?? '');
    
    // Obtener información de la tarea para calcular puntos
    $stmt = $pdo->prepare('SELECT due_date, priority, urgency FROM tasks WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, $user_id]);
    $task_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Marcar como desplegado con toda la información
    $stmt = $pdo->prepare('UPDATE tasks SET deployed = 1, deployed_at = NOW(), deployed_by = ?, deployment_notes = ?, deployment_duration = ?, checklist_backup = ?, checklist_tests = ?, checklist_docs = ?, checklist_team = ?, status = ? WHERE id = ? AND user_id = ?');
    $stmt->execute([$user_id, $deployment_notes, $deployment_duration, $checklist_backup, $checklist_tests, $checklist_docs, $checklist_team, 'Desplegada', $id, $user_id]);
    
    // Registrar en el historial
    $stmt = $pdo->prepare('INSERT INTO task_history (task_id, user_id, action, new_values) VALUES (?, ?, ?, ?)');
    $history_data = json_encode([
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
    ]);
    $stmt->execute([$id, $user_id, 'deployed', $history_data]);
    
    // ====== GAMIFICACIÓN: OTORGAR PUNTOS ======
    try {
        // Calcular puntos base
        $base_points = 20; // Puntos por completar tarea
        
        // Bonus por prioridad
        $priority = $task_info['priority'] ?? 'Medio';
        if ($priority === 'Crítico') {
            $priority_bonus = 15;
        } elseif ($priority === 'Alto') {
            $priority_bonus = 10;
        } elseif ($priority === 'Medio') {
            $priority_bonus = 5;
        } else {
            $priority_bonus = 3;
        }
        
        // Bonus por urgencia
        $urgency = $task_info['urgency'] ?? 'Media';
        if ($urgency === 'Alta') {
            $urgency_bonus = 10;
        } elseif ($urgency === 'Media') {
            $urgency_bonus = 5;
        } else {
            $urgency_bonus = 3;
        }
        
        // Bonus por puntualidad (completada antes de vencer)
        $on_time_bonus = 0;
        $is_on_time = false;
        if ($task_info['due_date']) {
            $due_date = new DateTime($task_info['due_date']);
            $now = new DateTime();
            if ($now <= $due_date) {
                $on_time_bonus = 10;
                $is_on_time = true;
            }
        }
        
        // Bonus por checklist completo (todos los items marcados)
        $checklist_bonus = 0;
        if ($checklist_backup && $checklist_tests && $checklist_docs && $checklist_team) {
            $checklist_bonus = 5;
        }
        
        $total_points = $base_points + $priority_bonus + $urgency_bonus + $on_time_bonus + $checklist_bonus;
        
        // Actualizar estadísticas del usuario
        $update_stats = "INSERT INTO user_stats (user_id, tasks_completed, tasks_deployed, tasks_on_time) 
                         VALUES (:user_id, 1, 1, :on_time)
                         ON CONFLICT (user_id) DO UPDATE SET
                           tasks_completed = user_stats.tasks_completed + 1,
                           tasks_deployed = user_stats.tasks_deployed + 1,
                           tasks_on_time = user_stats.tasks_on_time + :on_time";
        $stmt = $pdo->prepare($update_stats);
        $stmt->execute([
            'user_id' => $user_id,
            'on_time' => $is_on_time ? 1 : 0
        ]);
        
        // Actualizar racha
        $stmt = $pdo->prepare("SELECT update_user_streak(:user_id)");
        $stmt->execute(['user_id' => $user_id]);
        
        // Otorgar puntos
        $stmt = $pdo->prepare("SELECT award_points(:user_id, :points, :reason, 'task', :task_id)");
        $stmt->execute([
            'user_id' => $user_id,
            'points' => $total_points,
            'reason' => 'Tarea completada y desplegada',
            'task_id' => $id
        ]);
        
        // Verificar y desbloquear logros
        $stmt = $pdo->prepare("SELECT * FROM check_and_unlock_achievements(:user_id)");
        $stmt->execute(['user_id' => $user_id]);
        $achievements = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Si se desbloquearon logros, guardar en sesión para mostrar notificación
        if (!empty($achievements)) {
            $_SESSION['new_achievements'] = $achievements;
        }
    } catch (Exception $e) {
        // Silenciar errores de gamificación para no afectar el flujo principal
        error_log("Error en gamificación: " . $e->getMessage());
    }
    // ====== FIN GAMIFICACIÓN ======
    
    header('Location: index.php?deployed=success'); 
    exit;
}

// Manejar GET legacy (sin modal) - redirigir a index para usar modal
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: index.php'); exit; }

header('Location: index.php#deploy-' . $id); 
exit;
