<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../services/QuickTaskService.php';
require_once __DIR__ . '/../../src/db.php';
$pdo = get_pdo();
$quickTaskService = new QuickTaskService($pdo);
$userId = $_SESSION['user_id'];

// Obtener fecha seleccionada (hoy por defecto)
$selectedDate = $_GET['date'] ?? date('Y-m-d');

// Verificar si la tabla existe
$tableExists = true;
try {
    $pdo->query("SELECT 1 FROM quick_tasks LIMIT 1");
} catch (PDOException $e) {
    $tableExists = false;
}

// Obtener tareas del día solo si la tabla existe
$tasks = $tableExists ? $quickTaskService->getQuickTasksByDate($userId, $selectedDate) : [];
$stats = $tableExists ? $quickTaskService->getQuickTaskStats($userId) : [
    'today_completed' => 0,
    'today_tasks' => 0,
    'completed_same_day' => 0,
    'total_points' => 0
];

$pageTitle = "Tareas Rápidas";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="../../assets/style.css">
    <style>
        .quick-tasks-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .date-selector {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding: 15px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .date-selector input[type="date"] {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .quick-add-form {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .quick-add-form h3 {
            margin-top: 0;
            color: #333;
        }
        
        .form-row {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .form-row input[type="text"] {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .form-row input[type="time"] {
            width: 120px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .quick-tasks-list {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .quick-task-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #eee;
            transition: background 0.2s;
        }
        
        .quick-task-item:hover {
            background: #f9f9f9;
        }
        
        .quick-task-item:last-child {
            border-bottom: none;
        }
        
        .quick-task-item.completed {
            opacity: 0.6;
        }
        
        .task-checkbox {
            width: 24px;
            height: 24px;
            margin-right: 15px;
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
        
        .task-meta {
            font-size: 12px;
            color: #666;
        }
        
        .task-time {
            display: inline-block;
            background: #e3f2fd;
            color: #1976d2;
            padding: 2px 8px;
            border-radius: 4px;
            margin-right: 8px;
        }
        
        .task-points {
            display: inline-block;
            background: #fff3e0;
            color: #f57c00;
            padding: 2px 8px;
            border-radius: 4px;
            font-weight: 500;
        }
        
        .task-actions {
            display: flex;
            gap: 5px;
        }
        
        .btn-icon {
            padding: 6px 10px;
            background: transparent;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-icon:hover {
            background: #f5f5f5;
        }
        
        .btn-delete {
            color: #d32f2f;
        }
        
        .btn-delete:hover {
            background: #ffebee;
            border-color: #d32f2f;
        }
        
        .stats-bar {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
            margin-top: 5px;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        
        .btn-primary {
            background: #1976d2;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.2s;
        }
        
        .btn-primary:hover {
            background: #1565c0;
        }
        
        .nav-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="quick-tasks-container">
        <div class="nav-buttons">
            <a href="../index.php" class="btn-primary">← Volver a Tareas</a>
        </div>
        
        <h1>📝 Tareas Rápidas del Día</h1>
        <p>Crea tareas simples como reuniones, llamadas o revisiones. ¡Completa tus tareas y gana puntos! 🎯</p>
        
        <?php if (!$tableExists): ?>
            <div style="background: #fff3cd; border: 2px solid #ffc107; padding: 20px; border-radius: 8px; margin: 20px 0; color: #856404;">
                <h3 style="margin-top: 0; color: #856404;">⚠️ Tabla de Tareas Rápidas no está instalada</h3>
                <p>Para usar esta funcionalidad, necesitas ejecutar la migración de base de datos:</p>
                <pre style="background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; color: #212529;">psql -h apptarea.postgres.database.azure.com -U apptarea -d postgres -f db/add_quick_tasks.sql</pre>
                <p style="margin-bottom: 0;">O ejecuta el contenido del archivo <code>db/add_quick_tasks.sql</code> desde pgAdmin.</p>
            </div>
        <?php else: ?>
        
        <!-- Estadísticas -->
        <div class="stats-bar">
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['today_completed'] ?? 0; ?>/<?php echo $stats['today_tasks'] ?? 0; ?></div>
                <div class="stat-label">Completadas Hoy</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['completed_same_day'] ?? 0; ?></div>
                <div class="stat-label">Hechas el Mismo Día</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['total_points'] ?? 0; ?></div>
                <div class="stat-label">Puntos Ganados</div>
            </div>
        </div>
        
        <!-- Selector de fecha -->
        <div class="date-selector">
            <h3>📅 Tareas para: <?php echo date('d/m/Y', strtotime($selectedDate)); ?></h3>
            <form method="GET" style="display: flex; gap: 10px; align-items: center;">
                <input type="date" name="date" value="<?php echo $selectedDate; ?>" onchange="this.form.submit()">
                <a href="?date=<?php echo date('Y-m-d'); ?>" class="btn-primary">Hoy</a>
            </form>
        </div>
        
        <!-- Formulario de agregar rápido -->
        <div class="quick-add-form">
            <h3>➕ Agregar Tarea Rápida</h3>
            <form id="quickAddForm">
                <div class="form-row">
                    <input type="text" id="taskTitle" name="title" placeholder="Ej: Reunión con el equipo, Revisar documento..." required>
                    <input type="time" id="taskTime" name="time" placeholder="Hora">
                    <button type="submit" class="btn-primary">Agregar</button>
                </div>
                <small style="color: #666;">💡 Tip: Completa las tareas el mismo día para ganar 5 puntos en lugar de 3</small>
            </form>
        </div>
        
        <!-- Lista de tareas -->
        <div class="quick-tasks-list">
            <?php if (empty($tasks)): ?>
                <div class="empty-state">
                    <p>📋 No hay tareas para esta fecha</p>
                    <p>¡Agrega tu primera tarea rápida!</p>
                </div>
            <?php else: ?>
                <?php foreach ($tasks as $task): ?>
                    <div class="quick-task-item <?php echo $task['completed'] ? 'completed' : ''; ?>" data-task-id="<?php echo $task['id']; ?>">
                        <input 
                            type="checkbox" 
                            class="task-checkbox" 
                            <?php echo $task['completed'] ? 'checked' : ''; ?>
                            onchange="toggleTask(<?php echo $task['id']; ?>, this.checked)">
                        
                        <div class="task-content">
                            <div class="task-title <?php echo $task['completed'] ? 'completed' : ''; ?>">
                                <?php echo htmlspecialchars($task['title']); ?>
                            </div>
                            <div class="task-meta">
                                <?php if ($task['scheduled_time']): ?>
                                    <span class="task-time">⏰ <?php echo date('H:i', strtotime($task['scheduled_time'])); ?></span>
                                <?php endif; ?>
                                <?php if ($task['completed']): ?>
                                    <span class="task-points">+<?php echo $task['points_awarded']; ?> puntos</span>
                                    <span style="color: #999;">✓ <?php echo date('H:i', strtotime($task['completed_at'])); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if (!$task['completed']): ?>
                            <div class="task-actions">
                                <button class="btn-icon btn-delete" onclick="deleteTask(<?php echo $task['id']; ?>)" title="Eliminar">🗑️</button>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <?php endif; // fin del check $tableExists ?>
    </div>
    
    <script>
        // Agregar tarea rápida
        const quickAddForm = document.getElementById('quickAddForm');
        if (quickAddForm) {
            quickAddForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                
                const formData = new FormData(e.target);
                formData.append('action', 'create');
                formData.append('date', '<?php echo $selectedDate; ?>');
                
                try {
                    const response = await fetch('quick_tasks_api.php', {
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
                    console.error('Error:', error);
                    alert('Error al crear la tarea');
                }
            });
        }
        
        // Marcar/desmarcar tarea como completada
        async function toggleTask(taskId, completed) {
            const formData = new FormData();
            formData.append('action', completed ? 'complete' : 'uncomplete');
            formData.append('task_id', taskId);
            
            try {
                const response = await fetch('quick_tasks_api.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    if (completed && result.points > 0) {
                        showNotification(`¡Tarea completada! +${result.points} puntos 🎉`);
                    }
                    setTimeout(() => location.reload(), 1000);
                } else {
                    alert('Error: ' + (result.error || result.message));
                    location.reload();
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al actualizar la tarea');
                location.reload();
            }
        }
        
        // Eliminar tarea
        async function deleteTask(taskId) {
            if (!confirm('¿Seguro que deseas eliminar esta tarea?')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('task_id', taskId);
            
            try {
                const response = await fetch('quick_tasks_api.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    location.reload();
                } else {
                    alert('Error: ' + (result.error || 'No se pudo eliminar'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al eliminar la tarea');
            }
        }
        
        // Mostrar notificación
        function showNotification(message) {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: #4caf50;
                color: white;
                padding: 15px 20px;
                border-radius: 8px;
                box-shadow: 0 4px 8px rgba(0,0,0,0.2);
                z-index: 1000;
                animation: slideIn 0.3s ease;
            `;
            notification.textContent = message;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
    </script>
</body>
</html>
