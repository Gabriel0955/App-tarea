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
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">
  <title><?php echo htmlspecialchars($pageTitle); ?> | App-Tareas</title>
  
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
  <meta name="description" content="Detalle de proyecto y tareas">
  <style>
    <style>
        /* Estilos específicos de vista de proyecto */
        .task-checkbox {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
    </style>
</head>
<body>
<div class="container">
    <!-- Navegación -->
    <div style="margin-bottom: 24px;">
        <a href="projects.php" class="btn">← Volver a Proyectos</a>
    </div>
    
    <!-- Header del Proyecto -->
    <div class="card" style="margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 16px; gap: 16px; flex-wrap: wrap;">
            <div style="flex: 1;">
                <h1 style="margin: 0 0 8px 0;"><?php echo htmlspecialchars($project['name']); ?></h1>
                <p style="color: var(--text-secondary); margin: 0;"><?php echo htmlspecialchars($project['description'] ?? ''); ?></p>
            </div>
            <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
                <span class="badge badge-<?php echo strtolower(str_replace(' ', '', $project['status'])); ?>">
                    <?php echo $project['status']; ?>
                </span>
                <?php if ($project['progress_percentage'] >= 100 && $project['status'] !== 'En Producción'): ?>
                    <button class="btn" style="background: var(--accent-green);" onclick="deployProject()">
                        🚀 Desplegar a Producción
                    </button>
                <?php endif; ?>
            </div>
        </div>
        
        <div style="display: flex; gap: 24px; flex-wrap: wrap; margin-top: 16px;">
            <div style="display: flex; align-items: center; gap: 8px; font-size: 14px; color: var(--text-secondary);">
                <span>🎯</span>
                <span>Prioridad: <strong><?php echo $project['priority']; ?></strong></span>
            </div>
            <?php if ($project['category']): ?>
                <div style="display: flex; align-items: center; gap: 8px; font-size: 14px; color: var(--text-secondary);">
                    <span>🏷️</span>
                    <span><?php echo htmlspecialchars($project['category']); ?></span>
                </div>
            <?php endif; ?>
            <?php if ($project['target_date']): ?>
                <div style="display: flex; align-items: center; gap: 8px; font-size: 14px; color: var(--text-secondary);">
                    <span>📅</span>
                    <span>Objetivo: <?php echo date('d/m/Y', strtotime($project['target_date'])); ?></span>
                </div>
            <?php endif; ?>
            <?php if ($project['repository_url']): ?>
                <div style="display: flex; align-items: center; gap: 8px; font-size: 14px; color: var(--text-secondary);">
                    <span>📦</span>
                    <a href="<?php echo htmlspecialchars($project['repository_url']); ?>" target="_blank" style="color: var(--accent-blue);">Repositorio</a>
                </div>
            <?php endif; ?>
        </div>
        
        <div style="margin-top: 20px;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                <span style="font-weight: 500;">Progreso del Proyecto</span>
                <span style="color: var(--accent-blue); font-weight: 600;"><?php echo number_format($project['progress_percentage'], 1); ?>%</span>
            </div>
            <div class="progress-bar">
                <div class="progress-bar-fill" style="width: <?php echo $project['progress_percentage']; ?>%"></div>
            </div>
            <div style="font-size: 12px; color: var(--text-secondary); margin-top: 4px;">
                <?php echo $project['completed_tasks']; ?> de <?php echo $project['total_tasks']; ?> tareas completadas
            </div>
        </div>
        
        <!-- Estadísticas -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 16px; margin-top: 20px;">
            <div style="background: var(--bg-input); padding: 16px; border-radius: var(--radius-md); text-align: center;">
                <div style="font-size: 24px; font-weight: bold; color: var(--accent-blue);"><?php echo $stats['total_tasks'] ?? 0; ?></div>
                <div style="font-size: 12px; color: var(--text-secondary); margin-top: 4px;">Total Tareas</div>
            </div>
            <div style="background: var(--bg-input); padding: 16px; border-radius: var(--radius-md); text-align: center;">
                <div style="font-size: 24px; font-weight: bold; color: var(--accent-blue);"><?php echo $stats['tasks_in_progress'] ?? 0; ?></div>
                <div style="font-size: 12px; color: var(--text-secondary); margin-top: 4px;">En Progreso</div>
            </div>
            <div style="background: var(--bg-input); padding: 16px; border-radius: var(--radius-md); text-align: center;">
                <div style="font-size: 24px; font-weight: bold; color: var(--accent-blue);"><?php echo number_format($stats['total_estimated_hours'] ?? 0, 1); ?>h</div>
                <div style="font-size: 12px; color: var(--text-secondary); margin-top: 4px;">Horas Estimadas</div>
            </div>
            <div style="background: var(--bg-input); padding: 16px; border-radius: var(--radius-md); text-align: center;">
                <div style="font-size: 24px; font-weight: bold; color: var(--accent-blue);"><?php echo number_format($stats['total_actual_hours'] ?? 0, 1); ?>h</div>
                <div style="font-size: 12px; color: var(--text-secondary); margin-top: 4px;">Horas Reales</div>
            </div>
        </div>
    </div>
    
    <!-- Tareas -->
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="margin: 0;">📋 Tareas del Proyecto</h2>
            <button class="btn" style="background: var(--accent-blue);" onclick="showAddTaskModal()">+ Agregar Tarea</button>
        </div>
        
        <?php if (empty($tasks)): ?>
            <div style="text-align: center; padding: 40px; color: var(--text-secondary);">
                <p style="font-size: 48px; margin: 0;">📝</p>
                <h3>No hay tareas</h3>
                <p>Agrega tu primera tarea para empezar a trabajar en este proyecto</p>
                <button class="btn" style="background: var(--accent-blue); margin-top: 16px;" onclick="showAddTaskModal()">
                    + Crear Primera Tarea
                </button>
            </div>
        <?php else: ?>
            <?php foreach ($tasks as $task): ?>
                <div style="display: flex; align-items: center; gap: 12px; padding: 16px; border: 2px solid var(--border-color); border-radius: var(--radius-md); margin-bottom: 12px; transition: all 0.2s;">
                    <input 
                        type="checkbox" 
                        class="task-checkbox"
                        <?php echo $task['status'] === 'Completada' ? 'checked disabled' : ''; ?>
                        onchange="completeTask(<?php echo $task['id']; ?>)"
                    >
                    <div style="flex: 1;">
                        <div style="font-weight: 500; <?php echo $task['status'] === 'Completada' ? 'text-decoration: line-through; color: var(--text-muted);' : ''; ?>">
                            <?php echo htmlspecialchars($task['title']); ?>
                        </div>
                        <div style="font-size: 12px; color: var(--text-secondary);">
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
                    <span class="badge badge-<?php echo strtolower(str_replace(' ', '', $task['status'])); ?>">
                        <?php echo $task['status']; ?>
                    </span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Agregar Tarea -->
<div id="addTaskModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 500px;">
        <h3 style="margin-top: 0;">Agregar Nueva Tarea</h3>
        <form id="addTaskForm">
            <div style="margin-bottom: 16px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 500;">Título de la tarea *</label>
                <input type="text" name="title" required style="width: 100%; padding: 10px; border: 1px solid var(--border-color); background: var(--bg-input); color: var(--text-primary); border-radius: var(--radius-sm);">
            </div>
            <div style="margin-bottom: 16px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 500;">Descripción</label>
                <textarea name="description" style="width: 100%; padding: 10px; border: 1px solid var(--border-color); background: var(--bg-input); color: var(--text-primary); border-radius: var(--radius-sm); min-height: 80px;"></textarea>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: 500;">Horas estimadas</label>
                    <input type="number" name="estimated_hours" step="0.5" style="width: 100%; padding: 10px; border: 1px solid var(--border-color); background: var(--bg-input); color: var(--text-primary); border-radius: var(--radius-sm);">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: 500;">Fecha límite</label>
                    <input type="date" name="due_date" style="width: 100%; padding: 10px; border: 1px solid var(--border-color); background: var(--bg-input); color: var(--text-primary); border-radius: var(--radius-sm);">
                </div>
            </div>
            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" class="btn" onclick="hideAddTaskModal()">Cancelar</button>
                <button type="submit" class="btn" style="background: var(--accent-blue);">Crear Tarea</button>
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
