<?php
require_once __DIR__ . "/funciones.php";
validarMetodoPost();

try {
    $datos = obtenerJsonEntrada();
    $inventario = $datos["inventario"] ?? [];

    $conexion = obtenerConexion();
    $conexion->begin_transaction();

    $sql = "INSERT INTO inventario (producto, cantidad_disponible)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE
                cantidad_disponible = VALUES(cantidad_disponible)";
    $stmt = $conexion->prepare($sql);

    foreach ($inventario as $item) {
        $producto = texto($item["producto"] ?? "");
        $cantidad = (int)($item["cantidad"] ?? 0);

        if ($producto === "") {
            continue;
        }

        $stmt->bind_param("si", $producto, $cantidad);
        $stmt->execute();
    }

    $conexion->commit();
    responderJson(["ok" => true, "mensaje" => "Inventario guardado"]);
} catch (Throwable $e) {
    if (isset($conexion)) {
        $conexion->rollback();
    }
    responderJson([
        "ok" => false,
        "mensaje" => "No se pudo guardar el inventario",
        "error" => $e->getMessage()
    ], 500);
}
?>
