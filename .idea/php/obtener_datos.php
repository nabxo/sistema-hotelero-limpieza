<?php
require_once __DIR__ . "/empleados_schema.php";

try {
    $conexion = obtenerConexion();
    asegurarEstructuraEmpleados($conexion);

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
    $resultadoAsignaciones = $conexion->query("SELECT codigo_asignacion, habitacion, empleado_id, empleado, fecha_asignacion, hora_asignacion, estado FROM asignaciones_limpieza ORDER BY fecha_asignacion DESC, hora_asignacion DESC, id DESC");
    while ($fila = $resultadoAsignaciones->fetch_assoc()) {
        $asignaciones[] = [
            "id" => $fila["codigo_asignacion"],
            "habitacion" => $fila["habitacion"],
            "empleadoId" => $fila["empleado_id"] ? (int)$fila["empleado_id"] : null,
            "empleado" => $fila["empleado"],
            "fechaISO" => $fila["fecha_asignacion"],
            "hora24" => substr($fila["hora_asignacion"], 0, 5),
            "estado" => $fila["estado"]
        ];
    }

    // Trae empleados de limpieza
    $empleados = [];
    $resultadoEmpleados = $conexion->query("SELECT id, codigo_empleado, nombre, apellido, telefono, direccion, fecha_ingreso, puesto, estado_laboral, notas_internas, foto FROM empleados_limpieza ORDER BY estado_laboral = 'Activo' DESC, nombre ASC");
    while ($fila = $resultadoEmpleados->fetch_assoc()) {
        $empleados[] = [
            "id" => (int)$fila["id"],
            "codigo" => $fila["codigo_empleado"],
            "nombre" => $fila["nombre"],
            "apellido" => $fila["apellido"],
            "nombreCompleto" => trim($fila["nombre"] . " " . $fila["apellido"]),
            "telefono" => $fila["telefono"],
            "direccion" => $fila["direccion"],
            "fechaIngreso" => $fila["fecha_ingreso"],
            "puesto" => $fila["puesto"],
            "estadoLaboral" => $fila["estado_laboral"],
            "notasInternas" => $fila["notas_internas"],
            "foto" => $fila["foto"]
        ];
    }

    // Trae registros
    $registros = [];
    $resultadoRegistros = $conexion->query("SELECT id, codigo_asignacion, habitacion, empleado_id, empleado, fecha_registro, hora_registro, estado, observaciones FROM registros_limpieza ORDER BY fecha_registro DESC, hora_registro DESC, id DESC");
    while ($fila = $resultadoRegistros->fetch_assoc()) {
        $registros[] = [
            "idRegistro" => (int)$fila["id"],
            "asignacionId" => $fila["codigo_asignacion"],
            "habitacion" => $fila["habitacion"],
            "empleadoId" => $fila["empleado_id"] ? (int)$fila["empleado_id"] : null,
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
        "empleados" => $empleados,
        "asignaciones" => $asignaciones,
        "registros" => $registros
    ]);
} catch (Throwable $e) {
    manejarErrorServidor("No se pudieron cargar los datos", $e);
}
?>
