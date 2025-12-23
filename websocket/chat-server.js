/**
 * WebSocket Chat Server for App Tareas
 * Handles real-time messaging between users
 */

require('dotenv').config();
const WebSocket = require('ws');
const { Pool } = require('pg');
const crypto = require('crypto');

// PostgreSQL Connection Pool
const pool = new Pool({
  host: 'apptarea.postgres.database.azure.com',
  port: 5432,
  database: 'postgres',
  user: 'apptarea',
  password: 'Gabriel1405',
  ssl: {
    rejectUnauthorized: false // Azure PostgreSQL requires SSL
  }
});

// Test database connection
pool.query('SELECT NOW()', (err, res) => {
  if (err) {
    console.error('[ERROR] Database connection failed:', err);
    process.exit(1);
  }
  console.log('[OK] Database connected:', res.rows[0].now);
});

// WebSocket Server
const PORT = 8081; // Cambiado temporalmente para no conflictar con PHP
const wss = new WebSocket.Server({ 
  port: PORT,
  clientTracking: true 
});

// Active connections Map: userId -> WebSocket
const connections = new Map();

console.log(`[SERVER] WebSocket Chat Server running on ws://localhost:${PORT}`);

// Handle new connections
wss.on('connection', async (ws, req) => {
  const connectionId = crypto.randomUUID();
  const ip = req.socket.remoteAddress;
  const userAgent = req.headers['user-agent'];
  
  console.log(`[CONNECT] New connection: ${connectionId} from ${ip}`);
  
  ws.connectionId = connectionId;
  ws.userId = null;
  ws.isAlive = true;
  
  // Pong response for keepalive
  ws.on('pong', () => {
    ws.isAlive = true;
  });
  
  // Handle messages from client
  ws.on('message', async (data) => {
    try {
      const message = JSON.parse(data.toString());
      await handleMessage(ws, message, ip, userAgent);
    } catch (error) {
      console.error('[ERROR] Error handling message:', error);
      sendError(ws, 'Invalid message format');
    }
  });
  
  // Handle disconnection
  ws.on('close', async () => {
    console.log(`[DISCONNECT] Connection closed: ${connectionId}, User: ${ws.userId}`);
    
    if (ws.userId) {
      connections.delete(ws.userId);
      
      // Remove from database
      try {
        await pool.query(
          'DELETE FROM chat_connections WHERE connection_id = $1',
          [connectionId]
        );
        
        // Notify others that user went offline
        broadcastUserStatus(ws.userId, false);
      } catch (error) {
        console.error('Error removing connection:', error);
      }
    }
  });
  
  ws.on('error', (error) => {
    console.error('[ERROR] WebSocket error:', error);
  });
});

// Message handler
async function handleMessage(ws, message, ip, userAgent) {
  const { type, payload } = message;
  
  switch (type) {
    case 'auth':
      await handleAuth(ws, payload, ip, userAgent);
      break;
      
    case 'send_message':
      await handleSendMessage(ws, payload);
      break;
      
    case 'mark_read':
      await handleMarkRead(ws, payload);
      break;
      
    case 'typing':
      await handleTyping(ws, payload);
      break;
      
    case 'ping':
      await handlePing(ws);
      break;
      
    default:
      sendError(ws, 'Unknown message type');
  }
}

// Authenticate user
async function handleAuth(ws, payload, ip, userAgent) {
  const { userId, sessionToken } = payload;
  
  if (!userId || !sessionToken) {
    return sendError(ws, 'Missing userId or sessionToken');
  }
  
  try {
    // Verify user exists and session is valid
    const result = await pool.query(
      'SELECT id, username FROM users WHERE id = $1',
      [userId]
    );
    
    if (result.rows.length === 0) {
      return sendError(ws, 'Invalid user');
    }
    
    const user = result.rows[0];
    ws.userId = userId;
    ws.username = user.username;
    
    // Store connection in memory
    connections.set(userId, ws);
    
    // Store connection in database
    await pool.query(
      `INSERT INTO chat_connections (user_id, connection_id, ip_address, user_agent)
       VALUES ($1, $2, $3, $4)
       ON CONFLICT (connection_id) DO UPDATE SET last_ping = CURRENT_TIMESTAMP`,
      [userId, ws.connectionId, ip, userAgent]
    );
    
    console.log(`[AUTH] User authenticated: ${user.username} (${userId})`);
    
    // Send success response
    send(ws, {
      type: 'auth_success',
      payload: {
        userId: userId,
        username: user.username,
        connectionId: ws.connectionId
      }
    });
    
    // Notify others that user is online
    broadcastUserStatus(userId, true);
    
    // Send unread message count
    const unreadResult = await pool.query(
      'SELECT COUNT(*) as count FROM chat_messages WHERE receiver_id = $1 AND is_read = FALSE',
      [userId]
    );
    
    send(ws, {
      type: 'unread_count',
      payload: {
        count: parseInt(unreadResult.rows[0].count)
      }
    });
    
  } catch (error) {
    console.error('Auth error:', error);
    sendError(ws, 'Authentication failed');
  }
}

// Handle sending a message
async function handleSendMessage(ws, payload) {
  if (!ws.userId) {
    return sendError(ws, 'Not authenticated');
  }
  
  const { receiverId, message } = payload;
  
  if (!receiverId || !message) {
    return sendError(ws, 'Missing receiverId or message');
  }
  
  try {
    // Insert message into database
    const result = await pool.query(
      `INSERT INTO chat_messages (sender_id, receiver_id, message)
       VALUES ($1, $2, $3)
       RETURNING id, created_at`,
      [ws.userId, receiverId, message]
    );
    
    const messageData = {
      id: result.rows[0].id,
      senderId: ws.userId,
      senderUsername: ws.username,
      receiverId: receiverId,
      message: message,
      createdAt: result.rows[0].created_at,
      isRead: false
    };
    
    console.log(`[MESSAGE] From ${ws.username} to User ${receiverId}`);
    
    // Send confirmation to sender
    send(ws, {
      type: 'message_sent',
      payload: messageData
    });
    
    // Send message to receiver if online
    const receiverWs = connections.get(receiverId);
    if (receiverWs && receiverWs.readyState === WebSocket.OPEN) {
      send(receiverWs, {
        type: 'new_message',
        payload: messageData
      });
      
      console.log(`[DELIVERED] Message delivered to User ${receiverId}`);
    } else {
      console.log(`[OFFLINE] User ${receiverId} is offline, message stored`);
    }
    
  } catch (error) {
    console.error('Send message error:', error);
    sendError(ws, 'Failed to send message');
  }
}

// Handle marking messages as read
async function handleMarkRead(ws, payload) {
  if (!ws.userId) {
    return sendError(ws, 'Not authenticated');
  }
  
  const { senderId } = payload;
  
  try {
    const result = await pool.query(
      'SELECT mark_messages_as_read($1, $2) as count',
      [ws.userId, senderId]
    );
    
    const count = result.rows[0].count;
    console.log(`[READ] Marked ${count} messages as read from User ${senderId}`);
    
    send(ws, {
      type: 'messages_marked_read',
      payload: { senderId, count }
    });
    
    // Notify sender that messages were read
    const senderWs = connections.get(senderId);
    if (senderWs && senderWs.readyState === WebSocket.OPEN) {
      send(senderWs, {
        type: 'messages_read_by_receiver',
        payload: { receiverId: ws.userId }
      });
    }
    
  } catch (error) {
    console.error('Mark read error:', error);
    sendError(ws, 'Failed to mark messages as read');
  }
}

// Handle typing indicator
async function handleTyping(ws, payload) {
  if (!ws.userId) return;
  
  const { receiverId, isTyping } = payload;
  
  const receiverWs = connections.get(receiverId);
  if (receiverWs && receiverWs.readyState === WebSocket.OPEN) {
    send(receiverWs, {
      type: 'user_typing',
      payload: {
        userId: ws.userId,
        username: ws.username,
        isTyping: isTyping
      }
    });
  }
}

// Handle ping (keepalive)
async function handlePing(ws) {
  if (!ws.userId) return;
  
  try {
    await pool.query(
      'UPDATE chat_connections SET last_ping = CURRENT_TIMESTAMP WHERE user_id = $1',
      [ws.userId]
    );
    
    send(ws, { type: 'pong' });
  } catch (error) {
    console.error('Ping error:', error);
  }
}

// Broadcast user online/offline status
function broadcastUserStatus(userId, isOnline) {
  const message = {
    type: 'user_status',
    payload: {
      userId: userId,
      isOnline: isOnline
    }
  };
  
  connections.forEach((clientWs, clientId) => {
    if (clientId !== userId && clientWs.readyState === WebSocket.OPEN) {
      send(clientWs, message);
    }
  });
}

// Send message to client
function send(ws, data) {
  if (ws.readyState === WebSocket.OPEN) {
    ws.send(JSON.stringify(data));
  }
}

// Send error to client
function sendError(ws, errorMessage) {
  send(ws, {
    type: 'error',
    payload: { message: errorMessage }
  });
}

// Heartbeat interval (check every 30 seconds)
const heartbeatInterval = setInterval(() => {
  wss.clients.forEach((ws) => {
    if (ws.isAlive === false) {
      console.log(`[TERMINATE] Terminating dead connection: ${ws.connectionId}`);
      return ws.terminate();
    }
    
    ws.isAlive = false;
    ws.ping();
  });
}, 30000);

// Cleanup inactive connections every 5 minutes
const cleanupInterval = setInterval(async () => {
  try {
    const result = await pool.query('SELECT cleanup_inactive_connections()');
    const count = result.rows[0].cleanup_inactive_connections;
    if (count > 0) {
      console.log(`[CLEANUP] Cleaned up ${count} inactive connections`);
    }
  } catch (error) {
    console.error('Cleanup error:', error);
  }
}, 300000); // 5 minutes

// Graceful shutdown
process.on('SIGTERM', () => {
  console.log('[SHUTDOWN] SIGTERM received, shutting down gracefully...');
  clearInterval(heartbeatInterval);
  clearInterval(cleanupInterval);
  
  wss.close(() => {
    console.log('[OK] WebSocket server closed');
    pool.end(() => {
      console.log('[OK] Database pool closed');
      process.exit(0);
    });
  });
});

process.on('SIGINT', () => {
  console.log('\n[SHUTDOWN] SIGINT received, shutting down gracefully...');
  clearInterval(heartbeatInterval);
  clearInterval(cleanupInterval);
  
  wss.close(() => {
    console.log('[OK] WebSocket server closed');
    pool.end(() => {
      console.log('[OK] Database pool closed');
      process.exit(0);
    });
  });
});
