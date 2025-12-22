# Sistema de Supervisor - Documentación de Instalación

## 📋 Resumen

El sistema de supervisor permite que usuarios con rol "supervisor" puedan gestionar equipos y monitorear el progreso de las tareas de sus miembros, manteniendo la privacidad de información sensible.

## 🗂️ Archivos Creados

### Backend
- **db/add_supervisor_role.sql** - Migración de base de datos (104 líneas)
- **services/SupervisorService.php** - Lógica de negocio (217 líneas)
- **public/supervisor/api/supervisor_api.php** - API REST (66 líneas)

### Frontend
- **public/supervisor/team.php** - Dashboard del equipo (249 líneas)
- **public/supervisor/member_tasks.php** - Vista de tareas del miembro (219 líneas)
- **assets/css/pages/supervisor.css** - Estilos (450 líneas)
- **assets/js/pages/supervisor.js** - Interactividad (270 líneas)

## 🚀 Instalación

### Paso 1: Ejecutar Migración SQL

Conecta a tu base de datos PostgreSQL de Azure y ejecuta el script:

```powershell
# Opción A: Desde PowerShell (en la raíz del proyecto)
$env:PGPASSWORD="tu_password"
psql -h apptarea.postgres.database.azure.com -U apptarea -d postgres -f db/add_supervisor_role.sql

# Opción B: Copiar y pegar en Azure Portal
# Ve a Azure Portal → PostgreSQL → Query Editor
# Abre db/add_supervisor_role.sql y copia todo el contenido
# Pégalo en el editor y ejecuta
```

La migración crea:
- ✅ Rol "supervisor" en la tabla `roles`
- ✅ Tabla `supervisor_teams` (relación supervisor-miembro)
- ✅ Vista `team_member_stats` (estadísticas agregadas sin datos sensibles)
- ✅ Función `get_team_member_tasks()` (devuelve tareas sin descripciones)
- ✅ 4 permisos: team.read, team.manage, tasks.read, projects.read

### Paso 2: Asignar Rol Supervisor

Ve al panel de administración de usuarios y asigna el rol "supervisor" a los usuarios deseados:

```
1. Iniciar sesión como admin
2. Ir a /public/admin/users.php
3. Buscar el usuario
4. Cambiar rol a "Supervisor"
```

### Paso 3: Agregar Enlace en Navegación (Opcional)

Edita `public/index.php` (o tu archivo de header común) para agregar un enlace al panel de supervisor:

```php
<?php
// En la sección de navegación/sidebar
$supervisorService = new SupervisorService($pdo);
if ($supervisorService->isSupervisor($userId)) {
    echo '<a href="/supervisor/team.php" class="nav-link">👥 Mi Equipo</a>';
}
?>
```

## 🔒 Características de Privacidad

El sistema está diseñado con privacidad en mente:

### Datos Visibles para Supervisores
✅ Nombre de usuario del miembro
✅ Nivel y puntos
✅ Título de la tarea
✅ Categoría, prioridad, urgencia
✅ Fecha de vencimiento
✅ Proyecto asignado
✅ Estadísticas agregadas (streak, pomodoros)
✅ Notas del supervisor (privadas del supervisor)

### Datos NO Visibles
❌ Descripción de la tarea
❌ Notas de despliegue
❌ Campos personalizados sensibles
❌ Email o información personal
❌ Tareas de usuarios fuera de su equipo

## 📊 Estructura de la Base de Datos

### Tabla: supervisor_teams
```sql
id              SERIAL PRIMARY KEY
supervisor_id   INTEGER (FK a users.id)
team_member_id  INTEGER (FK a users.id)
assigned_at     TIMESTAMP
notes           TEXT (opcional, privado del supervisor)
```

### Vista: team_member_stats
Agrega estadísticas por usuario:
- `user_id`, `username`, `current_level`, `total_points`
- `total_tasks`, `pending_tasks`, `overdue_tasks`, `upcoming_tasks`, `completed_tasks`
- `streak_days`, `total_pomodoros`, `last_activity_date`

### Función: get_team_member_tasks()
```sql
get_team_member_tasks(p_supervisor_id INT, p_member_id INT)
RETURNS TABLE(task_id, title, category, priority, urgency, due_date, days_pending, status, project_name, created_at)
```
- Valida que el supervisor tenga acceso al miembro
- Retorna solo campos no sensibles
- Usa SECURITY DEFINER para bypass de permisos con validación

## 🎯 Funcionalidades

### Dashboard del Equipo (team.php)

**Resumen del Equipo (6 tarjetas):**
- Total de miembros
- Tareas pendientes del equipo
- Tareas vencidas
- Tareas completadas
- Nivel promedio del equipo
- Puntos totales del equipo

**Tarjetas de Miembros:**
- Avatar (inicial del nombre)
- Nombre y nivel
- 4 mini-estadísticas (pendiente, vencido, próximo, completado)
- Barra de progreso de completitud
- Streak de días consecutivos
- Total de sesiones Pomodoro
- Botones de acción:
  - **Ver Tareas** → member_tasks.php
  - **Notas** → Modal para editar notas del supervisor
  - **Remover** → Confirmar y quitar del equipo

**Modal: Agregar Miembro**
- Dropdown con usuarios disponibles (solo rol "user")
- Campo de notas opcional
- Validación de duplicados

### Vista de Tareas del Miembro (member_tasks.php)

**Header:**
- Nombre del miembro
- Nivel y puntos
- Botón "Volver al Equipo"

**Estadísticas (5 cajas):**
- Total de tareas
- Pendientes
- Vencidas
- Próximas (7 días)
- Completadas

**Tabla de Tareas (8 columnas):**
1. Estado (badge con color)
2. Título
3. Categoría
4. Prioridad (crítica/alta/media/baja)
5. Urgencia (alta/media/baja con emoji)
6. Fecha de vencimiento
7. Días pendientes (destacado si >7)
8. Proyecto

**Información Adicional:**
- Streak de días consecutivos
- Total de Pomodoros completados
- Última actividad
- Notas del supervisor (si existen)

## 🛠️ API Endpoints

### POST /supervisor/api/supervisor_api.php

**Action: add_member**
```
POST data:
  action=add_member
  member_id=123
  notes=Texto opcional

Response:
  Redirect a team.php?success=Miembro+agregado
  Redirect a team.php?error=Error+al+agregar
```

**Action: remove_member**
```
POST data:
  action=remove_member
  member_id=123

Response:
  Redirect a team.php?success=Miembro+removido
  Redirect a team.php?error=Error+al+remover
```

**Action: update_notes**
```
POST data:
  action=update_notes
  member_id=123
  notes=Nuevas notas

Response:
  Redirect a team.php?success=Notas+actualizadas
  Redirect a team.php?error=Error+al+actualizar
```

## 🧪 Testing

### Test 1: Verificar Rol Supervisor
```sql
-- Asignar rol supervisor a un usuario
UPDATE users SET role_id = (SELECT id FROM roles WHERE name = 'supervisor') WHERE id = 123;

-- Verificar
SELECT u.username, r.name as role FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = 123;
```

### Test 2: Agregar Miembro al Equipo
```sql
-- Manualmente (para testing)
INSERT INTO supervisor_teams (supervisor_id, team_member_id, notes)
VALUES (123, 456, 'Miembro de prueba');

-- Verificar
SELECT * FROM supervisor_teams WHERE supervisor_id = 123;
```

### Test 3: Ver Estadísticas del Equipo
```sql
-- Ver vista
SELECT * FROM team_member_stats WHERE user_id IN (
  SELECT team_member_id FROM supervisor_teams WHERE supervisor_id = 123
);
```

### Test 4: Obtener Tareas del Miembro
```sql
-- Llamar función
SELECT * FROM get_team_member_tasks(123, 456);
```

## 📱 Responsive

El diseño es completamente responsive:

- **Desktop (>768px):** Grid de 3-4 columnas, tabla completa
- **Tablet (768px):** Grid de 2 columnas, tabla con scroll horizontal
- **Mobile (<480px):** 1 columna, tabla simplificada

## 🎨 Temas

El sistema usa las variables CSS del tema activo:
- `--bg-secondary`, `--text-color`, `--text-secondary`
- `--accent-blue`, `--accent-red`, `--accent-green`, `--accent-yellow`
- Soporte para temas oscuros/claros

## 🔐 Permisos

Permisos asignados al rol supervisor:
- `team.read` - Ver información del equipo
- `team.manage` - Agregar/remover miembros
- `tasks.read` - Ver tareas de los miembros
- `projects.read` - Ver proyectos relacionados

## 🚨 Validaciones

### Backend (SupervisorService)
- `isSupervisor()` - Verificar que el usuario tiene rol supervisor
- `hasAccessToMember()` - Validar que el miembro está en el equipo del supervisor
- No permite agregar admins/supervisors al equipo (solo "user")
- No permite duplicados en supervisor_teams

### Frontend (JavaScript)
- Confirmación antes de remover miembros
- Validación de formularios antes de submit
- Manejo de errores con notificaciones toast
- Cierre de modales con ESC o clic fuera

## 📈 Métricas Disponibles

Para cada miembro:
- Total de tareas
- Tareas pendientes
- Tareas vencidas (overdue)
- Tareas próximas (next 7 days)
- Tareas completadas
- Nivel actual
- Puntos totales
- Streak de días consecutivos
- Sesiones Pomodoro completadas
- Última actividad

Para el equipo (agregado):
- Suma de todas las métricas individuales
- Promedio de niveles
- Total de puntos del equipo

## 🐛 Troubleshooting

### Error: "Not supervisor"
- Verificar que el usuario tiene role_id correcto
- Ejecutar: `SELECT * FROM users WHERE id = YOUR_ID;`
- El role_id debe coincidir con el id del rol "supervisor"

### Error: "No access"
- El miembro no está en supervisor_teams
- Ejecutar: `SELECT * FROM supervisor_teams WHERE supervisor_id = YOUR_ID;`

### Error: "Member not found"
- El member_id no existe o fue eliminado
- Verificar: `SELECT * FROM users WHERE id = MEMBER_ID;`

### La vista no carga datos
- Verificar que existen tareas: `SELECT * FROM tasks WHERE user_id = MEMBER_ID;`
- Verificar que user_stats existe: `SELECT * FROM user_stats WHERE user_id = MEMBER_ID;`

## 📝 Notas Adicionales

- Las notas del supervisor son privadas (solo el supervisor las ve)
- Remover un miembro del equipo NO elimina al usuario ni sus tareas
- Un usuario solo puede estar en el equipo de un supervisor a la vez
- Los supervisores no pueden verse a sí mismos en la lista de miembros disponibles
- Los administradores deben gestionar supervisores desde el panel de admin

## 🔄 Próximas Mejoras (Opcionales)

- [ ] Notificaciones cuando se agrega/remueve un miembro
- [ ] Exportar reporte del equipo a PDF
- [ ] Gráficos de progreso del equipo (Chart.js)
- [ ] Comparativa de rendimiento entre miembros
- [ ] Historial de cambios en el equipo
- [ ] Filtros y búsqueda avanzada en la tabla de tareas
- [ ] Asignación de objetivos por miembro

## ✅ Checklist de Instalación

- [ ] Ejecutar add_supervisor_role.sql en Azure PostgreSQL
- [ ] Asignar rol "supervisor" a usuarios deseados
- [ ] Verificar que los archivos CSS/JS se cargan correctamente
- [ ] Agregar enlace "Mi Equipo" en la navegación principal
- [ ] Probar agregar miembro desde el dashboard
- [ ] Probar ver tareas de un miembro
- [ ] Probar editar notas de un miembro
- [ ] Probar remover miembro del equipo
- [ ] Verificar que NO se ven datos sensibles
- [ ] Probar en mobile/tablet

## 📞 Soporte

Si encuentras problemas, revisa:
1. Logs de Apache: `c:\wamp64\logs\apache_error.log`
2. Logs de PostgreSQL en Azure Portal
3. Consola del navegador (F12) para errores JavaScript
4. Verificar permisos de archivos/directorios
