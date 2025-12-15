<?php
session_start();
require_once '../src/db.php';

if (!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'Usuario';

// Obtener todos los logros
$achievements_query = "SELECT a.*, 
                       ua.unlocked_at,
                       CASE WHEN ua.id IS NOT NULL THEN TRUE ELSE FALSE END as is_unlocked
                       FROM achievements a
                       LEFT JOIN user_achievements ua ON a.id = ua.achievement_id AND ua.user_id = :user_id
                       ORDER BY 
                         is_unlocked DESC,
                         CASE a.tier
                           WHEN 'diamond' THEN 5
                           WHEN 'platinum' THEN 4
                           WHEN 'gold' THEN 3
                           WHEN 'silver' THEN 2
                           ELSE 1
                         END DESC,
                         a.points DESC";
$stmt = $pdo->prepare($achievements_query);
$stmt->execute(['user_id' => $user_id]);
$achievements = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Agrupar por categoría
$by_category = [];
foreach ($achievements as $ach) {
  $category = $ach['category'];
  if (!isset($by_category[$category])) {
    $by_category[$category] = [];
  }
  $by_category[$category][] = $ach;
}

// Obtener estadísticas de usuario
$stats_query = "SELECT * FROM user_stats WHERE user_id = :user_id";
$stats_stmt = $pdo->prepare($stats_query);
$stats_stmt->execute(['user_id' => $user_id]);
$user_stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// Calcular progreso
$total_achievements = count($achievements);
$unlocked_count = count(array_filter($achievements, fn($a) => $a['is_unlocked']));
$completion_percentage = $total_achievements > 0 ? ($unlocked_count / $total_achievements) * 100 : 0;

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
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Logros | App-Tareas</title>
  <link rel="stylesheet" href="../assets/style.css">
  <style>
    .achievements-container {
      max-width: 1400px;
      margin: 0 auto;
      padding: 20px;
    }

    .progress-card {
      background: rgba(30, 33, 57, 0.95);
      padding: 32px;
      border-radius: 16px;
      margin-bottom: 32px;
      text-align: center;
    }

    .progress-stats {
      display: flex;
      justify-content: center;
      gap: 48px;
      margin: 24px 0;
      flex-wrap: wrap;
    }

    .progress-stat {
      text-align: center;
    }

    .progress-stat-value {
      font-size: 48px;
      font-weight: 700;
      color: #00b4d8;
      margin-bottom: 8px;
    }

    .progress-stat-label {
      color: #b0b0b0;
      font-size: 14px;
    }

    .completion-bar {
      width: 100%;
      max-width: 600px;
      height: 24px;
      background: rgba(15, 17, 23, 0.8);
      border-radius: 12px;
      overflow: hidden;
      margin: 24px auto;
      position: relative;
    }

    .completion-fill {
      height: 100%;
      background: linear-gradient(90deg, #00c896 0%, #00b4d8 50%, #FFD700 100%);
      transition: width 0.5s ease;
      border-radius: 12px;
    }

    .completion-text {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      color: white;
      font-weight: 600;
      font-size: 14px;
      text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
    }

    .category-section {
      background: rgba(30, 33, 57, 0.95);
      padding: 24px;
      border-radius: 16px;
      margin-bottom: 24px;
    }

    .category-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      padding-bottom: 16px;
      border-bottom: 2px solid rgba(255, 255, 255, 0.1);
    }

    .category-title {
      font-size: 24px;
      color: #00b4d8;
      margin: 0;
    }

    .category-desc {
      color: #808080;
      font-size: 14px;
    }

    .category-progress {
      color: #b0b0b0;
      font-size: 14px;
    }

    .achievements-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
      gap: 20px;
    }

    .achievement-card {
      background: rgba(15, 17, 23, 0.8);
      padding: 24px;
      border-radius: 16px;
      text-align: center;
      border: 3px solid transparent;
      transition: all 0.3s ease;
      cursor: pointer;
      position: relative;
      overflow: hidden;
    }

    .achievement-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: var(--tier-color);
      opacity: 0.5;
    }

    .achievement-card.unlocked {
      border-color: var(--tier-color);
    }

    .achievement-card.locked {
      opacity: 0.4;
      filter: grayscale(100%);
    }

    .achievement-card.locked.secret {
      opacity: 0.2;
    }

    .achievement-card:hover:not(.locked) {
      transform: translateY(-8px);
      box-shadow: 0 8px 32px rgba(0, 180, 216, 0.3);
    }

    .achievement-icon {
      font-size: 64px;
      margin-bottom: 16px;
      display: block;
    }

    .achievement-tier {
      display: inline-block;
      padding: 4px 12px;
      border-radius: 12px;
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      margin-bottom: 12px;
      color: var(--tier-color);
      border: 1px solid var(--tier-color);
    }

    .achievement-name {
      font-size: 18px;
      font-weight: 600;
      color: #e0e0e0;
      margin-bottom: 8px;
    }

    .achievement-desc {
      font-size: 13px;
      color: #808080;
      margin-bottom: 12px;
      line-height: 1.4;
    }

    .achievement-points {
      font-size: 16px;
      color: #00b4d8;
      font-weight: 600;
    }

    .achievement-unlocked-date {
      font-size: 11px;
      color: #00c896;
      margin-top: 8px;
    }

    .achievement-secret {
      font-size: 14px;
      color: #808080;
      font-style: italic;
    }

    .filter-tabs {
      display: flex;
      gap: 12px;
      margin-bottom: 32px;
      flex-wrap: wrap;
    }

    .filter-tab {
      padding: 12px 24px;
      background: rgba(30, 33, 57, 0.95);
      border: 2px solid transparent;
      border-radius: 12px;
      color: #b0b0b0;
      cursor: pointer;
      transition: all 0.3s ease;
      font-weight: 600;
    }

    .filter-tab.active {
      border-color: #00b4d8;
      color: #00b4d8;
      background: rgba(0, 180, 216, 0.1);
    }

    .filter-tab:hover {
      border-color: #00b4d8;
    }

    /* Animación de desbloqueo */
    @keyframes unlock {
      0% { transform: scale(1); }
      50% { transform: scale(1.2) rotate(10deg); }
      100% { transform: scale(1) rotate(0deg); }
    }

    .achievement-card.just-unlocked {
      animation: unlock 0.6s ease;
    }

    /* Responsive */
    @media (max-width: 768px) {
      .achievements-grid {
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 16px;
      }

      .achievement-icon {
        font-size: 48px;
      }

      .progress-stats {
        gap: 24px;
      }

      .progress-stat-value {
        font-size: 32px;
      }
    }
  </style>
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
        <a href="index.php" class="btn" style="background: rgba(30, 33, 57, 0.8);">
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

  <script>
    function filterCategory(category) {
      // Actualizar tabs activos
      document.querySelectorAll('.filter-tab').forEach(tab => {
        tab.classList.remove('active');
      });
      event.target.classList.add('active');

      // Mostrar/ocultar secciones
      const sections = document.querySelectorAll('.category-section');
      const cards = document.querySelectorAll('.achievement-card');

      if (category === 'all') {
        sections.forEach(s => s.style.display = 'block');
        cards.forEach(c => c.style.display = 'block');
      } else if (category === 'unlocked') {
        sections.forEach(s => s.style.display = 'block');
        cards.forEach(card => {
          card.style.display = card.dataset.unlocked === 'true' ? 'block' : 'none';
        });
      } else if (category === 'locked') {
        sections.forEach(s => s.style.display = 'block');
        cards.forEach(card => {
          card.style.display = card.dataset.unlocked === 'false' ? 'block' : 'none';
        });
      } else {
        sections.forEach(section => {
          section.style.display = section.dataset.category === category ? 'block' : 'none';
        });
      }
    }

    // Animación suave en scroll
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.style.opacity = '1';
          entry.target.style.transform = 'translateY(0)';
        }
      });
    }, { threshold: 0.1 });

    document.querySelectorAll('.category-section').forEach(section => {
      section.style.opacity = '0';
      section.style.transform = 'translateY(20px)';
      section.style.transition = 'all 0.5s ease';
      observer.observe(section);
    });
  </script>
</body>
</html>
