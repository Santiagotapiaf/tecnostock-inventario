-- TecnoStock - Script de base de datos
-- Ejecutar sobre un servidor limpio (phpMyAdmin > Importar).
-- Usuario de prueba: admin@tecnostock.cl / clave: 123456

CREATE DATABASE IF NOT EXISTS tecnostock
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tecnostock;

CREATE TABLE usuario (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    correo VARCHAR(100) NOT NULL UNIQUE,
    clave VARCHAR(255) NOT NULL,
    estado TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE categoria (
    id_categoria INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion VARCHAR(255),
    estado TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE producto (
    id_producto INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    nombre VARCHAR(150) NOT NULL,
    descripcion VARCHAR(255),
    precio DECIMAL(10,2) NOT NULL CHECK (precio >= 0),
    stock_actual INT NOT NULL DEFAULT 0 CHECK (stock_actual >= 0),
    stock_minimo INT NOT NULL DEFAULT 0 CHECK (stock_minimo >= 0),
    estado TINYINT(1) NOT NULL DEFAULT 1,
    id_categoria INT NOT NULL,
    FOREIGN KEY (id_categoria) REFERENCES categoria(id_categoria)
) ENGINE=InnoDB;

CREATE TABLE movimiento (
    id_movimiento INT AUTO_INCREMENT PRIMARY KEY,
    fecha_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    tipo ENUM('ENTRADA','SALIDA') NOT NULL,
    cantidad INT NOT NULL CHECK (cantidad > 0),
    observacion VARCHAR(255),
    id_producto INT NOT NULL,
    id_usuario INT NOT NULL,
    FOREIGN KEY (id_producto) REFERENCES producto(id_producto),
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
) ENGINE=InnoDB;

-- Datos de prueba
INSERT INTO usuario (nombre, correo, clave, estado) VALUES
('Encargado', 'admin@tecnostock.cl', '$2y$10$5jU4UWaIP/8OXKxkvGAfk.oNjLobmLjR3x86jLayF4nBrSj/p4ybG', 1);

INSERT INTO categoria (nombre, descripcion, estado) VALUES
('Audio', 'Audífonos, parlantes y accesorios de sonido', 1),
('Cables y adaptadores', 'Cables USB, HDMI, adaptadores y conectores', 1),
('Almacenamiento', 'Pendrives, discos externos y tarjetas de memoria', 1),
('Periféricos', 'Teclados, mouse y accesorios de computador', 1);

INSERT INTO producto (codigo, nombre, descripcion, precio, stock_actual, stock_minimo, estado, id_categoria) VALUES
('AUD-001', 'Audífonos Bluetooth XT200', 'Audífonos inalámbricos con cancelación de ruido', 15000, 15, 5, 1, 1),
('AUD-002', 'Parlante Portátil Mini', 'Parlante bluetooth resistente al agua', 22000, 8, 3, 1, 1),
('CAB-010', 'Cable USB-C a HDMI', 'Cable adaptador USB-C a HDMI 4K', 8000, 2, 5, 1, 2),
('ALM-001', 'Pendrive 64GB USB 3.0', 'Memoria USB de alta velocidad', 7000, 30, 10, 1, 3),
('PER-001', 'Mouse Inalámbrico', 'Mouse óptico inalámbrico 2.4 GHz', 9000, 12, 4, 1, 4);