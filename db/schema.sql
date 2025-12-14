-- Script para crear la base de datos y la tabla de tareas
CREATE DATABASE IF NOT EXISTS tasks_app CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE tasks_app;

-- Tabla de usuarios
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_username (username),
  INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla de tareas
CREATE TABLE IF NOT EXISTS tasks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  urgency ENUM('Baja','Media','Alta') DEFAULT 'Media',
  due_date DATE DEFAULT NULL,
  deployed TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  requires_docs TINYINT(1) DEFAULT 0,
  doc_plan_prueba TINYINT(1) DEFAULT 0,
  doc_plan_produccion TINYINT(1) DEFAULT 0,
  doc_control_objeto TINYINT(1) DEFAULT 0,
  doc_politica_respaldo TINYINT(1) DEFAULT 0,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user_id (user_id),
  INDEX idx_deployed (deployed),
  INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Si la tabla ya existe, agregar las columnas nuevas
ALTER TABLE tasks 
ADD COLUMN IF NOT EXISTS user_id INT NOT NULL AFTER id,
ADD COLUMN IF NOT EXISTS requires_docs TINYINT(1) DEFAULT 0,
ADD COLUMN IF NOT EXISTS doc_plan_prueba TINYINT(1) DEFAULT 0,
ADD COLUMN IF NOT EXISTS doc_plan_produccion TINYINT(1) DEFAULT 0,
ADD COLUMN IF NOT EXISTS doc_control_objeto TINYINT(1) DEFAULT 0,
ADD COLUMN IF NOT EXISTS doc_politica_respaldo TINYINT(1) DEFAULT 0;

-- Agregar índice y foreign key si no existe
ALTER TABLE tasks 
ADD INDEX IF NOT EXISTS idx_user_id (user_id);

-- Nota: Si tienes datos existentes, asigna un user_id válido antes de agregar la foreign key
