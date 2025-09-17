DROP DATABASE IF EXISTS Proyecto;
CREATE DATABASE Proyecto;
USE Proyecto;

-- 1. Tabla usuarios
CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100),
    correo VARCHAR(100) UNIQUE,
    contraseña VARCHAR(255),
    direccion TEXT,
    telefono VARCHAR(20)
);

-- 2. Géneros - CORREGIDO
CREATE TABLE generos (
    id_genero INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50)
);

-- 3. Usos - CORREGIDO  
CREATE TABLE usos (
    id_uso INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50)
);

-- 4. Deportes
CREATE TABLE deportes (
    id_deporte INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50)
);

-- 5. Tallas - AMPLIADAS
CREATE TABLE tallas (
    id_talla INT AUTO_INCREMENT PRIMARY KEY,
    talla VARCHAR(10)
);

-- 6. Productos
CREATE TABLE productos (
    id_producto INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100),
    marca VARCHAR(50),
    descripcion TEXT,
    precio DECIMAL(10,2),
    imagen_url VARCHAR(255),
    id_genero INT,
    id_uso INT,
    id_deporte INT,
    FOREIGN KEY (id_genero) REFERENCES generos(id_genero),
    FOREIGN KEY (id_uso) REFERENCES usos(id_uso),
    FOREIGN KEY (id_deporte) REFERENCES deportes(id_deporte)
);

-- 7. Stock por talla y producto
CREATE TABLE producto_tallas (
    id_producto INT,
    id_talla INT,
    stock INT DEFAULT 0,
    PRIMARY KEY (id_producto, id_talla),
    FOREIGN KEY (id_producto) REFERENCES productos(id_producto),
    FOREIGN KEY (id_talla) REFERENCES tallas(id_talla)
);

-- 8. Carrito
CREATE TABLE carrito (
    id_carrito INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT,
    id_producto INT,
    id_talla INT,
    cantidad INT DEFAULT 1,
    fecha_agregado DATETIME DEFAULT NOW(),
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario),
    FOREIGN KEY (id_producto) REFERENCES productos(id_producto),
    FOREIGN KEY (id_talla) REFERENCES tallas(id_talla)
);

-- 9. Lista de deseos
CREATE TABLE wishlist (
    id_wishlist INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT,
    id_producto INT,
    id_talla INT NULL,
    fecha_guardado DATETIME DEFAULT NOW(),
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario),
    FOREIGN KEY (id_producto) REFERENCES productos(id_producto),
    FOREIGN KEY (id_talla) REFERENCES tallas(id_talla)
);

-- Tabla de ventas
CREATE TABLE ventas (
    id_venta INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    fecha_venta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estado VARCHAR(50) DEFAULT 'completada',
    direccion_entrega TEXT,
    telefono VARCHAR(20),
    metodo_pago VARCHAR(50),
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
);

-- Tabla de detalle de ventas
CREATE TABLE detalle_ventas (
    id_detalle INT AUTO_INCREMENT PRIMARY KEY,
    id_venta INT NOT NULL,
    id_producto INT NOT NULL,
    id_talla INT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (id_venta) REFERENCES ventas(id_venta) ON DELETE CASCADE,
    FOREIGN KEY (id_producto) REFERENCES productos(id_producto) ON DELETE CASCADE,
    FOREIGN KEY (id_talla) REFERENCES tallas(id_talla) ON DELETE CASCADE
);

-- Agregar índices para mejorar el rendimiento
CREATE INDEX idx_ventas_usuario ON ventas(id_usuario);
CREATE INDEX idx_ventas_fecha ON ventas(fecha_venta);
CREATE INDEX idx_detalle_venta ON detalle_ventas(id_venta);
CREATE INDEX idx_detalle_producto ON detalle_ventas(id_producto);

-- Usuarios
INSERT INTO usuarios (nombre, correo, contraseña, direccion, telefono) VALUES
('Juan Pérez', 'juan@example.com', '123456', 'Calle Futura #123', '7777-1234'),
('Ana Torres', 'ana@example.com', 'contraseñaSegura', 'Av. Sol #456', '8888-5678'),
('Arias', 'arias@gmail.com', '1702', 'micasa', '74496057');

-- Géneros 
INSERT INTO generos (nombre) VALUES
('Hombre'),    -- id=1
('Mujer'),     -- id=2  
('Niños');     -- id=3

-- Usos 
INSERT INTO usos (nombre) VALUES 
('Ropa'),        -- id=1
('Calzado'),     -- id=2
('Accesorios');  -- id=3

-- Deportes
INSERT INTO deportes (nombre) VALUES 
('Fútbol'),      -- id=1
('Running'),     -- id=2
('General'),     -- id=3 (Lifestyle/General)
('Básquetbol');  -- id=4

-- Tallas 
INSERT INTO tallas (talla) VALUES
-- Calzado Hombre/Mujer
('35'), ('36'), ('37'), ('38'), ('39'), ('40'), ('41'), ('42'), ('43'), ('44'), ('45'),
-- Calzado Niños  
('28'), ('29'), ('30'), ('31'), ('32'), ('33'), ('34'),
-- Ropa Adultos
('XS'), ('S'), ('M'), ('L'), ('XL'), ('XXL'),
-- Ropa Niños
('4'), ('6'), ('8'), ('10'), ('12'), ('14'),
-- Accesorios/Especiales
('Única'), ('Size 4'), ('Size 5'), ('Size 6'), ('Size 7');


INSERT INTO productos (nombre, marca, descripcion, precio, imagen_url, id_genero, id_uso, id_deporte) VALUES
-- === HOMBRE ===
-- Calzado Hombre
('Nike Air Max 270', 'Nike', 'Zapatillas deportivas para running con máxima comodidad', 150.00, 'img/nike_airmax_hombre.jpg', 1, 2, 2), -- Running 
('Adidas Copa Mundial', 'Adidas', 'Botines de fútbol profesionales en cuero', 180.00, 'img/copa_mundial.jpg', 1, 2, 1), -- Fútbol 
('Nike Air Jordan Slides', 'Nike', 'Sandalias deportivas cómodas para descanso', 45.00, 'img/jordan_slides.jpg', 1, 2, 3), -- General 
('Adidas Stan Smith', 'Adidas', 'Sneakers casuales icónicos en blanco y verde', 95.00, 'img/airmax.jpg', 1, 2, 2), -- CORREGIDO: Running 

-- Ropa Hombre
('Camiseta Básica Nike', 'Nike', 'Camiseta de algodón para uso diario', 25.00, 'img/camiseta_nike_hombre.jpg', 1, 1, 3), -- General 
('Pantalón Deportivo Adidas', 'Adidas', 'Pantalón ligero ideal para running', 55.00, 'img/pantalon_adidas_hombre.jpg', 1, 1, 2), -- Running 
('Hoodie Nike Essential', 'Nike', 'Sudadera con capucha en algodón suave', 65.00, 'img/hoodie_nike_hombre.jpg', 1, 1, 3), -- General 
('Shorts Under Armour', 'Under Armour', 'Shorts deportivos con tecnología anti-sudor', 35.00, 'img/shorts_ua_hombre.jpg', 1, 1, 2), -- Running 

-- Accesorios Hombre
('Gorra Nike Dri-FIT', 'Nike', 'Gorra deportiva con tecnología anti-sudor', 28.00, 'img/gorra_nike_hombre.jpg', 1, 3, 3), -- General 
('Mochila Adidas Classic', 'Adidas', 'Mochila espaciosa para deporte y viaje', 85.00, 'img/mochila_adidas_hombre.jpg', 1, 3, 3), -- General 
('Calcetines Nike Crew 3-Pack', 'Nike', 'Pack de 3 calcetines deportivos', 18.00, 'img/calcetines_nike_hombre.jpg', 1, 3, 3), -- General 

-- === MUJER ===
-- Calzado Mujer
('Nike Air Max 97 Women', 'Nike', 'Zapatillas deportivas con diseño futurista', 165.00, 'img/airmax97_mujer.jpg', 2, 2, 2), -- Running 
('Adidas Cloudfoam Pure', 'Adidas', 'Sandalias cómodas para uso casual', 60.00, 'img/cloudfoam_mujer.jpg', 2, 2, 3), -- General 
('Nike Mercurial Vapor', 'Nike', 'Botines deportivos para fútbol femenino', 120.00, 'img/mercurial_mujer.jpg', 2, 2, 1), -- Fútbol 

-- Ropa Mujer
('Nike Pro Top', 'Nike', 'Top deportivo de alto rendimiento', 40.00, 'img/pro_top_mujer.jpg', 2, 1, 3), -- General 
('Adidas Leggings Essentials', 'Adidas', 'Leggings cómodos para entrenamiento', 45.00, 'img/leggings_adidas_mujer.jpg', 2, 1, 3), -- General 
('Under Armour Hoodie', 'Under Armour', 'Sudadera con capucha para mujer', 70.00, 'img/hoodie_ua_mujer.jpg', 2, 1, 3), -- General 
('Nike Shorts Dri-FIT', 'Nike', 'Shorts deportivos para running', 32.00, 'img/shorts_nike_mujer.jpg', 2, 1, 2), -- Running 

-- Accesorios Mujer
('Gorra Adidas Baseball', 'Adidas', 'Gorra deportiva ajustable para mujer', 30.00, 'img/gorra_adidas_mujer.jpg', 2, 3, 3), -- General 
('Mochila Nike Heritage', 'Nike', 'Mochila elegante y funcional', 75.00, 'img/mochila_nike_mujer.jpg', 2, 3, 3), -- General 
('Medias Nike Everyday', 'Nike', 'Medias deportivas pack x3 para mujer', 15.00, 'img/medias_nike_mujer.jpg', 2, 3, 3), -- General 

-- === NIÑOS ===
-- Calzado Niños
('Nike Air Force 1 Kids', 'Nike', 'Botines clásicos para niños', 90.00, 'img/airforce_ninos.jpg', 3, 2, 1), -- CORREGIDO: Fútbol (antes era General)
('Crocs Classic Kids', 'Crocs', 'Sandalias cómodas y resistentes para niños', 35.00, 'img/crocs_ninos.jpg', 3, 2, 3), -- General 

-- Ropa Niños
('Camiseta Deportiva Kids', 'Adidas', 'Camiseta cómoda para actividades diarias', 20.00, 'img/camiseta_adidas_ninos.jpg', 3, 1, 3), -- General 
('Conjunto Deportivo Nike Kids', 'Nike', 'Conjunto completo para deportes', 55.00, 'img/conjunto_nike_ninos.jpg', 3, 1, 2), -- Running 
('Shorts Adidas Kids', 'Adidas', 'Shorts deportivos para niños', 25.00, 'img/shorts_adidas_ninos.jpg', 3, 1, 2), -- Running 

-- Accesorios Niños
('Mochila Escolar Nike Kids', 'Nike', 'Mochila colorida para la escuela', 45.00, 'img/mochila_nike_ninos.jpg', 3, 3, 3), -- General 
('Gorra Adidas Kids', 'Adidas', 'Gorra ajustable para niños', 22.00, 'img/gorra_adidas_ninos.jpg', 3, 3, 3), -- General 

-- === DEPORTES ESPECÍFICOS ===
-- Fútbol
('Balón Nike Strike', 'Nike', 'Balón oficial de fútbol size 5', 35.00, 'img/balon_nike_futbol.jpg', 1, 3, 1), -- Fútbol 
('Camiseta Barcelona', 'Nike', 'Camiseta oficial FC Barcelona 2024', 85.00, 'img/camiseta_barcelona.jpg', 1, 1, 1), -- Fútbol 

-- Running  
('Reloj Garmin Forerunner', 'Garmin', 'Reloj GPS especializado para running', 250.00, 'img/garmin_running.jpg', 1, 3, 2), -- Running 
('Chaqueta Nike Shield', 'Nike', 'Chaqueta ligera resistente al viento', 90.00, 'img/chaqueta_nike_running.jpg', 2, 1, 2), -- Running 

-- Básquetbol
('Balón Spalding NBA', 'Spalding', 'Balón oficial de básquetbol NBA', 45.00, 'img/balon_spalding_basket.jpg', 1, 3, 4), -- Básquetbol 
('Nike LeBron 20', 'Nike', 'Tenis de básquetbol de alta gama', 200.00, 'img/lebron20.jpg', 1, 2, 4), -- Básquetbol 
('Jersey Lakers', 'Nike', 'Camiseta oficial Los Angeles Lakers', 95.00, 'img/jersey_lakers.jpg', 1, 1, 4); -- Básquetbol 

-- ===== TALLAS Y STOCK - USANDO IDS CORRECTOS =====
INSERT INTO producto_tallas (id_producto, id_talla, stock) VALUES
-- Nike Air Max 270 (ID=1) - Tallas 38-45 (IDs de tallas 4-10)
(1, 4, 8), (1, 5, 12), (1, 6, 15), (1, 7, 10), (1, 8, 18), (1, 9, 12), (1, 10, 8), (1, 11, 5),

-- Adidas Copa Mundial (ID=2) - Tallas 38-45
(2, 4, 6), (2, 5, 10), (2, 6, 12), (2, 7, 15), (2, 8, 20), (2, 9, 14), (2, 10, 8), (2, 11, 4),

-- Nike Air Jordan Slides (ID=3) - Tallas 38-45
(3, 4, 10), (3, 5, 15), (3, 6, 20), (3, 7, 18), (3, 8, 25), (3, 9, 15), (3, 10, 10), (3, 11, 6),

-- Adidas Stan Smith (ID=4) - Tallas 38-45
(4, 4, 7), (4, 5, 12), (4, 6, 16), (4, 7, 14), (4, 8, 22), (4, 9, 16), (4, 10, 9), (4, 11, 5),

-- Camiseta Básica Nike (ID=5) - Tallas S,M,L,XL,XXL (IDs 19-23)
(5, 19, 20), (5, 20, 30), (5, 21, 25), (5, 22, 15), (5, 23, 8),

-- Pantalón Deportivo Adidas (ID=6) - Tallas S,M,L,XL,XXL
(6, 19, 12), (6, 20, 20), (6, 21, 18), (6, 22, 12), (6, 23, 6),

-- Hoodie Nike Essential (ID=7) - Tallas S,M,L,XL,XXL
(7, 19, 15), (7, 20, 25), (7, 21, 22), (7, 22, 18), (7, 23, 10),

-- Shorts Under Armour (ID=8) - Tallas S,M,L,XL,XXL
(8, 19, 18), (8, 20, 28), (8, 21, 24), (8, 22, 16), (8, 23, 8),

-- Gorra Nike Dri-FIT (ID=9) - Talla única (ID=30)
(9, 30, 50),

-- Mochila Adidas Classic (ID=10) - Talla única
(10, 30, 30),

-- Calcetines Nike Crew (ID=11) - Tallas S,M,L
(11, 19, 25), (11, 20, 35), (11, 21, 20),

-- Nike Air Max 97 Women (ID=12) - Tallas 35-42 (IDs 1-8)
(12, 1, 8), (12, 2, 12), (12, 3, 18), (12, 4, 15), (12, 5, 20), (12, 6, 14), (12, 7, 10), (12, 8, 6),

-- Adidas Cloudfoam Pure (ID=13) - Tallas 35-42
(13, 1, 10), (13, 2, 15), (13, 3, 20), (13, 4, 18), (13, 5, 22), (13, 6, 16), (13, 7, 12), (13, 8, 7),

-- Nike Mercurial Vapor (ID=14) - Tallas 35-42
(14, 1, 6), (14, 2, 10), (14, 3, 14), (14, 4, 12), (14, 5, 16), (14, 6, 12), (14, 7, 8), (14, 8, 4),

-- Nike Pro Top (ID=15) - Tallas XS,S,M,L,XL (IDs 18-22)
(15, 18, 12), (15, 19, 20), (15, 20, 25), (15, 21, 18), (15, 22, 10),

-- Adidas Leggings (ID=16) - Tallas XS,S,M,L,XL
(16, 18, 10), (16, 19, 18), (16, 20, 22), (16, 21, 16), (16, 22, 8),

-- Under Armour Hoodie (ID=17) - Tallas XS,S,M,L,XL
(17, 18, 8), (17, 19, 16), (17, 20, 20), (17, 21, 15), (17, 22, 7),

-- Nike Shorts Dri-FIT (ID=18) - Tallas XS,S,M,L,XL
(18, 18, 12), (18, 19, 20), (18, 20, 24), (18, 21, 18), (18, 22, 10),

-- Gorra Adidas Baseball (ID=19) - Talla única
(19, 30, 40),

-- Mochila Nike Heritage (ID=20) - Talla única
(20, 30, 25),

-- Medias Nike Everyday (ID=21) - Tallas S,M,L
(21, 19, 20), (21, 20, 30), (21, 21, 15),

-- Nike Air Force 1 Kids (ID=22) - Tallas 28-35 (IDs 12-18)
(22, 12, 12), (22, 13, 15), (22, 14, 18), (22, 15, 16), (22, 16, 20), (22, 17, 18), (22, 18, 14),

-- Crocs Classic Kids (ID=23) - Tallas 28-35
(23, 12, 15), (23, 13, 20), (23, 14, 25), (23, 15, 22), (23, 16, 28), (23, 17, 24), (23, 18, 18),

-- Camiseta Deportiva Kids (ID=24) - Tallas niños 4,6,8,10,12,14 (IDs 24-29)
(24, 24, 15), (24, 25, 20), (24, 26, 25), (24, 27, 20), (24, 28, 18), (24, 29, 12),

-- Conjunto Deportivo Nike Kids (ID=25) - Tallas niños
(25, 24, 10), (25, 25, 15), (25, 26, 18), (25, 27, 15), (25, 28, 12), (25, 29, 8),

-- Shorts Adidas Kids (ID=26) - Tallas niños
(26, 24, 12), (26, 25, 18), (26, 26, 20), (26, 27, 16), (26, 28, 14), (26, 29, 10),

-- Mochila Escolar Nike Kids (ID=27) - Talla única
(27, 30, 35),

-- Gorra Adidas Kids (ID=28) - Talla única
(28, 30, 45),

-- Balón Nike Strike (ID=29) - Size 5 y 4 (IDs 32-33)
(29, 32, 20), (29, 31, 15),

-- Camiseta Barcelona (ID=30) - Tallas S,M,L,XL,XXL
(30, 19, 25), (30, 20, 35), (30, 21, 30), (30, 22, 20), (30, 23, 10),

-- Reloj Garmin (ID=31) - Talla única
(31, 30, 12),

-- Chaqueta Nike Shield (ID=32) - Tallas XS,S,M,L,XL
(32, 18, 8), (32, 19, 15), (32, 20, 18), (32, 21, 14), (32, 22, 8),

-- Balón Spalding NBA (ID=33) - Size 7 y 6 (IDs 34-35)
(33, 34, 18), (33, 33, 12),

-- Nike LeBron 20 (ID=34) - Tallas 38-45
(34, 4, 5), (34, 5, 8), (34, 6, 10), (34, 7, 12), (34, 8, 15), (34, 9, 10), (34, 10, 6), (34, 11, 3),

-- Jersey Lakers (ID=35) - Tallas S,M,L,XL,XXL
(35, 19, 20), (35, 20, 28), (35, 21, 25), (35, 22, 18), (35, 23, 12);