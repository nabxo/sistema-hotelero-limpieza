<?php
require_once __DIR__ . "/funciones.php";
validarMetodoPost();

try {
    $datos = obtenerJsonEntrada();
    $idRegistro = (int)($datos["idRegistro"] ?? 0);

    if ($idRegistro <= 0) {
        responderJson(["ok" => false, "mensaje" => "Falta el identificador del registro"], 400);
    }

    $conexion = obtenerConexion();
    $stmt = $conexion->prepare("DELETE FROM registros_limpieza WHERE id = ?");
    $stmt->bind_param("i", $idRegistro);
    $stmt->execute();

    intentarSincronizarMongoDesdeMySQL($conexion);
    responderJson(["ok" => true, "mensaje" => "Registro eliminado"]);
} catch (Throwable $e) {
    manejarErrorServidor("No se pudo eliminar el registro", $e);
}
?>
