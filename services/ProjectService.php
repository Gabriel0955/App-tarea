<?php
/**
 * ProjectService - Servicio para gestionar proyectos
 * Permite organizar tareas en proyectos
 */

class ProjectService {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Crear un nuevo proyecto
     */
    public function createProject($userId, $name, $description = '', $color = '#1976d2', $icon = '📁') {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO projects (user_id, name, description, color, icon)
                VALUES (:user_id, :name, :description, :color, :icon)
                RETURNING id
            ");
            
            $stmt->execute([
                ':user_id' => $userId,
                ':name' => $name,
                ':description' => $description,
                ':color' => $color,
                ':icon' => $icon
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
    public function getUserProjects($userId, $status = 'active') {
        try {
            $sql = "SELECT * FROM projects WHERE user_id = :user_id";
            
            if ($status !== 'all') {
                $sql .= " AND status = :status";
            }
            
            $sql .= " ORDER BY created_at DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $params = [':user_id' => $userId];
            
            if ($status !== 'all') {
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
                SELECT * FROM projects 
                WHERE id = :project_id AND user_id = :user_id
            ");
            
            $stmt->execute([
                ':project_id' => $projectId,
                ':user_id' => $userId
            ]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error obteniendo proyecto: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Obtener estadísticas de un proyecto
     */
    public function getProjectStats($projectId) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM get_project_stats(:project_id)");
            $stmt->execute([':project_id' => $projectId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error obteniendo estadísticas: " . $e->getMessage());
            return [
                'total_tasks' => 0,
                'completed_tasks' => 0,
                'pending_tasks' => 0,
                'completion_percentage' => 0
            ];
        }
    }
    
    /**
     * Obtener tareas de un proyecto
     */
    public function getProjectTasks($projectId, $userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM tasks 
                WHERE project_id = :project_id AND user_id = :user_id
                ORDER BY 
                    deployed ASC,
                    CASE urgency 
                        WHEN 'Alta' THEN 1 
                        WHEN 'Media' THEN 2 
                        ELSE 3 
                    END,
                    created_at DESC
            ");
            
            $stmt->execute([
                ':project_id' => $projectId,
                ':user_id' => $userId
            ]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error obteniendo tareas del proyecto: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Actualizar proyecto
     */
    public function updateProject($projectId, $userId, $data) {
        try {
            $fields = [];
            $params = [':project_id' => $projectId, ':user_id' => $userId];
            
            if (isset($data['name'])) {
                $fields[] = 'name = :name';
                $params[':name'] = $data['name'];
            }
            
            if (isset($data['description'])) {
                $fields[] = 'description = :description';
                $params[':description'] = $data['description'];
            }
            
            if (isset($data['color'])) {
                $fields[] = 'color = :color';
                $params[':color'] = $data['color'];
            }
            
            if (isset($data['icon'])) {
                $fields[] = 'icon = :icon';
                $params[':icon'] = $data['icon'];
            }
            
            if (isset($data['status'])) {
                $fields[] = 'status = :status';
                $params[':status'] = $data['status'];
                
                if ($data['status'] === 'completed') {
                    $fields[] = 'completed_at = CURRENT_TIMESTAMP';
                }
            }
            
            if (empty($fields)) {
                return ['success' => false, 'error' => 'No hay campos para actualizar'];
            }
            
            $sql = "UPDATE projects SET " . implode(', ', $fields) . 
                   " WHERE id = :project_id AND user_id = :user_id";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return ['success' => true];
            
        } catch (PDOException $e) {
            error_log("Error actualizando proyecto: " . $e->getMessage());
            return ['success' => false, 'error' => 'Error al actualizar proyecto'];
        }
    }
    
    /**
     * Eliminar proyecto
     * @param bool $deleteTasks - Si true, elimina las tareas asociadas. Si false, solo desvincula.
     */
    public function deleteProject($projectId, $userId, $deleteTasks = false) {
        try {
            $this->pdo->beginTransaction();
            
            if ($deleteTasks) {
                // Eliminar todas las tareas del proyecto
                $stmt = $this->pdo->prepare("
                    DELETE FROM tasks 
                    WHERE project_id = :project_id AND user_id = :user_id
                ");
                $stmt->execute([
                    ':project_id' => $projectId,
                    ':user_id' => $userId
                ]);
            } else {
                // Solo desvincular tareas (project_id = NULL)
                $stmt = $this->pdo->prepare("
                    UPDATE tasks 
                    SET project_id = NULL 
                    WHERE project_id = :project_id AND user_id = :user_id
                ");
                $stmt->execute([
                    ':project_id' => $projectId,
                    ':user_id' => $userId
                ]);
            }
            
            // Eliminar el proyecto
            $stmt = $this->pdo->prepare("
                DELETE FROM projects 
                WHERE id = :project_id AND user_id = :user_id
            ");
            $stmt->execute([
                ':project_id' => $projectId,
                ':user_id' => $userId
            ]);
            
            $this->pdo->commit();
            return ['success' => true];
            
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Error eliminando proyecto: " . $e->getMessage());
            return ['success' => false, 'error' => 'Error al eliminar proyecto'];
        }
    }
    
    /**
     * Completar proyecto
     */
    public function completeProject($projectId, $userId) {
        try {
            $stmt = $this->pdo->prepare("SELECT complete_project(:project_id, :user_id)");
            $stmt->execute([
                ':project_id' => $projectId,
                ':user_id' => $userId
            ]);
            
            $result = $stmt->fetchColumn();
            
            if ($result) {
                return ['success' => true];
            } else {
                return ['success' => false, 'error' => 'Proyecto no encontrado'];
            }
            
        } catch (PDOException $e) {
            error_log("Error completando proyecto: " . $e->getMessage());
            return ['success' => false, 'error' => 'Error al completar proyecto'];
        }
    }
    
    /**
     * Obtener proyectos con estadísticas
     */
    public function getProjectsWithStats($userId, $status = 'active') {
        $projects = $this->getUserProjects($userId, $status);
        
        foreach ($projects as &$project) {
            $project['stats'] = $this->getProjectStats($project['id']);
        }
        
        return $projects;
    }
}
