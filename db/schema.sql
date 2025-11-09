-- Script para crear la base de datos y la tabla de tareas
CREATE DATABASE IF NOT EXISTS tasks_app CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE tasks_app;

CREATE TABLE IF NOT EXISTS tasks (
  id INT AUTO_INCREMENT PRIMARY KEY,
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
  doc_politica_respaldo TINYINT(1) DEFAULT 0
);

-- Si la tabla ya existe, agregar las columnas nuevas
ALTER TABLE tasks 
ADD COLUMN IF NOT EXISTS requires_docs TINYINT(1) DEFAULT 0,
ADD COLUMN IF NOT EXISTS doc_plan_prueba TINYINT(1) DEFAULT 0,
ADD COLUMN IF NOT EXISTS doc_plan_produccion TINYINT(1) DEFAULT 0,
ADD COLUMN IF NOT EXISTS doc_control_objeto TINYINT(1) DEFAULT 0,
ADD COLUMN IF NOT EXISTS doc_politica_respaldo TINYINT(1) DEFAULT 0;
