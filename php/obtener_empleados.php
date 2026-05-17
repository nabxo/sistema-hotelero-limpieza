<?php
require_once __DIR__ . "/empleados_schema.php";

try {
    $conexion = obtenerConexion();
    asegurarEstructuraEmpleados($conexion);

    $estadisticasAsignaciones = [];
    $resultadoAsignaciones = $conexion->query("
        SELECT empleado_id, COUNT(*) AS total
        FROM asignaciones_limpieza
        WHERE empleado_id IS NOT NULL
        GROUP BY empleado_id
    ");
    while ($fila = $resultadoAsignaciones->fetch_assoc()) {
        $estadisticasAsignaciones[(int)$fila["empleado_id"]] = (int)$fila["total"];
    }

    $estadisticasRegistros = [];
    $resultadoRegistros = $conexion->query("
        SELECT empleado_id,
               COUNT(*) AS registradas,
               SUM(CASE WHEN estado = 'Limpia' THEN 1 ELSE 0 END) AS limpias,
               SUM(CASE WHEN estado = 'Sucia' THEN 1 ELSE 0 END) AS sucias,
               SUM(CASE WHEN estado = 'Mantenimiento' THEN 1 ELSE 0 END) AS mantenimiento
        FROM registros_limpieza
        WHERE empleado_id IS NOT NULL
        GROUP BY empleado_id
    ");
    while ($fila = $resultadoRegistros->fetch_assoc()) {
        $estadisticasRegistros[(int)$fila["empleado_id"]] = [
            "registradas" => (int)$fila["registradas"],
            "limpias" => (int)$fila["limpias"],
            "sucias" => (int)$fila["sucias"],
            "mantenimiento" => (int)$fila["mantenimiento"]
        ];
    }

    $empleados = [];
    $resultadoEmpleados = $conexion->query("
        SELECT id, codigo_empleado, nombre, apellido, telefono, direccion, fecha_ingreso, puesto,
               estado_laboral, fecha_salida, motivo_salida, notas_internas, foto
        FROM empleados_limpieza
        ORDER BY estado_laboral = 'Activo' DESC, nombre ASC, apellido ASC
    ");

    while ($fila = $resultadoEmpleados->fetch_assoc()) {
        $id = (int)$fila["id"];
        $asignadas = $estadisticasAsignaciones[$id] ?? 0;
        $registros = $estadisticasRegistros[$id] ?? [
            "registradas" => 0,
            "limpias" => 0,
            "sucias" => 0,
            "mantenimiento" => 0
        ];

        $cumplimientoBase = $asignadas > 0 ? ($registros["limpias"] / $asignadas) * 100 : 0;
        $cumplimiento = (int)max(0, min(100, round($cumplimientoBase)));

        $empleados[] = [
            "id" => $id,
            "codigo" => $fila["codigo_empleado"],
            "nombre" => $fila["nombre"],
            "apellido" => $fila["apellido"],
            "nombreCompleto" => trim($fila["nombre"] . " " . $fila["apellido"]),
            "telefono" => $fila["telefono"],
            "direccion" => $fila["direccion"],
            "fechaIngreso" => $fila["fecha_ingreso"],
            "puesto" => $fila["puesto"],
            "estadoLaboral" => $fila["estado_laboral"],
            "fechaSalida" => $fila["fecha_salida"],
            "motivoSalida" => $fila["motivo_salida"],
            "notasInternas" => $fila["notas_internas"],
            "foto" => $fila["foto"],
            "estadisticas" => [
                "asignadas" => $asignadas,
                "registradas" => $registros["registradas"],
                "cumplimiento" => $cumplimiento,
                "limpias" => $registros["limpias"],
                "sucias" => $registros["sucias"],
                "mantenimiento" => $registros["mantenimiento"]
            ]
        ];
    }

    responderJson([
        "ok" => true,
        "empleados" => $empleados
    ]);
} catch (Throwable $e) {
    manejarErrorServidor("No se pudieron cargar los empleados", $e);
}

