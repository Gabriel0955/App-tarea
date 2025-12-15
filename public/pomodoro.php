<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/auth.php';

if (!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}

$pdo = get_pdo();
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'Usuario';

// Obtener estadísticas del usuario
$stats_query = "SELECT * FROM user_stats WHERE user_id = :user_id";
$stats_stmt = $pdo->prepare($stats_query);
$stats_stmt->execute(['user_id' => $user_id]);
$user_stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// Si no existe, crear registro
if (!$user_stats) {
  $create_stats = "INSERT INTO user_stats (user_id) VALUES (:user_id)";
  $pdo->prepare($create_stats)->execute(['user_id' => $user_id]);
  $user_stats = [
    'total_points' => 0,
    'current_level' => 1,
    'points_to_next_level' => 100,
    'current_streak' => 0,
    'longest_streak' => 0,
    'tasks_completed' => 0,
    'pomodoros_completed' => 0,
    'total_focus_time' => 0
  ];
}

// Obtener tareas pendientes para el Pomodoro
$tasks_query = "SELECT id, title, urgency, category 
                FROM tasks 
                WHERE user_id = :user_id 
                AND deployed = 0
                ORDER BY 
                  CASE urgency 
                    WHEN 'Alta' THEN 1 
                    WHEN 'Media' THEN 2 
                    ELSE 3 
                  END,
                  due_date ASC NULLS LAST
                LIMIT 10";
$tasks_stmt = $pdo->prepare($tasks_query);
$tasks_stmt->execute(['user_id' => $user_id]);
$available_tasks = $tasks_stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener últimas sesiones Pomodoro
$pomodoro_history_query = "SELECT ps.*, t.title as task_title 
                           FROM pomodoro_sessions ps
                           LEFT JOIN tasks t ON ps.task_id = t.id
                           WHERE ps.user_id = :user_id
                           ORDER BY ps.started_at DESC
                           LIMIT 10";
$history_stmt = $pdo->prepare($pomodoro_history_query);
$history_stmt->execute(['user_id' => $user_id]);
$pomodoro_history = $history_stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener logros desbloqueados recientemente
$achievements_query = "SELECT a.*, ua.unlocked_at
                       FROM user_achievements ua
                       JOIN achievements a ON ua.achievement_id = a.id
                       WHERE ua.user_id = :user_id
                       ORDER BY ua.unlocked_at DESC
                       LIMIT 5";
$achievements_stmt = $pdo->prepare($achievements_query);
$achievements_stmt->execute(['user_id' => $user_id]);
$recent_achievements = $achievements_stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcular progreso al siguiente nivel
$progress_percentage = $user_stats['points_to_next_level'] > 0 
  ? min(100, ($user_stats['total_points'] / $user_stats['points_to_next_level']) * 100)
  : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pomodoro & Gamificación | App-Tareas</title>
  <link rel="stylesheet" href="../assets/style.css">
  <style>
    /* Estilos específicos para Pomodoro */
    .pomodoro-container {
      max-width: 1400px;
      margin: 0 auto;
      padding: 20px;
    }

    .pomodoro-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 24px;
      margin-bottom: 24px;
    }

    /* Timer principal */
    .timer-card {
      background: rgba(30, 33, 57, 0.95);
      padding: 40px;
      border-radius: 20px;
      text-align: center;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    }

    .timer-display {
      font-size: 120px;
      font-weight: 700;
      color: #00b4d8;
      font-variant-numeric: tabular-nums;
      line-height: 1;
      margin: 30px 0;
      text-shadow: 0 4px 20px rgba(0, 180, 216, 0.4);
      font-family: 'Courier New', monospace;
    }

    .timer-label {
      font-size: 24px;
      color: #b0b0b0;
      margin-bottom: 20px;
      text-transform: uppercase;
      letter-spacing: 2px;
    }

    .timer-controls {
      display: flex;
      gap: 16px;
      justify-content: center;
      margin: 30px 0;
    }

    .timer-btn {
      padding: 16px 32px;
      font-size: 18px;
      border-radius: 12px;
      border: none;
      cursor: pointer;
      font-weight: 600;
      transition: all 0.3s ease;
      min-width: 140px;
    }

    .timer-btn.start {
      background: linear-gradient(135deg, #00c896 0%, #00a878 100%);
      color: white;
    }

    .timer-btn.pause {
      background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
      color: #1e2139;
    }

    .timer-btn.reset {
      background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
      color: white;
    }

    .timer-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(0, 180, 216, 0.4);
    }

    .timer-btn:disabled {
      opacity: 0.5;
      cursor: not-allowed;
      transform: none;
    }

    /* Configuración del timer */
    .timer-settings {
      margin-top: 30px;
      padding-top: 30px;
      border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    .setting-group {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 16px;
    }

    .setting-group label {
      color: #b0b0b0;
      font-size: 14px;
    }

    .setting-group input[type="number"] {
      width: 80px;
      padding: 8px;
      background: rgba(15, 17, 23, 0.8);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 8px;
      color: #e0e0e0;
      text-align: center;
    }

    .task-select {
      width: 100%;
      padding: 12px;
      background: rgba(15, 17, 23, 0.8);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 8px;
      color: #e0e0e0;
      margin-top: 16px;
    }

    /* Stats card */
    .stats-card {
      background: rgba(30, 33, 57, 0.95);
      padding: 24px;
      border-radius: 16px;
      box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
    }

    .stats-card h3 {
      color: #00b4d8;
      margin-bottom: 20px;
      font-size: 20px;
    }

    .level-display {
      text-align: center;
      margin-bottom: 24px;
    }

    .level-badge {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 100px;
      height: 100px;
      background: linear-gradient(135deg, #00b4d8 0%, #0096c7 100%);
      border-radius: 50%;
      font-size: 36px;
      font-weight: 700;
      color: white;
      margin-bottom: 12px;
      box-shadow: 0 6px 20px rgba(0, 180, 216, 0.4);
    }

    .level-info {
      color: #b0b0b0;
      font-size: 14px;
    }

    .level-info .points {
      color: #00b4d8;
      font-weight: 600;
      font-size: 18px;
    }

    .progress-bar {
      width: 100%;
      height: 12px;
      background: rgba(15, 17, 23, 0.8);
      border-radius: 6px;
      overflow: hidden;
      margin-top: 12px;
    }

    .progress-fill {
      height: 100%;
      background: linear-gradient(90deg, #00c896 0%, #00b4d8 100%);
      transition: width 0.3s ease;
      border-radius: 6px;
    }

    .stat-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 12px 0;
      border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }

    .stat-row:last-child {
      border-bottom: none;
    }

    .stat-label {
      color: #b0b0b0;
      font-size: 14px;
    }

    .stat-value {
      color: #00b4d8;
      font-weight: 600;
      font-size: 18px;
    }

    .streak-indicator {
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }

    .streak-fire {
      font-size: 24px;
      animation: flicker 2s infinite;
    }

    @keyframes flicker {
      0%, 100% { opacity: 1; transform: scale(1); }
      50% { opacity: 0.8; transform: scale(1.1); }
    }

    /* Achievements */
    .achievements-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
      gap: 12px;
      margin-top: 16px;
    }

    .achievement-badge {
      background: rgba(15, 17, 23, 0.8);
      padding: 16px;
      border-radius: 12px;
      text-align: center;
      border: 2px solid transparent;
      transition: all 0.3s ease;
      cursor: pointer;
    }

    .achievement-badge:hover {
      border-color: #00b4d8;
      transform: translateY(-4px);
    }

    .achievement-badge.locked {
      opacity: 0.3;
    }

    .achievement-icon {
      font-size: 48px;
      margin-bottom: 8px;
    }

    .achievement-name {
      font-size: 12px;
      color: #b0b0b0;
      font-weight: 600;
    }

    .achievement-points {
      font-size: 14px;
      color: #00b4d8;
      margin-top: 4px;
    }

    /* History table */
    .history-table {
      width: 100%;
      margin-top: 16px;
    }

    .history-table th {
      text-align: left;
      padding: 12px;
      color: #b0b0b0;
      font-size: 12px;
      text-transform: uppercase;
      border-bottom: 2px solid rgba(255, 255, 255, 0.1);
    }

    .history-table td {
      padding: 12px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.05);
      font-size: 14px;
    }

    .status-badge {
      padding: 4px 12px;
      border-radius: 12px;
      font-size: 12px;
      font-weight: 600;
    }

    .status-badge.completed {
      background: rgba(0, 200, 150, 0.2);
      color: #00c896;
    }

    .status-badge.interrupted {
      background: rgba(255, 107, 107, 0.2);
      color: #ff6b6b;
    }

    /* Responsive */
    @media (max-width: 968px) {
      .pomodoro-grid {
        grid-template-columns: 1fr;
      }

      .timer-display {
        font-size: 80px;
      }

      .timer-btn {
        min-width: 100px;
        padding: 12px 24px;
        font-size: 16px;
      }
    }

    @media (max-width: 480px) {
      .timer-display {
        font-size: 60px;
      }

      .timer-controls {
        flex-direction: column;
      }

      .timer-btn {
        width: 100%;
      }

      .achievements-grid {
        grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
      }
    }

    /* Animación cuando el timer está activo */
    .timer-card.active {
      animation: pulse-border 2s infinite;
    }

    @keyframes pulse-border {
      0%, 100% {
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
      }
      50% {
        box-shadow: 0 8px 32px rgba(0, 180, 216, 0.4);
      }
    }

    /* Notificación de logro */
    .achievement-notification {
      position: fixed;
      top: 20px;
      right: 20px;
      background: linear-gradient(135deg, #00b4d8 0%, #0096c7 100%);
      color: white;
      padding: 20px 24px;
      border-radius: 12px;
      box-shadow: 0 8px 32px rgba(0, 180, 216, 0.6);
      z-index: 10000;
      transform: translateX(400px);
      transition: transform 0.5s ease;
    }

    .achievement-notification.show {
      transform: translateX(0);
    }

    .achievement-notification-content {
      display: flex;
      align-items: center;
      gap: 16px;
    }

    .achievement-notification-icon {
      font-size: 48px;
    }

    .achievement-notification-text h4 {
      margin: 0 0 4px 0;
      font-size: 18px;
    }

    .achievement-notification-text p {
      margin: 0;
      font-size: 14px;
      opacity: 0.9;
    }
  </style>
</head>
<body>
  <div class="pomodoro-container">
    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
      <div>
        <h1 style="color: #00b4d8; margin: 0;">🍅 Pomodoro & Gamificación</h1>
        <p style="color: #b0b0b0; margin: 8px 0 0 0;">¡Hola, <?= htmlspecialchars($username) ?>! Mantén tu enfoque y gana recompensas</p>
      </div>
      <a href="index.php" class="btn" style="background: rgba(30, 33, 57, 0.8);">
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

  <script>
    let timerInterval = null;
    let timeRemaining = 25 * 60; // segundos
    let isWorking = true;
    let isPaused = false;
    let sessionStartTime = null;
    let currentSessionId = null;

    // Sonido de notificación (usando Web Audio API)
    function playNotificationSound() {
      const audioContext = new (window.AudioContext || window.webkitAudioContext)();
      const oscillator = audioContext.createOscillator();
      const gainNode = audioContext.createGain();
      
      oscillator.connect(gainNode);
      gainNode.connect(audioContext.destination);
      
      oscillator.frequency.value = 800;
      oscillator.type = 'sine';
      gainNode.gain.value = 0.3;
      
      oscillator.start();
      setTimeout(() => oscillator.stop(), 200);
    }

    function formatTime(seconds) {
      const mins = Math.floor(seconds / 60);
      const secs = seconds % 60;
      return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }

    function updateDisplay() {
      document.getElementById('timerDisplay').textContent = formatTime(timeRemaining);
    }

    function startTimer() {
      if (timerInterval) return;
      
      isPaused = false;
      sessionStartTime = new Date();
      
      const workDuration = parseInt(document.getElementById('workDuration').value);
      const breakDuration = parseInt(document.getElementById('breakDuration').value);
      
      if (timeRemaining === workDuration * 60 || timeRemaining === breakDuration * 60) {
        // Nueva sesión
        timeRemaining = isWorking ? workDuration * 60 : breakDuration * 60;
      }
      
      document.getElementById('startBtn').style.display = 'none';
      document.getElementById('pauseBtn').style.display = 'inline-block';
      document.getElementById('timerCard').classList.add('active');
      
      timerInterval = setInterval(() => {
        timeRemaining--;
        updateDisplay();
        
        if (timeRemaining <= 0) {
          completeTimer();
        }
      }, 1000);
    }

    function pauseTimer() {
      if (!timerInterval) return;
      
      clearInterval(timerInterval);
      timerInterval = null;
      isPaused = true;
      
      document.getElementById('startBtn').style.display = 'inline-block';
      document.getElementById('pauseBtn').style.display = 'none';
      document.getElementById('timerCard').classList.remove('active');
    }

    function resetTimer() {
      clearInterval(timerInterval);
      timerInterval = null;
      isPaused = false;
      
      const workDuration = parseInt(document.getElementById('workDuration').value);
      timeRemaining = workDuration * 60;
      isWorking = true;
      
      updateDisplay();
      document.getElementById('timerLabel').textContent = '🍅 Pomodoro';
      document.getElementById('startBtn').style.display = 'inline-block';
      document.getElementById('pauseBtn').style.display = 'none';
      document.getElementById('timerCard').classList.remove('active');
    }

    function completeTimer() {
      clearInterval(timerInterval);
      timerInterval = null;
      
      playNotificationSound();
      document.getElementById('timerCard').classList.remove('active');
      
      if (isWorking) {
        // Completó un Pomodoro
        savePomodoro('completed');
        showNotification('¡Pomodoro Completado!', '¡Excelente trabajo! Toma un descanso. 🎉');
        
        // Cambiar a descanso
        isWorking = false;
        const breakDuration = parseInt(document.getElementById('breakDuration').value);
        timeRemaining = breakDuration * 60;
        document.getElementById('timerLabel').textContent = '☕ Descanso';
        updateDisplay();
        
        document.getElementById('startBtn').style.display = 'inline-block';
        document.getElementById('pauseBtn').style.display = 'none';
      } else {
        // Completó descanso
        showNotification('Descanso Terminado', '¡Listo para otro Pomodoro! 💪');
        
        // Volver a trabajo
        isWorking = true;
        const workDuration = parseInt(document.getElementById('workDuration').value);
        timeRemaining = workDuration * 60;
        document.getElementById('timerLabel').textContent = '🍅 Pomodoro';
        updateDisplay();
        
        document.getElementById('startBtn').style.display = 'inline-block';
        document.getElementById('pauseBtn').style.display = 'none';
      }
    }

    async function savePomodoro(status) {
      const taskId = document.getElementById('taskSelect').value || null;
      const workDuration = parseInt(document.getElementById('workDuration').value);
      
      try {
        const response = await fetch('pomodoro_save.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            task_id: taskId,
            work_duration: workDuration,
            status: status
          })
        });
        
        const data = await response.json();
        
        if (data.success) {
          // Mostrar logros desbloqueados
          if (data.achievements && data.achievements.length > 0) {
            data.achievements.forEach((ach, index) => {
              setTimeout(() => showAchievement(ach), index * 3000);
            });
          }
          
          // Recargar después de 3 segundos
          setTimeout(() => location.reload(), 3000);
        }
      } catch (error) {
        console.error('Error al guardar Pomodoro:', error);
      }
    }

    function showNotification(title, message) {
      if ('Notification' in window && Notification.permission === 'granted') {
        new Notification(title, {
          body: message,
          icon: '../assets/icon-192x192.png',
          badge: '../assets/icon-72x72.png'
        });
      }
    }

    function showAchievement(achievement) {
      const notification = document.getElementById('achievementNotification');
      document.getElementById('achievementIcon').textContent = achievement.icon;
      document.getElementById('achievementTitle').textContent = '🎉 ' + achievement.name;
      document.getElementById('achievementDesc').textContent = achievement.description + ' (+' + achievement.points + ' pts)';
      
      notification.classList.add('show');
      playNotificationSound();
      
      setTimeout(() => {
        notification.classList.remove('show');
      }, 5000);
    }

    // Solicitar permisos de notificación
    if ('Notification' in window && Notification.permission === 'default') {
      Notification.requestPermission();
    }

    // Advertir antes de cerrar si hay sesión activa
    window.addEventListener('beforeunload', (e) => {
      if (timerInterval && !isPaused) {
        e.preventDefault();
        e.returnValue = '¿Seguro que quieres salir? Perderás el progreso de tu Pomodoro actual.';
      }
    });

    // Inicializar
    updateDisplay();
  </script>
</body>
</html>
