<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/auth.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
  echo json_encode(['success' => false, 'error' => 'No autenticado']);
  exit;
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

$task_id = $data['task_id'] ?? null;
$work_duration = $data['work_duration'] ?? 25;
$status = $data['status'] ?? 'completed';

try {
  $pdo->beginTransaction();
  
  // Guardar sesión Pomodoro
  $insert_session = "INSERT INTO pomodoro_sessions 
                     (user_id, task_id, work_duration, status, completed_at, focus_score)
                     VALUES (:user_id, :task_id, :work_duration, :status, CURRENT_TIMESTAMP, 100)
                     RETURNING id";
  $stmt = $pdo->prepare($insert_session);
  $stmt->execute([
    'user_id' => $user_id,
    'task_id' => $task_id,
    'work_duration' => $work_duration,
    'status' => $status
  ]);
  $session_id = $stmt->fetchColumn();
  
  // Solo otorgar puntos si se completó
  if ($status === 'completed') {
    // Actualizar estadísticas de usuario
    $update_stats = "UPDATE user_stats 
                     SET pomodoros_completed = pomodoros_completed + 1,
                         total_focus_time = total_focus_time + :work_duration
                     WHERE user_id = :user_id";
    $pdo->prepare($update_stats)->execute([
      'work_duration' => $work_duration,
      'user_id' => $user_id
    ]);
    
    // Actualizar racha
    $pdo->prepare("SELECT update_user_streak(:user_id)")->execute(['user_id' => $user_id]);
    
    // Otorgar puntos (10 puntos por Pomodoro completado)
    $pdo->prepare("SELECT award_points(:user_id, 10, 'Pomodoro completado', 'pomodoro', :session_id)")
         ->execute(['user_id' => $user_id, 'session_id' => $session_id]);
    
    // Verificar y desbloquear logros
    $check_achievements = "SELECT * FROM check_and_unlock_achievements(:user_id)";
    $stmt = $pdo->prepare($check_achievements);
    $stmt->execute(['user_id' => $user_id]);
    $achievements = $stmt->fetchAll(PDO::FETCH_ASSOC);
  } else {
    $achievements = [];
  }
  
  $pdo->commit();
  
  echo json_encode([
    'success' => true,
    'session_id' => $session_id,
    'achievements' => $achievements
  ]);
  
} catch (Exception $e) {
  $pdo->rollBack();
  echo json_encode([
    'success' => false,
    'error' => $e->getMessage()
  ]);
}
?>
