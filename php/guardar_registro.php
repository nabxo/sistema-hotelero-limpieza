<?php
require_once __DIR__ . "/funciones.php";
validarMetodoPost();

try {
    // Toma los datos del registro
    $datos = obtenerJsonEntrada();

    $codigoAsignacion = texto($datos["asignacionId"] ?? "");
    $habitacion = texto($datos["habitacion"] ?? "");
    $empleado = texto($datos["empleado"] ?? "");
    $fecha = texto($datos["fecha"] ?? "");
    $hora = texto($datos["hora"] ?? "");
    $estado = texto($datos["estado"] ?? "");
    $observaciones = texto($datos["observaciones"] ?? "");

    if ($codigoAsignacion === "" || $habitacion === "" || $empleado === "" || $fecha === "" || $hora === "" || $estado === "") {
        responderJson(["ok" => false, "mensaje" => "Faltan datos del registro"], 400);
    }

    $conexion = obtenerConexion();
    $conexion->begin_transaction();

    // Guarda el registro en MySQL
    $sqlRegistro = "INSERT INTO registros_limpieza (codigo_asignacion, habitacion, empleado, fecha_registro, hora_registro, estado, observaciones)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmtRegistro = $conexion->prepare($sqlRegistro);
    $stmtRegistro->bind_param("sssssss", $codigoAsignacion, $habitacion, $empleado, $fecha, $hora, $estado, $observaciones);
    $stmtRegistro->execute();
    $idRegistro = $stmtRegistro->insert_id;

    // Actualiza el estado de la habitacion
    $sqlHabitacion = "UPDATE habitaciones SET estado = ? WHERE numero = ?";
    $stmtHabitacion = $conexion->prepare($sqlHabitacion);
    $stmtHabitacion->bind_param("ss", $estado, $habitacion);
    $stmtHabitacion->execute();

    // Actualiza el estado de la asignacion
    $sqlAsignacion = "UPDATE asignaciones_limpieza SET estado = ? WHERE codigo_asignacion = ?";
    $stmtAsignacion = $conexion->prepare($sqlAsignacion);
    $stmtAsignacion->bind_param("ss", $estado, $codigoAsignacion);
    $stmtAsignacion->execute();

    $conexion->commit();
    responderJson([
        "ok" => true,
        "mensaje" => "Registro guardado",
        "idRegistro" => $idRegistro
    ]);
} catch (Throwable $e) {
    if (isset($conexion)) {
        $conexion->rollback();
    }
    responderJson([
        "ok" => false,
        "mensaje" => "No se pudo guardar el registro",
        "error" => $e->getMessage()
    ], 500);
}
?>
