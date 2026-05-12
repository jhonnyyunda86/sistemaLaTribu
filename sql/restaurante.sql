CREATE DATABASE IF NOT EXISTS bdrestaurante CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bdrestaurante;

DROP TABLE IF EXISTS detalle_factura, factura, detalle_pedido, pedido, tipo_pedido, estado_pedido, movimiento_inventario, inventario, producto, categoria_producto, reserva, estado_reserva, mesa, mesero, cliente, usuario;

CREATE TABLE usuario (
  id_usuario INT PRIMARY KEY AUTO_INCREMENT,
  nombre VARCHAR(80) NOT NULL,
  correo VARCHAR(80) NOT NULL UNIQUE,
  telefono VARCHAR(20),
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','mesero','cliente') NOT NULL DEFAULT 'cliente',
  activo TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE cliente (id_cliente INT PRIMARY KEY AUTO_INCREMENT, id_usuario INT NOT NULL, telefono VARCHAR(20), nit VARCHAR(20), FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE);
CREATE TABLE mesero (id_mesero INT PRIMARY KEY AUTO_INCREMENT, id_usuario INT NOT NULL, FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE);
CREATE TABLE mesa (id_mesa INT PRIMARY KEY AUTO_INCREMENT, numero_mesa INT NOT NULL UNIQUE, capacidad INT NOT NULL, estado ENUM('disponible','ocupada','reservada','mantenimiento') DEFAULT 'disponible');
CREATE TABLE estado_reserva (id_estado_reserva INT PRIMARY KEY AUTO_INCREMENT, nombre_estado VARCHAR(30) NOT NULL);
CREATE TABLE reserva (id_reserva INT PRIMARY KEY AUTO_INCREMENT, id_cliente INT, id_mesa INT, fecha_reserva DATE, hora_reserva TIME, numero_personas INT, id_estado_reserva INT, FOREIGN KEY (id_cliente) REFERENCES cliente(id_cliente), FOREIGN KEY (id_mesa) REFERENCES mesa(id_mesa), FOREIGN KEY (id_estado_reserva) REFERENCES estado_reserva(id_estado_reserva));
CREATE TABLE categoria_producto (id_categoria INT PRIMARY KEY AUTO_INCREMENT, nombre_categoria VARCHAR(50) NOT NULL);
CREATE TABLE producto (id_producto INT PRIMARY KEY AUTO_INCREMENT, id_categoria INT, nombre VARCHAR(80) NOT NULL, precio DECIMAL(10,2) NOT NULL, descripcion VARCHAR(180), FOREIGN KEY (id_categoria) REFERENCES categoria_producto(id_categoria));
CREATE TABLE inventario (id_inventario INT PRIMARY KEY AUTO_INCREMENT, id_producto INT, cantidad_actual INT DEFAULT 0, cantidad_minima INT DEFAULT 0, fecha_actualizacion DATE, FOREIGN KEY (id_producto) REFERENCES producto(id_producto));
CREATE TABLE movimiento_inventario (id_movimiento INT PRIMARY KEY AUTO_INCREMENT, id_producto INT, tipo_movimiento VARCHAR(20), cantidad INT, fecha_movimiento DATE, FOREIGN KEY (id_producto) REFERENCES producto(id_producto));
CREATE TABLE tipo_pedido (id_tipo_pedido INT PRIMARY KEY AUTO_INCREMENT, nombre_tipo VARCHAR(30) NOT NULL);
CREATE TABLE estado_pedido (id_estado_pedido INT PRIMARY KEY AUTO_INCREMENT, nombre_estado VARCHAR(30) NOT NULL);
CREATE TABLE pedido (id_pedido INT PRIMARY KEY AUTO_INCREMENT, id_cliente INT, id_mesero INT, id_tipo_pedido INT, fecha_pedido DATETIME DEFAULT CURRENT_TIMESTAMP, id_estado_pedido INT, FOREIGN KEY (id_cliente) REFERENCES cliente(id_cliente), FOREIGN KEY (id_mesero) REFERENCES mesero(id_mesero), FOREIGN KEY (id_tipo_pedido) REFERENCES tipo_pedido(id_tipo_pedido), FOREIGN KEY (id_estado_pedido) REFERENCES estado_pedido(id_estado_pedido));
CREATE TABLE detalle_pedido (id_detalle INT PRIMARY KEY AUTO_INCREMENT, id_pedido INT, id_producto INT, cantidad INT, precio_unitario DECIMAL(10,2), subtotal DECIMAL(10,2), FOREIGN KEY (id_pedido) REFERENCES pedido(id_pedido), FOREIGN KEY (id_producto) REFERENCES producto(id_producto));
CREATE TABLE factura (id_factura INT PRIMARY KEY AUTO_INCREMENT, id_pedido INT, id_cliente INT, fecha DATE, metodo_pago VARCHAR(30), total_factura DECIMAL(10,2), FOREIGN KEY (id_pedido) REFERENCES pedido(id_pedido), FOREIGN KEY (id_cliente) REFERENCES cliente(id_cliente));
CREATE TABLE detalle_factura (id_detalleFactura INT PRIMARY KEY AUTO_INCREMENT, id_factura INT, id_producto INT, cantidad INT, precio_unitario DECIMAL(10,2), subtotal DECIMAL(10,2), FOREIGN KEY (id_factura) REFERENCES factura(id_factura), FOREIGN KEY (id_producto) REFERENCES producto(id_producto));

INSERT INTO usuario(nombre,correo,telefono,password,role) VALUES
('Administrador','admin@latribu.com','3000000000','$2y$12$OTPY4q5PtVNs89Pc.rcT2.5/1jFdO7D/OGWwBcEu6CgGpeOmyr.ia','admin'),
('Mesero Demo','mesero@latribu.com','3001111111','$2y$12$OTPY4q5PtVNs89Pc.rcT2.5/1jFdO7D/OGWwBcEu6CgGpeOmyr.ia','mesero'),
('Cliente Demo','cliente@latribu.com','3002222222','$2y$12$OTPY4q5PtVNs89Pc.rcT2.5/1jFdO7D/OGWwBcEu6CgGpeOmyr.ia','cliente');
INSERT INTO mesero(id_usuario) VALUES (2);
INSERT INTO cliente(id_usuario,telefono,nit) VALUES (3,'3002222222','123456789');
INSERT INTO mesa(numero_mesa,capacidad,estado) VALUES (1,4,'disponible'),(2,2,'ocupada'),(3,6,'reservada'),(4,4,'disponible');
INSERT INTO estado_reserva(nombre_estado) VALUES ('Pendiente'),('Confirmada'),('Cancelada');
INSERT INTO categoria_producto(nombre_categoria) VALUES ('Comidas rápidas'),('Bebidas'),('Parrilla');
INSERT INTO producto(id_categoria,nombre,precio,descripcion) VALUES (1,'Hamburguesa Tribu',22000,'Hamburguesa artesanal con papas'),(1,'Salchipapa Especial',18000,'Salchipapa con salsas de la casa'),(2,'Limonada Natural',7000,'Bebida refrescante'),(3,'Costillas BBQ',32000,'Costillas en salsa BBQ');
INSERT INTO tipo_pedido(nombre_tipo) VALUES ('Mesa'),('Domicilio'),('Para llevar');
INSERT INTO estado_pedido(nombre_estado) VALUES ('Pendiente'),('En preparación'),('Entregado'),('Cancelado');
