<?php
require_once __DIR__ . "/empleados_schema.php";

function obtenerEmpleadosDesdeMongo()
{
    mongoPing();
    $manager = obtenerMongoManager();

    $cursorEmpleados = $manager->executeQuery(
        obtenerMongoNamespace("empleados_limpieza"),
        new MongoDB\Driver\Query([], ["sort" => ["estado_laboral" => 1, "nombre" => 1, "apellido" => 1]])
    );

    $cursorAsignaciones = $manager->executeQuery(
        obtenerMongoNamespace("asignaciones_limpieza"),
        new MongoDB\Driver\Query([])
    );

    $cursorRegistros = $manager->executeQuery(
        obtenerMongoNamespace("registros_limpieza"),
        new MongoDB\Driver\Query([])
    );

    $estadisticas = [];

    foreach ($cursorAsignaciones as $asignacion) {
        $empleadoId = isset($asignacion->empleado_id) ? (int)$asignacion->empleado_id : 0;
        if ($empleadoId <= 0) {
            continue;
        }

        if (!isset($estadisticas[$empleadoId])) {
            $estadisticas[$empleadoId] = [
                "asignadas" => 0,
                "registradas" => 0,
                "limpias" => 0,
                "sucias" => 0,
                "mantenimiento" => 0
            ];
        }

        $estadisticas[$empleadoId]["asignadas"]++;
    }

    foreach ($cursorRegistros as $registro) {
        $empleadoId = isset($registro->empleado_id) ? (int)$registro->empleado_id : 0;
        if ($empleadoId <= 0) {
            continue;
        }

        if (!isset($estadisticas[$empleadoId])) {
            $estadisticas[$empleadoId] = [
                "asignadas" => 0,
                "registradas" => 0,
                "limpias" => 0,
                "sucias" => 0,
                "mantenimiento" => 0
            ];
        }

        $estadisticas[$empleadoId]["registradas"]++;

        $estado = $registro->estado ?? "";
        if ($estado === "Limpia") {
            $estadisticas[$empleadoId]["limpias"]++;
        } elseif ($estado === "Sucia") {
            $estadisticas[$empleadoId]["sucias"]++;
        } elseif ($estado === "Mantenimiento") {
            $estadisticas[$empleadoId]["mantenimiento"]++;
        }
    }

    $empleados = [];

    foreach ($cursorEmpleados as $fila) {
        $id = isset($fila->id_empleado) ? (int)$fila->id_empleado : 0;
        $stats = $estadisticas[$id] ?? [
            "asignadas" => 0,
            "registradas" => 0,
            "limpias" => 0,
            "sucias" => 0,
            "mantenimiento" => 0
        ];
        $cumplimientoBase = $stats["asignadas"] > 0 ? ($stats["registradas"] / $stats["asignadas"]) * 100 : 0;

        $empleados[] = [
            "id" => $id,
            "codigo" => $fila->codigo_empleado ?? "",
            "nombre" => $fila->nombre ?? "",
            "apellido" => $fila->apellido ?? "",
            "nombreCompleto" => $fila->nombre_completo ?? trim(($fila->nombre ?? "") . " " . ($fila->apellido ?? "")),
            "telefono" => $fila->telefono ?? "",
            "direccion" => $fila->direccion ?? "",
            "fechaIngreso" => $fila->fecha_ingreso ?? "",
            "puesto" => $fila->puesto ?? "Auxiliar de Limpieza",
            "estadoLaboral" => $fila->estado_laboral ?? "Activo",
            "fechaSalida" => $fila->fecha_salida ?? null,
            "motivoSalida" => $fila->motivo_salida ?? null,
            "notasInternas" => $fila->notas_internas ?? "",
            "foto" => $fila->foto ?? "",
            "estadisticas" => [
                "asignadas" => $stats["asignadas"],
                "registradas" => $stats["registradas"],
                "limpias" => $stats["limpias"],
                "sucias" => $stats["sucias"],
                "mantenimiento" => $stats["mantenimiento"],
                "cumplimiento" => min(100, (int)round($cumplimientoBase))
            ]
        ];
    }

    usort($empleados, function ($a, $b) {
        if ($a["estadoLaboral"] === $b["estadoLaboral"]) {
            return strcmp($a["nombreCompleto"], $b["nombreCompleto"]);
        }

        return $a["estadoLaboral"] === "Activo" ? -1 : 1;
    });

    return $empleados;
}

try {
    $conexion = obtenerConexion();
    asegurarEstructuraEmpleados($conexion);

    $sql = "SELECT
                e.id,
                e.codigo_empleado,
                e.nombre,
                e.apellido,
                e.telefono,
                e.direccion,
                e.fecha_ingreso,
                e.puesto,
                e.estado_laboral,
                e.fecha_salida,
                e.motivo_salida,
                e.notas_internas,
                e.foto,
                COALESCE(a.tareas_asignadas, 0) AS tareas_asignadas,
                COALESCE(r.tareas_registradas, 0) AS tareas_registradas,
                COALESCE(r.tareas_limpias, 0) AS tareas_limpias,
                COALESCE(r.tareas_sucias, 0) AS tareas_sucias,
                COALESCE(r.tareas_mantenimiento, 0) AS tareas_mantenimiento
            FROM empleados_limpieza e
            LEFT JOIN (
                SELECT empleado_id, COUNT(*) AS tareas_asignadas
                FROM asignaciones_limpieza
                WHERE empleado_id IS NOT NULL
                GROUP BY empleado_id
            ) a ON a.empleado_id = e.id
            LEFT JOIN (
                SELECT
                    empleado_id,
                    COUNT(*) AS tareas_registradas,
                    SUM(CASE WHEN estado = 'Limpia' THEN 1 ELSE 0 END) AS tareas_limpias,
                    SUM(CASE WHEN estado = 'Sucia' THEN 1 ELSE 0 END) AS tareas_sucias,
                    SUM(CASE WHEN estado = 'Mantenimiento' THEN 1 ELSE 0 END) AS tareas_mantenimiento
                FROM registros_limpieza
                WHERE empleado_id IS NOT NULL
                GROUP BY empleado_id
            ) r ON r.empleado_id = e.id
            ORDER BY e.estado_laboral = 'Activo' DESC, e.nombre ASC, e.apellido ASC";

    $resultado = $conexion->query($sql);
    $empleados = [];

    while ($fila = $resultado->fetch_assoc()) {
        $asignadas = (int)$fila["tareas_asignadas"];
        $registradas = (int)$fila["tareas_registradas"];
        $cumplimientoBase = $asignadas > 0 ? ($registradas / $asignadas) * 100 : 0;

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
            "fechaSalida" => $fila["fecha_salida"],
            "motivoSalida" => $fila["motivo_salida"],
            "notasInternas" => $fila["notas_internas"],
            "foto" => $fila["foto"],
            "estadisticas" => [
                "asignadas" => $asignadas,
                "registradas" => $registradas,
                "limpias" => (int)$fila["tareas_limpias"],
                "sucias" => (int)$fila["tareas_sucias"],
                "mantenimiento" => (int)$fila["tareas_mantenimiento"],
                "cumplimiento" => min(100, (int)round($cumplimientoBase))
            ]
        ];
    }

    responderJson([
        "ok" => true,
        "empleados" => $empleados
    ]);
} catch (Throwable $e) {
    try {
        $empleados = obtenerEmpleadosDesdeMongo();
        responderJson([
            "ok" => true,
            "empleados" => $empleados,
            "origen" => "mongodb"
        ]);
    } catch (Throwable $mongoError) {
        error_log("Error MongoDB empleados: " . $mongoError->getMessage());
        manejarErrorServidor("No se pudieron obtener los empleados", $e);
    }
}
?>
