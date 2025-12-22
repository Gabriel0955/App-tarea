<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../src/theme.php';
require_once __DIR__ . '/../../services/ProjectService.php';
require_once __DIR__ . '/../../src/db.php';

// Verificar permiso de lectura de proyectos
require_permission('projects', 'read');

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
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">
  <?php echo getThemeStyles(); ?>
    <title>Proyectos | App-Tareas</title>
    <link rel="stylesheet" href="../../assets/style.css">
    <link rel="stylesheet" href="../../assets/css/pages/projects.css">
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
        <div class="project-card-modern">
          <div class="project-card-accent" style="background: <?= esc($project['color']) ?>;"></div>
          
          <div class="project-card-actions">
            <button class="btn-icon-small" onclick="event.stopPropagation(); openEditModal(<?= $project['id'] ?>, '<?= esc($project['name']) ?>', '<?= esc($project['description']) ?>', '<?= esc($project['color']) ?>', '<?= esc($project['icon']) ?>')" title="Editar proyecto">
              ✏️
            </button>
            <button class="btn-icon-small btn-delete" onclick="event.stopPropagation(); openDeleteModal(<?= $project['id'] ?>, '<?= esc($project['name']) ?>', <?= $project['stats']['total_tasks'] ?>)" title="Eliminar proyecto">
              🗑️
            </button>
          </div>
          
          <div class="project-card-content" onclick="window.location.href='project_view.php?id=<?= $project['id'] ?>'" style="cursor: pointer;">
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
      <form action="api/project_api.php" method="post" id="projectForm">
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

<!-- Modal de confirmación de eliminación -->
<div id="deleteModal" class="modal-overlay" style="display: none;">
  <div class="modal-container">
    <div class="modal-content">
      <div class="modal-header">
        <h2>🗑️ Eliminar Proyecto</h2>
        <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
      </div>
      
      <div style="padding: 20px;">
        <p style="margin-bottom: 16px;">¿Estás seguro de que deseas eliminar el proyecto <strong id="deleteProjectName"></strong>?</p>
        
        <div id="deleteTasksSection" style="background: rgba(255, 152, 0, 0.1); padding: 16px; border-radius: 8px; border: 2px solid #ff9800; margin-bottom: 16px;">
          <p style="margin: 0 0 12px 0; color: #ff9800; font-weight: bold;">⚠️ Este proyecto tiene <span id="deleteTasksCount"></span> tarea(s) asociada(s)</p>
          
          <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 12px; background: rgba(0,0,0,0.2); border-radius: 6px;">
            <input type="checkbox" id="deleteTasksCheckbox" style="width: 20px; height: 20px; cursor: pointer;">
            <span style="font-weight: 500;">Eliminar también todas las tareas del proyecto</span>
          </label>
          
          <p style="margin: 12px 0 0 0; font-size: 0.85rem; color: var(--text-secondary);">
            Si no marcas esta opción, las tareas se mantendrán pero quedarán sin proyecto asignado.
          </p>
        </div>
        
        <div id="deleteNoTasksSection" style="display: none; background: rgba(76, 175, 80, 0.1); padding: 16px; border-radius: 8px; border: 2px solid #4caf50; margin-bottom: 16px;">
          <p style="margin: 0; color: #4caf50;">✅ Este proyecto no tiene tareas asociadas</p>
        </div>
      </div>
      
      <div class="modal-actions">
        <button class="btn red" onclick="confirmDelete()">🗑️ Eliminar Proyecto</button>
        <button class="btn" onclick="closeDeleteModal()">Cancelar</button>
      </div>
    </div>
  </div>
</div>

<script src="../../assets/js/pages/projects.js"></script>
</body>
</html>