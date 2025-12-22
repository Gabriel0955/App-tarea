-- Migración: Agregar rol de Supervisor y tabla de equipos
-- Fecha: 2025-12-21

-- 1. Agregar rol de Supervisor
INSERT INTO roles (name, description) 
VALUES ('supervisor', 'Supervisor de equipo - Puede ver progreso de usuarios asignados')
ON CONFLICT (name) DO NOTHING;

-- 2. Crear tabla para relacionar supervisores con usuarios (equipos)
CREATE TABLE IF NOT EXISTS supervisor_teams (
    id SERIAL PRIMARY KEY,
    supervisor_id INTEGER NOT NULL,
    team_member_id INTEGER NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    FOREIGN KEY (supervisor_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (team_member_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE(supervisor_id, team_member_id)
);

CREATE INDEX IF NOT EXISTS idx_supervisor_teams_supervisor ON supervisor_teams(supervisor_id);
CREATE INDEX IF NOT EXISTS idx_supervisor_teams_member ON supervisor_teams(team_member_id);

-- 3. Agregar permisos para el rol supervisor
-- Permiso para leer información del equipo
INSERT INTO permissions (role_id, resource, can_create, can_read, can_update, can_delete, created_at)
SELECT r.id, 'team', false, true, false, false, CURRENT_TIMESTAMP
FROM roles r
WHERE r.name = 'supervisor'
AND NOT EXISTS (
    SELECT 1 FROM permissions p 
    WHERE p.role_id = r.id AND p.resource = 'team'
);

-- Permiso para gestionar el equipo (crear/actualizar asignaciones)
INSERT INTO permissions (role_id, resource, can_create, can_read, can_update, can_delete, created_at)
SELECT r.id, 'team_manage', true, true, true, true, CURRENT_TIMESTAMP
FROM roles r
WHERE r.name = 'supervisor'
AND NOT EXISTS (
    SELECT 1 FROM permissions p 
    WHERE p.role_id = r.id AND p.resource = 'team_manage'
);

-- Permiso para leer tareas de los miembros
INSERT INTO permissions (role_id, resource, can_create, can_read, can_update, can_delete, created_at)
SELECT r.id, 'tasks', false, true, false, false, CURRENT_TIMESTAMP
FROM roles r
WHERE r.name = 'supervisor'
AND NOT EXISTS (
    SELECT 1 FROM permissions p 
    WHERE p.role_id = r.id AND p.resource = 'tasks'
);

-- Permiso para leer proyectos relacionados
INSERT INTO permissions (role_id, resource, can_create, can_read, can_update, can_delete, created_at)
SELECT r.id, 'projects', false, true, false, false, CURRENT_TIMESTAMP
FROM roles r
WHERE r.name = 'supervisor'
AND NOT EXISTS (
    SELECT 1 FROM permissions p 
    WHERE p.role_id = r.id AND p.resource = 'projects'
);

-- 4. Crear vista para estadísticas de equipo (respetando privacidad)
CREATE OR REPLACE VIEW team_member_stats AS
SELECT 
    u.id as user_id,
    u.username,
    COUNT(DISTINCT t.id) as total_tasks,
    COUNT(DISTINCT CASE WHEN t.deployed = 1 THEN t.id END) as completed_tasks,
    COUNT(DISTINCT CASE WHEN t.deployed = 0 THEN t.id END) as pending_tasks,
    COUNT(DISTINCT CASE WHEN t.due_date < CURRENT_DATE AND t.deployed = 0 THEN t.id END) as overdue_tasks,
    COUNT(DISTINCT CASE WHEN t.due_date BETWEEN CURRENT_DATE AND CURRENT_DATE + INTERVAL '7 days' AND t.deployed = 0 THEN t.id END) as upcoming_tasks,
    COALESCE(us.total_points, 0) as total_points,
    COALESCE(us.current_level, 1) as current_level,
    COALESCE(us.tasks_completed, 0) as tasks_completed_stats,
    COALESCE(us.current_streak, 0) as current_streak,
    COALESCE(us.pomodoros_completed, 0) as pomodoros_completed,
    us.last_activity_date
FROM users u
LEFT JOIN tasks t ON u.id = t.user_id
LEFT JOIN user_stats us ON u.id = us.user_id
GROUP BY u.id, u.username, us.total_points, us.current_level, us.tasks_completed, 
         us.current_streak, us.pomodoros_completed, us.last_activity_date;

-- 5. Función para obtener tareas resumidas del equipo (sin información sensible)
CREATE OR REPLACE FUNCTION get_team_member_tasks(
    p_supervisor_id INTEGER,
    p_member_id INTEGER
) RETURNS TABLE (
    task_id INTEGER,
    title VARCHAR(255),
    category VARCHAR(50),
    priority VARCHAR(10),
    urgency VARCHAR(10),
    due_date DATE,
    deployed SMALLINT,
    created_at TIMESTAMP,
    days_pending INTEGER,
    has_project BOOLEAN,
    project_name VARCHAR(100)
) AS $$
BEGIN
    -- Verificar que el supervisor tiene acceso a este miembro
    IF NOT EXISTS (
        SELECT 1 FROM supervisor_teams 
        WHERE supervisor_id = p_supervisor_id 
        AND team_member_id = p_member_id
    ) THEN
        RAISE EXCEPTION 'No autorizado para ver este usuario';
    END IF;
    
    RETURN QUERY
    SELECT 
        t.id as task_id,
        t.title,
        t.category,
        t.priority,
        t.urgency,
        t.due_date,
        t.deployed,
        t.created_at,
        EXTRACT(DAY FROM (CURRENT_TIMESTAMP - t.created_at))::INTEGER as days_pending,
        (t.project_id IS NOT NULL) as has_project,
        COALESCE(p.name, 'Sin proyecto') as project_name
    FROM tasks t
    LEFT JOIN projects p ON t.project_id = p.id
    WHERE t.user_id = p_member_id
    ORDER BY 
        CASE 
            WHEN t.deployed = 0 AND t.due_date < CURRENT_DATE THEN 1
            WHEN t.deployed = 0 AND t.due_date <= CURRENT_DATE + INTERVAL '3 days' THEN 2
            WHEN t.deployed = 0 THEN 3
            ELSE 4
        END,
        t.due_date NULLS LAST,
        t.created_at DESC;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

COMMENT ON TABLE supervisor_teams IS 'Relación entre supervisores y miembros de su equipo';
COMMENT ON VIEW team_member_stats IS 'Vista de estadísticas de usuarios para supervisores (sin datos sensibles)';
COMMENT ON FUNCTION get_team_member_tasks IS 'Obtiene tareas resumidas de un miembro del equipo respetando privacidad';
