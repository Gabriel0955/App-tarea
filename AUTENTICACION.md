# 🔐 Sistema de Autenticación - App-Tareas

## ✅ Lo que se implementó

1. **Tabla de usuarios** (`users`)
   - ID, username, email, password (encriptado con bcrypt)
   - Índices para búsquedas rápidas

2. **Relación usuarios-tareas**
   - Campo `user_id` en tabla `tasks`
   - Foreign key con CASCADE delete
   - Cada usuario solo ve SUS tareas

3. **Páginas nuevas**
   - `login.php` - Inicio de sesión
   - `register.php` - Crear cuenta nueva
   - `logout.php` - Cerrar sesión
   - `src/auth.php` - Middleware de autenticación

4. **Seguridad**
   - Sesiones PHP
   - Passwords con `password_hash()` y `password_verify()`
   - Validación de sesión en todas las páginas
   - Filtrado por user_id en TODAS las queries

## 🚀 Cómo usar (WAMP Local)

### Primera vez (base de datos nueva)

```bash
# 1. Ejecutar schema actualizado
mysql -u root < db/schema.sql

# 2. Abrir navegador
http://localhost/App-Tareas/public/register.php

# 3. Crear tu cuenta
# 4. ¡Listo! Ya puedes usar la app
```

### Si YA tienes tareas existentes

```bash
# 1. Ejecutar schema actualizado
mysql -u root < db/schema.sql

# 2. Migrar tareas existentes al usuario admin
mysql -u root < db/migrate_existing_tasks.sql

# 3. Iniciar sesión
http://localhost/App-Tareas/public/login.php
Usuario: admin
Contraseña: admin123

# 4. ¡IMPORTANTE! Cambia la contraseña del admin inmediatamente
```

## 🔑 Flujo de usuario

1. **Primera visita** → Redirige a `login.php`
2. **Sin cuenta** → Click en "Crear cuenta" → `register.php`
3. **Con cuenta** → Login → `index.php` (solo ve SUS tareas)
4. **Cerrar sesión** → Click en "Salir" → `logout.php` → `login.php`

## 🛡️ Seguridad implementada

- ✅ Sesiones PHP con `session_start()`
- ✅ Middleware de autenticación (`src/auth.php`)
- ✅ Passwords encriptados con bcrypt
- ✅ Validación de inputs (username, email, password)
- ✅ Queries con prepared statements (PDO)
- ✅ Filtrado por `user_id` en TODAS las operaciones
- ✅ Foreign keys con CASCADE delete
- ✅ Índices para performance

## 📋 Cambios en archivos existentes

### Modificados (agregan autenticación):
- `public/index.php` - Muestra username, botón logout, filtra por user_id
- `public/add.php` - Agrega user_id al crear tareas
- `public/edit.php` - Solo edita tareas del usuario
- `public/delete.php` - Solo elimina tareas del usuario
- `public/mark_deployed.php` - Valida user_id
- `public/update_doc.php` - Valida user_id

### Nuevos:
- `public/login.php` - Página de inicio de sesión
- `public/register.php` - Registro de nuevos usuarios
- `public/logout.php` - Cerrar sesión
- `src/auth.php` - Middleware de autenticación
- `db/migrate_existing_tasks.sql` - Script de migración

## 🌐 Despliegue en Azure

### Variables de entorno (agregar en Azure App Service)

Las mismas que antes, NO necesitas agregar nada nuevo:
```
DB_HOST=...
DB_NAME=tasks_app
DB_USER=...
DB_PASS=...
DB_PORT=3306
APP_DEBUG=false
```

### Después del deploy en Azure:

1. Conectarte a la base de datos MySQL
2. Ejecutar `db/schema.sql`
3. Crear tu primer usuario en `/public/register.php`
4. ¡Listo!

## 🎯 Próximas mejoras sugeridas

1. **Recuperación de contraseña** (reset via email)
2. **Niveles de usuario** (admin, normal)
3. **Compartir tareas** entre usuarios
4. **Equipos/Organizaciones**
5. **2FA** (autenticación de dos factores)

---

**Nota importante:** Todas las tareas ahora están vinculadas a usuarios. Si intentas acceder sin login, te redirige automáticamente a `login.php`.
