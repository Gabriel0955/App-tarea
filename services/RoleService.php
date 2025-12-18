<?php
/**
 * Servicio de gestión de roles y permisos
 */

/**
 * Obtener todos los roles disponibles
 */
function getRoles($pdo) {
    $stmt = $pdo->query("SELECT * FROM roles ORDER BY id");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Obtener rol de un usuario
 */
function getUserRole($pdo, $user_id) {
    $stmt = $pdo->prepare("
        SELECT r.id, r.name, r.description
        FROM users u
        JOIN roles r ON u.role_id = r.id
        WHERE u.id = ?
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Verificar si un usuario tiene un permiso específico
 */
function hasPermission($pdo, $user_id, $resource, $action) {
    $stmt = $pdo->prepare("SELECT check_permission(?, ?, ?)");
    $stmt->execute([$user_id, $resource, $action]);
    return (bool)$stmt->fetchColumn();
}

/**
 * Obtener todos los permisos de un usuario
 */
function getUserPermissions($pdo, $user_id) {
    $stmt = $pdo->prepare("
        SELECT 
            p.resource,
            p.can_create,
            p.can_read,
            p.can_update,
            p.can_delete
        FROM users u
        JOIN permissions p ON u.role_id = p.role_id
        WHERE u.id = ?
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Actualizar rol de un usuario (solo admin)
 */
function updateUserRole($pdo, $user_id, $role_id, $admin_id) {
    // Verificar que el admin tenga permisos
    if (!hasPermission($pdo, $admin_id, 'users', 'update')) {
        return ['success' => false, 'error' => 'Sin permisos para modificar usuarios'];
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET role_id = ? WHERE id = ?");
        $stmt->execute([$role_id, $user_id]);
        return ['success' => true];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Obtener usuarios con sus roles
 */
function getUsersWithRoles($pdo, $admin_id) {
    // Verificar permisos
    if (!hasPermission($pdo, $admin_id, 'users', 'read')) {
        return ['success' => false, 'error' => 'Sin permisos para ver usuarios'];
    }
    
    $stmt = $pdo->query("SELECT * FROM users_with_roles ORDER BY username");
    return [
        'success' => true,
        'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ];
}

/**
 * Verificar si un usuario es admin
 */
function isAdmin($pdo, $user_id) {
    $role = getUserRole($pdo, $user_id);
    return $role && $role['name'] === 'admin';
}

/**
 * Verificar si un usuario es manager o admin
 */
function isManagerOrAdmin($pdo, $user_id) {
    $role = getUserRole($pdo, $user_id);
    return $role && in_array($role['name'], ['admin', 'manager']);
}

/**
 * Middleware para verificar permisos en una acción
 */
function requirePermission($pdo, $user_id, $resource, $action, $redirect = true) {
    if (!hasPermission($pdo, $user_id, $resource, $action)) {
        if ($redirect) {
            header('Location: /index.php?error=permission_denied');
            exit;
        }
        return false;
    }
    return true;
}

/**
 * Obtener estadísticas de permisos por rol
 */
function getRolePermissionsStats($pdo) {
    $stmt = $pdo->query("
        SELECT 
            r.name as role_name,
            COUNT(p.id) as total_permissions,
            SUM(CASE WHEN p.can_create THEN 1 ELSE 0 END) as create_count,
            SUM(CASE WHEN p.can_update THEN 1 ELSE 0 END) as update_count,
            SUM(CASE WHEN p.can_delete THEN 1 ELSE 0 END) as delete_count
        FROM roles r
        LEFT JOIN permissions p ON r.id = p.role_id
        GROUP BY r.id, r.name
        ORDER BY r.id
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
