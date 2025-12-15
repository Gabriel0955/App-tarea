<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/auth.php';

$pdo = get_pdo();
$user_id = get_current_user_id();
$username = get_current_username();

// Estadísticas del dashboard
$stats_stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN deployed = 0 THEN 1 ELSE 0 END) as pendientes,
        SUM(CASE WHEN deployed = 1 THEN 1 ELSE 0 END) as desplegados,
        SUM(CASE WHEN urgency = 'Alta' AND deployed = 0 THEN 1 ELSE 0 END) as urgentes,
        SUM(CASE WHEN due_date IS NOT NULL AND due_date < CURRENT_DATE AND deployed = 0 THEN 1 ELSE 0 END) as vencidos,
        SUM(CASE WHEN due_date IS NOT NULL AND due_date BETWEEN CURRENT_DATE AND CURRENT_DATE + INTERVAL '7 days' AND deployed = 0 THEN 1 ELSE 0 END) as proximos
    FROM tasks WHERE user_id = ?
");
$stats_stmt->execute([$user_id]);
$stats = $stats_stmt->fetch();

// Filtros y búsqueda
$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? '';
$category = $_GET['category'] ?? '';
$priority = $_GET['priority'] ?? '';

$sql = 'SELECT * FROM tasks WHERE user_id = ?';
$params = [$user_id];

if ($search) {
    $sql .= ' AND (title ILIKE ? OR description ILIKE ?)';
    $search_param = '%' . $search . '%';
    $params[] = $search_param;
    $params[] = $search_param;
}

if ($filter === 'pending') {
    $sql .= ' AND deployed = 0';
} elseif ($filter === 'deployed') {
    $sql .= ' AND deployed = 1';
} elseif ($filter === 'urgent') {
    $sql .= ' AND urgency = \'Alta\' AND deployed = 0';
} elseif ($filter === 'overdue') {
    $sql .= ' AND due_date < CURRENT_DATE AND deployed = 0';
}

if ($category && $category !== 'all') {
    $sql .= ' AND category = ?';
    $params[] = $category;
}

if ($priority && $priority !== 'all') {
    $sql .= ' AND priority = ?';
    $params[] = $priority;
}

$sql .= ' ORDER BY created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
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

  <!-- Dashboard de Estadísticas -->
  <div class="dashboard-stats">
    <div class="stat-card">
      <div class="stat-icon">📊</div>
      <div class="stat-info">
        <div class="stat-value"><?= $stats['total'] ?></div>
        <div class="stat-label">Total Tareas</div>
      </div>
    </div>
    <div class="stat-card pending">
      <div class="stat-icon">⏳</div>
      <div class="stat-info">
        <div class="stat-value"><?= $stats['pendientes'] ?></div>
        <div class="stat-label">Pendientes</div>
      </div>
    </div>
    <div class="stat-card deployed">
      <div class="stat-icon">✅</div>
      <div class="stat-info">
        <div class="stat-value"><?= $stats['desplegados'] ?></div>
        <div class="stat-label">Desplegados</div>
      </div>
    </div>
    <div class="stat-card urgent">
      <div class="stat-icon">🔥</div>
      <div class="stat-info">
        <div class="stat-value"><?= $stats['urgentes'] ?></div>
        <div class="stat-label">Urgentes</div>
      </div>
    </div>
    <div class="stat-card overdue">
      <div class="stat-icon">⚠️</div>
      <div class="stat-info">
        <div class="stat-value"><?= $stats['vencidos'] ?></div>
        <div class="stat-label">Vencidos</div>
      </div>
    </div>
    <div class="stat-card upcoming">
      <div class="stat-icon">📅</div>
      <div class="stat-info">
        <div class="stat-value"><?= $stats['proximos'] ?></div>
        <div class="stat-label">Esta Semana</div>
      </div>
    </div>
  </div>

  <!-- Barra de búsqueda y filtros -->
  <div class="filters-section">
    <form method="get" action="index.php" style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
      <input type="text" name="search" placeholder="🔍 Buscar tareas..." value="<?= esc($search) ?>" style="flex: 1; min-width: 200px; padding: 10px; border-radius: 8px; border: 2px solid var(--border-color); background: var(--bg-input); color: var(--text-color);">
      
      <select name="filter" style="padding: 10px; border-radius: 8px; border: 2px solid var(--border-color); background: var(--bg-input); color: var(--text-color);">
        <option value="">📋 Todas</option>
        <option value="pending" <?= $filter === 'pending' ? 'selected' : '' ?>>⏳ Pendientes</option>
        <option value="deployed" <?= $filter === 'deployed' ? 'selected' : '' ?>>✅ Desplegados</option>
        <option value="urgent" <?= $filter === 'urgent' ? 'selected' : '' ?>>🔥 Urgentes</option>
        <option value="overdue" <?= $filter === 'overdue' ? 'selected' : '' ?>>⚠️ Vencidos</option>
      </select>

      <select name="category" style="padding: 10px; border-radius: 8px; border: 2px solid var(--border-color); background: var(--bg-input); color: var(--text-color);">
        <option value="all">🏷️ Categoría</option>
        <option value="Frontend" <?= $category === 'Frontend' ? 'selected' : '' ?>>Frontend</option>
        <option value="Backend" <?= $category === 'Backend' ? 'selected' : '' ?>>Backend</option>
        <option value="Database" <?= $category === 'Database' ? 'selected' : '' ?>>Database</option>
        <option value="Hotfix" <?= $category === 'Hotfix' ? 'selected' : '' ?>>Hotfix</option>
        <option value="Feature" <?= $category === 'Feature' ? 'selected' : '' ?>>Feature</option>
        <option value="Otro" <?= $category === 'Otro' ? 'selected' : '' ?>>Otro</option>
      </select>

      <select name="priority" style="padding: 10px; border-radius: 8px; border: 2px solid var(--border-color); background: var(--bg-input); color: var(--text-color);">
        <option value="all">⚡ Prioridad</option>
        <option value="Crítico" <?= $priority === 'Crítico' ? 'selected' : '' ?>>🔴 Crítico</option>
        <option value="Alto" <?= $priority === 'Alto' ? 'selected' : '' ?>>🟠 Alto</option>
        <option value="Medio" <?= $priority === 'Medio' ? 'selected' : '' ?>>🟡 Medio</option>
        <option value="Bajo" <?= $priority === 'Bajo' ? 'selected' : '' ?>>🟢 Bajo</option>
      </select>

      <button type="submit" class="btn" style="padding: 10px 20px;">Filtrar</button>
      <a href="index.php" class="btn" style="padding: 10px 20px; background: var(--bg-secondary);">Limpiar</a>
    </form>
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
    <a class="btn" href="calendar.php" title="Ver calendario de deployments">
      <span style="font-size: 1.2rem;">📅</span>
      <span class="btn-text">Calendario</span>
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
      <tr><th>Tarea</th><th>🏷️ Info</th><th>⚡ Estado</th><th>Descripción</th><th>📅 Vence</th><th>Acciones</th></tr>
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
        
        // Verificar si está vencida
        $is_overdue = $t['due_date'] && strtotime($t['due_date']) < time() && !$t['deployed'];
      ?>
      <tr <?= $is_overdue ? 'style="background: rgba(255, 107, 107, 0.1);"' : '' ?>>
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
        <td data-label="🏷️ Info">
          <div style="display: flex; flex-direction: column; gap: 6px;">
            <?php if (!empty($t['category'])): ?>
              <span class="badge" style="background: var(--accent-blue); font-size: 0.75rem; padding: 4px 8px;">
                <?= esc($t['category']) ?>
              </span>
            <?php endif; ?>
            <?php if (!empty($t['priority'])): ?>
              <?php
                $priority_colors = [
                  'Crítico' => 'background: #ff4757; color: white;',
                  'Alto' => 'background: #ffa502; color: white;',
                  'Medio' => 'background: #f1c40f; color: #000;',
                  'Bajo' => 'background: #2ed573; color: white;'
                ];
                $priority_style = $priority_colors[$t['priority']] ?? 'background: var(--bg-secondary);';
              ?>
              <span class="badge" style="<?= $priority_style ?> font-size: 0.75rem; padding: 4px 8px;">
                <?= esc($t['priority']) ?>
              </span>
            <?php endif; ?>
          </div>
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
        <td data-label="📅 Vence">
          <?php if ($t['due_date']): ?>
            <span style="<?= $is_overdue ? 'color: var(--accent-red); font-weight: bold;' : '' ?>">
              <?= esc($t['due_date']) ?>
              <?= $is_overdue ? ' ⚠️' : '' ?>
            </span>
          <?php else: ?>
            -
          <?php endif; ?>
        </td>
        <td data-label="Acciones">
          <?php if (!$t['deployed']): ?>
            <?php if ($can_deploy): ?>
              <a class="btn" href="#" onclick="openDeployModal(<?= $t['id'] ?>); return false;">✅ Producción</a>
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
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
          <div>
            <label>🏷️ Categoría</label>
            <select name="category">
              <option value="Otro">Otro</option>
              <option value="Frontend">Frontend</option>
              <option value="Backend">Backend</option>
              <option value="Database">Database</option>
              <option value="Hotfix">Hotfix</option>
              <option value="Feature">Feature</option>
            </select>
          </div>
          <div>
            <label>⚡ Prioridad</label>
            <select name="priority">
              <option value="Bajo">🟢 Bajo</option>
              <option value="Medio" selected>🟡 Medio</option>
              <option value="Alto">🟠 Alto</option>
              <option value="Crítico">🔴 Crítico</option>
            </select>
          </div>
        </div>
        
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

  <!-- Modal para marcar como desplegado con checklist -->
  <div id="deployModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2 style="margin: 0;">✅ Marcar como Desplegado</h2>
        <button class="modal-close" onclick="closeDeployModal()">&times;</button>
      </div>
      <form id="deployForm" method="post" action="mark_deployed.php">
        <input type="hidden" name="id" id="deployTaskId">
        
        <div style="background: var(--bg-input); padding: 16px; border-radius: 8px; margin-bottom: 16px;">
          <h3 style="margin: 0 0 12px 0; color: var(--accent-blue);">📋 Checklist Pre-Deployment</h3>
          <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 12px;">
            Verifica que todo esté listo antes de marcar como desplegado:
          </p>
          
          <label style="display: flex; align-items: center; padding: 8px; background: rgba(0,0,0,0.2); border-radius: 6px; margin-bottom: 8px; cursor: pointer;">
            <input type="checkbox" name="checklist_backup" value="1" required style="margin-right: 10px; width: 18px; height: 18px;">
            <span>💾 Backup realizado</span>
          </label>
          
          <label style="display: flex; align-items: center; padding: 8px; background: rgba(0,0,0,0.2); border-radius: 6px; margin-bottom: 8px; cursor: pointer;">
            <input type="checkbox" name="checklist_tests" value="1" required style="margin-right: 10px; width: 18px; height: 18px;">
            <span>🧪 Tests ejecutados</span>
          </label>
          
          <label style="display: flex; align-items: center; padding: 8px; background: rgba(0,0,0,0.2); border-radius: 6px; margin-bottom: 8px; cursor: pointer;">
            <input type="checkbox" name="checklist_docs" value="1" required style="margin-right: 10px; width: 18px; height: 18px;">
            <span>📚 Documentación actualizada</span>
          </label>
          
          <label style="display: flex; align-items: center; padding: 8px; background: rgba(0,0,0,0.2); border-radius: 6px; cursor: pointer;">
            <input type="checkbox" name="checklist_team" value="1" required style="margin-right: 10px; width: 18px; height: 18px;">
            <span>👥 Equipo notificado</span>
          </label>
        </div>
        
        <label>⏱️ Tiempo de deployment (minutos - opcional)</label>
        <input type="number" name="deployment_duration" min="1" placeholder="Ej: 15">
        
        <label>📝 Notas del deployment (opcional)</label>
        <textarea name="deployment_notes" rows="3" placeholder="¿Hubo algún problema? ¿Qué se desplegó exactamente?"></textarea>
        
        <div style="margin-top:20px; display: flex; gap: 10px;">
          <button class="btn" type="submit">✅ Confirmar Deployment</button>
          <button class="btn red" type="button" onclick="closeDeployModal()">Cancelar</button>
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

function openDeployModal(taskId) {
  document.getElementById('deployTaskId').value = taskId;
  document.getElementById('deployModal').style.display = 'flex';
}

function closeDeployModal() {
  document.getElementById('deployModal').style.display = 'none';
  document.getElementById('deployForm').reset();
}

// Cerrar modal al hacer clic fuera
window.onclick = function(event) {
  const taskModal = document.getElementById('taskModal');
  const deployModal = document.getElementById('deployModal');
  if (event.target === taskModal) {
    closeModal();
  }
  if (event.target === deployModal) {
    closeDeployModal();
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
