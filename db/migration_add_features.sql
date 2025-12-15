-- Migración para agregar nuevas columnas a base de datos existente
-- Ejecutar DESPUÉS de tener datos existentes para no perderlos

\c tasks_app

-- Agregar nuevas columnas a tasks
ALTER TABLE tasks ADD COLUMN IF NOT EXISTS priority VARCHAR(10) CHECK (priority IN ('Crítico', 'Alto', 'Medio', 'Bajo')) DEFAULT 'Medio';
ALTER TABLE tasks ADD COLUMN IF NOT EXISTS category VARCHAR(50) CHECK (category IN ('Frontend', 'Backend', 'Database', 'Hotfix', 'Feature', 'Otro')) DEFAULT 'Otro';
ALTER TABLE tasks ADD COLUMN IF NOT EXISTS deployed_at TIMESTAMP DEFAULT NULL;
ALTER TABLE tasks ADD COLUMN IF NOT EXISTS deployed_by INTEGER DEFAULT NULL;
ALTER TABLE tasks ADD COLUMN IF NOT EXISTS deployment_notes TEXT;
ALTER TABLE tasks ADD COLUMN IF NOT EXISTS deployment_duration INTEGER DEFAULT NULL;
ALTER TABLE tasks ADD COLUMN IF NOT EXISTS checklist_backup SMALLINT DEFAULT 0 CHECK (checklist_backup IN (0, 1));
ALTER TABLE tasks ADD COLUMN IF NOT EXISTS checklist_tests SMALLINT DEFAULT 0 CHECK (checklist_tests IN (0, 1));
ALTER TABLE tasks ADD COLUMN IF NOT EXISTS checklist_docs SMALLINT DEFAULT 0 CHECK (checklist_docs IN (0, 1));
ALTER TABLE tasks ADD COLUMN IF NOT EXISTS checklist_team SMALLINT DEFAULT 0 CHECK (checklist_team IN (0, 1));

-- Crear índices adicionales
CREATE INDEX IF NOT EXISTS idx_due_date ON tasks(due_date);
CREATE INDEX IF NOT EXISTS idx_priority ON tasks(priority);
CREATE INDEX IF NOT EXISTS idx_category ON tasks(category);

-- Crear tabla de historial/audit log
CREATE TABLE IF NOT EXISTS task_history (
  id SERIAL PRIMARY KEY,
  task_id INTEGER NOT NULL,
  user_id INTEGER NOT NULL,
  action VARCHAR(50) NOT NULL,
  old_values TEXT,
  new_values TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_task_history_task_id ON task_history(task_id);
CREATE INDEX IF NOT EXISTS idx_task_history_user_id ON task_history(user_id);
CREATE INDEX IF NOT EXISTS idx_task_history_created_at ON task_history(created_at);

-- Crear tabla de notificaciones
CREATE TABLE IF NOT EXISTS notifications (
  id SERIAL PRIMARY KEY,
  user_id INTEGER NOT NULL,
  task_id INTEGER NOT NULL,
  type VARCHAR(50) NOT NULL,
  message TEXT NOT NULL,
  sent SMALLINT DEFAULT 0 CHECK (sent IN (0, 1)),
  sent_at TIMESTAMP DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_notifications_user_id ON notifications(user_id);
CREATE INDEX IF NOT EXISTS idx_notifications_sent ON notifications(sent);
CREATE INDEX IF NOT EXISTS idx_notifications_created_at ON notifications(created_at);

-- Mensaje de éxito
\echo 'Migración completada exitosamente'
