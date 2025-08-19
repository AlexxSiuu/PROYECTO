<?php
session_start();

// Vaciar variables de sesión
$_SESSION = [];

// Borrar la cookie de sesión si existe
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destruir sesión completamente
session_destroy();

// Redirigir al archivo correcto
header("Location: PROYECTO.php");
exit;
?>