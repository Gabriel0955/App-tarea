#!/bin/bash
# Script para configurar SSL en WebSocket Server
# Ejecutar en la VM: sudo bash setup-ssl.sh

echo "🔐 Configurando SSL para WebSocket Server..."

# 1. Instalar Certbot para Let's Encrypt
apt-get update
apt-get install -y certbot

# 2. Obtener certificado SSL (reemplaza chat.tudominio.com con tu dominio)
# Si no tienes dominio, puedes usar certificado autofirmado (ver opción B abajo)

# OPCIÓN A: Con dominio (recomendado)
# certbot certonly --standalone -d chat.tudominio.com --email tu@email.com --agree-tos

# OPCIÓN B: Certificado autofirmado (solo para pruebas)
openssl req -x509 -newkey rsa:4096 -keyout /opt/chatserver/key.pem -out /opt/chatserver/cert.pem -days 365 -nodes -subj "/CN=20.81.210.24"

# 3. Dar permisos al usuario chatserver
chown chatserver:chatserver /opt/chatserver/*.pem
chmod 600 /opt/chatserver/key.pem
chmod 644 /opt/chatserver/cert.pem

echo "✅ Certificados SSL creados en /opt/chatserver/"
echo "📝 Ahora actualiza chat-server.js para usar SSL"
