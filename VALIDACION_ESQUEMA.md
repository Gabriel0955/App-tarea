# Validación de Esquema de Base de Datos

## Resumen Ejecutivo

✅ **Estado**: La mayoría de las columnas están correctamente nombradas  
⚠️ **Problema Crítico**: La tabla `projects` necesita agregar columnas `color` e `icon`

---

## Tablas Validadas

### 1. ✅ `users`
**Columnas en DB**: `id, username, email, password, created_at, updated_at`

**Uso en código**:
- `UserService.php`: `SELECT id, username, password FROM users WHERE username = ? OR email = ?`
- `UserService.php`: `INSERT INTO users (username, email, password) VALUES (?, ?, ?) RETURNING id`

**Estado**: ✅ Todas las columnas coinciden perfectamente

---

### 2. ✅ `tasks`
**Columnas en DB**: `id, user_id, title, description, urgency, priority, category, due_date, requires_docs, doc_plan_prueba, doc_plan_produccion, doc_control_objeto, doc_politica_respaldo, deployed, deployed_at, deployed_by, deployment_notes, deployment_duration, checklist_backup, checklist_tests, checklist_docs, checklist_team, project_id, created_at, updated_at`

**Uso en código**:
- `TaskService.php` línea 70:
  ```sql
  INSERT INTO tasks (user_id, title, description, urgency, priority, category, 
                     due_date, deployed, project_id, requires_docs, doc_plan_prueba, 
                     doc_plan_produccion, doc_control_objeto, doc_politica_respaldo)
  ```
- `TaskService.php` línea 103:
  ```sql
  UPDATE tasks SET title = ?, description = ?, urgency = ?, priority = ?, 
                   category = ?, due_date = ?, deployed = ?, requires_docs = ?, 
                   doc_plan_prueba = ?, doc_plan_produccion = ?, doc_control_objeto = ?, 
                   doc_politica_respaldo = ?
  ```
- `TaskService.php` línea 190:
  ```sql
  UPDATE tasks SET deployed = 1, deployed_at = NOW(), deployed_by = ?, 
                   deployment_notes = ?, deployment_duration = ?, 
                   checklist_backup = ?, checklist_tests = ?, checklist_docs = ?, 
                   checklist_team = ?
  ```

**Estado**: ✅ Todas las columnas coinciden perfectamente

---

### 3. ⚠️ `projects` - **REQUIERE ACCIÓN**
**Columnas en DB**: `id, user_id, name, description, status, priority, start_date, target_date, deployed_date, progress_percentage, total_tasks, completed_tasks, category, repository_url, notes, created_at, updated_at`

**Columnas que usa el código**:
- `ProjectService.php` línea 20-21:
  ```sql
  INSERT INTO projects (user_id, name, description, color, icon)
  VALUES (:user_id, :name, :description, :color, :icon)
  ```

**Columnas FALTANTES en DB**:
- ❌ `color` VARCHAR(7) - Para identificación visual del proyecto
- ❌ `icon` VARCHAR(10) - Emoji o icono del proyecto
- ❌ `completed_at` TIMESTAMP - Fecha de completado

**Uso de estas columnas**:
- `ProjectService.php`: Inserta color e icon al crear proyecto
- `projects.php`: Muestra proyectos con colores y iconos
- `project_view.php`: Usa color en header del proyecto
- `project_api.php`: Recibe color e icon desde formulario

**Solución**: Ejecutar el script `db/fix_projects_schema.sql` que agregará estas columnas

---

### 4. ✅ `user_stats`
**Columnas en DB**: `id, user_id, total_points, current_level, tasks_completed, pomodoros_completed, total_focus_time, current_streak, max_streak, points_to_next_level, created_at, updated_at, last_activity_date`

**Uso en código**:
- `GamificationService.php` línea 11: `SELECT * FROM user_stats WHERE user_id = ?`
- `GamificationService.php` línea 17: `INSERT INTO user_stats (user_id) VALUES (?)`
- `GamificationService.php` línea 221: `UPDATE user_stats SET tasks_completed = tasks_completed + 1 WHERE user_id = ?`
- `GamificationService.php` línea 47-49:
  ```sql
  UPDATE user_stats 
  SET pomodoros_completed = pomodoros_completed + 1,
      total_focus_time = total_focus_time + ?
  ```

**Estado**: ✅ Todas las columnas coinciden perfectamente

---

### 5. ✅ `achievements`
**Columnas en DB**: `id, key, name, description, icon, category, condition_type, condition_value, points, badge_image, display_order, is_active, created_at`

**Uso en código**: Solo lectura (`SELECT * FROM achievements`), no hay INSERT/UPDATE

**Estado**: ✅ Correcta

---

### 6. ✅ `user_achievements`
**Columnas en DB**: `id, user_id, achievement_id, unlocked_at`

**Uso en código**: Solo lectura en consultas de gamificación

**Estado**: ✅ Correcta

---

### 7. ✅ `pomodoro_sessions`
**Columnas en DB**: `id, user_id, task_id, work_duration, break_duration, status, completed_at, focus_score, created_at`

**Uso en código**:
- `GamificationService.php` línea 37-40:
  ```sql
  INSERT INTO pomodoro_sessions 
  (user_id, task_id, work_duration, status, completed_at, focus_score)
  VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, 100)
  ```

**Estado**: ✅ Todas las columnas coinciden perfectamente

---

### 8. ✅ `quick_tasks`
**Columnas en DB**: `id, user_id, title, description, task_date, scheduled_time, completed_at, points_awarded, streak_bonus, created_at`

**Uso en código**:
- `QuickTaskService.php` línea 23-24:
  ```sql
  INSERT INTO quick_tasks (user_id, title, description, task_date, scheduled_time)
  VALUES (:user_id, :title, :description, :task_date, :scheduled_time)
  ```
- `QuickTaskService.php` línea 152: `SELECT points_awarded FROM quick_tasks`

**Estado**: ✅ Todas las columnas coinciden perfectamente

---

### 9-13. ✅ Tablas adicionales
- `ranking_history`: Solo lectura
- `activity_log`: Solo lectura
- `notifications`: Solo lectura  
- `task_comments`: Solo lectura
- `task_attachments`: Solo lectura

**Estado**: ✅ Sin problemas detectados

---

## Acciones Requeridas

### 🔴 CRÍTICO: Agregar columnas a tabla `projects`

La funcionalidad de proyectos NO funcionará hasta ejecutar este script:

```bash
# Ejecutar en Azure PostgreSQL
psql -h apptarea.postgres.database.azure.com -U myadmin -d postgres -f db/fix_projects_schema.sql
```

O desde VS Code PowerShell:
```powershell
psql -h apptarea.postgres.database.azure.com -U myadmin@apptarea -d postgres -f "c:\wamp64\www\App-Tareas\db\fix_projects_schema.sql"
```

### Columnas que se agregarán:
1. `color VARCHAR(7)` - Default: `#1976d2`
2. `icon VARCHAR(10)` - Default: `📁`
3. `completed_at TIMESTAMP` - Para fecha de completado

---

## Conclusión

✅ **12 de 13 tablas** están correctamente configuradas  
⚠️ **1 tabla requiere migración**: `projects`

Una vez ejecutado el script `fix_projects_schema.sql`, todas las tablas estarán sincronizadas con el código.
