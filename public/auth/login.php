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
  <meta name="theme-color" content="#1e2139">
  <style>
    body {
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      padding: 20px;
    }
    .login-container {
      max-width: 450px;
      width: 100%;
      background: var(--bg-card);
      border-radius: var(--radius-lg);
      box-shadow: var(--shadow-lg), var(--shadow-glow);
      border: 2px solid var(--border-color);
      padding: 40px;
      animation: fadeIn 0.5s ease-out;
    }
    .login-header {
      text-align: center;
      margin-bottom: 32px;
    }
    .login-header h1 {
      font-size: 2rem;
      margin-bottom: 8px;
    }
    .login-header p {
      color: var(--text-secondary);
      font-size: 0.95rem;
    }
    .error-message {
      background: linear-gradient(135deg, var(--accent-red), #dc2626);
      color: white;
      padding: 12px 16px;
      border-radius: var(--radius-sm);
      margin-bottom: 20px;
      font-weight: 600;
      font-size: 0.9rem;
      text-align: center;
      animation: slideIn 0.3s ease-out;
    }
    form {
      padding: 0;
      margin: 0;
      background: transparent;
      border: none;
    }
    .form-group {
      margin-bottom: 20px;
    }
    .form-group label {
      display: block;
      font-weight: 600;
      color: var(--text-primary);
      margin-bottom: 8px;
      font-size: 0.95rem;
    }
    .form-group input {
      width: 100%;
      padding: 14px 16px;
      background: var(--bg-input);
      border: 2px solid var(--border-color);
      border-radius: var(--radius-sm);
      color: var(--text-primary);
      font-size: 1rem;
      font-family: inherit;
      transition: all 0.3s ease;
      box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.2);
    }
    .form-group input:focus {
      outline: none;
      border-color: var(--accent-blue);
      background: var(--bg-input-focus);
      box-shadow: 0 0 0 3px rgba(0, 212, 255, 0.1), inset 0 2px 4px rgba(0, 0, 0, 0.2);
    }
    .btn-login {
      width: 100%;
      padding: 14px;
      font-size: 1rem;
      font-weight: 700;
      margin-bottom: 16px;
    }
    .login-footer {
      text-align: center;
      margin-top: 24px;
      padding-top: 24px;
      border-top: 1px solid var(--border-color);
    }
    .login-footer a {
      color: var(--accent-blue);
      text-decoration: none;
      font-weight: 600;
      transition: color 0.3s ease;
    }
    .login-footer a:hover {
      color: var(--accent-purple);
      text-decoration: underline;
    }
    @media (max-width: 480px) {
      .login-container {
        padding: 24px;
      }
      .login-header h1 {
        font-size: 1.6rem;
      }
    }
  </style>
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
