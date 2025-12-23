/**
 * Chat Widget Component
 * Real-time chat interface using WebSocket (WSS)
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
      <button class="chat-toggle-btn" id="chatToggleBtn">
        💬
        <span class="badge" id="chatBadge" style="display:none">0</span>
      </button>

      <div class="chat-widget hidden" id="chatWidget">
        <div class="chat-connection-status" id="chatConnectionStatus">
          Conectando...
        </div>

        <div class="chat-header">
          <div class="chat-header-info">
            <div class="chat-avatar">
              <span id="chatAvatarText">?</span>
              <div class="status-dot offline" id="chatStatusDot"></div>
            </div>
            <div>
              <h3 id="chatHeaderTitle">Chat</h3>
              <p id="chatHeaderSubtitle">Selecciona un contacto</p>
            </div>
          </div>
          <div>
            <button id="chatBackBtn" style="display:none">←</button>
            <button id="chatNewChatBtn" title="Nuevo chat">👥</button>
            <button id="chatMinimizeBtn">−</button>
            <button id="chatCloseBtn">×</button>
          </div>
        </div>

        <div class="chat-body" id="chatBody"></div>

        <div class="chat-footer" id="chatFooter" style="display:none">
          <div id="chatTypingIndicator" style="display:none">
            <span id="chatTypingText"></span>
          </div>
          <textarea id="chatInput" placeholder="Escribe un mensaje..."></textarea>
          <button id="chatSendBtn" disabled>➤</button>
        </div>
      </div>
    `;
  }

  connectWebSocket() {
    this.chatClient = new ChatClient(
      this.wsUrl,
      this.userId,
      this.sessionToken
    );

    this.chatClient.on('connected', () => {
      console.log('✅ Chat conectado');
      this.updateConnectionStatus('connected', 'Conectado');
    });

    this.chatClient.on('disconnected', () => {
      console.log('❌ Chat desconectado');
      this.updateConnectionStatus('disconnected', 'Desconectado');
    });

    this.chatClient.on('authenticated', () => {
      this.loadConversations();
    });

    this.chatClient.on('message_received', data => {
      this.handleNewMessage(data);
    });

    this.chatClient.on('message_sent', data => {
      this.handleMessageSent(data);
    });
    
    this.chatClient.on('new_message', data => {
      this.handleNewMessage(data);
    });

    this.chatClient.connect();
  }

  attachEventListeners() {
    document.getElementById('chatToggleBtn')?.addEventListener('click', () => {
      document.getElementById('chatWidget').classList.toggle('hidden');
      if (!document.getElementById('chatWidget').classList.contains('hidden')) {
        this.loadConversations();
      }
    });

    document.getElementById('chatMinimizeBtn')?.addEventListener('click', () => {
      document.getElementById('chatWidget').classList.toggle('minimized');
    });

    document.getElementById('chatCloseBtn')?.addEventListener('click', () => {
      document.getElementById('chatWidget').classList.add('hidden');
    });
    
    document.getElementById('chatNewChatBtn')?.addEventListener('click', () => {
      this.showUsersList();
    });
    
    document.getElementById('chatBackBtn')?.addEventListener('click', () => {
      this.loadConversations();
      document.getElementById('chatBackBtn').style.display = 'none';
      document.getElementById('chatFooter').style.display = 'none';
    });

    const sendBtn = document.getElementById('chatSendBtn');
    const input = document.getElementById('chatInput');

    sendBtn?.addEventListener('click', () => {
      this.sendMessage();
    });

    input?.addEventListener('input', (e) => {
      const hasText = e.target.value.trim().length > 0;
      sendBtn.disabled = !hasText;
      
      e.target.style.height = 'auto';
      e.target.style.height = e.target.scrollHeight + 'px';
    });

    input?.addEventListener('keydown', e => {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        this.sendMessage();
      }
    });
  }

  async loadConversations() {
    const res = await fetch('/public/api/chat_api.php?action=get_conversations');
    const data = await res.json();

    const body = document.getElementById('chatBody');
    const headerTitle = document.getElementById('chatHeaderTitle');
    const headerSubtitle = document.getElementById('chatHeaderSubtitle');
    const backBtn = document.getElementById('chatBackBtn');
    
    body.innerHTML = '';
    headerTitle.textContent = 'Conversaciones';
    headerSubtitle.textContent = 'Selecciona una conversación';
    backBtn.style.display = 'none';

    if (!data.success || data.conversations.length === 0) {
      body.innerHTML = `
        <div class="chat-empty-state">
          <div class="chat-empty-state-icon">💬</div>
          <p class="chat-empty-state-text">No tienes conversaciones aún</p>
          <button onclick="window.chatWidget.showUsersList()" style="margin-top: 12px; padding: 8px 16px; border-radius: 20px; border: none; background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; cursor: pointer;">Iniciar chat</button>
        </div>
      `;
      return;
    }

    data.conversations.forEach(conv => {
      const el = document.createElement('div');
      el.className = 'chat-conversation-item';
      el.innerHTML = `
        <div class="chat-conversation-avatar">
          ${conv.other_username.charAt(0).toUpperCase()}
        </div>
        <div class="chat-conversation-info">
          <div class="chat-conversation-name">${this.escapeHtml(conv.other_username)}</div>
          <div class="chat-conversation-preview">${this.escapeHtml(conv.last_message || 'Sin mensajes')}</div>
        </div>
        <div class="chat-conversation-meta">
          ${conv.last_message_at ? this.formatTime(conv.last_message_at) : ''}
          ${conv.unread_count > 0 ? `<div class="chat-conversation-unread">${conv.unread_count}</div>` : ''}
        </div>
      `;
      el.onclick = () => this.openChat(conv);
      body.appendChild(el);
    });
  }
  
  async showUsersList() {
    const res = await fetch('/public/api/chat_api.php?action=get_available_users');
    const data = await res.json();

    const body = document.getElementById('chatBody');
    const headerTitle = document.getElementById('chatHeaderTitle');
    const headerSubtitle = document.getElementById('chatHeaderSubtitle');
    const backBtn = document.getElementById('chatBackBtn');
    
    body.innerHTML = '';
    headerTitle.textContent = 'Nuevo chat';
    headerSubtitle.textContent = 'Selecciona un usuario';
    backBtn.style.display = 'block';

    if (!data.success || data.users.length === 0) {
      body.innerHTML = `
        <div class="chat-empty-state">
          <div class="chat-empty-state-icon">👥</div>
          <p class="chat-empty-state-text">No hay usuarios disponibles</p>
        </div>
      `;
      return;
    }

    data.users.forEach(user => {
      const el = document.createElement('div');
      el.className = 'chat-conversation-item';
      el.innerHTML = `
        <div class="chat-conversation-avatar">
          ${user.username.charAt(0).toUpperCase()}
        </div>
        <div class="chat-conversation-info">
          <div class="chat-conversation-name">${this.escapeHtml(user.username)}</div>
          <div class="chat-conversation-preview">${this.escapeHtml(user.role || 'Usuario')}</div>
        </div>
      `;
      el.onclick = () => this.openChat(user);
      body.appendChild(el);
    });
  }

  async openChat(user) {
    this.currentChatUser = user;
    
    const userId = user.other_user_id || user.id;
    const username = user.other_username || user.username;
    const isOnline = user.isOnline !== undefined ? user.isOnline : false;

    document.getElementById('chatFooter').style.display = 'block';
    document.getElementById('chatHeaderTitle').textContent = username;
    document.getElementById('chatHeaderSubtitle').textContent = isOnline ? 'En línea' : 'Desconectado';
    document.getElementById('chatBackBtn').style.display = 'block';

    try {
      const res = await fetch(`/public/api/chat_api.php?action=get_messages&user_id=${userId}`);
      const data = await res.json();

      if (data.success) {
        this.messages = data.messages || [];
        this.renderMessages();
        
        // Guardar el ID normalizado
        this.currentChatUser.userId = userId;
        this.currentChatUser.username = username;
      } else {
        console.error('Error loading messages:', data.error);
        alert(data.error || 'Error al cargar mensajes');
      }
    } catch (error) {
      console.error('Error fetching messages:', error);
      alert('Error de conexión al cargar mensajes');
    }
  }

  renderMessages() {
    const body = document.getElementById('chatBody');
    body.innerHTML = '';

    this.messages.forEach(msg => {
      const isSent = msg.sender_id === this.userId || msg.senderId === this.userId;
      const messageDiv = document.createElement('div');
      messageDiv.className = `chat-message ${isSent ? 'sent' : 'received'}`;
      
      messageDiv.innerHTML = `
        <div class="chat-message-avatar">
          ${isSent ? this.username.charAt(0).toUpperCase() : (this.currentChatUser.username || 'U').charAt(0).toUpperCase()}
        </div>
        <div class="chat-message-bubble">
          <p class="chat-message-text">${this.escapeHtml(msg.message)}</p>
          <span class="chat-message-time">${this.formatTime(msg.created_at || msg.createdAt)}</span>
        </div>
      `;
      
      body.appendChild(messageDiv);
    });

    body.scrollTop = body.scrollHeight;
  }
  
  escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }
  
  formatTime(timestamp) {
    const date = new Date(timestamp);
    return date.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
  }

  sendMessage() {
    const input = document.getElementById('chatInput');
    const sendBtn = document.getElementById('chatSendBtn');
    const message = input.value.trim();

    if (!message || !this.currentChatUser) return;

    const receiverId = this.currentChatUser.userId || this.currentChatUser.other_user_id || this.currentChatUser.id;

    this.chatClient.sendMessage(
      receiverId,
      message
    );

    input.value = '';
    input.style.height = 'auto';
    sendBtn.disabled = true;
  }

  handleNewMessage(data) {
    this.messages.push(data);
    this.renderMessages();
  }

  handleMessageSent(data) {
    this.messages.push(data);
    this.renderMessages();
    
    // Limpiar input
    const input = document.getElementById('chatInput');
    if (input) {
      input.value = '';
      input.style.height = 'auto';
    }
  }
  
  handleNewMessage(data) {
    // Si el mensaje es del chat actual, agregarlo
    if (this.currentChatUser && 
        (data.senderId === this.currentChatUser.userId || 
         data.sender_id === this.currentChatUser.userId)) {
      this.messages.push(data);
      this.renderMessages();
      
      // Marcar como leído
      this.chatClient.markAsRead(data.senderId || data.sender_id);
    } else {
      // Actualizar contador de no leídos
      this.unreadCount++;
      this.updateBadge();
    }
  }

  toggleWidget() {
    const widget = document.getElementById('chatWidget');
    if (widget) {
      widget.classList.toggle('hidden');
      if (!widget.classList.contains('hidden')) {
        widget.classList.remove('minimized');
      }
    }
  }
  
  updateBadge() {
    const badge = document.getElementById('chatBadge');
    if (badge) {
      if (this.unreadCount > 0) {
        badge.textContent = this.unreadCount > 99 ? '99+' : this.unreadCount;
        badge.style.display = 'flex';
      } else {
        badge.style.display = 'none';
      }
    }
  }

  updateConnectionStatus(status, text) {
    const el = document.getElementById('chatConnectionStatus');
    el.textContent = text;
    el.className = `chat-connection-status ${status}`;
    if (status === 'connected') {
      setTimeout(() => (el.style.display = 'none'), 2000);
    }
  }
}

/**
 * Auto init
 */
document.addEventListener('DOMContentLoaded', () => {
  const userId = document.body.dataset.userId;
  const username = document.body.dataset.username;
  const sessionToken = document.body.dataset.sessionToken;

  if (!userId || !username) return;

  window.chatWidget = new ChatWidget({
    userId,
    username,
    sessionToken,
    wsUrl: 'wss://socket.integra-code.org'
  });
});
