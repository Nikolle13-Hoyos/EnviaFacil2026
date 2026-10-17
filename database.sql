CREATE DATABASE IF NOT EXISTS gestor_envios
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE gestor_envios;

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(120) NOT NULL,
    telefono VARCHAR(30),
    email VARCHAR(150),
    direccion VARCHAR(255),
    ciudad VARCHAR(100),
    departamento VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    origen VARCHAR(150) NOT NULL,
    destino VARCHAR(150) NOT NULL,
    peso_kg DECIMAL(8,2) NOT NULL,
    valor_declarado DECIMAL(12,2) DEFAULT 0,
    transportadora VARCHAR(80),
    guia VARCHAR(80),
    estado ENUM(
        'PEDIDO_CREADO',
        'GUIA_GENERADA',
        'EN_TRANSITO',
        'EN_REPARTO',
        'ENTREGADO',
        'CANCELADO'
    ) DEFAULT 'PEDIDO_CREADO',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id)
);

CREATE TABLE cotizaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NULL,
    transportadora VARCHAR(80) NOT NULL,
    precio DECIMAL(12,2) NOT NULL,
    dias_estimados INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE SET NULL
);

CREATE TABLE movimientos_envio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    estado VARCHAR(50) NOT NULL,
    descripcion VARCHAR(255),
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE
);

INSERT INTO clientes (nombre, telefono, email, direccion, ciudad, departamento) VALUES
('Laura Gómez','3001234567','laura.Gomez@icloud.com','Calle 45 #78-32','Bogotá','Cundinamarca'),
('Valentina Rojas','3015557788','valentinaRJ@gmail.com','Carrera 23 #10-20','Cali','Valle del Cauca'),
('Sofía Torres','3021112233','valentinaRJ@gmail.com','Carrera 43 #20-15','Medellín','Antioquia');

INSERT INTO pedidos
(cliente_id, origen, destino, peso_kg, valor_declarado, transportadora, guia, estado)
VALUES
(1,'Medellín','Bogotá',1.20,100000,'Servientrega','786543210','EN_TRANSITO'),
(2,'Medellín','Cali',0.80,80000,'Inter Rapidísimo','786543211','ENTREGADO'),
(3,'Medellín','Medellín',2.00,150000,'Coordinadora','786543212','EN_REPARTO');
