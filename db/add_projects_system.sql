-- Sistema de Proyectos con Tareas Relacionadas

-- Tabla de Proyectos
CREATE TABLE IF NOT EXISTS projects (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    status VARCHAR(50) DEFAULT 'En Desarrollo', -- En Desarrollo, En Pruebas, En Producción, Cancelado
    priority VARCHAR(20) DEFAULT 'Media', -- Alta, Media, Baja
    start_date DATE,
    target_date DATE,
    deployed_date DATE,
    progress_percentage DECIMAL(5,2) DEFAULT 0.00,
    total_tasks INTEGER DEFAULT 0,
    completed_tasks INTEGER DEFAULT 0,
    category VARCHAR(100),
    repository_url TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de Tareas de Proyecto (reemplaza/complementa tabla tasks actual)
CREATE TABLE IF NOT EXISTS project_tasks (
    id SERIAL PRIMARY KEY,
    project_id INTEGER NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    status VARCHAR(50) DEFAULT 'Pendiente', -- Pendiente, En Progreso, Completada, Bloqueada
    priority VARCHAR(20) DEFAULT 'Media',
    estimated_hours DECIMAL(5,2),
    actual_hours DECIMAL(5,2),
    completed_date DATE,
    due_date DATE,
    tags TEXT[], -- Array de etiquetas
    assigned_to VARCHAR(100),
    order_index INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de Progreso Diario (para trackear trabajo diario)
CREATE TABLE IF NOT EXISTS daily_progress (
    id SERIAL PRIMARY KEY,
    project_id INTEGER NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    work_date DATE NOT NULL DEFAULT CURRENT_DATE,
    tasks_completed INTEGER DEFAULT 0,
    hours_worked DECIMAL(5,2) DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Índices para rendimiento
CREATE INDEX IF NOT EXISTS idx_projects_user ON projects(user_id, status);
CREATE INDEX IF NOT EXISTS idx_project_tasks_project ON project_tasks(project_id, status);
CREATE INDEX IF NOT EXISTS idx_project_tasks_user ON project_tasks(user_id, completed_date);
CREATE INDEX IF NOT EXISTS idx_daily_progress_project_date ON daily_progress(project_id, work_date);

-- Triggers para actualizar timestamps
CREATE OR REPLACE FUNCTION update_projects_timestamp()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_update_projects_timestamp
BEFORE UPDATE ON projects
FOR EACH ROW
EXECUTE FUNCTION update_projects_timestamp();

CREATE OR REPLACE FUNCTION update_project_tasks_timestamp()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_update_project_tasks_timestamp
BEFORE UPDATE ON project_tasks
FOR EACH ROW
EXECUTE FUNCTION update_project_tasks_timestamp();

-- Función para actualizar progreso del proyecto automáticamente
CREATE OR REPLACE FUNCTION update_project_progress()
RETURNS TRIGGER AS $$
DECLARE
    v_total INTEGER;
    v_completed INTEGER;
    v_percentage DECIMAL(5,2);
BEGIN
    -- Contar tareas totales y completadas
    SELECT 
        COUNT(*),
        COUNT(CASE WHEN status = 'Completada' THEN 1 END)
    INTO v_total, v_completed
    FROM project_tasks
    WHERE project_id = COALESCE(NEW.project_id, OLD.project_id);
    
    -- Calcular porcentaje
    IF v_total > 0 THEN
        v_percentage := (v_completed::DECIMAL / v_total::DECIMAL) * 100;
    ELSE
        v_percentage := 0;
    END IF;
    
    -- Actualizar proyecto
    UPDATE projects
    SET total_tasks = v_total,
        completed_tasks = v_completed,
        progress_percentage = v_percentage
    WHERE id = COALESCE(NEW.project_id, OLD.project_id);
    
    RETURN COALESCE(NEW, OLD);
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_update_project_progress_insert
AFTER INSERT ON project_tasks
FOR EACH ROW
EXECUTE FUNCTION update_project_progress();

CREATE TRIGGER trigger_update_project_progress_update
AFTER UPDATE ON project_tasks
FOR EACH ROW
WHEN (OLD.status IS DISTINCT FROM NEW.status)
EXECUTE FUNCTION update_project_progress();

CREATE TRIGGER trigger_update_project_progress_delete
AFTER DELETE ON project_tasks
FOR EACH ROW
EXECUTE FUNCTION update_project_progress();

-- Función para completar tarea de proyecto y otorgar puntos
CREATE OR REPLACE FUNCTION complete_project_task(p_task_id INTEGER, p_user_id INTEGER, p_hours_worked DECIMAL)
RETURNS TABLE(success BOOLEAN, points INTEGER, message TEXT) AS $$
DECLARE
    v_task_status VARCHAR(50);
    v_project_id INTEGER;
    v_points INTEGER;
BEGIN
    -- Obtener información de la tarea
    SELECT status, project_id INTO v_task_status, v_project_id
    FROM project_tasks
    WHERE id = p_task_id AND user_id = p_user_id;
    
    IF NOT FOUND THEN
        RETURN QUERY SELECT FALSE, 0, 'Tarea no encontrada';
        RETURN;
    END IF;
    
    IF v_task_status = 'Completada' THEN
        RETURN QUERY SELECT FALSE, 0, 'Tarea ya completada';
        RETURN;
    END IF;
    
    -- Calcular puntos base (10 puntos por tarea completada)
    v_points := 10;
    
    -- Marcar tarea como completada
    UPDATE project_tasks
    SET status = 'Completada',
        completed_date = CURRENT_DATE,
        actual_hours = COALESCE(p_hours_worked, actual_hours)
    WHERE id = p_task_id;
    
    -- Registrar progreso diario
    INSERT INTO daily_progress (project_id, user_id, work_date, tasks_completed, hours_worked)
    VALUES (v_project_id, p_user_id, CURRENT_DATE, 1, COALESCE(p_hours_worked, 0))
    ON CONFLICT (project_id, user_id, work_date) 
    DO UPDATE SET 
        tasks_completed = daily_progress.tasks_completed + 1,
        hours_worked = daily_progress.hours_worked + COALESCE(p_hours_worked, 0);
    
    -- Otorgar puntos si existe el sistema de gamificación
    BEGIN
        PERFORM award_points(p_user_id, v_points, 'Tarea de proyecto completada', 'project_task', p_task_id);
    EXCEPTION WHEN OTHERS THEN
        -- Si no existe award_points, continuar sin error
        NULL;
    END;
    
    RETURN QUERY SELECT TRUE, v_points, 'Tarea completada exitosamente';
END;
$$ LANGUAGE plpgsql;

-- Función para marcar proyecto como desplegado
CREATE OR REPLACE FUNCTION deploy_project(p_project_id INTEGER, p_user_id INTEGER)
RETURNS TABLE(success BOOLEAN, points INTEGER, message TEXT) AS $$
DECLARE
    v_incomplete_tasks INTEGER;
    v_points INTEGER;
BEGIN
    -- Verificar que el proyecto pertenece al usuario
    IF NOT EXISTS (SELECT 1 FROM projects WHERE id = p_project_id AND user_id = p_user_id) THEN
        RETURN QUERY SELECT FALSE, 0, 'Proyecto no encontrado';
        RETURN;
    END IF;
    
    -- Contar tareas incompletas
    SELECT COUNT(*) INTO v_incomplete_tasks
    FROM project_tasks
    WHERE project_id = p_project_id AND status != 'Completada';
    
    IF v_incomplete_tasks > 0 THEN
        RETURN QUERY SELECT FALSE, 0, 'Aún hay ' || v_incomplete_tasks || ' tareas pendientes';
        RETURN;
    END IF;
    
    -- Calcular puntos (50 puntos por desplegar proyecto completo)
    v_points := 50;
    
    -- Marcar proyecto como desplegado
    UPDATE projects
    SET status = 'En Producción',
        deployed_date = CURRENT_DATE
    WHERE id = p_project_id;
    
    -- Otorgar puntos bonus
    BEGIN
        PERFORM award_points(p_user_id, v_points, 'Proyecto desplegado a producción', 'project_deploy', p_project_id);
    EXCEPTION WHEN OTHERS THEN
        NULL;
    END;
    
    RETURN QUERY SELECT TRUE, v_points, 'Proyecto desplegado exitosamente';
END;
$$ LANGUAGE plpgsql;

-- Agregar constraint único para daily_progress
ALTER TABLE daily_progress ADD CONSTRAINT unique_daily_progress 
    UNIQUE (project_id, user_id, work_date);

-- Comentarios
COMMENT ON TABLE projects IS 'Proyectos principales que contienen múltiples tareas';
COMMENT ON TABLE project_tasks IS 'Tareas individuales asociadas a un proyecto';
COMMENT ON TABLE daily_progress IS 'Registro de progreso diario por proyecto';
COMMENT ON COLUMN projects.progress_percentage IS 'Porcentaje calculado automáticamente basado en tareas completadas';
COMMENT ON COLUMN project_tasks.order_index IS 'Orden de la tarea dentro del proyecto';
