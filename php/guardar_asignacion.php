<?php
require_once __DIR__ . "/funciones.php";
validarMetodoPost();

try {
    // Toma los datos que vienen del formulario
    $datos = obtenerJsonEntrada();

    $codigo = texto($datos["id"] ?? "");
    $habitacion = texto($datos["habitacion"] ?? "");
    $empleado = texto($datos["empleado"] ?? "");
    $fecha = texto($datos["fechaISO"] ?? "");
    $hora = texto($datos["hora24"] ?? "");
    $estado = texto($datos["estado"] ?? "Sucia");

    if ($codigo === "" || $habitacion === "" || $empleado === "" || $fecha === "" || $hora === "") {
        responderJson(["ok" => false, "mensaje" => "Faltan datos de la asignacion"], 400);
    }

    $conexion = obtenerConexion();
    $conexion->begin_transaction();

    // Guarda la asignacion en MySQL
    $sqlAsignacion = "INSERT INTO asignaciones_limpieza (codigo_asignacion, habitacion, empleado, fecha_asignacion, hora_asignacion, estado)
                      VALUES (?, ?, ?, ?, ?, ?)
                      ON DUPLICATE KEY UPDATE
                        habitacion = VALUES(habitacion),
                        empleado = VALUES(empleado),
                        fecha_asignacion = VALUES(fecha_asignacion),
                        hora_asignacion = VALUES(hora_asignacion),
                        estado = VALUES(estado)";
    $stmtAsignacion = $conexion->prepare($sqlAsignacion);
    $stmtAsignacion->bind_param("ssssss", $codigo, $habitacion, $empleado, $fecha, $hora, $estado);
    $stmtAsignacion->execute();

    // Cambia la habitacion a sucia
    $sqlHabitacion = "UPDATE habitaciones
                      SET estado = 'Sucia'
                      WHERE numero = ? AND estado <> 'Mantenimiento'";
    $stmtHabitacion = $conexion->prepare($sqlHabitacion);
    $stmtHabitacion->bind_param("s", $habitacion);
    $stmtHabitacion->execute();

    $conexion->commit();
    responderJson(["ok" => true, "mensaje" => "Asignacion guardada"]);
} catch (Throwable $e) {
    if (isset($conexion)) {
        $conexion->rollback();
    }
    responderJson([
        "ok" => false,
        "mensaje" => "No se pudo guardar la asignacion",
        "error" => $e->getMessage()
    ], 500);
}
?>
