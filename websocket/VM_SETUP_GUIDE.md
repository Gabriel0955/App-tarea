# Guía de Instalación en VM Azure

## 📋 Requisitos

- VM Ubuntu 20.04/22.04 LTS
- 1 vCPU, 1GB RAM (mínimo)
- 2 vCPU, 2GB RAM (recomendado para producción)
- Puerto 8080 abierto en NSG (Network Security Group)

## 🚀 Opción 1: Instalación Automática (Recomendado)

### 1. Crear VM en Azure

```bash
# Desde Azure Portal o CLI
az vm create \
  --resource-group apptarea-rg \
  --name chatserver-vm \
  --image UbuntuLTS \
  --size Standard_B1s \
  --admin-username azureuser \
  --generate-ssh-keys \
  --public-ip-sku Standard
```

### 2. Conectar por SSH

```powershell
# Desde PowerShell local
ssh azureuser@<IP-PUBLICA-VM>
```

### 3. Subir archivos a la VM

```powershell
# Desde tu máquina local
scp websocket/chat-server.js azureuser@<IP-VM>:~/
scp websocket/install-vm.sh azureuser@<IP-VM>:~/
```

### 4. Ejecutar instalación

```bash
# En la VM
chmod +x install-vm.sh
sudo ./install-vm.sh
```

El script instala automáticamente:
- Node.js 18 LTS
- PM2 (opcional)
- Dependencias npm (ws, pg, dotenv)
- Servicio systemd
- Firewall (UFW)
- Nginx como proxy reverso (opcional)
- Logrotate para logs

### 5. Configurar credenciales

```bash
sudo nano /opt/chatserver/.env
```

Edita:
```env
DB_HOST=apptarea.postgres.database.azure.com
DB_USER=apptarea
DB_PASS=TU_PASSWORD_REAL
JWT_SECRET=genera-un-secreto-aleatorio-seguro
```

### 6. Reiniciar servicio

```bash
sudo systemctl restart chatserver
sudo systemctl status chatserver
```

### 7. Abrir puerto en Azure NSG

```bash
# Desde Azure CLI
az network nsg rule create \
  --resource-group apptarea-rg \
  --nsg-name chatserver-vm-nsg \
  --name AllowWebSocket \
  --priority 1000 \
  --source-address-prefixes '*' \
  --source-port-ranges '*' \
  --destination-address-prefixes '*' \
  --destination-port-ranges 8080 \
  --access Allow \
  --protocol Tcp \
  --description "WebSocket Chat Server"
```

O desde Azure Portal:
1. Ve a la VM → Networking → Add inbound port rule
2. Port: 8080
3. Protocol: TCP
4. Action: Allow
5. Name: AllowWebSocket

## 🛠️ Opción 2: Instalación Manual

### 1. Actualizar sistema

```bash
sudo apt update && sudo apt upgrade -y
```

### 2. Instalar Node.js 18

```bash
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs
node -v  # Debe mostrar v18.x.x
```

### 3. Crear directorio de trabajo

```bash
sudo mkdir -p /opt/chatserver
cd /opt/chatserver
```

### 4. Copiar archivos

```bash
# Subir chat-server.js, package.json, .env
# Usar scp o git clone
```

### 5. Instalar dependencias

```bash
sudo npm install --production
```

### 6. Crear servicio systemd

```bash
sudo nano /etc/systemd/system/chatserver.service
```

Contenido:
```ini
[Unit]
Description=WebSocket Chat Server
After=network.target

[Service]
Type=simple
User=root
WorkingDirectory=/opt/chatserver
ExecStart=/usr/bin/node /opt/chatserver/chat-server.js
Restart=on-failure
RestartSec=10
StandardOutput=append:/var/log/chatserver.log
StandardError=append:/var/log/chatserver-error.log

[Install]
WantedBy=multi-user.target
```

### 7. Iniciar servicio

```bash
sudo systemctl daemon-reload
sudo systemctl enable chatserver
sudo systemctl start chatserver
sudo systemctl status chatserver
```

### 8. Configurar firewall

```bash
sudo ufw allow 22/tcp   # SSH
sudo ufw allow 8080/tcp # WebSocket
sudo ufw enable
```

## 🔒 Configurar SSL/TLS (WSS)

### Opción A: Let's Encrypt con Nginx

```bash
# 1. Instalar Nginx y Certbot
sudo apt install -y nginx certbot python3-certbot-nginx

# 2. Configurar Nginx
sudo nano /etc/nginx/sites-available/chatserver
```

```nginx
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
        
        # WebSocket timeouts
        proxy_connect_timeout 7d;
        proxy_send_timeout 7d;
        proxy_read_timeout 7d;
    }
}
```

```bash
# 3. Habilitar sitio
sudo ln -s /etc/nginx/sites-available/chatserver /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx

# 4. Obtener certificado SSL
sudo certbot --nginx -d chat.tudominio.com

# 5. Verificar renovación automática
sudo certbot renew --dry-run
```

Ahora puedes usar: `wss://chat.tudominio.com`

### Opción B: Azure Application Gateway + SSL

1. Crear Application Gateway en Azure
2. Configurar backend pool → IP de la VM:8080
3. Agregar certificado SSL
4. Configurar reglas de routing para WebSocket

## 📊 Monitoreo y Logs

### Ver logs en tiempo real

```bash
# Logs del servicio
sudo journalctl -u chatserver -f

# Logs de archivo
tail -f /var/log/chatserver/output.log
tail -f /var/log/chatserver/error.log
```

### Ver conexiones activas

```bash
# Conexiones al puerto 8080
sudo netstat -anp | grep 8080

# Procesos Node
ps aux | grep node
```

### Recursos del sistema

```bash
# CPU y Memoria
htop

# Uso de disco
df -h

# Estado del servicio
sudo systemctl status chatserver
```

## 🔧 Comandos Útiles

```bash
# Reiniciar servicio
sudo systemctl restart chatserver

# Detener servicio
sudo systemctl stop chatserver

# Ver logs de errores
sudo journalctl -u chatserver --since "1 hour ago"

# Test de conexión WebSocket
curl -i -N \
  -H "Connection: Upgrade" \
  -H "Upgrade: websocket" \
  -H "Sec-WebSocket-Version: 13" \
  -H "Sec-WebSocket-Key: SGVsbG8sIHdvcmxkIQ==" \
  http://localhost:8080/

# Verificar puerto abierto
sudo netstat -tlnp | grep 8080
```

## 🔄 Actualizar Servidor

```bash
# 1. Detener servicio
sudo systemctl stop chatserver

# 2. Backup
sudo cp /opt/chatserver/chat-server.js /opt/chatserver/chat-server.js.backup

# 3. Actualizar código
cd /opt/chatserver
# Copiar nuevo chat-server.js

# 4. Actualizar dependencias si es necesario
sudo npm update

# 5. Reiniciar
sudo systemctl start chatserver
sudo systemctl status chatserver
```

## 🐛 Troubleshooting

### El servicio no inicia

```bash
# Ver error específico
sudo journalctl -u chatserver -n 50 --no-pager

# Verificar sintaxis del archivo
node -c /opt/chatserver/chat-server.js

# Permisos
ls -la /opt/chatserver/
```

### No se conecta a PostgreSQL

```bash
# Test de conexión
psql -h apptarea.postgres.database.azure.com -U apptarea -d postgres

# Verificar credenciales en .env
cat /opt/chatserver/.env

# Verificar SSL
openssl s_client -connect apptarea.postgres.database.azure.com:5432 -starttls postgres
```

### Puerto bloqueado

```bash
# Verificar firewall local
sudo ufw status

# Verificar NSG en Azure Portal
# VM → Networking → Inbound port rules

# Test desde otra máquina
telnet <IP-VM> 8080
```

### Alto consumo de memoria

```bash
# Ver uso de memoria
free -h
ps aux --sort=-%mem | head

# Reiniciar servicio
sudo systemctl restart chatserver

# Agregar más RAM a la VM si es necesario
```

## 🔐 Hardening de Seguridad

### 1. Crear usuario dedicado

```bash
sudo useradd -r -s /bin/false chatserver
sudo chown -R chatserver:chatserver /opt/chatserver
```

Actualizar servicio:
```ini
[Service]
User=chatserver
Group=chatserver
```

### 2. Firewall restrictivo

```bash
# Solo permitir tráfico de tu aplicación web
sudo ufw allow from <IP-WEBAPP> to any port 8080
```

### 3. Rate limiting con Nginx

```nginx
limit_conn_zone $binary_remote_addr zone=addr:10m;

server {
    limit_conn addr 10;  # Max 10 conexiones por IP
    ...
}
```

### 4. Fail2ban

```bash
sudo apt install fail2ban

# Crear jail para WebSocket
sudo nano /etc/fail2ban/jail.local
```

```ini
[chatserver]
enabled = true
port = 8080
filter = chatserver
logpath = /var/log/chatserver/output.log
maxretry = 5
bantime = 3600
```

## 📈 Escalabilidad

### Load Balancer con múltiples VMs

```bash
# Azure Load Balancer
az network lb create \
  --resource-group apptarea-rg \
  --name chat-lb \
  --sku Standard \
  --backend-pool-name chat-backend

# Agregar VMs al backend pool
```

### Redis para sticky sessions

```bash
# Instalar Redis en la VM
sudo apt install redis-server

# Modificar chat-server.js para usar Redis Adapter
npm install @socket.io/redis-adapter
```

## 💰 Costos Estimados Azure

| Tamaño VM | vCPU | RAM | Costo/Mes* |
|-----------|------|-----|------------|
| B1s       | 1    | 1GB | ~$10 USD   |
| B1ms      | 1    | 2GB | ~$20 USD   |
| B2s       | 2    | 4GB | ~$40 USD   |

*Precios aproximados, varían por región

## 🎯 Checklist de Producción

- [ ] VM creada con IP estática
- [ ] Node.js 18 instalado
- [ ] Servicio systemd configurado
- [ ] Puerto 8080 abierto en NSG
- [ ] SSL/TLS configurado (WSS)
- [ ] Logs configurados con rotación
- [ ] Monitoreo activo (Azure Monitor)
- [ ] Backups automatizados
- [ ] Firewall configurado (UFW)
- [ ] Usuario dedicado sin privilegios
- [ ] Variables de entorno protegidas
- [ ] Dominio personalizado configurado
- [ ] Pruebas de carga realizadas
- [ ] Documentación actualizada

## 📞 Soporte

Si tienes problemas:
1. Revisa logs: `sudo journalctl -u chatserver -f`
2. Verifica conectividad: `telnet <IP> 8080`
3. Consulta documentación de Azure
4. Revisa issues en el repositorio

---

**Próximo paso**: Ejecuta `install-vm.sh` en tu VM y configura el frontend con la IP pública de tu VM.
