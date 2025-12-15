# 🎉 Nuevas Funcionalidades - App-Tareas

## 📊 Dashboard de Estadísticas

El dashboard ahora muestra métricas en tiempo real:

- **Total de tareas**: Contador general
- **Pendientes**: Tareas sin desplegar
- **Desplegados**: Tareas en producción
- **Urgentes**: Tareas con urgencia alta sin desplegar
- **Vencidos**: Tareas que pasaron su fecha límite
- **Esta Semana**: Tareas con vencimiento en los próximos 7 días

## 🔍 Sistema de Filtros y Búsqueda Avanzada

### Búsqueda por Texto
Busca en títulos y descripciones de tareas.

### Filtros Disponibles
- **Estado**: Todas, Pendientes, Desplegados, Urgentes, Vencidos
- **Categoría**: Frontend, Backend, Database, Hotfix, Feature, Otro
- **Prioridad**: Crítico, Alto, Medio, Bajo

### Combinación de Filtros
Puedes combinar búsqueda de texto con filtros para resultados precisos.

## 🏷️ Categorías y Prioridades

### Categorías
Organiza tus tareas por tipo:
- **Frontend**: Cambios en UI/UX
- **Backend**: Lógica de servidor
- **Database**: Cambios en BD
- **Hotfix**: Correcciones urgentes
- **Feature**: Nuevas funcionalidades
- **Otro**: Misceláneos

### Prioridades
Sistema de 4 niveles:
- 🔴 **Crítico**: Atención inmediata
- 🟠 **Alto**: Importante
- 🟡 **Medio**: Normal
- 🟢 **Bajo**: Puede esperar

## ✅ Checklist Pre-Deployment

Antes de marcar una tarea como desplegada, debes completar:

1. **💾 Backup realizado**: Respaldo de seguridad
2. **🧪 Tests ejecutados**: Pruebas pasadas
3. **📚 Documentación actualizada**: Docs al día
4. **👥 Equipo notificado**: Comunicación completada

### Información Adicional
- **Tiempo de deployment**: Registra duración en minutos
- **Notas del deployment**: Documenta problemas o detalles

## 📅 Calendario Visual

### Características
- Vista mensual de todas las tareas
- Navegación entre meses
- Código de colores:
  - **Rojo**: Tareas vencidas ⚠️
  - **Amarillo**: Tareas pendientes ⏳
  - **Rojo oscuro**: Urgentes 🔥
  - **Verde**: Desplegadas ✅

### Resumen del Mes
Estadísticas del mes actual en la parte inferior.

### Acceso
Botón "📅 Calendario" en la barra superior.

## 📜 Historial y Audit Log

### Registro Automático
Cada cambio en una tarea se registra:
- Quién hizo el cambio
- Cuándo se hizo
- Qué cambió (valores antes/después)

### Tipos de Acciones
- ➕ Creada
- ✏️ Modificada
- ✅ Desplegada
- ↩️ Revertida
- 🗑️ Eliminada

### Visualización
Línea de tiempo visual con todos los eventos de la tarea.

### Acceso
Botón "📜 Historial" en la página de edición.

## 📧 Sistema de Notificaciones

### Tipos de Notificaciones

#### 1. Tareas Próximas (3 días antes)
Recordatorio de tareas que vencen pronto.

#### 2. Tareas Vencidas
Alerta de tareas que pasaron su fecha límite.

#### 3. Resumen Semanal
Email con estadísticas de la semana.

### Configuración

Las notificaciones se almacenan en la base de datos. Para enviar emails reales:

1. Configurar variables de entorno:
```env
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=tu-email@gmail.com
SMTP_PASS=tu-password
SMTP_FROM=noreply@tu-dominio.com
APP_URL=https://tu-dominio.com
```

2. Instalar PHPMailer (recomendado):
```bash
composer require phpmailer/phpmailer
```

3. Ejecutar script manualmente o con cron:
```bash
# Todas las notificaciones
php src/notifications.php all

# Solo próximas a vencer
php src/notifications.php upcoming

# Solo vencidas
php src/notifications.php overdue

# Resumen semanal
php src/notifications.php weekly
```

### Cron Job Sugerido (Linux/Mac)
```cron
# Diario a las 9 AM
0 9 * * * cd /ruta/a/app && php src/notifications.php all

# Resumen semanal los lunes a las 8 AM
0 8 * * 1 cd /ruta/a/app && php src/notifications.php weekly
```

### Windows Task Scheduler
1. Abrir "Programador de tareas"
2. Crear tarea básica
3. Trigger: Diariamente a las 9:00
4. Acción: Iniciar programa
5. Programa: `php.exe`
6. Argumentos: `C:\ruta\a\src\notifications.php all`

## 🗄️ Nuevas Tablas de Base de Datos

### task_history
Almacena el historial de cambios:
- `id`: ID único
- `task_id`: ID de la tarea
- `user_id`: Usuario que hizo el cambio
- `action`: Tipo de acción
- `old_values`: Valores anteriores (JSON)
- `new_values`: Valores nuevos (JSON)
- `created_at`: Fecha del cambio

### notifications
Almacena notificaciones pendientes:
- `id`: ID único
- `user_id`: Usuario destinatario
- `task_id`: Tarea relacionada
- `type`: Tipo de notificación
- `message`: Contenido del mensaje
- `sent`: Estado de envío (0/1)
- `sent_at`: Fecha de envío
- `created_at`: Fecha de creación

## 📦 Instalación de Nuevas Funcionalidades

### 1. Migrar Base de Datos Existente

Si ya tienes datos, ejecuta la migración:

```bash
# PostgreSQL
psql -U tu_usuario -d tasks_app -f db/migration_add_features.sql
```

### 2. Base de Datos Nueva

Si es instalación nueva:

```bash
# PostgreSQL
psql -U tu_usuario -d tasks_app -f db/schema.sql
```

### 3. Verificar Instalación

Accede a la aplicación y verifica:
- ✅ Dashboard muestra estadísticas
- ✅ Filtros funcionan correctamente
- ✅ Calendario se visualiza
- ✅ Modal de checklist aparece al desplegar
- ✅ Historial se registra

## 🎨 Nuevos Estilos CSS

Se agregaron estilos para:
- Dashboard con tarjetas estadísticas
- Sección de filtros
- Calendario mensual
- Línea de tiempo del historial
- Modal de checklist

## 🚀 Próximas Mejoras Sugeridas

- [ ] Exportar tareas a CSV/Excel
- [ ] Gráficas de productividad
- [ ] Etiquetas personalizadas
- [ ] Comentarios en tareas
- [ ] Adjuntar archivos
- [ ] Vista Kanban
- [ ] Integración con Slack/Teams
- [ ] API REST

## 📝 Notas Importantes

1. **Backup**: Siempre haz backup antes de migrar
2. **Permisos**: Asegúrate de tener permisos de escritura en la BD
3. **Cache**: Limpia cache del navegador después de actualizar
4. **Notificaciones**: Configura SMTP para emails reales
5. **Seguridad**: Usa HTTPS en producción

## 🆘 Soporte

Si encuentras problemas:
1. Verifica que la migración se ejecutó correctamente
2. Revisa los logs de PostgreSQL
3. Asegúrate de tener las columnas nuevas en la tabla `tasks`
4. Verifica que las tablas `task_history` y `notifications` existan

## 📄 Licencia

Este proyecto es de uso libre para aprendizaje y proyectos personales.
