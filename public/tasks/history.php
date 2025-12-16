<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../services/TaskService.php';

$pdo = get_pdo();
$user_id = get_current_user_id();
$username = get_current_username();

$task_id = intval($_GET['id'] ?? 0);
if ($task_id <= 0) {
    header('Location: ../index.php');
    exit;
}

// Verificar que la tarea pertenece al usuario usando servicio
$task = getTaskById($pdo, $task_id, $user_id);

if (!$task) {
    header('Location: ../index.php');
    exit;
}

// Obtener historial usando servicio
$history = getTaskHistory($pdo, $task_id);

function esc($s) { 
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); 
}

function format_action($action) {
    $actions = [
        'created' => '➕ Creada',
        'updated' => '✏️ Modificada',
        'deployed' => '✅ Desplegada',
        'reverted' => '↩️ Revertida',
        'deleted' => '🗑️ Eliminada'
    ];
    return $actions[$action] ?? $action;
}

?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">
  <title>Historial - <?= esc($task['title']) ?></title>
  <link rel="stylesheet" href="../../assets/style.css">
</head>
<body>
<div class="container">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
    <div>
      <h1>📜 Historial de Tarea</h1>
      <p class="subtitle" style="color: var(--text-secondary); font-size: 1.1rem; margin-top: -12px; margin-bottom: 0; font-weight: 400;">
        <?= esc($task['title']) ?>
      </p>
    </div>
    <div style="display: flex; gap: 10px;">
      <a class="btn" href="edit.php?id=<?= $task_id ?>" title="Volver a la tarea">
        ← Volver
      </a>
      <a class="btn red" href="../auth/logout.php" style="padding: 10px 20px; font-size: 0.9rem;" title="Cerrar sesión">
        🚪 Salir
      </a>
    </div>
  </div>

  <!-- Información de la tarea -->
  <div style="background: var(--bg-input); padding: 20px; border-radius: var(--radius-md); margin-bottom: 24px; border: 2px solid var(--border-color);">
    <h3 style="margin-top: 0; color: var(--accent-blue);">📋 Información Actual</h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
      <div>
        <div style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 4px;">Estado</div>
        <div style="font-weight: bold;">
          <?= $task['deployed'] ? '✅ Desplegado' : '⏳ Pendiente' ?>
        </div>
      </div>
      <div>
        <div style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 4px;">Urgencia</div>
        <div style="font-weight: bold;"><?= esc($task['urgency']) ?></div>
      </div>
      <?php if (!empty($task['priority'])): ?>
      <div>
        <div style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 4px;">Prioridad</div>
        <div style="font-weight: bold;"><?= esc($task['priority']) ?></div>
      </div>
      <?php endif; ?>
      <?php if (!empty($task['category'])): ?>
      <div>
        <div style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 4px;">Categoría</div>
        <div style="font-weight: bold;"><?= esc($task['category']) ?></div>
      </div>
      <?php endif; ?>
      <?php if ($task['due_date']): ?>
      <div>
        <div style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 4px;">Fecha Límite</div>
        <div style="font-weight: bold;"><?= esc($task['due_date']) ?></div>
      </div>
      <?php endif; ?>
      <?php if ($task['deployed'] && $task['deployed_at']): ?>
      <div>
        <div style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 4px;">Desplegado</div>
        <div style="font-weight: bold;"><?= esc($task['deployed_at']) ?></div>
      </div>
      <?php endif; ?>
    </div>
    
    <?php if ($task['deployed'] && !empty($task['deployment_notes'])): ?>
      <div style="margin-top: 16px; padding: 12px; background: rgba(0,0,0,0.2); border-radius: 6px;">
        <div style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 4px;">📝 Notas del Deployment</div>
        <div><?= nl2br(esc($task['deployment_notes'])) ?></div>
      </div>
    <?php endif; ?>
  </div>

  <!-- Línea de tiempo del historial -->
  <h3 style="color: var(--accent-blue); margin-bottom: 16px;">⏱️ Línea de Tiempo</h3>
  
  <?php if (empty($history)): ?>
    <p style="color: var(--text-secondary); text-align: center; padding: 40px;">
      No hay historial registrado para esta tarea.
    </p>
  <?php else: ?>
    <div style="position: relative;">
      <!-- Línea vertical -->
      <div style="position: absolute; left: 20px; top: 0; bottom: 0; width: 2px; background: var(--border-color);"></div>
      
      <?php foreach ($history as $entry): ?>
        <div style="position: relative; margin-bottom: 24px; padding-left: 60px;">
          <!-- Punto en la línea -->
          <div style="position: absolute; left: 11px; top: 8px; width: 20px; height: 20px; border-radius: 50%; background: var(--accent-blue); border: 3px solid var(--bg-card); box-shadow: 0 0 0 2px var(--accent-blue);"></div>
          
          <!-- Contenido -->
          <div style="background: var(--bg-input); padding: 16px; border-radius: var(--radius-md); border: 2px solid var(--border-color);">
            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 8px; flex-wrap: wrap; gap: 8px;">
              <div>
                <div style="font-weight: bold; font-size: 1.1rem; color: var(--accent-blue);">
                  <?= format_action($entry['action']) ?>
                </div>
                <div style="color: var(--text-secondary); font-size: 0.85rem;">
                  por <strong><?= esc($entry['username']) ?></strong>
                </div>
              </div>
              <div style="color: var(--text-muted); font-size: 0.85rem;">
                <?= esc($entry['created_at']) ?>
              </div>
            </div>
            
            <?php if (!empty($entry['old_values']) || !empty($entry['new_values'])): ?>
              <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 12px;">
                <?php if (!empty($entry['old_values'])): ?>
                  <div>
                    <div style="color: var(--accent-red); font-size: 0.85rem; font-weight: bold; margin-bottom: 4px;">❌ Valores Anteriores</div>
                    <pre style="background: rgba(0,0,0,0.3); padding: 8px; border-radius: 4px; font-size: 0.85rem; overflow-x: auto; margin: 0;"><?= esc($entry['old_values']) ?></pre>
                  </div>
                <?php endif; ?>
                
                <?php if (!empty($entry['new_values'])): ?>
                  <div>
                    <div style="color: var(--accent-green); font-size: 0.85rem; font-weight: bold; margin-bottom: 4px;">✅ Valores Nuevos</div>
                    <pre style="background: rgba(0,0,0,0.3); padding: 8px; border-radius: 4px; font-size: 0.85rem; overflow-x: auto; margin: 0;"><?= esc($entry['new_values']) ?></pre>
                  </div>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
      
      <!-- Punto de creación -->
      <div style="position: relative; padding-left: 60px;">
        <div style="position: absolute; left: 11px; top: 8px; width: 20px; height: 20px; border-radius: 50%; background: var(--accent-green); border: 3px solid var(--bg-card); box-shadow: 0 0 0 2px var(--accent-green);"></div>
        <div style="background: var(--bg-input); padding: 16px; border-radius: var(--radius-md); border: 2px solid var(--accent-green);">
          <div style="font-weight: bold; font-size: 1.1rem; color: var(--accent-green);">
            🎉 Tarea Creada
          </div>
          <div style="color: var(--text-muted); font-size: 0.85rem; margin-top: 4px;">
            <?= esc($task['created_at']) ?>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>
</body>
</html>
