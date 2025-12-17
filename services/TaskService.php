<?php
/**
 * Servicio de gestión de tareas
 * Contiene todas las funciones relacionadas con operaciones de tareas
 */

/**
 * Obtener estadísticas del dashboard del usuario
 */
function getTaskStatistics($pdo, $user_id) {
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN deployed = 0 THEN 1 ELSE 0 END) as pendientes,
            SUM(CASE WHEN deployed = 1 THEN 1 ELSE 0 END) as desplegados,
            SUM(CASE WHEN urgency = 'Alta' AND deployed = 0 THEN 1 ELSE 0 END) as urgentes,
            SUM(CASE WHEN due_date IS NOT NULL AND due_date < CURRENT_DATE AND deployed = 0 THEN 1 ELSE 0 END) as vencidos,
            SUM(CASE WHEN due_date IS NOT NULL AND due_date BETWEEN CURRENT_DATE AND CURRENT_DATE + INTERVAL '7 days' AND deployed = 0 THEN 1 ELSE 0 END) as proximos
        FROM tasks WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

/**
 * Obtener tareas filtradas con búsqueda
 */
function getTasksFiltered($pdo, $user_id, $search = '', $filter = '', $category = '', $priority = '') {
    $sql = 'SELECT * FROM tasks WHERE user_id = ?';
    $params = [$user_id];

    if ($search) {
        $sql .= ' AND (title ILIKE ? OR description ILIKE ?)';
        $search_param = '%' . $search . '%';
        $params[] = $search_param;
        $params[] = $search_param;
    }

    if ($filter === 'pending') {
        $sql .= ' AND deployed = 0';
    } elseif ($filter === 'deployed') {
        $sql .= ' AND deployed = 1';
    } elseif ($filter === 'urgent') {
        $sql .= ' AND urgency = \'Alta\' AND deployed = 0';
    } elseif ($filter === 'overdue') {
        $sql .= ' AND due_date < CURRENT_DATE AND deployed = 0';
    }

    if ($category && $category !== 'all') {
        $sql .= ' AND category = ?';
        $params[] = $category;
    }

    if ($priority && $priority !== 'all') {
        $sql .= ' AND priority = ?';
        $params[] = $priority;
    }

    $sql .= ' ORDER BY created_at DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Crear nueva tarea
 */
function createTask($pdo, $user_id, $data) {
    $stmt = $pdo->prepare('INSERT INTO tasks (user_id, title, description, urgency, priority, category, due_date, deployed, project_id, requires_docs, doc_plan_prueba, doc_plan_produccion, doc_control_objeto, doc_politica_respaldo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    
    return $stmt->execute([
        $user_id,
        $data['title'],
        $data['description'],
        $data['urgency'],
        $data['priority'],
        $data['category'],
        $data['due_date'],
        $data['deployed'],
        $data['project_id'] ?? null,
        $data['requires_docs'],
        $data['doc_plan_prueba'],
        $data['doc_plan_produccion'],
        $data['doc_control_objeto'],
        $data['doc_politica_respaldo']
    ]);
}

/**
 * Obtener tarea por ID
 */
function getTaskById($pdo, $task_id, $user_id) {
    $stmt = $pdo->prepare('SELECT * FROM tasks WHERE id = ? AND user_id = ?');
    $stmt->execute([$task_id, $user_id]);
    return $stmt->fetch();
}

/**
 * Actualizar tarea
 */
function updateTask($pdo, $task_id, $user_id, $data) {
    $stmt = $pdo->prepare('UPDATE tasks SET title = ?, description = ?, urgency = ?, priority = ?, category = ?, due_date = ?, deployed = ?, requires_docs = ?, doc_plan_prueba = ?, doc_plan_produccion = ?, doc_control_objeto = ?, doc_politica_respaldo = ? WHERE id = ? AND user_id = ?');
    
    return $stmt->execute([
        $data['title'],
        $data['description'],
        $data['urgency'],
        $data['priority'],
        $data['category'],
        $data['due_date'],
        $data['deployed'],
        $data['requires_docs'],
        $data['doc_plan_prueba'],
        $data['doc_plan_produccion'],
        $data['doc_control_objeto'],
        $data['doc_politica_respaldo'],
        $task_id,
        $user_id
    ]);
}

/**
 * Eliminar tarea
 */
function deleteTask($pdo, $task_id, $user_id) {
    $stmt = $pdo->prepare('DELETE FROM tasks WHERE id = ? AND user_id = ?');
    return $stmt->execute([$task_id, $user_id]);
}

/**
 * Registrar cambios en el historial
 */
function addTaskHistory($pdo, $task_id, $user_id, $action, $old_values, $new_values) {
    $stmt = $pdo->prepare('INSERT INTO task_history (task_id, user_id, action, old_values, new_values) VALUES (?, ?, ?, ?, ?)');
    return $stmt->execute([$task_id, $user_id, $action, json_encode($old_values), json_encode($new_values)]);
}

/**
 * Obtener historial de tarea
 */
function getTaskHistory($pdo, $task_id) {
    $stmt = $pdo->prepare('
        SELECT h.*, u.username 
        FROM task_history h
        JOIN users u ON h.user_id = u.id
        WHERE h.task_id = ?
        ORDER BY h.created_at DESC
    ');
    $stmt->execute([$task_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Obtener tareas del calendario por mes
 */
function getTasksByMonth($pdo, $user_id, $year, $month) {
    $start_date = sprintf('%04d-%02d-01', $year, $month);
    $end_date = date('Y-m-t', strtotime($start_date));
    
    $stmt = $pdo->prepare('SELECT * FROM tasks WHERE user_id = ? AND due_date BETWEEN ? AND ? ORDER BY due_date ASC');
    $stmt->execute([$user_id, $start_date, $end_date]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Obtener tareas pendientes para Pomodoro
 */
function getPendingTasksForPomodoro($pdo, $user_id, $limit = 10) {
    $stmt = $pdo->prepare("SELECT id, title, urgency, category 
                FROM tasks 
                WHERE user_id = ? 
                AND deployed = 0
                ORDER BY 
                  CASE urgency 
                    WHEN 'Alta' THEN 1 
                    WHEN 'Media' THEN 2 
                    ELSE 3 
                  END,
                  due_date ASC NULLS LAST
                LIMIT ?");
    $stmt->execute([$user_id, $limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Marcar tarea como desplegada con checklist
 */
function markTaskAsDeployed($pdo, $task_id, $user_id, $deployment_data) {
    $stmt = $pdo->prepare('UPDATE tasks SET deployed = 1, deployed_at = NOW(), deployed_by = ?, deployment_notes = ?, deployment_duration = ?, checklist_backup = ?, checklist_tests = ?, checklist_docs = ?, checklist_team = ? WHERE id = ? AND user_id = ?');
    
    return $stmt->execute([
        $user_id,
        $deployment_data['notes'],
        $deployment_data['duration'],
        $deployment_data['checklist_backup'],
        $deployment_data['checklist_tests'],
        $deployment_data['checklist_docs'],
        $deployment_data['checklist_team'],
        $task_id,
        $user_id
    ]);
}

/**
 * Verificar si la tarea puede desplegarse (documentos completos)
 */
function canTaskBeDeployed($pdo, $task_id, $user_id) {
    $stmt = $pdo->prepare('SELECT requires_docs, doc_plan_prueba, doc_plan_produccion, doc_control_objeto, doc_politica_respaldo FROM tasks WHERE id = ? AND user_id = ?');
    $stmt->execute([$task_id, $user_id]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$task) {
        return false;
    }
    
    // Si no requiere documentos, puede desplegarse
    if (!$task['requires_docs']) {
        return true;
    }
    
    // Si requiere documentos, todos deben estar completos
    return $task['doc_plan_prueba'] && 
           $task['doc_plan_produccion'] && 
           $task['doc_control_objeto'] && 
           $task['doc_politica_respaldo'];
}

/**
 * Actualizar estado de documentos
 */
function updateTaskDocuments($pdo, $task_id, $user_id, $documents) {
    $stmt = $pdo->prepare('UPDATE tasks SET doc_plan_prueba = ?, doc_plan_produccion = ?, doc_control_objeto = ?, doc_politica_respaldo = ? WHERE id = ? AND user_id = ?');
    
    return $stmt->execute([
        $documents['doc_plan_prueba'],
        $documents['doc_plan_produccion'],
        $documents['doc_control_objeto'],
        $documents['doc_politica_respaldo'],
        $task_id,
        $user_id
    ]);
}
