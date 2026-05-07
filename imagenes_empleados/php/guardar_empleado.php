<?php
require_once __DIR__ . "/empleados_schema.php";
validarMetodoPost();

function generarCodigoEmpleado(mysqli $conexion, int $id): string
{
    return "EMP-" . str_pad((string)$id, 3, "0", STR_PAD_LEFT);
}

function procesarFotoEmpleado(?array $archivo, string $codigoEmpleado, ?string $fotoActual = null): ?string
{
    if (!$archivo || ($archivo["error"] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return $fotoActual;
    }

    if (($archivo["error"] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException("No se pudo subir la foto del empleado.");
    }

    if (($archivo["size"] ?? 0) > 5 * 1024 * 1024) {
        throw new RuntimeException("La foto supera el limite permitido de 5 MB.");
    }

    $tmp = $archivo["tmp_name"] ?? "";
    $nombreOriginal = $archivo["name"] ?? "";
    $extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
    $permitidas = ["jpg", "jpeg", "png", "webp"];

    if (!in_array($extension, $permitidas, true)) {
        throw new RuntimeException("La foto debe ser JPG, PNG o WEBP.");
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($tmp);
    $mimesPermitidos = [
        "jpg" => "image/jpeg",
        "jpeg" => "image/jpeg",
        "png" => "image/png",
        "webp" => "image/webp"
    ];

    if (($mimesPermitidos[$extension] ?? "") !== $mime) {
        throw new RuntimeException("El archivo de la foto no es valido.");
    }

    $directorio = dirname(__DIR__) . "/imagenes_empleados";
    if (!is_dir($directorio)) {
        mkdir($directorio, 0777, true);
    }

    $nombreFinal = strtolower($codigoEmpleado) . "-" . time() . "." . $extension;
    $rutaDestino = $directorio . "/" . $nombreFinal;

    if (!move_uploaded_file($tmp, $rutaDestino)) {
        throw new RuntimeException("No se pudo guardar la foto del empleado.");
    }

    return "imagenes_empleados/" . $nombreFinal;
}

try {
    $conexion = obtenerConexion();
    asegurarEstructuraEmpleados($conexion);

    $id = (int)($_POST["empleadoId"] ?? 0);
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

    if ($nombre === "" || $apellido === "" || $telefono === "" || $direccion === "" || $fechaIngreso === "") {
        responderJson(["ok" => false, "mensaje" => "Completa todos los datos obligatorios del empleado."], 400);
    }

    if (!in_array($estadoLaboral, ["Activo", "Inactivo", "Vacaciones"], true)) {
        responderJson(["ok" => false, "mensaje" => "Selecciona un estado laboral valido."], 400);
    }

    if ($estadoLaboral === "Inactivo" && ($fechaSalida === "" || $motivoSalida === "")) {
        responderJson(["ok" => false, "mensaje" => "Completa la fecha y el motivo de salida para dar de baja al empleado."], 400);
    }

    if ($estadoLaboral !== "Inactivo") {
        $fechaSalida = "";
        $motivoSalida = "";
    }

    $conexion->begin_transaction();

    $fotoActual = null;
    $codigoEmpleado = "";

    if ($id > 0) {
        $stmtActual = $conexion->prepare("SELECT codigo_empleado, foto FROM empleados_limpieza WHERE id = ?");
        $stmtActual->bind_param("i", $id);
        $stmtActual->execute();
        $actual = $stmtActual->get_result()->fetch_assoc();

        if (!$actual) {
            responderJson(["ok" => false, "mensaje" => "El empleado que intentas editar no existe."], 404);
        }

        $codigoEmpleado = $actual["codigo_empleado"];
        $fotoActual = $actual["foto"];
    } else {
        $stmtInsert = $conexion->prepare("
            INSERT INTO empleados_limpieza
            (codigo_empleado, nombre, apellido, telefono, direccion, fecha_ingreso, puesto, estado_laboral, fecha_salida, motivo_salida, notas_internas, foto)
            VALUES ('TEMP', ?, ?, ?, ?, ?, ?, ?, NULLIF(?, ''), NULLIF(?, ''), ?, NULL)
        ");
        $stmtInsert->bind_param("ssssssssss", $nombre, $apellido, $telefono, $direccion, $fechaIngreso, $puesto, $estadoLaboral, $fechaSalida, $motivoSalida, $notasInternas);
        $stmtInsert->execute();
        $id = (int)$stmtInsert->insert_id;
        $codigoEmpleado = generarCodigoEmpleado($conexion, $id);

        $stmtCodigo = $conexion->prepare("UPDATE empleados_limpieza SET codigo_empleado = ? WHERE id = ?");
        $stmtCodigo->bind_param("si", $codigoEmpleado, $id);
        $stmtCodigo->execute();
    }

    $foto = procesarFotoEmpleado($_FILES["foto"] ?? null, $codigoEmpleado, $fotoActual);

    $stmtGuardar = $conexion->prepare("
        UPDATE empleados_limpieza
        SET nombre = ?, apellido = ?, telefono = ?, direccion = ?, fecha_ingreso = ?, puesto = ?, estado_laboral = ?,
            fecha_salida = NULLIF(?, ''), motivo_salida = NULLIF(?, ''), notas_internas = ?, foto = ?
        WHERE id = ?
    ");
    $stmtGuardar->bind_param("sssssssssssi", $nombre, $apellido, $telefono, $direccion, $fechaIngreso, $puesto, $estadoLaboral, $fechaSalida, $motivoSalida, $notasInternas, $foto, $id);
    $stmtGuardar->execute();

    $conexion->commit();
    intentarSincronizarMongoDesdeMySQL($conexion);

    responderJson([
        "ok" => true,
        "mensaje" => "Empleado guardado correctamente.",
        "id" => $id,
        "codigo" => $codigoEmpleado,
        "foto" => $foto
    ]);
} catch (Throwable $e) {
    if (isset($conexion)) {
        $conexion->rollback();
    }
    manejarErrorServidor("No se pudo guardar el empleado", $e);
}

