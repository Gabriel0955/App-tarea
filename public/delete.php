<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/auth.php';

$user_id = get_current_user_id();
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: index.php'); exit; }

$pdo = get_pdo();
$stmt = $pdo->prepare('DELETE FROM tasks WHERE id = ? AND user_id = ?');
$stmt->execute([$id, $user_id]);

header('Location: index.php'); exit;
