<?php
session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../services/GamificationService.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
  echo json_encode(['success' => false, 'error' => 'No autenticado']);
  exit;
}

$pdo = get_pdo();
$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

$task_id = $data['task_id'] ?? null;
$work_duration = $data['work_duration'] ?? 25;
$status = $data['status'] ?? 'completed';

// Solo procesar si se completó
if ($status === 'completed') {
  $result = processPomodoroCompletion($pdo, $user_id, $task_id, $work_duration);
  echo json_encode($result);
} else {
  // Guardar sesión incompleta
  try {
    $session_id = savePomodoroSession($pdo, $user_id, $task_id, $work_duration, $status);
    echo json_encode([
      'success' => true,
      'session_id' => $session_id,
      'achievements' => []
    ]);
  } catch (Exception $e) {
    echo json_encode([
      'success' => false,
      'error' => $e->getMessage()
    ]);
  }
}
?>
