<?php
/**
 * Servicio de gestión de usuarios
 * Contiene todas las funciones relacionadas con autenticación y usuarios
 */

/**
 * Autenticar usuario
 */
function authenticateUser($pdo, $username_or_email, $password) {
    $stmt = $pdo->prepare('SELECT id, username, password FROM users WHERE username = ? OR email = ?');
    $stmt->execute([$username_or_email, $username_or_email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        return [
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username']
            ]
        ];
    }
    
    return [
        'success' => false,
        'error' => 'Usuario o contraseña incorrectos'
    ];
}

/**
 * Registrar nuevo usuario
 */
function registerUser($pdo, $username, $email, $password) {
    // Validaciones
    $errors = [];
    
    if (strlen($username) < 3) {
        $errors[] = 'El usuario debe tener al menos 3 caracteres';
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email inválido';
    }
    
    if (strlen($password) < 6) {
        $errors[] = 'La contraseña debe tener al menos 6 caracteres';
    }
    
    if (!empty($errors)) {
        return [
            'success' => false,
            'errors' => $errors
        ];
    }
    
    // Verificar si el usuario o email ya existen
    if (userExists($pdo, $username, $email)) {
        return [
            'success' => false,
            'errors' => ['El usuario o email ya están registrados']
        ];
    }
    
    // Crear usuario
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO users (username, email, password) VALUES (?, ?, ?) RETURNING id');
    
    try {
        $stmt->execute([$username, $email, $password_hash]);
        $user_id = $stmt->fetchColumn();
        
        return [
            'success' => true,
            'user_id' => $user_id,
            'username' => $username
        ];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'errors' => ['Error al crear el usuario: ' . $e->getMessage()]
        ];
    }
}

/**
 * Verificar si un usuario o email ya existen
 */
function userExists($pdo, $username, $email) {
    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
    $stmt->execute([$username, $email]);
    return $stmt->fetch() !== false;
}

/**
 * Obtener usuario por ID
 */
function getUserById($pdo, $user_id) {
    $stmt = $pdo->prepare('SELECT id, username, email, created_at FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Obtener usuario por username
 */
function getUserByUsername($pdo, $username) {
    $stmt = $pdo->prepare('SELECT id, username, email, created_at FROM users WHERE username = ?');
    $stmt->execute([$username]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Actualizar perfil de usuario
 */
function updateUserProfile($pdo, $user_id, $data) {
    $fields = [];
    $params = [];
    
    if (isset($data['email'])) {
        $fields[] = 'email = ?';
        $params[] = $data['email'];
    }
    
    if (isset($data['username'])) {
        $fields[] = 'username = ?';
        $params[] = $data['username'];
    }
    
    if (empty($fields)) {
        return false;
    }
    
    $params[] = $user_id;
    $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?';
    
    $stmt = $pdo->prepare($sql);
    return $stmt->execute($params);
}

/**
 * Cambiar contraseña
 */
function changePassword($pdo, $user_id, $old_password, $new_password) {
    // Verificar contraseña actual
    $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user || !password_verify($old_password, $user['password'])) {
        return [
            'success' => false,
            'error' => 'Contraseña actual incorrecta'
        ];
    }
    
    // Validar nueva contraseña
    if (strlen($new_password) < 6) {
        return [
            'success' => false,
            'error' => 'La nueva contraseña debe tener al menos 6 caracteres'
        ];
    }
    
    // Actualizar contraseña
    $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
    $stmt->execute([$password_hash, $user_id]);
    
    return [
        'success' => true
    ];
}

/**
 * Obtener todos los usuarios (para ranking)
 */
function getAllUsers($pdo) {
    $stmt = $pdo->query('SELECT id, username, created_at FROM users ORDER BY created_at DESC');
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Contar usuarios totales
 */
function countUsers($pdo) {
    $stmt = $pdo->query('SELECT COUNT(*) FROM users');
    return $stmt->fetchColumn();
}

/**
 * Verificar si el usuario es administrador
 */
function isAdmin($pdo, $user_id) {
    $stmt = $pdo->prepare('SELECT is_admin FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    return $user && $user['is_admin'];
}
