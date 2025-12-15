-- Script para crear la base de datos y la tabla de tareas (PostgreSQL)
-- Crear base de datos (ejecutar como superusuario)
-- CREATE DATABASE tasks_app WITH ENCODING 'UTF8';
-- \c tasks_app

-- Tabla de usuarios
CREATE TABLE IF NOT EXISTS users (
  id SERIAL PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_username ON users(username);
CREATE INDEX IF NOT EXISTS idx_email ON users(email);

-- Tabla de tareas
CREATE TABLE IF NOT EXISTS tasks (
  id SERIAL PRIMARY KEY,
  user_id INTEGER NOT NULL,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  urgency VARCHAR(10) CHECK (urgency IN ('Baja', 'Media', 'Alta')) DEFAULT 'Media',
  priority VARCHAR(10) CHECK (priority IN ('Crítico', 'Alto', 'Medio', 'Bajo')) DEFAULT 'Medio',
  category VARCHAR(50) CHECK (category IN ('Frontend', 'Backend', 'Database', 'Hotfix', 'Feature', 'Otro')) DEFAULT 'Otro',
  due_date DATE DEFAULT NULL,
  deployed SMALLINT DEFAULT 0 CHECK (deployed IN (0, 1)),
  deployed_at TIMESTAMP DEFAULT NULL,
  deployed_by INTEGER DEFAULT NULL,
  deployment_notes TEXT,
  deployment_duration INTEGER DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  requires_docs SMALLINT DEFAULT 0 CHECK (requires_docs IN (0, 1)),
  doc_plan_prueba SMALLINT DEFAULT 0 CHECK (doc_plan_prueba IN (0, 1)),
  doc_plan_produccion SMALLINT DEFAULT 0 CHECK (doc_plan_produccion IN (0, 1)),
  doc_control_objeto SMALLINT DEFAULT 0 CHECK (doc_control_objeto IN (0, 1)),
  doc_politica_respaldo SMALLINT DEFAULT 0 CHECK (doc_politica_respaldo IN (0, 1)),
  checklist_backup SMALLINT DEFAULT 0 CHECK (checklist_backup IN (0, 1)),
  checklist_tests SMALLINT DEFAULT 0 CHECK (checklist_tests IN (0, 1)),
  checklist_docs SMALLINT DEFAULT 0 CHECK (checklist_docs IN (0, 1)),
  checklist_team SMALLINT DEFAULT 0 CHECK (checklist_team IN (0, 1)),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_user_id ON tasks(user_id);
CREATE INDEX IF NOT EXISTS idx_deployed ON tasks(deployed);
CREATE INDEX IF NOT EXISTS idx_created_at ON tasks(created_at);
CREATE INDEX IF NOT EXISTS idx_due_date ON tasks(due_date);
CREATE INDEX IF NOT EXISTS idx_priority ON tasks(priority);
CREATE INDEX IF NOT EXISTS idx_category ON tasks(category);

-- Tabla de historial/audit log
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

-- Tabla de notificaciones
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
