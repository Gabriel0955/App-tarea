<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/auth.php';

$user_id = get_current_user_id();

// Manejar POST del modal con checklist
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) { header('Location: index.php'); exit; }
    
    $pdo = get_pdo();
    
    // Verificar propiedad de la tarea
    $stmt = $pdo->prepare('SELECT requires_docs, doc_plan_prueba, doc_plan_produccion, doc_control_objeto, doc_politica_respaldo FROM tasks WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, $user_id]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$task) {
        header('Location: index.php'); 
        exit;
    }
    
    // Si requiere documentos, verificar que todos estén completos
    if ($task['requires_docs']) {
        $all_docs_complete = $task['doc_plan_prueba'] && 
                           $task['doc_plan_produccion'] && 
                           $task['doc_control_objeto'] && 
                           $task['doc_politica_respaldo'];
        
        if (!$all_docs_complete) {
            header('Location: edit.php?id=' . $id . '&error=docs_incompletos');
            exit;
        }
    }
    
    // Obtener datos del checklist y notas
    $checklist_backup = isset($_POST['checklist_backup']) ? 1 : 0;
    $checklist_tests = isset($_POST['checklist_tests']) ? 1 : 0;
    $checklist_docs = isset($_POST['checklist_docs']) ? 1 : 0;
    $checklist_team = isset($_POST['checklist_team']) ? 1 : 0;
    $deployment_duration = intval($_POST['deployment_duration'] ?? 0) ?: null;
    $deployment_notes = trim($_POST['deployment_notes'] ?? '');
    
    // Marcar como desplegado con toda la información
    $stmt = $pdo->prepare('UPDATE tasks SET deployed = 1, deployed_at = NOW(), deployed_by = ?, deployment_notes = ?, deployment_duration = ?, checklist_backup = ?, checklist_tests = ?, checklist_docs = ?, checklist_team = ? WHERE id = ? AND user_id = ?');
    $stmt->execute([$user_id, $deployment_notes, $deployment_duration, $checklist_backup, $checklist_tests, $checklist_docs, $checklist_team, $id, $user_id]);
    
    // Registrar en el historial
    $stmt = $pdo->prepare('INSERT INTO task_history (task_id, user_id, action, new_values) VALUES (?, ?, ?, ?)');
    $history_data = json_encode([
        'deployed' => 1,
        'deployed_at' => date('Y-m-d H:i:s'),
        'deployment_duration' => $deployment_duration,
        'deployment_notes' => $deployment_notes,
        'checklist' => [
            'backup' => $checklist_backup,
            'tests' => $checklist_tests,
            'docs' => $checklist_docs,
            'team' => $checklist_team
        ]
    ]);
    $stmt->execute([$id, $user_id, 'deployed', $history_data]);
    
    header('Location: index.php'); 
    exit;
}

// Manejar GET legacy (sin modal) - redirigir a index para usar modal
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: index.php'); exit; }

header('Location: index.php#deploy-' . $id); 
exit;
