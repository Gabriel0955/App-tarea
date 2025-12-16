<?php
/**
 * QuickTaskService - Servicio para gestionar tareas rápidas/cortas del día
 * Permite crear tareas simples como reuniones, revisiones, llamadas, etc.
 * que se completan al final del día otorgando puntos en la gamificación
 */

class QuickTaskService {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Crear una nueva tarea rápida
     */
    public function createQuickTask($userId, $title, $description = '', $taskDate = null, $scheduledTime = null) {
        try {
            $taskDate = $taskDate ?? date('Y-m-d');
            
            $stmt = $this->pdo->prepare("
                INSERT INTO quick_tasks (user_id, title, description, task_date, scheduled_time)
                VALUES (:user_id, :title, :description, :task_date, :scheduled_time)
                RETURNING id
            ");
            
            $stmt->execute([
                ':user_id' => $userId,
                ':title' => $title,
                ':description' => $description,
                ':task_date' => $taskDate,
                ':scheduled_time' => $scheduledTime
            ]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return ['success' => true, 'id' => $result['id']];
            
        } catch (PDOException $e) {
            error_log("Error creando tarea rápida: " . $e->getMessage());
            return ['success' => false, 'error' => 'Error al crear tarea rápida'];
        }
    }
    
    /**
     * Obtener tareas rápidas de un usuario por fecha
     */
    public function getQuickTasksByDate($userId, $date = null) {
        try {
            $date = $date ?? date('Y-m-d');
            
            $stmt = $this->pdo->prepare("
                SELECT 
                    id,
                    title,
                    description,
                    task_date,
                    scheduled_time,
                    completed,
                    completed_at,
                    points_awarded,
                    created_at
                FROM quick_tasks
                WHERE user_id = :user_id AND task_date = :task_date
                ORDER BY 
                    completed ASC,
                    scheduled_time ASC NULLS LAST,
                    created_at ASC
            ");
            
            $stmt->execute([
                ':user_id' => $userId,
                ':task_date' => $date
            ]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error obteniendo tareas rápidas: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener tareas rápidas de la semana actual
     */
    public function getWeekQuickTasks($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    id,
                    title,
                    description,
                    task_date,
                    scheduled_time,
                    completed,
                    completed_at,
                    points_awarded
                FROM quick_tasks
                WHERE user_id = :user_id 
                    AND task_date >= DATE_TRUNC('week', CURRENT_DATE)
                    AND task_date < DATE_TRUNC('week', CURRENT_DATE) + INTERVAL '7 days'
                ORDER BY task_date DESC, scheduled_time ASC NULLS LAST
            ");
            
            $stmt->execute([':user_id' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error obteniendo tareas de la semana: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Completar tarea rápida y otorgar puntos
     */
    public function completeQuickTask($taskId, $userId) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM complete_quick_task(:task_id, :user_id)");
            $stmt->execute([
                ':task_id' => $taskId,
                ':user_id' => $userId
            ]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'success' => $result['success'],
                'points' => $result['points'],
                'message' => $result['message']
            ];
            
        } catch (PDOException $e) {
            error_log("Error completando tarea rápida: " . $e->getMessage());
            return ['success' => false, 'error' => 'Error al completar tarea'];
        }
    }
    
    /**
     * Desmarcar tarea rápida como completada
     */
    public function uncompleteQuickTask($taskId, $userId) {
        try {
            // Obtener puntos otorgados para revertirlos
            $stmt = $this->pdo->prepare("
                SELECT points_awarded FROM quick_tasks 
                WHERE id = :task_id AND user_id = :user_id AND completed = TRUE
            ");
            $stmt->execute([':task_id' => $taskId, ':user_id' => $userId]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$task) {
                return ['success' => false, 'error' => 'Tarea no encontrada o no completada'];
            }
            
            // Revertir puntos
            if ($task['points_awarded'] > 0) {
                $stmt = $this->pdo->prepare("
                    UPDATE users 
                    SET total_points = GREATEST(0, total_points - :points)
                    WHERE id = :user_id
                ");
                $stmt->execute([
                    ':points' => $task['points_awarded'],
                    ':user_id' => $userId
                ]);
            }
            
            // Desmarcar tarea
            $stmt = $this->pdo->prepare("
                UPDATE quick_tasks 
                SET completed = FALSE, 
                    completed_at = NULL,
                    points_awarded = 0
                WHERE id = :task_id AND user_id = :user_id
            ");
            $stmt->execute([':task_id' => $taskId, ':user_id' => $userId]);
            
            return ['success' => true, 'message' => 'Tarea desmarcada'];
            
        } catch (PDOException $e) {
            error_log("Error desmarcando tarea: " . $e->getMessage());
            return ['success' => false, 'error' => 'Error al desmarcar tarea'];
        }
    }
    
    /**
     * Actualizar tarea rápida
     */
    public function updateQuickTask($taskId, $userId, $title, $description = '', $taskDate = null, $scheduledTime = null) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE quick_tasks 
                SET title = :title,
                    description = :description,
                    task_date = COALESCE(:task_date, task_date),
                    scheduled_time = :scheduled_time
                WHERE id = :task_id AND user_id = :user_id AND completed = FALSE
            ");
            
            $stmt->execute([
                ':task_id' => $taskId,
                ':user_id' => $userId,
                ':title' => $title,
                ':description' => $description,
                ':task_date' => $taskDate,
                ':scheduled_time' => $scheduledTime
            ]);
            
            return ['success' => true];
            
        } catch (PDOException $e) {
            error_log("Error actualizando tarea rápida: " . $e->getMessage());
            return ['success' => false, 'error' => 'Error al actualizar tarea'];
        }
    }
    
    /**
     * Eliminar tarea rápida (solo si no está completada)
     */
    public function deleteQuickTask($taskId, $userId) {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM quick_tasks 
                WHERE id = :task_id AND user_id = :user_id AND completed = FALSE
            ");
            
            $stmt->execute([':task_id' => $taskId, ':user_id' => $userId]);
            
            if ($stmt->rowCount() > 0) {
                return ['success' => true];
            } else {
                return ['success' => false, 'error' => 'Tarea no encontrada o ya completada'];
            }
            
        } catch (PDOException $e) {
            error_log("Error eliminando tarea rápida: " . $e->getMessage());
            return ['success' => false, 'error' => 'Error al eliminar tarea'];
        }
    }
    
    /**
     * Obtener estadísticas de tareas rápidas del usuario
     */
    public function getQuickTaskStats($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    COUNT(*) as total_tasks,
                    COUNT(CASE WHEN completed THEN 1 END) as completed_tasks,
                    COUNT(CASE WHEN completed AND task_date = completed_at::date THEN 1 END) as completed_same_day,
                    COALESCE(SUM(points_awarded), 0) as total_points,
                    COUNT(CASE WHEN task_date = CURRENT_DATE THEN 1 END) as today_tasks,
                    COUNT(CASE WHEN task_date = CURRENT_DATE AND completed THEN 1 END) as today_completed
                FROM quick_tasks
                WHERE user_id = :user_id
                    AND task_date >= CURRENT_DATE - INTERVAL '30 days'
            ");
            
            $stmt->execute([':user_id' => $userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error obteniendo estadísticas: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener tarea rápida por ID
     */
    public function getQuickTaskById($taskId, $userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM quick_tasks 
                WHERE id = :task_id AND user_id = :user_id
            ");
            
            $stmt->execute([':task_id' => $taskId, ':user_id' => $userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error obteniendo tarea: " . $e->getMessage());
            return null;
        }
    }
}
