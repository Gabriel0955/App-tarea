# 🔔 Sistema de Notificaciones del Chat

## Descripción

Sistema completo de notificaciones para alertar a los usuarios cuando reciben nuevos mensajes en el chat, incluso cuando la aplicación está minimizada o en segundo plano.

## Funcionalidades Implementadas

### 1. 🔊 Sonido de Notificación
- **Sonido embebido**: Audio integrado directamente en el código (no requiere archivos externos)
- **Reproducción automática**: Se reproduce cuando llega un nuevo mensaje
- **Volumen moderado**: Configurado al 50% para no ser intrusivo
- **Manejo de errores**: Captura errores de reproducción automática del navegador

### 2. 💬 Notificación del Navegador
- **API de Notifications**: Utiliza la API nativa del navegador
- **Solicitud de permisos**: Pide permiso al usuario al iniciar el widget
- **Notificación informativa**: Muestra:
  - Nombre del remitente con emoji 💬
  - Texto del mensaje (truncado a 100 caracteres)
  - Icono de la aplicación
- **Interactividad**: Al hacer clic en la notificación:
  - Trae la ventana del navegador al frente
  - Abre el chat automáticamente
- **Auto-cierre**: Se cierra automáticamente después de 5 segundos

### 3. 🔴 Título Parpadeante
- **Indicador visual**: El título de la página parpadea entre:
  - "🔴 Nuevo mensaje"
  - Título original de la página
- **Duración limitada**: Parpadea 10 veces (10 segundos)
- **Detención inteligente**: Se detiene automáticamente cuando:
  - El usuario abre el chat
  - Completa los 10 parpadeos
- **Restauración**: Vuelve al título original al finalizar

### 4. 📛 Badge con Contador
- **Contador de mensajes**: Muestra el número de mensajes no leídos
- **Actualización en tiempo real**: Se incrementa con cada mensaje nuevo
- **Reseteo automático**: Se limpia cuando el usuario abre el chat
- **Estilo distintivo**: Círculo rojo con número blanco

## Flujo de Funcionamiento

```
1. Llega nuevo mensaje
   ↓
2. ¿Es del chat actual abierto?
   ├─ SÍ → Mostrar mensaje directamente
   └─ NO → Activar notificaciones:
       ├─ Incrementar contador (badge)
       ├─ Reproducir sonido 🔊
       ├─ Mostrar notificación del navegador 💬
       └─ Iniciar parpadeo del título 🔴

3. Usuario abre el chat
   ↓
4. Detener todas las notificaciones:
   ├─ Resetear contador a 0
   ├─ Detener parpadeo del título
   └─ Ocultar badge
```

## Código Técnico

### Constructor del Widget
```javascript
constructor(options = {}) {
  // ... otras propiedades ...
  
  // Sistema de notificaciones
  this.notificationSound = new Audio('data:audio/wav;base64,...');
  this.originalTitle = document.title;
  this.titleBlinkInterval = null;
  this.requestNotificationPermission();
}
```

### Manejo de Mensajes Nuevos
```javascript
handleNewMessage(data) {
  if (this.currentChatUser && message_is_current_chat) {
    // Mostrar directamente
    this.renderMessages();
  } else {
    // Activar notificaciones
    this.unreadCount++;
    this.updateBadge();
    this.showNotification(data);
    this.playNotificationSound();
    this.startTitleBlink();
  }
}
```

### Métodos de Notificación

#### Reproducir Sonido
```javascript
playNotificationSound() {
  this.notificationSound.play().catch(err => {
    console.log('No se pudo reproducir el sonido:', err);
  });
}
```

#### Notificación del Navegador
```javascript
showNotification(data) {
  if (Notification.permission === 'granted') {
    const notification = new Notification(`💬 ${senderName}`, {
      body: messageText,
      icon: '/assets/icon-192x192.png',
      tag: 'chat-message',
      requireInteraction: false
    });
    
    notification.onclick = () => {
      window.focus();
      this.toggleWidget();
    };
    
    setTimeout(() => notification.close(), 5000);
  }
}
```

#### Parpadeo del Título
```javascript
startTitleBlink() {
  this.titleBlinkInterval = setInterval(() => {
    document.title = count % 2 === 0 
      ? '🔴 Nuevo mensaje' 
      : this.originalTitle;
    
    if (count >= 10 || !this.isMinimized) {
      this.stopTitleBlink();
    }
  }, 1000);
}
```

## Compatibilidad

### Navegadores Soportados
- ✅ Chrome/Edge (v22+)
- ✅ Firefox (v22+)
- ✅ Safari (v7+)
- ✅ Opera (v25+)

### Permisos Requeridos
- **Notificaciones**: El usuario debe conceder permiso para las notificaciones del navegador
- **Reproducción automática**: Algunos navegadores pueden bloquear el sonido si el usuario no ha interactuado con la página

## Comportamiento Según Estado

### Chat Minimizado
- ✅ Sonido activado
- ✅ Notificación del navegador
- ✅ Título parpadeante
- ✅ Badge con contador

### Chat Abierto y Conversación Activa
- ❌ Sin notificaciones
- ✅ Mensaje se muestra directamente
- ✅ Marcado automático como leído

### Chat Abierto pero en Otra Conversación
- ✅ Sonido activado
- ✅ Notificación del navegador
- ✅ Título parpadeante
- ✅ Badge con contador

## Mejoras Futuras Posibles

1. **Configuración de usuario**:
   - Activar/desactivar sonido
   - Activar/desactivar notificaciones
   - Cambiar volumen del sonido

2. **Sonidos personalizables**:
   - Diferentes sonidos para diferentes tipos de mensajes
   - Carga de sonidos personalizados

3. **Prioridad de notificaciones**:
   - Notificaciones más destacadas para mensajes urgentes
   - Repetición de sonido para mensajes importantes

4. **Historial de notificaciones**:
   - Registro de notificaciones recibidas
   - Centro de notificaciones en la aplicación

## Problemas Conocidos y Soluciones

### Sonido No Se Reproduce
**Problema**: Algunos navegadores bloquean la reproducción automática de audio.
**Solución**: El código maneja este error silenciosamente. El usuario debe interactuar con la página primero.

### Notificación No Aparece
**Problema**: El usuario no ha concedido permisos de notificación.
**Solución**: El sistema solicita permisos al cargar el widget. Si el usuario los deniega, las otras alertas (sonido y título) seguirán funcionando.

### Título No Parpadea
**Problema**: La pestaña está activa o el chat ya está abierto.
**Solución**: Esto es intencional - no se parpadea cuando el usuario ya está viendo la aplicación.

## Testing

### Para Probar las Notificaciones
1. Abrir la aplicación en un navegador
2. Conceder permisos de notificación si se solicita
3. Minimizar el chat o cambiar de pestaña
4. Enviar un mensaje desde otro usuario/navegador
5. Verificar:
   - ✅ Sonido se reproduce
   - ✅ Aparece notificación del navegador
   - ✅ Título de la pestaña parpadea
   - ✅ Badge muestra el contador

### Testing Multi-navegador
1. Usuario A: Chrome
2. Usuario B: Firefox
3. Enviar mensajes cruzados
4. Verificar funcionamiento en ambos navegadores

---

**Última actualización**: Diciembre 2024
**Versión**: 1.0.0
**Estado**: ✅ Implementado y funcional
