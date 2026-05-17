<?php
require_once __DIR__ . "/empleados_schema.php";
validarMetodoPost();

try {
    // Toma los datos que vienen del formulario
    $datos = obtenerJsonEntrada();

    $codigo = texto($datos["id"] ?? "");
    $habitacion = texto($datos["habitacion"] ?? "");
    $empleadoId = (int)($datos["empleadoId"] ?? 0);
    $empleado = texto($datos["empleado"] ?? "");
    $fecha = texto($datos["fechaISO"] ?? "");
    $hora = texto($datos["hora24"] ?? "");
    $estado = texto($datos["estado"] ?? "Sucia");

    if ($codigo === "" || $habitacion === "" || $empleadoId <= 0 || $empleado === "" || $fecha === "" || $hora === "") {
        responderJson(["ok" => false, "mensaje" => "Faltan datos de la asignacion"], 400);
    }

    $conexion = obtenerConexion();
    asegurarEstructuraEmpleados($conexion);
    $conexion->begin_transaction();

    $stmtEmpleado = $conexion->prepare("SELECT CONCAT(nombre, ' ', apellido) AS nombre_completo FROM empleados_limpieza WHERE id = ? AND estado_laboral = 'Activo'");
    $stmtEmpleado->bind_param("i", $empleadoId);
    $stmtEmpleado->execute();
    $empleadoActivo = $stmtEmpleado->get_result()->fetch_assoc();

    if (!$empleadoActivo) {
        responderJson(["ok" => false, "mensaje" => "Selecciona un empleado activo de limpieza"], 400);
    }

    $empleado = $empleadoActivo["nombre_completo"];

    // Guarda la asignacion en MySQL
    $sqlAsignacion = "INSERT INTO asignaciones_limpieza (codigo_asignacion, habitacion, empleado_id, empleado, fecha_asignacion, hora_asignacion, estado)
                      VALUES (?, ?, ?, ?, ?, ?, ?)
                      ON DUPLICATE KEY UPDATE
                        habitacion = VALUES(habitacion),
                        empleado_id = VALUES(empleado_id),
                        empleado = VALUES(empleado),
                        fecha_asignacion = VALUES(fecha_asignacion),
                        hora_asignacion = VALUES(hora_asignacion),
                        estado = VALUES(estado)";
    $stmtAsignacion = $conexion->prepare($sqlAsignacion);
    $stmtAsignacion->bind_param("ssissss", $codigo, $habitacion, $empleadoId, $empleado, $fecha, $hora, $estado);
    $stmtAsignacion->execute();

    // Cambia la habitacion a sucia
    $sqlHabitacion = "UPDATE habitaciones
                      SET estado = 'Sucia'
                      WHERE numero = ? AND estado <> 'Mantenimiento'";
    $stmtHabitacion = $conexion->prepare($sqlHabitacion);
    $stmtHabitacion->bind_param("s", $habitacion);
    $stmtHabitacion->execute();

    $conexion->commit();
    intentarSincronizarMongoDesdeMySQL($conexion);
    responderJson(["ok" => true, "mensaje" => "Asignacion guardada"]);
} catch (Throwable $e) {
    if (isset($conexion)) {
        $conexion->rollback();
    }
    manejarErrorServidor("No se pudo guardar la asignacion", $e);
}
?>
