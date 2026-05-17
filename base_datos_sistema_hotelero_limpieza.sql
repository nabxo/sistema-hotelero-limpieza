CREATE DATABASE IF NOT EXISTS `sistema-hotelero-limpieza`
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE `sistema-hotelero-limpieza`;

-- Limpia tablas viejas
DROP TABLE IF EXISTS `registro_limpieza`;
DROP TABLE IF EXISTS `inventario_limpieza`;
DROP TABLE IF EXISTS `registros_limpieza`;
DROP TABLE IF EXISTS `asignaciones_limpieza`;
DROP TABLE IF EXISTS `empleados_limpieza`;
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

-- Empleados de limpieza
CREATE TABLE IF NOT EXISTS `empleados_limpieza` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo_empleado` varchar(20) NOT NULL,
  `nombre` varchar(80) NOT NULL,
  `apellido` varchar(80) NOT NULL,
  `telefono` varchar(30) NOT NULL,
  `direccion` varchar(180) NOT NULL,
  `fecha_ingreso` date NOT NULL,
  `puesto` varchar(80) NOT NULL DEFAULT 'Auxiliar de Limpieza',
  `estado_laboral` varchar(30) NOT NULL DEFAULT 'Activo',
  `notas_internas` text DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_codigo_empleado` (`codigo_empleado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Asignaciones
CREATE TABLE IF NOT EXISTS `asignaciones_limpieza` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo_asignacion` varchar(60) NOT NULL,
  `habitacion` varchar(10) NOT NULL,
  `empleado_id` int DEFAULT NULL,
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
  `empleado_id` int DEFAULT NULL,
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

-- Datos base de empleados de limpieza
INSERT INTO `empleados_limpieza`
(`codigo_empleado`, `nombre`, `apellido`, `telefono`, `direccion`, `fecha_ingreso`, `puesto`, `estado_laboral`, `notas_internas`, `foto`) VALUES
('EMP-001', 'Ana', 'Perez', '809-555-0101', 'Av. Independencia 45, Santo Domingo', '2024-01-15', 'Auxiliar de Limpieza', 'Activo', 'Excelente puntualidad y buen manejo de habitaciones familiares.', 'imagenes_empleados/emp-001.svg'),
('EMP-002', 'Manuela', 'Gomez', '809-555-0102', 'Calle Duarte 18, Santo Domingo', '2024-02-02', 'Auxiliar de Limpieza', 'Activo', 'Apoya turnos de manana y refuerzos de fin de semana.', 'imagenes_empleados/emp-002.svg'),
('EMP-003', 'Samuel', 'Rodriguez', '809-555-0103', 'Ensanche La Fe, Santo Domingo', '2024-03-11', 'Auxiliar de Limpieza', 'Activo', 'Buen seguimiento de observaciones del supervisor.', 'imagenes_empleados/emp-003.svg'),
('EMP-004', 'Carolina', 'Mendez', '809-555-0104', 'Villa Juana, Santo Domingo', '2023-11-20', 'Auxiliar de Limpieza', 'Activo', 'Responsable de apoyo en habitaciones premium.', 'imagenes_empleados/emp-004.svg'),
('EMP-005', 'Jose', 'Ramirez', '809-555-0105', 'Los Mina, Santo Domingo Este', '2023-10-05', 'Auxiliar de Limpieza', 'Activo', 'Disponible para coberturas de tarde.', 'imagenes_empleados/emp-005.svg'),
('EMP-006', 'Elena', 'Martinez', '809-555-0106', 'Gazcue, Santo Domingo', '2024-04-01', 'Auxiliar de Limpieza', 'Vacaciones', 'En vacaciones temporalmente.', 'imagenes_empleados/emp-006.svg'),
('EMP-007', 'Miguel', 'Santos', '809-555-0107', 'Herrera, Santo Domingo Oeste', '2024-01-29', 'Auxiliar de Limpieza', 'Activo', 'Buen desempeno en limpieza profunda.', 'imagenes_empleados/emp-007.svg'),
('EMP-008', 'Rosa', 'Castillo', '809-555-0108', 'Cristo Rey, Santo Domingo', '2023-09-17', 'Auxiliar de Limpieza', 'Activo', 'Apoya inventario y preparacion de carritos.', 'imagenes_empleados/emp-008.svg')
ON DUPLICATE KEY UPDATE
`nombre` = VALUES(`nombre`),
`apellido` = VALUES(`apellido`),
`telefono` = VALUES(`telefono`),
`direccion` = VALUES(`direccion`),
`fecha_ingreso` = VALUES(`fecha_ingreso`),
`puesto` = VALUES(`puesto`),
`estado_laboral` = VALUES(`estado_laboral`),
`notas_internas` = VALUES(`notas_internas`),
`foto` = VALUES(`foto`);
