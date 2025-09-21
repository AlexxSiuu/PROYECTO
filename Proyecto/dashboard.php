<?php
session_start();
include 'conexion.php';

// Verificar que sea admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['correo'] !== "admin@tienda.com") {
    header("Location: PROYECTO.php");
    exit();
}

// Consultar productos
$sql = "SELECT id_producto, nombre, precio, marca, imagen_url FROM productos";
$result = $conexion->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Administración</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
        th { background: #f4f4f4; }
        img { width: 80px; }
        .btn { padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; }
        .editar { background: #3498db; color: white; }
        .eliminar { background: #e74c3c; color: white; }
    </style>
</head>
<body>
    <h1>Bienvenido, <?php echo $_SESSION['nombre']; ?></h1>
    <p><a href="logout.php">Cerrar sesión</a></p>

    <h2>Productos en la tienda</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Imagen</th>
            <th>Nombre</th>
            <th>Marca</th>
            <th>Precio</th>
            <th>Acciones</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()) { ?>
        <tr>
            <td><?php echo $row['id_producto']; ?></td>
            <td><img src="<?php echo $row['imagen_url']; ?>" alt=""></td>
            <td><?php echo $row['nombre']; ?></td>
            <td><?php echo $row['marca']; ?></td>
            <td>$<?php echo number_format($row['precio'], 2); ?></td>
            <td>
                <a href="editar_producto.php?id=<?php echo $row['id_producto']; ?>" class="btn editar">Editar</a>
                <a href="eliminar_producto.php?id=<?php echo $row['id_producto']; ?>" class="btn eliminar" onclick="return confirm('¿Seguro que quieres eliminar este producto?')">Eliminar</a>
            </td>
        </tr>
        <?php } ?>
    </table>
</body>
</html>
