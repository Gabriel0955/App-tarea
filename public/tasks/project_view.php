<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../services/ProjectService.php';
require_once __DIR__ . '/../../src/db.php';
$pdo = get_pdo();
$projectService = new ProjectService($pdo);
$userId = $_SESSION['user_id'];

$projectId = intval($_GET['id'] ?? 0);

if ($projectId <= 0) {
    header('Location: projects.php');
    exit;
}

$project = $projectService->getProjectById($projectId, $userId);

if (!$project) {
    header('Location: projects.php?error=project_not_found');
    exit;
}

$tasks = $projectService->getProjectTasks($projectId, $userId);
$stats = $projectService->getProjectStats($projectId);

function esc($s) { 
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); 
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc($project['name']); ?> - Tareas</title>
    <link rel="stylesheet" href="../../assets/style.css">
    <style>
        .project-view-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .project-header {
            background: <?php echo esc($project['color']); ?>;
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .project-icon-large {
            font-size: 64px;
            background: rgba(255,255,255,0.2);
            width: 100px;
            height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 20px;
        }
        
        .project-header-info {
            flex: 1;
        }
        
        .project-title {
            font-size: 32px;
            margin: 0 0 8px 0;
        }
        
        .project-description-header {
            opacity: 0.9;
            font-size: 16px;
            margin: 0;
        }
        
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card-large {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-value-large {
            font-size: 36px;
            font-weight: bold;
            color: <?php echo esc($project['color']); ?>;
        }
        
        .stat-label-large {
            font-size: 14px;
            color: #666;
            margin-top: 8px;
        }
        
        .progress-section {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .progress-bar-large {
            width: 100%;
            height: 20px;
            background: #e0e0e0;
            border-radius: 10px;
            overflow: hidden;
            margin: 10px 0;
        }
        
        .progress-fill-large {
            height: 100%;
            background: <?php echo esc($project['color']); ?>;
            transition: width 0.3s;
        }
        
        .tasks-section {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .task-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .task-item:last-child {
            border-bottom: none;
        }
        
        .task-item.completed {
            opacity: 0.6;
        }
        
        .task-checkbox {
            width: 24px;
            height: 24px;
        }
        
        .task-content {
            flex: 1;
        }
        
        .task-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 4px;
        }
        
        .task-title.completed {
            text-decoration: line-through;
            color: #999;
        }
        
        .task-meta {
            font-size: 12px;
            color: #666;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            margin-right: 8px;
        }
        
        .badge.alta {
            background: #ff4757;
            color: white;
        }
        
        .badge.media {
            background: #ffa502;
            color: white;
        }
        
        .badge.baja {
            background: #2ed573;
            color: white;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .empty-icon {
            font-size: 64px;
            margin-bottom: 16px;
        }
    </style>
</head>
<body>
    <div class="project-view-container">
        <!-- Header del Proyecto -->
        <div class="project-header">
            <div class="project-icon-large"><?php echo esc($project['icon']); ?></div>
            <div class="project-header-info">
                <h1 class="project-title"><?php echo esc($project['name']); ?></h1>
                <?php if ($project['description']): ?>
                    <p class="project-description-header"><?php echo esc($project['description']); ?></p>
                <?php endif; ?>
            </div>
            <a href="projects.php" style="background: rgba(255,255,255,0.2); color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 600;">
                ← Volver
            </a>
        </div>
        
        <!-- Estadísticas -->
        <div class="stats-row">
            <div class="stat-card-large">
                <div class="stat-value-large"><?php echo $stats['total_tasks']; ?></div>
                <div class="stat-label-large">Total de Tareas</div>
            </div>
            <div class="stat-card-large">
                <div class="stat-value-large" style="color: #4caf50;"><?php echo $stats['completed_tasks']; ?></div>
                <div class="stat-label-large">Completadas</div>
            </div>
            <div class="stat-card-large">
                <div class="stat-value-large" style="color: #ff9800;"><?php echo $stats['pending_tasks']; ?></div>
                <div class="stat-label-large">Pendientes</div>
            </div>
            <div class="stat-card-large">
                <div class="stat-value-large"><?php echo number_format($stats['completion_percentage'], 1); ?>%</div>
                <div class="stat-label-large">Progreso</div>
            </div>
        </div>
        
        <!-- Barra de Progreso -->
        <div class="progress-section">
            <h3 style="margin: 0 0 10px 0;">Progreso General</h3>
            <div class="progress-bar-large">
                <div class="progress-fill-large" style="width: <?php echo $stats['completion_percentage']; ?>%;"></div>
            </div>
            <p style="text-align: center; color: #666; margin: 10px 0 0 0;">
                <?php echo $stats['completed_tasks']; ?> de <?php echo $stats['total_tasks']; ?> tareas completadas
            </p>
        </div>
        
        <!-- Botón para agregar tarea -->
        <div style="margin-bottom: 20px;">
            <a href="../index.php?project=<?php echo $projectId; ?>" 
               style="background: <?php echo esc($project['color']); ?>; color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; display: inline-block; font-weight: 600;">
                ➕ Nueva Tarea en este Proyecto
            </a>
        </div>
        
        <!-- Lista de Tareas -->
        <div class="tasks-section">
            <h3 style="margin: 0 0 20px 0;">📋 Tareas del Proyecto</h3>
            
            <?php if (empty($tasks)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📝</div>
                    <h3>No hay tareas en este proyecto</h3>
                    <p>Crea la primera tarea para empezar a trabajar</p>
                </div>
            <?php else: ?>
                <?php foreach ($tasks as $task): ?>
                    <div class="task-item <?php echo $task['deployed'] ? 'completed' : ''; ?>">
                        <input type="checkbox" class="task-checkbox" 
                               <?php echo $task['deployed'] ? 'checked' : ''; ?> 
                               disabled>
                        
                        <div class="task-content">
                            <div class="task-title <?php echo $task['deployed'] ? 'completed' : ''; ?>">
                                <?php echo esc($task['title']); ?>
                            </div>
                            <div class="task-meta">
                                <?php if ($task['urgency']): ?>
                                    <span class="badge <?php echo strtolower($task['urgency']); ?>">
                                        <?php echo esc($task['urgency']); ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($task['category']): ?>
                                    <span><?php echo esc($task['category']); ?></span>
                                <?php endif; ?>
                                <?php if ($task['due_date']): ?>
                                    <span>• Vence: <?php echo esc($task['due_date']); ?></span>
                                <?php endif; ?>
                                <?php if ($task['deployed']): ?>
                                    <span style="color: #4caf50;">• ✓ Completada</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div>
                            <a href="../tasks/edit.php?id=<?php echo $task['id']; ?>" 
                               style="background: #f5f5f5; padding: 8px 16px; border-radius: 6px; text-decoration: none; color: #333;">
                                ✏️ Editar
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
