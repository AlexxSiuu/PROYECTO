<?php
session_start();
include 'conexion.php';
// Validación de datos
if (!isset($_POST['correo']) || !isset($_POST['contrasena']) || 
    empty(trim($_POST['correo'])) || empty(trim($_POST['contrasena']))) {
    header("Location: PROYECTO.php?error=1");
    exit;
}
$correo = filter_var(trim($_POST['correo']), FILTER_SANITIZE_EMAIL);
$contrasena = trim($_POST['contrasena']);
// Validar formato de email
if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    header("Location: PROYECTO.php?error=1");
    exit;
}
try {
    // Consulta preparada - CAMBIO AQUÍ: contraseña en lugar de contrasena
    $sql = "SELECT id_usuario, nombre, correo, contraseña FROM usuarios WHERE correo = ?";
    $stmt = $conexion->prepare($sql);
    
    if (!$stmt) {
        error_log("Error preparando consulta: " . $conexion->error);
        header("Location: PROYECTO.php?error=1");
        exit;
    }
    
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $usuario = $result->fetch_assoc();
        
        // Verificar contraseña encriptada - CAMBIO AQUÍ: contraseña en lugar de contrasena
        if (password_verify($contrasena, $usuario['contraseña'])) {
            // Regenerar ID de sesión por seguridad
            session_regenerate_id(true);
            
            // Guardar datos en sesión
            $_SESSION['id_usuario'] = $usuario['id_usuario'];
            $_SESSION['nombre'] = $usuario['nombre'];
            $_SESSION['correo'] = $usuario['correo'];
            $_SESSION['loggedin'] = true;
            
            // Cerrar statement
            $stmt->close();
            $conexion->close();
            
            // Redirigir al inicio
            header("Location: PROYECTO.php");
            exit;
        } else {
            // Contraseña incorrecta
            $stmt->close();
            $conexion->close();
            header("Location: PROYECTO.php?error=1");
            exit;
        }
    } else {
        // Usuario no encontrado
        $stmt->close();
        $conexion->close();
        header("Location: PROYECTO.php?error=1");
        exit;
    }
    
} catch (Exception $e) {
    // Log del error (no mostrar al usuario)
    error_log("Error en login: " . $e->getMessage());
    header("Location: PROYECTO.php?error=1");
    exit;
}
?>