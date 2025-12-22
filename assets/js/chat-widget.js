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
    });

    document.getElementById('chatMinimizeBtn')?.addEventListener('click', () => {
      document.getElementById('chatWidget').classList.toggle('minimized');
    });

    document.getElementById('chatCloseBtn')?.addEventListener('click', () => {
      document.getElementById('chatWidget').classList.add('hidden');
    });

    const sendBtn = document.getElementById('chatSendBtn');
    const input = document.getElementById('chatInput');

    sendBtn?.addEventListener('click', () => {
      this.sendMessage();
    });

    input?.addEventListener('input', (e) => {
      // Habilitar/deshabilitar botón según haya texto
      const hasText = e.target.value.trim().length > 0;
      sendBtn.disabled = !hasText;
      
      // Auto-resize del textarea
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
    body.innerHTML = '';

    if (!data.success || data.conversations.length === 0) {
      body.innerHTML = '<p>No hay conversaciones</p>';
      return;
    }

    data.conversations.forEach(conv => {
      const el = document.createElement('div');
      el.textContent = conv.other_username;
      el.onclick = () => this.openChat(conv);
      body.appendChild(el);
    });
  }

  async openChat(user) {
    this.currentChatUser = user;
    
    // Normalizar el objeto usuario (puede venir de diferentes fuentes)
    const userId = user.other_user_id || user.id;
    const username = user.other_username || user.username;
    const isOnline = user.isOnline !== undefined ? user.isOnline : false;

    document.getElementById('chatFooter').style.display = 'block';
    document.getElementById('chatHeaderTitle').textContent = username;

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
