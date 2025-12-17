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

// Obtener proyecto
$project = $projectService->getProjectById($projectId, $userId);

if (!$project) {
    header('Location: projects.php');
    exit;
}

// Obtener tareas del proyecto
$tasks = $projectService->getProjectTasks($projectId, $userId);

// Obtener estadísticas
$stats = $projectService->getProjectStats($projectId, $userId);

$pageTitle = $project['name'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="../../assets/style.css">
    <style>
        .project-view-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .project-header {
            background: white;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .project-title-row {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 16px;
            gap: 16px;
        }
        
        .project-meta {
            display: flex;
            gap: 24px;
            flex-wrap: wrap;
            margin-top: 16px;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: #666;
        }
        
        .progress-section {
            margin-top: 20px;
        }
        
        .progress-bar {
            height: 12px;
            background: #e0e0e0;
            border-radius: 6px;
            overflow: hidden;
            margin-bottom: 8px;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #1976d2 0%, #00b4d8 100%);
            transition: width 0.3s;
        }
        
        .tasks-section {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .task-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            margin-bottom: 12px;
            transition: all 0.2s;
        }
        
        .task-item:hover {
            border-color: #1976d2;
            box-shadow: 0 2px 8px rgba(25, 118, 210, 0.1);
        }
        
        .task-checkbox {
            width: 24px;
            height: 24px;
            cursor: pointer;
        }
        
        .task-content {
            flex: 1;
        }
        
        .task-title {
            font-weight: 500;
            color: #333;
            margin-bottom: 4px;
        }
        
        .task-title.completed {
            text-decoration: line-through;
            color: #999;
        }
        
        .task-meta-small {
            font-size: 12px;
            color: #666;
        }
        
        .task-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-pendiente {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .status-enprogreso {
            background: #fff3e0;
            color: #f57c00;
        }
        
        .status-completada {
            background: #e8f5e9;
            color: #388e3c;
        }
        
        .status-bloqueada {
            background: #ffebee;
            color: #d32f2f;
        }
        
        .btn-primary {
            background: #1976d2;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary:hover {
            background: #1565c0;
        }
        
        .btn-success {
            background: #388e3c;
            color: white;
        }
        
        .btn-success:hover {
            background: #2e7d32;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 16px;
            margin-top: 20px;
        }
        
        .stat-card {
            background: #f5f5f5;
            padding: 16px;
            border-radius: 8px;
            text-align: center;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #1976d2;
        }
        
        .stat-label {
            font-size: 12px;
            color: #666;
            margin-top: 4px;
        }
        
        @media (max-width: 768px) {
            .project-title-row {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="project-view-container">
        <!-- Navegación -->
        <div style="margin-bottom: 20px;">
            <a href="projects.php" class="btn-primary">← Volver a Proyectos</a>
        </div>
        
        <!-- Header del Proyecto -->
        <div class="project-header">
            <div class="project-title-row">
                <div>
                    <h1 style="margin: 0 0 8px 0;"><?php echo htmlspecialchars($project['name']); ?></h1>
                    <p style="color: #666; margin: 0;"><?php echo htmlspecialchars($project['description'] ?? ''); ?></p>
                </div>
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <span class="task-status status-<?php echo strtolower(str_replace(' ', '', $project['status'])); ?>">
                        <?php echo $project['status']; ?>
                    </span>
                    <?php if ($project['progress_percentage'] >= 100 && $project['status'] !== 'En Producción'): ?>
                        <button class="btn-primary btn-success" onclick="deployProject()">
                            🚀 Desplegar a Producción
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="project-meta">
                <div class="meta-item">
                    <span>🎯</span>
                    <span>Prioridad: <strong><?php echo $project['priority']; ?></strong></span>
                </div>
                <?php if ($project['category']): ?>
                    <div class="meta-item">
                        <span>🏷️</span>
                        <span><?php echo htmlspecialchars($project['category']); ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($project['target_date']): ?>
                    <div class="meta-item">
                        <span>📅</span>
                        <span>Objetivo: <?php echo date('d/m/Y', strtotime($project['target_date'])); ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($project['repository_url']): ?>
                    <div class="meta-item">
                        <span>📦</span>
                        <a href="<?php echo htmlspecialchars($project['repository_url']); ?>" target="_blank">Repositorio</a>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="progress-section">
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                    <span style="font-weight: 500;">Progreso del Proyecto</span>
                    <span style="color: #1976d2; font-weight: 600;"><?php echo number_format($project['progress_percentage'], 1); ?>%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo $project['progress_percentage']; ?>%"></div>
                </div>
                <div style="font-size: 12px; color: #666;">
                    <?php echo $project['completed_tasks']; ?> de <?php echo $project['total_tasks']; ?> tareas completadas
                </div>
            </div>
            
            <!-- Estadísticas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['total_tasks'] ?? 0; ?></div>
                    <div class="stat-label">Total Tareas</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['tasks_in_progress'] ?? 0; ?></div>
                    <div class="stat-label">En Progreso</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format($stats['total_estimated_hours'] ?? 0, 1); ?>h</div>
                    <div class="stat-label">Horas Estimadas</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format($stats['total_actual_hours'] ?? 0, 1); ?>h</div>
                    <div class="stat-label">Horas Reales</div>
                </div>
            </div>
        </div>
        
        <!-- Tareas -->
        <div class="tasks-section">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="margin: 0;">📋 Tareas del Proyecto</h2>
                <button class="btn-primary" onclick="showAddTaskModal()">+ Agregar Tarea</button>
            </div>
            
            <?php if (empty($tasks)): ?>
                <div class="empty-state">
                    <p style="font-size: 48px; margin: 0;">📝</p>
                    <h3>No hay tareas</h3>
                    <p>Agrega tu primera tarea para empezar a trabajar en este proyecto</p>
                    <button class="btn-primary" onclick="showAddTaskModal()" style="margin-top: 16px;">
                        + Crear Primera Tarea
                    </button>
                </div>
            <?php else: ?>
                <?php foreach ($tasks as $task): ?>
                    <div class="task-item">
                        <input 
                            type="checkbox" 
                            class="task-checkbox"
                            <?php echo $task['status'] === 'Completada' ? 'checked disabled' : ''; ?>
                            onchange="completeTask(<?php echo $task['id']; ?>)"
                        >
                        <div class="task-content">
                            <div class="task-title <?php echo $task['status'] === 'Completada' ? 'completed' : ''; ?>">
                                <?php echo htmlspecialchars($task['title']); ?>
                            </div>
                            <div class="task-meta-small">
                                <?php if ($task['estimated_hours']): ?>
                                    ⏱️ <?php echo $task['estimated_hours']; ?>h
                                <?php endif; ?>
                                <?php if ($task['due_date']): ?>
                                    • 📅 <?php echo date('d/m/Y', strtotime($task['due_date'])); ?>
                                <?php endif; ?>
                                <?php if ($task['completed_date']): ?>
                                    • ✅ Completada: <?php echo date('d/m/Y', strtotime($task['completed_date'])); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <span class="task-status status-<?php echo strtolower(str_replace(' ', '', $task['status'])); ?>">
                            <?php echo $task['status']; ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Modal Agregar Tarea (Simple) -->
    <div id="addTaskModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; padding: 20px;">
        <div style="max-width: 500px; margin: 50px auto; background: white; border-radius: 12px; padding: 24px;">
            <h3 style="margin-top: 0;">Agregar Nueva Tarea</h3>
            <form id="addTaskForm">
                <div style="margin-bottom: 16px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 500;">Título de la tarea *</label>
                    <input type="text" name="title" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                </div>
                <div style="margin-bottom: 16px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 500;">Descripción</label>
                    <textarea name="description" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; min-height: 80px;"></textarea>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: 500;">Horas estimadas</label>
                        <input type="number" name="estimated_hours" step="0.5" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: 500;">Fecha límite</label>
                        <input type="date" name="due_date" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                    </div>
                </div>
                <div style="display: flex; gap: 12px; justify-content: flex-end;">
                    <button type="button" onclick="hideAddTaskModal()" style="padding: 10px 20px; border: none; background: #f5f5f5; border-radius: 6px; cursor: pointer;">Cancelar</button>
                    <button type="submit" class="btn-primary">Crear Tarea</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        const projectId = <?php echo $projectId; ?>;
        
        function showAddTaskModal() {
            document.getElementById('addTaskModal').style.display = 'block';
        }
        
        function hideAddTaskModal() {
            document.getElementById('addTaskModal').style.display = 'none';
            document.getElementById('addTaskForm').reset();
        }
        
        document.getElementById('addTaskForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            formData.append('action', 'create_task');
            formData.append('project_id', projectId);
            
            try {
                const response = await fetch('project_api.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                
                if (result.success) {
                    location.reload();
                } else {
                    alert('Error: ' + (result.error || 'No se pudo crear la tarea'));
                }
            } catch (error) {
                console.error(error);
                alert('Error al crear la tarea');
            }
        });
        
        async function completeTask(taskId) {
            const hours = prompt('¿Cuántas horas trabajaste en esta tarea? (opcional, presiona Enter para omitir)');
            
            const formData = new FormData();
            formData.append('action', 'complete_task');
            formData.append('task_id', taskId);
            if (hours) formData.append('hours', hours);
            
            try {
                const response = await fetch('project_api.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                
                if (result.success) {
                    alert(`¡Tarea completada! +${result.points} puntos 🎉`);
                    location.reload();
                } else {
                    alert('Error: ' + (result.error || result.message));
                    location.reload();
                }
            } catch (error) {
                console.error(error);
                alert('Error al completar la tarea');
                location.reload();
            }
        }
        
        async function deployProject() {
            if (!confirm('¿Desplegar este proyecto a producción? Ganarás +50 puntos bonus.')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'deploy');
            formData.append('project_id', projectId);
            
            try {
                const response = await fetch('project_api.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                
                if (result.success) {
                    alert(`¡Proyecto desplegado! +${result.points} puntos 🚀`);
                    location.reload();
                } else {
                    alert('Error: ' + (result.error || result.message));
                }
            } catch (error) {
                console.error(error);
                alert('Error al desplegar el proyecto');
            }
        }
    </script>
</body>
</html>
