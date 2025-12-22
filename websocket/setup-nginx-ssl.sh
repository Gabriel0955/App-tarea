#!/bin/bash
# Configurar Nginx con SSL para WebSocket en chat.integra-code.org
# Ejecutar en la VM: sudo bash setup-nginx-ssl.sh

echo "🔐 Configurando Nginx + SSL para WebSocket..."

# 1. Instalar Nginx y Certbot
sudo apt-get update
sudo apt-get install -y nginx certbot python3-certbot-nginx

# Verificar que Nginx se instaló correctamente
if ! command -v nginx &> /dev/null; then
    echo "❌ Error: Nginx no se instaló correctamente"
    exit 1
fi

# Iniciar y habilitar Nginx
sudo systemctl start nginx
sudo systemctl enable nginx

# 2. Obtener certificado SSL para socket.integra-code.org
echo "📜 Solicitando certificado SSL de Let's Encrypt..."
sudo certbot certonly --nginx -d socket.integra-code.org --email admin@integra-code.org --agree-tos --non-interactive

# 3. Crear configuración Nginx
sudo tee /etc/nginx/sites-available/websocket > /dev/null <<'EOF'
# WebSocket Server con SSL
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name socket.integra-code.org;

    # Certificados SSL de Let's Encrypt
    ssl_certificate /etc/letsencrypt/live/socket.integra-code.org/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/socket.integra-code.org/privkey.pem;
    
    # Configuración SSL moderna
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers 'ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384';
    ssl_prefer_server_ciphers off;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;
    
    # Headers de seguridad
    add_header Strict-Transport-Security "max-age=31536000" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "SAMEORIGIN" always;

    # Proxy para WebSocket en localhost:8080
    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        
        # Headers requeridos para WebSocket
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        
        # Timeouts para conexiones WebSocket de larga duración
        proxy_connect_timeout 7d;
        proxy_send_timeout 7d;
        proxy_read_timeout 7d;
        
        # Buffer settings
        proxy_buffering off;
    }
    
    # Health check endpoint
    location /health {
        access_log off;
        return 200 "OK\n";
        add_header Content-Type text/plain;
    }
}

# Redirección HTTP -> HTTPS
server {
    listen 80;
    listen [::]:80;
    server_name socket.integra-code.org;
    
    return 301 https://$server_name$request_uri;
}
EOF

# 4. Habilitar sitio
sudo ln -sf /etc/nginx/sites-available/websocket /etc/nginx/sites-enabled/websocket

# 5. Verificar configuración
sudo nginx -t

# 6. Reiniciar Nginx
sudo systemctl restart nginx

# 7. Configurar renovación automática de certificado
sudo tee /etc/cron.d/certbot-renew > /dev/null <<'EOF'
0 0 * * * root certbot renew --quiet && systemctl reload nginx
EOF

# 8. Abrir puerto 443 en firewall
sudo ufw allow 443/tcp
sudo ufw allow 80/tcp

echo "✅ Nginx configurado con SSL"
echo "🔗 WebSocket disponible en: wss://socket.integra-code.org"
echo "🔄 Asegúrate de que el DNS socket.integra-code.org apunte a 20.81.210.24"
