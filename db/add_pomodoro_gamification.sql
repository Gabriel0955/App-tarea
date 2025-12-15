-- ==========================================
-- POMODORO + GAMIFICACIÓN
-- ==========================================

-- Tabla de sesiones Pomodoro
CREATE TABLE IF NOT EXISTS pomodoro_sessions (
  id SERIAL PRIMARY KEY,
  user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  task_id INTEGER REFERENCES tasks(id) ON DELETE SET NULL,
  
  -- Duración
  work_duration INTEGER NOT NULL DEFAULT 25, -- minutos
  break_duration INTEGER NOT NULL DEFAULT 5,  -- minutos
  
  -- Estado
  started_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  completed_at TIMESTAMP,
  interrupted_at TIMESTAMP,
  status VARCHAR(20) NOT NULL DEFAULT 'active', -- active, completed, interrupted
  
  -- Tracking
  focus_score INTEGER DEFAULT 100, -- 0-100, penalización por interrupciones
  notes TEXT,
  
  -- Índices
  CONSTRAINT valid_status CHECK (status IN ('active', 'completed', 'interrupted'))
);

CREATE INDEX idx_pomodoro_user ON pomodoro_sessions(user_id);
CREATE INDEX idx_pomodoro_task ON pomodoro_sessions(task_id);
CREATE INDEX idx_pomodoro_date ON pomodoro_sessions(started_at);

-- Tabla de estadísticas de usuario (gamificación)
CREATE TABLE IF NOT EXISTS user_stats (
  user_id INTEGER PRIMARY KEY REFERENCES users(id) ON DELETE CASCADE,
  
  -- Puntos y nivel
  total_points INTEGER NOT NULL DEFAULT 0,
  current_level INTEGER NOT NULL DEFAULT 1,
  points_to_next_level INTEGER NOT NULL DEFAULT 100,
  
  -- Rachas (streaks)
  current_streak INTEGER NOT NULL DEFAULT 0, -- días consecutivos con actividad
  longest_streak INTEGER NOT NULL DEFAULT 0,
  last_activity_date DATE,
  
  -- Estadísticas de tareas
  tasks_completed INTEGER NOT NULL DEFAULT 0,
  tasks_deployed INTEGER NOT NULL DEFAULT 0,
  tasks_on_time INTEGER NOT NULL DEFAULT 0, -- completadas antes de due_date
  tasks_late INTEGER NOT NULL DEFAULT 0,
  
  -- Estadísticas de Pomodoro
  pomodoros_completed INTEGER NOT NULL DEFAULT 0,
  total_focus_time INTEGER NOT NULL DEFAULT 0, -- minutos totales
  avg_focus_score DECIMAL(5,2) DEFAULT 100.00,
  
  -- Rankings
  rank_position INTEGER,
  rank_updated_at TIMESTAMP,
  
  -- Timestamps
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de logros (achievements)
CREATE TABLE IF NOT EXISTS achievements (
  id SERIAL PRIMARY KEY,
  code VARCHAR(50) NOT NULL UNIQUE,
  name VARCHAR(100) NOT NULL,
  description TEXT NOT NULL,
  category VARCHAR(30) NOT NULL, -- tasks, pomodoro, streak, speed, quality
  icon VARCHAR(10) NOT NULL, -- emoji
  points INTEGER NOT NULL DEFAULT 0,
  requirement_type VARCHAR(30) NOT NULL, -- count, streak, percentage, time
  requirement_value INTEGER NOT NULL,
  tier VARCHAR(20) DEFAULT 'bronze', -- bronze, silver, gold, platinum, diamond
  is_secret BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de logros desbloqueados por usuario
CREATE TABLE IF NOT EXISTS user_achievements (
  id SERIAL PRIMARY KEY,
  user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  achievement_id INTEGER NOT NULL REFERENCES achievements(id) ON DELETE CASCADE,
  unlocked_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  progress INTEGER DEFAULT 0, -- para logros en progreso
  
  UNIQUE(user_id, achievement_id)
);

CREATE INDEX idx_user_achievements_user ON user_achievements(user_id);
CREATE INDEX idx_user_achievements_date ON user_achievements(unlocked_at);

-- Tabla de historial de puntos
CREATE TABLE IF NOT EXISTS points_history (
  id SERIAL PRIMARY KEY,
  user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  points INTEGER NOT NULL,
  reason VARCHAR(100) NOT NULL,
  reference_type VARCHAR(30), -- task, pomodoro, achievement, streak
  reference_id INTEGER,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_points_history_user ON points_history(user_id);
CREATE INDEX idx_points_history_date ON points_history(created_at);

-- ==========================================
-- LOGROS PREDEFINIDOS
-- ==========================================

INSERT INTO achievements (code, name, description, category, icon, points, requirement_type, requirement_value, tier) VALUES
-- Tareas
('first_task', 'Primera Tarea', 'Completa tu primera tarea', 'tasks', '🎯', 10, 'count', 1, 'bronze'),
('task_master_10', 'Organizador', 'Completa 10 tareas', 'tasks', '📋', 50, 'count', 10, 'bronze'),
('task_master_50', 'Ejecutor', 'Completa 50 tareas', 'tasks', '⚡', 150, 'count', 50, 'silver'),
('task_master_100', 'Maestro de Tareas', 'Completa 100 tareas', 'tasks', '👑', 300, 'count', 100, 'gold'),
('task_master_500', 'Leyenda', 'Completa 500 tareas', 'tasks', '💎', 1000, 'count', 500, 'diamond'),

-- Deployments
('first_deploy', 'Primer Deploy', 'Realiza tu primer deployment exitoso', 'tasks', '🚀', 20, 'count', 1, 'bronze'),
('deploy_master_10', 'DevOps Junior', 'Completa 10 deployments', 'tasks', '🔧', 100, 'count', 10, 'silver'),
('deploy_master_50', 'DevOps Senior', 'Completa 50 deployments', 'tasks', '⚙️', 300, 'count', 50, 'gold'),
('deploy_master_100', 'DevOps Maestro', 'Completa 100 deployments', 'tasks', '🏆', 500, 'count', 100, 'platinum'),

-- Puntualidad
('on_time_10', 'Puntual', 'Completa 10 tareas antes de su fecha límite', 'quality', '⏰', 75, 'count', 10, 'bronze'),
('on_time_50', 'Súper Puntual', 'Completa 50 tareas a tiempo', 'quality', '⚡', 200, 'count', 50, 'silver'),
('perfect_week', 'Semana Perfecta', 'Completa todas las tareas de la semana a tiempo', 'quality', '✨', 150, 'percentage', 100, 'gold'),

-- Pomodoro
('first_pomodoro', 'Primer Pomodoro', 'Completa tu primera sesión Pomodoro', 'pomodoro', '🍅', 10, 'count', 1, 'bronze'),
('pomodoro_10', 'Enfocado', 'Completa 10 sesiones Pomodoro', 'pomodoro', '🎯', 50, 'count', 10, 'bronze'),
('pomodoro_50', 'Concentración Total', 'Completa 50 sesiones Pomodoro', 'pomodoro', '🧠', 150, 'count', 50, 'silver'),
('pomodoro_100', 'Maestro del Foco', 'Completa 100 sesiones Pomodoro', 'pomodoro', '💫', 300, 'count', 100, 'gold'),
('focus_master', 'Concentración Perfecta', 'Mantén un score de 100% en 10 pomodoros consecutivos', 'pomodoro', '🌟', 200, 'count', 10, 'platinum'),

-- Rachas (streaks)
('streak_3', 'Comprometido', 'Mantén una racha de 3 días', 'streak', '🔥', 30, 'streak', 3, 'bronze'),
('streak_7', 'Dedicado', 'Mantén una racha de 7 días', 'streak', '🔥', 75, 'streak', 7, 'silver'),
('streak_14', 'Imparable', 'Mantén una racha de 14 días', 'streak', '🔥', 150, 'streak', 14, 'gold'),
('streak_30', 'Leyenda Viviente', 'Mantén una racha de 30 días', 'streak', '🔥', 300, 'streak', 30, 'platinum'),
('streak_100', 'Dios del Olimpo', 'Mantén una racha de 100 días', 'streak', '⚡', 1000, 'streak', 100, 'diamond'),

-- Velocidad
('speed_demon', 'Velocista', 'Completa 5 tareas en un solo día', 'speed', '💨', 100, 'count', 5, 'silver'),
('productivity_beast', 'Bestia Productiva', 'Completa 10 tareas en un día', 'speed', '🦁', 250, 'count', 10, 'gold'),

-- Secretos
('night_owl', 'Búho Nocturno', 'Completa una tarea entre las 00:00 y 05:00', 'tasks', '🦉', 50, 'count', 1, 'silver') ON CONFLICT (code) DO NOTHING;

-- ==========================================
-- FUNCIONES AUXILIARES
-- ==========================================

-- Función para calcular puntos de nivel
CREATE OR REPLACE FUNCTION calculate_points_for_level(level INTEGER)
RETURNS INTEGER AS $$
BEGIN
  -- Formula exponencial: nivel * 100 * 1.2^(nivel-1)
  RETURN FLOOR(level * 100 * POWER(1.2, level - 1));
END;
$$ LANGUAGE plpgsql IMMUTABLE;

-- Función para actualizar nivel de usuario
CREATE OR REPLACE FUNCTION update_user_level()
RETURNS TRIGGER AS $$
DECLARE
  new_level INTEGER;
  points_needed INTEGER;
BEGIN
  -- Calcular nivel basado en puntos totales
  new_level := NEW.current_level;
  
  LOOP
    points_needed := calculate_points_for_level(new_level + 1);
    
    IF NEW.total_points >= points_needed THEN
      new_level := new_level + 1;
    ELSE
      EXIT;
    END IF;
  END LOOP;
  
  -- Actualizar si cambió el nivel
  IF new_level != NEW.current_level THEN
    NEW.current_level := new_level;
    NEW.points_to_next_level := calculate_points_for_level(new_level + 1);
  END IF;
  
  NEW.updated_at := CURRENT_TIMESTAMP;
  
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Trigger para actualizar nivel automáticamente
DROP TRIGGER IF EXISTS trigger_update_user_level ON user_stats;
CREATE TRIGGER trigger_update_user_level
  BEFORE UPDATE OF total_points ON user_stats
  FOR EACH ROW
  EXECUTE FUNCTION update_user_level();

-- Función para actualizar racha (streak)
CREATE OR REPLACE FUNCTION update_user_streak(p_user_id INTEGER)
RETURNS VOID AS $$
DECLARE
  last_date DATE;
  today DATE;
  v_current_streak INTEGER;
BEGIN
  today := CURRENT_DATE;
  
  SELECT last_activity_date, current_streak
  INTO last_date, v_current_streak
  FROM user_stats
  WHERE user_id = p_user_id;
  
  -- Si no hay registro previo, inicializar
  IF last_date IS NULL THEN
    UPDATE user_stats
    SET current_streak = 1,
        longest_streak = GREATEST(longest_streak, 1),
        last_activity_date = today
    WHERE user_id = p_user_id;
    RETURN;
  END IF;
  
  -- Si es el mismo día, no hacer nada
  IF last_date = today THEN
    RETURN;
  END IF;
  
  -- Si es día consecutivo, incrementar racha
  IF last_date = today - INTERVAL '1 day' THEN
    UPDATE user_stats
    SET current_streak = current_streak + 1,
        longest_streak = GREATEST(longest_streak, current_streak + 1),
        last_activity_date = today
    WHERE user_id = p_user_id;
  ELSE
    -- Racha rota, reiniciar
    UPDATE user_stats
    SET current_streak = 1,
        last_activity_date = today
    WHERE user_id = p_user_id;
  END IF;
END;
$$ LANGUAGE plpgsql;

-- Función para otorgar puntos
CREATE OR REPLACE FUNCTION award_points(
  p_user_id INTEGER,
  p_points INTEGER,
  p_reason VARCHAR(100),
  p_reference_type VARCHAR(30) DEFAULT NULL,
  p_reference_id INTEGER DEFAULT NULL
)
RETURNS VOID AS $$
BEGIN
  -- Registrar en historial
  INSERT INTO points_history (user_id, points, reason, reference_type, reference_id)
  VALUES (p_user_id, p_points, p_reason, p_reference_type, p_reference_id);
  
  -- Actualizar puntos totales
  UPDATE user_stats
  SET total_points = total_points + p_points
  WHERE user_id = p_user_id;
  
  -- Si no existe registro, crear
  IF NOT FOUND THEN
    INSERT INTO user_stats (user_id, total_points)
    VALUES (p_user_id, p_points);
  END IF;
END;
$$ LANGUAGE plpgsql;

-- Función para verificar y desbloquear logros
CREATE OR REPLACE FUNCTION check_and_unlock_achievements(p_user_id INTEGER)
RETURNS TABLE(achievement_code VARCHAR, achievement_name VARCHAR, points_awarded INTEGER) AS $$
DECLARE
  v_tasks_completed INTEGER;
  v_tasks_deployed INTEGER;
  v_tasks_on_time INTEGER;
  v_pomodoros_completed INTEGER;
  v_current_streak INTEGER;
  v_achievement RECORD;
  v_unlocked BOOLEAN;
BEGIN
  -- Obtener estadísticas del usuario
  SELECT us.tasks_completed, us.tasks_deployed, us.tasks_on_time, 
         us.pomodoros_completed, us.current_streak
  INTO v_tasks_completed, v_tasks_deployed, v_tasks_on_time,
       v_pomodoros_completed, v_current_streak
  FROM user_stats us
  WHERE us.user_id = p_user_id;
  
  -- Recorrer logros no desbloqueados
  FOR v_achievement IN 
    SELECT a.*
    FROM achievements a
    WHERE NOT EXISTS (
      SELECT 1 FROM user_achievements ua
      WHERE ua.user_id = p_user_id AND ua.achievement_id = a.id
    )
  LOOP
    v_unlocked := FALSE;
    
    -- Verificar según categoría y tipo de requisito
    CASE v_achievement.category
      WHEN 'tasks' THEN
        IF v_achievement.requirement_type = 'count' AND v_tasks_completed >= v_achievement.requirement_value THEN
          v_unlocked := TRUE;
        END IF;
      WHEN 'pomodoro' THEN
        IF v_achievement.requirement_type = 'count' AND v_pomodoros_completed >= v_achievement.requirement_value THEN
          v_unlocked := TRUE;
        END IF;
      WHEN 'streak' THEN
        IF v_achievement.requirement_type = 'streak' AND v_current_streak >= v_achievement.requirement_value THEN
          v_unlocked := TRUE;
        END IF;
      WHEN 'quality' THEN
        IF v_achievement.code = 'on_time_10' AND v_tasks_on_time >= 10 THEN
          v_unlocked := TRUE;
        ELSIF v_achievement.code = 'on_time_50' AND v_tasks_on_time >= 50 THEN
          v_unlocked := TRUE;
        END IF;
      ELSE
        -- Para logros de deploy, usar tasks_deployed
        IF v_achievement.code LIKE 'deploy_%' AND v_achievement.requirement_type = 'count' 
           AND v_tasks_deployed >= v_achievement.requirement_value THEN
          v_unlocked := TRUE;
        END IF;
    END CASE;
    
    -- Si se desbloqueó, registrar y otorgar puntos
    IF v_unlocked THEN
      INSERT INTO user_achievements (user_id, achievement_id)
      VALUES (p_user_id, v_achievement.id);
      
      PERFORM award_points(p_user_id, v_achievement.points, 
                          'Logro desbloqueado: ' || v_achievement.name,
                          'achievement', v_achievement.id);
      
      -- Retornar info del logro
      achievement_code := v_achievement.code;
      achievement_name := v_achievement.name;
      points_awarded := v_achievement.points;
      RETURN NEXT;
    END IF;
  END LOOP;
  
  RETURN;
END;
$$ LANGUAGE plpgsql;

-- ==========================================
-- INICIALIZAR user_stats PARA USUARIOS EXISTENTES
-- ==========================================

INSERT INTO user_stats (user_id, total_points, current_level, points_to_next_level)
SELECT id, 0, 1, 100
FROM users
WHERE NOT EXISTS (
  SELECT 1 FROM user_stats WHERE user_stats.user_id = users.id
);

-- ==========================================
-- COMENTARIOS
-- ==========================================

COMMENT ON TABLE pomodoro_sessions IS 'Sesiones de temporizador Pomodoro con tracking de enfoque';
COMMENT ON TABLE user_stats IS 'Estadísticas y gamificación por usuario';
COMMENT ON TABLE achievements IS 'Logros desbloqueables del sistema';
COMMENT ON TABLE user_achievements IS 'Logros desbloqueados por cada usuario';
COMMENT ON TABLE points_history IS 'Historial de puntos ganados';

COMMENT ON FUNCTION calculate_points_for_level IS 'Calcula puntos necesarios para alcanzar un nivel';
COMMENT ON FUNCTION update_user_level IS 'Actualiza automáticamente el nivel del usuario';
COMMENT ON FUNCTION update_user_streak IS 'Actualiza la racha diaria del usuario';
COMMENT ON FUNCTION award_points IS 'Otorga puntos al usuario y registra en historial';
COMMENT ON FUNCTION check_and_unlock_achievements IS 'Verifica y desbloquea logros automáticamente';
