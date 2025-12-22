/**
 * Chat Widget Component
 * Real-time chat interface using WebSocket
 */

class ChatWidget {
  constructor(options = {}) {
    this.userId = options.userId;
    this.username = options.username;
    this.sessionToken = options.sessionToken;
    this.wsUrl = options.wsUrl || 'wss://socket.integra-code.org';
    this.containerId = options.containerId || 'chat-widget-container';
    
    this.chatClient = null;
    this.currentChatUser = null;
    this.messages = [];
    this.typingTimeout = null;
    this.isMinimized = true;
    this.unreadCount = 0;
    
    this.init();
  }

  init() {
    this.createWidget();
    this.connectWebSocket();
    this.attachEventListeners();
  }

  createWidget() {
    const container = document.getElementById(this.containerId);
    if (!container) {
      console.error(`Container #${this.containerId} not found`);
      return;
    }

    container.innerHTML = `
      <!-- Chat Toggle Button -->
      <button class="chat-toggle-btn" id="chatToggleBtn">
        💬
        <span class="badge" id="chatBadge" style="display: none;">0</span>
      </button>

      <!-- Chat Widget -->
      <div class="chat-widget hidden" id="chatWidget">
        <!-- Connection Status -->
        <div class="chat-connection-status" id="chatConnectionStatus">
          Conectando...
        </div>

        <!-- Header -->
        <div class="chat-header" id="chatHeader">
          <div class="chat-header-info">
            <div class="chat-avatar">
              <span id="chatAvatarText">?</span>
              <div class="status-dot offline" id="chatStatusDot"></div>
            </div>
            <div class="chat-header-title">
              <h3 id="chatHeaderTitle">Chat</h3>
              <p id="chatHeaderSubtitle">Selecciona un contacto</p>
            </div>
          </div>
          <div class="chat-header-actions">
            <button class="chat-btn" id="chatBackBtn" title="Volver" style="display: none;">←</button>
            <button class="chat-btn" id="chatMinimizeBtn" title="Minimizar">−</button>
            <button class="chat-btn" id="chatCloseBtn" title="Cerrar">×</button>
          </div>
        </div>

        <!-- Body (Messages or Conversations) -->
        <div class="chat-body" id="chatBody">
          <div class="chat-empty-state">
            <div class="chat-empty-state-icon">💬</div>
            <div class="chat-empty-state-text">No hay conversaciones</div>
          </div>
        </div>

        <!-- Footer -->
        <div class="chat-footer" id="chatFooter" style="display: none;">
          <div class="chat-typing-indicator" id="chatTypingIndicator" style="display: none;">
            <div class="chat-typing-dots">
              <span class="chat-typing-dot"></span>
              <span class="chat-typing-dot"></span>
              <span class="chat-typing-dot"></span>
            </div>
            <span id="chatTypingText">está escribiendo...</span>
          </div>
          <div class="chat-input-container">
            <textarea 
              id="chatInput" 
              class="chat-input" 
              placeholder="Escribe un mensaje..." 
              rows="1"></textarea>
            <button class="chat-send-btn" id="chatSendBtn" disabled>
              ➤
            </button>
          </div>
        </div>
      </div>
    `;
  }

  connectWebSocket() {
    this.chatClient = new ChatClient(this.wsUrl, this.userId, this.sessionToken);
    
    this.chatClient.on('connected', () => {
      console.log('Chat connected');
      this.updateConnectionStatus('connected', 'Conectado');
    });
    
    this.chatClient.on('disconnected', () => {
      console.log('Chat disconnected');
      this.updateConnectionStatus('disconnected', 'Desconectado');
    });
    
    this.chatClient.on('authenticated', (data) => {
      console.log('Chat authenticated', data);
      this.loadConversations();
    });
    
    this.chatClient.on('message_received', (data) => {
      this.handleNewMessage(data);
    });
    
    this.chatClient.on('message_sent', (data) => {
      this.handleMessageSent(data);
    });
    
    this.chatClient.on('user_status', (data) => {
      this.handleUserStatus(data);
    });
    
    this.chatClient.on('user_typing', (data) => {
      this.handleUserTyping(data);
    });
    
    this.chatClient.on('unread_count', (data) => {
      this.updateUnreadBadge(data.count);
    });
    
    this.chatClient.connect();
  }

  attachEventListeners() {
    const toggleBtn = document.getElementById('chatToggleBtn');
    const minimizeBtn = document.getElementById('chatMinimizeBtn');
    const closeBtn = document.getElementById('chatCloseBtn');
    const backBtn = document.getElementById('chatBackBtn');
    const sendBtn = document.getElementById('chatSendBtn');
    const input = document.getElementById('chatInput');
    
    toggleBtn?.addEventListener('click', () => this.toggleWidget());
    minimizeBtn?.addEventListener('click', () => this.minimizeWidget());
    closeBtn?.addEventListener('click', () => this.closeWidget());
    backBtn?.addEventListener('click', () => this.showConversations());
    sendBtn?.addEventListener('click', () => this.sendMessage());
    
    input?.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        this.sendMessage();
      }
    });
    
    input?.addEventListener('input', (e) => {
      const text = e.target.value.trim();
      sendBtn.disabled = text.length === 0;
      
      // Auto-resize textarea
      e.target.style.height = 'auto';
      e.target.style.height = Math.min(e.target.scrollHeight, 100) + 'px';
      
      // Send typing indicator
      if (this.currentChatUser) {
        clearTimeout(this.typingTimeout);
        this.chatClient.setTyping(this.currentChatUser.id, true);
        
        this.typingTimeout = setTimeout(() => {
          this.chatClient.setTyping(this.currentChatUser.id, false);
        }, 2000);
      }
    });
  }

  async loadConversations() {
    try {
      const response = await fetch('/public/api/chat_api.php?action=get_conversations');
      const data = await response.json();
      
      if (data.success) {
        this.renderConversations(data.conversations);
      }
    } catch (error) {
      console.error('Error loading conversations:', error);
    }
  }

  renderConversations(conversations) {
    const body = document.getElementById('chatBody');
    
    if (conversations.length === 0) {
      body.innerHTML = `
        <div class="chat-empty-state">
          <div class="chat-empty-state-icon">💬</div>
          <div class="chat-empty-state-text">No hay conversaciones activas</div>
        </div>
      `;
      return;
    }
    
    body.innerHTML = '<div class="chat-conversations-list"></div>';
    const list = body.querySelector('.chat-conversations-list');
    
    conversations.forEach(conv => {
      const item = document.createElement('div');
      item.className = 'chat-conversation-item';
      item.innerHTML = `
        <div class="chat-conversation-avatar">
          ${conv.other_username.charAt(0).toUpperCase()}
          ${conv.is_online ? '<div class="status-dot"></div>' : ''}
        </div>
        <div class="chat-conversation-info">
          <div class="chat-conversation-name">
            ${this.escapeHtml(conv.other_username)}
          </div>
          <div class="chat-conversation-preview">
            ${this.escapeHtml(conv.last_message || 'Sin mensajes')}
          </div>
        </div>
        <div class="chat-conversation-meta">
          ${this.formatTime(conv.last_message_at)}
          ${conv.unread_count > 0 ? `<div class="chat-conversation-unread">${conv.unread_count}</div>` : ''}
        </div>
      `;
      
      item.addEventListener('click', () => {
        this.openChat({
          id: conv.other_user_id,
          username: conv.other_username,
          isOnline: conv.is_online
        });
      });
      
      list.appendChild(item);
    });
  }

  async openChat(user) {
    this.currentChatUser = user;
    this.messages = [];
    
    // Update header
    document.getElementById('chatAvatarText').textContent = user.username.charAt(0).toUpperCase();
    document.getElementById('chatHeaderTitle').textContent = user.username;
    document.getElementById('chatHeaderSubtitle').textContent = user.isOnline ? 'En línea' : 'Desconectado';
    document.getElementById('chatStatusDot').className = `status-dot ${user.isOnline ? '' : 'offline'}`;
    document.getElementById('chatBackBtn').style.display = 'flex';
    document.getElementById('chatFooter').style.display = 'block';
    
    // Load messages
    try {
      const response = await fetch(`/public/api/chat_api.php?action=get_messages&user_id=${user.id}`);
      const data = await response.json();
      
      if (data.success) {
        this.messages = data.messages;
        this.renderMessages();
        
        // Mark as read
        this.chatClient.markAsRead(user.id);
      }
    } catch (error) {
      console.error('Error loading messages:', error);
    }
  }

  renderMessages() {
    const body = document.getElementById('chatBody');
    body.innerHTML = '';
    
    if (this.messages.length === 0) {
      body.innerHTML = `
        <div class="chat-empty-state">
          <div class="chat-empty-state-icon">✉️</div>
          <div class="chat-empty-state-text">No hay mensajes aún.<br>¡Envía el primero!</div>
        </div>
      `;
      return;
    }
    
    this.messages.forEach(msg => {
      this.renderMessage(msg);
    });
    
    this.scrollToBottom();
  }

  renderMessage(msg) {
    const body = document.getElementById('chatBody');
    const isSent = msg.sender_id == this.userId;
    
    const messageEl = document.createElement('div');
    messageEl.className = `chat-message ${isSent ? 'sent' : 'received'}`;
    messageEl.innerHTML = `
      <div class="chat-message-avatar">
        ${isSent ? this.username.charAt(0).toUpperCase() : this.currentChatUser.username.charAt(0).toUpperCase()}
      </div>
      <div class="chat-message-bubble">
        <p class="chat-message-text">${this.escapeHtml(msg.message)}</p>
        <span class="chat-message-time">${this.formatTime(msg.created_at)}</span>
      </div>
    `;
    
    body.appendChild(messageEl);
  }

  sendMessage() {
    const input = document.getElementById('chatInput');
    const message = input.value.trim();
    
    if (!message || !this.currentChatUser) return;
    
    this.chatClient.sendMessage(this.currentChatUser.id, message);
    input.value = '';
    input.style.height = 'auto';
    document.getElementById('chatSendBtn').disabled = true;
  }

  handleNewMessage(data) {
    if (this.currentChatUser && data.senderId == this.currentChatUser.id) {
      this.messages.push(data);
      this.renderMessage(data);
      this.scrollToBottom();
      this.chatClient.markAsRead(data.senderId);
    } else {
      // Update unread count
      this.unreadCount++;
      this.updateUnreadBadge(this.unreadCount);
      
      // Show notification
      this.showNotification(`Nuevo mensaje de ${data.senderUsername}`);
    }
  }

  handleMessageSent(data) {
    this.messages.push(data);
    this.renderMessage(data);
    this.scrollToBottom();
  }

  handleUserStatus(data) {
    if (this.currentChatUser && data.userId == this.currentChatUser.id) {
      this.currentChatUser.isOnline = data.isOnline;
      document.getElementById('chatHeaderSubtitle').textContent = data.isOnline ? 'En línea' : 'Desconectado';
      document.getElementById('chatStatusDot').className = `status-dot ${data.isOnline ? '' : 'offline'}`;
    }
  }

  handleUserTyping(data) {
    if (this.currentChatUser && data.userId == this.currentChatUser.id) {
      const indicator = document.getElementById('chatTypingIndicator');
      const text = document.getElementById('chatTypingText');
      
      if (data.isTyping) {
        text.textContent = `${data.username} está escribiendo...`;
        indicator.style.display = 'flex';
      } else {
        indicator.style.display = 'none';
      }
    }
  }

  showConversations() {
    this.currentChatUser = null;
    document.getElementById('chatHeaderTitle').textContent = 'Chat';
    document.getElementById('chatHeaderSubtitle').textContent = 'Selecciona un contacto';
    document.getElementById('chatBackBtn').style.display = 'none';
    document.getElementById('chatFooter').style.display = 'none';
    this.loadConversations();
  }

  toggleWidget() {
    const widget = document.getElementById('chatWidget');
    widget.classList.toggle('hidden');
    
    if (!widget.classList.contains('hidden')) {
      this.isMinimized = false;
      widget.classList.remove('minimized');
    }
  }

  minimizeWidget() {
    const widget = document.getElementById('chatWidget');
    widget.classList.toggle('minimized');
    this.isMinimized = !this.isMinimized;
  }

  closeWidget() {
    const widget = document.getElementById('chatWidget');
    widget.classList.add('hidden');
  }

  updateConnectionStatus(status, text) {
    const statusEl = document.getElementById('chatConnectionStatus');
    statusEl.className = `chat-connection-status ${status}`;
    statusEl.textContent = text;
    
    if (status === 'connected') {
      setTimeout(() => {
        statusEl.style.display = 'none';
      }, 2000);
    } else {
      statusEl.style.display = 'block';
    }
  }

  updateUnreadBadge(count) {
    this.unreadCount = count;
    const badge = document.getElementById('chatBadge');
    
    if (count > 0) {
      badge.textContent = count > 99 ? '99+' : count;
      badge.style.display = 'flex';
    } else {
      badge.style.display = 'none';
    }
  }

  showNotification(message) {
    if ('Notification' in window && Notification.permission === 'granted') {
      new Notification('App Tareas', {
        body: message,
        icon: '/public/manifest-icon-192.maskable.png'
      });
    }
  }

  scrollToBottom() {
    const body = document.getElementById('chatBody');
    body.scrollTop = body.scrollHeight;
  }

  formatTime(timestamp) {
    if (!timestamp) return '';
    const date = new Date(timestamp);
    const now = new Date();
    const diff = now - date;
    const minutes = Math.floor(diff / 60000);
    const hours = Math.floor(diff / 3600000);
    const days = Math.floor(diff / 86400000);
    
    if (minutes < 1) return 'Ahora';
    if (minutes < 60) return `${minutes}m`;
    if (hours < 24) return `${hours}h`;
    if (days < 7) return `${days}d`;
    return date.toLocaleDateString();
  }

  escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }
}

// Auto-initialize if user is logged in
document.addEventListener('DOMContentLoaded', () => {
  const userId = document.body.dataset.userId;
  const username = document.body.dataset.username;
  const sessionToken = document.body.dataset.sessionToken;
  
  if (userId && username) {
    window.chatWidget = new ChatWidget({
      userId: userId,
      username: username,
      sessionToken: sessionToken || userId,
      wsUrl: 'ws://20.81.210.24:8080'
    });
  }
});
