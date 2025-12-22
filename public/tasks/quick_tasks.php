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

// Verificar si la tabla existe usando servicio
$tableExists = $quickTaskService->tableExists();

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
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="../../assets/style.css">
    <?php echo getThemeStyles(); ?>
    <link rel="stylesheet" href="../../assets/css/pages/quick-tasks.css">
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
    
    <script src="../../assets/js/pages/quick-tasks.js"></script>
</body>
</html>
