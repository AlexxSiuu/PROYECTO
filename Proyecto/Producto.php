<?php
include ('conexion.php');

function ejecutarSQL($tipo, $sql) {
    global $host, $usuario, $contrasena, $base_datos, $puerto;
    $conexion = new mysqli($host, $usuario, $contrasena, $base_datos, $puerto);
    $conexion->set_charset("utf8mb4");
    if ($conexion->connect_error) return false;

    $resultado = $conexion->query($sql);
    if (!$resultado) return false;

    if ($tipo === "select") {
        $datos = [];
        while ($fila = $resultado->fetch_object()) $datos[] = $fila;
        $conexion->close();
        return $datos;
    }
    $conexion->close();
    return true;
}

// Obtener ID del producto desde la URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) die("ID inválido.");

// Obtener datos del producto
$sql = "SELECT p.*, g.nombre AS genero, u.nombre AS uso, d.nombre AS deporte
        FROM productos p
        LEFT JOIN generos g ON p.id_genero = g.id_genero
        LEFT JOIN usos u ON p.id_uso = u.id_uso
        LEFT JOIN deportes d ON p.id_deporte = d.id_deporte
        WHERE p.id_producto = $id";
$producto = ejecutarSQL("select", $sql);
$producto = $producto ? $producto[0] : null;

if (!$producto) die("Producto no encontrado.");

// Obtener tallas
$sqlTallas = "SELECT t.talla, pt.stock
              FROM producto_tallas pt
              JOIN tallas t ON pt.id_talla = t.id_talla
              WHERE pt.id_producto = $id AND pt.stock > 0";
$tallas = ejecutarSQL("select", $sqlTallas);

// Productos relacionados
$sqlRelacionados = "SELECT id_producto, nombre, marca, precio, imagen_url
                    FROM productos
                    WHERE id_producto != $id
                    AND (
                        id_genero = {$producto->id_genero} OR
                        id_uso = {$producto->id_uso} OR
                        id_deporte = {$producto->id_deporte}
                    )
                    LIMIT 4";
$relacionados = ejecutarSQL("select", $sqlRelacionados);
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($producto->nombre) ?> | Detalle</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body {
      font-family: 'Arial', sans-serif;
      margin: 0;
      padding: 40px;
      background-color: #ffffff;
      color: #111;
    }
    .detalle-container {
      display: flex;
      max-width: 1200px;
      margin: auto;
      gap: 60px;
      align-items: flex-start;
    }
    .detalle-imagen {
      flex: 1;
      display: flex;
      justify-content: center;
    }
    .detalle-imagen img {
      width: 100%;
      max-width: 500px;
      border-radius: 6px;
    }
    .detalle-info {
      flex: 1;
      max-width: 500px;
    }
    .marca {
      text-transform: uppercase;
      font-size: 13px;
      color: #888;
      margin-bottom: 10px;
    }
    h1 {
      font-size: 28px;
      margin-bottom: 10px;
    }
    .descripcion {
      font-size: 15px;
      color: #333;
      margin-bottom: 20px;
    }
    .precio {
      font-size: 22px;
      font-weight: bold;
      margin-bottom: 20px;
    }
    label {
      font-weight: bold;
      margin-bottom: 5px;
      display: block;
    }
    select {
      padding: 12px;
      font-size: 16px;
      width: 100%;
      margin-bottom: 25px;
      border-radius: 6px;
      border: 1px solid #ccc;
    }
    button {
      background-color: #111;
      color: white;
      border: none;
      padding: 14px;
      font-size: 16px;
      border-radius: 6px;
      width: 100%;
      cursor: pointer;
    }
    button:hover {
      background-color: #333;
    }

    /* Relacionados */
    .relacionados {
      max-width: 1200px;
      margin: 60px auto;
    }

    .relacionados h2 {
      text-align: center;
      margin-bottom: 30px;
    }

    .relacionados-contenedor {
      display: flex;
      justify-content: center;
      gap: 30px;
      flex-wrap: wrap;
    }

    .relacionado-item {
      width: 220px;
      background: #fafafa;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 2px 8px rgba(0,0,0,0.05);
      text-align: center;
      padding: 10px;
      transition: transform 0.2s;
    }

    .relacionado-item:hover {
      transform: translateY(-4px);
    }

    .relacionado-item img {
      width: 100%;
      height: 200px;
      object-fit: cover;
      border-radius: 6px;
    }

    .relacionado-item h4 {
      margin: 10px 0 5px;
      font-size: 16px;
    }

    .relacionado-item p {
      font-size: 14px;
      color: #555;
      margin: 2px 0;
    }

    .relacionado-item .precio {
      font-weight: bold;
      color: #000;
    }
  </style>
</head>
<body>

<!-- Producto principal -->
<div class="detalle-container">
  <div class="detalle-imagen">
    <img src="<?= htmlspecialchars($producto->imagen_url) ?>" alt="<?= htmlspecialchars($producto->nombre) ?>">
  </div>
  <div class="detalle-info">
    <div class="marca"><?= htmlspecialchars($producto->marca) ?></div>
    <h1><?= htmlspecialchars($producto->nombre) ?></h1>
    <div class="descripcion"><?= htmlspecialchars($producto->descripcion) ?></div>
    <div class="precio">$<?= number_format($producto->precio, 2) ?></div>

    <?php if ($tallas): ?>
      <label for="talla">Seleccionar talla:</label>
      <select id="talla">
        <?php foreach ($tallas as $t): ?>
          <option><?= htmlspecialchars($t->talla) ?> (<?= $t->stock ?> disponibles)</option>
        <?php endforeach; ?>
      </select>
    <?php else: ?>
      <p style="color: gray;">Este producto no tiene tallas disponibles.</p>
    <?php endif; ?>

    <button class="btn-carrito">🛒 Agregar al carrito</button>
  </div>
</div>

<!-- Productos relacionados -->
<?php if ($relacionados): ?>
  <div class="relacionados">
    <h2>Productos relacionados</h2>
    <div class="relacionados-contenedor">
      <?php foreach ($relacionados as $p): ?>
        <div class="relacionado-item">
          <a href="detalle.php?id=<?= $p->id_producto ?>" style="text-decoration: none; color: inherit;">
            <img src="<?= htmlspecialchars($p->imagen_url) ?>" alt="<?= htmlspecialchars($p->nombre) ?>">
            <h4><?= htmlspecialchars($p->nombre) ?></h4>
            <p><?= htmlspecialchars($p->marca) ?></p>
            <p class="precio">$<?= number_format($p->precio, 2) ?></p>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

</body>
</html>
