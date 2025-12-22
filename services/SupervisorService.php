<?php
/**
 * SupervisorService - Servicio para gestionar equipos de supervisores
 * Permite a supervisores ver el progreso de su equipo respetando privacidad
 */

class SupervisorService {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Verificar si un usuario es supervisor
     */
    public function isSupervisor($userId) {
        $stmt = $this->pdo->prepare("
            SELECT r.name 
            FROM users u
            JOIN roles r ON u.role_id = r.id
            WHERE u.id = ? AND r.name = 'supervisor'
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch() !== false;
    }
    
    /**
     * Obtener miembros del equipo de un supervisor
     */
    public function getTeamMembers($supervisorId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    st.id as assignment_id,
                    st.team_member_id,
                    st.assigned_at,
                    st.notes,
                    tms.*
                FROM supervisor_teams st
                JOIN team_member_stats tms ON st.team_member_id = tms.user_id
                WHERE st.supervisor_id = ?
                ORDER BY tms.pending_tasks DESC, tms.username
            ");
            
            $stmt->execute([$supervisorId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error obteniendo equipo: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener usuarios disponibles para asignar al equipo
     */
    public function getAvailableUsers($supervisorId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    u.id,
                    u.username,
                    u.email,
                    r.name as role_name
                FROM users u
                LEFT JOIN roles r ON u.role_id = r.id
                WHERE u.id != ?
                AND u.id NOT IN (
                    SELECT team_member_id 
                    FROM supervisor_teams 
                    WHERE supervisor_id = ?
                )
                AND (r.name = 'user' OR r.name IS NULL)
                ORDER BY u.username
            ");
            
            $stmt->execute([$supervisorId, $supervisorId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error obteniendo usuarios disponibles: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Agregar miembro al equipo
     */
    public function addTeamMember($supervisorId, $memberId, $notes = '') {
        try {
            // Verificar que el miembro no sea supervisor o admin
            $stmt = $this->pdo->prepare("
                SELECT r.name 
                FROM users u
                LEFT JOIN roles r ON u.role_id = r.id
                WHERE u.id = ?
            ");
            $stmt->execute([$memberId]);
            $role = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($role && in_array($role['name'], ['admin', 'supervisor'])) {
                return ['success' => false, 'error' => 'No puedes asignar administradores o supervisores'];
            }
            
            $stmt = $this->pdo->prepare("
                INSERT INTO supervisor_teams (supervisor_id, team_member_id, notes)
                VALUES (?, ?, ?)
            ");
            
            $stmt->execute([$supervisorId, $memberId, $notes]);
            return ['success' => true];
            
        } catch (PDOException $e) {
            if ($e->getCode() === '23505') { // Unique violation
                return ['success' => false, 'error' => 'Este usuario ya está en tu equipo'];
            }
            error_log("Error agregando miembro: " . $e->getMessage());
            return ['success' => false, 'error' => 'Error al agregar miembro al equipo'];
        }
    }
    
    /**
     * Remover miembro del equipo
     */
    public function removeTeamMember($supervisorId, $memberId) {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM supervisor_teams 
                WHERE supervisor_id = ? AND team_member_id = ?
            ");
            
            $stmt->execute([$supervisorId, $memberId]);
            return ['success' => true];
            
        } catch (PDOException $e) {
            error_log("Error removiendo miembro: " . $e->getMessage());
            return ['success' => false, 'error' => 'Error al remover miembro del equipo'];
        }
    }
    
    /**
     * Obtener tareas de un miembro del equipo (información limitada)
     */
    public function getTeamMemberTasks($supervisorId, $memberId) {
        try {
            // Usar la función de base de datos que valida permisos
            $stmt = $this->pdo->prepare("SELECT * FROM get_team_member_tasks(?, ?)");
            $stmt->execute([$supervisorId, $memberId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error obteniendo tareas: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener estadísticas resumidas del equipo
     */
    public function getTeamSummary($supervisorId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    COUNT(DISTINCT st.team_member_id) as total_members,
                    SUM(tms.total_tasks) as total_tasks,
                    SUM(tms.pending_tasks) as total_pending,
                    SUM(tms.completed_tasks) as total_completed,
                    SUM(tms.overdue_tasks) as total_overdue,
                    SUM(tms.upcoming_tasks) as total_upcoming,
                    AVG(tms.current_level) as avg_level,
                    SUM(tms.total_points) as total_points
                FROM supervisor_teams st
                JOIN team_member_stats tms ON st.team_member_id = tms.user_id
                WHERE st.supervisor_id = ?
            ");
            
            $stmt->execute([$supervisorId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'total_members' => intval($result['total_members'] ?? 0),
                'total_tasks' => intval($result['total_tasks'] ?? 0),
                'total_pending' => intval($result['total_pending'] ?? 0),
                'total_completed' => intval($result['total_completed'] ?? 0),
                'total_overdue' => intval($result['total_overdue'] ?? 0),
                'total_upcoming' => intval($result['total_upcoming'] ?? 0),
                'avg_level' => round(floatval($result['avg_level'] ?? 1), 1),
                'total_points' => intval($result['total_points'] ?? 0)
            ];
            
        } catch (PDOException $e) {
            error_log("Error obteniendo resumen del equipo: " . $e->getMessage());
            return [
                'total_members' => 0,
                'total_tasks' => 0,
                'total_pending' => 0,
                'total_completed' => 0,
                'total_overdue' => 0,
                'total_upcoming' => 0,
                'avg_level' => 1,
                'total_points' => 0
            ];
        }
    }
    
    /**
     * Actualizar notas de un miembro del equipo
     */
    public function updateMemberNotes($supervisorId, $memberId, $notes) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE supervisor_teams 
                SET notes = ?
                WHERE supervisor_id = ? AND team_member_id = ?
            ");
            
            $stmt->execute([$notes, $supervisorId, $memberId]);
            return ['success' => true];
            
        } catch (PDOException $e) {
            error_log("Error actualizando notas: " . $e->getMessage());
            return ['success' => false, 'error' => 'Error al actualizar notas'];
        }
    }
    
    /**
     * Verificar si el supervisor tiene acceso a un miembro
     */
    public function hasAccessToMember($supervisorId, $memberId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 1 FROM supervisor_teams 
                WHERE supervisor_id = ? AND team_member_id = ?
            ");
            $stmt->execute([$supervisorId, $memberId]);
            return $stmt->fetch() !== false;
            
        } catch (PDOException $e) {
            return false;
        }
    }
}
