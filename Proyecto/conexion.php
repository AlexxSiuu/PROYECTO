<?php
include_once 'config.local.php';

// Crear conexión
$con = new mysqli($host, $usuario, $contrasena, $base_datos, $puerto);

// Verificar conexión
if ($con->connect_error) {
    die("Conexión fallida: " . $con->connect_error);
}
?>
