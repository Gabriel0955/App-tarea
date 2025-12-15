<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php'); exit;
}

$user_id = get_current_user_id();
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$urgency = $_POST['urgency'] ?? 'Media';
$priority = $_POST['priority'] ?? 'Medio';
$category = $_POST['category'] ?? 'Otro';
$due = $_POST['due_date'] ?: null;
$deployed = isset($_POST['deployed']) && $_POST['deployed'] == '1' ? 1 : 0;

// Documentos
$requires_docs = isset($_POST['requires_docs']) && $_POST['requires_docs'] == '1' ? 1 : 0;
$doc_plan_prueba = isset($_POST['doc_plan_prueba']) && $_POST['doc_plan_prueba'] == '1' ? 1 : 0;
$doc_plan_produccion = isset($_POST['doc_plan_produccion']) && $_POST['doc_plan_produccion'] == '1' ? 1 : 0;
$doc_control_objeto = isset($_POST['doc_control_objeto']) && $_POST['doc_control_objeto'] == '1' ? 1 : 0;
$doc_politica_respaldo = isset($_POST['doc_politica_respaldo']) && $_POST['doc_politica_respaldo'] == '1' ? 1 : 0;

if ($title === '') {
    header('Location: index.php?error=empty'); exit;
}

$pdo = get_pdo();
$stmt = $pdo->prepare('INSERT INTO tasks (user_id, title, description, urgency, priority, category, due_date, deployed, requires_docs, doc_plan_prueba, doc_plan_produccion, doc_control_objeto, doc_politica_respaldo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
$stmt->execute([$user_id, $title, $description, $urgency, $priority, $category, $due, $deployed, $requires_docs, $doc_plan_prueba, $doc_plan_produccion, $doc_control_objeto, $doc_politica_respaldo]);

header('Location: index.php'); exit;
