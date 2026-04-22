CREATE DATABASE IF NOT EXISTS `sistema-hotelero-limpieza`
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE `sistema-hotelero-limpieza`;

-- Limpia tablas viejas
DROP TABLE IF EXISTS `registro_limpieza`;
DROP TABLE IF EXISTS `inventario_limpieza`;
DROP TABLE IF EXISTS `registros_limpieza`;
DROP TABLE IF EXISTS `asignaciones_limpieza`;
DROP TABLE IF EXISTS `inventario`;
DROP TABLE IF EXISTS `habitaciones`;

-- Habitaciones
CREATE TABLE IF NOT EXISTS `habitaciones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `numero` varchar(10) NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `piso` int NOT NULL,
  `estado` varchar(30) NOT NULL,
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_numero_habitacion` (`numero`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inventario
CREATE TABLE IF NOT EXISTS `inventario` (
  `id` int NOT NULL AUTO_INCREMENT,
  `producto` varchar(100) NOT NULL,
  `cantidad_disponible` int NOT NULL DEFAULT 0,
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_producto` (`producto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Asignaciones
CREATE TABLE IF NOT EXISTS `asignaciones_limpieza` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo_asignacion` varchar(60) NOT NULL,
  `habitacion` varchar(10) NOT NULL,
  `empleado` varchar(120) NOT NULL,
  `fecha_asignacion` date NOT NULL,
  `hora_asignacion` time NOT NULL,
  `estado` varchar(30) NOT NULL DEFAULT 'Sucia',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_codigo_asignacion` (`codigo_asignacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Registros de limpieza
CREATE TABLE IF NOT EXISTS `registros_limpieza` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo_asignacion` varchar(60) NOT NULL,
  `habitacion` varchar(10) NOT NULL,
  `empleado` varchar(120) NOT NULL,
  `fecha_registro` date NOT NULL,
  `hora_registro` time NOT NULL,
  `estado` varchar(30) NOT NULL,
  `observaciones` text DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos base de habitaciones
INSERT INTO `habitaciones` (`numero`, `tipo`, `piso`, `estado`) VALUES
('101', 'Simple', 1, 'Limpia'),
('102', 'Simple', 1, 'Sucia'),
('103', 'Simple', 1, 'Limpia'),
('104', 'Simple', 1, 'Sucia'),
('105', 'Simple', 1, 'Limpia'),
('201', 'Normal', 2, 'Limpia'),
('202', 'Normal', 2, 'Sucia'),
('203', 'Normal', 2, 'Limpia'),
('204', 'Normal', 2, 'Sucia'),
('205', 'Normal', 2, 'Mantenimiento'),
('301', 'Familiar', 1, 'Limpia'),
('302', 'Familiar', 2, 'Sucia'),
('303', 'Familiar', 3, 'Sucia'),
('304', 'Familiar', 4, 'Limpia'),
('305', 'Familiar', 3, 'Mantenimiento'),
('401', 'Lujosa', 4, 'Limpia'),
('402', 'Lujosa', 5, 'Sucia'),
('403', 'Lujosa', 6, 'Limpia'),
('404', 'Lujosa', 6, 'Sucia'),
('405', 'Lujosa', 6, 'Mantenimiento')
ON DUPLICATE KEY UPDATE
`tipo` = VALUES(`tipo`),
`piso` = VALUES(`piso`),
`estado` = VALUES(`estado`);

-- Datos base del inventario
INSERT INTO `inventario` (`producto`, `cantidad_disponible`) VALUES
('Jabón', 50),
('Toallas', 30),
('Detergente', 20),
('Guantes', 100),
('Desinfectante', 15)
ON DUPLICATE KEY UPDATE
`cantidad_disponible` = VALUES(`cantidad_disponible`);
