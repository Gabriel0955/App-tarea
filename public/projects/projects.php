<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../services/ProjectService.php';
require_once __DIR__ . '/../../src/db.php';
$pdo = get_pdo();
$projectService = new ProjectService($pdo);
$userId = $_SESSION['user_id'];

// Filtro por estado
$statusFilter = $_GET['status'] ?? null;

// Obtener proyectos
$projects = $projectService->getUserProjects($userId, $statusFilter);

$pageTitle = "Mis Proyectos";
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
  <meta name="description" content="Gestión de proyectos y tareas">
</head>
<body>
<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1>📂 Mis Proyectos</h1>
            <p class="subtitle" style="color: var(--text-secondary); font-size: 1rem; margin-top: -8px; margin-bottom: 0;">
                Gestiona tus proyectos y completa tareas cada día
            </p>
        </div>
        <div style="display: flex; gap: 12px;">
            <a href="../index.php" class="btn" style="text-decoration: none;">← Dashboard</a>
            <button class="btn" style="background: var(--accent-blue);" onclick="window.location.href='project_create.php'">+ Nuevo Proyecto</button>
        </div>
    </div>
    
    <!-- Filtros -->
    <div class="top-actions" style="margin-bottom: 24px;">
        <a href="?" class="btn <?php echo !$statusFilter ? 'active' : ''; ?>">Todos</a>
        <a href="?status=En Desarrollo" class="btn <?php echo $statusFilter === 'En Desarrollo' ? 'active' : ''; ?>">En Desarrollo</a>
        <a href="?status=En Pruebas" class="btn <?php echo $statusFilter === 'En Pruebas' ? 'active' : ''; ?>">En Pruebas</a>
        <a href="?status=En Producción" class="btn <?php echo $statusFilter === 'En Producción' ? 'active' : ''; ?>">En Producción</a>
    </div>
    
    <!-- Grid de Proyectos -->
    <?php if (empty($projects)): ?>
        <div style="text-align: center; padding: 60px 20px; color: var(--text-secondary);">
            <div style="font-size: 64px; margin-bottom: 16px;">📂</div>
            <h3>No hay proyectos</h3>
            <p>Crea tu primer proyecto para empezar a organizar tus tareas</p>
            <button class="btn" style="background: var(--accent-blue); margin-top: 16px;" onclick="window.location.href='project_create.php'">
                + Crear Primer Proyecto
            </button>
        </div>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 24px; margin-bottom: 24px;">
            <?php foreach ($projects as $project): ?>
                <div class="card" onclick="window.location.href='project_view.php?id=<?php echo $project['id']; ?>'" style="cursor: pointer; transition: transform 0.2s;">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 12px;">
                        <h3 style="margin: 0; font-size: 18px;"><?php echo htmlspecialchars($project['name']); ?></h3>
                        <span class="badge <?php echo 'badge-' . strtolower(str_replace(' ', '', $project['status'])); ?>">
                            <?php echo $project['status']; ?>
                        </span>
                    </div>
                    
                    <?php if ($project['description']): ?>
                        <p style="color: var(--text-secondary); font-size: 14px; margin-bottom: 16px; line-height: 1.5;">
                            <?php echo htmlspecialchars(substr($project['description'], 0, 120)); ?>
                            <?php echo strlen($project['description']) > 120 ? '...' : ''; ?>
                        </p>
                    <?php endif; ?>
                    
                    <div style="margin-bottom: 12px;">
                        <div class="progress-bar" style="margin-bottom: 8px;">
                            <div class="progress-bar-fill" style="width: <?php echo $project['progress_percentage']; ?>%"></div>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 12px; color: var(--text-secondary);">
                            <span><?php echo $project['completed_tasks']; ?> / <?php echo $project['total_tasks']; ?> tareas</span>
                            <span><?php echo number_format($project['progress_percentage'], 1); ?>%</span>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 16px; flex-wrap: wrap; margin-bottom: 12px; font-size: 13px; color: var(--text-secondary);">
                        <div style="display: flex; align-items: center; gap: 4px;">
                            <span>🎯</span>
                            <span class="badge priority-<?php echo strtolower($project['priority']); ?>">
                                <?php echo $project['priority']; ?>
                            </span>
                        </div>
                        
                        <?php if ($project['target_date']): ?>
                            <div style="display: flex; align-items: center; gap: 4px;">
                                <span>📅</span>
                                <span><?php echo date('d/m/Y', strtotime($project['target_date'])); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($project['category']): ?>
                            <div style="display: flex; align-items: center; gap: 4px;">
                                <span>🏷️</span>
                                <span><?php echo htmlspecialchars($project['category']); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div style="display: flex; gap: 8px; margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--border-color);" onclick="event.stopPropagation()">
                        <button class="btn" style="flex: 1; background: var(--accent-blue);" onclick="window.location.href='project_view.php?id=<?php echo $project['id']; ?>'">
                            Ver Detalles
                        </button>
                        <button class="btn red" style="flex: 1;" onclick="deleteProject(<?php echo $project['id']; ?>)">
                            Eliminar
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
    
    <script>
        function deleteProject(projectId) {
            if (!confirm('¿Estás seguro de eliminar este proyecto? Se eliminarán todas sus tareas.')) {
                return;
            }
            
            fetch('project_api.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=delete&project_id=${projectId}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + (data.error || 'No se pudo eliminar'));
                }
            })
            .catch(err => {
                console.error(err);
                alert('Error al eliminar el proyecto');
            });
        }
    </script>
</body>
</html>
