<?php
session_start();
include 'conexion.php';

// Verificar que sea admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['correo'] !== "admin@tienda.com") {
    header("Location: PROYECTO.php");
    exit();
}

// Función para ejecutar consultas de manera segura
function ejecutarSQL($tipoSentencia, $sentenciaSQL, $params = []) {
    global $conexion;
    
    $stmt = $conexion->prepare($sentenciaSQL);
    if (!$stmt) {
        error_log("Error preparando consulta: " . $conexion->error);
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

// ESTADÍSTICAS PRINCIPALES
// 1. Total de productos
$totalProductos = ejecutarSQL("select", "SELECT COUNT(*) as total FROM productos")[0]['total'] ?? 0;

// 2. Total de ventas (dinero)
$ventasHoy = ejecutarSQL("select", "SELECT COALESCE(SUM(total), 0) as total FROM ventas WHERE DATE(fecha_venta) = CURDATE()")[0]['total'] ?? 0;
$ventasMes = ejecutarSQL("select", "SELECT COALESCE(SUM(total), 0) as total FROM ventas WHERE MONTH(fecha_venta) = MONTH(CURDATE()) AND YEAR(fecha_venta) = YEAR(CURDATE())")[0]['total'] ?? 0;
$ventasTotal = ejecutarSQL("select", "SELECT COALESCE(SUM(total), 0) as total FROM ventas")[0]['total'] ?? 0;

// 3. Total de pedidos
$pedidosHoy = ejecutarSQL("select", "SELECT COUNT(*) as total FROM ventas WHERE DATE(fecha_venta) = CURDATE()")[0]['total'] ?? 0;
$pedidosMes = ejecutarSQL("select", "SELECT COUNT(*) as total FROM ventas WHERE MONTH(fecha_venta) = MONTH(CURDATE()) AND YEAR(fecha_venta) = YEAR(CURDATE())")[0]['total'] ?? 0;
$pedidosTotal = ejecutarSQL("select", "SELECT COUNT(*) as total FROM ventas")[0]['total'] ?? 0;

// 4. Total de clientes registrados
$totalClientes = ejecutarSQL("select", "SELECT COUNT(*) as total FROM usuarios WHERE correo != 'admin@tienda.com'")[0]['total'] ?? 0;

// 5. Stock total y productos con stock bajo
$stockTotal = ejecutarSQL("select", "SELECT COALESCE(SUM(stock), 0) as total FROM producto_tallas")[0]['total'] ?? 0;
$productosStockBajo = ejecutarSQL("select", "
    SELECT COUNT(DISTINCT p.id_producto) as total 
    FROM productos p 
    LEFT JOIN producto_tallas pt ON p.id_producto = pt.id_producto 
    GROUP BY p.id_producto 
    HAVING COALESCE(SUM(pt.stock), 0) <= 10
")[0]['total'] ?? 0;

// 6. Productos sin stock
$productosSinStock = ejecutarSQL("select", "
    SELECT COUNT(DISTINCT p.id_producto) as total 
    FROM productos p 
    LEFT JOIN producto_tallas pt ON p.id_producto = pt.id_producto 
    GROUP BY p.id_producto 
    HAVING COALESCE(SUM(pt.stock), 0) = 0
")[0]['total'] ?? 0;

// PRODUCTOS MÁS VENDIDOS (últimos 30 días)
$productosMasVendidos = ejecutarSQL("select", "
    SELECT 
        p.nombre,
        p.marca,
        p.imagen_url,
        SUM(dv.cantidad) as total_vendido,
        SUM(dv.subtotal) as ingresos
    FROM detalle_ventas dv
    INNER JOIN productos p ON dv.id_producto = p.id_producto
    INNER JOIN ventas v ON dv.id_venta = v.id_venta
    WHERE v.fecha_venta >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY p.id_producto, p.nombre, p.marca, p.imagen_url
    ORDER BY total_vendido DESC
    LIMIT 5
");

// ÚLTIMAS VENTAS
$ultimasVentas = ejecutarSQL("select", "
    SELECT 
        v.id_venta,
        v.total,
        v.fecha_venta,
        u.nombre as cliente_nombre,
        u.correo as cliente_correo,
        v.estado,
        COUNT(dv.id_detalle) as total_productos
    FROM ventas v
    INNER JOIN usuarios u ON v.id_usuario = u.id_usuario
    LEFT JOIN detalle_ventas dv ON v.id_venta = dv.id_venta
    GROUP BY v.id_venta, v.total, v.fecha_venta, u.nombre, u.correo, v.estado
    ORDER BY v.fecha_venta DESC
    LIMIT 10
");

// VENTAS POR MES (últimos 6 meses para gráfico)
$ventasPorMes = ejecutarSQL("select", "
    SELECT 
        DATE_FORMAT(fecha_venta, '%Y-%m') as mes,
        DATE_FORMAT(fecha_venta, '%M %Y') as mes_nombre,
        COUNT(*) as total_pedidos,
        SUM(total) as total_ingresos
    FROM ventas 
    WHERE fecha_venta >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(fecha_venta, '%Y-%m')
    ORDER BY mes DESC
    LIMIT 6
");

// ALERTAS DE STOCK
$alertasStock = ejecutarSQL("select", "
    SELECT 
        p.id_producto,
        p.nombre,
        p.marca,
        p.imagen_url,
        COALESCE(SUM(pt.stock), 0) as stock_total
    FROM productos p
    LEFT JOIN producto_tallas pt ON p.id_producto = pt.id_producto
    GROUP BY p.id_producto, p.nombre, p.marca, p.imagen_url
    HAVING stock_total <= 5
    ORDER BY stock_total ASC
    LIMIT 5
");

// TOP CLIENTES (más compras)
$topClientes = ejecutarSQL("select", "
    SELECT 
        u.nombre,
        u.correo,
        COUNT(v.id_venta) as total_compras,
        SUM(v.total) as total_gastado
    FROM usuarios u
    INNER JOIN ventas v ON u.id_usuario = v.id_usuario
    WHERE u.correo != 'admin@tienda.com'
    GROUP BY u.id_usuario, u.nombre, u.correo
    ORDER BY total_compras DESC, total_gastado DESC
    LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Administrativo - Tienda Deportiva</title>
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
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }

        .header h1 {
            color: #2c3e50;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .header .welcome {
            color: #666;
            font-size: 14px;
        }

        .nav-links {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .nav-links a {
            color: #667eea;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 25px;
            background: rgba(102, 126, 234, 0.1);
            transition: all 0.3s ease;
            font-weight: 500;
            border: 2px solid transparent;
        }

        .nav-links a:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .nav-links a.logout {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }

        .nav-links a.logout:hover {
            background: #dc3545;
            color: white;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border-left: 5px solid transparent;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }

        .stat-card.primary {
            border-left-color: #667eea;
        }

        .stat-card.success {
            border-left-color: #28a745;
        }

        .stat-card.warning {
            border-left-color: #ffc107;
        }

        .stat-card.danger {
            border-left-color: #dc3545;
        }

        .stat-card.info {
            border-left-color: #17a2b8;
        }

        .stat-card.purple {
            border-left-color: #6f42c1;
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .stat-icon {
            font-size: 24px;
            padding: 12px;
            border-radius: 10px;
            color: white;
        }

        .stat-icon.primary { background: #667eea; }
        .stat-icon.success { background: #28a745; }
        .stat-icon.warning { background: #ffc107; }
        .stat-icon.danger { background: #dc3545; }
        .stat-icon.info { background: #17a2b8; }
        .stat-icon.purple { background: #6f42c1; }

        .stat-title {
            font-size: 14px;
            color: #666;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .stat-subtitle {
            font-size: 12px;
            color: #999;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .main-content {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }

        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }

        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .card h3 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 18px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }

        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #555;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .product-img {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            object-fit: cover;
        }

        .no-img {
            width: 40px;
            height: 40px;
            background: #f0f0f0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            color: #999;
        }

        .badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge.success {
            background: #d4edda;
            color: #155724;
        }

        .badge.warning {
            background: #fff3cd;
            color: #856404;
        }

        .badge.danger {
            background: #f8d7da;
            color: #721c24;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 10px;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #28a745, #20c997);
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        .chart-container {
            height: 300px;
            display: flex;
            align-items: end;
            gap: 10px;
            padding: 20px 0;
            border-bottom: 2px solid #e9ecef;
            position: relative;
        }

        .chart-bar {
            flex: 1;
            background: linear-gradient(0deg, #667eea, #764ba2);
            border-radius: 4px 4px 0 0;
            min-height: 20px;
            position: relative;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .chart-bar:hover {
            opacity: 0.8;
            transform: scaleY(1.05);
        }

        .chart-label {
            position: absolute;
            bottom: -25px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 11px;
            color: #666;
            white-space: nowrap;
        }

        .chart-value {
            position: absolute;
            top: -20px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 10px;
            color: #666;
            font-weight: bold;
            background: white;
            padding: 2px 6px;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .alert-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            border: 1px solid #ffeaa7;
            background: #fffbf0;
            border-radius: 8px;
            margin-bottom: 10px;
        }

        .alert-item.critical {
            border-color: #ff7675;
            background: #fff5f5;
        }

        @media (max-width: 1200px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }

            .header {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .nav-links {
                justify-content: center;
            }

            .chart-container {
                height: 200px;
            }
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }

        .empty-state h4 {
            margin-bottom: 10px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>Dashboard Administrativo</h1>
                <p class="welcome">Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre']); ?> | <?php echo date('d/m/Y H:i'); ?></p>
            </div>
            <div class="nav-links">
                <a href="admin_productos.php">📦 Productos</a>
                <a href="admin_inventario.php">📊 Inventario</a>
                <a href="logout.php" class="logout">🚪 Salir</a>
            </div>
        </div>

        <!-- Estadísticas Principales -->
        <div class="stats-grid">
            <div class="stat-card primary">
                <div class="stat-header">
                    <div>
                        <div class="stat-title">Ventas Hoy</div>
                        <div class="stat-value">$<?php echo number_format($ventasHoy, 2); ?></div>
                        <div class="stat-subtitle"><?php echo $pedidosHoy; ?> pedidos</div>
                    </div>
                    <div class="stat-icon primary">💰</div>
                </div>
            </div>

            <div class="stat-card success">
                <div class="stat-header">
                    <div>
                        <div class="stat-title">Ventas Este Mes</div>
                        <div class="stat-value">$<?php echo number_format($ventasMes, 2); ?></div>
                        <div class="stat-subtitle"><?php echo $pedidosMes; ?> pedidos</div>
                    </div>
                    <div class="stat-icon success">📈</div>
                </div>
            </div>

            <div class="stat-card info">
                <div class="stat-header">
                    <div>
                        <div class="stat-title">Total Productos</div>
                        <div class="stat-value"><?php echo number_format($totalProductos); ?></div>
                        <div class="stat-subtitle"><?php echo number_format($stockTotal); ?> en stock</div>
                    </div>
                    <div class="stat-icon info">📦</div>
                </div>
            </div>

            <div class="stat-card purple">
                <div class="stat-header">
                    <div>
                        <div class="stat-title">Clientes</div>
                        <div class="stat-value"><?php echo number_format($totalClientes); ?></div>
                        <div class="stat-subtitle">usuarios registrados</div>
                    </div>
                    <div class="stat-icon purple">👥</div>
                </div>
            </div>

            <div class="stat-card warning">
                <div class="stat-header">
                    <div>
                        <div class="stat-title">Stock Bajo</div>
                        <div class="stat-value"><?php echo $productosStockBajo; ?></div>
                        <div class="stat-subtitle">productos ≤ 10 unidades</div>
                    </div>
                    <div class="stat-icon warning">⚠️</div>
                </div>
            </div>

            <div class="stat-card danger">
                <div class="stat-header">
                    <div>
                        <div class="stat-title">Sin Stock</div>
                        <div class="stat-value"><?php echo $productosSinStock; ?></div>
                        <div class="stat-subtitle">productos agotados</div>
                    </div>
                    <div class="stat-icon danger">🚫</div>
                </div>
            </div>
        </div>

        <div class="content-grid">
            <div class="main-content">
                <!-- Gráfico de Ventas por Mes -->
                <div class="card">
                    <h3>📊 Ventas Últimos 6 Meses</h3>
                    <?php if (!empty($ventasPorMes)): ?>
                        <div class="chart-container">
                            <?php
                            $maxIngresos = max(array_column($ventasPorMes, 'total_ingresos'));
                            foreach (array_reverse($ventasPorMes) as $mes):
                                $altura = $maxIngresos > 0 ? (($mes['total_ingresos'] / $maxIngresos) * 100) : 0;
                            ?>
                                <div class="chart-bar" style="height: <?php echo max($altura, 5); ?>%;" 
                                     title="<?php echo $mes['mes_nombre']; ?>: $<?php echo number_format($mes['total_ingresos'], 2); ?>">
                                    <div class="chart-label"><?php echo date('M', strtotime($mes['mes'] . '-01')); ?></div>
                                    <div class="chart-value">$<?php echo number_format($mes['total_ingresos'], 0); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <h4>Sin datos de ventas</h4>
                            <p>No hay ventas registradas en los últimos meses</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Productos Más Vendidos -->
                <div class="card">
                    <h3>🏆 Productos Más Vendidos (Últimos 30 días)</h3>
                    <?php if (!empty($productosMasVendidos)): ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th>Marca</th>
                                        <th>Vendidos</th>
                                        <th>Ingresos</th>
                                        <th>Popularidad</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $maxVendido = max(array_column($productosMasVendidos, 'total_vendido'));
                                    foreach ($productosMasVendidos as $producto):
                                        $porcentaje = $maxVendido > 0 ? (($producto['total_vendido'] / $maxVendido) * 100) : 0;
                                    ?>
                                    <tr>
                                        <td style="display: flex; align-items: center; gap: 10px;">
                                            <?php if ($producto['imagen_url']): ?>
                                                <img src="<?php echo htmlspecialchars($producto['imagen_url']); ?>" 
                                                     alt="<?php echo htmlspecialchars($producto['nombre']); ?>" 
                                                     class="product-img">
                                            <?php else: ?>
                                                <div class="no-img">Sin img</div>
                                            <?php endif; ?>
                                            <strong><?php echo htmlspecialchars($producto['nombre']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($producto['marca']); ?></td>
                                        <td><strong><?php echo $producto['total_vendido']; ?></strong> unidades</td>
                                        <td><strong>$<?php echo number_format($producto['ingresos'], 2); ?></strong></td>
                                        <td>
                                            <div class="progress-bar">
                                                <div class="progress-fill" style="width: <?php echo $porcentaje; ?>%"></div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <h4>Sin ventas recientes</h4>
                            <p>No hay productos vendidos en los últimos 30 días</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Últimas Ventas -->
                <div class="card">
                    <h3>🛒 Últimas Ventas</h3>
                    <?php if (!empty($ultimasVentas)): ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>ID Venta</th>
                                        <th>Cliente</th>
                                        <th>Productos</th>
                                        <th>Total</th>
                                        <th>Estado</th>
                                        <th>Fecha</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ultimasVentas as $venta): ?>
                                    <tr>
                                        <td><strong>#<?php echo $venta['id_venta']; ?></strong></td>
                                        <td>
                                            <div><?php echo htmlspecialchars($venta['cliente_nombre']); ?></div>
                                            <small style="color: #666;"><?php echo htmlspecialchars($venta['cliente_correo']); ?></small>
                                        </td>
                                        <td><?php echo $venta['total_productos']; ?> items</td>
                                        <td><strong>$<?php echo number_format($venta['total'], 2); ?></strong></td>
                                        <td>
                                            <span class="badge <?php echo $venta['estado'] === 'completada' ? 'success' : 'warning'; ?>">
                                                <?php echo ucfirst($venta['estado']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($venta['fecha_venta'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <h4>Sin ventas registradas</h4>
                            <p>No hay ventas en el sistema aún</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="sidebar">
                <!-- Alertas de Stock -->
                <div class="card">
                    <h3>⚠️ Alertas de Stock</h3>
                    <?php if (!empty($alertasStock)): ?>
                        <?php foreach ($alertasStock as $alerta): ?>
                            <div class="alert-item <?php echo $alerta['stock_total'] == 0 ? 'critical' : ''; ?>">
                                <?php if ($alerta['imagen_url']): ?>
                                    <img src="<?php echo htmlspecialchars($alerta['imagen_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($alerta['nombre']); ?>" 
                                         class="product-img">
                                <?php else: ?>
                                    <div class="no-img">Sin img</div>
                                <?php endif; ?>
                                <div style="flex: 1;">
                                    <strong><?php echo htmlspecialchars($alerta['nombre']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($alerta['marca']); ?></small><br>
                                    <span class="badge <?php echo $alerta['stock_total'] == 0 ? 'danger' : 'warning'; ?>">
                                        <?php echo $alerta['stock_total']; ?> unidades
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <div style="text-align: center; margin-top: 15px;">
                            <a href="admin_inventario.php?stock_bajo=1" class="nav-links" style="display: inline-block;">
                                Ver todos los productos con stock bajo
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <h4>✅ Stock saludable</h4>
                            <p>Todos los productos tienen stock suficiente</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Top Clientes -->
                <div class="card">
                    <h3>👑 Top Clientes</h3>
                    <?php if (!empty($topClientes)): ?>
                        <?php foreach ($topClientes as $index => $cliente): ?>
                            <div style="padding: 15px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div style="background: #667eea; color: white; width: 25px; height: 25px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold;">
                                            <?php echo $index + 1; ?>
                                        </div>
                                        <div>
                                            <strong><?php echo htmlspecialchars($cliente['nombre']); ?></strong><br>
                                            <small style="color: #666;"><?php echo htmlspecialchars($cliente['correo']); ?></small>
                                        </div>
                                    </div>
                                </div>
                                <div style="text-align: right;">
                                    <div><strong><?php echo $cliente['total_compras']; ?></strong> compras</div>
                                    <div style="color: #28a745; font-weight: bold;">$<?php echo number_format($cliente['total_gastado'], 2); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <h4>Sin clientes aún</h4>
                            <p>No hay compras registradas de clientes</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Resumen Rápido -->
                <div class="card">
                    <h3>📋 Resumen Rápido</h3>
                    <div style="space-y: 15px;">
                        <div style="padding: 12px; background: #f8f9fa; border-radius: 8px; margin-bottom: 10px;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="color: #666;">Ventas Totales</span>
                                <strong style="color: #28a745;">$<?php echo number_format($ventasTotal, 2); ?></strong>
                            </div>
                        </div>
                        
                        <div style="padding: 12px; background: #f8f9fa; border-radius: 8px; margin-bottom: 10px;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="color: #666;">Pedidos Totales</span>
                                <strong style="color: #667eea;"><?php echo number_format($pedidosTotal); ?></strong>
                            </div>
                        </div>
                        
                        <div style="padding: 12px; background: #f8f9fa; border-radius: 8px; margin-bottom: 10px;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="color: #666;">Promedio por Venta</span>
                                <strong style="color: #17a2b8;">
                                    $<?php echo $pedidosTotal > 0 ? number_format($ventasTotal / $pedidosTotal, 2) : '0.00'; ?>
                                </strong>
                            </div>
                        </div>
                        
                        <div style="padding: 12px; background: #f8f9fa; border-radius: 8px; margin-bottom: 10px;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="color: #666;">Stock Promedio</span>
                                <strong style="color: #6f42c1;">
                                    <?php echo $totalProductos > 0 ? number_format($stockTotal / $totalProductos, 1) : '0'; ?> un/producto
                                </strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Acciones Rápidas -->
                <div class="card">
                    <h3>⚡ Acciones Rápidas</h3>
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <a href="admin_productos.php" style="background: #667eea; color: white; padding: 12px; text-decoration: none; border-radius: 8px; text-align: center; transition: all 0.3s ease;">
                            ➕ Agregar Producto
                        </a>
                        <a href="admin_inventario.php" style="background: #28a745; color: white; padding: 12px; text-decoration: none; border-radius: 8px; text-align: center; transition: all 0.3s ease;">
                            📊 Actualizar Inventario
                        </a>
                        <a href="admin_inventario.php?stock_bajo=1" style="background: #ffc107; color: #212529; padding: 12px; text-decoration: none; border-radius: 8px; text-align: center; transition: all 0.3s ease;">
                            ⚠️ Revisar Stock Bajo
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer con información adicional -->
        <div style="background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); padding: 20px; border-radius: 15px; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1); text-align: center; margin-top: 30px;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; text-align: center;">
                <div>
                    <div style="color: #666; font-size: 12px; text-transform: uppercase; margin-bottom: 5px;">Última Actualización</div>
                    <div style="font-weight: bold; color: #2c3e50;"><?php echo date('d/m/Y H:i:s'); ?></div>
                </div>
                <div>
                    <div style="color: #666; font-size: 12px; text-transform: uppercase; margin-bottom: 5px;">Sistema</div>
                    <div style="font-weight: bold; color: #2c3e50;">Tienda Deportiva v1.0</div>
                </div>
                <div>
                    <div style="color: #666; font-size: 12px; text-transform: uppercase; margin-bottom: 5px;">Estado del Sistema</div>
                    <div style="font-weight: bold; color: #28a745;">🟢 En línea</div>
                </div>
                <div>
                    <div style="color: #666; font-size: 12px; text-transform: uppercase; margin-bottom: 5px;">Proyecto Final</div>
                    <div style="font-weight: bold; color: #667eea;">Técnico en Sistemas</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Actualizar la hora cada minuto
        setInterval(function() {
            const now = new Date();
            const timeString = now.toLocaleString('es-ES', {
                day: '2-digit',
                month: '2-digit', 
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            
            const welcomeElement = document.querySelector('.welcome');
            if (welcomeElement) {
                const text = welcomeElement.textContent;
                const namepart = text.split(' | ')[0];
                welcomeElement.textContent = namepart + ' | ' + timeString;
            }
        }, 60000);

        // Añadir efectos hover a las tarjetas de estadísticas
        document.querySelectorAll('.stat-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px) scale(1.02)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });

        // Añadir efectos a los botones de acciones rápidas
        document.querySelectorAll('a[style*="background"]').forEach(btn => {
            btn.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 5px 15px rgba(0,0,0,0.2)';
            });
            
            btn.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = 'none';
            });
        });

        // Mostrar alertas si hay productos sin stock
        <?php if ($productosSinStock > 0): ?>
        setTimeout(function() {
            if (confirm('⚠️ Tienes <?php echo $productosSinStock; ?> producto(s) sin stock. ¿Quieres revisar el inventario ahora?')) {
                window.location.href = 'admin_inventario.php?stock_bajo=1';
            }
        }, 2000);
        <?php endif; ?>

        // Función para refrescar datos cada 5 minutos
        setInterval(function() {
            // Solo refrescar si la página está visible
            if (!document.hidden) {
                location.reload();
            }
        }, 300000); // 5 minutos

        // Guardar estado de scroll al refrescar
        window.addEventListener('beforeunload', function() {
            sessionStorage.setItem('scrollPosition', window.scrollY);
        });

        window.addEventListener('load', function() {
            const scrollPosition = sessionStorage.getItem('scrollPosition');
            if (scrollPosition) {
                window.scrollTo(0, parseInt(scrollPosition));
                sessionStorage.removeItem('scrollPosition');
            }
        });

        console.log('🚀 Dashboard cargado correctamente');
        console.log('📊 Datos del sistema:', {
            productos: <?php echo $totalProductos; ?>,
            ventasHoy: <?php echo $ventasHoy; ?>,
            stockBajo: <?php echo $productosStockBajo; ?>,
            clientes: <?php echo $totalClientes; ?>
        });
    </script>
</body>
</html>