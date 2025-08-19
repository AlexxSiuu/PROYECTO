<?php 
session_start(); 
include ('conexion.php');

// Función mejorada con prepared statements
function ejecutarSQL($tipoSentencia, $sentenciaSQL, $params = []) {
    global $conexion;
    
    if ($conexion->connect_error) {
        error_log("Error de conexión: " . $conexion->connect_error);
        return false;
    }
    
    $conexion->set_charset("utf8mb4");
    
    // Preparar statement
    $stmt = $conexion->prepare($sentenciaSQL);
    if (!$stmt) {
        error_log("Error preparando consulta: " . $conexion->error);
        return false;
    }
    
    // Bind parameters si existen
    if (!empty($params)) {
        $types = str_repeat('s', count($params)); // Asume que todos son strings
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    
    if (strtolower($tipoSentencia) == "select") {
        $result = $stmt->get_result();
        $datos = [];
        while ($fila = $result->fetch_object()) {
            $datos[] = $fila;
        }
        $stmt->close();
        return $datos;
    } else {
        $success = $stmt->affected_rows > 0;
        $stmt->close();
        return $success;
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
  <title>Tienda JERSEYKING</title>
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

    </ul>

   <div class="nav-icons">
  <input type="text" placeholder="Buscar...">
  <span>🔍</span>
  <span>🛒</span>

<?php if (isset($_SESSION['nombre']) && isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
  <span>👤 <?php echo htmlspecialchars($_SESSION['nombre']); ?></span>
  <a href="logout.php" style="color:white;">Cerrar sesión</a>
<?php else: ?>
  <span id="login-icon" style="cursor:pointer; color:white;">👤 Iniciar sesión</span>
<?php endif; ?>

</div>

  </nav>
</header>

<section class="hero">
</section>

<!-- Login Modal -->
<div class="modal" id="loginModal" style="display:none;">
  <div class="modal-content" style="background:white; padding:20px; border-radius:8px; min-width:300px; position:relative;">
    <span id="closeLogin" style="position:absolute; top:10px; right:15px; font-size:24px; cursor:pointer; color:#999;">&times;</span>
    <h2>Iniciar sesión</h2>
    
    <!-- Mostrar mensaje de error si existe -->
    <?php if (isset($_GET['error']) && $_GET['error'] == '1'): ?>
      <div style="color:red; background:#ffe6e6; padding:10px; border-radius:4px; margin:10px 0;">
        ❌ Correo o contraseña incorrectos
      </div>
    <?php endif; ?>
    



    <form method="POST" action="procesar_login.php">
      <div style="margin-bottom:15px;">
        <input type="email" name="correo" placeholder="Correo electrónico" required 
               style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px; box-sizing:border-box;">
      </div>
      <div style="margin-bottom:15px;">
        <input type="password" name="contrasena" placeholder="Contraseña" required
               style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px; box-sizing:border-box;">
      </div>
      <input type="submit" value="Iniciar sesión" 
             style="width:100%; padding:10px; background:#007bff; color:white; border:none; border-radius:4px; cursor:pointer;">
    </form>
    <p style="text-align:center; margin-top:15px;">
      ¿No tienes cuenta? <a href="registro.php" style="color:#007bff;">Regístrate</a>
    </p>
  </div>
</div>

<style>
.modal {
  position: fixed;
  top: 0; left: 0;
  width: 100vw; height: 100vh;
  background: rgba(0,0,0,0.6);
  display: none;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

.modal-content input[type="submit"]:hover {
  background: #0056b3;
}

/* Mostrar modal automáticamente si hay error */
<?php if (isset($_GET['error'])): ?>
.modal {
  display: flex !important;
}
<?php endif; ?>
</style>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const userIcon = document.getElementById("login-icon");
    const modal = document.getElementById("loginModal");
    const closeBtn = document.getElementById("closeLogin");

    // Solo agregar eventos si el usuario no está logueado
    <?php if (!isset($_SESSION['nombre']) || !isset($_SESSION['loggedin'])): ?>
    
    if (userIcon && modal && closeBtn) {
        // Mostrar modal al hacer clic en iniciar sesión
        userIcon.addEventListener("click", () => {
            modal.style.display = "flex";
        });
        
        // Cerrar modal con X
        closeBtn.addEventListener("click", () => {
            modal.style.display = "none";
            // Limpiar parámetro error de la URL
            if (window.location.search.includes('error=')) {
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        });
        
        // Cerrar modal al hacer clic fuera
        modal.addEventListener("click", (e) => {
            if (e.target === modal) {
                modal.style.display = "none";
                if (window.location.search.includes('error=')) {
                    window.history.replaceState({}, document.title, window.location.pathname);
                }
            }
        });
    }
    
    // Si hay error, mostrar modal automáticamente
    <?php if (isset($_GET['error'])): ?>
    if (modal) {
        modal.style.display = "flex";
    }
    <?php endif; ?>
    
    <?php endif; ?>
    
 if (window.location.hash === '#login' && modal) {
        modal.style.display = "flex";
        // Limpiar el hash
        window.history.replaceState({}, document.title, window.location.pathname);
    }


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