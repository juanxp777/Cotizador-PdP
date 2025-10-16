-- Base de datos para Sistema de Cotización de Impresión
-- Ejecutar este script en phpMyAdmin de Hostinger

CREATE DATABASE IF NOT EXISTS cotizador_impresion CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cotizador_impresion;

-- Tabla de usuarios administradores
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Insertar usuario por defecto (usuario: admin, contraseña: admin123)
INSERT INTO usuarios (username, password, nombre, email) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador', 'admin@empresa.com');

-- Tabla de parámetros del sistema
CREATE TABLE parametros (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_param VARCHAR(100) UNIQUE NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    descripcion VARCHAR(255),
    categoria VARCHAR(50),
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Insertar parámetros iniciales
INSERT INTO parametros (nombre_param, valor, descripcion, categoria) VALUES
-- Impresión Digital
('digital_portada', 2500, 'Costo impresión digital portada por ejemplar', 'digital'),
('digital_pagina_interior', 150, 'Costo impresión digital página interior por ejemplar', 'digital'),

-- Impresión Offset
('offset_montaje', 150000, 'Costo de montaje offset', 'offset'),
('offset_plancha_medio', 45000, 'Costo plancha litográfica medio pliego', 'offset'),
('offset_plancha_cuarto', 35000, 'Costo plancha litográfica cuarto de pliego', 'offset'),
('offset_tiraje_portada', 800, 'Costo tiraje offset portada por ejemplar', 'offset'),
('offset_tiraje_interior', 80, 'Costo tiraje offset página interior por ejemplar', 'offset'),

-- Papel
('papel_portada', 1200, 'Costo papel portada por ejemplar', 'papel'),
('papel_interior', 80, 'Costo papel página interior por ejemplar', 'papel'),

-- Plastificado
('plastificado_mate', 1500, 'Plastificado mate por ejemplar', 'plastificado'),
('plastificado_brillante', 1500, 'Plastificado brillante por ejemplar', 'plastificado'),
('plastificado_soft', 2500, 'Plastificado soft touch por ejemplar', 'plastificado'),

-- Encuadernación
('encuadernacion_grapado', 500, 'Grapado por ejemplar', 'encuadernacion'),
('encuadernacion_espiral_plastico', 2000, 'Espiral plástico por ejemplar', 'encuadernacion'),
('encuadernacion_espiral_metal', 3000, 'Espiral metálico por ejemplar', 'encuadernacion'),
('encuadernacion_encolado', 3500, 'Perfect bound por ejemplar', 'encuadernacion'),
('encuadernacion_tapa_dura', 8000, 'Tapa dura por ejemplar', 'encuadernacion'),

-- Acabados Especiales
('acabado_uv', 3000, 'Barniz UV selectivo por ejemplar', 'acabados'),
('acabado_relieve', 4000, 'Relieve por ejemplar', 'acabados'),
('acabado_hot_stamping', 5000, 'Hot stamping por ejemplar', 'acabados'),

-- Empaque y Transporte
('empaque_unidad', 300, 'Empaque por unidad', 'logistica'),
('transporte_base', 50000, 'Costo base de transporte', 'logistica'),
('transporte_unidad', 200, 'Costo transporte por unidad', 'logistica'),

-- Umbral
('umbral_digital', 200, 'Cantidad límite para usar digital', 'general');

-- Tabla de cotizaciones
CREATE TABLE cotizaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    folio VARCHAR(20) UNIQUE NOT NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Datos del cliente
    cliente_nombre VARCHAR(100),
    cliente_email VARCHAR(100),
    cliente_telefono VARCHAR(20),
    
    -- Datos del producto
    tipo_producto ENUM('agenda', 'cuaderno', 'revista') NOT NULL,
    cantidad INT NOT NULL,
    paginas INT NOT NULL,
    plastificado VARCHAR(50),
    encuadernacion VARCHAR(50),
    acabado_especial VARCHAR(50),
    incluye_transporte BOOLEAN DEFAULT TRUE,
    
    -- Resultados del cálculo
    metodo_impresion VARCHAR(100),
    costo_planchas DECIMAL(10,2) DEFAULT 0,
    detalle_planchas TEXT,
    costo_impresion DECIMAL(10,2) NOT NULL,
    costo_papel DECIMAL(10,2) NOT NULL,
    costo_acabados DECIMAL(10,2) NOT NULL,
    costo_empaque DECIMAL(10,2) NOT NULL,
    costo_transporte DECIMAL(10,2) DEFAULT 0,
    subtotal DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    
    -- Estado
    estado ENUM('pendiente', 'aprobada', 'rechazada') DEFAULT 'pendiente',
    notas TEXT,
    
    usuario_id INT,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB;

-- Índices para mejorar rendimiento
CREATE INDEX idx_fecha ON cotizaciones(fecha);
CREATE INDEX idx_cliente_email ON cotizaciones(cliente_email);
CREATE INDEX idx_estado ON cotizaciones(estado);
CREATE INDEX idx_categoria ON parametros(categoria);