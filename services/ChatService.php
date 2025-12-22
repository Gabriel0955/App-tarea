<?php
/**
 * ChatService - Gestión de mensajes de chat
 */
class ChatService {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Obtener conversaciones de un usuario
     */
    public function getUserConversations($userId) {
        $stmt = $this->pdo->prepare("SELECT * FROM get_user_conversations(?)");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener mensajes de una conversación
     */
    public function getConversationMessages($userId, $otherUserId, $limit = 50, $offset = 0) {
        $stmt = $this->pdo->prepare("
            SELECT 
                m.id,
                m.sender_id,
                m.receiver_id,
                m.message,
                m.is_read,
                m.read_at,
                m.created_at,
                u.username as sender_username
            FROM chat_messages m
            JOIN users u ON m.sender_id = u.id
            WHERE (m.sender_id = ? AND m.receiver_id = ?)
               OR (m.sender_id = ? AND m.receiver_id = ?)
            ORDER BY m.created_at DESC
            LIMIT ? OFFSET ?
        ");
        
        $stmt->execute([$userId, $otherUserId, $otherUserId, $userId, $limit, $offset]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Reverse order (oldest first)
        return array_reverse($messages);
    }
    
    /**
     * Enviar mensaje (fallback si WebSocket no está disponible)
     */
    public function sendMessage($senderId, $receiverId, $message) {
        $stmt = $this->pdo->prepare("
            INSERT INTO chat_messages (sender_id, receiver_id, message)
            VALUES (?, ?, ?)
            RETURNING id, created_at
        ");
        
        $stmt->execute([$senderId, $receiverId, $message]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Marcar mensajes como leídos
     */
    public function markMessagesAsRead($receiverId, $senderId) {
        $stmt = $this->pdo->prepare("SELECT mark_messages_as_read(?, ?) as count");
        $stmt->execute([$receiverId, $senderId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }
    
    /**
     * Obtener contador de mensajes no leídos
     */
    public function getUnreadCount($userId) {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as count
            FROM chat_messages
            WHERE receiver_id = ? AND is_read = FALSE
        ");
        
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['count'];
    }
    
    /**
     * Obtener mensajes no leídos agrupados por remitente
     */
    public function getUnreadMessagesBySender($userId) {
        $stmt = $this->pdo->prepare("
            SELECT 
                m.sender_id,
                u.username as sender_username,
                COUNT(*) as unread_count,
                MAX(m.created_at) as last_message_at
            FROM chat_messages m
            JOIN users u ON m.sender_id = u.id
            WHERE m.receiver_id = ? AND m.is_read = FALSE
            GROUP BY m.sender_id, u.username
            ORDER BY last_message_at DESC
        ");
        
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Verificar si el usuario puede chatear con otro usuario
     * (para supervisor: solo con miembros de su equipo)
     */
    public function canChatWith($userId, $otherUserId) {
        // Admin puede chatear con todos
        $stmt = $this->pdo->prepare("
            SELECT r.name FROM users u 
            JOIN roles r ON u.role_id = r.id 
            WHERE u.id = ?
        ");
        $stmt->execute([$userId]);
        $role = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($role['name'] === 'admin') {
            return true;
        }
        
        // Supervisor puede chatear con sus miembros
        if ($role['name'] === 'supervisor') {
            $stmt = $this->pdo->prepare("
                SELECT 1 FROM supervisor_teams 
                WHERE supervisor_id = ? AND team_member_id = ?
            ");
            $stmt->execute([$userId, $otherUserId]);
            if ($stmt->fetch()) {
                return true;
            }
            
            // También si el usuario es supervisor del otro
            $stmt = $this->pdo->prepare("
                SELECT 1 FROM supervisor_teams 
                WHERE supervisor_id = ? AND team_member_id = ?
            ");
            $stmt->execute([$otherUserId, $userId]);
            return (bool)$stmt->fetch();
        }
        
        // Usuario normal: puede chatear con su supervisor
        $stmt = $this->pdo->prepare("
            SELECT 1 FROM supervisor_teams 
            WHERE team_member_id = ? AND supervisor_id = ?
        ");
        $stmt->execute([$userId, $otherUserId]);
        return (bool)$stmt->fetch();
    }
    
    /**
     * Obtener usuarios disponibles para chatear
     */
    public function getAvailableChatUsers($userId) {
        $stmt = $this->pdo->prepare("
            SELECT u.id, u.username, r.name as role
            FROM users u
            JOIN roles r ON u.role_id = r.id
            WHERE u.id != ?
            ORDER BY u.username
        ");
        
        $stmt->execute([$userId]);
        $allUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Filtrar por permisos
        $available = [];
        foreach ($allUsers as $user) {
            if ($this->canChatWith($userId, $user['id'])) {
                $available[] = $user;
            }
        }
        
        return $available;
    }
    
    /**
     * Eliminar conversación (marcar mensajes como eliminados)
     */
    public function deleteConversation($userId, $otherUserId) {
        // Solo eliminar del lado del usuario actual
        $stmt = $this->pdo->prepare("
            DELETE FROM chat_messages 
            WHERE (sender_id = ? AND receiver_id = ?)
               OR (receiver_id = ? AND sender_id = ?)
        ");
        
        $stmt->execute([$userId, $otherUserId, $userId, $otherUserId]);
        return $stmt->rowCount();
    }
    
    /**
     * Buscar en mensajes
     */
    public function searchMessages($userId, $searchTerm, $limit = 20) {
        $stmt = $this->pdo->prepare("
            SELECT 
                m.id,
                m.sender_id,
                m.receiver_id,
                m.message,
                m.created_at,
                u1.username as sender_username,
                u2.username as receiver_username
            FROM chat_messages m
            JOIN users u1 ON m.sender_id = u1.id
            JOIN users u2 ON m.receiver_id = u2.id
            WHERE (m.sender_id = ? OR m.receiver_id = ?)
              AND m.message ILIKE ?
            ORDER BY m.created_at DESC
            LIMIT ?
        ");
        
        $stmt->execute([$userId, $userId, '%' . $searchTerm . '%', $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
