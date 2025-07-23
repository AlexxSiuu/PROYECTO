<?php
session_start();
include 'conexion.php';

// Validación de datos
if (!isset($_POST['correo']) || !isset($_POST['contrasena'])) {
    header("Location: Menu.php?error=1");
    exit;
}

$correo = $_POST['correo'];
$contrasena = $_POST['contrasena'];

$sql = "SELECT * FROM usuarios WHERE correo = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $correo);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $usuario = $result->fetch_assoc();

    // 🔐 Verificar contraseña encriptada
    if (password_verify($contrasena, $usuario['contraseña'])) {
        $_SESSION['id_usuario'] = $usuario['id_usuario'];
        $_SESSION['nombre'] = $usuario['nombre'];
        $_SESSION['correo'] = $usuario['correo'];

        header("Location: Menu.php");
        exit;
    } else {
        // Contraseña incorrecta
        header("Location: Menu.php?error=1");
        exit;
    }
} else {
    // Usuario no encontrado
    header("Location: Menu.php?error=1");
    exit;
}
?>
