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
  <link rel="stylesheet" href="../assets/style.css">
  <meta name="theme-color" content="#1e2139">
  <style>
    body {
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      padding: 20px;
    }
    .register-container {
      max-width: 500px;
      width: 100%;
      background: var(--bg-card);
      border-radius: var(--radius-lg);
      box-shadow: var(--shadow-lg), var(--shadow-glow);
      border: 2px solid var(--border-color);
      padding: 40px;
      animation: fadeIn 0.5s ease-out;
    }
    .register-header {
      text-align: center;
      margin-bottom: 32px;
    }
    .register-header h1 {
      font-size: 2rem;
      margin-bottom: 8px;
    }
    .register-header p {
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
    .success-message {
      background: linear-gradient(135deg, var(--accent-green), #059669);
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
      margin-bottom: 18px;
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
    .btn-register {
      width: 100%;
      padding: 14px;
      font-size: 1rem;
      font-weight: 700;
      margin-bottom: 16px;
    }
    .register-footer {
      text-align: center;
      margin-top: 24px;
      padding-top: 24px;
      border-top: 1px solid var(--border-color);
    }
    .register-footer a {
      color: var(--accent-blue);
      text-decoration: none;
      font-weight: 600;
      transition: color 0.3s ease;
    }
    .register-footer a:hover {
      color: var(--accent-purple);
      text-decoration: underline;
    }
    .password-hint {
      font-size: 0.8rem;
      color: var(--text-muted);
      margin-top: 4px;
    }
    @media (max-width: 480px) {
      .register-container {
        padding: 24px;
      }
      .register-header h1 {
        font-size: 1.6rem;
      }
    }
  </style>
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
