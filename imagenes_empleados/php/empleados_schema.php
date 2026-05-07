<?php
require_once __DIR__ . "/funciones.php";

function asegurarEstructuraEmpleados(mysqli $conexion): void
{
    $conexion->query("
        CREATE TABLE IF NOT EXISTS empleados_limpieza (
            id INT NOT NULL AUTO_INCREMENT,
            codigo_empleado VARCHAR(20) NOT NULL,
            nombre VARCHAR(80) NOT NULL,
            apellido VARCHAR(80) NOT NULL,
            telefono VARCHAR(30) NOT NULL,
            direccion VARCHAR(180) NOT NULL,
            fecha_ingreso DATE NOT NULL,
            puesto VARCHAR(80) NOT NULL DEFAULT 'Auxiliar de Limpieza',
            estado_laboral VARCHAR(30) NOT NULL DEFAULT 'Activo',
            fecha_salida DATE DEFAULT NULL,
            motivo_salida VARCHAR(180) DEFAULT NULL,
            notas_internas TEXT DEFAULT NULL,
            foto VARCHAR(255) DEFAULT NULL,
            fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uk_codigo_empleado (codigo_empleado)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $conexion->query("
        CREATE TABLE IF NOT EXISTS habitaciones (
            id INT NOT NULL AUTO_INCREMENT,
            numero VARCHAR(10) NOT NULL,
            tipo VARCHAR(50) NOT NULL,
            piso INT NOT NULL,
            estado VARCHAR(30) NOT NULL,
            fecha_actualizacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uk_numero_habitacion (numero)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $conexion->query("
        CREATE TABLE IF NOT EXISTS inventario (
            id INT NOT NULL AUTO_INCREMENT,
            producto VARCHAR(100) NOT NULL,
            cantidad_disponible INT NOT NULL DEFAULT 0,
            fecha_actualizacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uk_producto (producto)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $conexion->query("
        CREATE TABLE IF NOT EXISTS asignaciones_limpieza (
            id INT NOT NULL AUTO_INCREMENT,
            codigo_asignacion VARCHAR(60) NOT NULL,
            habitacion VARCHAR(10) NOT NULL,
            empleado_id INT DEFAULT NULL,
            empleado VARCHAR(120) NOT NULL,
            fecha_asignacion DATE NOT NULL,
            hora_asignacion TIME NOT NULL,
            estado VARCHAR(30) NOT NULL DEFAULT 'Sucia',
            fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uk_codigo_asignacion (codigo_asignacion)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $conexion->query("
        CREATE TABLE IF NOT EXISTS registros_limpieza (
            id INT NOT NULL AUTO_INCREMENT,
            codigo_asignacion VARCHAR(60) NOT NULL,
            habitacion VARCHAR(10) NOT NULL,
            empleado_id INT DEFAULT NULL,
            empleado VARCHAR(120) NOT NULL,
            fecha_registro DATE NOT NULL,
            hora_registro TIME NOT NULL,
            estado VARCHAR(30) NOT NULL,
            observaciones TEXT DEFAULT NULL,
            fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    agregarColumnaSiNoExiste($conexion, "empleados_limpieza", "fecha_salida", "DATE DEFAULT NULL");
    agregarColumnaSiNoExiste($conexion, "empleados_limpieza", "motivo_salida", "VARCHAR(180) DEFAULT NULL");
    agregarColumnaSiNoExiste($conexion, "asignaciones_limpieza", "empleado_id", "INT DEFAULT NULL");
    agregarColumnaSiNoExiste($conexion, "registros_limpieza", "empleado_id", "INT DEFAULT NULL");

    sembrarHabitacionesSiHaceFalta($conexion);
    sembrarInventarioSiHaceFalta($conexion);
    sembrarEmpleadosSiHaceFalta($conexion);
}

function agregarColumnaSiNoExiste(mysqli $conexion, string $tabla, string $columna, string $definicion): void
{
    $tablaSeguro = preg_replace('/[^a-zA-Z0-9_]/', '', $tabla);
    $columnaSegura = $conexion->real_escape_string($columna);
    $resultado = $conexion->query("SHOW COLUMNS FROM {$tablaSeguro} LIKE '{$columnaSegura}'");

    if ($resultado->num_rows === 0) {
        $conexion->query("ALTER TABLE {$tablaSeguro} ADD COLUMN {$columna} {$definicion}");
    }
}

function sembrarHabitacionesSiHaceFalta(mysqli $conexion): void
{
    $total = (int)$conexion->query("SELECT COUNT(*) AS total FROM habitaciones")->fetch_assoc()["total"];
    if ($total > 0) {
        return;
    }

    $habitaciones = [
        ["101", "Simple", 1, "Limpia"],
        ["102", "Simple", 1, "Sucia"],
        ["103", "Simple", 1, "Limpia"],
        ["104", "Simple", 1, "Sucia"],
        ["105", "Simple", 1, "Limpia"],
        ["201", "Normal", 2, "Limpia"],
        ["202", "Normal", 2, "Sucia"],
        ["203", "Normal", 2, "Limpia"],
        ["204", "Normal", 2, "Sucia"],
        ["205", "Normal", 2, "Mantenimiento"],
        ["301", "Familiar", 1, "Limpia"],
        ["302", "Familiar", 2, "Sucia"],
        ["303", "Familiar", 3, "Sucia"],
        ["304", "Familiar", 4, "Limpia"],
        ["305", "Familiar", 3, "Mantenimiento"],
        ["401", "Lujosa", 4, "Limpia"],
        ["402", "Lujosa", 5, "Sucia"],
        ["403", "Lujosa", 6, "Limpia"],
        ["404", "Lujosa", 6, "Sucia"],
        ["405", "Lujosa", 6, "Mantenimiento"]
    ];

    $stmt = $conexion->prepare("INSERT INTO habitaciones (numero, tipo, piso, estado) VALUES (?, ?, ?, ?)");
    foreach ($habitaciones as [$numero, $tipo, $piso, $estado]) {
        $stmt->bind_param("ssis", $numero, $tipo, $piso, $estado);
        $stmt->execute();
    }
}

function sembrarInventarioSiHaceFalta(mysqli $conexion): void
{
    $total = (int)$conexion->query("SELECT COUNT(*) AS total FROM inventario")->fetch_assoc()["total"];
    if ($total > 0) {
        return;
    }

    $inventario = [
        ["Jabon", 50],
        ["Toallas", 30],
        ["Detergente", 20],
        ["Guantes", 100],
        ["Desinfectante", 15]
    ];

    $stmt = $conexion->prepare("INSERT INTO inventario (producto, cantidad_disponible) VALUES (?, ?)");
    foreach ($inventario as [$producto, $cantidad]) {
        $stmt->bind_param("si", $producto, $cantidad);
        $stmt->execute();
    }
}

function sembrarEmpleadosSiHaceFalta(mysqli $conexion): void
{
    $total = (int)$conexion->query("SELECT COUNT(*) AS total FROM empleados_limpieza")->fetch_assoc()["total"];
    if ($total > 0) {
        return;
    }

    $empleados = [
        ["EMP-001", "Ana", "Perez", "809-555-0101", "Av. Independencia 45, Santo Domingo", "2024-01-15", "Activo", null, null, "Excelente puntualidad y buen manejo de habitaciones familiares.", "imagenes_empleados/emp-001.svg"],
        ["EMP-002", "Manuela", "Gomez", "809-555-0102", "Calle Duarte 18, Santo Domingo", "2024-02-02", "Activo", null, null, "Apoya turnos de manana y refuerzos de fin de semana.", "imagenes_empleados/emp-002.svg"],
        ["EMP-003", "Samuel", "Rodriguez", "809-555-0103", "Ensanche La Fe, Santo Domingo", "2024-03-11", "Activo", null, null, "Buen seguimiento de observaciones del supervisor.", "imagenes_empleados/emp-003.svg"],
        ["EMP-004", "Carolina", "Mendez", "809-555-0104", "Villa Juana, Santo Domingo", "2023-11-20", "Activo", null, null, "Responsable de apoyo en habitaciones premium.", "imagenes_empleados/emp-004.svg"],
        ["EMP-005", "Jose", "Ramirez", "809-555-0105", "Los Mina, Santo Domingo Este", "2023-10-05", "Activo", null, null, "Disponible para coberturas de tarde.", "imagenes_empleados/emp-005.svg"],
        ["EMP-006", "Elena", "Martinez", "809-555-0106", "Gazcue, Santo Domingo", "2024-04-01", "Vacaciones", null, null, "En vacaciones temporalmente.", "imagenes_empleados/emp-006.svg"],
        ["EMP-007", "Miguel", "Santos", "809-555-0107", "Herrera, Santo Domingo Oeste", "2024-01-29", "Activo", null, null, "Buen desempeno en limpieza profunda.", "imagenes_empleados/emp-007.svg"],
        ["EMP-008", "Rosa", "Castillo", "809-555-0108", "Cristo Rey, Santo Domingo", "2023-09-17", "Activo", null, null, "Apoya inventario y preparacion de carritos.", "imagenes_empleados/emp-008.svg"]
    ];

    $stmt = $conexion->prepare("
        INSERT INTO empleados_limpieza
        (codigo_empleado, nombre, apellido, telefono, direccion, fecha_ingreso, puesto, estado_laboral, fecha_salida, motivo_salida, notas_internas, foto)
        VALUES (?, ?, ?, ?, ?, ?, 'Auxiliar de Limpieza', ?, ?, ?, ?, ?)
    ");

    foreach ($empleados as [$codigo, $nombre, $apellido, $telefono, $direccion, $fechaIngreso, $estado, $fechaSalida, $motivoSalida, $notas, $foto]) {
        $stmt->bind_param("sssssssssss", $codigo, $nombre, $apellido, $telefono, $direccion, $fechaIngreso, $estado, $fechaSalida, $motivoSalida, $notas, $foto);
        $stmt->execute();
    }
}
