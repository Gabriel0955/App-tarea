-- Script para corregir el sistema de niveles de gamificación

-- Eliminar función existente si tiene nombre de parámetro diferente
DROP FUNCTION IF EXISTS calculate_points_for_level(integer);

-- Función para calcular puntos necesarios para un nivel
CREATE OR REPLACE FUNCTION calculate_points_for_level(p_level INTEGER)
RETURNS INTEGER AS $$
BEGIN
  -- Fórmula: 100 * nivel * 1.5 (escala exponencial moderada)
  -- Nivel 1: 100 puntos
  -- Nivel 2: 300 puntos (100 + 200)
  -- Nivel 3: 600 puntos (300 + 300)
  -- Nivel 4: 1050 puntos (600 + 450)
  RETURN FLOOR(100 * p_level * POWER(1.5, p_level - 1));
END;
$$ LANGUAGE plpgsql IMMUTABLE;

-- Función para actualizar nivel de usuario automáticamente
CREATE OR REPLACE FUNCTION update_user_level()
RETURNS TRIGGER AS $$
DECLARE
  new_level INTEGER;
  points_needed INTEGER;
BEGIN
  -- Calcular nivel basado en puntos totales
  new_level := NEW.current_level;
  
  -- Subir niveles mientras tenga suficientes puntos
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
    
    RAISE NOTICE 'Usuario % subió al nivel %! Puntos: % / %', NEW.user_id, new_level, NEW.total_points, NEW.points_to_next_level;
  END IF;
  
  NEW.updated_at := CURRENT_TIMESTAMP;
  
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Recrear trigger para actualizar nivel automáticamente
DROP TRIGGER IF EXISTS trigger_update_user_level ON user_stats;
CREATE TRIGGER trigger_update_user_level
  BEFORE UPDATE OF total_points ON user_stats
  FOR EACH ROW
  EXECUTE FUNCTION update_user_level();

-- Actualizar niveles de todos los usuarios existentes
UPDATE user_stats SET total_points = total_points WHERE total_points > 0;

-- Verificar niveles actualizados
SELECT 
    user_id, 
    total_points, 
    current_level, 
    points_to_next_level,
    points_to_next_level - total_points as points_remaining
FROM user_stats 
ORDER BY total_points DESC;
