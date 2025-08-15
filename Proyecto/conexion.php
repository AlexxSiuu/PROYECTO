<?php
include_once 'config.local.php';

$conexion = new mysqli($host, $usuario, $contrasena, $base_datos, $puerto);
$conexion->set_charset("utf8mb4");

if ($conexion->connect_error) {
    die("Conexión fallida: " . $conexion->connect_error);
}
?>
