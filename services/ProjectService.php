<?php
/**
 * ProjectService - Servicio para gestionar proyectos y sus tareas
 */

class ProjectService {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Crear nuevo proyecto
     */
    public function createProject($userId, $data) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO projects (user_id, name, description, status, priority, start_date, target_date, category, repository_url, notes)
                VALUES (:user_id, :name, :description, :status, :priority, :start_date, :target_date, :category, :repository_url, :notes)
                RETURNING id
            ");
            
            $stmt->execute([
                ':user_id' => $userId,
                ':name' => $data['name'],
                ':description' => $data['description'] ?? '',
                ':status' => $data['status'] ?? 'En Desarrollo',
                ':priority' => $data['priority'] ?? 'Media',
                ':start_date' => $data['start_date'] ?? date('Y-m-d'),
                ':target_date' => $data['target_date'] ?? null,
                ':category' => $data['category'] ?? null,
                ':repository_url' => $data['repository_url'] ?? null,
                ':notes' => $data['notes'] ?? null
            ]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return ['success' => true, 'id' => $result['id']];
            
        } catch (PDOException $e) {
            error_log("Error creando proyecto: " . $e->getMessage());
            return ['success' => false, 'error' => 'Error al crear proyecto'];
        }
    }
    
    /**
     * Obtener todos los proyectos del usuario
     */
    public function getUserProjects($userId, $status = null) {
        try {
            $sql = "
                SELECT 
                    p.*,
                    COALESCE(p.total_tasks, 0) as total_tasks,
                    COALESCE(p.completed_tasks, 0) as completed_tasks,
                    COALESCE(p.progress_percentage, 0) as progress_percentage
                FROM projects p
                WHERE p.user_id = :user_id
            ";
            
            if ($status) {
                $sql .= " AND p.status = :status";
            }
            
            $sql .= " ORDER BY 
                CASE p.priority 
                    WHEN 'Alta' THEN 1 
                    WHEN 'Media' THEN 2 
                    ELSE 3 
                END,
                p.target_date ASC NULLS LAST,
                p.created_at DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $params = [':user_id' => $userId];
            
            if ($status) {
                $params[':status'] = $status;
            }
            
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error obteniendo proyectos: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener proyecto por ID
     */
    public function getProjectById($projectId, $userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT p.* FROM projects p
                WHERE p.id = :project_id AND p.user_id = :user_id
            ");
            
            $stmt->execute([':project_id' => $projectId, ':user_id' => $userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error obteniendo proyecto: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Actualizar proyecto
     */
    public function updateProject($projectId, $userId, $data) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE projects 
                SET name = :name,
                    description = :description,
                    status = :status,
                    priority = :priority,
                    target_date = :target_date,
                    category = :category,
                    repository_url = :repository_url,
                    notes = :notes
                WHERE id = :project_id AND user_id = :user_id
            ");
            
            $stmt->execute([
                ':project_id' => $projectId,
                ':user_id' => $userId,
                ':name' => $data['name'],
                ':description' => $data['description'] ?? '',
                ':status' => $data['status'],
                ':priority' => $data['priority'],
                ':target_date' => $data['target_date'] ?? null,
                ':category' => $data['category'] ?? null,
                ':repository_url' => $data['repository_url'] ?? null,
                ':notes' => $data['notes'] ?? null
            ]);
            
            return ['success' => true];
            
        } catch (PDOException $e) {
            error_log("Error actualizando proyecto: " . $e->getMessage());
            return ['success' => false, 'error' => 'Error al actualizar proyecto'];
        }
    }
    
    /**
     * Eliminar proyecto
     */
    public function deleteProject($projectId, $userId) {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM projects 
                WHERE id = :project_id AND user_id = :user_id
            ");
            
            $stmt->execute([':project_id' => $projectId, ':user_id' => $userId]);
            
            return ['success' => true];
            
        } catch (PDOException $e) {
            error_log("Error eliminando proyecto: " . $e->getMessage());
            return ['success' => false, 'error' => 'Error al eliminar proyecto'];
        }
    }
    
    /**
     * Crear tarea de proyecto
     */
    public function createProjectTask($projectId, $userId, $data) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO project_tasks (project_id, user_id, title, description, status, priority, estimated_hours, due_date, assigned_to, order_index)
                VALUES (:project_id, :user_id, :title, :description, :status, :priority, :estimated_hours, :due_date, :assigned_to, :order_index)
                RETURNING id
            ");
            
            $stmt->execute([
                ':project_id' => $projectId,
                ':user_id' => $userId,
                ':title' => $data['title'],
                ':description' => $data['description'] ?? '',
                ':status' => $data['status'] ?? 'Pendiente',
                ':priority' => $data['priority'] ?? 'Media',
                ':estimated_hours' => $data['estimated_hours'] ?? null,
                ':due_date' => $data['due_date'] ?? null,
                ':assigned_to' => $data['assigned_to'] ?? null,
                ':order_index' => $data['order_index'] ?? 0
            ]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return ['success' => true, 'id' => $result['id']];
            
        } catch (PDOException $e) {
            error_log("Error creando tarea de proyecto: " . $e->getMessage());
            return ['success' => false, 'error' => 'Error al crear tarea'];
        }
    }
    
    /**
     * Obtener tareas de un proyecto
     */
    public function getProjectTasks($projectId, $userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM project_tasks
                WHERE project_id = :project_id AND user_id = :user_id
                ORDER BY 
                    CASE status 
                        WHEN 'En Progreso' THEN 1
                        WHEN 'Pendiente' THEN 2
                        WHEN 'Bloqueada' THEN 3
                        WHEN 'Completada' THEN 4
                    END,
                    order_index ASC,
                    due_date ASC NULLS LAST
            ");
            
            $stmt->execute([':project_id' => $projectId, ':user_id' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error obteniendo tareas del proyecto: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Completar tarea de proyecto
     */
    public function completeProjectTask($taskId, $userId, $hoursWorked = null) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM complete_project_task(:task_id, :user_id, :hours_worked)");
            $stmt->execute([
                ':task_id' => $taskId,
                ':user_id' => $userId,
                ':hours_worked' => $hoursWorked
            ]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'success' => $result['success'],
                'points' => $result['points'],
                'message' => $result['message']
            ];
            
        } catch (PDOException $e) {
            error_log("Error completando tarea: " . $e->getMessage());
            return ['success' => false, 'error' => 'Error al completar tarea'];
        }
    }
    
    /**
     * Actualizar tarea de proyecto
     */
    public function updateProjectTask($taskId, $userId, $data) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE project_tasks 
                SET title = :title,
                    description = :description,
                    status = :status,
                    priority = :priority,
                    estimated_hours = :estimated_hours,
                    due_date = :due_date
                WHERE id = :task_id AND user_id = :user_id
            ");
            
            $stmt->execute([
                ':task_id' => $taskId,
                ':user_id' => $userId,
                ':title' => $data['title'],
                ':description' => $data['description'] ?? '',
                ':status' => $data['status'],
                ':priority' => $data['priority'],
                ':estimated_hours' => $data['estimated_hours'] ?? null,
                ':due_date' => $data['due_date'] ?? null
            ]);
            
            return ['success' => true];
            
        } catch (PDOException $e) {
            error_log("Error actualizando tarea: " . $e->getMessage());
            return ['success' => false, 'error' => 'Error al actualizar tarea'];
        }
    }
    
    /**
     * Eliminar tarea de proyecto
     */
    public function deleteProjectTask($taskId, $userId) {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM project_tasks 
                WHERE id = :task_id AND user_id = :user_id
            ");
            
            $stmt->execute([':task_id' => $taskId, ':user_id' => $userId]);
            
            return ['success' => true];
            
        } catch (PDOException $e) {
            error_log("Error eliminando tarea: " . $e->getMessage());
            return ['success' => false, 'error' => 'Error al eliminar tarea'];
        }
    }
    
    /**
     * Desplegar proyecto a producción
     */
    public function deployProject($projectId, $userId) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM deploy_project(:project_id, :user_id)");
            $stmt->execute([
                ':project_id' => $projectId,
                ':user_id' => $userId
            ]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'success' => $result['success'],
                'points' => $result['points'],
                'message' => $result['message']
            ];
            
        } catch (PDOException $e) {
            error_log("Error desplegando proyecto: " . $e->getMessage());
            return ['success' => false, 'error' => 'Error al desplegar proyecto'];
        }
    }
    
    /**
     * Obtener progreso diario de un proyecto
     */
    public function getDailyProgress($projectId, $userId, $days = 30) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM daily_progress
                WHERE project_id = :project_id AND user_id = :user_id
                    AND work_date >= CURRENT_DATE - INTERVAL '$days days'
                ORDER BY work_date DESC
            ");
            
            $stmt->execute([':project_id' => $projectId, ':user_id' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error obteniendo progreso diario: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener estadísticas del proyecto
     */
    public function getProjectStats($projectId, $userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    p.total_tasks,
                    p.completed_tasks,
                    p.progress_percentage,
                    COALESCE(SUM(pt.estimated_hours), 0) as total_estimated_hours,
                    COALESCE(SUM(pt.actual_hours), 0) as total_actual_hours,
                    COUNT(CASE WHEN pt.status = 'En Progreso' THEN 1 END) as tasks_in_progress,
                    COUNT(CASE WHEN pt.status = 'Bloqueada' THEN 1 END) as blocked_tasks
                FROM projects p
                LEFT JOIN project_tasks pt ON p.id = pt.project_id
                WHERE p.id = :project_id AND p.user_id = :user_id
                GROUP BY p.id
            ");
            
            $stmt->execute([':project_id' => $projectId, ':user_id' => $userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error obteniendo estadísticas: " . $e->getMessage());
            return [];
        }
    }
}
