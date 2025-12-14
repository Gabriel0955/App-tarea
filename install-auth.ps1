# Script de instalación para sistema de autenticación
# App-Tareas - Sistema de usuarios

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "  APP-TAREAS - Instalación de Autenticación" -ForegroundColor Cyan
Write-Host "========================================`n" -ForegroundColor Cyan

$mysqlPath = "C:\wamp64\bin\mysql\mysql8.0.31\bin\mysql.exe"
$schemaPath = "C:/wamp64/www/App-Tareas/db/schema.sql"
$migratePath = "C:/wamp64/www/App-Tareas/db/migrate_existing_tasks.sql"

# Verificar si MySQL existe
if (-not (Test-Path $mysqlPath)) {
    Write-Host "❌ Error: No se encuentra MySQL en $mysqlPath" -ForegroundColor Red
    Write-Host "   Ajusta la ruta en este script si es necesaria" -ForegroundColor Yellow
    exit 1
}

Write-Host "✅ MySQL encontrado" -ForegroundColor Green

# Paso 1: Ejecutar schema.sql
Write-Host "`n📊 Paso 1: Actualizando base de datos..." -ForegroundColor Yellow
& $mysqlPath -u root -e "source $schemaPath" 2>&1

if ($LASTEXITCODE -eq 0) {
    Write-Host "✅ Schema ejecutado correctamente" -ForegroundColor Green
    Write-Host "   - Tabla 'users' creada" -ForegroundColor White
    Write-Host "   - Columna 'user_id' agregada a 'tasks'" -ForegroundColor White
} else {
    Write-Host "❌ Error al ejecutar schema.sql" -ForegroundColor Red
    exit 1
}

# Paso 2: Verificar si hay tareas existentes
Write-Host "`n🔍 Paso 2: Verificando tareas existentes..." -ForegroundColor Yellow
$taskCount = & $mysqlPath -u root -D tasks_app -se "SELECT COUNT(*) FROM tasks" 2>&1

if ($taskCount -match "^\d+$" -and [int]$taskCount -gt 0) {
    Write-Host "📋 Se encontraron $taskCount tareas existentes" -ForegroundColor Cyan
    
    $response = Read-Host "`n¿Deseas migrarlas al usuario 'admin'? (S/N)"
    
    if ($response -eq "S" -or $response -eq "s") {
        Write-Host "`n📦 Migrando tareas existentes..." -ForegroundColor Yellow
        & $mysqlPath -u root -e "source $migratePath" 2>&1
        
        if ($LASTEXITCODE -eq 0) {
            Write-Host "✅ Tareas migradas correctamente" -ForegroundColor Green
            Write-Host "`n🔑 Credenciales del usuario admin:" -ForegroundColor Cyan
            Write-Host "   Usuario: admin" -ForegroundColor White
            Write-Host "   Contraseña: admin123" -ForegroundColor White
            Write-Host "`n⚠️  ¡CAMBIA LA CONTRASEÑA INMEDIATAMENTE!" -ForegroundColor Yellow
        } else {
            Write-Host "❌ Error al migrar tareas" -ForegroundColor Red
        }
    } else {
        Write-Host "⏭️  Saltando migración. Las tareas sin user_id no serán visibles." -ForegroundColor Yellow
    }
} else {
    Write-Host "✅ No hay tareas existentes. Base de datos limpia." -ForegroundColor Green
}

# Resumen final
Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "  ✅ INSTALACIÓN COMPLETADA" -ForegroundColor Green
Write-Host "========================================`n" -ForegroundColor Cyan

Write-Host "📱 Próximos pasos:" -ForegroundColor Cyan
Write-Host "   1. Abre tu navegador" -ForegroundColor White
Write-Host "   2. Ve a: http://localhost/App-Tareas/public/register.php" -ForegroundColor White
Write-Host "   3. Crea tu cuenta" -ForegroundColor White
Write-Host "   4. ¡Empieza a usar la app!`n" -ForegroundColor White

Write-Host "📚 Documentación completa en: AUTENTICACION.md`n" -ForegroundColor Cyan

# Abrir navegador automáticamente
$openBrowser = Read-Host "¿Deseas abrir el navegador ahora? (S/N)"
if ($openBrowser -eq "S" -or $openBrowser -eq "s") {
    Start-Process "http://localhost/App-Tareas/public/register.php"
}
