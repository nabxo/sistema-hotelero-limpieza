<?php
require_once __DIR__ . "/empleados_schema.php";

try {
    $conexion = obtenerConexion();
    asegurarEstructuraEmpleados($conexion);
    $asignaciones = [];

    $sql = "SELECT codigo_asignacion, habitacion, empleado_id, empleado, fecha_asignacion, hora_asignacion, estado
            FROM asignaciones_limpieza
            ORDER BY fecha_asignacion DESC, hora_asignacion DESC, id DESC";
    $resultado = $conexion->query($sql);

    while ($fila = $resultado->fetch_assoc()) {
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

    responderJson($asignaciones);
} catch (Throwable $e) {
    manejarErrorServidor("No se pudieron obtener las asignaciones", $e);
}
?>
