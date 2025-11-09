<?php
// Configuración de base de datos
// En Azure, estas variables se configuran en "Configuración de la aplicación"

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'tasks_app');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_PORT', getenv('DB_PORT') ?: 3306);

// Modo debug (desactivar en producción)
define('APP_DEBUG', getenv('APP_DEBUG') ?: false);

// Zona horaria
date_default_timezone_set('America/Lima');

?>
