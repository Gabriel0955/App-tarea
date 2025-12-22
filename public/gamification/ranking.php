<?php
session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../services/GamificationService.php';
require_once __DIR__ . '/../../src/theme.php';

if (!isset($_SESSION['user_id'])) {
  header('Location: ../auth/login.php');
  exit;
}

$pdo = get_pdo();
$user_id = $_SESSION['user_id'];

// Obtener ranking global usando servicio
$rankings = getGlobalRanking($pdo, 50);

// Encontrar posición del usuario actual
$current_user_rank = array_search($user_id, array_column($rankings, 'id'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <?php echo getThemeStyles(); ?>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Ranking | App-Tareas</title>
  <link rel="stylesheet" href="../../assets/style.css">
  <link rel="stylesheet" href="../../assets/css/pages/ranking.css">
</head>
<body>
  <div class="ranking-container">
    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; flex-wrap: wrap; gap: 16px;">
      <div>
        <h1 style="color: #00b4d8; margin: 0;">🏆 Ranking Global</h1>
        <p style="color: #b0b0b0; margin: 8px 0 0 0;">Top 50 usuarios más productivos</p>
      </div>
      <div style="display: flex; gap: 12px;">
        <a href="pomodoro.php" class="btn" style="background: rgba(30, 33, 57, 0.8);">
          🍅 Pomodoro
        </a>
        <a href="../index.php" class="btn" style="background: rgba(30, 33, 57, 0.8);">
          ← Dashboard
        </a>
      </div>
    </div>

    <!-- Podio Top 3 -->
    <?php if (count($rankings) >= 3): ?>
      <div class="ranking-card">
        <h2 style="text-align: center; color: #00b4d8; margin-bottom: 32px;">👑 Podio de Campeones</h2>
        <div class="podium">
          <?php 
          $podium_positions = [
            'second' => $rankings[1] ?? null,
            'first' => $rankings[0] ?? null,
            'third' => $rankings[2] ?? null
          ];
          
          foreach ($podium_positions as $position => $user):
            if (!$user) continue;
            $medal = $position === 'first' ? '🥇' : ($position === 'second' ? '🥈' : '🥉');
            $initial = strtoupper(substr($user['username'], 0, 1));
          ?>
            <div class="podium-item <?= $position ?>">
              <div class="podium-medal"><?= $medal ?></div>
              <div class="podium-platform">
                <div class="podium-avatar"><?= $initial ?></div>
                <div class="podium-name"><?= htmlspecialchars($user['username']) ?></div>
                <div class="podium-points"><?= number_format($user['total_points']) ?> pts</div>
                <div style="font-size: 12px; color: rgba(255, 255, 255, 0.7); margin-top: 4px;">
                  Nivel <?= $user['current_level'] ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <!-- Tabla de Ranking -->
    <div class="ranking-card">
      <h2 style="color: #00b4d8; margin-bottom: 24px;">📊 Clasificación Completa</h2>
      
      <div class="ranking-table">
        <!-- Header -->
        <div class="ranking-row header">
          <div>Pos.</div>
          <div>Usuario</div>
          <div>Puntos</div>
          <div style="display: none;">Tareas</div>
          <div style="display: none;">Racha</div>
        </div>

        <!-- Rows -->
        <?php foreach ($rankings as $index => $user): ?>
          <?php 
            $rank = $index + 1;
            $is_current = $user['id'] == $user_id;
            $rank_class = '';
            if ($rank === 1) $rank_class = 'rank-1';
            elseif ($rank === 2) $rank_class = 'rank-2';
            elseif ($rank === 3) $rank_class = 'rank-3';
            
            $initial = strtoupper(substr($user['username'], 0, 1));
          ?>
          <div class="ranking-row <?= $is_current ? 'current-user' : '' ?>">
            <div class="rank-position <?= $rank_class ?>">
              <?php if ($rank <= 3): ?>
                <span class="rank-medal">
                  <?= $rank === 1 ? '🥇' : ($rank === 2 ? '🥈' : '🥉') ?>
                </span>
              <?php else: ?>
                <?= $rank ?>
              <?php endif; ?>
            </div>
            
            <div class="user-info">
              <div class="user-avatar"><?= $initial ?></div>
              <div>
                <div class="user-name">
                  <?= htmlspecialchars($user['username']) ?>
                  <?= $is_current ? '<span style="color: #00b4d8; margin-left: 8px;">(Tú)</span>' : '' ?>
                </div>
                <div class="user-level">
                  <span>⭐</span>
                  <span>Nivel <?= $user['current_level'] ?></span>
                </div>
              </div>
            </div>

            <div>
              <div class="stat-value"><?= number_format($user['total_points']) ?></div>
              <div class="stat-label">puntos</div>
            </div>

            <div style="display: none;">
              <div class="stat-value"><?= $user['tasks_completed'] ?></div>
              <div class="stat-label">tareas</div>
            </div>

            <div style="display: none;">
              <div class="stat-value">
                <span style="font-size: 16px;">🔥</span>
                <?= $user['current_streak'] ?>
              </div>
              <div class="stat-label">días</div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <?php if (count($rankings) === 0): ?>
        <p style="text-align: center; color: #808080; padding: 40px 0;">
          No hay usuarios en el ranking todavía.
        </p>
      <?php endif; ?>
    </div>

    <!-- Tu Posición (si no está en el top 50) -->
    <?php if ($current_user_rank === false || $current_user_rank >= 50): ?>
      <?php 
      // Obtener posición exacta del usuario actual usando servicio
      $user_position = getUserRankPosition($pdo, $user_id);
      
      // Obtener estadísticas del usuario usando servicio
      $current_user_stats = getCurrentUserStats($pdo, $user_id);
      ?>
      
      <div class="ranking-card" style="border: 2px solid #00b4d8;">
        <h3 style="color: #00b4d8; margin-bottom: 16px;">📍 Tu Posición</h3>
        <div class="ranking-row current-user">
          <div class="rank-position"><?= $user_position ?></div>
          <div class="user-info">
            <div class="user-avatar"><?= strtoupper(substr($current_user_stats['username'], 0, 1)) ?></div>
            <div>
              <div class="user-name"><?= htmlspecialchars($current_user_stats['username']) ?> (Tú)</div>
              <div class="user-level">
                <span>⭐</span>
                <span>Nivel <?= $current_user_stats['current_level'] ?></span>
              </div>
            </div>
          </div>
          <div>
            <div class="stat-value"><?= number_format($current_user_stats['total_points']) ?></div>
            <div class="stat-label">puntos</div>
          </div>
        </div>
        <p style="text-align: center; color: #808080; font-size: 14px; margin-top: 16px;">
          ¡Sigue completando tareas y Pomodoros para subir en el ranking! 💪
        </p>
      </div>
    <?php endif; ?>
  </div>
</body>
</html>
