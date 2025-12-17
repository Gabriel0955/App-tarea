-- Corregir esquema de la tabla projects para agregar columnas faltantes

-- Agregar columna project_id a tasks si no existe
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name = 'tasks' AND column_name = 'project_id'
    ) THEN
        ALTER TABLE tasks ADD COLUMN project_id INTEGER REFERENCES projects(id) ON DELETE SET NULL;
        COMMENT ON COLUMN tasks.project_id IS 'ID del proyecto al que pertenece la tarea';
        CREATE INDEX IF NOT EXISTS idx_tasks_project_id ON tasks(project_id);
    END IF;
END $$;

-- Agregar columna color si no existe
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name = 'projects' AND column_name = 'color'
    ) THEN
        ALTER TABLE projects ADD COLUMN color VARCHAR(7) DEFAULT '#1976d2';
        COMMENT ON COLUMN projects.color IS 'Color hex para identificación visual del proyecto';
    END IF;
END $$;

-- Agregar columna icon si no existe
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name = 'projects' AND column_name = 'icon'
    ) THEN
        ALTER TABLE projects ADD COLUMN icon VARCHAR(10) DEFAULT '📁';
        COMMENT ON COLUMN projects.icon IS 'Emoji o icono visual del proyecto';
    END IF;
END $$;

-- Agregar columna completed_at si no existe
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name = 'projects' AND column_name = 'completed_at'
    ) THEN
        ALTER TABLE projects ADD COLUMN completed_at TIMESTAMP;
        COMMENT ON COLUMN projects.completed_at IS 'Fecha cuando se completó el proyecto';
    END IF;
END $$;

-- Actualizar proyectos existentes con valores por defecto si es necesario
UPDATE projects 
SET color = '#1976d2' 
WHERE color IS NULL;

UPDATE projects 
SET icon = '📁' 
WHERE icon IS NULL;

-- Crear función para actualizar timestamp
CREATE OR REPLACE FUNCTION update_projects_timestamp()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Crear trigger para actualizar timestamp
DROP TRIGGER IF EXISTS update_projects_timestamp_trigger ON projects;
CREATE TRIGGER update_projects_timestamp_trigger
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
        SUM(CASE WHEN deployed = 1 THEN 1 ELSE 0 END)::INTEGER as completed_tasks,
        SUM(CASE WHEN deployed = 0 THEN 1 ELSE 0 END)::INTEGER as pending_tasks,
        CASE 
            WHEN COUNT(*) > 0 THEN (SUM(CASE WHEN deployed = 1 THEN 1 ELSE 0 END)::DECIMAL / COUNT(*) * 100)
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

-- Verificar las columnas actuales
SELECT 
    column_name, 
    data_type, 
    column_default,
    is_nullable
FROM information_schema.columns 
WHERE table_name = 'projects' 
ORDER BY ordinal_position;
