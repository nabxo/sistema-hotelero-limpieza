<?php
// Conexion a la base de datos

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function obtenerConexion()
{
    $servidor = "127.0.0.1";
    $usuario = "root";
    $contrasena = "";
    $base_datos = "sistema-hotelero-limpieza";

    $conexion = new mysqli($servidor, $usuario, $contrasena, $base_datos);
    $conexion->set_charset("utf8mb4");

    return $conexion;
}
?>
