# Sistema de Roles y Permisos - Guía de Uso

## 🎯 Resumen Rápido

El sistema de roles está **automáticamente activo** en todas las páginas que incluyan `auth.php`.

## 📋 Roles Disponibles

| Rol | Nivel | Descripción |
|-----|-------|-------------|
| **admin** | 3 | Acceso completo a todo |
| **manager** | 2 | Gestión de equipo y tareas |
| **user** | 1 | Usuario estándar |
| **viewer** | 0 | Solo lectura |

## 🔧 Funciones Disponibles

### Obtener información del usuario actual

```php
<?php
// Ya disponibles automáticamente en todas las páginas protegidas
$user_id = get_current_user_id();        // ID del usuario
$username = get_current_username();      // Nombre del usuario  
$role = get_current_role();              // Rol: 'admin', 'manager', 'user', 'viewer'
?>
```

### Verificar roles

```php
<?php
// Verificar si es admin
if (is_admin()) {
    echo "Eres administrador";
}

// Verificar si es manager o admin
if (is_manager_or_admin()) {
    echo "Puedes gestionar el equipo";
}
?>
```

### Verificar permisos específicos

```php
<?php
// Verificar si puede crear tareas
if (can('create', 'tasks')) {
    echo "Puedes crear tareas";
}

// Verificar si puede eliminar proyectos
if (can('delete', 'projects')) {
    echo "Puedes eliminar proyectos";
}

// Verificar si puede editar usuarios
if (can('update', 'users')) {
    echo "Puedes editar usuarios";
}
?>
```

## 🛡️ Proteger Páginas y Acciones

### 1. Proteger una página completa

```php
<?php
require_once __DIR__ . '/../src/auth.php';

// Solo admins pueden acceder a esta página
require_role('admin');

// O verificar un permiso específico
require_permission('users', 'update');
?>
```

### 2. Proteger acciones en archivos de procesamiento

```php
<?php
// En delete.php, mark_completed.php, etc.
require_once __DIR__ . '/../../../src/auth.php';

// Verificar permiso antes de eliminar
require_permission('tasks', 'delete');

// Continuar con la acción
deleteTask($pdo, $id, $user_id);
?>
```

### 3. Ocultar elementos del UI según permisos

```php
<!-- Mostrar botón solo si puede crear -->
<?php if (can('create', 'tasks')): ?>
    <button onclick="openModal()">➕ Nueva Tarea</button>
<?php endif; ?>

<!-- Mostrar botón de eliminar solo si puede -->
<?php if (can('delete', 'tasks')): ?>
    <a href="delete.php?id=<?= $id ?>" class="btn red">🗑️</a>
<?php endif; ?>

<!-- Mostrar panel de administración solo para admins -->
<?php if (is_admin()): ?>
    <a href="admin/users.php">👥 Gestionar Usuarios</a>
<?php endif; ?>
```

### 4. Mostrar información del rol en el UI

```php
<div class="user-info">
    <?= get_current_username() ?>
    <span class="badge"><?= strtoupper(get_current_role()) ?></span>
</div>
```

## 📝 Ejemplos de Uso Real

### Ejemplo 1: Proteger eliminación de tareas

**Archivo: `public/tasks/actions/delete.php`**

```php
<?php
require_once __DIR__ . '/../../../src/auth.php';

// Verificar permiso de eliminación
require_permission('tasks', 'delete');

$user_id = get_current_user_id();
$id = intval($_GET['id'] ?? 0);

// Si llegó aquí, tiene permiso
deleteTask($pdo, $id, $user_id);
header('Location: ../../index.php');
?>
```

### Ejemplo 2: Panel de administración

**Archivo: `public/admin/users.php`**

```php
<?php
require_once __DIR__ . '/../src/auth.php';

// Solo admins pueden ver esta página
require_role('admin');

$users = getUsersWithRoles($pdo, get_current_user_id());
?>

<h1>Gestión de Usuarios</h1>
<!-- Contenido del panel -->
```

### Ejemplo 3: Formulario condicional

**Archivo: `public/index.php`**

```php
<!-- Mostrar selector de rol solo si es admin -->
<?php if (is_admin()): ?>
    <select name="assigned_user">
        <!-- Lista de usuarios -->
    </select>
<?php endif; ?>

<!-- Botón de crear solo si tiene permiso -->
<?php if (can('create', 'tasks')): ?>
    <button onclick="openModal()">➕ Nueva</button>
<?php else: ?>
    <span class="text-muted">No tienes permiso para crear tareas</span>
<?php endif; ?>
```

## 🎨 Estilos para Badges de Roles

```css
.badge-admin {
    background: linear-gradient(135deg, #ff4757 0%, #ff6348 100%);
    color: white;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
}

.badge-manager {
    background: linear-gradient(135deg, #00b4d8 0%, #0096c7 100%);
}

.badge-user {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.badge-viewer {
    background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
}
```

## 🔒 Recursos Disponibles

Los siguientes recursos pueden verificarse:

- `tasks` - Tareas
- `projects` - Proyectos  
- `users` - Usuarios
- `reports` - Reportes
- `settings` - Configuración

## ⚡ Acciones Disponibles

- `create` - Crear
- `read` - Leer/Ver
- `update` - Editar
- `delete` - Eliminar

## 🚀 Aplicación Inmediata

Para aplicar protección a un archivo existente, simplemente agrega al inicio:

```php
<?php
require_once __DIR__ . '/ruta/src/auth.php';

// Opcional: Requerir rol o permiso
require_role('manager');
// O
require_permission('tasks', 'delete');
?>
```

**¡El sistema está activo automáticamente en todas las páginas que usan `auth.php`!** 🎉
