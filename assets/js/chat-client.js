/**
 * WebSocket Chat Client
 * Handles real-time messaging in the browser
 */

class ChatClient {
  constructor(serverUrl, userId, sessionToken) {
    this.serverUrl = serverUrl;
    this.userId = userId;
    this.sessionToken = sessionToken;
    this.ws = null;
    this.reconnectAttempts = 0;
    this.maxReconnectAttempts = 5;
    this.reconnectDelay = 3000;
    this.pingInterval = null;
    this.messageHandlers = new Map();
    this.isConnected = false;
  }

  // Connect to WebSocket server
connect() {
  console.log('🔌 Connecting to chat server...');

  const protocol = location.protocol === 'https:' ? 'wss' : 'ws';
  const url = this.serverUrl.replace(/^wss?:\/\//, '');

  this.ws = new WebSocket(`${protocol}://${url}`);

  this.ws.onopen = () => {
    console.log('✅ Connected to chat server');
    this.isConnected = true;
    this.reconnectAttempts = 0;

    // Authenticate
    this.send('auth', {
      userId: this.userId,
      sessionToken: this.sessionToken
    });

    // Start ping interval
    this.startPing();

    this.trigger('connected');
  };

  this.ws.onmessage = (event) => {
    try {
      const { type, payload } = JSON.parse(event.data);
      this.handleMessage(type, payload);
    } catch (error) {
      console.error('❌ Error parsing message:', error);
    }
  };

  this.ws.onclose = () => {
    console.log('📴 Disconnected from chat server');
    this.isConnected = false;
    this.stopPing();
    this.trigger('disconnected');

    if (this.reconnectAttempts < this.maxReconnectAttempts) {
      this.reconnectAttempts++;
      console.log(`🔄 Reconnecting... (${this.reconnectAttempts}/${this.maxReconnectAttempts})`);
      setTimeout(() => this.connect(), this.reconnectDelay);
    } else {
      console.error('❌ Max reconnection attempts reached');
      this.trigger('reconnect_failed');
    }
  };

  this.ws.onerror = (error) => {
    console.error('❌ WebSocket error:', error);
    this.trigger('error', error);
  };
}

  // Disconnect
  disconnect() {
    if (this.ws) {
      this.reconnectAttempts = this.maxReconnectAttempts; // Prevent auto-reconnect
      this.ws.close();
      this.stopPing();
    }
  }

  // Send message to server
  send(type, payload) {
    if (this.ws && this.ws.readyState === WebSocket.OPEN) {
      const message = JSON.stringify({ type, payload });
      console.log(`📤 Sending: ${type}`, payload);
      this.ws.send(message);
      return true;
    } else {
      console.error('⚠️ WebSocket not connected. ReadyState:', this.ws ? this.ws.readyState : 'null');
      return false;
    }
  }

  // Handle incoming messages
  handleMessage(type, payload) {
    console.log(`📨 Received: ${type}`, payload);
    
    switch (type) {
      case 'auth_success':
        this.trigger('authenticated', payload);
        break;
        
      case 'new_message':
        this.trigger('message_received', payload);
        this.playNotificationSound();
        break;
        
      case 'message_sent':
        this.trigger('message_sent', payload);
        break;
        
      case 'messages_marked_read':
        this.trigger('messages_read', payload);
        break;
        
      case 'messages_read_by_receiver':
        this.trigger('messages_read_by_receiver', payload);
        break;
        
      case 'user_status':
        this.trigger('user_status', payload);
        break;
        
      case 'user_typing':
        this.trigger('user_typing', payload);
        break;
        
      case 'unread_count':
        this.trigger('unread_count', payload);
        break;
        
      case 'pong':
        // Keepalive response
        break;
        
      case 'error':
        console.error('Server error:', payload.message);
        this.trigger('server_error', payload);
        break;
        
      default:
        console.warn('Unknown message type:', type);
    }
  }

  // Send a chat message
  sendMessage(receiverId, message) {
    console.log(`💬 Attempting to send message to User ${receiverId}:`, message);
    
    if (!this.isConnected) {
      console.error('❌ Cannot send message: WebSocket not connected');
      // Intentar fallback HTTP
      this.sendMessageHTTP(receiverId, message);
      return false;
    }
    
    const sent = this.send('send_message', {
      receiverId: receiverId,
      message: message
    });
    
    if (!sent) {
      console.error('❌ Message send failed, trying HTTP fallback');
      this.sendMessageHTTP(receiverId, message);
    }
    
    return sent;
  }
  
  // HTTP fallback for sending messages
  async sendMessageHTTP(receiverId, message) {
    try {
      console.log('🔄 Using HTTP fallback to send message');
      const response = await fetch('/public/api/chat_api.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
          action: 'send_message',
          receiver_id: receiverId,
          message: message
        })
      });
      
      const data = await response.json();
      
      if (data.success) {
        console.log('✅ Message sent via HTTP fallback');
        // Trigger local message_sent event
        this.trigger('message_sent', {
          id: data.message.id,
          senderId: this.userId,
          senderUsername: this.username,
          receiverId: receiverId,
          message: message,
          createdAt: data.message.created_at,
          isRead: false
        });
        return true;
      } else {
        console.error('❌ HTTP fallback failed:', data.error);
        alert('Error al enviar mensaje: ' + (data.error || 'Error desconocido'));
        return false;
      }
    } catch (error) {
      console.error('❌ HTTP fallback error:', error);
      alert('Error de conexión al enviar mensaje');
      return false;
    }
  }

  // Mark messages as read
  markAsRead(senderId) {
    this.send('mark_read', {
      senderId: senderId
    });
  }

  // Send typing indicator
  setTyping(receiverId, isTyping) {
    this.send('typing', {
      receiverId: receiverId,
      isTyping: isTyping
    });
  }

  // Start keepalive ping
  startPing() {
    this.pingInterval = setInterval(() => {
      if (this.isConnected) {
        this.send('ping', {});
      }
    }, 25000); // Every 25 seconds
  }

  // Stop keepalive ping
  stopPing() {
    if (this.pingInterval) {
      clearInterval(this.pingInterval);
      this.pingInterval = null;
    }
  }

  // Event system
  on(event, handler) {
    if (!this.messageHandlers.has(event)) {
      this.messageHandlers.set(event, []);
    }
    this.messageHandlers.get(event).push(handler);
  }

  off(event, handler) {
    if (this.messageHandlers.has(event)) {
      const handlers = this.messageHandlers.get(event);
      const index = handlers.indexOf(handler);
      if (index > -1) {
        handlers.splice(index, 1);
      }
    }
  }

  trigger(event, data) {
    if (this.messageHandlers.has(event)) {
      this.messageHandlers.get(event).forEach(handler => {
        try {
          handler(data);
        } catch (error) {
          console.error(`Error in handler for ${event}:`, error);
        }
      });
    }
  }

  // Notification sound
  playNotificationSound() {
    const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBTGH0fPTgjMGHm7A7+OZSA0PVKzn7LNUHAVGV8Hq6OavVBMLRlfA6ujrtmsgBSZ70e7imlAJDVKp5O61WxsLRVvA6unjtWQfBBh9y+/ilF8NBk+U4uvvp1gbCUR+y+/i3bBhGgpDc8rm7KpkGwU8gsPo6LNhGwU7d7/m7q5jHAU+esPn7K9gGgU7ebzm77BgGgU7eb3m7K9hGgU7ebzm7bBgGgU7ebzm7LFgGgU7eb3l7K9hGgU7ebzn7K9gGgU7ebzn7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7K9hGgU7ebzm7A==');
    audio.volume = 0.3;
    audio.play().catch(() => {}); // Ignore errors
  }
}

// Export for use in other scripts
window.ChatClient = ChatClient;
