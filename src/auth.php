<?php
// Archivo de autenticación - incluir en todas las páginas protegidas
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../services/RoleService.php';

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
    // Detectar si estamos en una subcarpeta
    $script_path = $_SERVER['SCRIPT_NAME'];
    if (strpos($script_path, '/public/tasks/') !== false || 
        strpos($script_path, '/public/gamification/') !== false) {
        header('Location: ../auth/login.php');
    } else {
        header('Location: auth/login.php');
    }
    exit;
}

// Cargar rol del usuario en sesión si no existe
if (!isset($_SESSION['user_role'])) {
    $pdo = get_pdo();
    $role = getUserRole($pdo, $_SESSION['user_id']);
    $_SESSION['user_role'] = $role['name'] ?? 'user';
    $_SESSION['role_id'] = $role['id'] ?? 3;
}

// Función para obtener el ID del usuario actual
function get_current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

// Función para obtener el nombre del usuario actual
function get_current_username() {
    return $_SESSION['username'] ?? 'Usuario';
}

// Función para obtener el rol del usuario actual
function get_current_role() {
    return $_SESSION['user_role'] ?? 'user';
}

// Función para verificar si el usuario actual es admin
function is_admin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

// Función para verificar si el usuario actual es manager o admin
function is_manager_or_admin() {
    return isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'manager']);
}

// Función para verificar permisos del usuario actual
function can($action, $resource = 'tasks') {
    $pdo = get_pdo();
    $user_id = get_current_user_id();
    return hasPermission($pdo, $user_id, $resource, $action);
}

// Función para requerir un permiso específico (redirige si no tiene permiso)
function require_permission($resource, $action) {
    if (!can($action, $resource)) {
        header('Location: /index.php?error=permission_denied');
        exit;
    }
}

// Función para requerir rol específico
function require_role($required_role) {
    $current_role = get_current_role();
    
    $role_hierarchy = ['admin' => 3, 'manager' => 2, 'user' => 1, 'viewer' => 0];
    
    if (!isset($role_hierarchy[$current_role]) || 
        !isset($role_hierarchy[$required_role]) ||
        $role_hierarchy[$current_role] < $role_hierarchy[$required_role]) {
        header('Location: /index.php?error=insufficient_role');
        exit;
    }
}
