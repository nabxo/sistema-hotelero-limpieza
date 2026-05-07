<?php
require_once __DIR__ . "/empleados_schema.php";
validarMetodoPost();

try {
    // Toma los datos del registro
    $datos = obtenerJsonEntrada();

    $codigoAsignacion = texto($datos["asignacionId"] ?? "");
    $habitacion = texto($datos["habitacion"] ?? "");
    $empleadoId = (int)($datos["empleadoId"] ?? 0);
    $empleado = texto($datos["empleado"] ?? "");
    $fecha = texto($datos["fecha"] ?? "");
    $hora = texto($datos["hora"] ?? "");
    $estado = texto($datos["estado"] ?? "");
    $observaciones = texto($datos["observaciones"] ?? "");

    if ($codigoAsignacion === "" || $habitacion === "" || $empleado === "" || $fecha === "" || $hora === "" || $estado === "") {
        responderJson(["ok" => false, "mensaje" => "Faltan datos del registro"], 400);
    }

    $conexion = obtenerConexion();
    asegurarEstructuraEmpleados($conexion);
    $conexion->begin_transaction();

    if ($empleadoId <= 0) {
        $stmtAsignacionEmpleado = $conexion->prepare("SELECT empleado_id FROM asignaciones_limpieza WHERE codigo_asignacion = ?");
        $stmtAsignacionEmpleado->bind_param("s", $codigoAsignacion);
        $stmtAsignacionEmpleado->execute();
        $asignacionEmpleado = $stmtAsignacionEmpleado->get_result()->fetch_assoc();
        $empleadoId = $asignacionEmpleado && $asignacionEmpleado["empleado_id"] ? (int)$asignacionEmpleado["empleado_id"] : 0;
    }

    // Guarda el registro en MySQL
    $sqlRegistro = "INSERT INTO registros_limpieza (codigo_asignacion, habitacion, empleado_id, empleado, fecha_registro, hora_registro, estado, observaciones)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmtRegistro = $conexion->prepare($sqlRegistro);
    $stmtRegistro->bind_param("ssisssss", $codigoAsignacion, $habitacion, $empleadoId, $empleado, $fecha, $hora, $estado, $observaciones);
    $stmtRegistro->execute();
    $idRegistro = $stmtRegistro->insert_id;

    // Actualiza el estado de la habitacion
    $sqlHabitacion = "UPDATE habitaciones SET estado = ? WHERE numero = ?";
    $stmtHabitacion = $conexion->prepare($sqlHabitacion);
    $stmtHabitacion->bind_param("ss", $estado, $habitacion);
    $stmtHabitacion->execute();

    // Actualiza el estado de la asignacion
    $sqlAsignacion = "UPDATE asignaciones_limpieza SET estado = ?, empleado_id = COALESCE(NULLIF(?, 0), empleado_id) WHERE codigo_asignacion = ?";
    $stmtAsignacion = $conexion->prepare($sqlAsignacion);
    $stmtAsignacion->bind_param("sis", $estado, $empleadoId, $codigoAsignacion);
    $stmtAsignacion->execute();

    $conexion->commit();
    intentarSincronizarMongoDesdeMySQL($conexion);
    responderJson([
        "ok" => true,
        "mensaje" => "Registro guardado",
        "idRegistro" => $idRegistro
    ]);
} catch (Throwable $e) {
    if (isset($conexion)) {
        $conexion->rollback();
    }
    manejarErrorServidor("No se pudo guardar el registro", $e);
}
?>
