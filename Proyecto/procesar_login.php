<?php
session_start();
include 'conexion.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $correo = trim($_POST["correo"] ?? "");
    $contrasena = trim($_POST["contrasena"] ?? "");

    if (empty($correo) || empty($contrasena)) {
        header("Location: proyecto.php?error=1");
        exit;
    }

    // Buscar usuario
    $sql = "SELECT id_usuario, nombre, correo, contrasena, telefono, direccion 
            FROM usuarios WHERE correo = ?";
    $stmt = $conexion->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("s", $correo);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $usuario = $result->fetch_assoc();
            
            // ✅ AQUÍ ESTÁ LA CLAVE: usar password_verify()
            if (password_verify($contrasena, $usuario['contrasena'])) {
                // Login exitoso
                $_SESSION['loggedin'] = true;
                $_SESSION['id_usuario'] = $usuario['id_usuario'];
                $_SESSION['nombre'] = $usuario['nombre'];
                $_SESSION['correo'] = $usuario['correo'];
                
                header("Location: proyecto.php");
                exit;
            } else {
                // Contraseña incorrecta
                header("Location: proyecto.php?error=1");
                exit;
            }
        } else {
            // Usuario no existe
            header("Location: proyecto.php?error=1");
            exit;
        }
        $stmt->close();
    } else {
        header("Location: proyecto.php?error=1");
        exit;
    }
}
?>