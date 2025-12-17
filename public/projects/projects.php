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
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="../../assets/style.css">
    <style>
        .projects-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .filters {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            padding: 8px 16px;
            border: 2px solid #ddd;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .filter-btn.active {
            background: #1976d2;
            color: white;
            border-color: #1976d2;
        }
        
        .projects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .project-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
        }
        
        .project-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        }
        
        .project-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 12px;
        }
        
        .project-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin: 0;
        }
        
        .project-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-desarrollo {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .status-pruebas {
            background: #fff3e0;
            color: #f57c00;
        }
        
        .status-produccion {
            background: #e8f5e9;
            color: #388e3c;
        }
        
        .status-cancelado {
            background: #ffebee;
            color: #d32f2f;
        }
        
        .project-description {
            color: #666;
            font-size: 14px;
            margin-bottom: 16px;
            line-height: 1.5;
        }
        
        .project-progress {
            margin-bottom: 12px;
        }
        
        .progress-bar {
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 8px;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #1976d2 0%, #00b4d8 100%);
            transition: width 0.3s;
        }
        
        .progress-text {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: #666;
        }
        
        .project-meta {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 12px;
            font-size: 13px;
            color: #666;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .project-priority {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .priority-alta {
            background: #ffebee;
            color: #d32f2f;
        }
        
        .priority-media {
            background: #fff3e0;
            color: #f57c00;
        }
        
        .priority-baja {
            background: #e8f5e9;
            color: #388e3c;
        }
        
        .project-actions {
            display: flex;
            gap: 8px;
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid #eee;
        }
        
        .btn-action {
            flex: 1;
            padding: 8px;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-view {
            background: #1976d2;
            color: white;
        }
        
        .btn-view:hover {
            background: #1565c0;
        }
        
        .btn-delete {
            background: #ffebee;
            color: #d32f2f;
        }
        
        .btn-delete:hover {
            background: #d32f2f;
            color: white;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 16px;
        }
        
        .btn-primary {
            background: #1976d2;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .btn-primary:hover {
            background: #1565c0;
        }
        
        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }
        
        @media (max-width: 768px) {
            .projects-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="projects-container">
        <div class="header-actions">
            <div>
                <h1>📂 Mis Proyectos</h1>
                <p style="color: #666; margin: 8px 0 0 0;">Gestiona tus proyectos y completa tareas cada día</p>
            </div>
            <div style="display: flex; gap: 12px;">
                <a href="../index.php" class="btn-primary" style="text-decoration: none;">← Dashboard</a>
                <button class="btn-primary" onclick="window.location.href='project_create.php'">+ Nuevo Proyecto</button>
            </div>
        </div>
        
        <!-- Filtros -->
        <div class="filters">
            <a href="?" class="filter-btn <?php echo !$statusFilter ? 'active' : ''; ?>">Todos</a>
            <a href="?status=En Desarrollo" class="filter-btn <?php echo $statusFilter === 'En Desarrollo' ? 'active' : ''; ?>">En Desarrollo</a>
            <a href="?status=En Pruebas" class="filter-btn <?php echo $statusFilter === 'En Pruebas' ? 'active' : ''; ?>">En Pruebas</a>
            <a href="?status=En Producción" class="filter-btn <?php echo $statusFilter === 'En Producción' ? 'active' : ''; ?>">En Producción</a>
        </div>
        
        <!-- Grid de Proyectos -->
        <?php if (empty($projects)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">📂</div>
                <h3>No hay proyectos</h3>
                <p>Crea tu primer proyecto para empezar a organizar tus tareas</p>
                <button class="btn-primary" onclick="window.location.href='project_create.php'" style="margin-top: 16px;">
                    + Crear Primer Proyecto
                </button>
            </div>
        <?php else: ?>
            <div class="projects-grid">
                <?php foreach ($projects as $project): ?>
                    <div class="project-card" onclick="window.location.href='project_view.php?id=<?php echo $project['id']; ?>'">
                        <div class="project-header">
                            <h3 class="project-title"><?php echo htmlspecialchars($project['name']); ?></h3>
                            <span class="project-status status-<?php echo strtolower(str_replace(' ', '', $project['status'])); ?>">
                                <?php echo $project['status']; ?>
                            </span>
                        </div>
                        
                        <?php if ($project['description']): ?>
                            <p class="project-description">
                                <?php echo htmlspecialchars(substr($project['description'], 0, 120)); ?>
                                <?php echo strlen($project['description']) > 120 ? '...' : ''; ?>
                            </p>
                        <?php endif; ?>
                        
                        <div class="project-progress">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $project['progress_percentage']; ?>%"></div>
                            </div>
                            <div class="progress-text">
                                <span><?php echo $project['completed_tasks']; ?> / <?php echo $project['total_tasks']; ?> tareas</span>
                                <span><?php echo number_format($project['progress_percentage'], 1); ?>%</span>
                            </div>
                        </div>
                        
                        <div class="project-meta">
                            <div class="meta-item">
                                <span>🎯</span>
                                <span class="project-priority priority-<?php echo strtolower($project['priority']); ?>">
                                    <?php echo $project['priority']; ?>
                                </span>
                            </div>
                            
                            <?php if ($project['target_date']): ?>
                                <div class="meta-item">
                                    <span>📅</span>
                                    <span><?php echo date('d/m/Y', strtotime($project['target_date'])); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($project['category']): ?>
                                <div class="meta-item">
                                    <span>🏷️</span>
                                    <span><?php echo htmlspecialchars($project['category']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="project-actions" onclick="event.stopPropagation()">
                            <button class="btn-action btn-view" onclick="window.location.href='project_view.php?id=<?php echo $project['id']; ?>'">
                                Ver Detalles
                            </button>
                            <button class="btn-action btn-delete" onclick="deleteProject(<?php echo $project['id']; ?>)">
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
