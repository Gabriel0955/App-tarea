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
$username = $_SESSION['username'] ?? 'Usuario';

// Obtener todos los logros usando servicio
$achievements = getAllAchievements($pdo, $user_id);

// Agrupar por categoría
$by_category = [];
foreach ($achievements as $ach) {
  $category = $ach['category'];
  if (!isset($by_category[$category])) {
    $by_category[$category] = [];
  }
  $by_category[$category][] = $ach;
}

// Obtener estadísticas de usuario usando servicio
$user_stats = getUserStats($pdo, $user_id);

// Calcular progreso usando servicio
$completion_percentage = calculateAchievementProgress($achievements);

// Contar logros desbloqueados y totales
$unlocked_count = count(array_filter($achievements, function($a) { return $a['is_unlocked'] ?? false; }));
$total_achievements = count($achievements);

// Mapeo de categorías
$category_names = [
  'tasks' => ['📋 Tareas', 'Logros relacionados con completar tareas'],
  'pomodoro' => ['🍅 Pomodoro', 'Logros de sesiones de concentración'],
  'streak' => ['🔥 Rachas', 'Logros por consistencia diaria'],
  'quality' => ['✨ Calidad', 'Logros por puntualidad y excelencia'],
  'speed' => ['💨 Velocidad', 'Logros por productividad rápida']
];

// Mapeo de tiers
$tier_colors = [
  'bronze' => ['#CD7F32', 'Bronce'],
  'silver' => ['#C0C0C0', 'Plata'],
  'gold' => ['#FFD700', 'Oro'],
  'platinum' => ['#E5E4E2', 'Platino'],
  'diamond' => ['#B9F2FF', 'Diamante']
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <?php echo getThemeStyles(); ?>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Logros | App-Tareas</title>
  <link rel="stylesheet" href="../../assets/style.css">
  <link rel="stylesheet" href="../../assets/css/pages/achievements.css">
</head>
<body>
  <div class="achievements-container">
    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; flex-wrap: wrap; gap: 16px;">
      <div>
        <h1 style="color: #00b4d8; margin: 0;">🏆 Logros</h1>
        <p style="color: #b0b0b0; margin: 8px 0 0 0;">Completa desafíos y desbloquea recompensas</p>
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

    <!-- Progress Card -->
    <div class="progress-card">
      <h2 style="color: #00b4d8; margin-bottom: 24px;">Tu Progreso</h2>
      
      <div class="progress-stats">
        <div class="progress-stat">
          <div class="progress-stat-value"><?= $unlocked_count ?></div>
          <div class="progress-stat-label">Logros Desbloqueados</div>
        </div>
        <div class="progress-stat">
          <div class="progress-stat-value"><?= number_format($user_stats['total_points'] ?? 0) ?></div>
          <div class="progress-stat-label">Puntos Totales</div>
        </div>
        <div class="progress-stat">
          <div class="progress-stat-value"><?= number_format($completion_percentage, 1) ?>%</div>
          <div class="progress-stat-label">Completado</div>
        </div>
      </div>

      <div class="completion-bar">
        <div class="completion-fill" style="width: <?= $completion_percentage ?>%"></div>
        <div class="completion-text"><?= $unlocked_count ?> / <?= $total_achievements ?> logros</div>
      </div>
    </div>

    <!-- Filter Tabs -->
    <div class="filter-tabs">
      <div class="filter-tab active" onclick="filterCategory('all')">🌟 Todos</div>
      <div class="filter-tab" onclick="filterCategory('tasks')">📋 Tareas</div>
      <div class="filter-tab" onclick="filterCategory('pomodoro')">🍅 Pomodoro</div>
      <div class="filter-tab" onclick="filterCategory('streak')">🔥 Rachas</div>
      <div class="filter-tab" onclick="filterCategory('quality')">✨ Calidad</div>
      <div class="filter-tab" onclick="filterCategory('speed')">💨 Velocidad</div>
      <div class="filter-tab" onclick="filterCategory('unlocked')">✅ Desbloqueados</div>
      <div class="filter-tab" onclick="filterCategory('locked')">🔒 Bloqueados</div>
    </div>

    <!-- Achievements by Category -->
    <?php foreach ($by_category as $category => $category_achievements): ?>
      <?php 
        $category_unlocked = count(array_filter($category_achievements, fn($a) => $a['is_unlocked']));
        $category_total = count($category_achievements);
        list($cat_icon, $cat_desc) = $category_names[$category] ?? ['🏆 ' . ucfirst($category), ''];
      ?>
      <div class="category-section" data-category="<?= $category ?>">
        <div class="category-header">
          <div>
            <h3 class="category-title"><?= $cat_icon ?></h3>
            <p class="category-desc"><?= $cat_desc ?></p>
          </div>
          <div class="category-progress">
            <?= $category_unlocked ?> / <?= $category_total ?> desbloqueados
          </div>
        </div>

        <div class="achievements-grid">
          <?php foreach ($category_achievements as $achievement): ?>
            <?php 
              $tier_color = $tier_colors[$achievement['tier']][0] ?? '#808080';
              $tier_name = $tier_colors[$achievement['tier']][1] ?? 'Común';
              $is_unlocked = $achievement['is_unlocked'];
              $is_secret = $achievement['is_secret'] && !$is_unlocked;
            ?>
            <div class="achievement-card <?= $is_unlocked ? 'unlocked' : 'locked' ?> <?= $is_secret ? 'secret' : '' ?>"
                 style="--tier-color: <?= $tier_color ?>;"
                 data-unlocked="<?= $is_unlocked ? 'true' : 'false' ?>"
                 title="<?= $is_secret ? 'Logro secreto' : htmlspecialchars($achievement['description']) ?>">
              
              <span class="achievement-icon">
                <?= $is_secret ? '❓' : $achievement['icon'] ?>
              </span>

              <div class="achievement-tier"><?= $tier_name ?></div>

              <?php if ($is_secret): ?>
                <div class="achievement-secret">
                  Logro Secreto<br>
                  ???
                </div>
              <?php else: ?>
                <div class="achievement-name"><?= htmlspecialchars($achievement['name']) ?></div>
                <div class="achievement-desc"><?= htmlspecialchars($achievement['description']) ?></div>
                <div class="achievement-points">+<?= $achievement['points'] ?> puntos</div>
                
                <?php if ($is_unlocked): ?>
                  <div class="achievement-unlocked-date">
                    ✓ Desbloqueado el <?= date('d/m/Y', strtotime($achievement['unlocked_at'])) ?>
                  </div>
                <?php endif; ?>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <script src="../../assets/js/pages/achievements.js"></script>
</body>
</html>
