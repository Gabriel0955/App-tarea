<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../services/TaskService.php';

$pdo = get_pdo();
$user_id = get_current_user_id();
$username = get_current_username();

// Obtener mes y año actual o del parámetro
$month = intval($_GET['month'] ?? date('m'));
$year = intval($_GET['year'] ?? date('Y'));

// Validar mes y año
if ($month < 1 || $month > 12) $month = date('m');
if ($year < 2020 || $year > 2100) $year = date('Y');

// Obtener tareas del mes usando servicio
$tasks = getTasksByMonth($pdo, $user_id, $year, $month);

// Organizar tareas por fecha
$tasks_by_date = [];
foreach ($tasks as $task) {
    $date = $task['due_date'];
    if (!isset($tasks_by_date[$date])) {
        $tasks_by_date[$date] = [];
    }
    $tasks_by_date[$date][] = $task;
}

// Información del calendario
$first_day = date('N', strtotime($start_date)); // 1 (Monday) - 7 (Sunday)
$days_in_month = date('t', strtotime($start_date));
$month_name = date('F', strtotime($start_date));

// Navegación
$prev_month = $month - 1;
$prev_year = $year;
if ($prev_month < 1) {
    $prev_month = 12;
    $prev_year--;
}

$next_month = $month + 1;
$next_year = $year;
if ($next_month > 12) {
    $next_month = 1;
    $next_year++;
}

function esc($s) { 
  return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); 
}

?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">
  <title>Calendario - App-Tareas</title>
  <link rel="stylesheet" href="../assets/style.css">
  <style>
    .calendar-container {
      max-width: 1200px;
      margin: 0 auto;
    }
    
    .calendar-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      padding: 16px;
      background: var(--bg-secondary);
      border-radius: var(--radius-md);
    }
    
    .calendar-grid {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      gap: 8px;
      margin-bottom: 20px;
    }
    
    .calendar-day-header {
      text-align: center;
      font-weight: bold;
      padding: 12px;
      background: var(--bg-secondary);
      border-radius: var(--radius-sm);
      color: var(--accent-blue);
    }
    
    .calendar-day {
      min-height: 100px;
      padding: 8px;
      background: var(--bg-secondary);
      border-radius: var(--radius-sm);
      border: 2px solid var(--border-color);
      position: relative;
    }
    
    .calendar-day.today {
      border-color: var(--accent-blue);
      background: rgba(74, 144, 226, 0.1);
    }
    
    .calendar-day.empty {
      background: rgba(0, 0, 0, 0.2);
      border-color: transparent;
    }
    
    .day-number {
      font-weight: bold;
      margin-bottom: 8px;
      color: var(--text-color);
    }
    
    .day-task {
      font-size: 0.75rem;
      padding: 4px 6px;
      margin-bottom: 4px;
      border-radius: 4px;
      cursor: pointer;
      transition: transform 0.2s;
    }
    
    .day-task:hover {
      transform: scale(1.02);
    }
    
    .day-task.pending {
      background: var(--accent-yellow);
      color: #000;
    }
    
    .day-task.deployed {
      background: var(--accent-green);
      color: white;
    }
    
    .day-task.urgent {
      background: var(--accent-red);
      color: white;
    }
    
    .day-task.overdue {
      background: #ff4757;
      color: white;
      font-weight: bold;
    }
    
    @media (max-width: 768px) {
      .calendar-grid {
        gap: 4px;
      }
      
      .calendar-day {
        min-height: 80px;
        padding: 4px;
      }
      
      .day-task {
        font-size: 0.65rem;
        padding: 2px 4px;
      }
    }
  </style>
</head>
<body>
<div class="container calendar-container">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
    <div>
      <h1>📅 Calendario de Deployments</h1>
      <p class="subtitle" style="color: var(--text-secondary); font-size: 1.1rem; margin-top: -12px; margin-bottom: 0; font-weight: 400;">
        <?= esc($username) ?>
      </p>
    </div>
    <div style="display: flex; gap: 10px;">
      <a class="btn" href="index.php" title="Volver al inicio">
        🏠 Inicio
      </a>
      <a class="btn red" href="logout.php" style="padding: 10px 20px; font-size: 0.9rem;" title="Cerrar sesión">
        🚪 Salir
      </a>
    </div>
  </div>

  <div class="calendar-header">
    <a href="calendar.php?month=<?= $prev_month ?>&year=<?= $prev_year ?>" class="btn">
      ← Anterior
    </a>
    <h2 style="margin: 0;"><?= $month_name ?> <?= $year ?></h2>
    <a href="calendar.php?month=<?= $next_month ?>&year=<?= $next_year ?>" class="btn">
      Siguiente →
    </a>
  </div>

  <div class="calendar-grid">
    <div class="calendar-day-header">Lun</div>
    <div class="calendar-day-header">Mar</div>
    <div class="calendar-day-header">Mié</div>
    <div class="calendar-day-header">Jue</div>
    <div class="calendar-day-header">Vie</div>
    <div class="calendar-day-header">Sáb</div>
    <div class="calendar-day-header">Dom</div>

    <?php
    // Días vacíos al inicio
    for ($i = 1; $i < $first_day; $i++) {
        echo '<div class="calendar-day empty"></div>';
    }

    // Días del mes
    for ($day = 1; $day <= $days_in_month; $day++) {
        $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
        $is_today = ($date === date('Y-m-d'));
        $has_tasks = isset($tasks_by_date[$date]);
        
        $class = 'calendar-day';
        if ($is_today) $class .= ' today';
        
        echo "<div class=\"$class\">";
        echo "<div class=\"day-number\">$day</div>";
        
        if ($has_tasks) {
            foreach ($tasks_by_date[$date] as $task) {
                $task_class = 'day-task';
                $is_overdue = strtotime($date) < time() && !$task['deployed'];
                
                if ($is_overdue) {
                    $task_class .= ' overdue';
                } elseif ($task['deployed']) {
                    $task_class .= ' deployed';
                } elseif ($task['urgency'] === 'Alta') {
                    $task_class .= ' urgent';
                } else {
                    $task_class .= ' pending';
                }
                
                $title = esc($task['title']);
                $icon = $task['deployed'] ? '✅' : ($is_overdue ? '⚠️' : '⏳');
                
                echo "<div class=\"$task_class\" title=\"$title\" onclick=\"window.location.href='edit.php?id={$task['id']}'\">";
                echo "$icon " . (strlen($title) > 20 ? substr($title, 0, 20) . '...' : $title);
                echo "</div>";
            }
        }
        
        echo "</div>";
    }
    ?>
  </div>

  <div style="padding: 20px; background: var(--bg-secondary); border-radius: var(--radius-md);">
    <h3 style="margin-top: 0;">📊 Resumen del Mes</h3>
    <?php
    $total_month = count($tasks);
    $pending_month = count(array_filter($tasks, fn($t) => !$t['deployed']));
    $deployed_month = count(array_filter($tasks, fn($t) => $t['deployed']));
    $overdue_month = count(array_filter($tasks, fn($t) => strtotime($t['due_date']) < time() && !$t['deployed']));
    ?>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px;">
      <div style="padding: 12px; background: var(--bg-input); border-radius: 8px; text-align: center;">
        <div style="font-size: 1.5rem; font-weight: bold; color: var(--accent-blue);"><?= $total_month ?></div>
        <div style="font-size: 0.85rem; color: var(--text-secondary);">Total</div>
      </div>
      <div style="padding: 12px; background: var(--bg-input); border-radius: 8px; text-align: center;">
        <div style="font-size: 1.5rem; font-weight: bold; color: var(--accent-yellow);"><?= $pending_month ?></div>
        <div style="font-size: 0.85rem; color: var(--text-secondary);">Pendientes</div>
      </div>
      <div style="padding: 12px; background: var(--bg-input); border-radius: 8px; text-align: center;">
        <div style="font-size: 1.5rem; font-weight: bold; color: var(--accent-green);"><?= $deployed_month ?></div>
        <div style="font-size: 0.85rem; color: var(--text-secondary);">Desplegados</div>
      </div>
      <div style="padding: 12px; background: var(--bg-input); border-radius: 8px; text-align: center;">
        <div style="font-size: 1.5rem; font-weight: bold; color: var(--accent-red);"><?= $overdue_month ?></div>
        <div style="font-size: 0.85rem; color: var(--text-secondary);">Vencidos</div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
