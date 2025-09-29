<?php
session_start();
include 'conexion.php';

// Verificar que sea admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['correo'] !== "admin@tienda.com") {
    header("Location: proyecto.php");
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

// Procesar cambio de estado
if ($_POST && isset($_POST['cambiar_estado'])) {
    $id_venta = (int)$_POST['id_venta'];
    $nuevo_estado = $_POST['nuevo_estado'];
    
    $resultado = ejecutarSQL("update", "UPDATE ventas SET estado = ? WHERE id_venta = ?", [$nuevo_estado, $id_venta]);
    
    if ($resultado) {
        $mensaje = "Estado actualizado correctamente";
        $tipo_mensaje = "success";
    } else {
        $mensaje = "Error al actualizar el estado";
        $tipo_mensaje = "error";
    }
}

// Obtener filtro de estado
$filtro_estado = $_GET['estado'] ?? 'todos';

$sql_base = "
    SELECT 
        v.id_venta,
        v.total,
        v.fecha_venta,
        v.estado,
        v.direccion_entrega,
        v.telefono,
        'Pago con efectivo (contraentrega)' as metodo_pago,
        u.nombre as cliente_nombre,
        u.correo as cliente_correo,
        COUNT(dv.id_detalle) as total_productos
    FROM ventas v
    INNER JOIN usuarios u ON v.id_usuario = u.id_usuario
    LEFT JOIN detalle_ventas dv ON v.id_venta = dv.id_venta
";

// Aplicar filtro
if ($filtro_estado !== 'todos') {
    $sql_base .= " WHERE v.estado = ?";
    $pedidos = ejecutarSQL("select", $sql_base . " GROUP BY v.id_venta ORDER BY v.fecha_venta DESC", [$filtro_estado]);
} else {
    $pedidos = ejecutarSQL("select", $sql_base . " GROUP BY v.id_venta ORDER BY v.fecha_venta DESC");
}

// Estadísticas rápidas
$stats = [
    'total' => ejecutarSQL("select", "SELECT COUNT(*) as total FROM ventas")[0]['total'] ?? 0,
    'pendientes' => ejecutarSQL("select", "SELECT COUNT(*) as total FROM ventas WHERE estado = 'completada'")[0]['total'] ?? 0,
    'entregadas' => ejecutarSQL("select", "SELECT COUNT(*) as total FROM ventas WHERE estado = 'entregada'")[0]['total'] ?? 0
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Pedidos - Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            color: #2d3748;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: #ffffff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            border: 1px solid #e2e8f0;
        }

        .header h1 {
            color: #1a202c;
            font-size: 28px;
            font-weight: 700;
        }

        .nav-links {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .nav-links a {
            color: #4a5568;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 8px;
            background: #f7fafc;
            transition: all 0.2s ease;
            font-weight: 500;
            border: 1px solid #e2e8f0;
        }

        .nav-links a:hover {
            background: #edf2f7;
            color: #2d3748;
            transform: translateY(-1px);
        }

        .nav-links a.logout {
            background: #fed7d7;
            color: #c53030;
        }

        .nav-links a.logout:hover {
            background: #feb2b2;
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: #ffffff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
            text-align: center;
        }

        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #1a202c;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #718096;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .filters {
            background: #ffffff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
            margin-bottom: 30px;
        }

        .filters h3 {
            color: #1a202c;
            margin-bottom: 15px;
            font-size: 18px;
        }

        .filter-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 8px 16px;
            border: 1px solid #e2e8f0;
            background: #f7fafc;
            color: #4a5568;
            text-decoration: none;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.2s ease;
        }

        .filter-btn:hover, .filter-btn.active {
            background: #4a5568;
            color: white;
            border-color: #4a5568;
        }

        .pedidos-container {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }

        .pedidos-header {
            padding: 25px;
            border-bottom: 1px solid #e2e8f0;
        }

        .pedidos-header h2 {
            color: #1a202c;
            font-size: 20px;
            margin-bottom: 5px;
        }

        .pedido-item {
            padding: 20px 25px;
            border-bottom: 1px solid #f7fafc;
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 20px;
            align-items: start;
        }

        .pedido-item:last-child {
            border-bottom: none;
        }

        .pedido-info {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .pedido-id {
            font-weight: bold;
            color: #1a202c;
            font-size: 18px;
        }

        .cliente-info {
            color: #4a5568;
        }

        .cliente-info strong {
            color: #1a202c;
        }

        .pedido-detalles {
            background: #f7fafc;
            padding: 12px;
            border-radius: 8px;
            margin-top: 10px;
        }

        .detalle-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 14px;
        }

        .detalle-row:last-child {
            margin-bottom: 0;
        }

        .detalle-label {
            color: #718096;
            font-weight: 500;
        }

        .detalle-value {
            color: #1a202c;
        }

        .estado-actual {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .estado-completada {
            background: #faf089;
            color: #744210;
        }

        .estado-entregada {
            background: #c6f6d5;
            color: #22543d;
        }

        .cambiar-estado {
            display: flex;
            flex-direction: column;
            gap: 10px;
            min-width: 180px;
        }

        .select-estado {
            padding: 8px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            background: #ffffff;
            color: #4a5568;
            font-size: 14px;
        }

        .btn-actualizar {
            padding: 8px 12px;
            background: #4a5568;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s ease;
        }

        .btn-actualizar:hover {
            background: #2d3748;
        }

        .mensaje {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .mensaje.success {
            background: #c6f6d5;
            color: #22543d;
            border: 1px solid #9ae6b4;
        }

        .mensaje.error {
            background: #fed7d7;
            color: #742a2a;
            border: 1px solid #feb2b2;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #718096;
        }

        .empty-state h3 {
            color: #a0aec0;
            margin-bottom: 10px;
            font-size: 18px;
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

            .pedido-item {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .cambiar-estado {
                min-width: auto;
            }

            .filter-buttons {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Gestión de Pedidos</h1>
            <div class="nav-links">
                <a href="dashboard.php">Dashboard</a>
                <a href="admin_productos.php">Productos</a>
                <a href="admin_inventario.php">Inventario</a>
                <a href="admin_pedidos.php" style="background: #edf2f7; color: #2d3748;">Pedidos</a>
                <a href="proyecto.php" class="logout">Volver al Inicio</a>
            </div>
        </div>

        <!-- Estadísticas -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total Pedidos</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['pendientes']; ?></div>
                <div class="stat-label">Por Entregar</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['entregadas']; ?></div>
                <div class="stat-label">Entregadas</div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filters">
            <h3>Filtrar por Estado</h3>
            <div class="filter-buttons">
                <a href="?estado=todos" class="filter-btn <?php echo $filtro_estado === 'todos' ? 'active' : ''; ?>">Todos</a>
                <a href="?estado=completada" class="filter-btn <?php echo $filtro_estado === 'completada' ? 'active' : ''; ?>">Por Entregar</a>
                <a href="?estado=entregada" class="filter-btn <?php echo $filtro_estado === 'entregada' ? 'active' : ''; ?>">Entregadas</a>
            </div>
        </div>

        <!-- Mensaje de confirmación -->
        <?php if (isset($mensaje)): ?>
            <div class="mensaje <?php echo $tipo_mensaje; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <!-- Lista de Pedidos -->
        <div class="pedidos-container">
            <div class="pedidos-header">
                <h2>Pedidos Registrados</h2>
                <p style="color: #718096; margin: 0;">Gestiona las entregas y estados de los pedidos</p>
            </div>

            <?php if (empty($pedidos)): ?>
                <div class="empty-state">
                    <h3>No hay pedidos</h3>
                    <p>No se encontraron pedidos con los filtros seleccionados</p>
                </div>
            <?php else: ?>
                <?php foreach ($pedidos as $pedido): ?>
                    <div class="pedido-item">
                        <div class="pedido-info">
                            <div class="pedido-id">Pedido #<?php echo $pedido['id_venta']; ?></div>
                            <div class="cliente-info">
                                <strong><?php echo htmlspecialchars($pedido['cliente_nombre']); ?></strong><br>
                                <span><?php echo htmlspecialchars($pedido['cliente_correo']); ?></span><br>
                                <span>Tel: <?php echo htmlspecialchars($pedido['telefono']); ?></span>
                            </div>
                            
                            <div class="pedido-detalles">
                                <div class="detalle-row">
                                    <span class="detalle-label">Fecha:</span>
                                    <span class="detalle-value"><?php echo date('d/m/Y H:i', strtotime($pedido['fecha_venta'])); ?></span>
                                </div>
                                <div class="detalle-row">
                                    <span class="detalle-label">Total:</span>
                                    <span class="detalle-value">$<?php echo number_format($pedido['total'], 2); ?></span>
                                </div>
                                <div class="detalle-row">
                                    <span class="detalle-label">Productos:</span>
                                    <span class="detalle-value"><?php echo $pedido['total_productos']; ?> items</span>
                                </div>
                                <div class="detalle-row">
                                    <span class="detalle-label">Pago:</span>
                                    <span class="detalle-value"><?php echo htmlspecialchars($pedido['metodo_pago']); ?></span>
                                </div>
                                <div class="detalle-row">
                                    <span class="detalle-label">Dirección:</span>
                                    <span class="detalle-value"><?php echo htmlspecialchars($pedido['direccion_entrega']); ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="cambiar-estado">
                            <div class="estado-actual estado-<?php echo $pedido['estado']; ?>">
                                <?php echo $pedido['estado'] === 'completada' ? 'Por Entregar' : 'Entregada'; ?>
                            </div>
                            
                            <form method="POST" style="display: flex; flex-direction: column; gap: 10px;">
                                <input type="hidden" name="id_venta" value="<?php echo $pedido['id_venta']; ?>">
                                <select name="nuevo_estado" class="select-estado" required>
                                    <option value="">Cambiar estado...</option>
                                    <option value="completada" <?php echo $pedido['estado'] === 'completada' ? 'selected' : ''; ?>>Por Entregar</option>
                                    <option value="entregada" <?php echo $pedido['estado'] === 'entregada' ? 'selected' : ''; ?>>Entregada</option>
                                </select>
                                <button type="submit" name="cambiar_estado" class="btn-actualizar">Actualizar</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>


    <script>
        // Auto-refresh cada 30 segundos si hay pedidos pendientes
        <?php if ($stats['pendientes'] > 0): ?>
        setTimeout(function() {
            if (!document.hidden) {
                location.reload();
            }
        }, 30000);
        <?php endif; ?>
    </script>
</body>
</html>