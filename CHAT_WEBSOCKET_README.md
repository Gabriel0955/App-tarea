# Sistema de Chat en Vivo con WebSockets

Sistema de mensajería en tiempo real para App Tareas usando WebSockets con Node.js + PostgreSQL.

## 🎯 Características

✅ **Mensajería en tiempo real** - WebSocket bidireccional  
✅ **Indicadores de escritura** - "Usuario está escribiendo..."  
✅ **Estado online/offline** - Ver quién está conectado  
✅ **Notificaciones** - Badge con conteo de mensajes no leídos  
✅ **Historial persistente** - Mensajes guardados en PostgreSQL  
✅ **Permisos** - Solo supervisores y sus miembros pueden chatear  
✅ **Reconexión automática** - Si se pierde la conexión  
✅ **Responsive** - Funciona en móvil y desktop  

## 📦 Instalación

### Paso 1: Ejecutar migración SQL

```powershell
# En Azure Portal Query Editor, ejecutar:
c:\wamp64\www\App-Tareas\db\add_chat.sql
```

Esto crea 4 tablas:
- `chat_messages` - Mensajes
- `chat_conversations` - Conversaciones activas
- `chat_connections` - Conexiones WebSocket activas
- `chat_notifications` - Notificaciones de mensajes

### Paso 2: Instalar Node.js y dependencias

```powershell
# Instalar Node.js si no lo tienes
# Descargar de: https://nodejs.org/ (LTS 18+)

# Navegar al directorio del servidor WebSocket
cd c:\wamp64\www\App-Tareas\websocket

# Instalar dependencias
npm install
```

### Paso 3: Configurar variables de entorno

Edita `websocket\.env` con tus credenciales (ya está configurado):

```env
WS_PORT=8080
DB_HOST=apptarea.postgres.database.azure.com
DB_USER=apptarea
DB_PASS=Gabriel1405
DB_NAME=postgres
```

### Paso 4: Iniciar servidor WebSocket

```powershell
# En modo desarrollo (con auto-reload)
cd websocket
npm run dev

# O en modo producción
npm start
```

Deberías ver:
```
✅ Database connected: 2025-12-21...
🚀 WebSocket Chat Server running on ws://localhost:8080
```

### Paso 5: Integrar en tus páginas PHP

Agrega en el `<head>` o antes del `</body>`:

```php
<!-- En cualquier página donde quieras el chat -->
<link rel="stylesheet" href="/assets/css/chat.css">
<script src="/assets/js/chat-client.js"></script>
<script src="/assets/js/chat-widget.js"></script>

<!-- Contenedor para el chat -->
<div id="chat-widget-container"></div>

<!-- Pasar datos del usuario -->
<script>
  document.body.dataset.userId = '<?= $_SESSION['user_id'] ?>';
  document.body.dataset.username = '<?= $_SESSION['username'] ?>';
  document.body.dataset.sessionToken = '<?= session_id() ?>';
</script>
```

## 🖥️ Ejecutar en Producción (Azure)

### Opción 1: VM dedicada (RECOMENDADO)

La forma más sencilla y confiable es usar una VM Linux dedicada.

**Ver guía completa**: `websocket/VM_SETUP_GUIDE.md`

**Pasos rápidos**:

```powershell
# 1. Crear VM en Azure Portal
# - Ubuntu 20.04/22.04 LTS
# - Standard_B1s o superior
# - Puerto 8080 en NSG

# 2. Conectar por SSH
ssh azureuser@<IP-PUBLICA-VM>

# 3. Subir archivos
scp websocket/chat-server.js azureuser@<IP-VM>:~/
scp websocket/install-vm.sh azureuser@<IP-VM>:~/

# 4. Instalar (en la VM)
chmod +x install-vm.sh
sudo ./install-vm.sh

# 5. Configurar
sudo nano /opt/chatserver/.env
# Editar credenciales

# 6. Reiniciar
sudo systemctl restart chatserver
```

**Resultado**: 
- Servidor WebSocket corriendo en `ws://<IP-VM>:8080`
- Con SSL/Nginx: `wss://chat.tudominio.com`
- Servicio systemd auto-reinicio
- Logs en `/var/log/chatserver/`

### Opción 2: Azure App Service con Node.js

1. **Crear App Service** para Node.js:
   ```bash
   az webapp create --resource-group apptarea-rg --plan apptarea-plan --name apptarea-chat --runtime "NODE|18-lts"
   ```

2. **Configurar variables**:
   - WS_PORT=80
   - DB_HOST=apptarea.postgres.database.azure.com
   - DB_USER=apptarea
   - DB_PASS=Gabriel1405

3. **Deploy**:
   ```powershell
   cd websocket
   git init
   git add .
   git commit -m "Chat server"
   az webapp deployment source config-local-git --name apptarea-chat --resource-group apptarea-rg
   git remote add azure <URL>
   git push azure master
   ```

4. **Actualizar frontend**:
   ```javascript
   wsUrl: 'wss://apptarea-chat.azurewebsites.net'
   ```

### Opción 2: Azure Container Instances

1. **Crear Dockerfile**:
   ```dockerfile
   FROM node:18-alpine
   WORKDIR /app
   COPY package*.json ./
   RUN npm install --production
   COPY . .
   EXPOSE 8080
   CMD ["node", "chat-server.js"]
   ```

2. **Build y push**:
   ```bash
   docker build -t apptarea-chat .
   docker tag apptarea-chat apptarea.azurecr.io/chat:latest
   docker push apptarea.azurecr.io/chat:latest
   ```

3. **Deploy**:
   ```bash
   az container create --resource-group apptarea-rg --name apptarea-chat \
     --image apptarea.azurecr.io/chat:latest \
     --dns-name-label apptarea-chat \
     --ports 8080
   ```

### Opción 3: Ejecutar Local + ngrok (Para testing)

```powershell
# Instalar ngrok
choco install ngrok

# Iniciar servidor local
cd websocket
npm start

# En otra terminal, exponer con ngrok
ngrok http 8080

# Copiar la URL https://xxxx.ngrok.io y usar en frontend
```

## 🔧 Uso

### Abrir el chat

El chat se inicializa automáticamente si el usuario está logueado. Un botón flotante aparece en la esquina inferior derecha.

### Chatear con supervisor

1. Supervisor abre su dashboard: `/public/supervisor/team.php`
2. En cada tarjeta de miembro, hay un botón "💬 Chat"
3. Click abre el widget de chat con ese miembro

### Chatear con miembro del equipo

1. Miembro recibe notificación cuando supervisor le escribe
2. Click en el badge rojo del botón flotante
3. Selecciona la conversación con el supervisor

## 📡 API WebSocket

### Mensajes del Cliente → Servidor

**Autenticación**:
```json
{
  "type": "auth",
  "payload": {
    "userId": 123,
    "sessionToken": "abc123"
  }
}
```

**Enviar mensaje**:
```json
{
  "type": "send_message",
  "payload": {
    "receiverId": 456,
    "message": "Hola!"
  }
}
```

**Marcar como leído**:
```json
{
  "type": "mark_read",
  "payload": {
    "senderId": 456
  }
}
```

**Indicador de escritura**:
```json
{
  "type": "typing",
  "payload": {
    "receiverId": 456,
    "isTyping": true
  }
}
```

### Mensajes del Servidor → Cliente

**Nuevo mensaje**:
```json
{
  "type": "new_message",
  "payload": {
    "id": 789,
    "senderId": 456,
    "senderUsername": "Juan",
    "message": "Hola!",
    "createdAt": "2025-12-21T10:30:00Z"
  }
}
```

**Estado de usuario**:
```json
{
  "type": "user_status",
  "payload": {
    "userId": 456,
    "isOnline": true
  }
}
```

## 🔒 Permisos

- **Supervisor** puede chatear con sus miembros del equipo
- **Miembros** pueden chatear con su supervisor
- **Admin** puede chatear con todos
- Usuarios regulares solo pueden chatear con su supervisor asignado

Esto se valida en:
- `ChatService::canChatWith()` (Backend)
- Tabla `supervisor_teams` (Base de datos)

## 🧪 Testing

### Test 1: Verificar servidor WebSocket

```powershell
# Desde PowerShell
$ws = New-Object Net.WebSockets.ClientWebSocket
$uri = [Uri]"ws://localhost:8080"
$ws.ConnectAsync($uri, [Threading.CancellationToken]::None).Wait()
echo $ws.State  # Debe ser "Open"
```

### Test 2: Enviar mensaje desde navegador

```javascript
// Abrir consola del navegador (F12)
const ws = new WebSocket('ws://localhost:8080');
ws.onopen = () => {
  ws.send(JSON.stringify({
    type: 'auth',
    payload: { userId: 1, sessionToken: 'test' }
  }));
};
ws.onmessage = (e) => console.log(JSON.parse(e.data));
```

### Test 3: Verificar base de datos

```sql
-- Ver conexiones activas
SELECT * FROM chat_connections;

-- Ver mensajes
SELECT * FROM chat_messages ORDER BY created_at DESC LIMIT 10;

-- Ver conversaciones
SELECT * FROM get_user_conversations(1);
```

## 📊 Monitoring

### Logs del servidor

```powershell
# Ver logs en tiempo real
cd websocket
npm start

# Los logs muestran:
# ✅ Conexiones nuevas
# 💬 Mensajes enviados/recibidos
# 📴 Desconexiones
# 🧹 Limpieza de conexiones inactivas
```

### Limpieza automática

El servidor limpia:
- Conexiones inactivas (>5 minutos sin ping) cada 5 minutos
- Heartbeat cada 30 segundos

## 🐛 Troubleshooting

### Error: "Database connection failed"

- Verifica credenciales en `.env`
- Asegúrate que PostgreSQL permite conexiones desde tu IP
- Azure PostgreSQL requiere SSL

### Error: "EADDRINUSE"

- El puerto 8080 ya está en uso
- Cambia `WS_PORT` en `.env`
- O mata el proceso: `npx kill-port 8080`

### Chat no conecta

- Verifica que el servidor WebSocket esté corriendo
- Revisa la consola del navegador (F12)
- Asegúrate que `wsUrl` en `chat-widget.js` sea correcto
- Si usas HTTPS, necesitas WSS (WebSocket Secure)

### Mensajes no se envían

- Verifica que el usuario esté autenticado
- Revisa permisos con `ChatService::canChatWith()`
- Verifica que supervisor_teams tenga la relación

## 🚀 Mejoras Futuras

- [ ] Envío de archivos/imágenes
- [ ] Mensajes de voz
- [ ] Videollamadas (WebRTC)
- [ ] Grupos de chat
- [ ] Encriptación end-to-end
- [ ] Emojis y reacciones
- [ ] Mensajes programados
- [ ] Chatbots con IA

## 📚 Dependencias

- **Node.js 18+** - Runtime JavaScript
- **ws** - Librería WebSocket para Node.js
- **pg** - Cliente PostgreSQL
- **dotenv** - Variables de entorno

## ⚙️ Configuración Avanzada

### Cambiar puerto WebSocket

```env
# .env
WS_PORT=9000
```

```javascript
// chat-widget.js
wsUrl: 'ws://localhost:9000'
```

### Habilitar CORS (si frontend está en otro dominio)

```javascript
// chat-server.js
const wss = new WebSocket.Server({ 
  port: PORT,
  verifyClient: (info) => {
    const origin = info.origin;
    // Permitir tu dominio
    return origin === 'https://tu-dominio.com';
  }
});
```

### SSL/TLS (WSS)

```javascript
const https = require('https');
const fs = require('fs');

const server = https.createServer({
  cert: fs.readFileSync('/path/to/cert.pem'),
  key: fs.readFileSync('/path/to/key.pem')
});

const wss = new WebSocket.Server({ server });
server.listen(8080);
```

## 📄 Licencia

Este sistema es parte de App Tareas.

---

**Autor**: GitHub Copilot + Gabriel  
**Fecha**: Diciembre 2025  
**Versión**: 1.0.0
