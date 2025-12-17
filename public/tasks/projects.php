<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../services/ProjectService.php';
require_once __DIR__ . '/../../src/db.php';

$pdo = get_pdo();
$projectService = new ProjectService($pdo);
$userId = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'Usuario';

// Obtener proyectos con estadísticas
$projects = $projectService->getProjectsWithStats($userId, 'all');

function esc($s) {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proyectos | App-Tareas</title>
    <link rel="stylesheet" href="../../assets/style.css">
    <link rel="stylesheet" href="../../assets/style.css">
</head>
<body>
<div class="container">
  <div class="header-section">
    <div>
      <h1>📁 Mis Proyectos</h1>
      <p class="subtitle">Bienvenido, <strong><?= esc($username) ?></strong></p>
    </div>
    <a class="btn red" href="../index.php">← Volver a Tareas</a>
  </div>

  <div class="top-actions">
    <button class="btn" onclick="openCreateModal()">
      <span>➕</span>
      <span class="btn-text">Nuevo Proyecto</span>
    </button>
  </div>

  <?php if (empty($projects)): ?>
    <div class="empty-state">
      <div class="empty-icon">📁</div>
      <h3>No tienes proyectos aún</h3>
      <p>Crea tu primer proyecto para organizar tus tareas</p>
      <button class="btn" onclick="openCreateModal()">➕ Crear Proyecto</button>
    </div>
  <?php else: ?>
    <div class="projects-grid-modern">
      <?php foreach ($projects as $project): ?>
        <div class="project-card-modern" onclick="window.location.href='project_view.php?id=<?= $project['id'] ?>'">
          <div class="project-card-accent" style="background: <?= esc($project['color']) ?>;"></div>
          
          <div class="project-card-content">
            <div class="project-icon-wrapper" style="background: <?= esc($project['color']) ?>;">
              <span class="project-icon-big"><?= esc($project['icon']) ?></span>
            </div>
            
            <h3 class="project-name-modern"><?= esc($project['name']) ?></h3>
            
            <?php if ($project['description']): ?>
              <p class="project-desc-modern"><?= esc($project['description']) ?></p>
            <?php endif; ?>
            
            <div class="project-stats-inline">
              <div class="stat-inline">
                <span class="stat-icon">📋</span>
                <span class="stat-text"><?= $project['stats']['total_tasks'] ?> tareas</span>
              </div>
              <div class="stat-inline">
                <span class="stat-icon">✅</span>
                <span class="stat-text"><?= $project['stats']['completed_tasks'] ?> completadas</span>
              </div>
            </div>
            
            <div class="progress-modern">
              <div class="progress-bar-modern">
                <div class="progress-fill-modern" style="width: <?= $project['stats']['completion_percentage'] ?>%; background: <?= esc($project['color']) ?>;"></div>
              </div>
              <span class="progress-percentage"><?= number_format($project['stats']['completion_percentage'], 0) ?>%</span>
            </div>
          </div>
          
          <div class="project-card-footer">
            <span class="project-status-badge">
              <?= $project['stats']['pending_tasks'] ?> pendientes
            </span>
            <span class="project-arrow">→</span>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<!-- Modal crear proyecto -->
<div id="projectModal" class="modal-overlay">
  <div class="modal-container">
    <div class="modal-content">
      <div class="modal-header">
        <h2 id="modalTitle">➕ Nuevo Proyecto</h2>
        <button class="modal-close" onclick="closeModal()">&times;</button>
      </div>
      <form action="project_api.php" method="post" id="projectForm">
        <input type="hidden" name="action" id="formAction" value="create">
        <input type="hidden" name="project_id" id="projectId">
        <input type="hidden" name="color" id="selectedColor" value="#00b4d8">
        <input type="hidden" name="icon" id="selectedIcon" value="📁">

        <label>Nombre del Proyecto</label>
        <input type="text" name="name" id="projectName" required placeholder="Ej: Desarrollo Web">

        <label>Descripción</label>
        <textarea name="description" id="projectDescription" rows="3" placeholder="Detalles del proyecto..."></textarea>

        <label>Icono</label>
        <div class="icon-grid">
          <span class="icon-item selected" onclick="selectIcon('📁')">📁</span>
          <span class="icon-item" onclick="selectIcon('💼')">💼</span>
          <span class="icon-item" onclick="selectIcon('🎯')">🎯</span>
          <span class="icon-item" onclick="selectIcon('🚀')">🚀</span>
          <span class="icon-item" onclick="selectIcon('💻')">💻</span>
          <span class="icon-item" onclick="selectIcon('📱')">📱</span>
          <span class="icon-item" onclick="selectIcon('🎨')">🎨</span>
          <span class="icon-item" onclick="selectIcon('📊')">📊</span>
          <span class="icon-item" onclick="selectIcon('🏠')">🏠</span>
          <span class="icon-item" onclick="selectIcon('🎓')">🎓</span>
          <span class="icon-item" onclick="selectIcon('⚡')">⚡</span>
          <span class="icon-item" onclick="selectIcon('🔥')">🔥</span>
          <span class="icon-item" onclick="selectIcon('💡')">💡</span>
          <span class="icon-item" onclick="selectIcon('🎮')">🎮</span>
          <span class="icon-item" onclick="selectIcon('📚')">📚</span>
          <span class="icon-item" onclick="selectIcon('🎬')">🎬</span>
        </div>

        <label>Color</label>
        <div class="color-grid">
          <span class="color-item selected" style="background: #00b4d8;" onclick="selectColor('#00b4d8')"></span>
          <span class="color-item" style="background: #ef4444;" onclick="selectColor('#ef4444')"></span>
          <span class="color-item" style="background: #10b981;" onclick="selectColor('#10b981')"></span>
          <span class="color-item" style="background: #f59e0b;" onclick="selectColor('#f59e0b')"></span>
          <span class="color-item" style="background: #a855f7;" onclick="selectColor('#a855f7')"></span>
          <span class="color-item" style="background: #06b6d4;" onclick="selectColor('#06b6d4')"></span>
          <span class="color-item" style="background: #ec4899;" onclick="selectColor('#ec4899')"></span>
          <span class="color-item" style="background: #78716c;" onclick="selectColor('#78716c')"></span>
        </div>

        <div class="modal-actions">
          <button class="btn" type="submit">✅ Guardar</button>
          <button class="btn red" type="button" onclick="closeModal()">Cancelar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function openCreateModal() {
  document.getElementById('modalTitle').textContent = '➕ Nuevo Proyecto';
  document.getElementById('formAction').value = 'create';
  document.getElementById('projectForm').reset();
  document.getElementById('selectedColor').value = '#00b4d8';
  document.getElementById('selectedIcon').value = '📁';
  document.querySelectorAll('.icon-item').forEach(el => el.classList.remove('selected'));
  document.querySelectorAll('.icon-item')[0].classList.add('selected');
  document.querySelectorAll('.color-item').forEach(el => el.classList.remove('selected'));
  document.querySelectorAll('.color-item')[0].classList.add('selected');
  document.getElementById('projectModal').style.display = 'flex';
}

function closeModal() {
  document.getElementById('projectModal').style.display = 'none';
}

function selectColor(color) {
  document.getElementById('selectedColor').value = color;
  document.querySelectorAll('.color-item').forEach(el => el.classList.remove('selected'));
  event.target.classList.add('selected');
}

function selectIcon(icon) {
  document.getElementById('selectedIcon').value = icon;
  document.querySelectorAll('.icon-item').forEach(el => el.classList.remove('selected'));
  event.target.classList.add('selected');
}

window.onclick = function(event) {
  const modal = document.getElementById('projectModal');
  if (event.target === modal) {
    closeModal();
  }
}
</script>
</body>
</html>