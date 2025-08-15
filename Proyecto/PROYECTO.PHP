<?php session_start(); 
// Tus datos de conexión
include ('conexion.php');

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

// Consulta para traer productos con stock
$sqlProductos = "
    SELECT DISTINCT p.id_producto, p.nombre, p.precio, p.imagen_url
    FROM productos p
    JOIN producto_tallas pt ON p.id_producto = pt.id_producto
    WHERE pt.stock > 0
    ORDER BY p.nombre
";

$productos = ejecutarSQL("select", $sqlProductos);

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Tienda Puma</title>
  <link rel="stylesheet" href="prueba.css">
</head>
<body>

<header class="header">
  <nav class="navbar">
    <div class="logo"><a href="PROYECTO.php">JERSEYKING</a></div>

    <ul class="nav-links">
  <li><a href="PROYECTO.php">Inicio</a></li>

  <!-- HOMBRE -->
<li class="dropdown">
  <a href="productos.php?genero=1">Hombre</a>
  <div class="mega-menu">
    <div class="mega-left hombre-img"></div>
    <div class="mega-right">
      <div class="column">
        <h4><a href="productos.php?genero=1&uso=2" style="color:inherit; text-decoration:none;">Calzado</a></h4>
        <a href="productos.php?genero=1&uso=2&deporte=2">Zapatillas deportivas</a>
        <a href="productos.php?genero=1&uso=2&deporte=1">Botines de fútbol</a>
        <a href="productos.php?genero=1&uso=2&deporte=3">Sandalias</a>
        <a href="productos.php?genero=1&uso=2&deporte=3">Sneakers de moda</a>
      </div>
      <div class="column">
        <h4><a href="productos.php?genero=1&uso=1" style="color:inherit; text-decoration:none;">Ropa</a></h4>
        <a href="productos.php?genero=1&uso=1&deporte=3">Camisetas</a>
        <a href="productos.php?genero=1&uso=1&deporte=2">Pantalones deportivos</a>
        <a href="productos.php?genero=1&uso=1&deporte=3">Sudaderas / Hoodies</a>
        <a href="productos.php?genero=1&uso=1&deporte=2">Shorts</a>
      </div>
      <div class="column">
        <h4><a href="productos.php?genero=1&uso=3" style="color:inherit; text-decoration:none;">Accesorios</a></h4>
        <a href="productos.php?genero=1&uso=3&deporte=3">Gorras</a>
        <a href="productos.php?genero=1&uso=3&deporte=3">Mochilas</a>
        <a href="productos.php?genero=1&uso=3&deporte=3">Calcetines</a>
      </div>
    </div>
  </div>
</li>

<!-- MUJER -->
<li class="dropdown">
  <a href="productos.php?genero=2">Mujer</a>
  <div class="mega-menu">
    <div class="mega-left mujer-img"></div>
    <div class="mega-right">
      <div class="column">
        <h4><a href="productos.php?genero=2&uso=2" style="color:inherit; text-decoration:none;">Calzado</a></h4>
        <a href="productos.php?genero=2&uso=2&deporte=2">Zapatillas deportivas</a>
        <a href="productos.php?genero=2&uso=2&deporte=3">Sandalias</a>
        <a href="productos.php?genero=2&uso=2&deporte=1">Botines deportivos</a>
      </div>
      <div class="column">
        <h4><a href="productos.php?genero=2&uso=1" style="color:inherit; text-decoration:none;">Ropa</a></h4>
        <a href="productos.php?genero=2&uso=1&deporte=3">Tops deportivos</a>
        <a href="productos.php?genero=2&uso=1&deporte=3">Leggings</a>
        <a href="productos.php?genero=2&uso=1&deporte=3">Sudaderas</a>
        <a href="productos.php?genero=2&uso=1&deporte=2">Shorts</a>
      </div>
      <div class="column">
        <h4><a href="productos.php?genero=2&uso=3" style="color:inherit; text-decoration:none;">Accesorios</a></h4>
        <a href="productos.php?genero=2&uso=3&deporte=3">Gorras</a>
        <a href="productos.php?genero=2&uso=3&deporte=3">Mochilas</a>
        <a href="productos.php?genero=2&uso=3&deporte=3">Medias</a>
      </div>
    </div>
  </div>
</li>

<!-- NIÑOS -->
<li class="dropdown">
  <a href="productos.php?genero=3">Niños</a>
  <div class="mega-menu">
    <div class="mega-left ninos-img"></div>
    <div class="mega-right">
      <div class="column">
        <h4><a href="productos.php?genero=3&uso=2" style="color:inherit; text-decoration:none;">Calzado</a></h4>
        <a href="productos.php?genero=3&uso=2&deporte=1">Botines</a>
        <a href="productos.php?genero=3&uso=2&deporte=3">Sandalias</a>
      </div>
      <div class="column">
        <h4><a href="productos.php?genero=3&uso=1" style="color:inherit; text-decoration:none;">Ropa</a></h4>
        <a href="productos.php?genero=3&uso=1&deporte=3">Camisetas</a>
        <a href="productos.php?genero=3&uso=1&deporte=2">Conjuntos deportivos</a>
        <a href="productos.php?genero=3&uso=1&deporte=2">Shorts</a>
      </div>
      <div class="column">
        <h4><a href="productos.php?genero=3&uso=3" style="color:inherit; text-decoration:none;">Accesorios</a></h4>
        <a href="productos.php?genero=3&uso=3&deporte=3">Mochilas</a>
        <a href="productos.php?genero=3&uso=3&deporte=3">Gorras</a>
      </div>
    </div>
  </div>
</li>

<!-- DEPORTES -->
<li class="dropdown">
  <a href="productos.php">Deportes</a>
  <div class="mega-menu">
    <div class="mega-left deportes-img"></div>
    <div class="mega-right">
      <div class="column">
        <h4><a href="productos.php?deporte=1" style="color:inherit; text-decoration:none;">Fútbol</a></h4>
        <a href="productos.php?uso=2&deporte=1">Botines</a>
        <a href="productos.php?uso=1&deporte=1">Camisetas</a>
        <a href="productos.php?uso=3&deporte=1">Balones</a>
      </div>
      <div class="column">
        <h4><a href="productos.php?deporte=2" style="color:inherit; text-decoration:none;">Running</a></h4>
        <a href="productos.php?uso=2&deporte=2">Zapatillas</a>
        <a href="productos.php?uso=1&deporte=2">Ropa ligera</a>
      </div>
      <div class="column">
        <h4><a href="productos.php?deporte=4" style="color:inherit; text-decoration:none;">Básquetbol</a></h4>
        <a href="productos.php?uso=1&deporte=4">Ropa</a>
        <a href="productos.php?uso=2&deporte=4">Tenis</a>
        <a href="productos.php?uso=3&deporte=4">Balones</a>
      </div>
    </div>
  </div>
</li>


   <div class="nav-icons">
  <input type="text" placeholder="Buscar...">
  <span>🔍</span>
  <span>🛒</span>

<?php if (isset($_SESSION['nombre'])): ?>
  <span>👤 <?php echo htmlspecialchars($_SESSION['nombre']); ?></span>
  <a href="logout.php" style="color:white;">Cerrar sesión</a>
<?php else: ?>
  <span id="login-icon" style="cursor:pointer; color:white;">👤 Iniciar sesión</span>
<?php endif; ?>



</div>

  </nav>
</header>

<section class="hero">
  <div class="hero-content">
    <h1>Bienvenido a JERSEYKING</h1>
    <p>Descubre lo último en moda deportiva</p>
    <div class="hero-buttons">
      <a href="#">Comprar ahora</a>
      <a href="#">Colecciones</a>
    </div>
  </div>
</section>

<?php include("login_modal.php"); ?>

<script>
  // Mostrar modal al hacer clic en el icono de usuario si no está logueado
  document.addEventListener("DOMContentLoaded", function() {
   const userIcon = document.getElementById("login-icon");
    const modal = document.getElementById("loginModal");
    const closeBtn = document.getElementById("closeLogin");

    <?php if (!isset($_SESSION['nombre'])): ?>
    if (userIcon && modal) {
      userIcon.style.cursor = "pointer";
      userIcon.addEventListener("click", () => {
        modal.style.display = "flex";
      });
      closeBtn.addEventListener("click", () => {
        modal.style.display = "none";
      });
    }
    <?php endif; ?>
  });
</script>

    <section class="productos-lista">
      <?php if ($productos && count($productos) > 0): ?>
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
        <p>No hay productos disponibles.</p>
      <?php endif; ?>
    </section>



</body>
</html>