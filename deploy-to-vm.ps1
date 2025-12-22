# Script de Deployment a VM Azure
# Ejecutar desde la raíz del proyecto

param(
    [Parameter(Mandatory=$true)]
    [string]$VMIp,
    
    [Parameter(Mandatory=$false)]
    [string]$VMUser = "azureuser",
    
    [Parameter(Mandatory=$false)]
    [string]$SSHKey = "$env:USERPROFILE\.ssh\id_rsa"
)

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  Deploy WebSocket Server a VM Azure  " -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Verificar que scp y ssh están disponibles
$scpPath = Get-Command scp -ErrorAction SilentlyContinue
$sshPath = Get-Command ssh -ErrorAction SilentlyContinue

if (-not $scpPath -or -not $sshPath) {
    Write-Host "❌ ERROR: scp/ssh no encontrados" -ForegroundColor Red
    Write-Host ""
    Write-Host "Opciones:" -ForegroundColor Yellow
    Write-Host "1. Instalar OpenSSH: https://docs.microsoft.com/en-us/windows-server/administration/openssh/openssh_install_firstuse" -ForegroundColor White
    Write-Host "2. Usar Git Bash (incluye SSH): https://git-scm.com/downloads" -ForegroundColor White
    Write-Host "3. Usar WSL: wsl --install" -ForegroundColor White
    exit 1
}

Write-Host "✅ SSH/SCP disponibles" -ForegroundColor Green
Write-Host ""

# Verificar archivos necesarios
$requiredFiles = @(
    "websocket\chat-server.js",
    "websocket\package.json",
    "websocket\.env",
    "websocket\install-vm.sh"
)

$missingFiles = @()
foreach ($file in $requiredFiles) {
    if (-not (Test-Path $file)) {
        $missingFiles += $file
    }
}

if ($missingFiles.Count -gt 0) {
    Write-Host "❌ Archivos faltantes:" -ForegroundColor Red
    $missingFiles | ForEach-Object { Write-Host "   - $_" -ForegroundColor Red }
    exit 1
}

Write-Host "✅ Todos los archivos necesarios encontrados" -ForegroundColor Green
Write-Host ""

# Test de conexión SSH
Write-Host "🔌 Probando conexión SSH a $VMUser@$VMIp..." -ForegroundColor Yellow

$testConnection = ssh -o ConnectTimeout=5 -o BatchMode=yes -i $SSHKey "$VMUser@$VMIp" "echo 'OK'" 2>&1

if ($LASTEXITCODE -ne 0) {
    Write-Host "❌ No se pudo conectar a la VM" -ForegroundColor Red
    Write-Host ""
    Write-Host "Verifica:" -ForegroundColor Yellow
    Write-Host "  - IP correcta: $VMIp" -ForegroundColor White
    Write-Host "  - Puerto 22 abierto en NSG" -ForegroundColor White
    Write-Host "  - SSH key correcta: $SSHKey" -ForegroundColor White
    Write-Host ""
    Write-Host "Para conectar manualmente:" -ForegroundColor Yellow
    Write-Host "  ssh -i $SSHKey $VMUser@$VMIp" -ForegroundColor White
    exit 1
}

Write-Host "✅ Conexión SSH exitosa" -ForegroundColor Green
Write-Host ""

# Confirmar deployment
Write-Host "📋 Resumen del deployment:" -ForegroundColor Cyan
Write-Host "  VM IP:        $VMIp" -ForegroundColor White
Write-Host "  Usuario:      $VMUser" -ForegroundColor White
Write-Host "  SSH Key:      $SSHKey" -ForegroundColor White
Write-Host "  Archivos:     4 (chat-server.js, package.json, .env, install-vm.sh)" -ForegroundColor White
Write-Host ""

$confirm = Read-Host "¿Continuar con el deployment? (S/N)"
if ($confirm -notmatch '^[Ss]$') {
    Write-Host "❌ Deployment cancelado" -ForegroundColor Yellow
    exit 0
}

Write-Host ""
Write-Host "🚀 Iniciando deployment..." -ForegroundColor Cyan
Write-Host ""

# 1. Crear directorio temporal en VM
Write-Host "📁 Creando directorio temporal en VM..." -ForegroundColor Yellow
ssh -i $SSHKey "$VMUser@$VMIp" "mkdir -p ~/chatserver-deploy"

# 2. Copiar archivos
Write-Host "📤 Copiando archivos a VM..." -ForegroundColor Yellow

$filesToCopy = @{
    "websocket\chat-server.js" = "~/chatserver-deploy/chat-server.js"
    "websocket\package.json" = "~/chatserver-deploy/package.json"
    "websocket\.env" = "~/chatserver-deploy/.env"
    "websocket\install-vm.sh" = "~/chatserver-deploy/install-vm.sh"
}

foreach ($source in $filesToCopy.Keys) {
    $dest = $filesToCopy[$source]
    Write-Host "  → $source" -ForegroundColor Gray
    scp -i $SSHKey $source "${VMUser}@${VMIp}:${dest}"
    
    if ($LASTEXITCODE -ne 0) {
        Write-Host "❌ Error copiando $source" -ForegroundColor Red
        exit 1
    }
}

Write-Host "✅ Archivos copiados" -ForegroundColor Green
Write-Host ""

# 3. Ejecutar instalación
Write-Host "⚙️  Ejecutando instalación en VM..." -ForegroundColor Yellow
Write-Host "    (Esto puede tomar varios minutos)" -ForegroundColor Gray
Write-Host ""

$installScript = @"
cd ~/chatserver-deploy
chmod +x install-vm.sh
sudo ./install-vm.sh
"@

ssh -i $SSHKey "$VMUser@$VMIp" $installScript

if ($LASTEXITCODE -ne 0) {
    Write-Host ""
    Write-Host "⚠️  Instalación completada con advertencias" -ForegroundColor Yellow
    Write-Host "    Revisa los logs arriba para más detalles" -ForegroundColor Gray
} else {
    Write-Host ""
    Write-Host "✅ Instalación completada exitosamente" -ForegroundColor Green
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  ✅ Deployment Completado" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

Write-Host "🌐 Tu servidor WebSocket está en:" -ForegroundColor Green
Write-Host "   ws://${VMIp}:8080" -ForegroundColor White
Write-Host ""

Write-Host "📋 Próximos pasos:" -ForegroundColor Yellow
Write-Host ""
Write-Host "1. Verificar que el servicio está corriendo:" -ForegroundColor White
Write-Host "   ssh -i $SSHKey $VMUser@$VMIp 'sudo systemctl status chatserver'" -ForegroundColor Gray
Write-Host ""
Write-Host "2. Ver logs:" -ForegroundColor White
Write-Host "   ssh -i $VMUser@$VMIp 'sudo journalctl -u chatserver -f'" -ForegroundColor Gray
Write-Host ""
Write-Host "3. Abrir puerto 8080 en Azure NSG:" -ForegroundColor White
Write-Host "   - Ve a Azure Portal → VM → Networking" -ForegroundColor Gray
Write-Host "   - Add inbound port rule: 8080 TCP" -ForegroundColor Gray
Write-Host ""
Write-Host "4. Actualizar frontend (chat-widget.js):" -ForegroundColor White
Write-Host "   wsUrl: 'ws://${VMIp}:8080'" -ForegroundColor Gray
Write-Host ""
Write-Host "5. Test de conexión:" -ForegroundColor White
Write-Host "   Test-NetConnection -ComputerName $VMIp -Port 8080" -ForegroundColor Gray
Write-Host ""

Write-Host "🔧 Comandos útiles:" -ForegroundColor Yellow
Write-Host ""
Write-Host "  Conectar SSH:" -ForegroundColor White
Write-Host "    ssh -i $SSHKey $VMUser@$VMIp" -ForegroundColor Gray
Write-Host ""
Write-Host "  Ver estado servicio:" -ForegroundColor White
Write-Host "    ssh $VMUser@$VMIp 'sudo systemctl status chatserver'" -ForegroundColor Gray
Write-Host ""
Write-Host "  Reiniciar servicio:" -ForegroundColor White
Write-Host "    ssh $VMUser@$VMIp 'sudo systemctl restart chatserver'" -ForegroundColor Gray
Write-Host ""
Write-Host "  Ver logs en tiempo real:" -ForegroundColor White
Write-Host "    ssh $VMUser@$VMIp 'sudo journalctl -u chatserver -f'" -ForegroundColor Gray
Write-Host ""

# Ofrecer abrir SSH
$openSSH = Read-Host "¿Quieres conectarte ahora por SSH? (S/N)"
if ($openSSH -match '^[Ss]$') {
    Write-Host ""
    Write-Host "🔌 Conectando a VM..." -ForegroundColor Cyan
    ssh -i $SSHKey "$VMUser@$VMIp"
}

Write-Host ""
Write-Host "✅ Script completado" -ForegroundColor Green
