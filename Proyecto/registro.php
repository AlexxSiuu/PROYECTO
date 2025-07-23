<?php
$host = "127.0.0.1";
$usuario = "root";
$contrasena = "Ragnarok2025";
$base_datos = "Proyecto"; // Cambié a Proyecto que es la base que hicimos para la tienda
$puerto = 3308;

function ejecutarSQL($tipoSentencia, $sentenciaSQL) {
    global $host, $usuario, $contrasena, $base_datos, $puerto;

    $conexion = new mysqli($host, $usuario, $contrasena, $base_datos, $puerto);
    $conexion->set_charset("utf8mb4");

    if ($conexion->connect_error) {
        echo "No se pudo establecer conexión: " . $conexion->connect_error;
        return false;
    }

    $resultado = $conexion->query($sentenciaSQL);

    if ($resultado) {
        if (strtolower($tipoSentencia) == "select") {
            $datos = [];
            while ($fila = $resultado->fetch_object()) {
                $datos[] = $fila;
            }
            $conexion->close();
            return $datos;
        } else {
            $conexion->close();
            return true;
        }
    } else {
        echo "Error: " . $conexion->error;
        $conexion->close();
        return false;
    }
}

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST["nombre"];
    $correo = $_POST["correo"];
    $contrasena = $_POST["contrasena"];
    $direccion = $_POST["direccion"];
    $telefono = $_POST["telefono"];

    // Verificar si el correo ya existe
    $sql = "SELECT * FROM usuarios WHERE correo = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $mensaje = "⚠️ Este correo ya está registrado.";
    } else {
        // ✅ Encriptar la contraseña
        $contrasena_hashed = password_hash($contrasena, PASSWORD_DEFAULT);

        // Insertar nuevo usuario
        $insert = "INSERT INTO usuarios (nombre, correo, contraseña, direccion, telefono) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert);
        $stmt->bind_param("sssss", $nombre, $correo, $contrasena_hashed, $direccion, $telefono);

        if ($stmt->execute()) {
            header("Location: Menu.php?registro=1");
            exit;
        } else {
            $mensaje = "❌ Error al registrar. Intenta de nuevo.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Registrarse - Tienda Puma</title>
  <link rel="stylesheet" href="estilos.css">
  <style>
    .form-container {
      width: 400px;
      margin: 80px auto;
      padding: 30px;
      border: 1px solid #ccc;
      border-radius: 8px;
      background-color: #f9f9f9;
    }

    .form-container h2 {
      text-align: center;
      margin-bottom: 20px;
    }

    .form-container input[type="text"],
    .form-container input[type="email"],
    .form-container input[type="password"],
    .form-container textarea {
      width: 100%;
      padding: 10px;
      margin: 8px 0;
      border-radius: 4px;
      border: 1px solid #aaa;
    }

    .form-container input[type="submit"] {
      width: 100%;
      padding: 10px;
      background-color: #111;
      color: white;
      border: none;
      border-radius: 4px;
      font-weight: bold;
      cursor: pointer;
    }

    .form-container input[type="submit"]:hover {
      background-color: #333;
    }

    .mensaje {
      text-align: center;
      color: red;
      font-weight: bold;
      margin-bottom: 10px;
    }

    .form-container p {
      text-align: center;
      margin-top: 15px;
    }
  </style>
</head>
<body>
  <div class="form-container">
    <h2>Registro de Usuario</h2>

    <?php if ($mensaje): ?>
      <p class="mensaje"><?= $mensaje ?></p>
    <?php endif; ?>

    <form method="POST">
      <input type="text" name="nombre" placeholder="Nombre completo" required>
      <input type="email" name="correo" placeholder="Correo electrónico" required>
      <input type="password" name="contrasena" placeholder="Contraseña" required>
      <textarea name="direccion" placeholder="Dirección" rows="3" required></textarea>
      <input type="text" name="telefono" placeholder="Teléfono" required>
      <input type="submit" value="Registrarme">
    </form>

    <p>¿Ya tienes cuenta? <a href="Menu.php">Inicia sesión</a></p>
  </div>
</body>
</html>
