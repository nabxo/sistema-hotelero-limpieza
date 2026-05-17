<?php

function intentarSincronizarMongoDesdeMySQL(mysqli $conexion): bool
{
    if (!class_exists('\MongoDB\Client')) {
        return false;
    }

    try {
        $cliente = new \MongoDB\Client("mongodb://127.0.0.1:27017");
        $base = $cliente->selectDatabase("sistema_hotelero_limpieza");

        $colecciones = [
            "habitaciones" => "SELECT numero, tipo, piso, estado FROM habitaciones ORDER BY numero ASC",
            "inventario" => "SELECT producto, cantidad_disponible FROM inventario ORDER BY producto ASC",
            "asignaciones_limpieza" => "SELECT codigo_asignacion, habitacion, empleado_id, empleado, fecha_asignacion, hora_asignacion, estado FROM asignaciones_limpieza ORDER BY id ASC",
            "registros_limpieza" => "SELECT id, codigo_asignacion, habitacion, empleado_id, empleado, fecha_registro, hora_registro, estado, observaciones FROM registros_limpieza ORDER BY id ASC",
            "empleados_limpieza" => "SELECT id, codigo_empleado, nombre, apellido, telefono, direccion, fecha_ingreso, puesto, estado_laboral, fecha_salida, motivo_salida, notas_internas, foto FROM empleados_limpieza ORDER BY id ASC"
        ];

        foreach ($colecciones as $nombre => $sql) {
            $documentos = [];
            $resultado = $conexion->query($sql);
            while ($fila = $resultado->fetch_assoc()) {
                $documentos[] = $fila;
            }

            $coleccion = $base->selectCollection($nombre);
            $coleccion->deleteMany([]);
            if (!empty($documentos)) {
                $coleccion->insertMany($documentos);
            }
        }

        return true;
    } catch (Throwable $error) {
        error_log("No se pudo sincronizar con MongoDB: " . $error->getMessage());
        return false;
    }
}

