<?php
// index.php - Punto de entrada principal
// Redirige a la aplicación en la carpeta public/

session_start();

// Si el usuario ya tiene sesión, ir directo a la app
if (isset($_SESSION['user_id'])) {
    header('Location: public/index.php');
    exit;
}

// Si no tiene sesión, ir al login
header('Location: public/login.php');
exit;
