<?php
session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../services/UserService.php';

// Si ya está logueado, redirigir a index
if (isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if ($username === '' || $password === '') {
        $error = 'Por favor completa todos los campos';
    } else {
        $pdo = get_pdo();
        $result = authenticateUser($pdo, $username, $password);
        
        if ($result['success']) {
            // Login exitoso
            $_SESSION['user_id'] = $result['user']['id'];
            $_SESSION['username'] = $result['user']['username'];
            header('Location: ../index.php');
            exit;
        } else {
            $error = $result['error'];
        }
    }
}

function esc($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">
  <title>Iniciar Sesión | App-Tareas</title>
  <link rel="stylesheet" href="../../assets/style.css">
  <link rel="stylesheet" href="../../assets/css/pages/auth.css">
  <meta name="theme-color" content="#1e2139">
</head>
<body>
<div class="login-container">
  <div class="login-header">
    <h1>⚡ App-Tareas</h1>
    <p>Iniciar Sesión</p>
  </div>
  
  <?php if ($error): ?>
    <div class="error-message">⚠️ <?= esc($error) ?></div>
  <?php endif; ?>
  
  <form method="post" action="login.php">
    <div class="form-group">
      <label>Usuario o Email</label>
      <input type="text" name="username" required autofocus placeholder="Ingresa tu usuario o email" value="<?= esc($_POST['username'] ?? '') ?>">
    </div>
    
    <div class="form-group">
      <label>Contraseña</label>
      <input type="password" name="password" required placeholder="Ingresa tu contraseña">
    </div>
    
    <button class="btn btn-login" type="submit">🔓 Iniciar Sesión</button>
  </form>
  
  <div class="login-footer">
    <p style="color: var(--text-secondary); margin-bottom: 12px;">¿No tienes cuenta?</p>
    <a href="register.php">➕ Crear una cuenta nueva</a>
  </div>
</div>
</body>
</html>
