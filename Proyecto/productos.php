<?php
session_start();
include('conexion.php');

// Función mejorada con prepared statements (misma que en PROYECTO.php)
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
        $types = str_repeat('i', count($params)); // 'i' para enteros
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

// Obtener y validar filtros desde URL
$genero = isset($_GET['genero']) ? intval($_GET['genero']) : 0;
$uso = isset($_GET['uso']) ? intval($_GET['uso']) : 0;
$deporte = isset($_GET['deporte']) ? intval($_GET['deporte']) : 0;

// Validar que los IDs existan en la BD (opcional pero recomendado)
$generos_validos = [1, 2, 3]; // Hombre, Mujer, Niños
$usos_validos = [1, 2, 3];    // Ropa, Calzado, Accesorios  
$deportes_validos = [1, 2, 3, 4]; // Fútbol, Running, General, Básquetbol

if ($genero > 0 && !in_array($genero, $generos_validos)) $genero = 0;
if ($uso > 0 && !in_array($uso, $usos_validos)) $uso = 0;
if ($deporte > 0 && !in_array($deporte, $deportes_validos)) $deporte = 0;

// Construir consulta con placeholders seguros
$where_conditions = ["pt.stock > 0"];
$params = [];

if ($genero > 0) {
    $where_conditions[] = "p.id_genero = ?";
    $params[] = $genero;
}

if ($uso > 0) {
    $where_conditions[] = "p.id_uso = ?";
    $params[] = $uso;
}

if ($deporte > 0) {
    $where_conditions[] = "p.id_deporte = ?";
    $params[] = $deporte;
}

// Consulta con JOINs para obtener nombres de categorías
$sql = "SELECT DISTINCT 
            p.id_producto, 
            p.nombre, 
            p.precio, 
            p.imagen_url,
            p.marca,
            g.nombre AS genero_nombre,
            u.nombre AS uso_nombre,
            d.nombre AS deporte_nombre
        FROM productos p
        JOIN producto_tallas pt ON p.id_producto = pt.id_producto
        LEFT JOIN generos g ON p.id_genero = g.id_genero
        LEFT JOIN usos u ON p.id_uso = u.id_uso
        LEFT JOIN deportes d ON p.id_deporte = d.id_deporte
        WHERE " . implode(" AND ", $where_conditions) . "
        ORDER BY p.nombre";

// Ejecutar consulta de forma segura
$productos = ejecutarSQL("select", $sql, $params);

// Obtener nombres de categorías para el título
$titulo_partes = [];
if ($genero > 0) {
    $nombres_generos = [1 => 'Hombre', 2 => 'Mujer', 3 => 'Niños'];
    $titulo_partes[] = $nombres_generos[$genero];
}
if ($uso > 0) {
    $nombres_usos = [1 => 'Ropa', 2 => 'Calzado', 3 => 'Accesorios'];
    $titulo_partes[] = $nombres_usos[$uso];
}
if ($deporte > 0) {
    $nombres_deportes = [1 => 'Fútbol', 2 => 'Running', 3 => 'General', 4 => 'Básquetbol'];
    $titulo_partes[] = $nombres_deportes[$deporte];
}

$titulo = count($titulo_partes) > 0 ? implode(" - ", $titulo_partes) : "Todos los Productos";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titulo) ?> - JERSEYKING</title>
    <link rel="stylesheet" href="prueba.css">
</head>
<body>

<!-- Header igual que en PROYECTO.php -->
<header class="header">
    <nav class="navbar">
        <div class="logo"><a href="PROYECTO.php">JERSEYKING</a></div>
        <ul class="nav-links">
            <li><a href="PROYECTO.php">Inicio</a></li>
            <!-- Aquí puedes copiar el mismo menú de PROYECTO.php -->
        </ul>
        <div class="nav-icons">
            <input type="text" placeholder="Buscar...">
            <span>🔍</span>
            <span>🛒</span>
            <?php if (isset($_SESSION['nombre']) && isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
                <span>👤 <?php echo htmlspecialchars($_SESSION['nombre']); ?></span>
                <a href="logout.php" style="color:white;">Cerrar sesión</a>
            <?php else: ?>
                <a href="PROYECTO.php#login" style="color:white;">👤 Iniciar sesión</a>
            <?php endif; ?>
        </div>
    </nav>
</header>

<!-- Breadcrumb/Navegación -->
<div class="breadcrumb">
    <a href="PROYECTO.php">Inicio</a>
    <?php foreach($titulo_partes as $parte): ?>
        <span> > <?= htmlspecialchars($parte) ?></span>
    <?php endforeach; ?>
</div>

<!-- Lista de Productos -->
<section class="productos-lista">
    <h2><?= htmlspecialchars($titulo) ?></h2>
    

    
    <!-- Productos -->
    <?php if ($productos && count($productos) > 0): ?>
        <div class="productos-grid">
            <?php foreach ($productos as $producto): ?>
                <div class="producto-card">
                    <div class="producto-imagen-container">
                        <img src="<?= htmlspecialchars($producto->imagen_url) ?>" 
                             alt="<?= htmlspecialchars($producto->nombre) ?>"
                             class="producto-imagen">
                    </div>
                    
                    <div class="producto-info">
                        <!-- Marca pequeña arriba -->
                        <div class="producto-marca"><?= htmlspecialchars($producto->marca) ?></div>
                        
                        <!-- Título del producto -->
                        <h3 class="producto-titulo">
                            <a href="Producto.php?id=<?= $producto->id_producto ?>">
                                <?= htmlspecialchars($producto->nombre) ?>
                            </a>
                        </h3>
                        
                        <!-- Precio prominente -->
                        <div class="producto-precio">$<?= number_format($producto->precio, 2) ?></div>
                        
                        <!-- Categorías discretas -->
                        <div class="producto-categorias">
                            <?= htmlspecialchars($producto->genero_nombre) ?> • 
                            <?= htmlspecialchars($producto->uso_nombre) ?> • 
                            <?= htmlspecialchars($producto->deporte_nombre) ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Información de resultados -->
        <div class="resultados-info">
            <p>Mostrando <?= count($productos) ?> producto(s)</p>
        </div>
        
    <?php else: ?>
        <div class="no-productos">
            <h3>No hay productos disponibles</h3>
            <p>No encontramos productos que coincidan con los filtros seleccionados.</p>
            <a href="productos.php" class="btn-volver">Ver todos los productos</a>
        </div>
    <?php endif; ?>
</section>

<!-- CSS adicional para los nuevos elementos -->
<style>
.breadcrumb {
    padding: 10px 20px;
    background: #f8f9fa;
    font-size: 14px;
}

.filtros-activos {
    background: #e7f3ff;
    padding: 15px;
    margin: 20px 0;
    border-radius: 5px;
}

.filtro-tag {
    background: #007bff;
    color: white;
    padding: 3px 8px;
    border-radius: 3px;
    margin: 0 5px;
    font-size: 12px;
}

.limpiar-filtros {
    color: #dc3545;
    text-decoration: none;
    font-weight: bold;
}

.productos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 25px;
    padding: 20px 0;
}

/* Estilo de las cards de productos - inspirado en Sportline */
.producto-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    cursor: pointer;
    position: relative;
}

.producto-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.producto-imagen-container {
    width: 100%;
    height: 280px;
    position: relative;
    overflow: hidden;
    background: #f8f9fa;
}

.producto-imagen {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.producto-card:hover .producto-imagen {
    transform: scale(1.05);
}

.producto-info {
    padding: 20px;
    text-align: left;
}

.producto-marca {
    font-size: 12px;
    color: #666;
    text-transform: uppercase;
    font-weight: 500;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
}

.producto-titulo {
    margin: 0 0 12px 0;
    font-size: 16px;
    font-weight: 600;
    line-height: 1.3;
    color: #000;
}

.producto-titulo a {
    text-decoration: none;
    color: inherit;
    display: block;
}

.producto-titulo a:hover {
    color: #007bff;
}

.producto-precio {
    font-size: 20px;
    font-weight: bold;
    color: #000;
    margin-bottom: 10px;
}

.producto-categorias {
    font-size: 12px;
    color: #888;
    margin-top: auto;
}

.no-productos {
    text-align: center;
    padding: 40px;
    background: #f8f9fa;
    border-radius: 8px;
    margin: 20px 0;
}

.btn-volver {
    display: inline-block;
    background: #007bff;
    color: white;
    padding: 10px 20px;
    text-decoration: none;
    border-radius: 5px;
    margin-top: 15px;
}

.resultados-info {
    text-align: center;
    padding: 20px;
    color: #666;
}
</style>

</body>
</html>