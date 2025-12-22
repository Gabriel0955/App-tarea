# Script para verificar el estado del WebSocket server en la VM
# Ejecutar: .\websocket\check-vm-status.ps1

$VM_IP = "20.81.210.24"
$VM_USER = "apptarea"

Write-Host "🔍 Verificando estado del servidor WebSocket..." -ForegroundColor Cyan
Write-Host ""

# Test 1: Verificar si el servidor Node.js está corriendo
Write-Host "1️⃣ Verificando proceso Node.js..." -ForegroundColor Yellow
ssh ${VM_USER}@${VM_IP} "ps aux | grep chat-server.js | grep -v grep"

Write-Host ""

# Test 2: Verificar si Nginx está instalado y corriendo
Write-Host "2️⃣ Verificando Nginx..." -ForegroundColor Yellow
ssh ${VM_USER}@${VM_IP} "systemctl status nginx --no-pager | head -5"

Write-Host ""

# Test 3: Verificar certificado SSL
Write-Host "3️⃣ Verificando certificado SSL..." -ForegroundColor Yellow
ssh ${VM_USER}@${VM_IP} "ls -la /etc/letsencrypt/live/socket.integra-code.org/ 2>/dev/null || echo 'Certificado no encontrado'"

Write-Host ""

# Test 4: Verificar puertos abiertos
Write-Host "4️⃣ Verificando puertos..." -ForegroundColor Yellow
ssh ${VM_USER}@${VM_IP} "netstat -tuln | grep -E ':(80|443|8080)'"

Write-Host ""

# Test 5: Test de conexión WebSocket desde internet
Write-Host "5️⃣ Probando conexión HTTPS..." -ForegroundColor Yellow
try {
    $response = Invoke-WebRequest -Uri "https://socket.integra-code.org/health" -TimeoutSec 5 -UseBasicParsing
    Write-Host "✅ Respuesta: $($response.StatusCode) - $($response.Content)" -ForegroundColor Green
} catch {
    Write-Host "❌ Error: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""
Write-Host "📋 Resumen de acciones necesarias:" -ForegroundColor Cyan
Write-Host "1. Si Node.js no está corriendo: ssh y ejecutar 'cd /opt/chatserver && node chat-server.js &'"
Write-Host "2. Si Nginx no está configurado: ejecutar setup-nginx-ssl.sh"
Write-Host "3. Si el certificado no existe: verificar DNS apunta a la VM"
Write-Host "4. Si puerto 443 no está abierto: ejecutar 'sudo ufw allow 443/tcp'"
