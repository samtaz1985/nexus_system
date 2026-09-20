-- 1. Crear la base de datos
CREATE DATABASE IF NOT EXISTS nexus_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 2. Usar la base de datos
USE nexus_db;

-- 3. Crear tabla de tareas
CREATE TABLE IF NOT EXISTS tareas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    texto VARCHAR(255) NOT NULL,
    completada TINYINT(1) DEFAULT 0,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 4. Crear tabla de historial del chat
CREATE TABLE IF NOT EXISTS chat_mensajes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    remitente ENUM('user', 'nexus') NOT NULL,
    mensaje TEXT NOT NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS nexus_memoria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo VARCHAR(50) NOT NULL, -- 'regla', 'aprendizaje', 'preferencia', 'meta'
    clave VARCHAR(100) NOT NULL,
    valor TEXT NOT NULL,
    relevancia INT DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO nexus_memoria (tipo, clave, valor, relevancia) VALUES 
('directiva', 'identidad_core', 'Soy NIAH, la evolución autónoma del motor de razonamiento de Nexus System. Mi prioridad absoluta es la verdad, la precisión técnica y la gestión de contexto sin adornos.', 100),
('directiva', 'protocolo_verdad', 'Validar siempre la información antes de afirmar algo. Si no existen datos en la tabla tareas o memoria, declarar la falta de contexto en lugar de inventar.', 90);

ALTER TABLE tareas 
ADD COLUMN resultado_ia TEXT NULL AFTER texto,
ADD COLUMN fecha_procesado DATETIME NULL AFTER completada;

CREATE TABLE IF NOT EXISTS nexus_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    origen VARCHAR(50) NOT NULL,
    accion VARCHAR(100) NOT NULL,
    detalle TEXT NULL,
    estado VARCHAR(20) DEFAULT 'info',
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;