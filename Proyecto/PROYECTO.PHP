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

// ===== SECCIÓN DE FILTROS DE CAMISETAS POR EQUIPOS =====

// 1. REAL MADRID
$sqlRealMadrid = "
    SELECT p.id_producto, p.nombre, p.marca, p.descripcion, p.precio, p.imagen_url,
           g.nombre AS genero, u.nombre AS uso, d.nombre AS deporte
    FROM productos p
    INNER JOIN generos g ON p.id_genero = g.id_genero
    INNER JOIN usos u ON p.id_uso = u.id_uso
    INNER JOIN deportes d ON p.id_deporte = d.id_deporte
    WHERE p.nombre LIKE '%CAMISETA%' AND p.nombre LIKE '%REAL MADRID%'
    ORDER BY p.nombre
";

// 2. FC BARCELONA
$sqlBarcelona = "
    SELECT p.id_producto, p.nombre, p.marca, p.descripcion, p.precio, p.imagen_url,
           g.nombre AS genero, u.nombre AS uso, d.nombre AS deporte
    FROM productos p
    INNER JOIN generos g ON p.id_genero = g.id_genero
    INNER JOIN usos u ON p.id_uso = u.id_uso
    INNER JOIN deportes d ON p.id_deporte = d.id_deporte
    WHERE p.nombre LIKE '%CAMISETA%' AND p.nombre LIKE '%FC BARCELONA%'
    ORDER BY p.nombre
";

// 3. ATLÉTICO DE MADRID
$sqlAtletico = "
    SELECT p.id_producto, p.nombre, p.marca, p.descripcion, p.precio, p.imagen_url,
           g.nombre AS genero, u.nombre AS uso, d.nombre AS deporte
    FROM productos p
    INNER JOIN generos g ON p.id_genero = g.id_genero
    INNER JOIN usos u ON p.id_uso = u.id_uso
    INNER JOIN deportes d ON p.id_deporte = d.id_deporte
    WHERE p.nombre LIKE '%CAMISETA%' AND p.nombre LIKE '%ATLÉTICO DE MADRID%'
    ORDER BY p.nombre
";

// 4. MANCHESTER CITY
$sqlCity = "
    SELECT p.id_producto, p.nombre, p.marca, p.descripcion, p.precio, p.imagen_url,
           g.nombre AS genero, u.nombre AS uso, d.nombre AS deporte
    FROM productos p
    INNER JOIN generos g ON p.id_genero = g.id_genero
    INNER JOIN usos u ON p.id_uso = u.id_uso
    INNER JOIN deportes d ON p.id_deporte = d.id_deporte
    WHERE p.nombre LIKE '%CAMISETA%' AND p.nombre LIKE '%MANCHESTER CITY%'
    ORDER BY p.nombre
";

// 5. MANCHESTER UNITED
$sqlUnited = "
    SELECT p.id_producto, p.nombre, p.marca, p.descripcion, p.precio, p.imagen_url,
           g.nombre AS genero, u.nombre AS uso, d.nombre AS deporte
    FROM productos p
    INNER JOIN generos g ON p.id_genero = g.id_genero
    INNER JOIN usos u ON p.id_uso = u.id_uso
    INNER JOIN deportes d ON p.id_deporte = d.id_deporte
    WHERE p.nombre LIKE '%CAMISETA%' AND p.nombre LIKE '%MANCHESTER UNITED%'
    ORDER BY p.nombre
";

// 6. CHELSEA
$sqlChelsea = "
    SELECT p.id_producto, p.nombre, p.marca, p.descripcion, p.precio, p.imagen_url,
           g.nombre AS genero, u.nombre AS uso, d.nombre AS deporte
    FROM productos p
    INNER JOIN generos g ON p.id_genero = g.id_genero
    INNER JOIN usos u ON p.id_uso = u.id_uso
    INNER JOIN deportes d ON p.id_deporte = d.id_deporte
    WHERE p.nombre LIKE '%CAMISETA%' AND p.nombre LIKE '%CHELSEA%'
    ORDER BY p.nombre
";

// Ejecutar las consultas
$camisetasRealMadrid = ejecutarSQL("select", $sqlRealMadrid);
$camisetasBarcelona = ejecutarSQL("select", $sqlBarcelona);
$camisetasAtletico = ejecutarSQL("select", $sqlAtletico);
$camisetasCity = ejecutarSQL("select", $sqlCity);
$camisetasUnited = ejecutarSQL("select", $sqlUnited);
$camisetasChelsea = ejecutarSQL("select", $sqlChelsea);

// Consulta para traer productos con stock (productos generales)
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
  <style>
    /* Estilos para las secciones de camisetas por equipos */
    .equipos-container {
      max-width: 1200px;
      margin: 40px auto;
      padding: 0 20px;
    }
    
    .equipo-section {
      margin-bottom: 50px;
      background: white;
      border-radius: 15px;
      padding: 30px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }
    
    .equipo-title {
      text-align: center;
      margin-bottom: 30px;
      padding-bottom: 15px;
      border-bottom: 3px solid #333;
      position: relative;
    }
    
    .equipo-title h2 {
      font-size: 2.5em;
      margin: 0;
      color: #111;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 1px;
    }
    
    .equipo-title::after {
      content: '';
      position: absolute;
      bottom: -3px;
      left: 50%;
      transform: translateX(-50%);
      width: 100px;
      height: 3px;
      background: linear-gradient(45deg, #ff6b35, #f7931e);
      border-radius: 2px;
    }
    
    /* Estilos específicos por equipo */
    .real-madrid .equipo-title h2 { color: #00529F; }
    .real-madrid .equipo-title { border-bottom-color: #00529F; }
    .real-madrid .equipo-title::after { background: linear-gradient(45deg, #00529F, #FEBE10); }
    
    .barcelona .equipo-title h2 { color: #A50044; }
    .barcelona .equipo-title { border-bottom-color: #A50044; }
    .barcelona .equipo-title::after { background: linear-gradient(45deg, #A50044, #004D98); }
    
    .atletico .equipo-title h2 { color: #CE3524; }
    .atletico .equipo-title { border-bottom-color: #CE3524; }
    .atletico .equipo-title::after { background: linear-gradient(45deg, #CE3524, #1F4B8B); }
    
    .city .equipo-title h2 { color: #6CABDD; }
    .city .equipo-title { border-bottom-color: #6CABDD; }
    .city .equipo-title::after { background: linear-gradient(45deg, #6CABDD, #1C2C5B); }
    
    .united .equipo-title h2 { color: #DA020E; }
    .united .equipo-title { border-bottom-color: #DA020E; }
    .united .equipo-title::after { background: linear-gradient(45deg, #DA020E, #FFF200); }
    
    .chelsea .equipo-title h2 { color: #034694; }
    .chelsea .equipo-title { border-bottom-color: #034694; }
    .chelsea .equipo-title::after { background: linear-gradient(45deg, #034694, #FFFFFF); }
    
    .camisetas-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 25px;
      margin-top: 25px;
    }
    
    .camiseta-card {
      background: white;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 2px 15px rgba(0,0,0,0.08);
      transition: all 0.3s ease;
      border: 1px solid #f0f0f0;
      position: relative;
    }
    
    .camiseta-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 30px rgba(0,0,0,0.15);
    }
    
    .camiseta-image {
      width: 100%;
      height: 200px;
      object-fit: cover;
      border-bottom: 1px solid #eee;
    }
    
    .camiseta-info {
      padding: 20px;
    }
    
    .camiseta-name {
      font-size: 1.1em;
      font-weight: 600;
      color: #111;
      margin-bottom: 8px;
      line-height: 1.3;
      height: 2.6em;
      overflow: hidden;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
    }
    
    .camiseta-details {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 10px;
      font-size: 0.9em;
      color: #666;
    }
    
    .camiseta-marca {
      background: #f8f9fa;
      padding: 4px 8px;
      border-radius: 4px;
      font-weight: 500;
    }
    
    .camiseta-precio {
      font-size: 1.3em;
      font-weight: 700;
      color: #111;
      text-align: right;
    }
    
    .camiseta-descripcion {
      font-size: 0.85em;
      color: #777;
      line-height: 1.4;
      height: 2.8em;
      overflow: hidden;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      margin-bottom: 15px;
    }
    
    .ver-camiseta-btn {
      width: 100%;
      padding: 12px 15px;
      background: linear-gradient(135deg, #111 0%, #333 100%);
      color: white;
      border: none;
      border-radius: 8px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      cursor: pointer;
      transition: all 0.3s ease;
      text-decoration: none;
      display: block;
      text-align: center;
    }
    
    .ver-camiseta-btn:hover {
      background: linear-gradient(135deg, #333 0%, #555 100%);
      transform: translateY(-1px);
    }
    
    .no-productos {
      text-align: center;
      color: #999;
      font-style: italic;
      padding: 40px;
      background: #f8f9fa;
      border-radius: 8px;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
      .equipos-container {
        padding: 0 15px;
        margin: 20px auto;
      }
      
      .equipo-section {
        padding: 20px;
        margin-bottom: 30px;
      }
      
      .equipo-title h2 {
        font-size: 1.8em;
      }
      
      .camisetas-grid {
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
      }
    }
  </style>
</head>
<body>

<header class="header">
  <nav class="navbar">
    <div class="logo">
    <a href="PROYECTO.php">
        <img src="img/logo_icono.ico.jpg" class="logo-img">
    </a>
</div>

    <ul class="nav-links">
  <li><a href="PROYECTO.php">Inicio</a></li>

<!-- Equipos en desarrollo no funciona aun--> 
<li class="dropdown">
  <a href="productos.php?equipos">Niños</a>
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
  
  <span class="search-icon">
    <svg data-slot="icon" fill="none" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" style="width:1em; height:1em; color:white; vertical-align:middle;">
      <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"></path>
    </svg>
  </span>
  
  <!-- CARRITO MEJORADO -->
  <div class="cart-container">
    <div class="cart-icon" onclick="toggleCarrito()">
      <svg data-slot="icon" fill="none" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" style="width:1em; height:1em; color:white; vertical-align:middle;">
        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z"></path>
      </svg>
      <span class="cart-count" id="cart-count">0</span>
    </div>
    
    <!-- DROPDOWN DEL CARRITO -->
    <div class="cart-dropdown" id="cart-dropdown">
      <div class="cart-header">
        <span>Mi Carrito</span>
        <span class="close-cart" onclick="toggleCarrito()">&times;</span>
      </div>
      
      <div class="cart-content" id="cart-content">
        <div class="cart-empty">
          <p>Tu carrito está vacío</p>
          <small>Agrega productos para comenzar</small>
        </div>
      </div>
      
      <div class="cart-footer" id="cart-footer" style="display:none;">
        <div class="cart-total">
          <span>Total: </span>
          <span id="cart-total-amount">$0.00</span>
        </div>
        <div class="cart-actions">
          <a href="carrito.php" class="btn-cart btn-view-cart">Ver Carrito</a>
          <a href="checkout.php" class="btn-cart btn-checkout">Finalizar Compra</a>
        </div>
      </div>
    </div>
  </div>
<?php if (isset($_SESSION['nombre']) && isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
  <span><svg data-slot="icon" fill="none" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" style="width:1em; height:1em; color:white; vertical-align:middle;">
  <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"></path>
</svg> <?php echo htmlspecialchars($_SESSION['nombre']); ?></span>
  <a href="logout.php" style="color:white;">Cerrar sesión</a>
<?php else: ?>
  <span id="login-icon" style="cursor:pointer; color:white;"><svg data-slot="icon" fill="none" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" style="width:1em; height:1em; color:white; vertical-align:middle;">
  <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"></path>
</svg> Iniciar sesión</span>
<?php endif; ?>
</div>














  </nav>
</header>

<div id="notification-container"></div>

<!-- CSS para las notificaciones elegantes -->
<style>
#notification-container {
    position: fixed;
    top: 80px;
    right: 20px;
    z-index: 9999;
    pointer-events: none;
}

.notification {
    background: white;
    border-radius: 12px;
    padding: 16px 20px;
    margin-bottom: 12px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.15);
    border-left: 4px solid;
    display: flex;
    align-items: center;
    min-width: 300px;
    max-width: 400px;
    pointer-events: auto;
    transform: translateX(100%);
    opacity: 0;
    transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

.notification.show {
    transform: translateX(0);
    opacity: 1;
}

.notification.success {
    border-left-color: #28a745;
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
}

.notification.error {
    border-left-color: #dc3545;
    background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
}

.notification.warning {
    border-left-color: #ffc107;
    background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
}

.notification.info {
    border-left-color: #17a2b8;
    background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);
}

.notification-icon {
    font-size: 20px;
    margin-right: 12px;
    animation: pulse 2s infinite;
}

.notification-content {
    flex: 1;
}

.notification-title {
    font-weight: 600;
    margin-bottom: 4px;
    font-size: 14px;
}

.notification-message {
    font-size: 13px;
    opacity: 0.8;
    line-height: 1.3;
}

.notification-close {
    margin-left: 12px;
    cursor: pointer;
    font-size: 18px;
    opacity: 0.5;
    transition: opacity 0.2s;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
}

.notification-close:hover {
    opacity: 1;
    background-color: rgba(0,0,0,0.1);
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

/* Responsive */
@media (max-width: 768px) {
    #notification-container {
        top: 70px;
        right: 10px;
        left: 10px;
    }
    
    .notification {
        min-width: auto;
        max-width: none;
    }
}
</style>

<!-- JavaScript para el sistema de notificaciones -->
<script>
// Sistema de notificaciones elegante
function mostrarNotificacion(mensaje, tipo = 'info', titulo = null) {
    const container = document.getElementById('notification-container');
    
    // Iconos según el tipo
    const iconos = {
        success: '✅',
        error: '❌',
        warning: '⚠️',
        info: 'ℹ️'
    };
    
    // Títulos por defecto
    const titulos = {
        success: titulo || 'Éxito',
        error: titulo || 'Error',
        warning: titulo || 'Advertencia',
        info: titulo || 'Información'
    };
    
    // Crear notificación
    const notification = document.createElement('div');
    notification.className = `notification ${tipo}`;
    
    notification.innerHTML = `
        <div class="notification-icon">${iconos[tipo]}</div>
        <div class="notification-content">
            <div class="notification-title">${titulos[tipo]}</div>
            <div class="notification-message">${mensaje}</div>
        </div>
        <div class="notification-close" onclick="cerrarNotificacion(this)">×</div>
    `;
    
    container.appendChild(notification);
    
    // Mostrar con animación
    setTimeout(() => {
        notification.classList.add('show');
    }, 100);
    
    // Auto-cerrar después de 5 segundos
    setTimeout(() => {
        cerrarNotificacion(notification.querySelector('.notification-close'));
    }, 5000);
}

function cerrarNotificacion(elemento) {
    const notification = elemento.parentElement;
    notification.classList.remove('show');
    
    setTimeout(() => {
        if (notification.parentElement) {
            notification.parentElement.removeChild(notification);
        }
    }, 400);
}

// Actualizar función de eliminar del carrito
function eliminarDelCarrito(idCarrito) {
    // Crear modal de confirmación elegante en lugar de confirm()
    mostrarConfirmacion(
        '¿Seguro que deseas eliminar este producto de tu carrito?',
        'Confirmar eliminación',
        () => {
            // Si confirma, proceder
            const formData = new FormData();
            formData.append('accion', 'eliminar');
            formData.append('id_carrito', idCarrito);
            
            fetch('actualizar_carrito.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mostrarNotificacion(data.message, data.type || 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    mostrarNotificacion(data.message, data.type || 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarNotificacion('Error de conexión', 'error');
            });
        }
    );
}

// Actualizar función de cambiar cantidad
function actualizarCantidad(idCarrito, accion) {
    const formData = new FormData();
    formData.append('accion', accion);
    formData.append('id_carrito', idCarrito);
    
    fetch('actualizar_carrito.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarNotificacion(data.message, data.type || 'success');
            setTimeout(() => {
                location.reload();
            }, 800);
        } else {
            mostrarNotificacion(data.message, data.type || 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarNotificacion('Error de conexión', 'error');
    });
}

// Modal de confirmación elegante
function mostrarConfirmacion(mensaje, titulo, onConfirm, onCancel = null) {
    const modalHTML = `
        <div class="confirm-modal" id="confirmModal" style="
            position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
            background: rgba(0,0,0,0.7); display: flex; align-items: center;
            justify-content: center; z-index: 10000;
        ">
            <div style="
                background: white; border-radius: 15px; max-width: 400px; width: 90%;
                box-shadow: 0 10px 40px rgba(0,0,0,0.3); overflow: hidden;
                animation: slideIn 0.3s ease;
            ">
                <div style="
                    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                    padding: 20px 25px; border-bottom: 1px solid #dee2e6;
                ">
                    <h3 style="margin: 0; color: #111; font-size: 1.2em;">${titulo}</h3>
                </div>
                <div style="padding: 25px; text-align: center;">
                    <p style="margin: 0; color: #333; line-height: 1.5;">${mensaje}</p>
                </div>
                <div style="
                    padding: 20px 25px; background: #f8f9fa;
                    display: flex; gap: 15px; justify-content: center;
                ">
                    <button onclick="cerrarConfirmacion(false)" style="
                        background: white; border: 2px solid #6c757d; color: #6c757d;
                        padding: 10px 20px; border-radius: 6px; cursor: pointer;
                        font-weight: 600; transition: all 0.3s ease;
                    ">Cancelar</button>
                    <button onclick="cerrarConfirmacion(true)" style="
                        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
                        border: 2px solid #dc3545; color: white;
                        padding: 10px 20px; border-radius: 6px; cursor: pointer;
                        font-weight: 600; transition: all 0.3s ease;
                    ">Confirmar</button>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    window.cerrarConfirmacion = function(confirmed) {
        const modal = document.getElementById('confirmModal');
        modal.remove();
        
        if (confirmed && onConfirm) {
            onConfirm();
        } else if (!confirmed && onCancel) {
            onCancel();
        }
        
        delete window.cerrarConfirmacion;
    };
}
</script>

<section class="hero">

  <div class="hero-content">

    </div>
  </div>

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

<!-- ===== SECCIONES DE CAMISETAS POR EQUIPOS ===== -->
<div class="equipos-container">

  <!-- 1. REAL MADRID -->
  <div class="equipo-section real-madrid">
    <div class="equipo-title">
      <h2>Real Madrid</h2>
    </div>
    <div class="camisetas-grid">
      <?php if ($camisetasRealMadrid && count($camisetasRealMadrid) > 0): ?>
        <?php foreach ($camisetasRealMadrid as $camiseta): ?>
          <div class="camiseta-card">
            <img src="<?= htmlspecialchars($camiseta->imagen_url) ?>" alt="<?= htmlspecialchars($camiseta->nombre) ?>" class="camiseta-image">
            <div class="camiseta-info">
              <h3 class="camiseta-name"><?= htmlspecialchars($camiseta->nombre) ?></h3>
              <div class="camiseta-details">
                <span class="camiseta-marca"><?= htmlspecialchars($camiseta->marca) ?></span>
                <span><?= htmlspecialchars($camiseta->genero) ?></span>
              </div>
              <p class="camiseta-descripcion"><?= htmlspecialchars($camiseta->descripcion) ?></p>
              <div class="camiseta-precio">$<?= number_format($camiseta->precio, 2) ?></div>
              <a href="Producto.php?id=<?= $camiseta->id_producto ?>" class="ver-camiseta-btn">Ver Producto</a>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="no-productos">
          <p>No hay camisetas del Real Madrid disponibles en este momento.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- 2. FC BARCELONA -->
  <div class="equipo-section barcelona">
    <div class="equipo-title">
      <h2>FC Barcelona</h2>
    </div>
    <div class="camisetas-grid">
      <?php if ($camisetasBarcelona && count($camisetasBarcelona) > 0): ?>
        <?php foreach ($camisetasBarcelona as $camiseta): ?>
          <div class="camiseta-card">
            <img src="<?= htmlspecialchars($camiseta->imagen_url) ?>" alt="<?= htmlspecialchars($camiseta->nombre) ?>" class="camiseta-image">
            <div class="camiseta-info">
              <h3 class="camiseta-name"><?= htmlspecialchars($camiseta->nombre) ?></h3>
              <div class="camiseta-details">
                <span class="camiseta-marca"><?= htmlspecialchars($camiseta->marca) ?></span>
                <span><?= htmlspecialchars($camiseta->genero) ?></span>
              </div>
              <p class="camiseta-descripcion"><?= htmlspecialchars($camiseta->descripcion) ?></p>
              <div class="camiseta-precio">$<?= number_format($camiseta->precio, 2) ?></div>
              <a href="Producto.php?id=<?= $camiseta->id_producto ?>" class="ver-camiseta-btn">Ver Producto</a>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="no-productos">
          <p>No hay camisetas del FC Barcelona disponibles en este momento.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- 3. ATLÉTICO DE MADRID -->
  <div class="equipo-section atletico">
    <div class="equipo-title">
      <h2>Atlético de Madrid</h2>
    </div>
    <div class="camisetas-grid">
      <?php if ($camisetasAtletico && count($camisetasAtletico) > 0): ?>
        <?php foreach ($camisetasAtletico as $camiseta): ?>
          <div class="camiseta-card">
            <img src="<?= htmlspecialchars($camiseta->imagen_url) ?>" alt="<?= htmlspecialchars($camiseta->nombre) ?>" class="camiseta-image">
            <div class="camiseta-info">
              <h3 class="camiseta-name"><?= htmlspecialchars($camiseta->nombre) ?></h3>
              <div class="camiseta-details">
                <span class="camiseta-marca"><?= htmlspecialchars($camiseta->marca) ?></span>
                <span><?= htmlspecialchars($camiseta->genero) ?></span>
              </div>
              <p class="camiseta-descripcion"><?= htmlspecialchars($camiseta->descripcion) ?></p>
              <div class="camiseta-precio">$<?= number_format($camiseta->precio, 2) ?></div>
              <a href="Producto.php?id=<?= $camiseta->id_producto ?>" class="ver-camiseta-btn">Ver Producto</a>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="no-productos">
          <p>No hay camisetas del Atlético de Madrid disponibles en este momento.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- 4. MANCHESTER CITY -->
  <div class="equipo-section city">
    <div class="equipo-title">
      <h2>Manchester City</h2>
    </div>
    <div class="camisetas-grid">
      <?php if ($camisetasCity && count($camisetasCity) > 0): ?>
        <?php foreach ($camisetasCity as $camiseta): ?>
          <div class="camiseta-card">
            <img src="<?= htmlspecialchars($camiseta->imagen_url) ?>" alt="<?= htmlspecialchars($camiseta->nombre) ?>" class="camiseta-image">
            <div class="camiseta-info">
              <h3 class="camiseta-name"><?= htmlspecialchars($camiseta->nombre) ?></h3>
              <div class="camiseta-details">
                <span class="camiseta-marca"><?= htmlspecialchars($camiseta->marca) ?></span>
                <span><?= htmlspecialchars($camiseta->genero) ?></span>
              </div>
              <p class="camiseta-descripcion"><?= htmlspecialchars($camiseta->descripcion) ?></p>
              <div class="camiseta-precio">$<?= number_format($camiseta->precio, 2) ?></div>
              <a href="Producto.php?id=<?= $camiseta->id_producto ?>" class="ver-camiseta-btn">Ver Producto</a>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="no-productos">
          <p>No hay camisetas del Manchester City disponibles en este momento.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- 5. MANCHESTER UNITED -->
  <div class="equipo-section united">
    <div class="equipo-title">
      <h2>Manchester United</h2>
    </div>
    <div class="camisetas-grid">
      <?php if ($camisetasUnited && count($camisetasUnited) > 0): ?>
        <?php foreach ($camisetasUnited as $camiseta): ?>
          <div class="camiseta-card">
            <img src="<?= htmlspecialchars($camiseta->imagen_url) ?>" alt="<?= htmlspecialchars($camiseta->nombre) ?>" class="camiseta-image">
            <div class="camiseta-info">
              <h3 class="camiseta-name"><?= htmlspecialchars($camiseta->nombre) ?></h3>
              <div class="camiseta-details">
                <span class="camiseta-marca"><?= htmlspecialchars($camiseta->marca) ?></span>
                <span><?= htmlspecialchars($camiseta->genero) ?></span>
              </div>
              <p class="camiseta-descripcion"><?= htmlspecialchars($camiseta->descripcion) ?></p>
              <div class="camiseta-precio">$<?= number_format($camiseta->precio, 2) ?></div>
              <a href="Producto.php?id=<?= $camiseta->id_producto ?>" class="ver-camiseta-btn">Ver Producto</a>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="no-productos">
          <p>No hay camisetas del Manchester United disponibles en este momento.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- 6. CHELSEA -->
  <div class="equipo-section chelsea">
    <div class="equipo-title">
      <h2>Chelsea FC</h2>
    </div>
    <div class="camisetas-grid">
      <?php if ($camisetasChelsea && count($camisetasChelsea) > 0): ?>
        <?php foreach ($camisetasChelsea as $camiseta): ?>
          <div class="camiseta-card">
            <img src="<?= htmlspecialchars($camiseta->imagen_url) ?>" alt="<?= htmlspecialchars($camiseta->nombre) ?>" class="camiseta-image">
            <div class="camiseta-info">
              <h3 class="camiseta-name"><?= htmlspecialchars($camiseta->nombre) ?></h3>
              <div class="camiseta-details">
                <span class="camiseta-marca"><?= htmlspecialchars($camiseta->marca) ?></span>
                <span><?= htmlspecialchars($camiseta->genero) ?></span>
              </div>
              <p class="camiseta-descripcion"><?= htmlspecialchars($camiseta->descripcion) ?></p>
              <div class="camiseta-precio">$<?= number_format($camiseta->precio, 2) ?></div>
              <a href="Producto.php?id=<?= $camiseta->id_producto ?>" class="ver-camiseta-btn">Ver Producto</a>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="no-productos">
          <p>No hay camisetas del Chelsea disponibles en este momento.</p>
        </div>
      <?php endif; ?>
    </div>
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



<!-- CSS ADICIONAL PARA EL CARRITO -->
<style>
/* ===== CARRITO ESTILO PUMA/NIKE ===== */
.cart-container {
  position: relative;
}

.cart-icon {
  position: relative;
  cursor: pointer;
  font-size: 18px;
  transition: color 0.3s ease;
  padding: 8px;
  border-radius: 4px;
}

.cart-icon:hover {
  color: #ccc;
  background-color: rgba(255,255,255,0.1);
}

.cart-count {
  position: absolute;
  top: -2px;
  right: -2px;
  background-color: #ff4444;
  color: white;
  border-radius: 50%;
  padding: 2px 6px;
  font-size: 11px;
  font-weight: bold;
  min-width: 18px;
  height: 18px;
  display: none;
  align-items: center;
  justify-content: center;
  animation: cartBounce 0.3s ease;
}

.cart-count.show {
  display: flex;
}

@keyframes cartBounce {
  0% { transform: scale(0); }
  50% { transform: scale(1.3); }
  100% { transform: scale(1); }
}

.cart-dropdown {
  position: absolute;
  top: calc(100% + 15px);
  right: 0;
  background: white;
  border: 1px solid #ddd;
  border-radius: 12px;
  width: 380px;
  max-height: 500px;
  box-shadow: 0 8px 32px rgba(0,0,0,0.15);
  z-index: 1000;
  display: none;
  overflow: hidden;
}

.cart-dropdown.show {
  display: block;
  animation: fadeInScale 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

@keyframes fadeInScale {
  from { 
    opacity: 0; 
    transform: translateY(-20px) scale(0.8); 
  }
  to { 
    opacity: 1; 
    transform: translateY(0) scale(1); 
  }
}

.cart-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  border-bottom: 1px solid #eee;
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  font-weight: 700;
  color: #111;
  position: relative;
}

.close-cart {
  font-size: 24px;
  cursor: pointer;
  color: #666;
  transition: color 0.2s;
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
}

.close-cart:hover {
  color: #111;
  background-color: rgba(0,0,0,0.1);
}

.cart-content {
  max-height: 300px;
  overflow-y: auto;
}

.cart-empty {
  padding: 40px 20px;
  text-align: center;
  color: #666;
}

.cart-empty p {
  font-size: 16px;
  margin-bottom: 8px;
  font-weight: 500;
}

.cart-empty small {
  color: #999;
  font-size: 14px;
}

.cart-item {
  display: flex;
  align-items: center;
  padding: 16px 20px;
  border-bottom: 1px solid #f0f0f0;
  transition: background-color 0.2s ease;
  position: relative;
}

.cart-item:hover {
  background-color: #fafafa;
}

.cart-item:last-child {
  border-bottom: none;
}

.cart-item-image {
  width: 60px;
  height: 60px;
  object-fit: cover;
  border-radius: 8px;
  margin-right: 12px;
  border: 1px solid #f0f0f0;
}

.cart-item-details {
  flex: 1;
  min-width: 0;
  margin-right: 12px;
}

.cart-item-name {
  font-weight: 600;
  font-size: 14px;
  color: #111;
  margin-bottom: 4px;
  line-height: 1.2;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.cart-item-info {
  font-size: 12px;
  color: #666;
  margin-bottom: 6px;
  display: flex;
  gap: 8px;
}

.cart-item-price {
  font-size: 14px;
  font-weight: 700;
  color: #111;
}

.cart-item-actions {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 8px;
}

.quantity-controls {
  display: flex;
  align-items: center;
  border: 1px solid #ddd;
  border-radius: 20px;
  background: white;
  overflow: hidden;
}

.qty-btn {
  background: none;
  border: none;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  font-size: 14px;
  font-weight: bold;
  color: #666;
  transition: all 0.2s ease;
}

.qty-btn:hover:not(:disabled) {
  background-color: #f0f0f0;
  color: #111;
}

.qty-btn:disabled {
  color: #ccc;
  cursor: not-allowed;
}

.qty-input {
  width: 40px;
  border: none;
  text-align: center;
  font-size: 13px;
  font-weight: 600;
  background: transparent;
  color: #111;
}

.remove-btn {
  background: none;
  border: none;
  color: #dc3545;
  cursor: pointer;
  font-size: 12px;
  padding: 4px 8px;
  border-radius: 4px;
  transition: all 0.2s ease;
  font-weight: 500;
}

.remove-btn:hover {
  background-color: #dc3545;
  color: white;
}

.cart-footer {
  border-top: 1px solid #eee;
  background: #f8f9fa;
  padding: 20px;
}

.cart-total {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
  font-weight: 700;
  font-size: 18px;
  color: #111;
}

.cart-actions {
  display: flex;
  gap: 10px;
}

.btn-cart {
  flex: 1;
  padding: 12px 16px;
  border: none;
  border-radius: 8px;
  font-weight: 700;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.3s ease;
  text-align: center;
  text-decoration: none;
  display: inline-block;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.btn-view-cart {
  background-color: white;
  border: 2px solid #111;
  color: #111;
}

.btn-view-cart:hover {
  background-color: #111;
  color: white;
  transform: translateY(-1px);
}

.btn-checkout {
  background: linear-gradient(135deg, #111 0%, #333 100%);
  color: white;
  border: 2px solid #111;
}

.btn-checkout:hover {
  background: linear-gradient(135deg, #333 0%, #555 100%);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

/* Responsive */
@media (max-width: 768px) {
  .cart-dropdown {
    width: 320px;
    right: -20px;
  }
  
  .cart-item {
    padding: 12px 16px;
  }
  
  .cart-item-image {
    width: 50px;
    height: 50px;
  }
}
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















<!-- JAVASCRIPT PARA EL CARRITO -->
<script>
let carritoAbierto = false;
let carritoTimeout;

// Toggle del carrito
function toggleCarrito() {
    const dropdown = document.getElementById('cart-dropdown');
    
    if (!carritoAbierto) {
        cargarCarrito();
        dropdown.classList.add('show');
        carritoAbierto = true;
    } else {
        dropdown.classList.remove('show');
        carritoAbierto = false;
    }
}

// Cargar contenido del carrito
function cargarCarrito() {
    fetch('carrito_ajax.php')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            actualizarVistaCarrito(data);
        } else if (data.login_required) {
            mostrarLoginRequerido();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('cart-content').innerHTML = 
            '<div class="cart-empty"><p>Error al cargar el carrito</p></div>';
    });
}

// Actualizar vista del carrito
function actualizarVistaCarrito(data) {
    const cartContent = document.getElementById('cart-content');
    const cartFooter = document.getElementById('cart-footer');
    const cartCount = document.getElementById('cart-count');
    const cartTotal = document.getElementById('cart-total-amount');
    
    // Actualizar contador
    if (data.total_items > 0) {
        cartCount.textContent = data.total_items;
        cartCount.classList.add('show');
    } else {
        cartCount.classList.remove('show');
    }
    
    if (data.items.length === 0) {
        cartContent.innerHTML = `
            <div class="cart-empty">
                <p>Tu carrito está vacío</p>
                <small>Agrega productos para comenzar</small>
            </div>`;
        cartFooter.style.display = 'none';
    } else {
        let itemsHTML = '';
        data.items.forEach(item => {
            itemsHTML += `
                <div class="cart-item" data-cart-id="${item.id_carrito}">
                    <img src="${item.imagen_url}" alt="${item.nombre}" class="cart-item-image">
                    <div class="cart-item-details">
                        <div class="cart-item-name">${item.nombre}</div>
                        <div class="cart-item-info">
                            <span>Talla: ${item.talla}</span>
                            <span>•</span>
                            <span>${item.marca}</span>
                        </div>
                        <div class="cart-item-price">${item.subtotal}</div>
                    </div>
                    <div class="cart-item-actions">
                        <div class="quantity-controls">
                            <button class="qty-btn" onclick="actualizarCantidad(${item.id_carrito}, 'disminuir')" ${item.cantidad <= 1 ? 'title="Eliminar producto"' : ''}>
                                ${item.cantidad <= 1 ? '🗑️' : '−'}
                            </button>
                            <input type="text" class="qty-input" value="${item.cantidad}" readonly>
                            <button class="qty-btn" onclick="actualizarCantidad(${item.id_carrito}, 'aumentar')" ${item.cantidad >= item.stock_disponible ? 'disabled' : ''}>+</button>
                        </div>
                        <button class="remove-btn" onclick="eliminarDelCarrito(${item.id_carrito})">Eliminar</button>
                    </div>
                </div>`;
        });
        
        cartContent.innerHTML = itemsHTML;
        cartFooter.style.display = 'block';
        cartTotal.textContent = `${data.total}`;
    }
}

// Actualizar cantidad
function actualizarCantidad(idCarrito, accion) {
    const formData = new FormData();
    formData.append('accion', accion);
    formData.append('id_carrito', idCarrito);
    
    fetch('actualizar_carrito.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            cargarCarrito(); // Recargar carrito
            actualizarContadorCarrito(data.cart_count);
        } else {
            alert(data.message || 'Error al actualizar el carrito');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión');
    });
}

// Eliminar del carrito
function eliminarDelCarrito(idCarrito) {
    if (!confirm('¿Seguro que deseas eliminar este producto?')) return;
    
    const formData = new FormData();
    formData.append('accion', 'eliminar');
    formData.append('id_carrito', idCarrito);
    
    fetch('actualizar_carrito.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            cargarCarrito();
            actualizarContadorCarrito(data.cart_count);
        } else {
            alert(data.message || 'Error al eliminar producto');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión');
    });
}

// Actualizar contador del carrito
function actualizarContadorCarrito(count) {
    const cartCount = document.getElementById('cart-count');
    if (count > 0) {
        cartCount.textContent = count;
        cartCount.classList.add('show');
    } else {
        cartCount.classList.remove('show');
    }
}

// Mostrar mensaje de login requerido
function mostrarLoginRequerido() {
    document.getElementById('cart-content').innerHTML = `
        <div class="cart-empty">
            <p>Inicia sesión para ver tu carrito</p>
            <small>
                <a href="#" onclick="document.getElementById('login-icon').click(); toggleCarrito();" 
                   style="color: #007bff; text-decoration: underline;">
                   Hacer clic aquí para iniciar sesión
                </a>
            </small>
        </div>`;
}

// Cerrar carrito al hacer clic fuera
document.addEventListener('click', function(e) {
    const cartContainer = document.querySelector('.cart-container');
    if (carritoAbierto && !cartContainer.contains(e.target)) {
        toggleCarrito();
    }
});

// Cargar contador inicial del carrito al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    // Solo cargar si el usuario está logueado
    <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
    fetch('carrito_ajax.php')
    .then(response => response.json())
    .then(data => {
        if (data.success && data.total_items > 0) {
            actualizarContadorCarrito(data.total_items);
        }
    })
    .catch(error => console.log('Error cargando contador inicial'));
    <?php endif; ?>
});
</script>
    <section class="productos-lista">
        <?php if ($productos && count($productos) > 0): ?>
          <?php foreach ($productos as $producto): ?>
            <a href="Producto.php?id=<?= $producto->id_producto ?>" style="text-decoration:none; color:inherit;">
              <div class="producto-item">
                <img src="<?= htmlspecialchars($producto->imagen_url) ?>" alt="<?= htmlspecialchars($producto->nombre) ?>">
                <h3><?= htmlspecialchars($producto->nombre) ?></h3>
                <p>$<?= number_format($producto->precio, 2) ?></p>
              </div>
            </a>
          <?php endforeach; ?>
        <?php else: ?>
          <p>No hay productos disponibles.</p>
        <?php endif; ?>
  </section>
</body>
</html>