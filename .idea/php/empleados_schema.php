<?php
require_once __DIR__ . "/funciones.php";

function columnaExiste($conexion, $tabla, $columna)
{
    $sql = "SELECT COUNT(*) AS total
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ss", $tabla, $columna);
    $stmt->execute();
    return (int)$stmt->get_result()->fetch_assoc()["total"] > 0;
}

function asegurarEstructuraEmpleados($conexion)
{
    $conexion->query("CREATE TABLE IF NOT EXISTS empleados_limpieza (
        id int NOT NULL AUTO_INCREMENT,
        codigo_empleado varchar(20) NOT NULL,
        nombre varchar(80) NOT NULL,
        apellido varchar(80) NOT NULL,
        telefono varchar(30) NOT NULL,
        direccion varchar(180) NOT NULL,
        fecha_ingreso date NOT NULL,
        puesto varchar(80) NOT NULL DEFAULT 'Auxiliar de Limpieza',
        estado_laboral varchar(30) NOT NULL DEFAULT 'Activo',
        fecha_salida date DEFAULT NULL,
        motivo_salida varchar(120) DEFAULT NULL,
        notas_internas text DEFAULT NULL,
        foto varchar(255) DEFAULT NULL,
        fecha_creacion timestamp NOT NULL DEFAULT current_timestamp(),
        fecha_actualizacion timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (id),
        UNIQUE KEY uk_codigo_empleado (codigo_empleado)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    if (!columnaExiste($conexion, "asignaciones_limpieza", "empleado_id")) {
        $conexion->query("ALTER TABLE asignaciones_limpieza ADD COLUMN empleado_id int NULL AFTER habitacion");
    }

    if (!columnaExiste($conexion, "registros_limpieza", "empleado_id")) {
        $conexion->query("ALTER TABLE registros_limpieza ADD COLUMN empleado_id int NULL AFTER habitacion");
    }

    if (!columnaExiste($conexion, "empleados_limpieza", "fecha_salida")) {
        $conexion->query("ALTER TABLE empleados_limpieza ADD COLUMN fecha_salida date NULL AFTER estado_laboral");
    }

    if (!columnaExiste($conexion, "empleados_limpieza", "motivo_salida")) {
        $conexion->query("ALTER TABLE empleados_limpieza ADD COLUMN motivo_salida varchar(120) NULL AFTER fecha_salida");
    }

    $total = (int)$conexion->query("SELECT COUNT(*) AS total FROM empleados_limpieza")->fetch_assoc()["total"];
    if ($total > 0) {
        return;
    }

    $empleados = [
        ["EMP-001", "Ana", "Perez", "809-555-0101", "Av. Independencia 45, Santo Domingo", "2024-01-15", "Activo", "Excelente puntualidad y buen manejo de habitaciones familiares.", "imagenes_empleados/emp-001.svg"],
        ["EMP-002", "Manuela", "Gomez", "809-555-0102", "Calle Duarte 18, Santo Domingo", "2024-02-02", "Activo", "Apoya turnos de manana y refuerzos de fin de semana.", "imagenes_empleados/emp-002.svg"],
        ["EMP-003", "Samuel", "Rodriguez", "809-555-0103", "Ensanche La Fe, Santo Domingo", "2024-03-11", "Activo", "Buen seguimiento de observaciones del supervisor.", "imagenes_empleados/emp-003.svg"],
        ["EMP-004", "Carolina", "Mendez", "809-555-0104", "Villa Juana, Santo Domingo", "2023-11-20", "Activo", "Responsable de apoyo en habitaciones premium.", "imagenes_empleados/emp-004.svg"],
        ["EMP-005", "Jose", "Ramirez", "809-555-0105", "Los Mina, Santo Domingo Este", "2023-10-05", "Activo", "Disponible para coberturas de tarde.", "imagenes_empleados/emp-005.svg"],
        ["EMP-006", "Elena", "Martinez", "809-555-0106", "Gazcue, Santo Domingo", "2024-04-01", "Vacaciones", "En vacaciones temporalmente.", "imagenes_empleados/emp-006.svg"],
        ["EMP-007", "Miguel", "Santos", "809-555-0107", "Herrera, Santo Domingo Oeste", "2024-01-29", "Activo", "Buen desempeno en limpieza profunda.", "imagenes_empleados/emp-007.svg"],
        ["EMP-008", "Rosa", "Castillo", "809-555-0108", "Cristo Rey, Santo Domingo", "2023-09-17", "Activo", "Apoya inventario y preparacion de carritos.", "imagenes_empleados/emp-008.svg"]
    ];

    $stmt = $conexion->prepare("INSERT INTO empleados_limpieza
        (codigo_empleado, nombre, apellido, telefono, direccion, fecha_ingreso, puesto, estado_laboral, notas_internas, foto)
        VALUES (?, ?, ?, ?, ?, ?, 'Auxiliar de Limpieza', ?, ?, ?)");

    foreach ($empleados as $empleado) {
        $stmt->bind_param(
            "sssssssss",
            $empleado[0],
            $empleado[1],
            $empleado[2],
            $empleado[3],
            $empleado[4],
            $empleado[5],
            $empleado[6],
            $empleado[7],
            $empleado[8]
        );
        $stmt->execute();
    }
}
?>
