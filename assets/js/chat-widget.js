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

    document.getElementById('chatSendBtn')?.addEventListener('click', () => {
      this.sendMessage();
    });

    document.getElementById('chatInput')?.addEventListener('keydown', e => {
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

    document.getElementById('chatFooter').style.display = 'block';
    document.getElementById('chatHeaderTitle').textContent = user.other_username;

    const res = await fetch(`/public/api/chat_api.php?action=get_messages&user_id=${user.other_user_id}`);
    const data = await res.json();

    this.messages = data.messages || [];
    this.renderMessages();
  }

  renderMessages() {
    const body = document.getElementById('chatBody');
    body.innerHTML = '';

    this.messages.forEach(msg => {
      const div = document.createElement('div');
      div.textContent = msg.message;
      body.appendChild(div);
    });

    body.scrollTop = body.scrollHeight;
  }

  sendMessage() {
    const input = document.getElementById('chatInput');
    const message = input.value.trim();

    if (!message || !this.currentChatUser) return;

    this.chatClient.sendMessage(
      this.currentChatUser.other_user_id,
      message
    );

    input.value = '';
  }

  handleNewMessage(data) {
    this.messages.push(data);
    this.renderMessages();
  }

  handleMessageSent(data) {
    this.messages.push(data);
    this.renderMessages();
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
