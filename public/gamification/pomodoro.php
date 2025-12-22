<?php
session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/theme.php';
require_once __DIR__ . '/../../services/TaskService.php';
require_once __DIR__ . '/../../services/GamificationService.php';

if (!isset($_SESSION['user_id'])) {
  header('Location: ../auth/login.php');
  exit;
}

$pdo = get_pdo();
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'Usuario';

// Obtener estadísticas del usuario usando servicio
$user_stats = getUserStats($pdo, $user_id);

// Obtener tareas pendientes para el Pomodoro usando servicio
$available_tasks = getPendingTasksForPomodoro($pdo, $user_id, 10);

// Obtener últimas sesiones Pomodoro usando servicio
$pomodoro_history = getPomodoroHistory($pdo, $user_id, 10);

// Obtener logros desbloqueados recientemente usando servicio
$recent_achievements = getRecentAchievements($pdo, $user_id, 5);

// Calcular progreso al siguiente nivel
$progress_percentage = calculateLevelProgress($user_stats['total_points'], $user_stats['points_to_next_level']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php echo getThemeStyles(); ?>
  <title>Pomodoro & Gamificación | App-Tareas</title>
  <link rel="stylesheet" href="../../assets/style.css">
  <link rel="stylesheet" href="../../assets/css/pages/pomodoro.css">
</head>
<body>
  <div class="pomodoro-container">
    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
      <div>
        <h1 style="color: #00b4d8; margin: 0;">🍅 Pomodoro & Gamificación</h1>
        <p style="color: #b0b0b0; margin: 8px 0 0 0;">¡Hola, <?= htmlspecialchars($username) ?>! Mantén tu enfoque y gana recompensas</p>
      </div>
      <a href="../index.php" class="btn" style="background: rgba(30, 33, 57, 0.8);">
        ← Volver al Dashboard
      </a>
    </div>

    <!-- Grid principal -->
    <div class="pomodoro-grid">
      <!-- Timer Card -->
      <div class="timer-card" id="timerCard">
        <div class="timer-label" id="timerLabel">🍅 Pomodoro</div>
        <div class="timer-display" id="timerDisplay">25:00</div>
        
        <div class="timer-controls">
          <button class="timer-btn start" id="startBtn" onclick="startTimer()">▶️ Iniciar</button>
          <button class="timer-btn pause" id="pauseBtn" onclick="pauseTimer()" style="display: none;">⏸️ Pausar</button>
          <button class="timer-btn reset" id="resetBtn" onclick="resetTimer()">🔄 Reiniciar</button>
        </div>

        <div class="timer-settings">
          <h4 style="color: #00b4d8; margin-bottom: 16px;">⚙️ Configuración</h4>
          
          <div class="setting-group">
            <label>Tiempo de trabajo (min):</label>
            <input type="number" id="workDuration" value="25" min="1" max="60">
          </div>
          
          <div class="setting-group">
            <label>Tiempo de descanso (min):</label>
            <input type="number" id="breakDuration" value="5" min="1" max="30">
          </div>

          <label style="display: block; color: #b0b0b0; font-size: 14px; margin-bottom: 8px;">
            📋 Tarea asociada (opcional):
          </label>
          <select class="task-select" id="taskSelect">
            <option value="">Sin tarea específica</option>
            <?php foreach ($available_tasks as $task): ?>
              <option value="<?= $task['id'] ?>">
                <?= htmlspecialchars($task['title']) ?>
                <?php if ($task['category']): ?>(<?= htmlspecialchars($task['category']) ?>)<?php endif; ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <!-- Stats Card -->
      <div class="stats-card">
        <h3>📊 Tu Progreso</h3>
        
        <div class="level-display">
          <div class="level-badge">
            <?= $user_stats['current_level'] ?>
          </div>
          <div class="level-info">
            <div class="points"><?= number_format($user_stats['total_points']) ?> puntos</div>
            <div><?= number_format($user_stats['points_to_next_level'] - $user_stats['total_points']) ?> puntos para nivel <?= $user_stats['current_level'] + 1 ?></div>
          </div>
          <div class="progress-bar">
            <div class="progress-fill" style="width: <?= $progress_percentage ?>%"></div>
          </div>
        </div>

        <div class="stat-row">
          <span class="stat-label">🔥 Racha actual</span>
          <span class="stat-value streak-indicator">
            <span class="streak-fire">🔥</span>
            <?= $user_stats['current_streak'] ?> días
          </span>
        </div>

        <div class="stat-row">
          <span class="stat-label">🏆 Récord de racha</span>
          <span class="stat-value"><?= $user_stats['longest_streak'] ?> días</span>
        </div>

        <div class="stat-row">
          <span class="stat-label">✅ Tareas completadas</span>
          <span class="stat-value"><?= $user_stats['tasks_completed'] ?></span>
        </div>

        <div class="stat-row">
          <span class="stat-label">🍅 Pomodoros completados</span>
          <span class="stat-value"><?= $user_stats['pomodoros_completed'] ?></span>
        </div>

        <div class="stat-row">
          <span class="stat-label">⏱️ Tiempo de enfoque</span>
          <span class="stat-value"><?= number_format($user_stats['total_focus_time'] / 60, 1) ?>h</span>
        </div>
      </div>
    </div>

    <!-- Achievements Section -->
    <div class="stats-card" style="margin-bottom: 24px;">
      <h3>🏆 Logros Recientes</h3>
      
      <?php if (empty($recent_achievements)): ?>
        <p style="color: #808080; text-align: center; padding: 40px 0;">
          ¡Completa tareas y pomodoros para desbloquear logros!
        </p>
      <?php else: ?>
        <div class="achievements-grid">
          <?php foreach ($recent_achievements as $achievement): ?>
            <div class="achievement-badge" title="<?= htmlspecialchars($achievement['description']) ?>">
              <div class="achievement-icon"><?= $achievement['icon'] ?></div>
              <div class="achievement-name"><?= htmlspecialchars($achievement['name']) ?></div>
              <div class="achievement-points">+<?= $achievement['points'] ?> pts</div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div style="text-align: center; margin-top: 20px;">
        <a href="achievements.php" class="btn" style="display: inline-block;">
          Ver Todos los Logros →
        </a>
      </div>
    </div>

    <!-- Pomodoro History -->
    <div class="stats-card">
      <h3>📜 Historial de Sesiones</h3>
      
      <?php if (empty($pomodoro_history)): ?>
        <p style="color: #808080; text-align: center; padding: 40px 0;">
          ¡Inicia tu primera sesión Pomodoro!
        </p>
      <?php else: ?>
        <table class="history-table">
          <thead>
            <tr>
              <th>Fecha</th>
              <th>Tarea</th>
              <th>Duración</th>
              <th>Estado</th>
              <th>Score</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($pomodoro_history as $session): ?>
              <tr>
                <td><?= date('d/m/Y H:i', strtotime($session['started_at'])) ?></td>
                <td><?= $session['task_title'] ? htmlspecialchars($session['task_title']) : '<em>Sin tarea</em>' ?></td>
                <td><?= $session['work_duration'] ?> min</td>
                <td>
                  <span class="status-badge <?= $session['status'] ?>">
                    <?= $session['status'] === 'completed' ? '✓ Completado' : '⚠️ Interrumpido' ?>
                  </span>
                </td>
                <td style="color: <?= $session['focus_score'] >= 90 ? '#00c896' : ($session['focus_score'] >= 70 ? '#ffc107' : '#ff6b6b') ?>;">
                  <?= $session['focus_score'] ?>%
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>

  <!-- Achievement Notification (oculta por defecto) -->
  <div class="achievement-notification" id="achievementNotification">
    <div class="achievement-notification-content">
      <div class="achievement-notification-icon" id="achievementIcon">🏆</div>
      <div class="achievement-notification-text">
        <h4 id="achievementTitle">¡Logro Desbloqueado!</h4>
        <p id="achievementDesc">Has completado un logro</p>
      </div>
    </div>
  </div>

  <script src="../../assets/js/pages/pomodoro.js"></script>
</body>
</html>
