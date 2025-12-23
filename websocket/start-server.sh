#!/bin/bash
# Script para iniciar el servidor WebSocket en la VM

echo "🚀 Iniciando servidor WebSocket..."

# Ir al directorio del servidor
cd /opt/chatserver 2>/dev/null || cd /home/apptarea/chatserver || cd ~/chatserver

# Verificar que existen los archivos necesarios
if [ ! -f "chat-server.js" ]; then
    echo "❌ Error: chat-server.js no encontrado"
    echo "📁 Directorio actual: $(pwd)"
    ls -la
    exit 1
fi

if [ ! -f ".env" ]; then
    echo "❌ Error: .env no encontrado"
    exit 1
fi

# Instalar dependencias si no existen
if [ ! -d "node_modules" ]; then
    echo "📦 Instalando dependencias..."
    npm install
fi

# Iniciar el servidor
echo "✅ Iniciando servidor en puerto 8080..."
node chat-server.js
