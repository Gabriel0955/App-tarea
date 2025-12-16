<?php
// Archivo de autenticación - incluir en todas las páginas protegidas
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

// Función para obtener el ID del usuario actual
function get_current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

// Función para obtener el nombre del usuario actual
function get_current_username() {
    return $_SESSION['username'] ?? 'Usuario';
}
