<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/auth.php';

$pdo = get_pdo();
$user_id = get_current_user_id();
$username = get_current_username();

$filter = $_GET['filter'] ?? '';
if ($filter === 'pending') {
    $stmt = $pdo->prepare('SELECT * FROM tasks WHERE user_id = ? AND deployed = 0 ORDER BY created_at DESC');
    $stmt->execute([$user_id]);
} else {
    $stmt = $pdo->prepare('SELECT * FROM tasks WHERE user_id = ? ORDER BY created_at DESC');
    $stmt->execute([$user_id]);
}
$tasks = $stmt->fetchAll();

function esc($s) { 
  return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); 
}

?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">
  <title>App-Tareas | Gestión Profesional de Tareas</title>
  <link rel="stylesheet" href="../assets/style.css">
  <meta name="description" content="Sistema de gestión de tareas con seguimiento de urgencias y estado de producción">
  <meta name="theme-color" content="#1e2139">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
</head>
<body>
<div class="container">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
    <div>
      <h1>⚡ App-Tareas</h1>
      <p class="subtitle" style="color: var(--text-secondary); font-size: 1.1rem; margin-top: -12px; margin-bottom: 0; font-weight: 400;">
        Bienvenido, <strong style="color: var(--accent-blue);"><?= esc($username) ?></strong>
      </p>
    </div>
    <a class="btn red" href="logout.php" style="padding: 10px 20px; font-size: 0.9rem;" title="Cerrar sesión">
      🚪 Salir
    </a>
  </div>

  <div class="top-actions">
    <a class="btn" href="index.php" title="Ver todas las tareas">
      <span style="font-size: 1.2rem;">📋</span>
      <span class="btn-text">Todas</span>
    </a>
    <a class="btn" href="index.php?filter=pending" title="Tareas pendientes de producción">
      <span style="font-size: 1.2rem;">⏳</span>
      <span class="btn-text">Pendientes</span>
    </a>
    <button class="btn" onclick="openModal()" title="Crear nueva tarea">
      <span style="font-size: 1.2rem;">➕</span>
      <span class="btn-text">Nueva</span>
    </button>
  </div>

  <?php if (count($tasks) === 0): ?>
    <p class="small">No hay tareas registradas.</p>
  <?php else: ?>
  <table>
    <thead>
      <tr><th>Tarea</th><th>⚡ Estado</th><th>Descripción</th><th>📅 Vence</th><th>Acciones</th></tr>
    </thead>
    <tbody>
    <?php foreach ($tasks as $t): ?>
      <?php
        // Verificar si todos los documentos están completos
        $docs_complete = true;
        $docs_status = '';
        if ($t['requires_docs']) {
          $total_docs = 4;
          $completed_docs = $t['doc_plan_prueba'] + $t['doc_plan_produccion'] + $t['doc_control_objeto'] + $t['doc_politica_respaldo'];
          $docs_complete = ($completed_docs == $total_docs);
          $docs_status = "📄 {$completed_docs}/{$total_docs} docs";
        }
        $can_deploy = !$t['requires_docs'] || $docs_complete;
      ?>
      <tr>
        <td data-label="Tarea">
          <strong style="display: block; margin-bottom: 2px;"><?= esc($t['title']) ?></strong>
          
          <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 6px; margin-top: 4px;">
            <!-- Fecha de creación -->
            <span class="small" style="color: var(--text-muted); white-space: nowrap;"><?= esc($t['created_at']) ?></span>
            
            <!-- Estado de documentos -->
            <?php if ($t['requires_docs']): ?>
              <span class="small" style="color: <?= $docs_complete ? 'var(--accent-green)' : 'var(--accent-yellow)' ?>; font-weight: 600; white-space: nowrap;">
                • <?= $docs_status ?>
              </span>
            <?php endif; ?>
          </div>
          
          <!-- Checkboxes de documentos -->
          <?php if ($t['requires_docs']): ?>
            <div style="margin-top: 6px; padding: 4px 6px; background: rgba(0,0,0,0.2); border-radius: 4px; font-size: 0.75rem; line-height: 1.3;">
              <form method="post" action="update_doc.php" style="margin: 0; display: flex; flex-wrap: wrap; gap: 4px 8px;">
                <input type="hidden" name="task_id" value="<?= $t['id'] ?>">
                <label style="display: inline-flex; align-items: center; cursor: pointer; white-space: nowrap;">
                  <input type="checkbox" name="doc_plan_prueba" value="1" <?= $t['doc_plan_prueba'] ? 'checked' : '' ?> onchange="this.form.submit()" style="margin: 0 3px 0 0;">
                  Prueba
                </label>
                <label style="display: inline-flex; align-items: center; cursor: pointer; white-space: nowrap;">
                  <input type="checkbox" name="doc_plan_produccion" value="1" <?= $t['doc_plan_produccion'] ? 'checked' : '' ?> onchange="this.form.submit()" style="margin: 0 3px 0 0;">
                  Producción
                </label>
                <label style="display: inline-flex; align-items: center; cursor: pointer; white-space: nowrap;">
                  <input type="checkbox" name="doc_control_objeto" value="1" <?= $t['doc_control_objeto'] ? 'checked' : '' ?> onchange="this.form.submit()" style="margin: 0 3px 0 0;">
                  Control
                </label>
                <label style="display: inline-flex; align-items: center; cursor: pointer; white-space: nowrap;">
                  <input type="checkbox" name="doc_politica_respaldo" value="1" <?= $t['doc_politica_respaldo'] ? 'checked' : '' ?> onchange="this.form.submit()" style="margin: 0 3px 0 0;">
                  Respaldo
                </label>
              </form>
            </div>
          <?php endif; ?>
        </td>
        <td data-label="⚡ Estado">
          <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
            <!-- Badge de urgencia -->
            <?php if ($t['urgency'] === 'Alta'): ?><span class="badge alta">Alta</span>
            <?php elseif ($t['urgency'] === 'Media'): ?><span class="badge media">Media</span>
            <?php else: ?><span class="badge baja">Baja</span><?php endif; ?>
            
            <!-- Estado de producción -->
            <?php if ($t['deployed']): ?>
              <span class="badge" style="background: var(--accent-green); font-size: 0.75rem; padding: 4px 8px;">✓ Prod</span>
            <?php endif; ?>
          </div>
        </td>
        <td data-label="Descripción"><?= nl2br(esc($t['description'])) ?></td>
        <td data-label="📅 Vence"><?= esc($t['due_date']) ?: '-' ?></td>
        <td data-label="Acciones">
          <?php if (!$t['deployed']): ?>
            <?php if ($can_deploy): ?>
              <a class="btn" href="mark_deployed.php?id=<?= $t['id'] ?>">✅ Producción</a>
            <?php else: ?>
              <a class="btn" href="edit.php?id=<?= $t['id'] ?>" style="background: var(--accent-yellow); color: #000;" title="Completa los documentos primero">
                📋 <?= $completed_docs ?>/4
              </a>
            <?php endif; ?>
          <?php endif; ?>
          <a class="btn btn-icon" href="edit.php?id=<?= $t['id'] ?>" title="Editar">✏️</a>
          <a class="btn btn-icon red" href="delete.php?id=<?= $t['id'] ?>" onclick="return confirm('¿Eliminar esta tarea?')" title="Eliminar">🗑️</a>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>

  <!-- Modal para crear tarea -->
  <div id="taskModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2 style="margin: 0;">➕ Nueva Tarea</h2>
        <button class="modal-close" onclick="closeModal()">&times;</button>
      </div>
      <form action="add.php" method="post" id="taskForm">
        <label>Título de la tarea</label>
        <input type="text" name="title" required placeholder="Ej: Implementar nueva funcionalidad">
        <label>Descripción</label>
        <textarea name="description" rows="3" placeholder="Detalles importantes..."></textarea>
        <label>⚡ Urgencia</label>
        <select name="urgency">
          <option value="Baja">Baja</option>
          <option value="Media" selected>Media</option>
          <option value="Alta">Alta</option>
        </select>
        <label>📅 Fecha límite (opcional)</label>
        <input type="date" name="due_date">
        
        <div style="margin-top: 16px; padding: 14px; background: var(--bg-input); border-radius: var(--radius-sm); border: 2px solid var(--border-color);">
          <label style="margin-bottom: 10px;">
            <input type="checkbox" name="requires_docs" value="1" id="requiresDocs" onchange="toggleDocuments()">
            <strong>📋 Requiere documentos obligatorios</strong>
          </label>
          
          <div id="documentsSection" style="display: none; margin-top: 10px; padding: 10px; background: rgba(0,0,0,0.2); border-radius: 6px;">
            <p style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 8px;">
              Marcar documentos que ya están completos:
            </p>
            <label style="font-size: 0.9rem;"><input type="checkbox" name="doc_plan_prueba" value="1"> Plan de Prueba</label>
            <label style="font-size: 0.9rem;"><input type="checkbox" name="doc_plan_produccion" value="1"> Plan de Producción</label>
            <label style="font-size: 0.9rem;"><input type="checkbox" name="doc_control_objeto" value="1"> Control de Objeto</label>
            <label style="font-size: 0.9rem;"><input type="checkbox" name="doc_politica_respaldo" value="1"> Política de Respaldo</label>
          </div>
        </div>
        
        <label style="margin-top: 12px;"><input type="checkbox" name="deployed" value="1" id="deployedCheck"> Ya está en producción</label>
        <div style="margin-top:20px; display: flex; gap: 10px;">
          <button class="btn" type="submit">✅ Crear Tarea</button>
          <button class="btn red" type="button" onclick="closeModal()">Cancelar</button>
        </div>
      </form>
    </div>
  </div>

<script>
function openModal() {
  document.getElementById('taskModal').style.display = 'flex';
}

function closeModal() {
  document.getElementById('taskModal').style.display = 'none';
  document.getElementById('taskForm').reset();
  document.getElementById('documentsSection').style.display = 'none';
}

// Cerrar modal al hacer clic fuera
window.onclick = function(event) {
  const modal = document.getElementById('taskModal');
  if (event.target === modal) {
    closeModal();
  }
}

function toggleDocuments() {
  const requiresDocs = document.getElementById('requiresDocs');
  const docsSection = document.getElementById('documentsSection');
  const docInputs = docsSection.querySelectorAll('input[type="checkbox"]');
  
  if (requiresDocs.checked) {
    docsSection.style.display = 'block';
  } else {
    docsSection.style.display = 'none';
    // Desmarcar todos los documentos si se desactiva
    docInputs.forEach(input => input.checked = false);
  }
}
</script>

</div>

<!-- Botón flotante para móvil (solo visible en móvil) -->
<button class="fab-mobile" onclick="openModal()" style="display: none;" title="Crear nueva tarea">
  ➕
</button>

</body>
</html>
