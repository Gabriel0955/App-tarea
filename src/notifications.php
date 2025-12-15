<?php
/**
 * Sistema de Notificaciones por Email
 * Envía recordatorios de tareas pendientes y próximas a vencer
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/db.php';

// Configuración de email (ajustar según tu servidor SMTP)
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.gmail.com');
define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
define('SMTP_USER', getenv('SMTP_USER') ?: 'tu-email@gmail.com');
define('SMTP_PASS', getenv('SMTP_PASS') ?: 'tu-password');
define('SMTP_FROM', getenv('SMTP_FROM') ?: 'noreply@app-tareas.com');
define('SMTP_FROM_NAME', getenv('SMTP_FROM_NAME') ?: 'App-Tareas');

$pdo = get_pdo();

/**
 * Enviar email (simplificado - usar PHPMailer o similar en producción)
 */
function send_email($to, $subject, $message) {
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM . ">\r\n";
    
    // En producción, usar PHPMailer o biblioteca SMTP
    // Por ahora, solo registramos en la base de datos
    return true; // mail($to, $subject, $message, $headers);
}

/**
 * Crear notificación pendiente en base de datos
 */
function create_notification($user_id, $task_id, $type, $message) {
    global $pdo;
    $stmt = $pdo->prepare('INSERT INTO notifications (user_id, task_id, type, message) VALUES (?, ?, ?, ?)');
    return $stmt->execute([$user_id, $task_id, $type, $message]);
}

/**
 * Marcar notificación como enviada
 */
function mark_notification_sent($notification_id) {
    global $pdo;
    $stmt = $pdo->prepare('UPDATE notifications SET sent = 1, sent_at = NOW() WHERE id = ?');
    return $stmt->execute([$notification_id]);
}

/**
 * Procesar tareas próximas a vencer (dentro de 3 días)
 */
function notify_upcoming_tasks() {
    global $pdo;
    
    $stmt = $pdo->query("
        SELECT t.*, u.username, u.email 
        FROM tasks t
        JOIN users u ON t.user_id = u.id
        WHERE t.deployed = 0
        AND t.due_date IS NOT NULL
        AND t.due_date BETWEEN CURRENT_DATE AND CURRENT_DATE + INTERVAL '3 days'
        AND NOT EXISTS (
            SELECT 1 FROM notifications n 
            WHERE n.task_id = t.id 
            AND n.type = 'upcoming' 
            AND n.created_at > CURRENT_DATE - INTERVAL '1 day'
        )
    ");
    
    $count = 0;
    while ($task = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $days_until = ceil((strtotime($task['due_date']) - time()) / 86400);
        
        $message = "
        <h2>🔔 Tarea Próxima a Vencer</h2>
        <p><strong>Tarea:</strong> {$task['title']}</p>
        <p><strong>Descripción:</strong> {$task['description']}</p>
        <p><strong>Vence:</strong> {$task['due_date']} (en $days_until días)</p>
        <p><strong>Urgencia:</strong> {$task['urgency']}</p>
        <p><a href=\"" . getenv('APP_URL') . "/public/edit.php?id={$task['id']}\" style=\"background: #4a90e2; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px;\">Ver Tarea</a></p>
        ";
        
        create_notification($task['user_id'], $task['id'], 'upcoming', $message);
        // send_email($task['email'], "Tarea próxima a vencer: {$task['title']}", $message);
        $count++;
    }
    
    return $count;
}

/**
 * Procesar tareas vencidas
 */
function notify_overdue_tasks() {
    global $pdo;
    
    $stmt = $pdo->query("
        SELECT t.*, u.username, u.email 
        FROM tasks t
        JOIN users u ON t.user_id = u.id
        WHERE t.deployed = 0
        AND t.due_date < CURRENT_DATE
        AND NOT EXISTS (
            SELECT 1 FROM notifications n 
            WHERE n.task_id = t.id 
            AND n.type = 'overdue' 
            AND n.created_at > CURRENT_DATE - INTERVAL '1 day'
        )
    ");
    
    $count = 0;
    while ($task = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $days_overdue = ceil((time() - strtotime($task['due_date'])) / 86400);
        
        $message = "
        <h2>⚠️ Tarea Vencida</h2>
        <p><strong>Tarea:</strong> {$task['title']}</p>
        <p><strong>Descripción:</strong> {$task['description']}</p>
        <p><strong>Venció:</strong> {$task['due_date']} (hace $days_overdue días)</p>
        <p><strong>Urgencia:</strong> {$task['urgency']}</p>
        <p style=\"color: #ef4444; font-weight: bold;\">Esta tarea requiere atención inmediata.</p>
        <p><a href=\"" . getenv('APP_URL') . "/public/edit.php?id={$task['id']}\" style=\"background: #ef4444; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px;\">Ver Tarea</a></p>
        ";
        
        create_notification($task['user_id'], $task['id'], 'overdue', $message);
        // send_email($task['email'], "⚠️ Tarea vencida: {$task['title']}", $message);
        $count++;
    }
    
    return $count;
}

/**
 * Enviar resumen semanal
 */
function send_weekly_summary() {
    global $pdo;
    
    $users_stmt = $pdo->query("SELECT id, username, email FROM users");
    $count = 0;
    
    while ($user = $users_stmt->fetch(PDO::FETCH_ASSOC)) {
        $stats_stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN deployed = 0 THEN 1 ELSE 0 END) as pendientes,
                SUM(CASE WHEN deployed = 1 AND deployed_at > CURRENT_DATE - INTERVAL '7 days' THEN 1 ELSE 0 END) as desplegados_semana,
                SUM(CASE WHEN due_date < CURRENT_DATE AND deployed = 0 THEN 1 ELSE 0 END) as vencidos
            FROM tasks WHERE user_id = ?
        ");
        $stats_stmt->execute([$user['id']]);
        $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($stats['total'] == 0) continue;
        
        $message = "
        <h2>📊 Resumen Semanal - App-Tareas</h2>
        <p>Hola <strong>{$user['username']}</strong>,</p>
        <p>Aquí está tu resumen de la semana:</p>
        <ul>
            <li><strong>Total de tareas:</strong> {$stats['total']}</li>
            <li><strong>Pendientes:</strong> {$stats['pendientes']}</li>
            <li><strong>Desplegados esta semana:</strong> {$stats['desplegados_semana']}</li>
            <li><strong>Vencidos:</strong> {$stats['vencidos']}</li>
        </ul>
        ";
        
        if ($stats['vencidos'] > 0) {
            $message .= "<p style=\"color: #ef4444; font-weight: bold;\">⚠️ Tienes {$stats['vencidos']} tarea(s) vencida(s) que requieren atención.</p>";
        }
        
        $message .= "<p><a href=\"" . getenv('APP_URL') . "/public/index.php\" style=\"background: #4a90e2; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px;\">Ir a App-Tareas</a></p>";
        
        // send_email($user['email'], "Resumen Semanal - App-Tareas", $message);
        $count++;
    }
    
    return $count;
}

// Ejecutar según el parámetro
$action = $argv[1] ?? 'all';

$results = [];

if ($action === 'all' || $action === 'upcoming') {
    $results['upcoming'] = notify_upcoming_tasks();
    echo "✅ Notificaciones de tareas próximas: {$results['upcoming']}\n";
}

if ($action === 'all' || $action === 'overdue') {
    $results['overdue'] = notify_overdue_tasks();
    echo "✅ Notificaciones de tareas vencidas: {$results['overdue']}\n";
}

if ($action === 'weekly') {
    $results['weekly'] = send_weekly_summary();
    echo "✅ Resúmenes semanales enviados: {$results['weekly']}\n";
}

echo "\n📧 Total de notificaciones procesadas: " . array_sum($results) . "\n";
