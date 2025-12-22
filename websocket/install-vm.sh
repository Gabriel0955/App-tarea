#!/bin/bash
# Script de instalación para servidor WebSocket en VM
# Ubuntu 20.04/22.04 LTS

echo "=========================================="
echo "  WebSocket Chat Server - Instalación"
echo "=========================================="
echo ""

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Verificar que se ejecuta como root
if [ "$EUID" -ne 0 ]; then 
  echo -e "${RED}❌ Por favor ejecuta este script como root (sudo)${NC}"
  exit 1
fi

# 1. Actualizar sistema
echo -e "${YELLOW}📦 Actualizando sistema...${NC}"
apt update && apt upgrade -y

# 2. Instalar Node.js 18 LTS
echo -e "${YELLOW}📦 Instalando Node.js 18 LTS...${NC}"
curl -fsSL https://deb.nodesource.com/setup_18.x | bash -
apt install -y nodejs

# Verificar instalación
node -v
npm -v

# 3. Instalar PM2 (Process Manager)
echo -e "${YELLOW}📦 Instalando PM2...${NC}"
npm install -g pm2

# 4. Crear usuario para el servidor
echo -e "${YELLOW}👤 Creando usuario 'chatserver'...${NC}"
useradd -r -s /bin/bash -d /opt/chatserver -m chatserver

# 5. Crear directorios
echo -e "${YELLOW}📁 Creando directorios...${NC}"
mkdir -p /opt/chatserver
mkdir -p /var/log/chatserver

# 6. Copiar archivos del proyecto
echo -e "${YELLOW}📄 Configurando archivos...${NC}"

# Crear package.json
cat > /opt/chatserver/package.json << 'EOF'
{
  "name": "app-tareas-chat-server",
  "version": "1.0.0",
  "description": "WebSocket Chat Server for App Tareas",
  "main": "chat-server.js",
  "scripts": {
    "start": "node chat-server.js"
  },
  "dependencies": {
    "ws": "^8.16.0",
    "pg": "^8.11.3",
    "dotenv": "^16.3.1"
  }
}
EOF

# Crear archivo .env (EDITAR CON TUS CREDENCIALES)
cat > /opt/chatserver/.env << 'EOF'
# WebSocket Server Configuration
WS_PORT=8080
WS_HOST=0.0.0.0

# PostgreSQL Configuration
DB_HOST=apptarea.postgres.database.azure.com
DB_PORT=5432
DB_NAME=postgres
DB_USER=apptarea
DB_PASS=Gabriel1405

# Security
JWT_SECRET=change-this-secret-key-in-production

# Environment
NODE_ENV=production
EOF

echo -e "${YELLOW}⚠️  IMPORTANTE: Edita /opt/chatserver/.env con tus credenciales reales${NC}"

# Copiar chat-server.js (debe estar en el directorio actual)
if [ -f "./chat-server.js" ]; then
    cp ./chat-server.js /opt/chatserver/
    echo -e "${GREEN}✅ chat-server.js copiado${NC}"
else
    echo -e "${RED}❌ No se encontró chat-server.js en el directorio actual${NC}"
    echo -e "${YELLOW}   Debes copiarlo manualmente a /opt/chatserver/${NC}"
fi

# 7. Instalar dependencias
echo -e "${YELLOW}📦 Instalando dependencias de Node.js...${NC}"
cd /opt/chatserver
npm install --production

# 8. Configurar permisos
echo -e "${YELLOW}🔒 Configurando permisos...${NC}"
chown -R chatserver:chatserver /opt/chatserver
chown -R chatserver:chatserver /var/log/chatserver
chmod 600 /opt/chatserver/.env

# 9. Configurar Firewall (UFW)
echo -e "${YELLOW}🔥 Configurando firewall...${NC}"
ufw allow 8080/tcp comment 'WebSocket Chat Server'
ufw allow 22/tcp comment 'SSH'
ufw --force enable

# 10. Crear servicio systemd
echo -e "${YELLOW}⚙️  Creando servicio systemd...${NC}"
cat > /etc/systemd/system/chatserver.service << 'EOF'
[Unit]
Description=WebSocket Chat Server for App Tareas
Documentation=https://github.com/yourrepo
After=network.target

[Service]
Type=simple
User=chatserver
WorkingDirectory=/opt/chatserver
ExecStart=/usr/bin/node /opt/chatserver/chat-server.js
Restart=on-failure
RestartSec=10
StandardOutput=append:/var/log/chatserver/output.log
StandardError=append:/var/log/chatserver/error.log

# Security
NoNewPrivileges=true
PrivateTmp=true
ProtectSystem=strict
ProtectHome=true
ReadWritePaths=/var/log/chatserver

[Install]
WantedBy=multi-user.target
EOF

# Recargar systemd
systemctl daemon-reload

# 11. Habilitar e iniciar servicio
echo -e "${YELLOW}🚀 Iniciando servicio...${NC}"
systemctl enable chatserver
systemctl start chatserver

# 12. Verificar estado
sleep 2
systemctl status chatserver --no-pager

# 13. Configurar logrotate
echo -e "${YELLOW}📝 Configurando rotación de logs...${NC}"
cat > /etc/logrotate.d/chatserver << 'EOF'
/var/log/chatserver/*.log {
    daily
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 chatserver chatserver
    sharedscripts
    postrotate
        systemctl reload chatserver > /dev/null 2>&1 || true
    endscript
}
EOF

# 14. Instalar Nginx como proxy reverso (opcional)
read -p "¿Deseas instalar Nginx como proxy reverso? (s/n): " install_nginx

if [[ $install_nginx =~ ^[Ss]$ ]]; then
    echo -e "${YELLOW}📦 Instalando Nginx...${NC}"
    apt install -y nginx
    
    cat > /etc/nginx/sites-available/chatserver << 'EOF'
upstream websocket {
    server 127.0.0.1:8080;
}

server {
    listen 80;
    server_name chat.tudominio.com;

    location / {
        proxy_pass http://websocket;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        
        # Timeouts
        proxy_connect_timeout 7d;
        proxy_send_timeout 7d;
        proxy_read_timeout 7d;
    }
}
EOF

    ln -sf /etc/nginx/sites-available/chatserver /etc/nginx/sites-enabled/
    nginx -t && systemctl restart nginx
    
    echo -e "${GREEN}✅ Nginx configurado${NC}"
    echo -e "${YELLOW}   Recuerda configurar SSL con certbot${NC}"
fi

# 15. Resumen
echo ""
echo -e "${GREEN}=========================================="
echo "  ✅ Instalación Completada"
echo "==========================================${NC}"
echo ""
echo "📋 Comandos útiles:"
echo ""
echo "  Ver estado:     sudo systemctl status chatserver"
echo "  Iniciar:        sudo systemctl start chatserver"
echo "  Detener:        sudo systemctl stop chatserver"
echo "  Reiniciar:      sudo systemctl restart chatserver"
echo "  Ver logs:       sudo journalctl -u chatserver -f"
echo "  Logs archivo:   tail -f /var/log/chatserver/output.log"
echo ""
echo "📁 Archivos importantes:"
echo "  Código:         /opt/chatserver/"
echo "  Config:         /opt/chatserver/.env"
echo "  Logs:           /var/log/chatserver/"
echo "  Servicio:       /etc/systemd/system/chatserver.service"
echo ""
echo "🔧 Próximos pasos:"
echo "  1. Edita /opt/chatserver/.env con tus credenciales"
echo "  2. Reinicia el servicio: sudo systemctl restart chatserver"
echo "  3. Verifica logs: sudo journalctl -u chatserver -f"
echo "  4. Prueba conexión: telnet $(hostname -I | awk '{print $1}') 8080"
echo ""
echo "🌐 Tu servidor WebSocket está en:"
echo "  ws://$(hostname -I | awk '{print $1}'):8080"
echo ""
if [[ $install_nginx =~ ^[Ss]$ ]]; then
    echo "  O vía Nginx: ws://chat.tudominio.com"
    echo ""
    echo "🔒 Para SSL (WSS):"
    echo "  sudo apt install certbot python3-certbot-nginx"
    echo "  sudo certbot --nginx -d chat.tudominio.com"
    echo ""
fi

echo -e "${YELLOW}⚠️  No olvides abrir el puerto 8080 en el firewall de Azure${NC}"
