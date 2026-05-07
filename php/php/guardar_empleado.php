<?php
require_once __DIR__ . "/empleados_schema.php";
validarMetodoPost();

function generarCodigoEmpleado($conexion)
{
    $resultado = $conexion->query("SELECT COALESCE(MAX(id), 0) + 1 AS siguiente FROM empleados_limpieza");
    $siguiente = (int)$resultado->fetch_assoc()["siguiente"];
    return "EMP-" . str_pad((string)$siguiente, 3, "0", STR_PAD_LEFT);
}

function guardarFotoEmpleado($codigo, $fotoActual)
{
    if (!isset($_FILES["foto"]) || $_FILES["foto"]["error"] !== UPLOAD_ERR_OK) {
        return $fotoActual;
    }

    $tamanoMaximo = 5 * 1024 * 1024;
    if (($_FILES["foto"]["size"] ?? 0) > $tamanoMaximo) {
        responderJson(["ok" => false, "mensaje" => "La foto no puede superar los 5 MB"], 400);
    }

    $permitidos = [
        "image/jpeg" => "jpg",
        "image/png" => "png",
        "image/webp" => "webp"
    ];

    $tipo = mime_content_type($_FILES["foto"]["tmp_name"]);
    if (!isset($permitidos[$tipo])) {
        responderJson(["ok" => false, "mensaje" => "La foto debe ser JPG, PNG o WEBP"], 400);
    }

    $carpeta = dirname(__DIR__) . DIRECTORY_SEPARATOR . "imagenes_empleados";
    if (!is_dir($carpeta)) {
        mkdir($carpeta, 0775, true);
    }

    $archivo = strtolower($codigo) . "." . $permitidos[$tipo];
    $destino = $carpeta . DIRECTORY_SEPARATOR . $archivo;

    if (!move_uploaded_file($_FILES["foto"]["tmp_name"], $destino)) {
        responderJson(["ok" => false, "mensaje" => "No se pudo guardar la foto"], 500);
    }

    return "imagenes_empleados/" . $archivo;
}

try {
    $conexion = obtenerConexion();
    asegurarEstructuraEmpleados($conexion);

    $id = (int)($_POST["id"] ?? 0);
    $nombre = texto($_POST["nombre"] ?? "");
    $apellido = texto($_POST["apellido"] ?? "");
    $telefono = texto($_POST["telefono"] ?? "");
    $direccion = texto($_POST["direccion"] ?? "");
    $fechaIngreso = texto($_POST["fechaIngreso"] ?? "");
    $estadoLaboral = texto($_POST["estadoLaboral"] ?? "Activo");
    $fechaSalida = texto($_POST["fechaSalida"] ?? "");
    $motivoSalida = texto($_POST["motivoSalida"] ?? "");
    $notasInternas = texto($_POST["notasInternas"] ?? "");
    $puesto = "Auxiliar de Limpieza";

    if ($nombre === "" || $apellido === "" || $telefono === "" || $direccion === "" || $fechaIngreso === "" || $estadoLaboral === "") {
        responderJson(["ok" => false, "mensaje" => "Completa los datos obligatorios del empleado"], 400);
    }

    if (!in_array($estadoLaboral, ["Activo", "Inactivo", "Vacaciones"], true)) {
        responderJson(["ok" => false, "mensaje" => "Estado laboral no valido"], 400);
    }

    if ($estadoLaboral === "Inactivo") {
        if ($fechaSalida === "" || $motivoSalida === "") {
            responderJson(["ok" => false, "mensaje" => "Completa la fecha y el motivo de salida para dar de baja al empleado"], 400);
        }
    } else {
        $fechaSalida = null;
        $motivoSalida = null;
    }

    if ($id > 0) {
        $stmtActual = $conexion->prepare("SELECT codigo_empleado, foto FROM empleados_limpieza WHERE id = ?");
        $stmtActual->bind_param("i", $id);
        $stmtActual->execute();
        $actual = $stmtActual->get_result()->fetch_assoc();
        if (!$actual) {
            responderJson(["ok" => false, "mensaje" => "Empleado no encontrado"], 404);
        }

        $codigo = $actual["codigo_empleado"];
        $foto = guardarFotoEmpleado($codigo, $actual["foto"]);

        $sql = "UPDATE empleados_limpieza
                SET nombre = ?, apellido = ?, telefono = ?, direccion = ?, fecha_ingreso = ?,
                    puesto = ?, estado_laboral = ?, fecha_salida = ?, motivo_salida = ?, notas_internas = ?, foto = ?
                WHERE id = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("sssssssssssi", $nombre, $apellido, $telefono, $direccion, $fechaIngreso, $puesto, $estadoLaboral, $fechaSalida, $motivoSalida, $notasInternas, $foto, $id);
        $stmt->execute();
    } else {
        $codigo = generarCodigoEmpleado($conexion);
        $foto = guardarFotoEmpleado($codigo, "");

        $sql = "INSERT INTO empleados_limpieza
                (codigo_empleado, nombre, apellido, telefono, direccion, fecha_ingreso, puesto, estado_laboral, fecha_salida, motivo_salida, notas_internas, foto)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("ssssssssssss", $codigo, $nombre, $apellido, $telefono, $direccion, $fechaIngreso, $puesto, $estadoLaboral, $fechaSalida, $motivoSalida, $notasInternas, $foto);
        $stmt->execute();
        $id = $stmt->insert_id;
    }

    intentarSincronizarMongoDesdeMySQL($conexion);
    responderJson([
        "ok" => true,
        "mensaje" => "Empleado guardado",
        "id" => $id,
        "codigo" => $codigo
    ]);
} catch (Throwable $e) {
    manejarErrorServidor("No se pudo guardar el empleado", $e);
}
?>
