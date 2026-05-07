<?php
require_once __DIR__ . "/funciones.php";
validarMetodoPost();

try {
    $datos = obtenerJsonEntrada();
    $habitaciones = $datos["habitaciones"] ?? [];

    $conexion = obtenerConexion();
    $conexion->begin_transaction();

    $sql = "INSERT INTO habitaciones (numero, tipo, piso, estado)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                tipo = VALUES(tipo),
                piso = VALUES(piso),
                estado = VALUES(estado)";
    $stmt = $conexion->prepare($sql);

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

    $conexion->commit();
    intentarSincronizarMongoDesdeMySQL($conexion);
    responderJson(["ok" => true, "mensaje" => "Habitaciones guardadas"]);
} catch (Throwable $e) {
    if (isset($conexion)) {
        $conexion->rollback();
    }
    manejarErrorServidor("No se pudieron guardar las habitaciones", $e);
}
?>
