<?php
require_once __DIR__ . "/funciones.php";

try {
    $conexion = obtenerConexion();
    $asignaciones = [];

    $sql = "SELECT codigo_asignacion, habitacion, empleado, fecha_asignacion, hora_asignacion, estado
            FROM asignaciones_limpieza
            ORDER BY fecha_asignacion DESC, hora_asignacion DESC, id DESC";
    $resultado = $conexion->query($sql);

    while ($fila = $resultado->fetch_assoc()) {
        $asignaciones[] = [
            "id" => $fila["codigo_asignacion"],
            "habitacion" => $fila["habitacion"],
            "empleado" => $fila["empleado"],
            "fechaISO" => $fila["fecha_asignacion"],
            "hora24" => substr($fila["hora_asignacion"], 0, 5),
            "estado" => $fila["estado"]
        ];
    }

    responderJson($asignaciones);
} catch (Throwable $e) {
    responderJson([
        "ok" => false,
        "mensaje" => "No se pudieron obtener las asignaciones",
        "error" => $e->getMessage()
    ], 500);
}
?>
