<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../services/TaskService.php';
require_once __DIR__ . '/../../src/theme.php';

// Verificar permiso de edición
require_permission('tasks', 'update');

$pdo = get_pdo();
$user_id = get_current_user_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $urgency = $_POST['urgency'] ?? 'Media';
    $priority = $_POST['priority'] ?? 'Medio';
    $category = $_POST['category'] ?? 'Otro';
    $due = $_POST['due_date'] ?: null;
    $deployed = isset($_POST['deployed']) && $_POST['deployed'] == '1' ? 1 : 0;
    
    // Capturar documentos
    $requires_docs = isset($_POST['requires_docs']) ? 1 : 0;
    $doc_plan_prueba = isset($_POST['doc_plan_prueba']) ? 1 : 0;
    $doc_plan_produccion = isset($_POST['doc_plan_produccion']) ? 1 : 0;
    $doc_control_objeto = isset($_POST['doc_control_objeto']) ? 1 : 0;
    $doc_politica_respaldo = isset($_POST['doc_politica_respaldo']) ? 1 : 0;

    if ($title === '') {
        header('Location: ../index.php?error=empty'); exit;
    }

    // Obtener valores anteriores para el historial usando servicio
    $old_task = getTaskById($pdo, $id, $user_id);
    
    // Actualizar tarea usando servicio
    $task_data = [
        'title' => $title,
        'description' => $description,
        'urgency' => $urgency,
        'priority' => $priority,
        'category' => $category,
        'due_date' => $due,
        'deployed' => $deployed,
        'requires_docs' => $requires_docs,
        'doc_plan_prueba' => $doc_plan_prueba,
        'doc_plan_produccion' => $doc_plan_produccion,
        'doc_control_objeto' => $doc_control_objeto,
        'doc_politica_respaldo' => $doc_politica_respaldo
    ];
    updateTask($pdo, $id, $user_id, $task_data);
    
    // Registrar en historial si hubo cambios
    $changes = [];
    if ($old_task['title'] !== $title) $changes['title'] = ['old' => $old_task['title'], 'new' => $title];
    if ($old_task['description'] !== $description) $changes['description'] = ['old' => $old_task['description'], 'new' => $description];
    if ($old_task['urgency'] !== $urgency) $changes['urgency'] = ['old' => $old_task['urgency'], 'new' => $urgency];
    if ($old_task['priority'] !== $priority) $changes['priority'] = ['old' => $old_task['priority'], 'new' => $priority];
    if ($old_task['category'] !== $category) $changes['category'] = ['old' => $old_task['category'], 'new' => $category];
    if ($old_task['deployed'] != $deployed) $changes['deployed'] = ['old' => $old_task['deployed'], 'new' => $deployed];
    
    if (!empty($changes)) {
        addTaskHistory($pdo, $id, $user_id, 'updated', $old_task, $changes);
    }
    
    header('Location: ../index.php'); exit;
}

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: ../index.php'); exit; }

// Obtener tarea usando servicio
$task = getTaskById($pdo, $id, $user_id);
if (!$task) { header('Location: ../index.php'); exit; }

function esc($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

?>
<!doctype html>
<html lang="es">
<head>
  <?php echo getThemeStyles(); ?>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">
  <title>Editar tarea | App-Tareas</title>
  <link rel="stylesheet" href="../../assets/style.css">
  <link rel="stylesheet" href="../../assets/css/pages/edit.css">
  <meta name="theme-color" content="#1e2139">
  <script defer src="../../assets/js/pages/edit.js"></script>
</head>
<body>
<div class="container">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
    <h1 style="margin: 0;">✏️ Editar tarea</h1>
    <a class="btn" href="history.php?id=<?= $task['id'] ?>" style="background: var(--accent-purple);" title="Ver historial de cambios">
      📜 Historial
    </a>
  </div>
  
  <?php if (isset($_GET['error']) && $_GET['error'] === 'docs_incompletos'): ?>
    <div style="background: var(--accent-red); color: white; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-weight: 600; font-size: 0.95rem;">
      ⚠️ Faltan documentos: debes completar los 4 antes de pasar a producción
    </div>
  <?php endif; ?>
  
  <form method="post" action="edit.php">
    <input type="hidden" name="id" value="<?= esc($task['id']) ?>">
    <label>Título de la tarea</label>
    <input type="text" name="title" value="<?= esc($task['title']) ?>" required>
    
    <label>Descripción</label>
    <textarea name="description" rows="3"><?= esc($task['description']) ?></textarea>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
      <div>
        <label>🏷️ Categoría</label>
        <select name="category">
          <option value="Otro" <?= $task['category']==='Otro' ? 'selected' : '' ?>>Otro</option>
          <option value="Frontend" <?= $task['category']==='Frontend' ? 'selected' : '' ?>>Frontend</option>
          <option value="Backend" <?= $task['category']==='Backend' ? 'selected' : '' ?>>Backend</option>
          <option value="Database" <?= $task['category']==='Database' ? 'selected' : '' ?>>Database</option>
          <option value="Hotfix" <?= $task['category']==='Hotfix' ? 'selected' : '' ?>>Hotfix</option>
          <option value="Feature" <?= $task['category']==='Feature' ? 'selected' : '' ?>>Feature</option>
        </select>
      </div>
      <div>
        <label>⚡ Prioridad</label>
        <select name="priority">
          <option value="Bajo" <?= $task['priority']==='Bajo' ? 'selected' : '' ?>>🟢 Bajo</option>
          <option value="Medio" <?= $task['priority']==='Medio' ? 'selected' : '' ?>>🟡 Medio</option>
          <option value="Alto" <?= $task['priority']==='Alto' ? 'selected' : '' ?>>🟠 Alto</option>
          <option value="Crítico" <?= $task['priority']==='Crítico' ? 'selected' : '' ?>>🔴 Crítico</option>
        </select>
      </div>
    </div>
    
    <label>⚡ Urgencia</label>
    <select name="urgency">
      <option value="Baja" <?= $task['urgency']==='Baja' ? 'selected' : '' ?>>Baja</option>
      <option value="Media" <?= $task['urgency']==='Media' ? 'selected' : '' ?>>Media</option>
      <option value="Alta" <?= $task['urgency']==='Alta' ? 'selected' : '' ?>>Alta</option>
    </select>
    
    <label>📅 Fecha límite (opcional)</label>
    <input type="date" name="due_date" value="<?= esc($task['due_date']) ?>">
    
    <label style="margin-top: 12px;">
      <input type="checkbox" name="requires_docs" value="1" id="requiresDocs" onchange="toggleDocuments()" <?= $task['requires_docs'] ? 'checked' : '' ?>>
      <strong>📋 Requiere documentos obligatorios</strong>
    </label>
    
    <div id="documentsSection" style="display: <?= $task['requires_docs'] ? 'block' : 'none' ?>; background: rgba(0,0,0,0.2); padding: 12px; border-radius: 6px; margin: 10px 0;">
      <p style="margin: 0 0 8px 0; color: var(--text-secondary); font-size: 0.85rem;">
        Marcar documentos completos:
      </p>
      <label style="display: block; margin: 6px 0; font-size: 0.9rem;">
        <input type="checkbox" name="doc_plan_prueba" value="1" <?= $task['doc_plan_prueba'] ? 'checked' : '' ?>>
        Plan de Prueba
      </label>
      <label style="display: block; margin: 6px 0; font-size: 0.9rem;">
        <input type="checkbox" name="doc_plan_produccion" value="1" <?= $task['doc_plan_produccion'] ? 'checked' : '' ?>>
        Plan de Producción
      </label>
      <label style="display: block; margin: 6px 0; font-size: 0.9rem;">
        <input type="checkbox" name="doc_control_objeto" value="1" <?= $task['doc_control_objeto'] ? 'checked' : '' ?>>
        Control de Objeto
      </label>
      <label style="display: block; margin: 6px 0; font-size: 0.9rem;">
        <input type="checkbox" name="doc_politica_respaldo" value="1" <?= $task['doc_politica_respaldo'] ? 'checked' : '' ?>>
        Política de Respaldo
      </label>
    </div>
    
    <label style="margin-top: 12px;"><input type="checkbox" name="deployed" value="1" <?= $task['deployed'] ? 'checked' : '' ?>> Ya está en producción</label>
    <div style="margin-top: 16px; display: flex; gap: 10px;">
      <button class="btn" type="submit">💾 Guardar</button>
      <a class="btn red" href="../index.php">✖️ Cancelar</a>
    </div>
  </form>
</div>
</body>
</html>
