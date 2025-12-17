-- Agregar soporte de proyectos al sistema de tareas
-- Este script agrega la funcionalidad para asociar tareas con proyectos

-- Tabla de proyectos
CREATE TABLE IF NOT EXISTS projects (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    color VARCHAR(7) DEFAULT '#1976d2', -- Color hex para identificación visual
    icon VARCHAR(10) DEFAULT '📁',
    status VARCHAR(20) DEFAULT 'active', -- active, completed, archived
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP
);

-- Índices para proyectos
CREATE INDEX IF NOT EXISTS idx_projects_user_id ON projects(user_id);
CREATE INDEX IF NOT EXISTS idx_projects_status ON projects(user_id, status);

-- Agregar columna project_id a la tabla tasks
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name = 'tasks' AND column_name = 'project_id'
    ) THEN
        ALTER TABLE tasks ADD COLUMN project_id INTEGER REFERENCES projects(id) ON DELETE SET NULL;
        CREATE INDEX idx_tasks_project_id ON tasks(project_id);
    END IF;
END $$;

-- Trigger para actualizar updated_at en projects
CREATE OR REPLACE FUNCTION update_projects_timestamp()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trigger_update_projects_timestamp ON projects;
CREATE TRIGGER trigger_update_projects_timestamp
BEFORE UPDATE ON projects
FOR EACH ROW
EXECUTE FUNCTION update_projects_timestamp();

-- Función para obtener estadísticas de un proyecto
CREATE OR REPLACE FUNCTION get_project_stats(p_project_id INTEGER)
RETURNS TABLE(
    total_tasks INTEGER,
    completed_tasks INTEGER,
    pending_tasks INTEGER,
    completion_percentage DECIMAL(5,2)
) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        COUNT(*)::INTEGER as total_tasks,
        SUM(CASE WHEN deployed = TRUE THEN 1 ELSE 0 END)::INTEGER as completed_tasks,
        SUM(CASE WHEN deployed = FALSE THEN 1 ELSE 0 END)::INTEGER as pending_tasks,
        CASE 
            WHEN COUNT(*) > 0 THEN (SUM(CASE WHEN deployed = TRUE THEN 1 ELSE 0 END)::DECIMAL / COUNT(*) * 100)
            ELSE 0
        END as completion_percentage
    FROM tasks
    WHERE project_id = p_project_id;
END;
$$ LANGUAGE plpgsql;

-- Función para marcar proyecto como completado
CREATE OR REPLACE FUNCTION complete_project(p_project_id INTEGER, p_user_id INTEGER)
RETURNS BOOLEAN AS $$
DECLARE
    v_project_exists BOOLEAN;
BEGIN
    -- Verificar que el proyecto existe y pertenece al usuario
    SELECT EXISTS(
        SELECT 1 FROM projects 
        WHERE id = p_project_id AND user_id = p_user_id
    ) INTO v_project_exists;
    
    IF NOT v_project_exists THEN
        RETURN FALSE;
    END IF;
    
    -- Marcar proyecto como completado
    UPDATE projects 
    SET status = 'completed',
        completed_at = CURRENT_TIMESTAMP
    WHERE id = p_project_id AND user_id = p_user_id;
    
    RETURN TRUE;
END;
$$ LANGUAGE plpgsql;

-- Comentarios
COMMENT ON TABLE projects IS 'Proyectos para organizar tareas';
COMMENT ON COLUMN projects.color IS 'Color hex para identificación visual del proyecto';
COMMENT ON COLUMN projects.status IS 'Estado: active, completed, archived';
COMMENT ON COLUMN tasks.project_id IS 'Proyecto al que pertenece la tarea (opcional)';
