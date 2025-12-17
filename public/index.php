<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../services/TaskService.php';
require_once __DIR__ . '/../services/GamificationService.php';
require_once __DIR__ . '/../services/ProjectService.php';

$pdo = get_pdo();
$user_id = get_current_user_id();
$username = get_current_username();

// Actualizar estadísticas de gamificación (calcula y actualiza nivel automáticamente)
$user_stats = getUserStats($pdo, $user_id);

// Estadísticas del dashboard usando servicio
$stats = getTaskStatistics($pdo, $user_id);

// Filtros y búsqueda
$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? '';
$category = $_GET['category'] ?? '';
$priority = $_GET['priority'] ?? '';

// Obtener tareas filtradas usando servicio
$tasks = getTasksFiltered($pdo, $user_id, $search, $filter, $category, $priority);

// Obtener proyectos del usuario para el selector
$user_projects = [];
try {
    $projectService = new ProjectService($pdo);
    $user_projects = $projectService->getUserProjects($user_id, 'all'); // Traer todos los proyectos
    if ($user_projects === null) {
        $user_projects = [];
    }
} catch (Exception $e) {
    error_log("Error cargando proyectos: " . $e->getMessage());
    $user_projects = [];
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
  <title>App-Tareas | Gestión Profesional de Tareas</title>
  
  <!-- PWA Meta Tags -->
  <link rel="manifest" href="pwa/manifest.json">
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
  <link rel="apple-touch-icon" href="../assets/icon-152x152.png">
  <link rel="apple-touch-icon" sizes="72x72" href="../assets/icon-72x72.png">
  <link rel="apple-touch-icon" sizes="96x96" href="../assets/icon-96x96.png">
  <link rel="apple-touch-icon" sizes="128x128" href="../assets/icon-128x128.png">
  <link rel="apple-touch-icon" sizes="144x144" href="../assets/icon-144x144.png">
  <link rel="apple-touch-icon" sizes="152x152" href="../assets/icon-152x152.png">
  <link rel="apple-touch-icon" sizes="192x192" href="../assets/icon-192x192.png">
  <link rel="apple-touch-icon" sizes="384x384" href="../assets/icon-384x384.png">
  <link rel="apple-touch-icon" sizes="512x512" href="../assets/icon-512x512.png">
  
  <!-- Favicon -->
  <link rel="icon" type="image/png" sizes="32x32" href="../assets/icon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="../assets/icon-16x16.png">
  <link rel="shortcut icon" href="../assets/favicon.ico">
  
  <link rel="stylesheet" href="../assets/style.css">
  <meta name="description" content="Sistema de gestión de tareas con seguimiento de urgencias y estado de producción">
</head>
<body>
<div class="container">
  <?php if (isset($_GET['success']) && $_GET['success'] === 'task_completed'): ?>
    <div style="background: var(--accent-green); color: white; padding: 12px 20px; border-radius: 8px; margin-bottom: 16px; display: flex; align-items: center; gap: 10px;">
      <span style="font-size: 1.5rem;">✓</span>
      <span>¡Tarea completada! Has ganado <strong>+<?= intval($_GET['points'] ?? 10) ?> puntos</strong> 🎉</span>
    </div>
  <?php endif; ?>
  
  <?php if (isset($_GET['success']) && $_GET['success'] === 'task_deleted'): ?>
    <div style="background: var(--accent-yellow); color: #000; padding: 12px 20px; border-radius: 8px; margin-bottom: 16px; display: flex; align-items: center; gap: 10px;">
      <span style="font-size: 1.5rem;">🗑️</span>
      <span>
        Tarea eliminada. 
        <?php if (intval($_GET['points_deducted'] ?? 0) > 0): ?>
          Se han restado <strong>-<?= intval($_GET['points_deducted']) ?> puntos</strong> 📉
        <?php endif; ?>
      </span>
    </div>
  <?php endif; ?>
  
  <?php if (isset($_GET['error'])): ?>
    <div style="background: var(--accent-red); color: white; padding: 12px 20px; border-radius: 8px; margin-bottom: 16px; display: flex; align-items: center; gap: 10px;">
      <span style="font-size: 1.5rem;">⚠️</span>
      <span>
        <?php
          $error_messages = [
            'task_not_found' => 'Tarea no encontrada',
            'requires_production' => 'Esta tarea requiere documentos, usa "Producción" en su lugar',
            'already_completed' => 'Esta tarea ya está completada',
            'database_error' => 'Error al completar la tarea'
          ];
          echo $error_messages[$_GET['error']] ?? 'Error desconocido';
        ?>
      </span>
    </div>
  <?php endif; ?>
  
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
    <div style="position: relative;">
      <h1 style="cursor: pointer; user-select: none;" onclick="toggleThemeDropdown()" title="Click para cambiar tema">⚡ App-Tareas</h1>
      
      <!-- Dropdown de temas -->
      <div id="themeDropdown" style="display: none; position: absolute; top: 100%; left: 0; background: var(--bg-card); border: 2px solid var(--border-color); border-radius: 8px; padding: 8px; margin-top: 8px; min-width: 200px; box-shadow: var(--shadow-lg); z-index: 1000;">
        <div style="padding: 8px 12px; color: var(--text-muted); font-size: 0.8rem; font-weight: 600; border-bottom: 1px solid var(--border-color); margin-bottom: 8px;">
          🎨 SELECCIONAR TEMA
        </div>
        <div onclick="changeTheme('dark')" style="padding: 10px 12px; cursor: pointer; border-radius: 6px; display: flex; align-items: center; gap: 10px; transition: all 0.2s ease;" onmouseover="this.style.background='var(--bg-card-hover)'" onmouseout="this.style.background='transparent'">
          <span style="font-size: 1.2rem;">🌙</span>
          <span style="font-weight: 500;">Oscuro</span>
        </div>
        <div onclick="changeTheme('blue')" style="padding: 10px 12px; cursor: pointer; border-radius: 6px; display: flex; align-items: center; gap: 10px; transition: all 0.2s ease;" onmouseover="this.style.background='var(--bg-card-hover)'" onmouseout="this.style.background='transparent'">
          <span style="font-size: 1.2rem;">🔷</span>
          <span style="font-weight: 500;">Azul Acero</span>
        </div>
        <div onclick="changeTheme('purple')" style="padding: 10px 12px; cursor: pointer; border-radius: 6px; display: flex; align-items: center; gap: 10px; transition: all 0.2s ease;" onmouseover="this.style.background='var(--bg-card-hover)'" onmouseout="this.style.background='transparent'">
          <span style="font-size: 1.2rem;">⚡</span>
          <span style="font-weight: 500;">Eléctrico</span>
        </div>
        <div onclick="changeTheme('green')" style="padding: 10px 12px; cursor: pointer; border-radius: 6px; display: flex; align-items: center; gap: 10px; transition: all 0.2s ease;" onmouseover="this.style.background='var(--bg-card-hover)'" onmouseout="this.style.background='transparent'">
          <span style="font-size: 1.2rem;">🎯</span>
          <span style="font-weight: 500;">Militar</span>
        </div>
        <div onclick="changeTheme('red')" style="padding: 10px 12px; cursor: pointer; border-radius: 6px; display: flex; align-items: center; gap: 10px; transition: all 0.2s ease;" onmouseover="this.style.background='var(--bg-card-hover)'" onmouseout="this.style.background='transparent'">
          <span style="font-size: 1.2rem;">🔥</span>
          <span style="font-weight: 500;">Fuego</span>
        </div>
        <div onclick="changeTheme('gray')" style="padding: 10px 12px; cursor: pointer; border-radius: 6px; display: flex; align-items: center; gap: 10px; transition: all 0.2s ease;" onmouseover="this.style.background='var(--bg-card-hover)'" onmouseout="this.style.background='transparent'">
          <span style="font-size: 1.2rem;">⚙️</span>
          <span style="font-weight: 500;">Titanio</span>
        </div>
      </div>
      
      <p class="subtitle" style="color: var(--text-secondary); font-size: 1.1rem; margin-top: -12px; margin-bottom: 0; font-weight: 400;">
        Bienvenido, <strong style="color: var(--accent-blue);"><?= esc($username) ?></strong>
      </p>
    </div>
    <div style="display: flex; gap: 10px; align-items: center;">
      <a class="btn red" href="auth/logout.php" style="padding: 10px 20px; font-size: 0.9rem;" title="Cerrar sesión">
        Salir
      </a>
    </div>
  </div>
  <div class="top-actions">
    <a class="btn" href="index.php" title="Ver todas las tareas">
      <span style="font-size: 1.2rem;">📋</span>
      <span class="btn-text">Todas</span>
    </a>
    <a class="btn" href="index.php?filter=pending" title="Tareas pendientes de producción">
      <span style="font-size: 1.2rem;">⏳</span>
      <span class="btn-text">Pendientes</span>
    </a>
    <a class="btn" href="tasks/projects.php" title="Ver proyectos" style="background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);">
      <span style="font-size: 1.2rem;">📁</span>
      <span class="btn-text">Proyectos</span>
    </a>

    <a class="btn" href="gamification/pomodoro.php" title="Temporizador Pomodoro y Gamificación" style="background: linear-gradient(135deg, #00c896 0%, #00a878 100%);">
      <span style="font-size: 1.2rem;">🍅</span>
      <span class="btn-text">Pomodoro</span>
    </a>
    <button class="btn" onclick="openModal()" title="Crear nueva tarea">
      <span style="font-size: 1.2rem;">➕</span>
      <span class="btn-text">Nueva</span>
    </button>
  </div>

  <!-- Widget de Gamificación -->
  <?php
  // Calcular progreso al siguiente nivel
  $progress_percentage = calculateLevelProgress($user_stats['total_points'], $user_stats['points_to_next_level']);
  
  // Obtener últimos logros usando servicio
  $recent_achievements = getRecentAchievements($pdo, $user_id, 3);
  ?>
  
  <div style="background: rgba(30, 33, 57, 0.95); padding: 20px; border-radius: 12px; margin-bottom: 24px; border: 2px solid rgba(0, 180, 216, 0.3);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 16px;">
      <h3 style="color: #00b4d8; margin: 0; font-size: 18px;">🏆 Tu Progreso</h3>
      <a href="gamification/achievements.php" class="btn" style="font-size: 14px; padding: 8px 16px;">Ver Logros →</a>
    </div>
    
    <div style="display: grid; grid-template-columns: auto 1fr auto; gap: 24px; align-items: center;">
      <!-- Nivel Badge -->
      <div style="text-align: center;">
        <div style="width: 80px; height: 80px; background: linear-gradient(135deg, #00b4d8 0%, #0096c7 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; font-weight: 700; color: white; box-shadow: 0 4px 16px rgba(0, 180, 216, 0.4); margin-bottom: 8px;">
          <?= $user_stats['current_level'] ?>
        </div>
        <div style="font-size: 12px; color: #b0b0b0;">Nivel</div>
      </div>
      
      <!-- Progreso y Puntos -->
      <div>
        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
          <span style="color: #b0b0b0; font-size: 13px;">
            <strong style="color: #00b4d8;"><?= number_format($user_stats['total_points']) ?></strong> puntos
          </span>
          <span style="color: #808080; font-size: 12px;">
            <?= number_format($user_stats['points_to_next_level'] - $user_stats['total_points']) ?> para nivel <?= $user_stats['current_level'] + 1 ?>
          </span>
        </div>
        <div style="width: 100%; height: 10px; background: rgba(15, 17, 23, 0.8); border-radius: 5px; overflow: hidden;">
          <div style="height: 100%; background: linear-gradient(90deg, #00c896 0%, #00b4d8 100%); width: <?= $progress_percentage ?>%; transition: width 0.5s ease; border-radius: 5px;"></div>
        </div>
        
        <!-- Mini Stats -->
        <div style="display: flex; gap: 24px; margin-top: 12px; font-size: 13px;">
          <span title="Racha actual">
            🔥 <strong style="color: #ff6b6b;"><?= $user_stats['current_streak'] ?></strong> días
          </span>
          <span title="Pomodoros completados">
            🍅 <strong style="color: #00b4d8;"><?= $user_stats['pomodoros_completed'] ?></strong>
          </span>
          <span title="Tareas completadas">
            ✅ <strong style="color: #00c896;"><?= $user_stats['tasks_completed'] ?></strong>
          </span>
        </div>
      </div>
      
      <!-- Últimos Logros -->
      <div style="min-width: 120px; max-width: 200px;">
        <?php if (empty($recent_achievements)): ?>
          <div style="color: #808080; font-size: 12px; text-align: center; padding: 0 16px;">
            ¡Completa tareas<br>para desbloquear<br>logros!
          </div>
        <?php else: ?>
          <div style="display: flex; flex-wrap: wrap; gap: 8px; justify-content: center;">
            <?php foreach ($recent_achievements as $ach): ?>
              <div title="<?= htmlspecialchars($ach['name']) ?> - Desbloqueado el <?= date('d/m/Y', strtotime($ach['unlocked_at'])) ?>" 
                   onclick="showAchievementInfo('<?= htmlspecialchars($ach['name']) ?>', '<?= htmlspecialchars($ach['description']) ?>', '<?= $ach['icon'] ?>', '<?= date('d/m/Y', strtotime($ach['unlocked_at'])) ?>', <?= $ach['points'] ?>)"
                   style="width: 50px; height: 50px; background: rgba(15, 17, 23, 0.8); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 28px; border: 2px solid #00b4d8; cursor: pointer; transition: transform 0.2s ease; flex-shrink: 0;"
                   onmouseover="this.style.transform='scale(1.1)'"
                   onmouseout="this.style.transform='scale(1)'">
                <?= $ach['icon'] ?>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  
  <!-- Dashboard de Estadísticas -->
  <div class="dashboard-stats">
    <div class="stat-card">
      <div class="stat-icon">📊</div>
      <div class="stat-info">
        <div class="stat-value"><?= $stats['total'] ?></div>
        <div class="stat-label">Total</div>
      </div>
    </div>
    <div class="stat-card pending">
      <div class="stat-icon">⏳</div>
      <div class="stat-info">
        <div class="stat-value"><?= $stats['pendientes'] ?></div>
        <div class="stat-label">Pendientes</div>
      </div>
    </div>
    <div class="stat-card deployed">
      <div class="stat-icon">✅</div>
      <div class="stat-info">
        <div class="stat-value"><?= $stats['desplegados'] ?></div>
        <div class="stat-label">Desplegados</div>
      </div>
    </div>
    <div class="stat-card urgent">
      <div class="stat-icon">🔥</div>
      <div class="stat-info">
        <div class="stat-value"><?= $stats['urgentes'] ?></div>
        <div class="stat-label">Urgentes</div>
      </div>
    </div>
    <div class="stat-card overdue">
      <div class="stat-icon">⚠️</div>
      <div class="stat-info">
        <div class="stat-value"><?= $stats['vencidos'] ?></div>
        <div class="stat-label">Vencidos</div>
      </div>
    </div>
    <div class="stat-card upcoming">
      <div class="stat-icon">📅</div>
      <div class="stat-info">
        <div class="stat-value"><?= $stats['proximos'] ?></div>
        <div class="stat-label">7 días</div>
      </div>
    </div>
  </div>




  <?php if (count($tasks) === 0): ?>
    <p class="small">No hay tareas registradas.</p>
  <?php else: ?>
  <table>
    <thead>
      <tr><th>Tarea</th><th>🏷️ Info</th><th>⚡ Estado</th><th>Descripción</th><th>📅 Vence</th><th>Acciones</th></tr>
    </thead>
    <tbody>
    <?php foreach ($tasks as $t): ?>
      <?php
        // Verificar si todos los documentos están completos
        $docs_complete = true;
        $docs_status = '';
        if ($t['requires_docs']) {
          $total_docs = 4;
          $completed_docs = $t['doc_plan_prueba'] + $t['doc_plan_produccion'] + $t['doc_control_objeto'] + $t['doc_politica_respaldo'];
          $docs_complete = ($completed_docs == $total_docs);
          $docs_status = "📄 {$completed_docs}/{$total_docs} docs";
        }
        $can_deploy = !$t['requires_docs'] || $docs_complete;
        
        // Verificar si está vencida
        $is_overdue = $t['due_date'] && strtotime($t['due_date']) < time() && !$t['deployed'];
      ?>
      <tr <?= $is_overdue ? 'style="background: rgba(255, 107, 107, 0.1);"' : '' ?>>
        <td data-label="Tarea">
          <strong style="display: block; margin-bottom: 2px;"><?= esc($t['title']) ?></strong>
          
          <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 6px; margin-top: 4px;">
            <!-- Fecha de creación -->
            <span class="small" style="color: var(--text-muted); white-space: nowrap;"><?= esc($t['created_at']) ?></span>
            
            <!-- Estado de documentos -->
            <?php if ($t['requires_docs']): ?>
              <span class="small" style="color: <?= $docs_complete ? 'var(--accent-green)' : 'var(--accent-yellow)' ?>; font-weight: 600; white-space: nowrap;">
                • <?= $docs_status ?>
              </span>
            <?php endif; ?>
          </div>
          
          <!-- Checkboxes de documentos -->
          <?php if ($t['requires_docs']): ?>
            <div style="margin-top: 6px; padding: 4px 6px; background: rgba(0,0,0,0.2); border-radius: 4px; font-size: 0.75rem; line-height: 1.3;">
              <form method="post" action="tasks/update_doc.php" style="margin: 0; display: flex; flex-wrap: wrap; gap: 4px 8px;">
                <input type="hidden" name="task_id" value="<?= $t['id'] ?>">
                <label style="display: inline-flex; align-items: center; cursor: pointer; white-space: nowrap;">
                  <input type="checkbox" name="doc_plan_prueba" value="1" <?= $t['doc_plan_prueba'] ? 'checked' : '' ?> onchange="this.form.submit()" style="margin: 0 3px 0 0;">
                  Prueba
                </label>
                <label style="display: inline-flex; align-items: center; cursor: pointer; white-space: nowrap;">
                  <input type="checkbox" name="doc_plan_produccion" value="1" <?= $t['doc_plan_produccion'] ? 'checked' : '' ?> onchange="this.form.submit()" style="margin: 0 3px 0 0;">
                  Producción
                </label>
                <label style="display: inline-flex; align-items: center; cursor: pointer; white-space: nowrap;">
                  <input type="checkbox" name="doc_control_objeto" value="1" <?= $t['doc_control_objeto'] ? 'checked' : '' ?> onchange="this.form.submit()" style="margin: 0 3px 0 0;">
                  Control
                </label>
                <label style="display: inline-flex; align-items: center; cursor: pointer; white-space: nowrap;">
                  <input type="checkbox" name="doc_politica_respaldo" value="1" <?= $t['doc_politica_respaldo'] ? 'checked' : '' ?> onchange="this.form.submit()" style="margin: 0 3px 0 0;">
                  Respaldo
                </label>
              </form>
            </div>
          <?php endif; ?>
        </td>
        <td data-label="🏷️ Info">
          <div style="display: flex; flex-direction: column; gap: 6px;">
            <?php if (!empty($t['category'])): ?>
              <span class="badge" style="background: var(--accent-blue); font-size: 0.75rem; padding: 4px 8px;">
                <?= esc($t['category']) ?>
              </span>
            <?php endif; ?>
            <?php if (!empty($t['priority'])): ?>
              <?php
                $priority_colors = [
                  'Crítico' => 'background: #ff4757; color: white;',
                  'Alto' => 'background: #ffa502; color: white;',
                  'Medio' => 'background: #f1c40f; color: #000;',
                  'Bajo' => 'background: #2ed573; color: white;'
                ];
                $priority_style = $priority_colors[$t['priority']] ?? 'background: var(--bg-secondary);';
              ?>
              <span class="badge" style="<?= $priority_style ?> font-size: 0.75rem; padding: 4px 8px;">
                <?= esc($t['priority']) ?>
              </span>
            <?php endif; ?>
          </div>
        </td>
        <td data-label="⚡ Estado">
          <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
            <!-- Badge de urgencia -->
            <?php if ($t['urgency'] === 'Alta'): ?><span class="badge alta">Alta</span>
            <?php elseif ($t['urgency'] === 'Media'): ?><span class="badge media">Media</span>
            <?php else: ?><span class="badge baja">Baja</span><?php endif; ?>
            
            <!-- Estado de producción o completada -->
            <?php if ($t['deployed']): ?>
              <?php if ($t['requires_docs']): ?>
                <span class="badge" style="background: var(--accent-green); font-size: 0.75rem; padding: 4px 8px;">✓ Prod</span>
              <?php else: ?>
                <span class="badge" style="background: var(--accent-green); font-size: 0.75rem; padding: 4px 8px;">✓ Completada</span>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        </td>
        <td data-label="Descripción"><?= nl2br(esc($t['description'])) ?></td>
        <td data-label="📅 Vence">
          <?php if ($t['due_date']): ?>
            <span style="<?= $is_overdue ? 'color: var(--accent-red); font-weight: bold;' : '' ?>">
              <?= esc($t['due_date']) ?>
              <?= $is_overdue ? ' ⚠️' : '' ?>
            </span>
          <?php else: ?>
            -
          <?php endif; ?>
        </td>
        <td data-label="Acciones">
          <?php if (!$t['deployed']): ?>
            <?php if ($t['requires_docs']): ?>
              <!-- Tarea de producción: requiere documentos -->
              <?php if ($can_deploy): ?>
                <a class="btn" href="#" onclick="openDeployModal(<?= $t['id'] ?>, true); return false;">✅ Producción</a>
              <?php else: ?>
                <a class="btn" href="tasks/edit.php?id=<?= $t['id'] ?>" style="background: var(--accent-yellow); color: #000;" title="Completa los documentos primero">
                  📋 <?= $completed_docs ?>/4
                </a>
              <?php endif; ?>
            <?php else: ?>
              <!-- Tarea de proyecto: no requiere documentos, solo marcar como completada -->
              <a class="btn" href="tasks/mark_completed.php?id=<?= $t['id'] ?>" onclick="return confirm('¿Marcar esta tarea como completada?')" style="background: var(--accent-green);">✓ Completada</a>
            <?php endif; ?>
          <?php endif; ?>
          <a class="btn btn-icon" href="tasks/edit.php?id=<?= $t['id'] ?>" title="Editar">✏️</a>
          <a class="btn btn-icon red" href="#" onclick="openDeleteModal(<?= $t['id'] ?>, <?= $t['deployed'] ? 'true' : 'false' ?>, '<?= htmlspecialchars($t['title'], ENT_QUOTES) ?>'); return false;" title="Eliminar">🗑️</a>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>

  <!-- Modal para crear tarea -->
  <div id="taskModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2 style="margin: 0;">➕ Nueva Tarea</h2>
        <button class="modal-close" onclick="closeModal()">&times;</button>
      </div>
      <form action="tasks/add.php" method="post" id="taskForm">
        <label>Título de la tarea</label>
        <input type="text" name="title" required placeholder="Ej: Implementar nueva funcionalidad">
        
        <label>Descripción</label>
        <textarea name="description" rows="3" placeholder="Detalles importantes..."></textarea>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
          <div>
            <label>📁 Proyecto (opcional)</label>
            <select name="project_id">
              <option value="">Sin proyecto</option>
              <?php if (!empty($user_projects)): ?>
                <?php foreach ($user_projects as $proj): ?>
                  <option value="<?php echo $proj['id']; ?>">
                    <?php echo esc($proj['icon'] ?? '📁'); ?> <?php echo esc($proj['name']); ?>
                  </option>
                <?php endforeach; ?>
              <?php endif; ?>
            </select>
          </div>
          <div>
            <label>🏷️ Categoría</label>
            <select name="category">
              <option value="Otro">Otro</option>
              <option value="Frontend">Frontend</option>
              <option value="Backend">Backend</option>
              <option value="Database">Database</option>
              <option value="Hotfix">Hotfix</option>
              <option value="Feature">Feature</option>
            </select>
          </div>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
          <div>
            <label>⚡ Prioridad</label>
            <select name="priority">
              <option value="Bajo">🟢 Bajo</option>
              <option value="Medio" selected>🟡 Medio</option>
              <option value="Alto">🟠 Alto</option>
              <option value="Crítico">🔴 Crítico</option>
            </select>
          </div>
          <div>
            <label>⚡ Urgencia</label>
            <select name="urgency">
              <option value="Baja">Baja</option>
              <option value="Media" selected>Media</option>
              <option value="Alta">Alta</option>
            </select>
          </div>
        </div>
        
        <label>📅 Fecha límite (opcional)</label>
        <input type="date" name="due_date">
        
        <div style="margin-top: 16px; padding: 14px; background: var(--bg-input); border-radius: var(--radius-sm); border: 2px solid var(--border-color);">
          <label style="margin-bottom: 10px;">
            <input type="checkbox" name="requires_docs" value="1" id="requiresDocs" onchange="toggleDocuments()">
            <strong>📋 Requiere documentos obligatorios</strong>
          </label>
          
          <div id="documentsSection" style="display: none; margin-top: 10px; padding: 10px; background: rgba(0,0,0,0.2); border-radius: 6px;">
            <p style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 8px;">
              Marcar documentos que ya están completos:
            </p>
            <label style="font-size: 0.9rem;"><input type="checkbox" name="doc_plan_prueba" value="1"> Plan de Prueba</label>
            <label style="font-size: 0.9rem;"><input type="checkbox" name="doc_plan_produccion" value="1"> Plan de Producción</label>
            <label style="font-size: 0.9rem;"><input type="checkbox" name="doc_control_objeto" value="1"> Control de Objeto</label>
            <label style="font-size: 0.9rem;"><input type="checkbox" name="doc_politica_respaldo" value="1"> Política de Respaldo</label>
          </div>
        </div>
        
        <label style="margin-top: 12px;"><input type="checkbox" name="deployed" value="1" id="deployedCheck"> Ya está en producción</label>
        <div style="margin-top:20px; display: flex; gap: 10px;">
          <button class="btn" type="submit">✅ Crear Tarea</button>
          <button class="btn red" type="button" onclick="closeModal()">Cancelar</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal para marcar como desplegado con checklist -->
  <div id="deployModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2 style="margin: 0;">✅ Marcar como Desplegado</h2>
        <button class="modal-close" onclick="closeDeployModal()">&times;</button>
      </div>
      <form id="deployForm" method="post" action="tasks/mark_deployed.php">
        <input type="hidden" name="id" id="deployTaskId">
        <input type="hidden" name="requires_docs" id="deployRequiresDocs" value="0">
        
        <div style="background: var(--bg-input); padding: 16px; border-radius: 8px; margin-bottom: 16px;">
          <h3 style="margin: 0 0 12px 0; color: var(--accent-blue);">📋 Checklist Pre-Deployment</h3>
          <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 12px;">
            Verifica que todo esté listo antes de marcar como desplegado:
          </p>
          
          <label style="display: flex; align-items: center; padding: 8px; background: rgba(0,0,0,0.2); border-radius: 6px; margin-bottom: 8px; cursor: pointer;">
            <input type="checkbox" name="checklist_backup" value="1" id="checklistBackup" style="margin-right: 10px; width: 18px; height: 18px;">
            <span>💾 Backup realizado</span>
          </label>
          
          <label style="display: flex; align-items: center; padding: 8px; background: rgba(0,0,0,0.2); border-radius: 6px; margin-bottom: 8px; cursor: pointer;">
            <input type="checkbox" name="checklist_tests" value="1" id="checklistTests" style="margin-right: 10px; width: 18px; height: 18px;">
            <span>🧪 Tests ejecutados</span>
          </label>
          
          <label style="display: flex; align-items: center; padding: 8px; background: rgba(0,0,0,0.2); border-radius: 6px; margin-bottom: 8px; cursor: pointer;">
            <input type="checkbox" name="checklist_docs" value="1" id="checklistDocs" style="margin-right: 10px; width: 18px; height: 18px;">
            <span>📚 Documentación actualizada</span>
          </label>
          
          <label style="display: flex; align-items: center; padding: 8px; background: rgba(0,0,0,0.2); border-radius: 6px; cursor: pointer;">
            <input type="checkbox" name="checklist_team" value="1" id="checklistTeam" style="margin-right: 10px; width: 18px; height: 18px;">
            <span>👥 Equipo notificado</span>
          </label>
        </div>
        
        <label>⏱️ Tiempo de deployment (minutos - opcional)</label>
        <input type="number" name="deployment_duration" min="1" placeholder="Ej: 15">
        
        <label>📝 Notas del deployment (opcional)</label>
        <textarea name="deployment_notes" rows="3" placeholder="¿Hubo algún problema? ¿Qué se desplegó exactamente?"></textarea>
        
        <div style="margin-top:20px; display: flex; gap: 10px;">
          <button class="btn" type="submit">✅ Confirmar Deployment</button>
          <button class="btn red" type="button" onclick="closeDeployModal()">Cancelar</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal de información de logro -->
  <div id="achievementModal" class="modal">
    <div class="modal-content" style="max-width: 400px;">
      <div class="modal-header">
        <h2 style="margin: 0; display: flex; align-items: center; gap: 10px;">
          <span id="achievementIcon" style="font-size: 2rem;"></span>
          <span id="achievementName"></span>
        </h2>
        <button class="modal-close" onclick="closeAchievementModal()">&times;</button>
      </div>
      <div style="padding: 20px;">
        <p id="achievementDescription" style="color: var(--text-secondary); margin-bottom: 16px; line-height: 1.6;"></p>
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: var(--bg-input); border-radius: 8px;">
          <span style="color: var(--text-muted);">Puntos obtenidos</span>
          <span style="color: var(--accent-green); font-weight: bold; font-size: 1.2rem;">+<span id="achievementPoints"></span> 🏆</span>
        </div>
        <div style="margin-top: 12px; text-align: center; color: var(--text-muted); font-size: 0.9rem;">
          Desbloqueado el <strong id="achievementDate"></strong>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal de selección de tema -->
  <div id="themeModal" class="modal">
    <div class="modal-content" style="max-width: 600px;">
      <div class="modal-header">
        <h2 style="margin: 0;">🎨 Seleccionar Tema</h2>
        <button class="modal-close" onclick="closeThemeModal()">&times;</button>
      </div>
      <div style="padding: 20px;">
        <p style="color: var(--text-secondary); margin-bottom: 20px;">
          Elige el tema que más te guste. Tu preferencia se guardará automáticamente.
        </p>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 16px;">
          <!-- Tema Oscuro (Default) -->
          <div class="theme-option" data-theme="dark" onclick="changeTheme('dark')">
            <div style="background: linear-gradient(135deg, #1a1d29 0%, #2d3561 100%); height: 80px; border-radius: 8px; margin-bottom: 8px; border: 3px solid transparent; display: flex; align-items: center; justify-content: center; font-size: 2rem; cursor: pointer; transition: all 0.3s ease;">
              🌙
            </div>
            <div style="text-align: center; font-weight: 600;">Oscuro</div>
            <div style="text-align: center; font-size: 0.8rem; color: var(--text-muted);">Por defecto</div>
          </div>
          
          <!-- Tema Azul Profundo -->
          <div class="theme-option" data-theme="blue" onclick="changeTheme('blue')">
            <div style="background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%); height: 80px; border-radius: 8px; margin-bottom: 8px; border: 3px solid transparent; display: flex; align-items: center; justify-content: center; font-size: 2rem; cursor: pointer; transition: all 0.3s ease;">
              🔷
            </div>
            <div style="text-align: center; font-weight: 600;">Azul Acero</div>
            <div style="text-align: center; font-size: 0.8rem; color: var(--text-muted);">Profesional</div>
          </div>
          
          <!-- Tema Púrpura -->
          <div class="theme-option" data-theme="purple" onclick="changeTheme('purple')">
            <div style="background: linear-gradient(135deg, #1e1b4b 0%, #5b21b6 100%); height: 80px; border-radius: 8px; margin-bottom: 8px; border: 3px solid transparent; display: flex; align-items: center; justify-content: center; font-size: 2rem; cursor: pointer; transition: all 0.3s ease;">
              ⚡
            </div>
            <div style="text-align: center; font-weight: 600;">Electrico</div>
            <div style="text-align: center; font-size: 0.8rem; color: var(--text-muted);">Energético</div>
          </div>
          
          <!-- Tema Verde Militar -->
          <div class="theme-option" data-theme="green" onclick="changeTheme('green')">
            <div style="background: linear-gradient(135deg, #022c22 0%, #065f46 100%); height: 80px; border-radius: 8px; margin-bottom: 8px; border: 3px solid transparent; display: flex; align-items: center; justify-content: center; font-size: 2rem; cursor: pointer; transition: all 0.3s ease;">
              🎯
            </div>
            <div style="text-align: center; font-weight: 600;">Militar</div>
            <div style="text-align: center; font-size: 0.8rem; color: var(--text-muted);">Táctico</div>
          </div>
          
          <!-- Tema Rojo Fuego -->
          <div class="theme-option" data-theme="red" onclick="changeTheme('red')">
            <div style="background: linear-gradient(135deg, #1f1418 0%, #7f1d1d 100%); height: 80px; border-radius: 8px; margin-bottom: 8px; border: 3px solid transparent; display: flex; align-items: center; justify-content: center; font-size: 2rem; cursor: pointer; transition: all 0.3s ease;">
              🔥
            </div>
            <div style="text-align: center; font-weight: 600;">Fuego</div>
            <div style="text-align: center; font-size: 0.8rem; color: var(--text-muted);">Agresivo</div>
          </div>
          
          <!-- Tema Gris Titanio -->
          <div class="theme-option" data-theme="gray" onclick="changeTheme('gray')">
            <div style="background: linear-gradient(135deg, #111827 0%, #374151 100%); height: 80px; border-radius: 8px; margin-bottom: 8px; border: 3px solid transparent; display: flex; align-items: center; justify-content: center; font-size: 2rem; cursor: pointer; transition: all 0.3s ease;">
              ⚙️
            </div>
            <div style="text-align: center; font-weight: 600;">Titanio</div>
            <div style="text-align: center; font-size: 0.8rem; color: var(--text-muted);">Industrial</div>
          </div>
        </div>
        
        <div style="margin-top: 20px; text-align: center; color: var(--text-muted); font-size: 0.9rem;">
          ✨ El tema seleccionado se aplicará en toda la aplicación
        </div>
      </div>
    </div>
  </div>

  <!-- Modal de confirmación para eliminar tarea -->
  <div id="deleteModal" class="modal">
    <div class="modal-content" style="max-width: 500px;">
      <div class="modal-header">
        <h2 style="margin: 0;">🗑️ Eliminar Tarea</h2>
        <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
      </div>
      <div style="padding: 20px;">
        <div id="deleteWarningCompleted" style="display: none; background: rgba(255, 107, 107, 0.2); border: 2px solid var(--accent-red); border-radius: 8px; padding: 16px; margin-bottom: 16px;">
          <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
            <span style="font-size: 2rem;">⚠️</span>
            <strong style="color: var(--accent-red); font-size: 1.1rem;">ADVERTENCIA</strong>
          </div>
          <p style="margin: 8px 0; line-height: 1.5;">Esta tarea ya está completada.</p>
          <ul style="margin: 8px 0 0 20px; line-height: 1.8; color: var(--text-secondary);">
            <li>❌ Se <strong>RESTARÁN los puntos</strong> que ganaste</li>
            <li>📉 Tu nivel podría <strong>bajar</strong></li>
            <li>📊 Se actualizarán tus estadísticas</li>
          </ul>
        </div>
        
        <p style="color: var(--text-secondary); margin-bottom: 16px; line-height: 1.6;">
          ¿Estás seguro de que deseas eliminar esta tarea?
        </p>
        <div style="background: var(--bg-input); padding: 12px; border-radius: 8px; margin-bottom: 20px;">
          <strong id="deleteTaskTitle" style="color: var(--text-primary);"></strong>
        </div>
        
        <div style="display: flex; gap: 10px;">
          <button class="btn red" onclick="confirmDeleteTask()" style="flex: 1;">
            🗑️ Sí, Eliminar
          </button>
          <button class="btn" onclick="closeDeleteModal()" style="flex: 1; background: var(--bg-secondary);">
            Cancelar
          </button>
        </div>
      </div>
    </div>
  </div>

<script>
// Funciones globales para los modales
window.openModal = function() {
  const modal = document.getElementById('taskModal');
  if (modal) {
    modal.style.display = 'flex';
  } else {
    console.error('Modal taskModal no encontrado');
  }
}

window.closeModal = function() {
  const modal = document.getElementById('taskModal');
  const form = document.getElementById('taskForm');
  const docsSection = document.getElementById('documentsSection');
  
  if (modal) modal.style.display = 'none';
  if (form) form.reset();
  if (docsSection) docsSection.style.display = 'none';
}

window.openDeployModal = function(taskId, requiresDocs) {
  document.getElementById('deployTaskId').value = taskId;
  document.getElementById('deployRequiresDocs').value = requiresDocs ? '1' : '0';
  
  // Obtener los checkboxes
  const checklistBackup = document.getElementById('checklistBackup');
  const checklistTests = document.getElementById('checklistTests');
  const checklistDocs = document.getElementById('checklistDocs');
  const checklistTeam = document.getElementById('checklistTeam');
  
  // Si la tarea requiere documentos, hacer los checkboxes obligatorios
  if (requiresDocs) {
    checklistBackup.setAttribute('required', 'required');
    checklistTests.setAttribute('required', 'required');
    checklistDocs.setAttribute('required', 'required');
    checklistTeam.setAttribute('required', 'required');
  } else {
    // Si no requiere documentos, quitar el required
    checklistBackup.removeAttribute('required');
    checklistTests.removeAttribute('required');
    checklistDocs.removeAttribute('required');
    checklistTeam.removeAttribute('required');
  }
  
  document.getElementById('deployModal').style.display = 'flex';
}

window.closeDeployModal = function() {
  const modal = document.getElementById('deployModal');
  const form = document.getElementById('deployForm');
  
  if (modal) modal.style.display = 'none';
  if (form) form.reset();
}

// Función para mostrar información de logro
window.showAchievementInfo = function(name, description, icon, date, points) {
  document.getElementById('achievementIcon').textContent = icon;
  document.getElementById('achievementName').textContent = name;
  document.getElementById('achievementDescription').textContent = description;
  document.getElementById('achievementPoints').textContent = points;
  document.getElementById('achievementDate').textContent = date;
  document.getElementById('achievementModal').style.display = 'flex';
}

window.closeAchievementModal = function() {
  const modal = document.getElementById('achievementModal');
  if (modal) modal.style.display = 'none';
}

// Variables globales para el modal de eliminar
let deleteTaskId = null;

window.openDeleteModal = function(taskId, isCompleted, taskTitle) {
  deleteTaskId = taskId;
  document.getElementById('deleteTaskTitle').textContent = taskTitle;
  
  const warningDiv = document.getElementById('deleteWarningCompleted');
  if (isCompleted) {
    warningDiv.style.display = 'block';
  } else {
    warningDiv.style.display = 'none';
  }
  
  document.getElementById('deleteModal').style.display = 'flex';
}

window.closeDeleteModal = function() {
  const modal = document.getElementById('deleteModal');
  if (modal) modal.style.display = 'none';
  deleteTaskId = null;
}

window.confirmDeleteTask = function() {
  if (deleteTaskId) {
    window.location.href = 'tasks/delete.php?id=' + deleteTaskId;
  }
}

window.toggleFilters = function() {
  const filtersForm = document.getElementById('filtersForm');
  const filterIcon = document.getElementById('filterIcon');
  
  if (filtersForm.style.display === 'none') {
    filtersForm.style.display = 'flex';
    filterIcon.textContent = '▲';
  } else {
    filtersForm.style.display = 'none';
    filterIcon.textContent = '▼';
  }
}

// Cerrar modal al hacer clic fuera
window.onclick = function(event) {
  const taskModal = document.getElementById('taskModal');
  const deployModal = document.getElementById('deployModal');
  const achievementModal = document.getElementById('achievementModal');
  const deleteModal = document.getElementById('deleteModal');
  const themeModal = document.getElementById('themeModal');
  if (event.target === taskModal) {
    closeModal();
  }
  if (event.target === deployModal) {
    closeDeployModal();
  }
  if (event.target === achievementModal) {
    closeAchievementModal();
  }
  if (event.target === deleteModal) {
    closeDeleteModal();
  }
  if (event.target === themeModal) {
    closeThemeModal();
  }
}

// Sistema de temas
const themes = {
  dark: {
    '--bg-body': '#0f1117',
    '--bg-card': '#1e2139',
    '--bg-card-hover': '#252a42',
    '--primary-gradient-start': '#2d3561',
    '--primary-gradient-end': '#1a1d29'
  },
  blue: {
    '--bg-body': '#0a0e1a',
    '--bg-card': '#0f172a',
    '--bg-card-hover': '#1e293b',
    '--primary-gradient-start': '#1e3a8a',
    '--primary-gradient-end': '#0f172a'
  },
  purple: {
    '--bg-body': '#0d0a1a',
    '--bg-card': '#1e1b4b',
    '--bg-card-hover': '#2e1c5d',
    '--primary-gradient-start': '#5b21b6',
    '--primary-gradient-end': '#1e1b4b'
  },
  green: {
    '--bg-body': '#061412',
    '--bg-card': '#022c22',
    '--bg-card-hover': '#064e3b',
    '--primary-gradient-start': '#065f46',
    '--primary-gradient-end': '#022c22'
  },
  red: {
    '--bg-body': '#120b0e',
    '--bg-card': '#1f1418',
    '--bg-card-hover': '#3f1f27',
    '--primary-gradient-start': '#7f1d1d',
    '--primary-gradient-end': '#1f1418'
  },
  gray: {
    '--bg-body': '#0a0c10',
    '--bg-card': '#111827',
    '--bg-card-hover': '#1f2937',
    '--primary-gradient-start': '#374151',
    '--primary-gradient-end': '#111827'
  }
};

window.openThemeModal = function() {
  const modal = document.getElementById('themeModal');
  if (modal) {
    modal.style.display = 'flex';
    // Marcar el tema actual
    const currentTheme = localStorage.getItem('appTheme') || 'dark';
    document.querySelectorAll('.theme-option').forEach(option => {
      const themeDiv = option.querySelector('div');
      if (option.dataset.theme === currentTheme) {
        themeDiv.style.borderColor = 'var(--accent-blue)';
        themeDiv.style.boxShadow = '0 0 20px rgba(0, 212, 255, 0.5)';
      } else {
        themeDiv.style.borderColor = 'transparent';
        themeDiv.style.boxShadow = 'none';
      }
    });
  }
}

window.closeThemeModal = function() {
  const modal = document.getElementById('themeModal');
  if (modal) modal.style.display = 'none';
}

window.changeTheme = function(themeName) {
  const theme = themes[themeName];
  if (!theme) return;
  
  // Aplicar colores del tema
  Object.keys(theme).forEach(property => {
    document.documentElement.style.setProperty(property, theme[property]);
  });
  
  // Guardar en localStorage
  localStorage.setItem('appTheme', themeName);
  
  // Cerrar dropdown
  const dropdown = document.getElementById('themeDropdown');
  if (dropdown) dropdown.style.display = 'none';
  
  // Cerrar modal si está abierto
  closeThemeModal();
}

// Toggle dropdown de temas
window.toggleThemeDropdown = function() {
  const dropdown = document.getElementById('themeDropdown');
  if (!dropdown) return;
  
  if (dropdown.style.display === 'none' || dropdown.style.display === '') {
    dropdown.style.display = 'block';
  } else {
    dropdown.style.display = 'none';
  }
}

// Cargar tema guardado al iniciar
window.addEventListener('DOMContentLoaded', function() {
  const savedTheme = localStorage.getItem('appTheme') || 'dark';
  if (savedTheme !== 'dark') {
    changeTheme(savedTheme);
  }
});

// Cerrar dropdown al hacer clic fuera
document.addEventListener('click', function(event) {
  const dropdown = document.getElementById('themeDropdown');
  const appTitle = event.target.closest('h1');
  
  if (dropdown && !appTitle && dropdown.style.display === 'block') {
    dropdown.style.display = 'none';
  }
});

window.toggleDocuments = function() {
  const requiresDocs = document.getElementById('requiresDocs');
  const docsSection = document.getElementById('documentsSection');
  const docInputs = docsSection ? docsSection.querySelectorAll('input[type="checkbox"]') : [];
  
  if (requiresDocs && requiresDocs.checked) {
    if (docsSection) docsSection.style.display = 'block';
  } else {
    if (docsSection) docsSection.style.display = 'none';
    docInputs.forEach(input => input.checked = false);
  }
}

// Sistema de notificaciones del navegador
function requestNotificationPermission() {
  if ('Notification' in window && Notification.permission === 'default') {
    Notification.requestPermission();
  }
}

function showNotification(title, body, icon = '⚠️') {
  if ('Notification' in window && Notification.permission === 'granted') {
    new Notification(title, {
      body: body,
      icon: '../assets/icon.png',
      badge: '../assets/badge.png',
      tag: 'app-tareas-alert',
      requireInteraction: false,
      vibrate: [200, 100, 200]
    });
  }
}

function checkPendingTasks() {
  const pendingCount = <?= intval($stats['pendientes'] ?? 0) ?>;
  const overdueCount = <?= intval($stats['vencidos'] ?? 0) ?>;
  const urgentCount = <?= intval($stats['urgentes'] ?? 0) ?>;
  const upcomingCount = <?= intval($stats['proximos'] ?? 0) ?>;
  
  // Notificar tareas vencidas (prioridad alta)
  if (overdueCount > 0) {
    showNotification(
      '⚠️ Tareas Vencidas!',
      `Tienes ${overdueCount} tarea(s) que ya pasaron su fecha límite y necesitan atención urgente.`
    );
  }
  // Notificar tareas urgentes pendientes
  else if (urgentCount > 0) {
    showNotification(
      '🔥 Tareas Urgentes',
      `Tienes ${urgentCount} tarea(s) urgentes pendientes de desplegar.`
    );
  }
  // Notificar tareas próximas a vencer
  else if (upcomingCount > 0) {
    showNotification(
      '📅 Tareas Próximas',
      `Tienes ${upcomingCount} tarea(s) que vencen en los próximos 7 días.`
    );
  }
  // Notificación general de pendientes
  else if (pendingCount > 0) {
    showNotification(
      '⏳ Tareas Pendientes',
      `Tienes ${pendingCount} tarea(s) pendientes de desplegar.`
    );
  }
}

// Pedir permiso al cargar la página
document.addEventListener('DOMContentLoaded', function() {
  requestNotificationPermission();
  
  // Mostrar notificación después de 3 segundos (dar tiempo a que se cargue)
  setTimeout(checkPendingTasks, 3000);
  
  // Verificar cada 30 minutos si hay tareas pendientes
  setInterval(checkPendingTasks, 30 * 60 * 1000);
  
  // Si viene desde un proyecto, abrir el modal y preseleccionar el proyecto
  const urlParams = new URLSearchParams(window.location.search);
  const projectId = urlParams.get('project');
  if (projectId) {
    openModal();
    const projectSelect = document.querySelector('select[name="project_id"]');
    if (projectSelect) {
      projectSelect.value = projectId;
    }
  }
});

// Registrar Service Worker para PWA
if ('serviceWorker' in navigator) {
  window.addEventListener('load', function() {
    navigator.serviceWorker.register('pwa/sw.js')
      .then(function(registration) {
        console.log('✅ Service Worker registrado:', registration.scope);
      })
      .catch(function(error) {
        console.log('❌ Error al registrar Service Worker:', error);
      });
  });
}
</script>

</div>

<!-- Botón flotante para móvil (solo visible en móvil) -->
<button class="fab-mobile" onclick="openModal()" style="display: none;" title="Crear nueva tarea">
  ➕
</button>

</body>
</html>
