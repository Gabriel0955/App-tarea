<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/auth.php';

$pdo = get_pdo();
$user_id = get_current_user_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $urgency = $_POST['urgency'] ?? 'Media';
    $due = $_POST['due_date'] ?: null;
    $deployed = isset($_POST['deployed']) && $_POST['deployed'] == '1' ? 1 : 0;
    
    // Capturar documentos
    $requires_docs = isset($_POST['requires_docs']) ? 1 : 0;
    $doc_plan_prueba = isset($_POST['doc_plan_prueba']) ? 1 : 0;
    $doc_plan_produccion = isset($_POST['doc_plan_produccion']) ? 1 : 0;
    $doc_control_objeto = isset($_POST['doc_control_objeto']) ? 1 : 0;
    $doc_politica_respaldo = isset($_POST['doc_politica_respaldo']) ? 1 : 0;

    if ($title === '') {
    header('Location: index.php?error=empty'); exit;
    }

    $stmt = $pdo->prepare('UPDATE tasks SET title = ?, description = ?, urgency = ?, due_date = ?, deployed = ?, requires_docs = ?, doc_plan_prueba = ?, doc_plan_produccion = ?, doc_control_objeto = ?, doc_politica_respaldo = ? WHERE id = ? AND user_id = ?');
    $stmt->execute([$title, $description, $urgency, $due, $deployed, $requires_docs, $doc_plan_prueba, $doc_plan_produccion, $doc_control_objeto, $doc_politica_respaldo, $id, $user_id]);
  header('Location: index.php'); exit;
}

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: index.php'); exit; }

$stmt = $pdo->prepare('SELECT * FROM tasks WHERE id = ? AND user_id = ?');
$stmt->execute([$id, $user_id]);
$task = $stmt->fetch();
if (!$task) { header('Location: index.php'); exit; }

function esc($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">
  <title>Editar tarea | App-Tareas</title>
  <link rel="stylesheet" href="../assets/style.css">
  <meta name="theme-color" content="#1e2139">
  <script>
    function toggleDocuments() {
      const checkbox = document.getElementById('requiresDocs');
      const section = document.getElementById('documentsSection');
      
      if (checkbox.checked) {
        section.style.display = 'block';
      } else {
        section.style.display = 'none';
        // Desmarcar todos los documentos si se deselecciona
        document.querySelectorAll('#documentsSection input[type="checkbox"]').forEach(cb => cb.checked = false);
      }
    }
  </script>
</head>
<body>
<div class="container">
  <h1>✏️ Editar tarea</h1>
  
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
      <a class="btn red" href="index.php">✖️ Cancelar</a>
    </div>
  </form>
</div>
</body>
</html>
