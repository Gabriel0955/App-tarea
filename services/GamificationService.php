<?php
/**
 * Servicio de gamificación
 * Contiene todas las funciones relacionadas con sistema de puntos, logros y pomodoro
 */

/**
 * Obtener estadísticas de usuario
 */
function getUserStats($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT * FROM user_stats WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Si no existe, crear registro
    if (!$stats) {
        $pdo->prepare("INSERT INTO user_stats (user_id) VALUES (?)")->execute([$user_id]);
        return [
            'total_points' => 0,
            'current_level' => 1,
            'points_to_next_level' => 100,
            'current_streak' => 0,
            'longest_streak' => 0,
            'tasks_completed' => 0,
            'pomodoros_completed' => 0,
            'total_focus_time' => 0
        ];
    }
    
    // Calcular nivel correcto basado en puntos totales
    $total_points = (int)$stats['total_points'];
    $calculated_level = 1;
    
    // Calcular nivel: 100 puntos = nivel 2, 300 puntos = nivel 3, etc.
    // Nivel 1: 0-99 puntos
    // Nivel 2: 100-299 puntos
    // Nivel 3: 300+ puntos
    $cumulative_points = 0;
    while (true) {
        $points_for_next_level = (int)floor(100 * $calculated_level * pow(1.5, $calculated_level - 1));
        if ($total_points >= $cumulative_points + $points_for_next_level) {
            $cumulative_points += $points_for_next_level;
            $calculated_level++;
        } else {
            break;
        }
    }
    
    // Calcular puntos necesarios para el siguiente nivel
    $points_needed_for_next = (int)floor(100 * $calculated_level * pow(1.5, $calculated_level - 1));
    $cumulative_for_current_level = $cumulative_points;
    
    // Si el nivel calculado es diferente al guardado, actualizar inmediatamente
    if ($calculated_level != (int)$stats['current_level']) {
        $stmt = $pdo->prepare("UPDATE user_stats SET current_level = ?, points_to_next_level = ? WHERE user_id = ?");
        $stmt->execute([$calculated_level, $cumulative_for_current_level + $points_needed_for_next, $user_id]);
        $stats['current_level'] = $calculated_level;
        $stats['points_to_next_level'] = $cumulative_for_current_level + $points_needed_for_next;
    }
    
    return $stats;
}

/**
 * Guardar sesión Pomodoro
 */
function savePomodoroSession($pdo, $user_id, $task_id, $work_duration, $status) {
    $stmt = $pdo->prepare("INSERT INTO pomodoro_sessions 
                     (user_id, task_id, work_duration, status, completed_at, focus_score)
                     VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, 100)
                     RETURNING id");
    $stmt->execute([$user_id, $task_id, $work_duration, $status]);
    return $stmt->fetchColumn();
}

/**
 * Actualizar estadísticas después de Pomodoro
 */
function updateStatsAfterPomodoro($pdo, $user_id, $work_duration) {
    $stmt = $pdo->prepare("UPDATE user_stats 
                     SET pomodoros_completed = pomodoros_completed + 1,
                         total_focus_time = total_focus_time + ?
                     WHERE user_id = ?");
    return $stmt->execute([$work_duration, $user_id]);
}

/**
 * Actualizar racha de usuario
 */
function updateUserStreak($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT update_user_streak(?)");
    $stmt->execute([$user_id]);
    return $stmt->fetchColumn();
}

/**
 * Otorgar puntos a usuario
 */
function awardPoints($pdo, $user_id, $points, $reason, $source, $reference_id = null) {
    $stmt = $pdo->prepare("SELECT award_points(?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $points, $reason, $source, $reference_id]);
    return $stmt->fetchColumn();
}

/**
 * Restar puntos a usuario (cuando se elimina una tarea completada)
 */
function deductPoints($pdo, $user_id, $points, $reason, $reference_type = 'task', $reference_id = null) {
    try {
        // Restar puntos de user_stats
        $stmt = $pdo->prepare("UPDATE user_stats SET total_points = GREATEST(0, total_points - ?) WHERE user_id = ?");
        $stmt->execute([$points, $user_id]);
        
        // Registrar en historial con puntos negativos
        $stmt = $pdo->prepare("INSERT INTO points_history (user_id, points, reason, reference_type, reference_id) 
                               VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, -$points, $reason, $reference_type, $reference_id]);
        
        // Decrementar contador de tareas completadas si es una tarea
        if ($reference_type === 'task') {
            $stmt = $pdo->prepare("UPDATE user_stats SET tasks_completed = GREATEST(0, tasks_completed - 1) WHERE user_id = ?");
            $stmt->execute([$user_id]);
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Error restando puntos: " . $e->getMessage());
        return false;
    }
}

/**
 * Verificar y desbloquear logros
 */
function checkAndUnlockAchievements($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT * FROM check_and_unlock_achievements(?)");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Obtener historial de sesiones Pomodoro
 */
function getPomodoroHistory($pdo, $user_id, $limit = 10) {
    $stmt = $pdo->prepare("SELECT ps.*, t.title as task_title 
                           FROM pomodoro_sessions ps
                           LEFT JOIN tasks t ON ps.task_id = t.id
                           WHERE ps.user_id = ?
                           ORDER BY ps.started_at DESC
                           LIMIT ?");
    $stmt->execute([$user_id, $limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Obtener logros desbloqueados recientemente
 */
function getRecentAchievements($pdo, $user_id, $limit = 5) {
    $stmt = $pdo->prepare("SELECT a.*, ua.unlocked_at
                       FROM user_achievements ua
                       JOIN achievements a ON ua.achievement_id = a.id
                       WHERE ua.user_id = ?
                       ORDER BY ua.unlocked_at DESC
                       LIMIT ?");
    $stmt->execute([$user_id, $limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Obtener todos los logros con estado de desbloqueo
 */
function getAllAchievements($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT a.*, 
                       ua.unlocked_at,
                       CASE WHEN ua.id IS NOT NULL THEN TRUE ELSE FALSE END as is_unlocked
                       FROM achievements a
                       LEFT JOIN user_achievements ua ON a.id = ua.achievement_id AND ua.user_id = ?
                       ORDER BY 
                         is_unlocked DESC,
                         CASE a.tier
                           WHEN 'diamond' THEN 5
                           WHEN 'platinum' THEN 4
                           WHEN 'gold' THEN 3
                           WHEN 'silver' THEN 2
                           ELSE 1
                         END DESC,
                         a.points DESC");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Obtener ranking global
 */
function getGlobalRanking($pdo, $limit = 50) {
    $stmt = $pdo->prepare("SELECT u.id, u.username, us.*,
                  ROW_NUMBER() OVER (ORDER BY us.total_points DESC) as rank
                  FROM user_stats us
                  JOIN users u ON us.user_id = u.id
                  ORDER BY us.total_points DESC
                  LIMIT ?");
    $stmt->execute([$limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Calcular puntos necesarios para alcanzar un nivel específico (NO acumulativo, solo para ese nivel)
 */
function calculatePointsForLevel($level) {
    // Nivel 1->2: 100 puntos
    // Nivel 2->3: 150 puntos (100 * 1.5)
    // Nivel 3->4: 225 puntos (150 * 1.5)
    // etc.
    if ($level <= 1) return 0;
    return (int)floor(100 * ($level - 1) * pow(1.5, $level - 2));
}

/**
 * Calcular progreso de nivel (porcentaje dentro del nivel actual)
 */
function calculateLevelProgress($total_points, $points_to_next_level) {
    // Calcular nivel actual
    $current_level = 1;
    $cumulative_points = 0;
    
    while (true) {
        $points_for_next = (int)floor(100 * $current_level * pow(1.5, $current_level - 1));
        if ($total_points >= $cumulative_points + $points_for_next) {
            $cumulative_points += $points_for_next;
            $current_level++;
        } else {
            break;
        }
    }
    
    // Puntos en el nivel actual
    $points_in_current_level = $total_points - $cumulative_points;
    $points_needed_for_next = (int)floor(100 * $current_level * pow(1.5, $current_level - 1));
    
    if ($points_needed_for_next <= 0) {
        return 100;
    }
    
    return min(100, ($points_in_current_level / $points_needed_for_next) * 100);
}

/**
 * Calcular progreso de logros
 */
function calculateAchievementProgress($achievements) {
    $total = count($achievements);
    if ($total === 0) {
        return 0;
    }
    $unlocked = count(array_filter($achievements, fn($a) => $a['is_unlocked']));
    return ($unlocked / $total) * 100;
}

/**
 * Procesar sesión Pomodoro completa con gamificación
 */
function processPomodoroCompletion($pdo, $user_id, $task_id, $work_duration) {
    try {
        $pdo->beginTransaction();
        
        // Guardar sesión
        $session_id = savePomodoroSession($pdo, $user_id, $task_id, $work_duration, 'completed');
        
        // Actualizar estadísticas
        updateStatsAfterPomodoro($pdo, $user_id, $work_duration);
        
        // Actualizar racha
        updateUserStreak($pdo, $user_id);
        
        // Otorgar puntos (10 puntos por Pomodoro)
        awardPoints($pdo, $user_id, 10, 'Pomodoro completado', 'pomodoro', $session_id);
        
        // Verificar logros
        $achievements = checkAndUnlockAchievements($pdo, $user_id);
        
        $pdo->commit();
        
        return [
            'success' => true,
            'session_id' => $session_id,
            'achievements' => $achievements
        ];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Procesar deployment con gamificación
 */
function processDeploymentWithGamification($pdo, $task_id, $user_id, $task_info) {
    try {
        // Calcular puntos
        $points_data = calculateDeploymentPoints($task_info);
        
        // Otorgar puntos
        awardPoints($pdo, $user_id, $points_data['total'], 'Tarea desplegada', 'deployment', $task_id);
        
        // Actualizar contador de tareas completadas
        $pdo->prepare("UPDATE user_stats SET tasks_completed = tasks_completed + 1 WHERE user_id = ?")->execute([$user_id]);
        
        // Actualizar racha
        updateUserStreak($pdo, $user_id);
        
        // Verificar logros
        $achievements = checkAndUnlockAchievements($pdo, $user_id);
        
        return [
            'success' => true,
            'points_awarded' => $points_data,
            'achievements' => $achievements
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Calcular puntos de deployment
 */
function calculateDeploymentPoints($task_info) {
    $base_points = 20;
    
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
    
    // Bonus por puntualidad
    $on_time_bonus = 0;
    $is_on_time = false;
    if (!empty($task_info['due_date'])) {
        $due_date = strtotime($task_info['due_date']);
        $today = strtotime(date('Y-m-d'));
        if ($today <= $due_date) {
            $on_time_bonus = 15;
            $is_on_time = true;
        }
    }
    
    $total = $base_points + $priority_bonus + $urgency_bonus + $on_time_bonus;
    
    return [
        'total' => $total,
        'base' => $base_points,
        'priority_bonus' => $priority_bonus,
        'urgency_bonus' => $urgency_bonus,
        'on_time_bonus' => $on_time_bonus,
        'is_on_time' => $is_on_time
    ];
}
