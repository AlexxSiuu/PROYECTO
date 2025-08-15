<?php
session_start();

// Conexión
include ('conexion.php'); 

$conexion = new mysqli($host, $usuario, $contrasena, $base_datos, $puerto);
$conexion->set_charset("utf8mb4");

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Obtener filtros desde URL
$genero  = isset($_GET['genero'])  ? intval($_GET['genero'])  : 0;
$uso     = isset($_GET['uso'])     ? intval($_GET['uso'])     : 0;
$deporte = isset($_GET['deporte']) ? intval($_GET['deporte']) : 0;

// Construir condición dinámica
$where = [];
if ($genero > 0)  $where[] = "p.id_genero = $genero";
if ($uso > 0)     $where[] = "p.id_uso = $uso";
if ($deporte > 0) $where[] = "p.id_deporte = $deporte";

// Siempre mostrar solo productos con stock
$where[] = "pt.stock > 0";

// Unir condiciones
$condicion = implode(" AND ", $where);

// Consulta final
$sql = "SELECT DISTINCT p.id_producto, p.nombre, p.precio, p.imagen_url
        FROM productos p
        JOIN producto_tallas pt ON p.id_producto = pt.id_producto
        WHERE $condicion
        ORDER BY p.nombre";

$resultado = $conexion->query($sql);

// Guardar resultados
$productos = [];
if ($resultado && $resultado->num_rows > 0) {
    while ($fila = $resultado->fetch_object()) {
        $productos[] = $fila;
    }
}

$conexion->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Productos</title>
  <link rel="stylesheet" href="prueba.css">
</head>
<body>

<section class="productos-lista">
  <h2>Productos</h2>

  <?php if (count($productos) > 0): ?>
    <?php foreach ($productos as $producto): ?>
      <div class="producto-item">
        <a href="Producto.php?id=<?= $producto->id_producto ?>" style="text-decoration:none; color:inherit;">
          <img src="<?= htmlspecialchars($producto->imagen_url) ?>" alt="<?= htmlspecialchars($producto->nombre) ?>">
          <h3><?= htmlspecialchars($producto->nombre) ?></h3>
          <p>$<?= number_format($producto->precio, 2) ?></p>
        </a>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <p>No hay productos disponibles para esta categoría.</p>
  <?php endif; ?>
</section>

</body>
</html>
