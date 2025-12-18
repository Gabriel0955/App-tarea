<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Conexión PostgreSQL</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #0f1117;
            color: #e4e4e7;
            padding: 40px;
            max-width: 900px;
            margin: 0 auto;
        }
        .box {
            background: #1e2139;
            border: 2px solid #2d3250;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
        }
        .success { color: #10b981; }
        .error { color: #ef4444; }
        .warning { color: #fbbf24; }
        .info { color: #00b4d8; }
        h1 { color: #00d4ff; }
        h2 { color: #a855f7; margin-top: 0; }
        pre {
            background: #0f1117;
            padding: 16px;
            border-radius: 8px;
            overflow-x: auto;
            border: 1px solid #2d3250;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 600;
            margin: 4px;
        }
        .badge-success { background: #10b981; color: white; }
        .badge-error { background: #ef4444; color: white; }
    </style>
</head>
<body>
    <h1>🔍 Test de Conexión PostgreSQL Azure</h1>
    
    <div class="box">
        <h2>📋 Información de PHP</h2>
        <?php
        echo "<strong>Versión PHP:</strong> " . PHP_VERSION . "<br>";
        echo "<strong>php.ini:</strong> " . php_ini_loaded_file() . "<br>";
        echo "<strong>SAPI:</strong> " . php_sapi_name() . "<br>";
        ?>
    </div>

    <div class="box">
        <h2>🔌 Extensiones PHP</h2>
        <?php
        $extensions = ['pdo', 'pdo_pgsql', 'pgsql', 'openssl'];
        foreach ($extensions as $ext) {
            $loaded = extension_loaded($ext);
            $class = $loaded ? 'badge-success' : 'badge-error';
            $icon = $loaded ? '✅' : '❌';
            echo "<span class='badge $class'>$icon $ext</span>";
        }
        ?>
    </div>

    <div class="box">
        <h2>⚙️ Configuración de Conexión</h2>
        <?php
        require_once __DIR__ . '/config.php';
        echo "<pre>";
        echo "Host: " . DB_HOST . "\n";
        echo "Database: " . DB_NAME . "\n";
        echo "User: " . DB_USER . "\n";
        echo "Port: " . DB_PORT . "\n";
        echo "</pre>";
        ?>
    </div>

    <div class="box">
        <h2>🧪 Prueba de Conexión</h2>
        <?php
        try {
            require_once __DIR__ . '/src/db.php';
            
            echo "<p class='success'>✅ <strong>CONEXIÓN EXITOSA</strong></p>";
            
            $pdo = get_pdo();
            
            // Test query
            $stmt = $pdo->query("SELECT version()");
            $version = $stmt->fetchColumn();
            echo "<p class='info'>📊 PostgreSQL: " . htmlspecialchars($version) . "</p>";
            
            // Count tables
            $stmt = $pdo->query("
                SELECT COUNT(*) 
                FROM information_schema.tables 
                WHERE table_schema = 'public'
            ");
            $count = $stmt->fetchColumn();
            echo "<p class='info'>📋 Tablas en la BD: <strong>$count</strong></p>";
            
            // Check users
            $stmt = $pdo->query("SELECT COUNT(*) FROM users");
            $users = $stmt->fetchColumn();
            echo "<p class='info'>👥 Usuarios registrados: <strong>$users</strong></p>";
            
            // Check roles
            if ($pdo->query("SELECT to_regclass('public.roles')")->fetchColumn()) {
                $stmt = $pdo->query("SELECT COUNT(*) FROM roles");
                $roles = $stmt->fetchColumn();
                echo "<p class='info'>🎭 Roles configurados: <strong>$roles</strong></p>";
            }
            
            echo "<p class='success'>🎉 <strong>¡Todo funciona correctamente!</strong></p>";
            echo "<p><a href='public/index.php' style='color: #00d4ff;'>➡️ Ir a la aplicación</a></p>";
            
        } catch (PDOException $e) {
            echo "<p class='error'>❌ <strong>ERROR DE CONEXIÓN</strong></p>";
            echo "<pre class='error'>";
            echo "Código: " . $e->getCode() . "\n";
            echo "Mensaje: " . htmlspecialchars($e->getMessage()) . "\n";
            echo "</pre>";
            
            echo "<div class='box' style='background: rgba(239, 68, 68, 0.1); border-color: #ef4444;'>";
            echo "<h3 class='error'>💡 Soluciones:</h3>";
            echo "<ol>";
            echo "<li>Verifica que WAMP esté completamente reiniciado</li>";
            echo "<li>Verifica tu IP en el firewall de Azure PostgreSQL</li>";
            echo "<li>Revisa que las extensiones PHP estén habilitadas en php.ini</li>";
            echo "<li>Verifica las credenciales en config.php</li>";
            echo "</ol>";
            echo "</div>";
        } catch (Exception $e) {
            echo "<p class='error'>❌ <strong>ERROR GENERAL</strong></p>";
            echo "<pre class='error'>" . htmlspecialchars($e->getMessage()) . "</pre>";
        }
        ?>
    </div>

    <div class="box">
        <h2>🔧 Comandos útiles</h2>
        <p>Para verificar conexión desde terminal:</p>
        <pre>php test-connection.php</pre>
        
        <p>Para ver información de PHP:</p>
        <pre>php -i | findstr pgsql</pre>
    </div>
</body>
</html>
