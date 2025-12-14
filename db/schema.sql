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
  due_date DATE DEFAULT NULL,
  deployed SMALLINT DEFAULT 0 CHECK (deployed IN (0, 1)),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  requires_docs SMALLINT DEFAULT 0 CHECK (requires_docs IN (0, 1)),
  doc_plan_prueba SMALLINT DEFAULT 0 CHECK (doc_plan_prueba IN (0, 1)),
  doc_plan_produccion SMALLINT DEFAULT 0 CHECK (doc_plan_produccion IN (0, 1)),
  doc_control_objeto SMALLINT DEFAULT 0 CHECK (doc_control_objeto IN (0, 1)),
  doc_politica_respaldo SMALLINT DEFAULT 0 CHECK (doc_politica_respaldo IN (0, 1)),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_user_id ON tasks(user_id);
CREATE INDEX IF NOT EXISTS idx_deployed ON tasks(deployed);
CREATE INDEX IF NOT EXISTS idx_created_at ON tasks(created_at);

-- Para tablas existentes (migración)
-- ALTER TABLE tasks ADD COLUMN IF NOT EXISTS user_id INTEGER;
-- ALTER TABLE tasks ADD COLUMN IF NOT EXISTS requires_docs SMALLINT DEFAULT 0;
-- ALTER TABLE tasks ADD COLUMN IF NOT EXISTS doc_plan_prueba SMALLINT DEFAULT 0;
-- ALTER TABLE tasks ADD COLUMN IF NOT EXISTS doc_plan_produccion SMALLINT DEFAULT 0;
-- ALTER TABLE tasks ADD COLUMN IF NOT EXISTS doc_control_objeto SMALLINT DEFAULT 0;
-- ALTER TABLE tasks ADD COLUMN IF NOT EXISTS doc_politica_respaldo SMALLINT DEFAULT 0;
