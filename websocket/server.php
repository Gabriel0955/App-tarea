<?php

require __DIR__ . '/vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

class ChatServer implements MessageComponentInterface {
    protected $clients;
    protected $connections;
    protected $pdo;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->connections = [];
        
        $this->connectDatabase();
        
        echo "[OK] Ratchet Chat Server iniciado en puerto 8080\n";
    }

    private function connectDatabase() {
        try {
            $host = 'apptarea.postgres.database.azure.com';
            $dbname = 'postgres';
            $user = 'apptarea';
            $password = 'Gabriel1405';
            
            $dsn = "pgsql:host=$host;port=5432;dbname=$dbname;sslmode=require";
            $this->pdo = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            
            echo "[OK] Database connected\n";
        } catch (PDOException $e) {
            echo "[ERROR] Database connection failed: " . $e->getMessage() . "\n";
            exit(1);
        }
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        $conn->userId = null;
        $conn->username = null;
        $conn->connectionId = uniqid('conn_', true);
        
        echo "[CONNECT] New connection: {$conn->connectionId} from {$conn->remoteAddress}\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        try {
            $data = json_decode($msg, true);
            
            if (!$data || !isset($data['type'])) {
                $this->sendError($from, 'Invalid message format');
                return;
            }

            switch ($data['type']) {
                case 'auth':
                    $this->handleAuth($from, $data['payload'] ?? []);
                    break;
                    
                case 'send_message':
                    $this->handleSendMessage($from, $data['payload'] ?? []);
                    break;
                    
                case 'mark_read':
                    $this->handleMarkRead($from, $data['payload'] ?? []);
                    break;
                    
                case 'typing':
                    $this->handleTyping($from, $data['payload'] ?? []);
                    break;
                    
                case 'ping':
                    $this->handlePing($from);
                    break;
                    
                default:
                    $this->sendError($from, 'Unknown message type');
            }
        } catch (Exception $e) {
            echo "[ERROR] Error handling message: " . $e->getMessage() . "\n";
            $this->sendError($from, 'Internal server error');
        }
    }

    private function handleAuth(ConnectionInterface $conn, array $payload) {
        $userId = $payload['userId'] ?? null;
        $sessionToken = $payload['sessionToken'] ?? null;

        if (!$userId || !$sessionToken) {
            $this->sendError($conn, 'Missing userId or sessionToken');
            return;
        }

        try {
            $stmt = $this->pdo->prepare('SELECT id, username FROM users WHERE id = ?');
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            if (!$user) {
                $this->sendError($conn, 'Invalid user');
                return;
            }

            $conn->userId = $userId;
            $conn->username = $user['username'];
            $this->connections[$userId] = $conn;

            $stmt = $this->pdo->prepare(
                'INSERT INTO chat_connections (user_id, connection_id, ip_address, user_agent) 
                 VALUES (?, ?, ?, ?) 
                 ON CONFLICT (connection_id) DO UPDATE SET last_ping = CURRENT_TIMESTAMP'
            );
            $stmt->execute([
                $userId,
                $conn->connectionId,
                $conn->remoteAddress,
                $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
            ]);

            echo "[AUTH] User authenticated: {$user['username']} ({$userId})\n";

            $this->send($conn, [
                'type' => 'auth_success',
                'payload' => [
                    'userId' => $userId,
                    'username' => $user['username'],
                    'connectionId' => $conn->connectionId
                ]
            ]);

            $this->broadcastUserStatus($userId, true);

            $stmt = $this->pdo->prepare(
                'SELECT COUNT(*) as count FROM chat_messages WHERE receiver_id = ? AND is_read = FALSE'
            );
            $result = $stmt->fetch();

            $this->send($conn, [
                'type' => 'unread_count',
                'payload' => ['count' => (int)$result['count']]
            ]);

        } catch (PDOException $e) {
            echo "[ERROR] Auth error: " . $e->getMessage() . "\n";
            $this->sendError($conn, 'Authentication failed');
        }
    }

    private function handleSendMessage(ConnectionInterface $from, array $payload) {
        if (!$from->userId) {
            $this->sendError($from, 'Not authenticated');
            return;
        }

        $receiverId = $payload['receiverId'] ?? null;
        $message = $payload['message'] ?? null;

        if (!$receiverId || !$message) {
            $this->sendError($from, 'Missing receiverId or message');
            return;
        }

        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO chat_messages (sender_id, receiver_id, message) 
                 VALUES (?, ?, ?) 
                 RETURNING id, created_at'
            );
            $stmt->execute([$from->userId, $receiverId, $message]);
            $result = $stmt->fetch();

            $messageData = [
                'id' => $result['id'],
                'senderId' => $from->userId,
                'senderUsername' => $from->username,
                'receiverId' => $receiverId,
                'message' => $message,
                'createdAt' => $result['created_at'],
                'isRead' => false
            ];

            echo "[MESSAGE] From {$from->username} to User {$receiverId}\n";

            $this->send($from, [
                'type' => 'message_sent',
                'payload' => $messageData
            ]);

            if (isset($this->connections[$receiverId])) {
                $receiverConn = $this->connections[$receiverId];
                $this->send($receiverConn, [
                    'type' => 'new_message',
                    'payload' => $messageData
                ]);
                echo "[DELIVERED] Message delivered to User {$receiverId}\n";
            } else {
                echo "[OFFLINE] User {$receiverId} is offline, message stored\n";
            }

        } catch (PDOException $e) {
            echo "[ERROR] Send message error: " . $e->getMessage() . "\n";
            $this->sendError($from, 'Failed to send message');
        }
    }

    private function handleMarkRead(ConnectionInterface $from, array $payload) {
        if (!$from->userId) {
            $this->sendError($from, 'Not authenticated');
            return;
        }

        $senderId = $payload['senderId'] ?? null;

        try {
            $stmt = $this->pdo->prepare('SELECT mark_messages_as_read(?, ?) as count');
            $stmt->execute([$from->userId, $senderId]);
            $result = $stmt->fetch();
            $count = $result['count'];

            echo "[READ] Marked {$count} messages as read from User {$senderId}\n";

            $this->send($from, [
                'type' => 'messages_marked_read',
                'payload' => ['senderId' => $senderId, 'count' => $count]
            ]);

            if (isset($this->connections[$senderId])) {
                $this->send($this->connections[$senderId], [
                    'type' => 'messages_read_by_receiver',
                    'payload' => ['receiverId' => $from->userId]
                ]);
            }

        } catch (PDOException $e) {
            echo "[ERROR] Mark read error: " . $e->getMessage() . "\n";
            $this->sendError($from, 'Failed to mark messages as read');
        }
    }

    private function handleTyping(ConnectionInterface $from, array $payload) {
        if (!$from->userId) return;

        $receiverId = $payload['receiverId'] ?? null;
        $isTyping = $payload['isTyping'] ?? false;

        if (isset($this->connections[$receiverId])) {
            $this->send($this->connections[$receiverId], [
                'type' => 'user_typing',
                'payload' => [
                    'userId' => $from->userId,
                    'username' => $from->username,
                    'isTyping' => $isTyping
                ]
            ]);
        }
    }

    private function handlePing(ConnectionInterface $from) {
        if (!$from->userId) return;

        try {
            $stmt = $this->pdo->prepare(
                'UPDATE chat_connections SET last_ping = CURRENT_TIMESTAMP WHERE user_id = ?'
            );
            $stmt->execute([$from->userId]);

            $this->send($from, ['type' => 'pong']);
        } catch (PDOException $e) {
            echo "[ERROR] Ping error: " . $e->getMessage() . "\n";
        }
    }

    private function broadcastUserStatus($userId, $isOnline) {
        $message = [
            'type' => 'user_status',
            'payload' => [
                'userId' => $userId,
                'isOnline' => $isOnline
            ]
        ];

        foreach ($this->connections as $uid => $conn) {
            if ($uid != $userId) {
                $this->send($conn, $message);
            }
        }
    }

    private function send(ConnectionInterface $conn, array $data) {
        $conn->send(json_encode($data));
    }

    private function sendError(ConnectionInterface $conn, string $errorMessage) {
        $this->send($conn, [
            'type' => 'error',
            'payload' => ['message' => $errorMessage]
        ]);
    }

    public function onClose(ConnectionInterface $conn) {
        echo "[DISCONNECT] Connection closed: {$conn->connectionId}, User: {$conn->userId}\n";

        if ($conn->userId) {
            unset($this->connections[$conn->userId]);

            try {
                $stmt = $this->pdo->prepare('DELETE FROM chat_connections WHERE connection_id = ?');
                $stmt->execute([$conn->connectionId]);

                $this->broadcastUserStatus($conn->userId, false);
            } catch (PDOException $e) {
                echo "[ERROR] Error removing connection: " . $e->getMessage() . "\n";
            }
        }

        $this->clients->detach($conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "[ERROR] WebSocket error: {$e->getMessage()}\n";
        $conn->close();
    }
}

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new ChatServer()
        )
    ),
    8080
);

echo "[SERVER] WebSocket Chat Server running on ws://0.0.0.0:8080\n";
$server->run();
