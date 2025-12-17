<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../services/ProjectService.php';
require_once __DIR__ . '/../../src/db.php';

$pdo = get_pdo();
$projectService = new ProjectService($pdo);
$userId = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'Usuario';

$projectId = intval($_GET['id'] ?? 0);

if ($projectId <= 0) {
    header('Location: projects.php');
    exit;
}

$project = $projectService->getProjectById($projectId, $userId);

if (!$project) {
    header('Location: projects.php?error=project_not_found');
    exit;
}

$tasks = $projectService->getProjectTasks($projectId, $userId);
$stats = $projectService->getProjectStats($projectId);

function esc($s) { 
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); 
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc($project['name']); ?> | App-Tareas</title>
    <link rel="stylesheet" href="../../assets/style.css">
</head>
<body>
<div class="container">
  <!-- Header del Proyecto -->
  <div class="project-header-hero" style="background: linear-gradient(135deg, <?= esc($project['color']) ?> 0%, <?= esc($project['color']) ?>dd 100%);">
    <div class="project-hero-content">
      <div class="project-hero-icon"><?= esc($project['icon']) ?></div>
      <div class="project-hero-info">
        <h1 class="project-hero-title"><?= esc($project['name']) ?></h1>
        <?php if ($project['description']): ?>
          <p class="project-hero-desc"><?= esc($project['description']) ?></p>
        <?php endif; ?>
      </div>
    </div>
    <a class="btn-back-hero" href="projects.php">← Proyectos</a>
  </div>

  <!-- Estadísticas Destacadas -->
  <div class="stats-hero-grid">
    <div class="stat-hero-card">
      <div class="stat-hero-icon">📋</div>
      <div class="stat-hero-content">
        <span class="stat-hero-number"><?= $stats['total_tasks'] ?></span>
        <span class="stat-hero-label">Total de Tareas</span>
      </div>
    </div>
    <div class="stat-hero-card stat-success">
      <div class="stat-hero-icon">✅</div>
      <div class="stat-hero-content">
        <span class="stat-hero-number"><?= $stats['completed_tasks'] ?></span>
        <span class="stat-hero-label">Completadas</span>
      </div>
    </div>
    <div class="stat-hero-card stat-warning">
      <div class="stat-hero-icon">⏳</div>
      <div class="stat-hero-content">
        <span class="stat-hero-number"><?= $stats['pending_tasks'] ?></span>
        <span class="stat-hero-label">Pendientes</span>
      </div>
    </div>
    <div class="stat-hero-card stat-primary">
      <div class="stat-hero-icon">📊</div>
      <div class="stat-hero-content">
        <span class="stat-hero-number"><?= number_format($stats['completion_percentage'], 0) ?>%</span>
        <span class="stat-hero-label">Progreso</span>
      </div>
    </div>
  </div>

  <!-- Barra de Progreso Grande -->
  <div class="progress-hero-container">
    <div class="progress-hero-header">
      <span class="progress-hero-title">Progreso General del Proyecto</span>
      <span class="progress-hero-percentage"><?= number_format($stats['completion_percentage'], 1) ?>%</span>
    </div>
    <div class="progress-bar-hero">
      <div class="progress-fill-hero" style="width: <?= $stats['completion_percentage'] ?>%; background: <?= esc($project['color']) ?>;"></div>
    </div>
    <div class="progress-hero-footer">
      <span><?= $stats['completed_tasks'] ?> de <?= $stats['total_tasks'] ?> tareas completadas</span>
    </div>
  </div>

  <!-- Acción Nueva Tarea -->
  <div class="action-section">
    <a class="btn-action-large" href="../index.php?project=<?= $projectId ?>" style="background: <?= esc($project['color']) ?>;">
      <span class="btn-action-icon">➕</span>
      <div class="btn-action-content">
        <span class="btn-action-title">Nueva Tarea</span>
        <span class="btn-action-subtitle">Agregar tarea a este proyecto</span>
      </div>
    </a>
  </div>

  <!-- Lista de Tareas -->
  <div class="tasks-section-header">
    <h2 class="section-title">📝 Tareas del Proyecto</h2>
    <span class="task-count-badge"><?= count($tasks) ?> tareas</span>
  </div>

  <?php if (empty($tasks)): ?>
    <div class="empty-state">
      <div class="empty-icon">📝</div>
      <h3>No hay tareas en este proyecto</h3>
      <p>Crea la primera tarea para empezar a trabajar en este proyecto</p>
      <a href="../index.php?project=<?= $projectId ?>" class="btn">➕ Crear Primera Tarea</a>
    </div>
  <?php else: ?>
    <div class="tasks-list-modern">
      <?php foreach ($tasks as $task): ?>
        <div class="task-item-modern <?= $task['deployed'] ? 'task-completed' : '' ?>">
          <div class="task-item-check">
            <?php if ($task['deployed']): ?>
              <span class="check-icon">✓</span>
            <?php else: ?>
              <span class="check-empty"></span>
            <?php endif; ?>
          </div>
          
          <div class="task-item-content">
            <h3 class="task-item-title <?= $task['deployed'] ? 'task-title-done' : '' ?>">
              <?= esc($task['title']) ?>
            </h3>
            
            <?php if ($task['description']): ?>
              <p class="task-item-desc"><?= esc($task['description']) ?></p>
            <?php endif; ?>
            
            <div class="task-item-meta">
              <?php if ($task['urgency']): ?>
                <span class="task-meta-badge badge-urgency-<?= strtolower($task['urgency']) ?>">
                  <?= esc($task['urgency']) ?>
                </span>
              <?php endif; ?>
              
              <?php if ($task['category']): ?>
                <span class="task-meta-badge badge-category">
                  🏷️ <?= esc($task['category']) ?>
                </span>
              <?php endif; ?>
              
              <?php if ($task['due_date']): ?>
                <span class="task-meta-badge badge-date">
                  📅 <?= esc($task['due_date']) ?>
                </span>
              <?php endif; ?>
            </div>
          </div>
          
          <div class="task-item-actions">
            <a href="../index.php#task-<?= $task['id'] ?>" class="btn-task-action">
              Ver
            </a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
</body>
</html>