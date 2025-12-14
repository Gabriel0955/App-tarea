<?php
// Copia este archivo a config.php y completa con tus credenciales locales.

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'tasks_app');
define('DB_USER', getenv('DB_USER') ?: 'postgres');
define('DB_PASS', getenv('DB_PASS') ?: 'postgres');
define('DB_PORT', getenv('DB_PORT') ?: 5432);

// Opcional: indica el modo debug
define('APP_DEBUG', true);

// No incluir credenciales reales en repositorios públicos.

?>
