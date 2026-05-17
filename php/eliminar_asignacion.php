<?php
require_once __DIR__ . "/funciones.php";
validarMetodoPost();

try {
    $datos = obtenerJsonEntrada();
    $codigo = texto($datos["id"] ?? "");

    if ($codigo === "") {
        responderJson(["ok" => false, "mensaje" => "Falta el identificador de la asignacion"], 400);
    }

    $conexion = obtenerConexion();
    $stmt = $conexion->prepare("DELETE FROM asignaciones_limpieza WHERE codigo_asignacion = ?");
    $stmt->bind_param("s", $codigo);
    $stmt->execute();

    intentarSincronizarMongoDesdeMySQL($conexion);
    responderJson(["ok" => true, "mensaje" => "Asignacion eliminada"]);
} catch (Throwable $e) {
    manejarErrorServidor("No se pudo eliminar la asignacion", $e);
}
?>
