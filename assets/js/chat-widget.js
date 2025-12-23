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
    
    // Sistema de notificaciones
    this.notificationSound = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBTGH0fPTgjMGHm7A7+OZSA0PVqzn77BdGAg+ltryxnMoBSuAze/bkkMKFl+z6OyrWRkKSKDf8sFuJAUuhM7y0n0vBRtz0O7gjksMEVqr5O+0ZBsHPJnZ88p1KgYogdDu3JFCCRVftOrurVscCkii4PKybCMGMIXP8tWAMQYbbdLu4JBGDA9XrOXwsmAbBz2Y2PPJdigFKoHO79yQQgoVX7Lp7q1bHApJouDysm0kBjCFzvLVgTEGG23R7eCPRgsOVqzl8LJgGgc9mNjzyXYoBSqBzu/ckEIKFV+y6e6tWxwKSaLg8rJtJAYwhdDy1YExBhtu0e3gj0YLDlat5fCyYBoHPZjY88l2KAUqgc7v3JBCChZfsuqurVscCkqj4PKybCQGMIXP8tWAMQYbbdHu349GCw5Wrufws18bBjyY2PPJdSgFKYDO8NyQQgoVX7Hr765bHQpLo+HywmwkBjCEz/PVgDIGGmzR7t+PRgsNVavm8LNfHAY8mdjzx3YpBCqBzu/bj0IKFV6x6++tWxwLSqPh8sJsIwYwhM/y1YAxBhts0O7fj0ULDVWr5vCzXxsGPJjY88d2KQUqgc/v24lAChVesvns7V4bC0mi4fLCbSMGMoTP8tZ/MgYabM/v349GCg1Vq+fws18aBj2Y2PPHdikFKoHP79uJQAoVXrL57O1eGwtJouHywm0jBjKEz/LWfzIGGmzP79+PRgoNVazn8LNfGwY9mdj0yHYpBSqBz+/biUEKFV2y+eztXhoLSKLh8sJtIwYyhM/y1n8yBhpsz+/fj0YKDVWs5/CzXxsGPZnY88d2KQUqgc/v24lBChVdsvrr7F4aDEii4fLBbCIGMYTP89Z/MgYaa9Du349GCw1Xqufws18bBj2Z2fPHdikFKYHP79qIQQoVXbL56+1dGgxIouLywW0iBjGEz/PWfjMGGWvQ7+CPRgoOV6vo8LJgGwY8mdnzyHUpBSmBz+/aiEEKFl2y+evtXRoMSKLi8sFtIgYxhM/z1n4zBhlr0O/gj0YKDles5/CyYBsGPJnZ88h1KQUpgc/v2ohBChZdsvnr7V0aDEmi4vLBbSIGMYPP89V+MwYZa9Dv4I9GCw5Xq+fwsWAcBjyZ2fPIdSkFKYHP79qIQQoWXbL56+1dGgxJouLywW0iBjGDz/PVfjMGGWvQ7+CPRgsOV6rn8LFgHAY8mdrzyHUpBSmBz+/aiEEKFl2y+evtXRoMSaLi8sFtIgYxhM/y1n4yBhpr0O/gj0YLDliq5/CyYBwGO5nZ88d1KQUpgc/v2ohBChZesvnr7V0aDEmj4vLBbSIGMYPP8tZ+MgYaatDv4I9GCw5Yqufwsl8cBjuZ2fPHdCkFKYHP79qIQQoWXrL56+1dGgxJo+LywW0iBjGDz/LWfjIGGmrQ7+CPRgsOWKrn8LJfGwY7mdnzx3QpBSmBz+/aiEEKFl6y+evtXRoMSaPi8sFtIgYxg8/y1n4yBhpq0O/gj0YLDliq5/CyXxsGO5nZ88d0KQUpgc/v2ohBChZesvnr7V0aDEmj4vLBbSIGMYPP8tZ+MgYaatDv4I9GCw5Yqufwsl8bBjuZ2fPHdCkFKYHP79qIQQoWXrL56+1dGgxJo+LywW0iBjGDz/LWfjIGGmrQ7+CPRgsOWKrn8LJfGwY7mdnzx3QpBSmBz+/aiEEKFl6y+evtXRoMSaPi8sFtIgYxg8/y1n4yBhpq0O/gj0YLDliq5/CyXxsGO5nZ88d0KQUpgc/v2ohBChZesvnr7V0aDEmj4vLBbSIGMYPP8tZ+MgYaatDv4I9GCw5Yqufwsl8bBjuZ2fPHdCkFKYHP79qIQQoWXrL56+1dGgxJo+LywW0iBjGDz/LWfjIGGmrQ7+CPRgsOWKrn8LJfGwY7mdnzx3QpBSmBz+/aiEEKFl6y+evtXRoMSaPi8sFtIgYxg8/y1n4yBhpq0O/gj0YLDliq5/CyXxsGO5nZ88d0KQUpgc/v2ohBChZesvnr7V0aDEmj4vLBbSIGMYPP8tZ+MgYaatDv4I9GCw5Yqufwsl8bBjuZ2fPHdCkFKYHP79qIQQoWXrL56+xdGgxJo+LywW0iBjGDz/LWfjIGGmrQ7+CPRgsOWKrn8LJfGwY7mdnzx3QpBSmBz+/aiEEKFl6y+evtXBoMSaPi8sFtIgYxg8/y1n4yBhpq0O/gj0YLDliq5/CyXxsGO5nZ88d0KQUpgc/v2ohBChZesvnr7V0aDEmj4vLBbSIGMYPP8tZ+MgYaatDv4I9GCw5Yqufwsl8bBjuZ2fPHdCkFKYHP79qIQQoWXrL56+1dGgxJo+LywW0iBjGDz/LWfjIGGmrQ7+CPRgsOV6vn8LJfGwY7mdrzx3QoBSmBz+/aiEEKFl6y+evtXRoMSaPi8sFtIgYxg8/y1n4yBhlq0e/gj0YLDleq5/CzXxsGO5nZ88h0KAUpgdDv2ohBChVfsvns7l0ZDEqj4vLCbSEGMYPP89Z+MgYZatDv4I9GCw5Xq+bwsV8bBjyZ2vPHdCcFKIHQ79qIQQoVXrP57O1dGQxKo+LywW0hBjGEz/PVfzEGGWvQ7+GPRgoOV6vm8LNfGwY8mdrzx3QoBSiBz+/aiEAKFV6z+eztXRkMSqLi8sFtIQYxhM/z1X8xBhlr0O/gj0YKDles5vCzXxsGPJna88d0KAUogc/v2ohAChVes/ns7V0ZDEqi4vLBbSEGMYTP89V/MQYZa9Dv4I9GCg5XrObws18bBjyZ2vPHdCgFKIHP79uIQAoVXrP57O1dGQxKouLywW0hBjGEz/PVfzEGGWvQ7+CPRgoOV6zm8LNfGwY8mdrzx3QoBSiBz+/biEAKFV6y+eztXRkMSqLi8sFtIQYxhM/z1X8xBhlr0O/gj0YKDles5vCzXxsGPJna88d0KAUogc/v24hAChVesvns7V0ZDEqi4vLBbSEGMYTP89V/MQYZa9Dv4I9GCg5XrObws18bBjyZ2vPHdCgFKIHP79uIQAoVXrL57O1dGQxKouLywW0hBjGEz/PVfzEGGWvQ7+CPRgoOV6zm8LNfGwY8mdrzx3QoBSiBz+/biEAKFV6y+eztXRkMSqLi8sFtIQYxhM/z1X8xBhlr0O/gj0YKDles5vCzXxsGPJna88d0KAUogc/v24hAChVesvns7V0ZDEqi4vLBbSEGMYTP89V/MQYZa9Dv4I9GCg5XrObws18bBjyZ2vPHdCgFKIHP79uIQAoVXrL57O1dGQxKouLywW0hBjGEz/PVfzEGGWvQ7+CPRgoOV6zm8LNfGwY8mdrzx3QoBSiBz+/biEAKFV6y+eztXRkMSqLi8sFtIQYxhM/z1X8xBhlr0O/gj0YKDles5vCzXxsGPJna88d0KAUogc/v24hAChVesvns7V0ZDEqi4vLBbSEGMYTP89V/MQYZa9Dv4I9GCg5XrObws18bBjyZ2vPHdCgFKIHP79uIQAoVXrL57O1dGQxKouLywW0hBjGEz/PVfzEGGWvQ7+CPRgoOV6zm8LNfGwY8mdrzx3QoBSiBz+/biEAKFV6y+eztXRkMSqLi8sFtIQYxhM/z1X8xBhlr0O/gj0YKDles5vCzXxsGPJna88d0KAUogc/v24hAChVesvns7V0ZDEqi4vLBbSEGMYTP89V/MQYZa9Dv4I9GCg5XrObws18bBjyZ2vPHdCgFKIHP79uIQAoVXrL57O1dGQxKouLywW0hBjGEz/PVfzEGGWvQ7+CPRgoOV6zm8LNfGwY8mdrzx3QoBSiBz+/biEAKFV6y+eztXRkMSqLi8sFtIQYxhM/z1X8xBhlr0O/gj0YKDles5vCzXxsGPJna88d0KAUogc/v24hAChVesvns7V0ZDEqi4vLBbSEGMYTP89V/MQYZa9Dv4I9GCg==');
    this.originalTitle = document.title;
    this.titleBlinkInterval = null;
    this.requestNotificationPermission();

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
            <button id="chatBackBtn" style="display:none" title="Volver">←</button>
            <button id="chatNewChatBtn" title="💬 Iniciar nueva conversación - Click aquí para ver todos los usuarios" style="position: relative;">
              👥
              <span style="position: absolute; top: -4px; right: -4px; background: #ef4444; color: white; border-radius: 50%; width: 16px; height: 16px; font-size: 10px; display: flex; align-items: center; justify-content: center; font-weight: bold; border: 2px solid white;">+</span>
            </button>
            <button id="chatMinimizeBtn" title="Minimizar">−</button>
            <button id="chatCloseBtn" title="Cerrar">×</button>
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
      // Si no hay conversaciones, mostrar automáticamente la lista de usuarios
      this.showUsersList();
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
    
    // Agregar banner para iniciar nuevas conversaciones
    const newChatBanner = document.createElement('div');
    newChatBanner.style.cssText = 'padding: 16px; margin: 12px; background: linear-gradient(135deg, rgba(37, 99, 235, 0.1) 0%, rgba(30, 64, 175, 0.1) 100%); border: 2px dashed rgba(37, 99, 235, 0.3); border-radius: 12px; text-align: center; cursor: pointer; transition: all 0.3s ease;';
    newChatBanner.innerHTML = `
      <div style="font-size: 2rem; margin-bottom: 8px;">👥</div>
      <div style="font-size: 0.9rem; color: #64748b;">Click aquí para ver todos los usuarios disponibles</div>
    `;
    newChatBanner.onmouseover = () => {
      newChatBanner.style.background = 'linear-gradient(135deg, rgba(37, 99, 235, 0.2) 0%, rgba(30, 64, 175, 0.2) 100%)';
      newChatBanner.style.transform = 'scale(1.02)';
    };
    newChatBanner.onmouseout = () => {
      newChatBanner.style.background = 'linear-gradient(135deg, rgba(37, 99, 235, 0.1) 0%, rgba(30, 64, 175, 0.1) 100%)';
      newChatBanner.style.transform = 'scale(1)';
    };
    newChatBanner.onclick = () => this.showUsersList();
    body.appendChild(newChatBanner);
  }
  
  async showUsersList() {
    try {
      const res = await fetch('/public/api/chat_api.php?action=get_available_users');
      const data = await res.json();
      
      console.log('📋 Available users:', data);

      const body = document.getElementById('chatBody');
      const headerTitle = document.getElementById('chatHeaderTitle');
      const headerSubtitle = document.getElementById('chatHeaderSubtitle');
      const backBtn = document.getElementById('chatBackBtn');
      
      body.innerHTML = '';
      headerTitle.textContent = 'Usuarios';
      headerSubtitle.textContent = 'Selecciona para chatear';
      backBtn.style.display = 'block';

      if (!data.success || !data.users || data.users.length === 0) {
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
    } catch (error) {
      console.error('❌ Error loading users:', error);
      const body = document.getElementById('chatBody');
      body.innerHTML = `
        <div class="chat-empty-state">
          <div class="chat-empty-state-icon">⚠️</div>
          <p class="chat-empty-state-text">Error al cargar usuarios</p>
        </div>
      `;
    }
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
      
      // Marcar como leído si la ventana está visible
      if (!document.hidden) {
        this.chatClient.markAsRead(data.senderId || data.sender_id);
      }
    } else {
      // Actualizar contador de no leídos
      this.unreadCount++;
      this.updateBadge();
      
      // Notificar al usuario
      this.showNotification(data);
      this.playNotificationSound();
      this.startTitleBlink();
    }
    
    // Recargar lista de conversaciones para actualizar
    if (!this.currentChatUser) {
      this.loadConversations();
    }
  }

  toggleWidget() {
    const widget = document.getElementById('chatWidget');
    if (widget) {
      widget.classList.toggle('hidden');
      this.isMinimized = widget.classList.contains('hidden');
      
      if (!widget.classList.contains('hidden')) {
        widget.classList.remove('minimized');
        // Detener notificaciones visuales al abrir el chat
        this.stopTitleBlink();
        // Resetear contador si se abre el chat
        this.unreadCount = 0;
        this.updateBadge();
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

  // Solicitar permiso para notificaciones
  requestNotificationPermission() {
    if ('Notification' in window && Notification.permission === 'default') {
      Notification.requestPermission();
    }
  }

  // Reproducir sonido de notificación
  playNotificationSound() {
    try {
      this.notificationSound.play().catch(err => {
        console.log('No se pudo reproducir el sonido:', err);
      });
    } catch (err) {
      console.log('Error al reproducir sonido:', err);
    }
  }

  // Mostrar notificación del navegador
  showNotification(data) {
    if ('Notification' in window && Notification.permission === 'granted') {
      const senderName = data.senderName || data.sender_name || 'Usuario';
      const messageText = data.content || data.message || 'Nuevo mensaje';
      
      // Truncar mensaje si es muy largo
      const shortMessage = messageText.length > 100 
        ? messageText.substring(0, 100) + '...' 
        : messageText;

      const notification = new Notification(`💬 ${senderName}`, {
        body: shortMessage,
        icon: '/assets/icon-192x192.png',
        badge: '/assets/icon-192x192.png',
        tag: 'chat-message',
        requireInteraction: false
      });

      // Al hacer clic en la notificación, abrir el chat
      notification.onclick = () => {
        window.focus();
        this.toggleWidget();
        if (this.isMinimized) {
          this.toggleWidget();
        }
        notification.close();
      };

      // Cerrar automáticamente después de 5 segundos
      setTimeout(() => notification.close(), 5000);
    }
  }

  // Parpadear el título de la página
  startTitleBlink() {
    // Detener parpadeo anterior si existe
    if (this.titleBlinkInterval) {
      clearInterval(this.titleBlinkInterval);
    }

    let count = 0;
    const maxBlinks = 10;
    
    this.titleBlinkInterval = setInterval(() => {
      document.title = count % 2 === 0 
        ? '🔴 Nuevo mensaje' 
        : this.originalTitle;
      
      count++;
      
      // Detener después de 10 parpadeos o si el usuario abre el chat
      if (count >= maxBlinks || !this.isMinimized) {
        clearInterval(this.titleBlinkInterval);
        document.title = this.originalTitle;
        this.titleBlinkInterval = null;
      }
    }, 1000);
  }

  // Detener parpadeo del título
  stopTitleBlink() {
    if (this.titleBlinkInterval) {
      clearInterval(this.titleBlinkInterval);
      this.titleBlinkInterval = null;
      document.title = this.originalTitle;
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
