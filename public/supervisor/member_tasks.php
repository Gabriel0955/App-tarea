<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../src/theme.php';
require_once __DIR__ . '/../../services/SupervisorService.php';
require_once __DIR__ . '/../../src/db.php';

$pdo = get_pdo();
$supervisorId = $_SESSION['user_id'];
$memberId = intval($_GET['member_id'] ?? 0);

$supervisorService = new SupervisorService($pdo);

// Verificar permisos
if (!$supervisorService->isSupervisor($supervisorId)) {
    header('Location: ../index.php?error=not_supervisor');
    exit;
}

if ($memberId <= 0 || !$supervisorService->hasAccessToMember($supervisorId, $memberId)) {
    header('Location: team.php?error=no_access');
    exit;
}

// Obtener información del miembro y sus tareas
$memberInfo = $supervisorService->getTeamMembers($supervisorId);
$memberInfo = array_filter($memberInfo, fn($m) => $m['team_member_id'] == $memberId);
$memberInfo = reset($memberInfo);

if (!$memberInfo) {
    header('Location: team.php?error=member_not_found');
    exit;
}

$tasks = $supervisorService->getTeamMemberTasks($supervisorId, $memberId);

function esc($s) {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

function getPriorityClass($priority) {
    $map = [
        'Crítico' => 'priority-critical',
        'Alto' => 'priority-high',
        'Medio' => 'priority-medium',
        'Bajo' => 'priority-low'
    ];
    return $map[$priority] ?? '';
}

function getUrgencyBadge($urgency) {
    $map = [
        'Alta' => '🔴',
        'Media' => '🟡',
        'Baja' => '🟢'
    ];
    return $map[$urgency] ?? '';
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">
  <?php echo getThemeStyles(); ?>
  <title>Tareas de <?= esc($memberInfo['username']) ?> | Supervisor</title>
  <link rel="stylesheet" href="../../assets/style.css">
  <link rel="stylesheet" href="../../assets/css/pages/supervisor.css">
</head>
<body>
<div class="container">
  <div class="header-section">
    <div>
      <h1>📋 Tareas de <?= esc($memberInfo['username']) ?></h1>
      <p class="subtitle">Nivel <?= $memberInfo['current_level'] ?> • <?= number_format($memberInfo['total_points']) ?> puntos</p>
    </div>
    <div style="display: flex; gap: 10px;">
      <a class="btn" href="team.php">← Volver al Equipo</a>
    </div>
  </div>

  <!-- Estadísticas del miembro -->
  <div class="member-stats-summary">
    <div class="stat-box">
      <span class="stat-number"><?= $memberInfo['total_tasks'] ?></span>
      <span class="stat-text">Total</span>
    </div>
    <div class="stat-box pending">
      <span class="stat-number"><?= $memberInfo['pending_tasks'] ?></span>
      <span class="stat-text">Pendientes</span>
    </div>
    <div class="stat-box overdue">
      <span class="stat-number"><?= $memberInfo['overdue_tasks'] ?></span>
      <span class="stat-text">Vencidas</span>
    </div>
    <div class="stat-box upcoming">
      <span class="stat-number"><?= $memberInfo['upcoming_tasks'] ?></span>
      <span class="stat-text">Próximas</span>
    </div>
    <div class="stat-box completed">
      <span class="stat-number"><?= $memberInfo['completed_tasks'] ?></span>
      <span class="stat-text">Completadas</span>
    </div>
  </div>

  <!-- Tabla de tareas -->
  <?php if (empty($tasks)): ?>
    <div class="empty-state">
      <div class="empty-icon">📋</div>
      <h3><?= esc($memberInfo['username']) ?> no tiene tareas asignadas</h3>
    </div>
  <?php else: ?>
    <div class="tasks-table-container">
      <table class="tasks-table">
        <thead>
          <tr>
            <th>Estado</th>
            <th>Título</th>
            <th>Categoría</th>
            <th>Prioridad</th>
            <th>Urgencia</th>
            <th>Fecha Límite</th>
            <th>Días Pendiente</th>
            <th>Proyecto</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($tasks as $task): ?>
            <tr class="task-row <?= $task['deployed'] ? 'task-completed' : '' ?> <?= $task['due_date'] && $task['due_date'] < date('Y-m-d') && !$task['deployed'] ? 'task-overdue' : '' ?>">
              <td>
                <?php if ($task['deployed']): ?>
                  <span class="status-badge status-completed">✅ Completada</span>
                <?php elseif ($task['due_date'] && $task['due_date'] < date('Y-m-d')): ?>
                  <span class="status-badge status-overdue">⚠️ Vencida</span>
                <?php elseif ($task['due_date'] && $task['due_date'] <= date('Y-m-d', strtotime('+3 days'))): ?>
                  <span class="status-badge status-upcoming">⏰ Próxima</span>
                <?php else: ?>
                  <span class="status-badge status-pending">⏳ Pendiente</span>
                <?php endif; ?>
              </td>
              <td class="task-title"><?= esc($task['title']) ?></td>
              <td>
                <span class="category-badge"><?= esc($task['category']) ?></span>
              </td>
              <td>
                <span class="priority-badge <?= getPriorityClass($task['priority']) ?>">
                  <?= esc($task['priority']) ?>
                </span>
              </td>
              <td>
                <span class="urgency-badge">
                  <?= getUrgencyBadge($task['urgency']) ?> <?= esc($task['urgency']) ?>
                </span>
              </td>
              <td>
                <?php if ($task['due_date']): ?>
                  <span class="due-date"><?= date('d/m/Y', strtotime($task['due_date'])) ?></span>
                <?php else: ?>
                  <span class="no-date">Sin fecha</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if (!$task['deployed']): ?>
                  <span class="days-pending <?= $task['days_pending'] > 7 ? 'warning' : '' ?>">
                    <?= $task['days_pending'] ?> días
                  </span>
                <?php else: ?>
                  -
                <?php endif; ?>
              </td>
              <td>
                <?php if ($task['has_project']): ?>
                  <span class="project-badge">📁 <?= esc($task['project_name']) ?></span>
                <?php else: ?>
                  <span class="no-project">Sin proyecto</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

  <!-- Información adicional -->
  <div class="member-additional-info">
    <div class="info-card">
      <h3>🔥 Racha Actual</h3>
      <p class="info-value"><?= $memberInfo['current_streak'] ?> días</p>
    </div>
    <div class="info-card">
      <h3>🍅 Sesiones Pomodoro</h3>
      <p class="info-value"><?= $memberInfo['total_pomodoros'] ?></p>
    </div>
    <div class="info-card">
      <h3>📅 Última Actividad</h3>
      <p class="info-value">
        <?php 
        if ($memberInfo['last_activity_date']) {
          echo date('d/m/Y', strtotime($memberInfo['last_activity_date']));
        } else {
          echo 'Sin actividad';
        }
        ?>
      </p>
    </div>
  </div>

  <?php if ($memberInfo['notes']): ?>
    <div class="supervisor-notes">
      <h3>📝 Notas del Supervisor</h3>
      <p><?= nl2br(esc($memberInfo['notes'])) ?></p>
      <button class="btn btn-sm" onclick="window.location.href='team.php'">✏️ Editar Notas</button>
    </div>
  <?php endif; ?>
</div>
</body>
</html>
