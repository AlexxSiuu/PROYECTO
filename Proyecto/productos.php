<?php
session_start();
include('conexion.php');

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

// Obtener datos del usuario logueado para el dropdown
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true && isset($_SESSION['id_usuario'])) {
    $sqlUsuario = "
        SELECT nombre, correo, telefono, direccion 
        FROM usuarios 
        WHERE id_usuario = ?
    ";
    $datosUsuario = ejecutarSQL("select", $sqlUsuario, [$_SESSION['id_usuario']]);
    $usuario = $datosUsuario ? $datosUsuario[0] : null;
}

// Obtener y validar filtros desde URL
$genero = isset($_GET['genero']) ? intval($_GET['genero']) : 0;
$uso = isset($_GET['uso']) ? intval($_GET['uso']) : 0;
$deporte = isset($_GET['deporte']) ? intval($_GET['deporte']) : 0;

// revisa que los id existan en la Base de datos
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

<!-- Header completo igual que en PROYECTO.php -->
<header class="header">
    <nav class="navbar">
        <div class="logo">
            <a href="PROYECTO.php">
                <img src="img/logo_icono.ico.jpg" class="logo-img">
            </a>
        </div>
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

            <!-- USUARIO CON DROPDOWN ESTILO APPLE -->
            <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
            <div class="user-container">
                <div class="user-icon" id="user-icon">
                    <div class="user-avatar">
                        <?php 
                        $iniciales = '';
                        if (isset($usuario->nombre)) {
                            $nombres = explode(' ', trim($usuario->nombre));
                            $iniciales = strtoupper(substr($nombres[0], 0, 1));
                            if (count($nombres) > 1) {
                                $iniciales .= strtoupper(substr($nombres[1], 0, 1));
                            }
                        }
                        echo $iniciales;
                        ?>
                    </div>
                    <svg class="dropdown-arrow" fill="none" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"></path>
                    </svg>
                </div>

                <!-- DROPDOWN ESTILO APPLE -->
                <div id="user-dropdown-apple" class="user-dropdown-apple">
                    <div class="dropdown-content-apple">
                        <!-- Header con avatar y saludo -->
                        <div class="dropdown-header-apple">
                            <div class="user-avatar-large">
                                <?php echo $iniciales; ?>
                            </div>
                            <div class="user-greeting">
                                <h3>Hola, <?php echo isset($usuario->nombre) ? explode(' ', $usuario->nombre)[0] : 'Usuario'; ?></h3>
                                <p>Miembro de JERSEYKING</p>
                            </div>
                        </div>

                        <!-- Información del usuario -->
                        <div class="user-info-section">
                            <div class="info-group">
                                <div class="info-label">Información Personal</div>
                                <div class="info-item">
                                    <div class="info-icon">
                                        <svg fill="none" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.1a7.5 7.5 0 0 1 15 0A17.357 17.357 0 0 1 12 21.75c-2.993 0-5.7-.694-8.25-1.89Z" />
                                        </svg>
                                    </div>
                                    <div class="info-text">
                                        <span class="info-title">Nombre completo</span>
                                        <span class="info-value"><?php echo isset($usuario->nombre) ? htmlspecialchars($usuario->nombre) : 'No definido'; ?></span>
                                    </div>
                                </div>

                                <div class="info-item">
                                    <div class="info-icon">
                                        <svg fill="none" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 21.75 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                                        </svg>
                                    </div>
                                    <div class="info-text">
                                        <span class="info-title">Correo electrónico</span>
                                        <span class="info-value"><?php echo isset($usuario->correo) ? htmlspecialchars($usuario->correo) : 'No definido'; ?></span>
                                    </div>
                                </div>

                                <div class="info-item">
                                    <div class="info-icon">
                                        <svg fill="none" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                                        </svg>
                                    </div>
                                    <div class="info-text">
                                        <span class="info-title">Teléfono</span>
                                        <span class="info-value"><?php echo isset($usuario->telefono) ? htmlspecialchars($usuario->telefono) : 'No definido'; ?></span>
                                    </div>
                                </div>

                                <div class="info-item">
                                    <div class="info-icon">
                                        <svg fill="none" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                                        </svg>
                                    </div>
                                    <div class="info-text">
                                        <span class="info-title">Dirección</span>
                                        <span class="info-value"><?php echo isset($usuario->direccion) ? htmlspecialchars($usuario->direccion) : 'No definida'; ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Acciones rápidas -->
                        <div class="quick-actions">
                            <a href="mi-cuenta.php" class="action-item">
                                <div class="action-icon">
                                    <svg fill="none" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a6.759 6.759 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                    </svg>
                                </div>
                                <span>Configurar cuenta</span>
                            </a>

                            <a href="mis-pedidos.php" class="action-item">
                                <div class="action-icon">
                                    <svg fill="none" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 1-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m6 4.5v-3.75m0 0V8.25m0 2.25h10.5V6.375a1.125 1.125 0 0 0-1.125-1.125H9.75a1.125 1.125 0 0 0-1.125 1.125v3.75Z" />
                                    </svg>
                                </div>
                                <span>Mis pedidos</span>
                            </a>
                        </div>

                        <!-- Botón cerrar sesión -->
                        <div class="logout-section">
                            <a href="logout.php" class="logout-btn-apple">
                                <div class="logout-icon">
                                    <svg fill="none" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                                    </svg>
                                </div>
                                <span>Cerrar sesión</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <span id="login-icon" style="cursor:pointer; color:white;">
                <svg fill="none" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" style="width:1em; height:1em; vertical-align:middle;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.1a7.5 7.5 0 0 1 15 0A18 18 0 0 1 12 21.8c-2.7 0-5.2-.6-7.5-1.7Z" />
                </svg>
                Iniciar sesión
            </span>
            <?php endif; ?>
        </div>
    </nav>
</header>

<div id="notification-container"></div>

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
                <input type="email" name="correo" placeholder="Correo electrónico" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px; box-sizing:border-box;">
            </div>
            <div style="margin-bottom:15px;">
                <input type="password" name="contrasena" placeholder="Contraseña" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px; box-sizing:border-box;">
            </div>
            <input type="submit" value="Iniciar sesión" style="width:100%; padding:10px; background:#007bff; color:white; border:none; border-radius:4px; cursor:pointer;">
        </form>
        <p style="text-align:center; margin-top:15px;">
            ¿No tienes cuenta? <a href="registro.php" style="color:#007bff;">Regístrate</a>
        </p>
    </div>
</div>

<!-- Breadcrumb y filtros activos -->
<div class="breadcrumb">
    
    <?php foreach($titulo_partes as $parte): ?>
        <span> > <?= htmlspecialchars($parte) ?></span>
    <?php endforeach; ?>
</div>


<!-- Lista de Productos -->
<section class="productos-lista">
   
    
    <!-- Productos -->
    <?php if ($productos && count($productos) > 0): ?>
        <div class="productos-grid">
            <?php foreach ($productos as $producto): ?>
                <!-- Hacemos que toda la card sea clickeable -->
                <a href="Producto.php?id=<?= $producto->id_producto ?>" class="producto-card">
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
                            <?= htmlspecialchars($producto->nombre) ?>
                        </h3>
                        
                        <!-- Precio prominente -->
                        <div class="producto-precio">$<?= number_format($producto->precio, 2) ?></div>
                        
                        <!-- 🔥 Eliminado el bloque de categorías -->
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
        
        
        
    <?php else: ?>
        <div class="no-productos">
            <h3>No hay productos disponibles</h3>
            <p>No encontramos productos que coincidan con los filtros seleccionados.</p>
            <a href="productos.php" class="btn-volver">Ver todos los productos</a>
        </div>
    <?php endif; ?>
</section>


<!-- CSS COMPLETO -->
<style>
    .producto-card {
    display: flex;
    flex-direction: column;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow: hidden;
    text-decoration: none; /* quita subrayado del enlace */
    color: inherit; /* mantiene el color de texto */
    transition: transform 0.2s ease;
}

.producto-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
    .productos-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 25px;
    padding: 20px 0;
    max-width: 1200px;
    margin: 0 auto;
}
/* ===== NOTIFICACIONES ===== */
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

/* ===== MODAL LOGIN ===== */
.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0,0,0,0.6);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.modal-content input[type="submit"]:hover {
    background: #0056b3;
}

/* ===== BREADCRUMB Y FILTROS ===== */
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

/* ===== PRODUCTOS GRID ===== */
.productos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 25px;
    padding: 20px 0;
}

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

/* ===== DROPDOWN USUARIO ESTILO APPLE ===== */
.user-container {
    position: relative;
}

.user-icon {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    padding: 6px 12px;
    border-radius: 20px;
    transition: all 0.2s ease;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.user-icon:hover {
    background: rgba(255, 255, 255, 0.15);
    transform: translateY(-1px);
}

.user-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 0.5px;
}

.dropdown-arrow {
    width: 16px;
    height: 16px;
    color: white;
    transition: transform 0.2s ease;
}

.user-icon.active .dropdown-arrow {
    transform: rotate(180deg);
}

.user-dropdown-apple {
    position: absolute;
    top: calc(100% + 12px);
    right: 0;
    width: 420px;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(0, 0, 0, 0.1);
    border-radius: 20px;
    box-shadow: 
        0 8px 32px rgba(0, 0, 0, 0.12),
        0 2px 8px rgba(0, 0, 0, 0.08);
    z-index: 10000;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px) scale(0.95);
    transition: all 0.3s cubic-bezier(0.4, 0.0, 0.2, 1);
    overflow: hidden;
}

.user-dropdown-apple.show {
    opacity: 1;
    visibility: visible;
    transform: translateY(0) scale(1);
}

.dropdown-content-apple {
    padding: 0;
}

.dropdown-header-apple {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 24px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-bottom: 1px solid rgba(0, 0, 0, 0.08);
}

.user-avatar-large {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    font-weight: 700;
    letter-spacing: 0.5px;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.user-greeting h3 {
    margin: 0 0 4px 0;
    color: #1d1d1f;
    font-size: 18px;
    font-weight: 600;
}

.user-greeting p {
    margin: 0;
    color: #86868b;
    font-size: 14px;
}

.user-info-section {
    padding: 24px;
}

.info-group .info-label {
    font-size: 13px;
    font-weight: 600;
    color: #86868b;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    margin-bottom: 16px;
}

.info-item {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding: 12px 0;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.info-item:last-child {
    border-bottom: none;
}

.info-icon {
    width: 20px;
    height: 20px;
    color: #86868b;
    flex-shrink: 0;
    margin-top: 2px;
}

.info-icon svg {
    width: 100%;
    height: 100%;
}

.info-text {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.info-title {
    font-size: 12px;
    font-weight: 500;
    color: #86868b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.info-value {
    font-size: 15px;
    font-weight: 400;
    color: #1d1d1f;
    line-height: 1.3;
}

.quick-actions {
    padding: 16px 24px;
    background: rgba(248, 249, 250, 0.5);
    border-top: 1px solid rgba(0, 0, 0, 0.05);
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.action-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 0;
    color: #1d1d1f;
    text-decoration: none;
    font-size: 15px;
    font-weight: 400;
    transition: all 0.2s ease;
    border-radius: 8px;
}

.action-item:hover {
    color: #0071e3;
    background: rgba(0, 113, 227, 0.05);
    padding-left: 8px;
    padding-right: 8px;
}

.action-icon {
    width: 18px;
    height: 18px;
    color: #86868b;
}

.action-icon svg {
    width: 100%;
    height: 100%;
}

.logout-section {
    padding: 20px 24px;
}

.logout-btn-apple {
    display: flex;
    align-items: center;
    gap: 12px;
    width: 100%;
    padding: 12px 16px;
    background: linear-gradient(135deg, #ff3b30 0%, #ff2d92 100%);
    color: white;
    text-decoration: none;
    border-radius: 12px;
    font-size: 15px;
    font-weight: 600;
    transition: all 0.2s ease;
    text-align: center;
    justify-content: center;
}

.logout-btn-apple:hover {
    background: linear-gradient(135deg, #ff2d20 0%, #ff1d82 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 16px rgba(255, 59, 48, 0.3);
    color: white;
}

.logout-icon {
    width: 18px;
    height: 18px;
}

.logout-icon svg {
    width: 100%;
    height: 100%;
}

/* Responsive */
@media (max-width: 768px) {
    .cart-dropdown {
        width: 320px;
        right: -20px;
    }
    
    .user-dropdown-apple {
        width: 360px;
        right: -20px;
    }
    
    .productos-grid {
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
    }
}

@media (max-width: 480px) {
    .user-dropdown-apple {
        width: calc(100vw - 40px);
        right: -10px;
    }
    
    .dropdown-header-apple {
        padding: 20px;
    }
    
    .user-info-section {
        padding: 20px;
    }
}
</style>

<!-- JavaScript completo -->
<script>
// Sistema de notificaciones elegante
function mostrarNotificacion(mensaje, tipo = 'info', titulo = null) {
    const container = document.getElementById('notification-container');
    
    const iconos = {
        success: '✅',
        error: '❌',
        warning: '⚠️',
        info: 'ℹ️'
    };
    
    const titulos = {
        success: titulo || 'Éxito',
        error: titulo || 'Error',
        warning: titulo || 'Advertencia',
        info: titulo || 'Información'
    };
    
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
    
    setTimeout(() => {
        notification.classList.add('show');
    }, 100);
    
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

document.addEventListener("DOMContentLoaded", function() {
    // MODAL LOGIN
    const userIcon = document.getElementById("login-icon");
    const modal = document.getElementById("loginModal");
    const closeBtn = document.getElementById("closeLogin");

    <?php if (!isset($_SESSION['nombre']) || !isset($_SESSION['loggedin'])): ?>
    if (userIcon && modal && closeBtn) {
        userIcon.addEventListener("click", () => {
            modal.style.display = "flex";
        });

        closeBtn.addEventListener("click", () => {
            modal.style.display = "none";
            if (window.location.search.includes('error=')) {
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        });

        modal.addEventListener("click", (e) => {
            if (e.target === modal) {
                modal.style.display = "none";
                if (window.location.search.includes('error=')) {
                    window.history.replaceState({}, document.title, window.location.pathname);
                }
            }
        });
    }

    <?php if (isset($_GET['error'])): ?>
    if (modal) {
        modal.style.display = "flex";
    }
    <?php endif; ?>
    <?php endif; ?>

    // DROPDOWN USUARIO ESTILO APPLE
    const userIcon2 = document.getElementById('user-icon');
    const dropdown = document.getElementById('user-dropdown-apple');
    
    if (userIcon2 && dropdown) {
        userIcon2.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            userIcon2.classList.toggle('active');
            dropdown.classList.toggle('show');
        });
        
        document.addEventListener('click', function(e) {
            if (!userIcon2.contains(e.target) && !dropdown.contains(e.target)) {
                userIcon2.classList.remove('active');
                dropdown.classList.remove('show');
            }
        });
        
        dropdown.addEventListener('click', function(e) {
            e.stopPropagation();
        });
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && dropdown.classList.contains('show')) {
                userIcon2.classList.remove('active');
                dropdown.classList.remove('show');
            }
        });
    }
});

// CARRITO
let carritoAbierto = false;

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
            document.getElementById('cart-content').innerHTML = '<div class="cart-empty"><p>Error al cargar el carrito</p></div>';
        });
}

function actualizarVistaCarrito(data) {
    const cartContent = document.getElementById('cart-content');
    const cartFooter = document.getElementById('cart-footer');
    const cartCount = document.getElementById('cart-count');
    const cartTotal = document.getElementById('cart-total-amount');

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
            cargarCarrito();
            actualizarContadorCarrito(data.cart_count);
            mostrarNotificacion(data.message, data.type || 'success');
        } else {
            mostrarNotificacion(data.message, data.type || 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarNotificacion('Error de conexión', 'error');
    });
}

function eliminarDelCarrito(idCarrito) {
    mostrarConfirmacion(
        '¿Seguro que deseas eliminar este producto de tu carrito?',
        'Confirmar eliminación',
        () => {
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
                    mostrarNotificacion(data.message, data.type || 'success');
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

function actualizarContadorCarrito(count) {
    const cartCount = document.getElementById('cart-count');
    if (count > 0) {
        cartCount.textContent = count;
        cartCount.classList.add('show');
    } else {
        cartCount.classList.remove('show');
    }
}

function mostrarLoginRequerido() {
    document.getElementById('cart-content').innerHTML = `
        <div class="cart-empty">
            <p>Inicia sesión para ver tu carrito</p>
            <small>
                <a href="#" onclick="document.getElementById('login-icon').click(); toggleCarrito();" style="color: #007bff; text-decoration: underline;">
                    Hacer clic aquí para iniciar sesión
                </a>
            </small>
        </div>`;
}

function mostrarConfirmacion(mensaje, titulo, onConfirm, onCancel = null) {
    const modalHTML = `
        <div class="confirm-modal" id="confirmModal" style="
            position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
            background: rgba(0,0,0,0.7); display: flex; align-items: center; justify-content: center; z-index: 10000;
        ">
            <div style="
                background: white; border-radius: 15px; max-width: 400px; width: 90%;
                box-shadow: 0 10px 40px rgba(0,0,0,0.3); overflow: hidden; animation: slideIn 0.3s ease;
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
                    padding: 20px 25px; background: #f8f9fa; display: flex; gap: 15px; justify-content: center;
                ">
                    <button onclick="cerrarConfirmacion(false)" style="
                        background: white; border: 2px solid #6c757d; color: #6c757d;
                        padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 600; transition: all 0.3s ease;
                    ">Cancelar</button>
                    <button onclick="cerrarConfirmacion(true)" style="
                        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); border: 2px solid #dc3545; color: white;
                        padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 600; transition: all 0.3s ease;
                    ">Confirmar</button>
                </div>
            </div>
        </div>`;
    
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

// Cerrar carrito al hacer clic fuera
document.addEventListener('click', function(e) {
    const cartContainer = document.querySelector('.cart-container');
    if (carritoAbierto && !cartContainer.contains(e.target)) {
        toggleCarrito();
    }
});

// Cargar contador inicial del carrito al cargar la página
<?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) { ?>
fetch('carrito_ajax.php')
    .then(response => response.json())
    .then(data => {
        if (data.success && data.total_items > 0) {
            actualizarContadorCarrito(data.total_items);
        }
    })
<?php } ?>

</script>

</body>
</html>