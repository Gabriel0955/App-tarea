# Snippet para Agregar Enlace de Supervisor en la Navegación

## Opción 1: Si tienes un archivo de header/navegación común

Busca el archivo donde defines tu menú de navegación (por ejemplo, `public/index.php` o un archivo incluido como `includes/header.php`) y agrega este código:

```php
<?php
// Importar el servicio de supervisor si no está ya importado
require_once __DIR__ . '/../services/SupervisorService.php';

// Obtener el usuario actual
$userId = $_SESSION['user_id'] ?? 0;

// Verificar si es supervisor
$supervisorService = new SupervisorService($pdo);
$isSupervisor = $supervisorService->isSupervisor($userId);
?>

<!-- En tu menú de navegación, agrega este enlace condicional -->
<?php if ($isSupervisor): ?>
    <a href="/supervisor/team.php" class="nav-link">
        <span class="nav-icon">👥</span>
        <span class="nav-text">Mi Equipo</span>
    </a>
<?php endif; ?>
```

## Opción 2: Si cada página tiene su propio menú

En cada archivo PHP donde quieras mostrar el enlace, agrega:

```php
<?php
// Al inicio del archivo (después de auth.php y db.php)
require_once __DIR__ . '/../services/SupervisorService.php';

$supervisorService = new SupervisorService($pdo);
$isSupervisor = $supervisorService->isSupervisor($_SESSION['user_id'] ?? 0);

// Luego, en el HTML donde esté tu navegación:
?>
<?php if ($isSupervisor): ?>
    <a href="/supervisor/team.php" class="btn btn-secondary">
        👥 Mi Equipo
    </a>
<?php endif; ?>
```

## Opción 3: Agregar en el sidebar (si tienes uno)

```php
<!-- En tu sidebar -->
<nav class="sidebar">
    <ul class="sidebar-menu">
        <li><a href="/public/index.php">🏠 Inicio</a></li>
        <li><a href="/public/calendar.php">📅 Calendario</a></li>
        <li><a href="/public/tasks/projects.php">📁 Proyectos</a></li>
        <li><a href="/public/tasks/quick_tasks.php">⚡ Tareas Rápidas</a></li>
        <li><a href="/public/pomodoro.php">🍅 Pomodoro</a></li>
        
        <?php if ($isSupervisor): ?>
            <li><a href="/supervisor/team.php" class="supervisor-link">👥 Mi Equipo</a></li>
        <?php endif; ?>
        
        <?php if ($isAdmin): ?>
            <li><a href="/public/admin/users.php">⚙️ Admin</a></li>
        <?php endif; ?>
    </ul>
</nav>
```

## Opción 4: Dropdown de usuario (Recomendado para UX)

Si tienes un menú desplegable de usuario, agrégalo ahí:

```php
<!-- Dropdown del usuario -->
<div class="user-menu">
    <button onclick="toggleUserDropdown()" class="user-menu-btn">
        <span class="avatar"><?= strtoupper(substr($username, 0, 1)) ?></span>
        <span><?= htmlspecialchars($username) ?></span>
        <span class="dropdown-arrow">▼</span>
    </button>
    
    <div id="userDropdown" class="user-dropdown" style="display: none;">
        <a href="/public/profile.php">👤 Mi Perfil</a>
        <a href="/public/achievements.php">🏆 Logros</a>
        <a href="/public/ranking.php">🥇 Ranking</a>
        
        <?php if ($isSupervisor): ?>
            <a href="/supervisor/team.php">👥 Mi Equipo</a>
        <?php endif; ?>
        
        <?php if ($isAdmin): ?>
            <hr class="dropdown-divider">
            <a href="/public/admin/users.php">⚙️ Administración</a>
        <?php endif; ?>
        
        <hr class="dropdown-divider">
        <a href="/public/logout.php">🚪 Cerrar Sesión</a>
    </div>
</div>
```

## CSS para el enlace (opcional)

Si quieres darle un estilo especial al enlace de supervisor:

```css
/* En tu archivo CSS principal o en supervisor.css */
.supervisor-link {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 10px 16px;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.supervisor-link:hover {
    transform: translateX(4px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.nav-icon {
    font-size: 1.2em;
    margin-right: 8px;
}
```

## Verificación

Después de agregar el enlace:

1. ✅ Cierra sesión y vuelve a iniciar como un usuario CON rol supervisor
2. ✅ Verifica que el enlace "👥 Mi Equipo" aparece en la navegación
3. ✅ Haz clic y verifica que redirecciona a `/supervisor/team.php`
4. ✅ Cierra sesión y vuelve a iniciar como usuario SIN rol supervisor
5. ✅ Verifica que el enlace NO aparece

## Troubleshooting

Si el enlace no aparece:
- Verifica que el usuario tiene `role_id` correcto en la base de datos
- Ejecuta: `SELECT u.id, u.username, r.name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = TU_USER_ID;`
- El rol debe ser "supervisor"

Si aparece el enlace pero redirige a index.php:
- Verifica que la ruta sea correcta: `/supervisor/team.php` (sin `public/`)
- El archivo está en `public/supervisor/team.php`
