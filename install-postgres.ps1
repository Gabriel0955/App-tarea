# Script de instalación PostgreSQL - App-Tareas

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "  APP-TAREAS - Instalación PostgreSQL" -ForegroundColor Cyan
Write-Host "========================================`n" -ForegroundColor Cyan

# Variables de configuración (ajusta según tu instalación)
$pgPath = "C:\Program Files\PostgreSQL\15\bin\psql.exe"  # Ajusta la versión
$pgUser = "postgres"
$pgHost = "localhost"
$pgPort = "5432"
$dbName = "tasks_app"
$schemaPath = "db\schema.sql"

# Verificar si psql existe
if (-not (Test-Path $pgPath)) {
    Write-Host "❌ Error: No se encuentra PostgreSQL en $pgPath" -ForegroundColor Red
    Write-Host "`n💡 Opciones:" -ForegroundColor Yellow
    Write-Host "   1. Instala PostgreSQL desde: https://www.postgresql.org/download/" -ForegroundColor White
    Write-Host "   2. Usa Docker: docker run --name postgres-tasks -e POSTGRES_PASSWORD=postgres -p 5432:5432 -d postgres:14" -ForegroundColor White
    Write-Host "   3. Ajusta la ruta `$pgPath en este script`n" -ForegroundColor White
    exit 1
}

Write-Host "✅ PostgreSQL encontrado" -ForegroundColor Green

# Solicitar contraseña
$pgPassword = Read-Host -Prompt "Ingresa la contraseña de PostgreSQL para usuario '$pgUser'" -AsSecureString
$pgPasswordPlain = [Runtime.InteropServices.Marshal]::PtrToStringAuto([Runtime.InteropServices.Marshal]::SecureStringToBSTR($pgPassword))

# Establecer variable de entorno para contraseña
$env:PGPASSWORD = $pgPasswordPlain

Write-Host "`n📊 Paso 1: Creando base de datos..." -ForegroundColor Yellow
& $pgPath -h $pgHost -p $pgPort -U $pgUser -d postgres -c "CREATE DATABASE $dbName;" 2>$null

if ($LASTEXITCODE -eq 0) {
    Write-Host "✅ Base de datos '$dbName' creada" -ForegroundColor Green
} else {
    Write-Host "⚠️  Base de datos ya existe (esto es normal)" -ForegroundColor Yellow
}

Write-Host "`n📊 Paso 2: Ejecutando schema.sql..." -ForegroundColor Yellow
& $pgPath -h $pgHost -p $pgPort -U $pgUser -d $dbName -f $schemaPath 2>&1

if ($LASTEXITCODE -eq 0) {
    Write-Host "✅ Schema ejecutado correctamente" -ForegroundColor Green
    Write-Host "   - Tabla 'users' creada" -ForegroundColor White
    Write-Host "   - Tabla 'tasks' creada" -ForegroundColor White
} else {
    Write-Host "❌ Error al ejecutar schema" -ForegroundColor Red
    Write-Host "   Puedes ejecutarlo manualmente: psql -U postgres -d tasks_app -f db/schema.sql" -ForegroundColor Yellow
}

# Limpiar contraseña de la memoria
$env:PGPASSWORD = $null

# Resumen final
Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "  ✅ INSTALACIÓN COMPLETADA" -ForegroundColor Green
Write-Host "========================================`n" -ForegroundColor Cyan

Write-Host "📱 Próximos pasos:" -ForegroundColor Cyan
Write-Host "   1. Inicia el servidor PHP:" -ForegroundColor White
Write-Host "      php -S localhost:8000 -t public`n" -ForegroundColor Cyan
Write-Host "   2. Abre tu navegador:" -ForegroundColor White
Write-Host "      http://localhost:8000/register.php`n" -ForegroundColor Cyan
Write-Host "   3. Crea tu cuenta y empieza a usar la app`n" -ForegroundColor White

# Preguntar si iniciar servidor
$startServer = Read-Host "¿Deseas iniciar el servidor PHP ahora? (S/N)"
if ($startServer -eq "S" -or $startServer -eq "s") {
    Write-Host "`n🚀 Iniciando servidor en http://localhost:8000..." -ForegroundColor Green
    Write-Host "   Presiona Ctrl+C para detener`n" -ForegroundColor Yellow
    Start-Sleep -Seconds 2
    Start-Process "http://localhost:8000/register.php"
    php -S localhost:8000 -t public
}
