<?php
require_once __DIR__ . "/empleados_schema.php";
validarMetodoPost();

try {
    // Toma los datos actuales del navegador
    $datos = obtenerJsonEntrada();

    $habitaciones = $datos["habitaciones"] ?? [];
    $inventario = $datos["inventario"] ?? [];
    $asignaciones = $datos["asignaciones"] ?? [];
    $registros = $datos["registros"] ?? [];

    $conexion = obtenerConexion();
    asegurarEstructuraEmpleados($conexion);
    $conexion->begin_transaction();

    // Solo copia si la base esta vacia
    $tablasVacias = [
        "habitaciones" => (int)$conexion->query("SELECT COUNT(*) AS total FROM habitaciones")->fetch_assoc()["total"] === 0,
        "inventario" => (int)$conexion->query("SELECT COUNT(*) AS total FROM inventario")->fetch_assoc()["total"] === 0,
        "asignaciones" => (int)$conexion->query("SELECT COUNT(*) AS total FROM asignaciones_limpieza")->fetch_assoc()["total"] === 0,
        "registros" => (int)$conexion->query("SELECT COUNT(*) AS total FROM registros_limpieza")->fetch_assoc()["total"] === 0
    ];

    // Copia habitaciones
    if ($tablasVacias["habitaciones"] && !empty($habitaciones)) {
        $stmt = $conexion->prepare("INSERT INTO habitaciones (numero, tipo, piso, estado) VALUES (?, ?, ?, ?)");
        foreach ($habitaciones as $habitacion) {
            $numero = texto($habitacion["numero"] ?? "");
            $tipo = texto($habitacion["tipo"] ?? "");
            $piso = (int)($habitacion["piso"] ?? 0);
            $estado = texto($habitacion["estado"] ?? "");
            if ($numero === "" || $tipo === "" || $estado === "") {
                continue;
            }
            $stmt->bind_param("ssis", $numero, $tipo, $piso, $estado);
            $stmt->execute();
        }
    }

    // Copia inventario
    if ($tablasVacias["inventario"] && !empty($inventario)) {
        $stmt = $conexion->prepare("INSERT INTO inventario (producto, cantidad_disponible) VALUES (?, ?)");
        foreach ($inventario as $item) {
            $producto = texto($item["producto"] ?? "");
            $cantidad = (int)($item["cantidad"] ?? 0);
            if ($producto === "") {
                continue;
            }
            $stmt->bind_param("si", $producto, $cantidad);
            $stmt->execute();
        }
    }

    // Copia asignaciones
    if ($tablasVacias["asignaciones"] && !empty($asignaciones)) {
        $stmt = $conexion->prepare("INSERT INTO asignaciones_limpieza (codigo_asignacion, habitacion, empleado_id, empleado, fecha_asignacion, hora_asignacion, estado) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($asignaciones as $asignacion) {
            $codigo = texto($asignacion["id"] ?? "");
            $habitacion = texto($asignacion["habitacion"] ?? "");
            $empleadoId = (int)($asignacion["empleadoId"] ?? 0);
            $empleado = texto($asignacion["empleado"] ?? "");
            $fecha = texto($asignacion["fechaISO"] ?? "");
            $hora = texto($asignacion["hora24"] ?? "");
            $estado = texto($asignacion["estado"] ?? "Sucia");
            if ($codigo === "" || $habitacion === "" || $empleado === "" || $fecha === "" || $hora === "") {
                continue;
            }
            $empleadoIdParam = $empleadoId > 0 ? $empleadoId : null;
            $stmt->bind_param("ssissss", $codigo, $habitacion, $empleadoIdParam, $empleado, $fecha, $hora, $estado);
            $stmt->execute();
        }
    }

    // Copia registros
    if ($tablasVacias["registros"] && !empty($registros)) {
        $stmt = $conexion->prepare("INSERT INTO registros_limpieza (codigo_asignacion, habitacion, empleado_id, empleado, fecha_registro, hora_registro, estado, observaciones) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($registros as $registro) {
            $codigoAsignacion = texto($registro["asignacionId"] ?? "");
            $habitacion = texto($registro["habitacion"] ?? "");
            $empleadoId = (int)($registro["empleadoId"] ?? 0);
            $empleado = texto($registro["empleado"] ?? "");
            $fecha = texto($registro["fecha"] ?? "");
            $hora = texto($registro["hora"] ?? "");
            $estado = texto($registro["estado"] ?? "");
            $observaciones = texto($registro["observaciones"] ?? "");
            if ($codigoAsignacion === "" || $habitacion === "" || $empleado === "" || $fecha === "" || $hora === "" || $estado === "") {
                continue;
            }
            $empleadoIdParam = $empleadoId > 0 ? $empleadoId : null;
            $stmt->bind_param("ssisssss", $codigoAsignacion, $habitacion, $empleadoIdParam, $empleado, $fecha, $hora, $estado, $observaciones);
            $stmt->execute();
        }
    }

    $conexion->commit();
    intentarSincronizarMongoDesdeMySQL($conexion);
    responderJson(["ok" => true, "mensaje" => "Sincronizacion inicial completada"]);
} catch (Throwable $e) {
    if (isset($conexion)) {
        $conexion->rollback();
    }
    manejarErrorServidor("No se pudo sincronizar la informacion inicial", $e);
}
?>
