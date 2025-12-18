-- Migración: Sistema de roles y permisos
-- Fecha: 2025-12-17
-- Descripción: Agrega tabla de roles y relaciona con usuarios

-- Tabla de roles
CREATE TABLE IF NOT EXISTS roles (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insertar roles por defecto
INSERT INTO roles (name, description) VALUES
    ('admin', 'Administrador con acceso completo al sistema'),
    ('manager', 'Gestor que puede ver y administrar tareas de su equipo'),
    ('user', 'Usuario estándar con acceso a sus propias tareas'),
    ('viewer', 'Solo visualización, sin permisos de edición')
ON CONFLICT (name) DO NOTHING;

-- Agregar columna role_id a la tabla users
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS role_id INTEGER DEFAULT 3 REFERENCES roles(id);

-- Asignar rol 'user' (id=3) a todos los usuarios existentes
UPDATE users SET role_id = 3 WHERE role_id IS NULL;

-- Crear índice para mejorar consultas
CREATE INDEX IF NOT EXISTS idx_users_role_id ON users(role_id);

-- Tabla de permisos (opcional, para sistema más avanzado)
CREATE TABLE IF NOT EXISTS permissions (
    id SERIAL PRIMARY KEY,
    role_id INTEGER NOT NULL REFERENCES roles(id) ON DELETE CASCADE,
    resource VARCHAR(50) NOT NULL, -- 'tasks', 'projects', 'users', 'reports'
    can_create BOOLEAN DEFAULT FALSE,
    can_read BOOLEAN DEFAULT TRUE,
    can_update BOOLEAN DEFAULT FALSE,
    can_delete BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(role_id, resource)
);

-- Permisos para rol ADMIN (acceso total)
INSERT INTO permissions (role_id, resource, can_create, can_read, can_update, can_delete) VALUES
    (1, 'tasks', TRUE, TRUE, TRUE, TRUE),
    (1, 'projects', TRUE, TRUE, TRUE, TRUE),
    (1, 'users', TRUE, TRUE, TRUE, TRUE),
    (1, 'reports', TRUE, TRUE, TRUE, TRUE),
    (1, 'settings', TRUE, TRUE, TRUE, TRUE)
ON CONFLICT (role_id, resource) DO NOTHING;

-- Permisos para rol MANAGER (gestión de equipo)
INSERT INTO permissions (role_id, resource, can_create, can_read, can_update, can_delete) VALUES
    (2, 'tasks', TRUE, TRUE, TRUE, TRUE),
    (2, 'projects', TRUE, TRUE, TRUE, TRUE),
    (2, 'users', FALSE, TRUE, FALSE, FALSE),
    (2, 'reports', TRUE, TRUE, TRUE, FALSE),
    (2, 'settings', FALSE, TRUE, FALSE, FALSE)
ON CONFLICT (role_id, resource) DO NOTHING;

-- Permisos para rol USER (operaciones estándar)
INSERT INTO permissions (role_id, resource, can_create, can_read, can_update, can_delete) VALUES
    (3, 'tasks', TRUE, TRUE, TRUE, TRUE),
    (3, 'projects', TRUE, TRUE, TRUE, TRUE),
    (3, 'users', FALSE, FALSE, FALSE, FALSE),
    (3, 'reports', FALSE, TRUE, FALSE, FALSE),
    (3, 'settings', FALSE, TRUE, FALSE, FALSE)
ON CONFLICT (role_id, resource) DO NOTHING;

-- Permisos para rol VIEWER (solo lectura)
INSERT INTO permissions (role_id, resource, can_create, can_read, can_update, can_delete) VALUES
    (4, 'tasks', FALSE, TRUE, FALSE, FALSE),
    (4, 'projects', FALSE, TRUE, FALSE, FALSE),
    (4, 'users', FALSE, FALSE, FALSE, FALSE),
    (4, 'reports', FALSE, TRUE, FALSE, FALSE),
    (4, 'settings', FALSE, FALSE, FALSE, FALSE)
ON CONFLICT (role_id, resource) DO NOTHING;

-- Crear vista para facilitar consultas de usuarios con roles
CREATE OR REPLACE VIEW users_with_roles AS
SELECT 
    u.id,
    u.username,
    u.email,
    u.created_at,
    r.id as role_id,
    r.name as role_name,
    r.description as role_description
FROM users u
LEFT JOIN roles r ON u.role_id = r.id;

-- Función para verificar permisos
CREATE OR REPLACE FUNCTION check_permission(
    p_user_id INTEGER,
    p_resource VARCHAR,
    p_action VARCHAR -- 'create', 'read', 'update', 'delete'
) RETURNS BOOLEAN AS $$
DECLARE
    v_has_permission BOOLEAN;
BEGIN
    SELECT 
        CASE 
            WHEN p_action = 'create' THEN p.can_create
            WHEN p_action = 'read' THEN p.can_read
            WHEN p_action = 'update' THEN p.can_update
            WHEN p_action = 'delete' THEN p.can_delete
            ELSE FALSE
        END INTO v_has_permission
    FROM users u
    JOIN permissions p ON u.role_id = p.role_id
    WHERE u.id = p_user_id AND p.resource = p_resource;
    
    RETURN COALESCE(v_has_permission, FALSE);
END;
$$ LANGUAGE plpgsql;

-- Función para obtener rol de un usuario
CREATE OR REPLACE FUNCTION get_user_role(p_user_id INTEGER)
RETURNS TABLE (
    role_id INTEGER,
    role_name VARCHAR,
    role_description TEXT
) AS $$
BEGIN
    RETURN QUERY
    SELECT r.id, r.name, r.description
    FROM users u
    JOIN roles r ON u.role_id = r.id
    WHERE u.id = p_user_id;
END;
$$ LANGUAGE plpgsql;

-- Comentarios en tablas
COMMENT ON TABLE roles IS 'Roles del sistema con diferentes niveles de acceso';
COMMENT ON TABLE permissions IS 'Permisos granulares por rol y recurso';
COMMENT ON COLUMN users.role_id IS 'Rol asignado al usuario (por defecto: user)';

-- Log de migración
DO $$
BEGIN
    RAISE NOTICE '✓ Sistema de roles instalado correctamente';
    RAISE NOTICE '✓ 4 roles creados: admin, manager, user, viewer';
    RAISE NOTICE '✓ Permisos configurados para cada rol';
    RAISE NOTICE '✓ Usuarios existentes asignados como "user"';
END $$;
