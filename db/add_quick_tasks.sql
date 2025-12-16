-- Tabla para tareas rápidas/cortas del día
CREATE TABLE IF NOT EXISTS quick_tasks (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    task_date DATE NOT NULL DEFAULT CURRENT_DATE,
    scheduled_time TIME,
    completed BOOLEAN DEFAULT FALSE,
    completed_at TIMESTAMP,
    points_awarded INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Índices para mejorar rendimiento
CREATE INDEX IF NOT EXISTS idx_quick_tasks_user_date ON quick_tasks(user_id, task_date);
CREATE INDEX IF NOT EXISTS idx_quick_tasks_completed ON quick_tasks(user_id, completed, task_date);

-- Trigger para actualizar updated_at
CREATE OR REPLACE FUNCTION update_quick_tasks_timestamp()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_update_quick_tasks_timestamp
BEFORE UPDATE ON quick_tasks
FOR EACH ROW
EXECUTE FUNCTION update_quick_tasks_timestamp();

-- Función para completar tarea rápida y otorgar puntos
CREATE OR REPLACE FUNCTION complete_quick_task(p_task_id INTEGER, p_user_id INTEGER)
RETURNS TABLE(success BOOLEAN, points INTEGER, message TEXT) AS $$
DECLARE
    v_already_completed BOOLEAN;
    v_points INTEGER;
    v_task_date DATE;
    v_completed_today BOOLEAN;
BEGIN
    -- Verificar si la tarea existe y pertenece al usuario
    SELECT completed, task_date INTO v_already_completed, v_task_date
    FROM quick_tasks
    WHERE id = p_task_id AND user_id = p_user_id;
    
    IF NOT FOUND THEN
        RETURN QUERY SELECT FALSE, 0, 'Tarea no encontrada';
        RETURN;
    END IF;
    
    IF v_already_completed THEN
        RETURN QUERY SELECT FALSE, 0, 'Tarea ya completada';
        RETURN;
    END IF;
    
    -- Determinar puntos: más puntos si se completa el mismo día
    v_completed_today := v_task_date = CURRENT_DATE;
    v_points := CASE 
        WHEN v_completed_today THEN 5  -- Completada el mismo día
        ELSE 3  -- Completada después
    END;
    
    -- Marcar como completada
    UPDATE quick_tasks 
    SET completed = TRUE,
        completed_at = CURRENT_TIMESTAMP,
        points_awarded = v_points
    WHERE id = p_task_id;
    
    -- Otorgar puntos
    PERFORM award_points(p_user_id, v_points, 'Tarea rápida completada', 'quick_task', p_task_id);
    
    -- Actualizar racha
    PERFORM update_user_streak(p_user_id);
    
    -- Verificar logros
    PERFORM check_and_unlock_achievements(p_user_id);
    
    RETURN QUERY SELECT TRUE, v_points, 'Tarea completada exitosamente';
END;
$$ LANGUAGE plpgsql;

-- Comentarios
COMMENT ON TABLE quick_tasks IS 'Tareas rápidas del día para seguimiento diario';
COMMENT ON COLUMN quick_tasks.task_date IS 'Fecha para la cual está programada la tarea';
COMMENT ON COLUMN quick_tasks.scheduled_time IS 'Hora programada opcional (ej: reunión a las 10:00)';
COMMENT ON COLUMN quick_tasks.points_awarded IS 'Puntos otorgados al completar (5 si se hace el mismo día, 3 si es después)';
