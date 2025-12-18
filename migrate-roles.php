<?php
require_once 'src/db.php';

echo "=== MIGRACIÓN: Sistema de Roles ===\n\n";

try {
    $pdo = get_pdo();
    $pdo->beginTransaction();
    
    // 1. Crear tabla de roles
    echo "1. Creando tabla 'roles'... ";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS roles (
            id SERIAL PRIMARY KEY,
            name VARCHAR(50) NOT NULL UNIQUE,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "✅\n";
    
    // 2. Insertar roles por defecto
    echo "2. Insertando roles por defecto... ";
    $pdo->exec("
        INSERT INTO roles (name, description) VALUES
            ('admin', 'Administrador con acceso completo al sistema'),
            ('manager', 'Gestor que puede ver y administrar tareas de su equipo'),
            ('user', 'Usuario estándar con acceso a sus propias tareas'),
            ('viewer', 'Solo visualización, sin permisos de edición')
        ON CONFLICT (name) DO NOTHING
    ");
    echo "✅\n";
    
    // 3. Agregar columna role_id a users
    echo "3. Agregando columna role_id a tabla users... ";
    $pdo->exec("
        ALTER TABLE users 
        ADD COLUMN IF NOT EXISTS role_id INTEGER DEFAULT 3 REFERENCES roles(id)
    ");
    echo "✅\n";
    
    // 4. Asignar rol 'user' a usuarios existentes
    echo "4. Asignando rol 'user' a usuarios existentes... ";
    $stmt = $pdo->exec("UPDATE users SET role_id = 3 WHERE role_id IS NULL");
    echo "✅ ($stmt usuarios actualizados)\n";
    
    // 5. Crear índice
    echo "5. Creando índice idx_users_role_id... ";
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_users_role_id ON users(role_id)");
    echo "✅\n";
    
    // 6. Crear tabla de permisos
    echo "6. Creando tabla 'permissions'... ";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS permissions (
            id SERIAL PRIMARY KEY,
            role_id INTEGER NOT NULL REFERENCES roles(id) ON DELETE CASCADE,
            resource VARCHAR(50) NOT NULL,
            can_create BOOLEAN DEFAULT FALSE,
            can_read BOOLEAN DEFAULT TRUE,
            can_update BOOLEAN DEFAULT FALSE,
            can_delete BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(role_id, resource)
        )
    ");
    echo "✅\n";
    
    // 7. Insertar permisos para ADMIN
    echo "7. Configurando permisos para ADMIN... ";
    $pdo->exec("
        INSERT INTO permissions (role_id, resource, can_create, can_read, can_update, can_delete) VALUES
            (1, 'tasks', TRUE, TRUE, TRUE, TRUE),
            (1, 'projects', TRUE, TRUE, TRUE, TRUE),
            (1, 'users', TRUE, TRUE, TRUE, TRUE),
            (1, 'reports', TRUE, TRUE, TRUE, TRUE),
            (1, 'settings', TRUE, TRUE, TRUE, TRUE)
        ON CONFLICT (role_id, resource) DO NOTHING
    ");
    echo "✅\n";
    
    // 8. Insertar permisos para MANAGER
    echo "8. Configurando permisos para MANAGER... ";
    $pdo->exec("
        INSERT INTO permissions (role_id, resource, can_create, can_read, can_update, can_delete) VALUES
            (2, 'tasks', TRUE, TRUE, TRUE, TRUE),
            (2, 'projects', TRUE, TRUE, TRUE, FALSE),
            (2, 'users', FALSE, TRUE, FALSE, FALSE),
            (2, 'reports', TRUE, TRUE, FALSE, FALSE)
        ON CONFLICT (role_id, resource) DO NOTHING
    ");
    echo "✅\n";
    
    // 9. Insertar permisos para USER
    echo "9. Configurando permisos para USER... ";
    $pdo->exec("
        INSERT INTO permissions (role_id, resource, can_create, can_read, can_update, can_delete) VALUES
            (3, 'tasks', TRUE, TRUE, TRUE, TRUE),
            (3, 'projects', TRUE, TRUE, TRUE, FALSE),
            (3, 'users', FALSE, FALSE, FALSE, FALSE),
            (3, 'reports', FALSE, TRUE, FALSE, FALSE)
        ON CONFLICT (role_id, resource) DO NOTHING
    ");
    echo "✅\n";
    
    // 10. Insertar permisos para VIEWER
    echo "10. Configurando permisos para VIEWER... ";
    $pdo->exec("
        INSERT INTO permissions (role_id, resource, can_create, can_read, can_update, can_delete) VALUES
            (4, 'tasks', FALSE, TRUE, FALSE, FALSE),
            (4, 'projects', FALSE, TRUE, FALSE, FALSE),
            (4, 'users', FALSE, FALSE, FALSE, FALSE),
            (4, 'reports', FALSE, TRUE, FALSE, FALSE)
        ON CONFLICT (role_id, resource) DO NOTHING
    ");
    echo "✅\n";
    
    // 11. Crear vista users_with_roles
    echo "11. Creando vista users_with_roles... ";
    $pdo->exec("
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
        LEFT JOIN roles r ON u.role_id = r.id
    ");
    echo "✅\n";
    
    $pdo->commit();
    
    echo "\n=== ✅ MIGRACIÓN COMPLETADA EXITOSAMENTE ===\n\n";
    
    // Mostrar resumen
    echo "Resumen:\n";
    echo str_repeat("-", 50) . "\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM roles");
    $count = $stmt->fetch()['total'];
    echo "Roles creados: $count\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM permissions");
    $count = $stmt->fetch()['total'];
    echo "Permisos configurados: $count\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role_id = 3");
    $count = $stmt->fetch()['total'];
    echo "Usuarios con rol 'user': $count\n";
    
    echo "\n";
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
