<?php

function obtenerMongoManager()
{
    static $manager = null;

    if ($manager instanceof MongoDB\Driver\Manager) {
        return $manager;
    }

    $manager = new MongoDB\Driver\Manager("mongodb://127.0.0.1:27017");
    return $manager;
}

function obtenerMongoDatabase()
{
    return "sistema_hotelero_limpieza";
}

function obtenerMongoNamespace($coleccion)
{
    return obtenerMongoDatabase() . "." . $coleccion;
}

function mongoNormalizarValor($valor)
{
    if (is_array($valor)) {
        $normalizado = [];
        foreach ($valor as $clave => $item) {
            $normalizado[$clave] = mongoNormalizarValor($item);
        }
        return $normalizado;
    }

    if ($valor instanceof stdClass) {
        return mongoNormalizarValor((array)$valor);
    }

    return $valor;
}

function mongoPing()
{
    $manager = obtenerMongoManager();
    $comando = new MongoDB\Driver\Command(["ping" => 1]);
    $manager->executeCommand("admin", $comando);
    return true;
}

function mongoUpsertDocumento($coleccion, array $filtro, array $documento)
{
    $manager = obtenerMongoManager();
    $bulk = new MongoDB\Driver\BulkWrite();
    $bulk->update(
        mongoNormalizarValor($filtro),
        ['$set' => mongoNormalizarValor($documento)],
        ['multi' => false, 'upsert' => true]
    );
    $manager->executeBulkWrite(obtenerMongoNamespace($coleccion), $bulk);
}

function mongoEliminarDocumento($coleccion, array $filtro)
{
    $manager = obtenerMongoManager();
    $bulk = new MongoDB\Driver\BulkWrite();
    $bulk->delete(mongoNormalizarValor($filtro), ['limit' => 1]);
    $manager->executeBulkWrite(obtenerMongoNamespace($coleccion), $bulk);
}

function mongoReemplazarColeccion($coleccion, array $documentos)
{
    $manager = obtenerMongoManager();
    $bulk = new MongoDB\Driver\BulkWrite();
    $bulk->delete([], ['limit' => 0]);

    foreach ($documentos as $documento) {
        $bulk->insert(mongoNormalizarValor($documento));
    }

    $manager->executeBulkWrite(obtenerMongoNamespace($coleccion), $bulk);
}

function sincronizarMongoDesdeMySQL($conexion)
{
    $habitaciones = [];
    $resultadoHabitaciones = $conexion->query("SELECT numero, tipo, piso, estado FROM habitaciones ORDER BY numero ASC");
    while ($fila = $resultadoHabitaciones->fetch_assoc()) {
        $habitaciones[] = [
            "numero" => $fila["numero"],
            "tipo" => $fila["tipo"],
            "piso" => (string)$fila["piso"],
            "estado" => $fila["estado"]
        ];
    }

    $inventario = [];
    $resultadoInventario = $conexion->query("SELECT producto, cantidad_disponible AS cantidad FROM inventario ORDER BY producto ASC");
    while ($fila = $resultadoInventario->fetch_assoc()) {
        $inventario[] = [
            "producto" => $fila["producto"],
            "cantidad" => (int)$fila["cantidad"]
        ];
    }

    $asignaciones = [];
    $resultadoAsignaciones = $conexion->query("SELECT codigo_asignacion, habitacion, empleado_id, empleado, fecha_asignacion, hora_asignacion, estado FROM asignaciones_limpieza ORDER BY fecha_asignacion DESC, hora_asignacion DESC, id DESC");
    while ($fila = $resultadoAsignaciones->fetch_assoc()) {
        $asignaciones[] = [
            "codigo_asignacion" => $fila["codigo_asignacion"],
            "habitacion" => $fila["habitacion"],
            "empleado_id" => $fila["empleado_id"] ? (int)$fila["empleado_id"] : null,
            "empleado" => $fila["empleado"],
            "fecha_asignacion" => $fila["fecha_asignacion"],
            "hora_asignacion" => substr($fila["hora_asignacion"], 0, 5),
            "estado" => $fila["estado"]
        ];
    }

    $registros = [];
    $resultadoRegistros = $conexion->query("SELECT id, codigo_asignacion, habitacion, empleado_id, empleado, fecha_registro, hora_registro, estado, observaciones FROM registros_limpieza ORDER BY fecha_registro DESC, hora_registro DESC, id DESC");
    while ($fila = $resultadoRegistros->fetch_assoc()) {
        $registros[] = [
            "id_registro" => (int)$fila["id"],
            "codigo_asignacion" => $fila["codigo_asignacion"],
            "habitacion" => $fila["habitacion"],
            "empleado_id" => $fila["empleado_id"] ? (int)$fila["empleado_id"] : null,
            "empleado" => $fila["empleado"],
            "fecha_registro" => $fila["fecha_registro"],
            "hora_registro" => substr($fila["hora_registro"], 0, 5),
            "estado" => $fila["estado"],
            "observaciones" => $fila["observaciones"]
        ];
    }

    $empleados = [];
    $resultadoEmpleados = $conexion->query("SELECT id, codigo_empleado, nombre, apellido, telefono, direccion, fecha_ingreso, puesto, estado_laboral, fecha_salida, motivo_salida, notas_internas, foto FROM empleados_limpieza ORDER BY id ASC");
    while ($fila = $resultadoEmpleados->fetch_assoc()) {
        $empleados[] = [
            "id_empleado" => (int)$fila["id"],
            "codigo_empleado" => $fila["codigo_empleado"],
            "nombre" => $fila["nombre"],
            "apellido" => $fila["apellido"],
            "nombre_completo" => trim($fila["nombre"] . " " . $fila["apellido"]),
            "telefono" => $fila["telefono"],
            "direccion" => $fila["direccion"],
            "fecha_ingreso" => $fila["fecha_ingreso"],
            "puesto" => $fila["puesto"],
            "estado_laboral" => $fila["estado_laboral"],
            "fecha_salida" => $fila["fecha_salida"],
            "motivo_salida" => $fila["motivo_salida"],
            "notas_internas" => $fila["notas_internas"],
            "foto" => $fila["foto"]
        ];
    }

    mongoReemplazarColeccion("habitaciones", $habitaciones);
    mongoReemplazarColeccion("inventario", $inventario);
    mongoReemplazarColeccion("asignaciones_limpieza", $asignaciones);
    mongoReemplazarColeccion("registros_limpieza", $registros);
    mongoReemplazarColeccion("empleados_limpieza", $empleados);
}

function intentarSincronizarMongoDesdeMySQL($conexion)
{
    try {
        mongoPing();
        sincronizarMongoDesdeMySQL($conexion);
        return true;
    } catch (Throwable $e) {
        error_log("MongoDB sync error: " . $e->getMessage());
        return false;
    }
}

function intentarMongoUpsertDocumento($coleccion, array $filtro, array $documento)
{
    try {
        mongoPing();
        mongoUpsertDocumento($coleccion, $filtro, $documento);
        return true;
    } catch (Throwable $e) {
        error_log("MongoDB upsert error: " . $e->getMessage());
        return false;
    }
}

function intentarMongoEliminarDocumento($coleccion, array $filtro)
{
    try {
        mongoPing();
        mongoEliminarDocumento($coleccion, $filtro);
        return true;
    } catch (Throwable $e) {
        error_log("MongoDB delete error: " . $e->getMessage());
        return false;
    }
}
?>
