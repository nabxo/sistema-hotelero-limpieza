<?php
require_once __DIR__ . "/empleados_schema.php";

try {
    $conexion = obtenerConexion();
    asegurarEstructuraEmpleados($conexion);
    mongoPing();
    sincronizarMongoDesdeMySQL($conexion);

    responderJson([
        "ok" => true,
        "mensaje" => "MongoDB sincronizado correctamente",
        "base" => obtenerMongoDatabase(),
        "host" => "127.0.0.1:27017"
    ]);
} catch (Throwable $e) {
    manejarErrorServidor("No se pudo sincronizar MongoDB", $e);
}
?>
