<?php
// Configuración de base de datos PostgreSQL
// En Azure, estas variables se configuran en "Configuración de la aplicación"

define('DB_HOST', 'apptarea.postgres.database.azure.com');
define('DB_NAME', 'postgres');
define('DB_USER', 'apptarea');
define('DB_PASS', 'Gabriel1405');
define('DB_PORT',  5432);

// Modo debug (desactivar en producción)
define('APP_DEBUG', getenv('APP_DEBUG') ?: false);

// Zona horaria
date_default_timezone_set('America/Lima');

?>
