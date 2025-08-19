<?php
session_start();
include 'conexion.php';

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Debug: Verificar conexión
    if ($conexion->connect_error) {
        $mensaje = "❌ Error de conexión: " . $conexion->connect_error;
    } else {
        echo "<!-- Debug: Conexión OK -->";
        
        // Validar y sanitizar datos
        $nombre = trim($_POST["nombre"]);
        $correo = filter_var(trim($_POST["correo"]), FILTER_SANITIZE_EMAIL);
        $contrasena = trim($_POST["contrasena"]);
        $direccion = trim($_POST["direccion"]);
        $telefono = trim($_POST["telefono"]);

        // Debug: Mostrar datos recibidos
        echo "<!-- Debug datos: nombre=$nombre, correo=$correo, contrasena=".strlen($contrasena)." chars -->";

        // Validaciones básicas
        if (empty($nombre) || empty($correo) || empty($contrasena) || empty($direccion) || empty($telefono)) {
            $mensaje = "❌ Todos los campos son obligatorios.";
        } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $mensaje = "❌ El formato del correo no es válido.";
        } elseif (strlen($contrasena) < 6) {
            $mensaje = "❌ La contraseña debe tener al menos 6 caracteres.";
        } else {
            // Debug: Verificar si tabla existe
            $check_table = "SHOW TABLES LIKE 'usuarios'";
            $table_result = $conexion->query($check_table);
            if ($table_result->num_rows == 0) {
                $mensaje = "❌ La tabla 'usuarios' no existe en la base de datos.";
            } else {
                echo "<!-- Debug: Tabla usuarios existe -->";
                
                // Verificar estructura de la tabla
                $check_columns = "DESCRIBE usuarios";
                $columns_result = $conexion->query($check_columns);
                $columns = [];
                while ($col = $columns_result->fetch_assoc()) {
                    $columns[] = $col['Field'];
                }
                echo "<!-- Debug: Columnas en usuarios: " . implode(', ', $columns) . " -->";
                
                // Verificar si el correo ya existe
                $sql = "SELECT id_usuario FROM usuarios WHERE correo = ?";
                $stmt = $conexion->prepare($sql);
                
                if (!$stmt) {
                    $mensaje = "❌ Error preparando consulta SELECT: " . $conexion->error;
                } else {
                    echo "<!-- Debug: SELECT statement preparado OK -->";
                    $stmt->bind_param("s", $correo);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0) {
                        $mensaje = "⚠️ Este correo ya está registrado.";
                        $stmt->close();
                    } else {
                        $stmt->close();
                        echo "<!-- Debug: Correo disponible -->";
                        
                        // Encriptar la contraseña
                        $contrasena_hashed = password_hash($contrasena, PASSWORD_DEFAULT);
                        echo "<!-- Debug: Contraseña hasheada OK -->";

                        // Determinar el nombre correcto de la columna contraseña
                        $password_column = in_array('contrasena', $columns) ? 'contrasena' : 'contraseña';
                        echo "<!-- Debug: Usando columna '$password_column' para contraseña -->";

                        // Insertar nuevo usuario
                        $insert = "INSERT INTO usuarios (nombre, correo, $password_column, direccion, telefono) VALUES (?, ?, ?, ?, ?)";
                        echo "<!-- Debug: Query INSERT: $insert -->";
                        
                        $stmt_insert = $conexion->prepare($insert);
                        
                        if (!$stmt_insert) {
                            $mensaje = "❌ Error preparando INSERT: " . $conexion->error;
                        } else {
                            echo "<!-- Debug: INSERT statement preparado OK -->";
                            $stmt_insert->bind_param("sssss", $nombre, $correo, $contrasena_hashed, $direccion, $telefono);

                            if ($stmt_insert->execute()) {
                                echo "<!-- Debug: INSERT ejecutado OK -->";
                                $stmt_insert->close();
                                $conexion->close();
                                
                                // Redirigir con éxito
                                header("Location: PROYECTO.php?registro=1");
                                exit;
                            } else {
                                $mensaje = "❌ Error ejecutando INSERT: " . $stmt_insert->error;
                                $stmt_insert->close();
                            }
                        }
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Registrarse - JERSEYKING</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #f5f5f5;
      margin: 0;
      padding: 0;
    }
    
    .form-container {
      width: 100%;
      max-width: 400px;
      margin: 80px auto;
      padding: 30px;
      border: 1px solid #ddd;
      border-radius: 8px;
      background-color: white;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .form-container h2 {
      text-align: center;
      margin-bottom: 20px;
      color: #333;
    }

    .form-container input[type="text"],
    .form-container input[type="email"],
    .form-container input[type="password"],
    .form-container textarea {
      width: 100%;
      padding: 12px;
      margin: 8px 0;
      border-radius: 4px;
      border: 1px solid #ddd;
      box-sizing: border-box;
      font-size: 14px;
    }

    .form-container input[type="submit"] {
      width: 100%;
      padding: 12px;
      background-color: #007bff;
      color: white;
      border: none;
      border-radius: 4px;
      font-weight: bold;
      cursor: pointer;
      font-size: 16px;
      margin-top: 10px;
    }

    .form-container input[type="submit"]:hover {
      background-color: #0056b3;
    }

    .mensaje {
      text-align: center;
      padding: 10px;
      border-radius: 4px;
      margin-bottom: 15px;
      font-weight: bold;
    }
    
    .mensaje.error {
      color: #721c24;
      background-color: #f8d7da;
      border: 1px solid #f5c6cb;
    }

    .form-container p {
      text-align: center;
      margin-top: 20px;
    }
    
    .form-container p a {
      color: #007bff;
      text-decoration: none;
    }
    
    .back-link {
      text-align: center;
      margin-bottom: 20px;
    }
    
    .back-link a {
      color: #007bff;
      text-decoration: none;
      font-size: 14px;
    }
  </style>
</head>
<body>
  <div class="form-container">
    <div class="back-link">
      <a href="PROYECTO.php">← Volver al inicio</a>
    </div>
    
    <h2>Registro de Usuario</h2>

    <?php if ($mensaje): ?>
      <div class="mensaje error"><?= $mensaje ?></div>
    <?php endif; ?>

    <form method="POST">
      <input type="text" name="nombre" placeholder="Nombre completo" required 
             value="<?= isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : '' ?>">
      
      <input type="email" name="correo" placeholder="Correo electrónico" required
             value="<?= isset($_POST['correo']) ? htmlspecialchars($_POST['correo']) : '' ?>">
      
      <input type="password" name="contrasena" placeholder="Contraseña (mín. 6 caracteres)" required>
      
      <textarea name="direccion" placeholder="Dirección completa" rows="3" required><?= isset($_POST['direccion']) ? htmlspecialchars($_POST['direccion']) : '' ?></textarea>
      
      <input type="text" name="telefono" placeholder="Número de teléfono" required
             value="<?= isset($_POST['telefono']) ? htmlspecialchars($_POST['telefono']) : '' ?>">
      
      <input type="submit" value="Registrarme">
    </form>


<p>¿Ya tienes cuenta? <a href="PROYECTO.php#login">Iniciar sesión</a></p>
  </div>
</body>
</html>