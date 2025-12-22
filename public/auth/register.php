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
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    
    // Validación de confirmación de contraseña
    if ($password !== $password_confirm) {
        $error = 'Las contraseñas no coinciden';
    } elseif ($username === '' || $email === '' || $password === '') {
        $error = 'Por favor completa todos los campos';
    } else {
        $pdo = get_pdo();
        $result = registerUser($pdo, $username, $email, $password);
        
        if ($result['success']) {
            $success = 'Cuenta creada exitosamente. Redirigiendo...';
            
            // Auto-login
            $_SESSION['user_id'] = $result['user_id'];
            $_SESSION['username'] = $result['username'];
            
            // Redirigir después de 2 segundos
            header('Refresh: 2; url=../index.php');
        } else {
            $error = implode(', ', $result['errors']);
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
  <title>Crear Cuenta | App-Tareas</title>
  <link rel="stylesheet" href="../../assets/style.css">
  <link rel="stylesheet" href="../../assets/css/pages/auth.css">
  <meta name="theme-color" content="#1e2139">
</head>
<body>
<div class="register-container">
  <div class="register-header">
    <h1>⚡ App-Tareas</h1>
    <p>Crear Cuenta Nueva</p>
  </div>
  
  <?php if ($error): ?>
    <div class="error-message">⚠️ <?= esc($error) ?></div>
  <?php endif; ?>
  
  <?php if ($success): ?>
    <div class="success-message">✅ <?= esc($success) ?></div>
  <?php endif; ?>
  
  <form method="post" action="register.php">
    <div class="form-group">
      <label>Nombre de Usuario</label>
      <input type="text" name="username" required autofocus placeholder="Ej: juan123" value="<?= esc($_POST['username'] ?? '') ?>" minlength="3">
      <div class="password-hint">Mínimo 3 caracteres</div>
    </div>
    
    <div class="form-group">
      <label>Email</label>
      <input type="email" name="email" required placeholder="tu@email.com" value="<?= esc($_POST['email'] ?? '') ?>">
    </div>
    
    <div class="form-group">
      <label>Contraseña</label>
      <input type="password" name="password" required placeholder="Mínimo 6 caracteres" minlength="6">
      <div class="password-hint">Mínimo 6 caracteres</div>
    </div>
    
    <div class="form-group">
      <label>Confirmar Contraseña</label>
      <input type="password" name="password_confirm" required placeholder="Repite tu contraseña" minlength="6">
    </div>
    
    <button class="btn btn-register" type="submit">✅ Crear Cuenta</button>
  </form>
  
  <div class="register-footer">
    <p style="color: var(--text-secondary); margin-bottom: 12px;">¿Ya tienes cuenta?</p>
    <a href="login.php">🔓 Iniciar Sesión</a>
  </div>
</div>
</body>
</html>
