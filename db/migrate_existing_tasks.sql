-- Script de migración para usuarios existentes
-- Ejecutar SOLO SI YA TIENES TAREAS EN LA BASE DE DATOS

USE tasks_app;

-- Paso 1: Crear un usuario por defecto para tareas existentes
INSERT INTO users (username, email, password) 
VALUES ('admin', 'admin@apptareas.com', '$2y$10$rH7QYw9qM1D5KZxV3L9b2.K6XQZQF6eVBJ4Xv5Qj2wZC9qKLvX6bm')
ON DUPLICATE KEY UPDATE id=id;
-- Contraseña por defecto: admin123

-- Paso 2: Obtener el ID del usuario admin
SET @admin_user_id = (SELECT id FROM users WHERE username = 'admin' LIMIT 1);

-- Paso 3: Asignar todas las tareas existentes al usuario admin
UPDATE tasks SET user_id = @admin_user_id WHERE user_id = 0 OR user_id IS NULL;

-- Verificar
SELECT 
    u.username,
    COUNT(t.id) as total_tareas
FROM users u
LEFT JOIN tasks t ON u.id = t.user_id
GROUP BY u.id, u.username;

-- Nota: Después de ejecutar este script, puedes iniciar sesión con:
-- Usuario: admin
-- Contraseña: admin123
-- ¡CAMBIA LA CONTRASEÑA INMEDIATAMENTE!
