<?php
/**
 * Chat API - REST endpoints para chat
 */
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../services/ChatService.php';

header('Content-Type: application/json');

$pdo = get_pdo();
$chatService = new ChatService($pdo);
$userId = $_SESSION['user_id'];

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'get_conversations':
            $conversations = $chatService->getUserConversations($userId);
            echo json_encode([
                'success' => true,
                'conversations' => $conversations
            ]);
            break;
            
        case 'get_messages':
            $otherUserId = intval($_GET['user_id'] ?? 0);
            
            if (!$chatService->canChatWith($userId, $otherUserId)) {
                throw new Exception('No tienes permiso para chatear con este usuario');
            }
            
            $limit = intval($_GET['limit'] ?? 50);
            $offset = intval($_GET['offset'] ?? 0);
            
            $messages = $chatService->getConversationMessages($userId, $otherUserId, $limit, $offset);
            
            echo json_encode([
                'success' => true,
                'messages' => $messages
            ]);
            break;
            
        case 'send_message':
            $receiverId = intval($_POST['receiver_id'] ?? 0);
            $message = trim($_POST['message'] ?? '');
            
            if (empty($message)) {
                throw new Exception('El mensaje no puede estar vacío');
            }
            
            if (!$chatService->canChatWith($userId, $receiverId)) {
                throw new Exception('No tienes permiso para chatear con este usuario');
            }
            
            $result = $chatService->sendMessage($userId, $receiverId, $message);
            
            echo json_encode([
                'success' => true,
                'message' => $result
            ]);
            break;
            
        case 'mark_read':
            $senderId = intval($_POST['sender_id'] ?? 0);
            $count = $chatService->markMessagesAsRead($userId, $senderId);
            
            echo json_encode([
                'success' => true,
                'count' => $count
            ]);
            break;
            
        case 'get_unread_count':
            $count = $chatService->getUnreadCount($userId);
            
            echo json_encode([
                'success' => true,
                'count' => $count
            ]);
            break;
            
        case 'get_available_users':
            $users = $chatService->getAvailableChatUsers($userId);
            
            echo json_encode([
                'success' => true,
                'users' => $users
            ]);
            break;
            
        case 'delete_conversation':
            $otherUserId = intval($_POST['user_id'] ?? 0);
            $count = $chatService->deleteConversation($userId, $otherUserId);
            
            echo json_encode([
                'success' => true,
                'deleted' => $count
            ]);
            break;
            
        case 'search':
            $searchTerm = trim($_GET['q'] ?? '');
            $limit = intval($_GET['limit'] ?? 20);
            
            if (empty($searchTerm)) {
                throw new Exception('Término de búsqueda vacío');
            }
            
            $results = $chatService->searchMessages($userId, $searchTerm, $limit);
            
            echo json_encode([
                'success' => true,
                'results' => $results
            ]);
            break;
            
        default:
            throw new Exception('Acción no válida');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
