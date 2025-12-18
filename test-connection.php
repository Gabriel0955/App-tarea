<?php
// Script de prueba de conexión a PostgreSQL Azure
require_once __DIR__ . '/config.php';

echo "🔍 Verificando conexión a PostgreSQL Azure...\n\n";

echo "📋 Configuración:\n";
echo "Host: " . DB_HOST . "\n";
echo "Database: " . DB_NAME . "\n";
echo "User: " . DB_USER . "\n";
echo "Port: " . DB_PORT . "\n\n";

try {
    $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";sslmode=require";
    
    echo "🔌 Intentando conectar...\n";
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
    
    echo "✅ ¡CONEXIÓN EXITOSA!\n\n";
    
    // Probar una consulta
    echo "🧪 Ejecutando consulta de prueba...\n";
    $stmt = $pdo->query("SELECT version()");
    $version = $stmt->fetchColumn();
    
    echo "✅ PostgreSQL versión: " . $version . "\n\n";
    
    // Verificar tablas
    echo "📊 Verificando tablas...\n";
    $stmt = $pdo->query("
        SELECT table_name 
        FROM information_schema.tables 
        WHERE table_schema = 'public' 
        ORDER BY table_name
    ");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($tables) > 0) {
        echo "✅ Tablas encontradas (" . count($tables) . "):\n";
        foreach ($tables as $table) {
            echo "   - " . $table . "\n";
        }
    } else {
        echo "⚠️  No se encontraron tablas. La base de datos está vacía.\n";
    }
    
    // Verificar extensión SSL
    echo "\n🔒 Verificando SSL...\n";
    $stmt = $pdo->query("SHOW ssl");
    $ssl = $stmt->fetchColumn();
    echo "SSL: " . ($ssl === 'on' ? '✅ Habilitado' : '❌ Deshabilitado') . "\n";
    
} catch (PDOException $e) {
    echo "❌ ERROR DE CONEXIÓN:\n";
    echo "Código: " . $e->getCode() . "\n";
    echo "Mensaje: " . $e->getMessage() . "\n\n";
    
    echo "💡 SOLUCIONES POSIBLES:\n";
    echo "1. Verifica que el firewall de Azure permita tu IP\n";
    echo "2. Verifica que las credenciales sean correctas\n";
    echo "3. Verifica que el servidor PostgreSQL esté activo en Azure\n";
    echo "4. Verifica que tu PHP tenga la extensión pdo_pgsql habilitada\n";
    
    // Verificar extensión PHP
    echo "\n🔍 Verificando extensiones PHP:\n";
    echo "PDO: " . (extension_loaded('pdo') ? '✅' : '❌') . "\n";
    echo "PDO_PGSQL: " . (extension_loaded('pdo_pgsql') ? '✅' : '❌') . "\n";
}
