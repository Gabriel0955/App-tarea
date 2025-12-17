<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../services/ProjectService.php';
require_once __DIR__ . '/../../src/db.php';
$pdo = get_pdo();
$projectService = new ProjectService($pdo);
$userId = $_SESSION['user_id'];

// Si es POST, crear el proyecto
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $projectService->createProject($userId, $_POST);
    
    if ($result['success']) {
        header('Location: project_view.php?id=' . $result['id']);
        exit;
    } else {
        $error = $result['error'] ?? 'Error al crear el proyecto';
    }
}

$pageTitle = "Nuevo Proyecto";
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">
  <title><?php echo $pageTitle; ?> | App-Tareas</title>
  
  <!-- PWA Meta Tags -->
  <link rel="manifest" href="../pwa/manifest.json">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="App-Tareas">
  <meta name="application-name" content="App-Tareas">
  <meta name="msapplication-TileColor" content="#1e2139">
  <meta name="msapplication-tap-highlight" content="no">
  
  <!-- Theme Colors -->
  <meta name="theme-color" content="#1e2139">
  <meta name="msapplication-navbutton-color" content="#1e2139">
  
  <!-- iOS Icons -->
  <link rel="apple-touch-icon" href="../../assets/icon-152x152.png">
  <link rel="apple-touch-icon" sizes="72x72" href="../../assets/icon-72x72.png">
  <link rel="apple-touch-icon" sizes="96x96" href="../../assets/icon-96x96.png">
  <link rel="apple-touch-icon" sizes="128x128" href="../../assets/icon-128x128.png">
  <link rel="apple-touch-icon" sizes="144x144" href="../../assets/icon-144x144.png">
  <link rel="apple-touch-icon" sizes="152x152" href="../../assets/icon-152x152.png">
  <link rel="apple-touch-icon" sizes="192x192" href="../../assets/icon-192x192.png">
  <link rel="apple-touch-icon" sizes="384x384" href="../../assets/icon-384x384.png">
  <link rel="apple-touch-icon" sizes="512x512" href="../../assets/icon-512x512.png">
  
  <!-- Favicon -->
  <link rel="icon" type="image/png" sizes="32x32" href="../../assets/icon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="../../assets/icon-16x16.png">
  <link rel="shortcut icon" href="../../assets/favicon.ico">
  
  <link rel="stylesheet" href="../../assets/style.css">
  <meta name="description" content="Crear nuevo proyecto">
</head>
<body>
<div class="container" style="max-width: 800px;">
    <div style="margin-bottom: 24px;">
        <h1>📂 Crear Nuevo Proyecto</h1>
        <p class="subtitle" style="color: var(--text-secondary); font-size: 1rem; margin-top: -8px; margin-bottom: 0;">
            Crea un proyecto para organizar tus tareas y hacer seguimiento del progreso
        </p>
    </div>
    
    <?php if (isset($error)): ?>
        <div style="background: var(--bg-card-hover); color: var(--accent-red); padding: 12px; border-radius: var(--radius-md); margin-bottom: 20px; border: 1px solid var(--accent-red);">
            ⚠️ <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <form method="POST" action="">
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 500;">Nombre del Proyecto *</label>
                <input 
                    type="text" 
                    name="name" 
                    required 
                    placeholder="Ej: Sistema de Ventas, App Móvil, Migración BD..."
                    value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                    style="width: 100%; padding: 12px; background: var(--bg-input); color: var(--text-primary); border: 1px solid var(--border-color); border-radius: var(--radius-sm);">
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 500;">Descripción</label>
                <textarea 
                    name="description" 
                    placeholder="Describe el objetivo y alcance del proyecto..."
                    style="width: 100%; padding: 12px; background: var(--bg-input); color: var(--text-primary); border: 1px solid var(--border-color); border-radius: var(--radius-sm); min-height: 100px; resize: vertical;"
                ><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                <div style="font-size: 12px; color: var(--text-secondary); margin-top: 4px;">Opcional: Agrega detalles sobre qué planeas lograr</div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: 500;">Estado</label>
                    <select name="status" style="width: 100%; padding: 12px; background: var(--bg-input); color: var(--text-primary); border: 1px solid var(--border-color); border-radius: var(--radius-sm);">
                        <option value="En Desarrollo" selected>En Desarrollo</option>
                        <option value="En Pruebas">En Pruebas</option>
                        <option value="En Producción">En Producción</option>
                        <option value="Cancelado">Cancelado</option>
                    </select>
                </div>
                
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: 500;">Prioridad</label>
                    <select name="priority" style="width: 100%; padding: 12px; background: var(--bg-input); color: var(--text-primary); border: 1px solid var(--border-color); border-radius: var(--radius-sm);">
                        <option value="Alta">🔴 Alta</option>
                        <option value="Media" selected>🟡 Media</option>
                        <option value="Baja">🟢 Baja</option>
                    </select>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: 500;">Fecha de Inicio</label>
                    <input 
                        type="date" 
                        name="start_date" 
                        value="<?php echo $_POST['start_date'] ?? date('Y-m-d'); ?>"
                        style="width: 100%; padding: 12px; background: var(--bg-input); color: var(--text-primary); border: 1px solid var(--border-color); border-radius: var(--radius-sm);">
                </div>
                
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: 500;">Fecha Objetivo</label>
                    <input 
                        type="date" 
                        name="target_date"
                        value="<?php echo $_POST['target_date'] ?? ''; ?>"
                        style="width: 100%; padding: 12px; background: var(--bg-input); color: var(--text-primary); border: 1px solid var(--border-color); border-radius: var(--radius-sm);">
                    <div style="font-size: 12px; color: var(--text-secondary); margin-top: 4px;">Opcional: Fecha límite para completar el proyecto</div>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: 500;">Categoría</label>
                    <input 
                        type="text" 
                        name="category" 
                        placeholder="Ej: Backend, Frontend, DevOps..."
                        value="<?php echo htmlspecialchars($_POST['category'] ?? ''); ?>"
                        style="width: 100%; padding: 12px; background: var(--bg-input); color: var(--text-primary); border: 1px solid var(--border-color); border-radius: var(--radius-sm);">
                </div>
                
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: 500;">URL del Repositorio</label>
                    <input 
                        type="url" 
                        name="repository_url" 
                        placeholder="https://github.com/..."
                        value="<?php echo htmlspecialchars($_POST['repository_url'] ?? ''); ?>"
                        style="width: 100%; padding: 12px; background: var(--bg-input); color: var(--text-primary); border: 1px solid var(--border-color); border-radius: var(--radius-sm);">
                </div>
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 500;">Notas Adicionales</label>
                <textarea 
                    name="notes" 
                    placeholder="Tecnologías, dependencias, consideraciones especiales..."
                    style="width: 100%; padding: 12px; background: var(--bg-input); color: var(--text-primary); border: 1px solid var(--border-color); border-radius: var(--radius-sm); min-height: 80px; resize: vertical;"
                ><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
            </div>
            
            <div style="display: flex; gap: 12px; margin-top: 24px; justify-content: flex-end;">
                <a href="projects.php" class="btn" style="text-decoration: none;">Cancelar</a>
                <button type="submit" class="btn" style="background: var(--accent-blue);">✨ Crear Proyecto</button>
            </div>
        </form>
    </div>
    
    <div style="margin-top: 24px; padding: 16px; background: var(--bg-card-hover); border-radius: var(--radius-md); color: var(--text-secondary); border: 1px solid var(--border-color);">
        <strong style="color: var(--accent-blue);">💡 Tip:</strong> Después de crear el proyecto, podrás agregar tareas específicas y hacer seguimiento del progreso diario.
    </div>
</div>
</body>
</html>
