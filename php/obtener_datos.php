<?php
require_once __DIR__ . "/funciones.php";

try {
    $conexion = obtenerConexion();

    // Trae habitaciones
    $habitaciones = [];
    $resultadoHabitaciones = $conexion->query("SELECT numero, tipo, piso, estado FROM habitaciones ORDER BY numero ASC");
    while ($fila = $resultadoHabitaciones->fetch_assoc()) {
        $habitaciones[] = $fila;
    }

    // Trae inventario
    $inventario = [];
    $resultadoInventario = $conexion->query("SELECT producto, cantidad_disponible AS cantidad FROM inventario ORDER BY producto ASC");
    while ($fila = $resultadoInventario->fetch_assoc()) {
        $fila["cantidad"] = (int)$fila["cantidad"];
        $inventario[] = $fila;
    }

    // Trae asignaciones
    $asignaciones = [];
    $resultadoAsignaciones = $conexion->query("SELECT codigo_asignacion, habitacion, empleado, fecha_asignacion, hora_asignacion, estado FROM asignaciones_limpieza ORDER BY fecha_asignacion DESC, hora_asignacion DESC, id DESC");
    while ($fila = $resultadoAsignaciones->fetch_assoc()) {
        $asignaciones[] = [
            "id" => $fila["codigo_asignacion"],
            "habitacion" => $fila["habitacion"],
            "empleado" => $fila["empleado"],
            "fechaISO" => $fila["fecha_asignacion"],
            "hora24" => substr($fila["hora_asignacion"], 0, 5),
            "estado" => $fila["estado"]
        ];
    }

    // Trae registros
    $registros = [];
    $resultadoRegistros = $conexion->query("SELECT id, codigo_asignacion, habitacion, empleado, fecha_registro, hora_registro, estado, observaciones FROM registros_limpieza ORDER BY fecha_registro DESC, hora_registro DESC, id DESC");
    while ($fila = $resultadoRegistros->fetch_assoc()) {
        $registros[] = [
            "idRegistro" => (int)$fila["id"],
            "asignacionId" => $fila["codigo_asignacion"],
            "habitacion" => $fila["habitacion"],
            "empleado" => $fila["empleado"],
            "fecha" => $fila["fecha_registro"],
            "hora" => substr($fila["hora_registro"], 0, 5),
            "estado" => $fila["estado"],
            "observaciones" => $fila["observaciones"]
        ];
    }

    // Devuelve todo al frontend
    responderJson([
        "ok" => true,
        "habitaciones" => $habitaciones,
        "inventario" => $inventario,
        "asignaciones" => $asignaciones,
        "registros" => $registros
    ]);
} catch (Throwable $e) {
    responderJson([
        "ok" => false,
        "mensaje" => "No se pudieron cargar los datos",
        "error" => $e->getMessage()
    ], 500);
}
?>
