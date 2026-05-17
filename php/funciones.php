<?php
require_once __DIR__ . "/conexion.php";
require_once __DIR__ . "/mongodb.php";

enviarCabecerasSeguridad();

function enviarCabecerasSeguridad()
{
    header("X-Content-Type-Options: nosniff");
    header("X-Frame-Options: SAMEORIGIN");
    header("Referrer-Policy: same-origin");
}

// Respuesta en JSON
function responderJson($datos, $codigo = 200)
{
    http_response_code($codigo);
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

// Lee lo que manda fetch
function obtenerJsonEntrada()
{
    $contenido = file_get_contents("php://input");
    $datos = json_decode($contenido, true);

    return is_array($datos) ? $datos : [];
}

// Este archivo solo debe recibir POST
function validarMetodoPost()
{
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        responderJson([
            "ok" => false,
            "mensaje" => "Metodo no permitido"
        ], 405);
    }
}

// Limpia texto simple
function texto($valor)
{
    return trim((string)($valor ?? ""));
}

function manejarErrorServidor($mensaje, Throwable $e)
{
    error_log($mensaje . " | " . $e->getMessage());
    responderJson([
        "ok" => false,
        "mensaje" => $mensaje
    ], 500);
}
?>
