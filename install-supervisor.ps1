# Script de Instalación - Sistema de Supervisor
# Ejecutar desde la raíz del proyecto: .\install-supervisor.ps1

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  Sistema de Supervisor - Instalación  " -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Verificar que el archivo SQL existe
$sqlFile = "db\add_supervisor_role.sql"
if (-not (Test-Path $sqlFile)) {
    Write-Host "❌ ERROR: No se encuentra el archivo $sqlFile" -ForegroundColor Red
    Write-Host "   Asegúrate de ejecutar este script desde la raíz del proyecto." -ForegroundColor Yellow
    exit 1
}

Write-Host "✅ Archivo SQL encontrado: $sqlFile" -ForegroundColor Green
Write-Host ""

# Solicitar credenciales de PostgreSQL
Write-Host "📝 Configuración de la Base de Datos:" -ForegroundColor Yellow
Write-Host ""

$pgHost = Read-Host "Host de PostgreSQL [apptarea.postgres.database.azure.com]"
if ([string]::IsNullOrWhiteSpace($pgHost)) {
    $pgHost = "apptarea.postgres.database.azure.com"
}

$pgUser = Read-Host "Usuario de PostgreSQL [apptarea]"
if ([string]::IsNullOrWhiteSpace($pgUser)) {
    $pgUser = "apptarea"
}

$pgDb = Read-Host "Base de datos [postgres]"
if ([string]::IsNullOrWhiteSpace($pgDb)) {
    $pgDb = "postgres"
}

$pgPassword = Read-Host "Password de PostgreSQL" -AsSecureString
$pgPasswordPlain = [Runtime.InteropServices.Marshal]::PtrToStringAuto(
    [Runtime.InteropServices.Marshal]::SecureStringToBSTR($pgPassword)
)

Write-Host ""
Write-Host "📊 Resumen de la Configuración:" -ForegroundColor Cyan
Write-Host "  Host:     $pgHost" -ForegroundColor White
Write-Host "  Usuario:  $pgUser" -ForegroundColor White
Write-Host "  Database: $pgDb" -ForegroundColor White
Write-Host ""

$confirm = Read-Host "¿Continuar con la instalación? (S/N)"
if ($confirm -notmatch '^[Ss]$') {
    Write-Host "❌ Instalación cancelada por el usuario." -ForegroundColor Yellow
    exit 0
}

Write-Host ""
Write-Host "🔄 Ejecutando migración SQL..." -ForegroundColor Cyan

# Verificar si psql está disponible
$psqlPath = Get-Command psql -ErrorAction SilentlyContinue
if (-not $psqlPath) {
    Write-Host "❌ ERROR: psql no encontrado en PATH" -ForegroundColor Red
    Write-Host ""
    Write-Host "Por favor instala PostgreSQL Client o agrégalo al PATH:" -ForegroundColor Yellow
    Write-Host "  https://www.postgresql.org/download/windows/" -ForegroundColor White
    Write-Host ""
    Write-Host "Alternativamente, ejecuta el SQL manualmente:" -ForegroundColor Yellow
    Write-Host "  1. Ve a Azure Portal → PostgreSQL → Query Editor" -ForegroundColor White
    Write-Host "  2. Copia el contenido de db\add_supervisor_role.sql" -ForegroundColor White
    Write-Host "  3. Pégalo y ejecuta" -ForegroundColor White
    exit 1
}

# Configurar variable de entorno para password
$env:PGPASSWORD = $pgPasswordPlain

try {
    # Ejecutar el script SQL
    $output = psql -h $pgHost -U $pgUser -d $pgDb -f $sqlFile 2>&1
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host ""
        Write-Host "✅ ¡Migración completada exitosamente!" -ForegroundColor Green
        Write-Host ""
        Write-Host "📋 Se han creado los siguientes objetos:" -ForegroundColor Cyan
        Write-Host "  • Rol 'supervisor' en tabla roles" -ForegroundColor White
        Write-Host "  • Tabla supervisor_teams" -ForegroundColor White
        Write-Host "  • Vista team_member_stats" -ForegroundColor White
        Write-Host "  • Función get_team_member_tasks()" -ForegroundColor White
        Write-Host "  • 4 permisos (team.read, team.manage, tasks.read, projects.read)" -ForegroundColor White
        Write-Host ""
        Write-Host "🎯 Próximos pasos:" -ForegroundColor Yellow
        Write-Host "  1. Asigna el rol 'supervisor' a usuarios desde /public/admin/users.php" -ForegroundColor White
        Write-Host "  2. Agrega el enlace '👥 Mi Equipo' en tu navegación principal" -ForegroundColor White
        Write-Host "  3. Accede a /public/supervisor/team.php como supervisor" -ForegroundColor White
        Write-Host ""
        Write-Host "📖 Lee SUPERVISOR_README.md para más información." -ForegroundColor Cyan
        Write-Host ""
    } else {
        Write-Host ""
        Write-Host "❌ Error al ejecutar la migración:" -ForegroundColor Red
        Write-Host $output -ForegroundColor Red
        Write-Host ""
        Write-Host "💡 Posibles soluciones:" -ForegroundColor Yellow
        Write-Host "  • Verifica las credenciales de conexión" -ForegroundColor White
        Write-Host "  • Asegúrate de tener permisos CREATE en la base de datos" -ForegroundColor White
        Write-Host "  • Revisa que el servidor PostgreSQL esté accesible" -ForegroundColor White
        Write-Host "  • Si ya ejecutaste la migración antes, verifica en Azure Portal" -ForegroundColor White
        exit 1
    }
} catch {
    Write-Host ""
    Write-Host "❌ Error inesperado:" -ForegroundColor Red
    Write-Host $_.Exception.Message -ForegroundColor Red
    exit 1
} finally {
    # Limpiar password del entorno
    $env:PGPASSWORD = $null
}
