<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/auth.php';

if (!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}

$user_id = $_SESSION['user_id'];

// Obtener ranking global
$ranking_query = "SELECT u.id, u.username, us.*,
                  ROW_NUMBER() OVER (ORDER BY us.total_points DESC) as rank
                  FROM user_stats us
                  JOIN users u ON us.user_id = u.id
                  ORDER BY us.total_points DESC
                  LIMIT 50";
$ranking_stmt = $pdo->query($ranking_query);
$rankings = $ranking_stmt->fetchAll(PDO::FETCH_ASSOC);

// Encontrar posición del usuario actual
$current_user_rank = array_search($user_id, array_column($rankings, 'id'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Ranking | App-Tareas</title>
  <link rel="stylesheet" href="../assets/style.css">
  <style>
    .ranking-container {
      max-width: 1000px;
      margin: 0 auto;
      padding: 20px;
    }

    .ranking-card {
      background: rgba(30, 33, 57, 0.95);
      border-radius: 16px;
      padding: 24px;
      margin-bottom: 24px;
    }

    .ranking-table {
      width: 100%;
    }

    .ranking-row {
      display: grid;
      grid-template-columns: 60px 1fr 120px 120px 120px;
      gap: 16px;
      padding: 16px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.05);
      align-items: center;
      transition: all 0.3s ease;
    }

    .ranking-row:hover {
      background: rgba(0, 180, 216, 0.05);
    }

    .ranking-row.current-user {
      background: rgba(0, 180, 216, 0.1);
      border: 2px solid #00b4d8;
      border-radius: 8px;
      margin: 8px 0;
    }

    .ranking-row.header {
      font-weight: 600;
      color: #b0b0b0;
      font-size: 13px;
      text-transform: uppercase;
      border-bottom: 2px solid rgba(255, 255, 255, 0.1);
    }

    .ranking-row.header:hover {
      background: transparent;
    }

    .rank-position {
      text-align: center;
      font-size: 24px;
      font-weight: 700;
    }

    .rank-1 { color: #FFD700; } /* Oro */
    .rank-2 { color: #C0C0C0; } /* Plata */
    .rank-3 { color: #CD7F32; } /* Bronce */

    .rank-medal {
      font-size: 32px;
    }

    .user-info {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .user-avatar {
      width: 40px;
      height: 40px;
      background: linear-gradient(135deg, #00b4d8 0%, #0096c7 100%);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 18px;
      font-weight: 700;
      color: white;
    }

    .user-name {
      font-size: 16px;
      font-weight: 600;
      color: #e0e0e0;
    }

    .user-level {
      font-size: 13px;
      color: #00b4d8;
      display: flex;
      align-items: center;
      gap: 4px;
    }

    .stat-value {
      text-align: center;
      font-size: 18px;
      font-weight: 600;
      color: #00b4d8;
    }

    .stat-label {
      font-size: 12px;
      color: #808080;
      text-align: center;
      margin-top: 4px;
    }

    .podium {
      display: flex;
      justify-content: center;
      align-items: flex-end;
      gap: 24px;
      margin: 40px 0;
      padding: 0 40px;
    }

    .podium-item {
      text-align: center;
      transition: transform 0.3s ease;
    }

    .podium-item:hover {
      transform: translateY(-8px);
    }

    .podium-item.first {
      order: 2;
    }

    .podium-item.second {
      order: 1;
    }

    .podium-item.third {
      order: 3;
    }

    .podium-platform {
      background: linear-gradient(135deg, var(--color-start) 0%, var(--color-end) 100%);
      border-radius: 12px 12px 0 0;
      padding: 24px;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    }

    .podium-item.first .podium-platform {
      --color-start: #FFD700;
      --color-end: #FFA500;
      height: 180px;
    }

    .podium-item.second .podium-platform {
      --color-start: #C0C0C0;
      --color-end: #A8A8A8;
      height: 140px;
    }

    .podium-item.third .podium-platform {
      --color-start: #CD7F32;
      --color-end: #B8860B;
      height: 120px;
    }

    .podium-avatar {
      width: 80px;
      height: 80px;
      background: white;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 36px;
      font-weight: 700;
      margin: 0 auto 16px;
      box-shadow: 0 4px 16px rgba(0, 0, 0, 0.3);
    }

    .podium-name {
      font-size: 18px;
      font-weight: 700;
      color: white;
      margin-bottom: 8px;
    }

    .podium-points {
      font-size: 16px;
      color: rgba(255, 255, 255, 0.9);
      font-weight: 600;
    }

    .podium-medal {
      font-size: 48px;
      margin-bottom: 16px;
    }

    @media (max-width: 768px) {
      .ranking-row {
        grid-template-columns: 50px 1fr 80px;
        font-size: 14px;
      }

      .ranking-row .stat-value:not(:first-of-type),
      .ranking-row .stat-label:not(:first-of-type) {
        display: none;
      }

      .podium {
        gap: 16px;
        padding: 0 16px;
      }

      .podium-platform {
        padding: 16px;
      }

      .podium-avatar {
        width: 60px;
        height: 60px;
        font-size: 24px;
      }
    }
  </style>
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
        <a href="index.php" class="btn" style="background: rgba(30, 33, 57, 0.8);">
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
      // Obtener posición exacta del usuario actual
      $user_position_query = "SELECT COUNT(*) + 1 as position
                              FROM user_stats
                              WHERE total_points > (SELECT total_points FROM user_stats WHERE user_id = :user_id)";
      $stmt = $pdo->prepare($user_position_query);
      $stmt->execute(['user_id' => $user_id]);
      $user_position = $stmt->fetchColumn();
      
      $user_stats_query = "SELECT us.*, u.username
                           FROM user_stats us
                           JOIN users u ON us.user_id = u.id
                           WHERE us.user_id = :user_id";
      $stmt = $pdo->prepare($user_stats_query);
      $stmt->execute(['user_id' => $user_id]);
      $current_user_stats = $stmt->fetch(PDO::FETCH_ASSOC);
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
