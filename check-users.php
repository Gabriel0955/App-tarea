<?php
require_once 'src/db.php';

try {
    $pdo = get_pdo();
    
    // Verificar si existe la columna role_id
    $stmt = $pdo->query("
        SELECT column_name 
        FROM information_schema.columns 
        WHERE table_name = 'users' 
        AND column_name = 'role_id'
    ");
    
    if ($stmt->rowCount() === 0) {
        echo "❌ La columna role_id NO existe en la tabla users\n";
        echo "Necesitas ejecutar: db/add_roles.sql\n";
        exit;
    }
    
    echo "✅ La columna role_id existe\n\n";
    
    // Contar usuarios sin rol
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role_id IS NULL");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Usuarios sin rol asignado: " . $result['total'] . "\n\n";
    
    // Listar usuarios
    $stmt = $pdo->query("
        SELECT u.id, u.username, u.email, r.name as role_name, r.id as role_id
        FROM users u
        LEFT JOIN roles r ON u.role_id = r.id
        ORDER BY u.id
    ");
    
    echo "Lista de usuarios:\n";
    echo str_repeat("-", 80) . "\n";
    printf("%-5s %-20s %-30s %-15s %-5s\n", "ID", "Usuario", "Email", "Rol", "Nivel");
    echo str_repeat("-", 80) . "\n";
    
    while ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
        printf(
            "%-5s %-20s %-30s %-15s %-5s\n",
            $user['id'],
            $user['username'],
            $user['email'],
            $user['role_name'] ?? 'SIN ROL',
            $user['role_id'] ?? '-'
        );
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
