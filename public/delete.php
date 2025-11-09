<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/db.php';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: index.php'); exit; }

$pdo = get_pdo();
$stmt = $pdo->prepare('DELETE FROM tasks WHERE id = ?');
$stmt->execute([$id]);

header('Location: index.php'); exit;
