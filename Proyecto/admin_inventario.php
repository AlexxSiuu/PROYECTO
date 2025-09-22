<?php
session_start();
include 'conexion.php';

// Verificar que sea admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['correo'] !== "admin@tienda.com") {
    header("Location: PROYECTO.php");
    exit();
}

// Función para detectar categoría (mejorada - igual que en productos)
function detectarCategoria($nombre, $descripcion = '', $marca = '') {
    $texto = strtolower($nombre . ' ' . $descripcion . ' ' . $marca);
    
    // Calzado - MEJORADO con más patrones específicos
    if (preg_match('/\b(zapato|tennis|tenis|zapatilla|bota|sandalia|calzado|shoe|shoes)\b/i', $texto) ||
        preg_match('/\b(air|max|force|jordan|slides|mercurial|vapor|cloudfoam|copa|mundial|stan|smith)\b/i', $texto) ||
        preg_match('/\b(runner|running|deportivo|futbol|soccer|basketball|skate)\b/i', $texto) ||
        preg_match('/\b(adidas.*pure|adidas.*copa|adidas.*stan|nike.*air|nike.*jordan|nike.*mercurial)\b/i', $texto) ||
        preg_match('/\b(puma.*suede|puma.*rs|converse|vans|new.*balance|reebok)\b/i', $texto)) {
        return 'calzado';
    }
    
    if (preg_match('/\b(camiseta|camisa|polo|playera|blusa|top|jersey|shirt|tee|dri.?fit|pro)\b/i', $texto)) {
        return 'camisetas';
    }
    
    if (preg_match('/\b(pantalon|short|bermuda|leggin|jogger|pants|tiro|deportivo)\b/i', $texto)) {
        return 'pantalones';
    }
    
    if (preg_match('/\b(sudadera|hoodie|capucha|buzo|chaqueta|jacket|chamarra)\b/i', $texto)) {
        return 'sudaderas';
    }
    
    if (preg_match('/\b(gorra|cap|sombrero|balon|pelota|guante|reloj|banda|accesorio|mochila|bolsa|bag)\b/i', $texto)) {
        return 'accesorios';
    }
    
    return 'general';
}

// Función para obtener tallas específicas por categoría
function obtenerTallasEspecificas($categoria) {
    $tallas_por_categoria = [
        'calzado' => ['23', '24', '25', '26', '27', '28', '29', '30', '31', '32', '33'],
        'camisetas' => ['XS', 'S', 'M', 'L', 'XL', 'XXL'],
        'pantalones' => ['XS', 'S', 'M', 'L', 'XL', 'XXL'],
        'sudaderas' => ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'],
        'accesorios' => ['ÚNICA'],
        'general' => ['XS', 'S', 'M', 'L', 'XL', 'XXL']
    ];
    
    return $tallas_por_categoria[$categoria] ?? $tallas_por_categoria['general'];
}

function ejecutarSQL($tipoSentencia, $sentenciaSQL, $params = []) {
    global $conexion;
    
    $stmt = $conexion->prepare($sentenciaSQL);
    if (!$stmt) {
        return false;
    }
    
    if (!empty($params)) {
        $types = str_repeat('s', count($params));
        foreach ($params as $i => $param) {
            if (is_int($param)) $types[$i] = 'i';
            if (is_float($param)) $types[$i] = 'd';
        }
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    
    if (strtolower($tipoSentencia) == "select") {
        $result = $stmt->get_result();
        $datos = [];
        while ($fila = $result->fetch_assoc()) {
            $datos[] = $fila;
        }
        $stmt->close();
        return $datos;
    } else {
        $success = $stmt->affected_rows >= 0;
        $stmt->close();
        return $success;
    }
}

$mensaje = "";
$error = "";

// Procesar actualizaciones de stock
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    
    if ($accion === 'actualizar_stock') {
        $id_producto = intval($_POST['id_producto']);
        $stocks = $_POST['stock'] ?? [];
        
        $actualizaciones_exitosas = 0;
        
        foreach ($stocks as $id_talla => $nuevo_stock) {
            $nuevo_stock = intval($nuevo_stock);
            if ($nuevo_stock < 0) $nuevo_stock = 0;
            
            // Verificar si ya existe el registro
            $existe = ejecutarSQL("select", "SELECT 1 FROM producto_tallas WHERE id_producto = ? AND id_talla = ?", [$id_producto, $id_talla]);
            
            if (!empty($existe)) {
                // Actualizar stock existente
                $resultado = ejecutarSQL("update", "UPDATE producto_tallas SET stock = ? WHERE id_producto = ? AND id_talla = ?", [$nuevo_stock, $id_producto, $id_talla]);
            } else {
                // Insertar nuevo registro
                $resultado = ejecutarSQL("insert", "INSERT INTO producto_tallas (id_producto, id_talla, stock) VALUES (?, ?, ?)", [$id_producto, $id_talla, $nuevo_stock]);
            }
            
            if ($resultado) {
                $actualizaciones_exitosas++;
            }
        }
        
        if ($actualizaciones_exitosas > 0) {
            $mensaje = "Stock actualizado correctamente para $actualizaciones_exitosas tallas";
        } else {
            $error = "No se pudo actualizar el stock";
        }
    }
    
    if ($accion === 'agregar_stock_masivo') {
        $id_producto = intval($_POST['id_producto']);
        $cantidad_agregar = intval($_POST['cantidad_agregar']);
        
        if ($cantidad_agregar > 0) {
            $actualizaciones_exitosas = 0;
            
            // Detectar categoría del producto para obtener solo sus tallas específicas
            $producto_info = ejecutarSQL("select", "SELECT nombre, descripcion, marca FROM productos WHERE id_producto = ?", [$id_producto]);
            if (!empty($producto_info)) {
                $producto_data = $producto_info[0];
                $categoria = detectarCategoria($producto_data['nombre'], $producto_data['descripcion'], $producto_data['marca']);
                $tallas_especificas = obtenerTallasEspecificas($categoria);
                
                // Obtener IDs de las tallas específicas de esta categoría
                $tallas_ids = [];
                foreach ($tallas_especificas as $talla_nombre) {
                    $talla_info = ejecutarSQL("select", "SELECT id_talla FROM tallas WHERE talla = ?", [$talla_nombre]);
                    if (!empty($talla_info)) {
                        $tallas_ids[] = $talla_info[0]['id_talla'];
                    }
                }
                
                // Agregar stock solo a las tallas específicas de esta categoría
                foreach ($tallas_ids as $id_talla) {
                    // Verificar si ya existe el registro
                    $existe = ejecutarSQL("select", "SELECT stock FROM producto_tallas WHERE id_producto = ? AND id_talla = ?", [$id_producto, $id_talla]);
                    
                    if (!empty($existe)) {
                        // Actualizar stock existente
                        $resultado = ejecutarSQL("update", "UPDATE producto_tallas SET stock = stock + ? WHERE id_producto = ? AND id_talla = ?", [$cantidad_agregar, $id_producto, $id_talla]);
                    } else {
                        // Insertar nuevo registro
                        $resultado = ejecutarSQL("insert", "INSERT INTO producto_tallas (id_producto, id_talla, stock) VALUES (?, ?, ?)", [$id_producto, $id_talla, $cantidad_agregar]);
                    }
                    
                    if ($resultado) {
                        $actualizaciones_exitosas++;
                    }
                }
                
                if ($actualizaciones_exitosas > 0) {
                    $mensaje = "Se agregaron $cantidad_agregar unidades a las " . count($tallas_ids) . " tallas específicas de categoría '$categoria' ($actualizaciones_exitosas tallas actualizadas)";
                } else {
                    $error = "Error al agregar stock masivo";
                }
            } else {
                $error = "Producto no encontrado";
            }
        } else {
            $error = "Ingresa una cantidad válida para agregar";
        }
    }
}

// Filtros
$filtro_producto = $_GET['producto'] ?? '';
$filtro_marca = $_GET['marca'] ?? '';
$filtro_stock_bajo = isset($_GET['stock_bajo']);

// Construir consulta con filtros
$where_conditions = [];
$params = [];

if (!empty($filtro_producto)) {
    // Buscar por ID si es numérico, sino buscar por nombre
    if (is_numeric($filtro_producto)) {
        $where_conditions[] = "p.id_producto = ?";
        $params[] = intval($filtro_producto);
    } else {
        $where_conditions[] = "p.nombre LIKE ?";
        $params[] = "%$filtro_producto%";
    }
}

if (!empty($filtro_marca)) {
    $where_conditions[] = "p.marca LIKE ?";
    $params[] = "%$filtro_marca%";
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Consulta mejorada: solo obtener tallas específicas para cada producto según su categoría
$sql_inventario = "
    SELECT DISTINCT
        p.id_producto,
        p.nombre,
        p.marca,
        p.precio,
        p.imagen_url,
        p.descripcion,
        g.nombre as genero,
        u.nombre as uso,
        d.nombre as deporte
    FROM productos p
    LEFT JOIN generos g ON p.id_genero = g.id_genero
    LEFT JOIN usos u ON p.id_uso = u.id_uso
    LEFT JOIN deportes d ON p.id_deporte = d.id_deporte
    $where_clause
    ORDER BY p.nombre
";

$productos_base = ejecutarSQL("select", $sql_inventario, $params);

// Agrupar productos con sus tallas específicas
$productos_agrupados = [];
foreach ($productos_base as $producto) {
    $id_producto = $producto['id_producto'];
    
    // Detectar categoría y obtener tallas específicas
    $categoria = detectarCategoria($producto['nombre'], $producto['descripcion'], $producto['marca']);
    $tallas_especificas = obtenerTallasEspecificas($categoria);
    
    // Inicializar estructura del producto
    $productos_agrupados[$id_producto] = [
        'info' => [
            'id_producto' => $producto['id_producto'],
            'nombre' => $producto['nombre'],
            'marca' => $producto['marca'],
            'precio' => $producto['precio'],
            'imagen_url' => $producto['imagen_url'],
            'genero' => $producto['genero'],
            'uso' => $producto['uso'],
            'deporte' => $producto['deporte'],
            'categoria' => $categoria
        ],
        'tallas' => [],
        'stock_total' => 0
    ];
    
    // Obtener stock para cada talla específica de esta categoría
    foreach ($tallas_especificas as $talla_nombre) {
        // Obtener ID de la talla
        $talla_info = ejecutarSQL("select", "SELECT id_talla FROM tallas WHERE talla = ?", [$talla_nombre]);
        if (!empty($talla_info)) {
            $id_talla = $talla_info[0]['id_talla'];
            
            // Obtener stock actual para esta talla y producto
            $stock_info = ejecutarSQL("select", "SELECT stock FROM producto_tallas WHERE id_producto = ? AND id_talla = ?", [$id_producto, $id_talla]);
            $stock_actual = !empty($stock_info) ? intval($stock_info[0]['stock']) : 0;
            
            $productos_agrupados[$id_producto]['tallas'][$id_talla] = [
                'talla' => $talla_nombre,
                'stock' => $stock_actual
            ];
            
            $productos_agrupados[$id_producto]['stock_total'] += $stock_actual;
        }
    }
}

// Filtrar productos con stock bajo si se solicita
if ($filtro_stock_bajo) {
    $productos_agrupados = array_filter($productos_agrupados, function($producto) {
        return $producto['stock_total'] <= 10;
    });
}

// Obtener lista de marcas para el filtro
$marcas = ejecutarSQL("select", "SELECT DISTINCT marca FROM productos ORDER BY marca");

// Obtener todas las tallas
$tallas = ejecutarSQL("select", "SELECT * FROM tallas ORDER BY talla");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control de Inventario - Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }
        
        .container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .header h1 {
            color: #2c3e50;
            font-size: 28px;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .nav-links a {
            color: #667eea;
            text-decoration: none;
            margin: 0 15px;
            padding: 12px 24px;
            border-radius: 25px;
            background: rgba(102, 126, 234, 0.1);
            transition: all 0.3s ease;
            font-weight: 600;
            border: 2px solid transparent;
            backdrop-filter: blur(10px);
        }
        
        .nav-links a:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
            border-color: rgba(255, 255, 255, 0.2);
        }
        
        .filters {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
            margin-bottom: 30px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        
        .filter-group label {
            margin-bottom: 5px;
            font-weight: 600;
            color: #555;
        }
        
        .filter-group input,
        .filter-group select {
            padding: 8px 12px;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .alert {
            padding: 20px;
            margin-bottom: 25px;
            border-radius: 15px;
            font-weight: 600;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .alert.success {
            background: rgba(212, 237, 218, 0.9);
            color: #155724;
            box-shadow: 0 8px 32px rgba(40, 167, 69, 0.2);
        }
        
        .alert.error {
            background: rgba(248, 215, 218, 0.9);
            color: #721c24;
            box-shadow: 0 8px 32px rgba(220, 53, 69, 0.2);
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-block;
            text-decoration: none;
            text-align: center;
        }
        
        .btn.primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .btn.primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        
        .btn.success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }
        
        .btn.success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.4);
        }
        
        .btn.warning {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            color: #212529;
            box-shadow: 0 4px 15px rgba(255, 193, 7, 0.3);
        }
        
        .btn.warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 193, 7, 0.4);
        }
        
        .btn.small {
            padding: 4px 8px;
            font-size: 12px;
        }
        
        .product-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
            border: 2px solid transparent;
            transition: all 0.3s;
        }
        
        .product-card:hover {
            border-color: rgba(102, 126, 234, 0.3);
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }
        
        .product-card.low-stock {
            border-color: rgba(220, 53, 69, 0.3);
            background: rgba(255, 245, 245, 0.95);
        }
        
        .product-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 12px;
            border: 3px solid #e9ecef;
        }
        
        .product-image.no-image {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            color: #6c757d;
        }
        
        .product-info h3 {
            margin-bottom: 5px;
            color: #2c3e50;
            font-size: 20px;
        }
        
        .product-info p {
            color: #666;
            margin: 2px 0;
        }
        
        .product-info .price {
            font-weight: bold;
            color: #28a745;
            font-size: 18px;
        }
        
        .stock-overview {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 10px;
        }
        
        .total-stock {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .stock-status {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
        }
        
        .stock-high {
            background: #d4edda;
            color: #155724;
        }
        
        .stock-medium {
            background: #fff3cd;
            color: #856404;
        }
        
        .stock-low {
            background: #f8d7da;
            color: #721c24;
        }
        
        .tallas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .talla-item {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            transition: all 0.3s;
        }
        
        .talla-item:hover {
            border-color: #667eea;
            background: #e7f3ff;
        }
        
        .talla-item.stock-zero {
            background: #fee;
            border-color: #fcc;
        }
        
        .talla-label {
            font-weight: bold;
            margin-bottom: 8px;
            color: #2c3e50;
        }
        
        .stock-input {
            width: 100%;
            padding: 8px;
            border: 2px solid #ddd;
            border-radius: 6px;
            text-align: center;
            font-size: 16px;
            font-weight: bold;
        }
        
        .stock-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.25);
        }
        
        .actions-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .bulk-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .bulk-input {
            width: 80px;
            padding: 6px;
            border: 2px solid #ddd;
            border-radius: 4px;
            text-align: center;
        }
        
        .stats-summary {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 25px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
        }
        
        .stat-item {
            text-align: center;
            padding: 15px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 8px;
        }
        
        .stat-number {
            font-size: 28px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .header {
                flex-direction: column;
                text-align: center;
            }
            
            .filter-grid {
                grid-template-columns: 1fr;
            }
            
            .product-header {
                flex-direction: column;
                text-align: center;
            }
            
            .tallas-grid {
                grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            }
            
            .actions-bar {
                flex-direction: column;
                align-items: stretch;
            }
            
            .bulk-actions {
                justify-content: center;
            }
        }
        
        .no-products {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .no-products h3 {
            margin-bottom: 10px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Control de Inventario</h1>
            <div class="nav-links">
                <a href="dashboard.php">Dashboard</a>
                <a href="admin_productos.php">Productos</a>
                <a href="logout.php">Cerrar Sesión</a>
            </div>
        </div>

        <?php if ($mensaje): ?>
            <div class="alert success"><?php echo $mensaje; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert error"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Filtros -->
        <div class="filters">
            <form method="GET" action="">
                <div class="filter-grid">
                    <div class="filter-group">
                        <label>Buscar por producto</label>
                        <input type="text" name="producto" value="<?php echo htmlspecialchars($filtro_producto); ?>" 
                               placeholder="ID o nombre del producto">
                    </div>
                    <div class="filter-group">
                        <label>Filtrar por marca</label>
                        <select name="marca">
                            <option value="">Todas las marcas</option>
                            <?php foreach ($marcas as $marca): ?>
                                <option value="<?php echo htmlspecialchars($marca['marca']); ?>"
                                    <?php echo ($filtro_marca === $marca['marca']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($marca['marca']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <div class="checkbox-group">
                            <input type="checkbox" name="stock_bajo" id="stock_bajo" 
                                   <?php echo $filtro_stock_bajo ? 'checked' : ''; ?>>
                            <label for="stock_bajo">Solo stock bajo (≤10)</label>
                        </div>
                    </div>
                    <div class="filter-group">
                        <button type="submit" class="btn primary">Filtrar</button>
                        <a href="admin_inventario.php" class="btn warning">Limpiar</a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Estadísticas Resumen -->
        <?php if (!empty($productos_agrupados)): ?>
        <div class="stats-summary">
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number"><?php echo count($productos_agrupados); ?></div>
                    <div class="stat-label">Productos</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">
                        <?php echo array_sum(array_column($productos_agrupados, 'stock_total')); ?>
                    </div>
                    <div class="stat-label">Total Stock</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">
                        <?php echo count(array_filter($productos_agrupados, function($p) { return $p['stock_total'] <= 10; })); ?>
                    </div>
                    <div class="stat-label">Stock Bajo</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">
                        <?php echo count(array_filter($productos_agrupados, function($p) { return $p['stock_total'] == 0; })); ?>
                    </div>
                    <div class="stat-label">Sin Stock</div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Lista de Productos -->
        <?php if (empty($productos_agrupados)): ?>
            <div class="no-products">
                <h3>No se encontraron productos</h3>
                <p>Intenta ajustar los filtros o <a href="admin_productos.php">agregar nuevos productos</a></p>
            </div>
        <?php else: ?>
            <?php foreach ($productos_agrupados as $producto): ?>
                <?php
                $stock_total = $producto['stock_total'];
                $clase_stock = $stock_total <= 5 ? 'low-stock' : '';
                $status_class = $stock_total > 50 ? 'stock-high' : ($stock_total > 10 ? 'stock-medium' : 'stock-low');
                $status_text = $stock_total > 50 ? 'Stock Alto' : ($stock_total > 10 ? 'Stock Medio' : ($stock_total > 0 ? 'Stock Bajo' : 'Sin Stock'));
                ?>
                
                <div class="product-card <?php echo $clase_stock; ?>">
                    <div class="product-header">
                        <?php if ($producto['info']['imagen_url']): ?>
                            <img src="<?php echo htmlspecialchars($producto['info']['imagen_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($producto['info']['nombre']); ?>" 
                                 class="product-image">
                        <?php else: ?>
                            <div class="product-image no-image">Sin imagen</div>
                        <?php endif; ?>
                        
                        <div class="product-info">
                            <h3><?php echo htmlspecialchars($producto['info']['nombre']); ?></h3>
                            <p><strong>Marca:</strong> <?php echo htmlspecialchars($producto['info']['marca']); ?></p>
                            <p><strong>ID:</strong> <?php echo $producto['info']['id_producto']; ?></p>
                            <p><strong>Categoría:</strong> 
                                <span style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 3px 8px; border-radius: 10px; font-size: 11px; font-weight: 600; text-transform: capitalize;">
                                    <?php echo $producto['info']['categoria']; ?>
                                </span>
                            </p>
                            <p><strong>Clasificación:</strong> 
                                <?php echo htmlspecialchars($producto['info']['genero'] ?? 'N/A'); ?> | 
                                <?php echo htmlspecialchars($producto['info']['uso'] ?? 'N/A'); ?> | 
                                <?php echo htmlspecialchars($producto['info']['deporte'] ?? 'N/A'); ?>
                            </p>
                            <p class="price">$<?php echo number_format($producto['info']['precio'], 2); ?></p>
                        </div>
                    </div>
                    
                    <div class="stock-overview">
                        <div>
                            <span>Total en Stock: </span>
                            <span class="total-stock"><?php echo $stock_total; ?> unidades</span>
                        </div>
                        <div class="stock-status <?php echo $status_class; ?>">
                            <?php echo $status_text; ?>
                        </div>
                    </div>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="accion" value="actualizar_stock">
                        <input type="hidden" name="id_producto" value="<?php echo $producto['info']['id_producto']; ?>">
                        
                        <div class="tallas-grid">
                            <?php foreach ($producto['tallas'] as $id_talla => $talla_info): ?>
                                <div class="talla-item <?php echo $talla_info['stock'] == 0 ? 'stock-zero' : ''; ?>">
                                    <div class="talla-label">Talla <?php echo htmlspecialchars($talla_info['talla']); ?></div>
                                    <input type="number" 
                                           name="stock[<?php echo $id_talla; ?>]" 
                                           value="<?php echo $talla_info['stock']; ?>"
                                           min="0" 
                                           class="stock-input">
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="actions-bar">
                            <div class="bulk-actions">
                                <label>Agregar a todas las tallas:</label>
                                <input type="number" name="cantidad_agregar" min="0" value="0" class="bulk-input">
                                <button type="submit" name="accion" value="agregar_stock_masivo" class="btn warning small">
                                    + Agregar
                                </button>
                            </div>
                            <button type="submit" class="btn success">Guardar Cambios</button>
                        </div>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        // Auto-guardar cuando se cambia el stock
        document.querySelectorAll('.stock-input').forEach(input => {
            input.addEventListener('change', function() {
                if (this.value < 0) {
                    this.value = 0;
                }
            });
        });

        // Confirmation para stock masivo
        document.querySelectorAll('button[value="agregar_stock_masivo"]').forEach(btn => {
            btn.addEventListener('click', function(e) {
                const form = this.closest('form');
                const cantidadInput = form.querySelector('input[name="cantidad_agregar"]');
                const cantidad = parseInt(cantidadInput.value);
                
                if (cantidad > 0) {
                    if (!confirm(`¿Agregar ${cantidad} unidades a todas las tallas de este producto?`)) {
                        e.preventDefault();
                    }
                } else {
                    e.preventDefault();
                    alert('Ingresa una cantidad válida para agregar (mayor a 0)');
                    cantidadInput.focus();
                }
            });
        });
    </script>
</body>
</html>