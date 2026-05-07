<?php
require_once __DIR__ . "/empleados_schema.php";

try {
    $conexion = obtenerConexion();
    asegurarEstructuraEmpleados($conexion);
    $registros = [];

    $sql = "SELECT id, codigo_asignacion, habitacion, empleado_id, empleado, fecha_registro, hora_registro, estado, observaciones
            FROM registros_limpieza
            ORDER BY fecha_registro DESC, hora_registro DESC, id DESC";
    $resultado = $conexion->query($sql);

    while ($fila = $resultado->fetch_assoc()) {
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

    responderJson($registros);
} catch (Throwable $e) {
    manejarErrorServidor("No se pudieron obtener los registros", $e);
}
?>
