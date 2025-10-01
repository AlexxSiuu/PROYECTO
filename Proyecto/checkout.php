<?php 
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
include ('conexion.php');

// Verificar si el usuario está logueado
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: proyecto.php#login");
    exit(); 
}

$id_usuario = $_SESSION['id_usuario'];

// Función ejecutarSQL (reutilizando la tuya)
function ejecutarSQL($tipoSentencia, $sentenciaSQL, $params = []) {
    global $conexion;
    
    if ($conexion->connect_error) {
        error_log("Error de conexión: " . $conexion->connect_error);
        return false;
    }
    
    $conexion->set_charset("utf8mb4");
    
    $stmt = $conexion->prepare($sentenciaSQL);
    if (!$stmt) {
        error_log("Error preparando consulta: " . $conexion->error);
        return false;
    }
    
    if (!empty($params)) {
        $types = str_repeat('s', count($params));
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

// Obtener productos del carrito
$sqlCarrito = "
    SELECT 
        c.id_carrito,
        c.cantidad,
        c.id_producto,
        c.id_talla,
        p.nombre,
        p.precio,
        p.imagen_url,
        t.talla,
        pt.stock,
        p.marca,
        (p.precio * c.cantidad) as subtotal
    FROM carrito c
    INNER JOIN productos p ON c.id_producto = p.id_producto
    INNER JOIN tallas t ON c.id_talla = t.id_talla
    INNER JOIN producto_tallas pt ON (c.id_producto = pt.id_producto AND c.id_talla = pt.id_talla)
    WHERE c.id_usuario = ?
    ORDER BY c.fecha_agregado DESC
";

$productos_carrito = ejecutarSQL("select", $sqlCarrito, [$id_usuario]);

if (!$productos_carrito || count($productos_carrito) === 0) {
    header("Location: carrito.php");
    exit();
}

// Calcular total
$total = 0;
$total_items = 0;
foreach ($productos_carrito as $item) {
    $total += $item->subtotal;
    $total_items += $item->cantidad;
}

// Procesar la compra si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar_compra'])) {
    

  // Capturar datos del formulario
    $telefono = $_POST['telefono'];
    $direccion = $_POST['direccion'];

    
    // Iniciar transacción
    $conexion->autocommit(FALSE);
    
    try {
        // 1. Verificar stock disponible para todos los productos
        $stock_suficiente = true;
        $productos_sin_stock = [];
        
        foreach ($productos_carrito as $item) {
            if ($item->stock < $item->cantidad) {
                $stock_suficiente = false;
                $productos_sin_stock[] = $item->nombre . " (Talla: {$item->talla})";
            }
        }
        
        if (!$stock_suficiente) {
            throw new Exception("Stock insuficiente para: " . implode(", ", $productos_sin_stock));
        }
        
        // 2. Crear registro de venta
    $sqlVenta = "INSERT INTO ventas (id_usuario, total, telefono, direccion_entrega, fecha_venta, estado) VALUES (?, ?, ?, ?, NOW(), 'completada')";
        $stmt = $conexion->prepare($sqlVenta);
 $stmt->bind_param("sdss", $id_usuario, $total, $_POST['telefono'], $_POST['direccion']);
        $stmt->execute();
        $id_venta = $conexion->insert_id;
        $stmt->close();
        
        // 3. Crear detalles de venta y actualizar stock
        foreach ($productos_carrito as $item) {
            // Insertar detalle de venta
            $sqlDetalle = "INSERT INTO detalle_ventas (id_venta, id_producto, id_talla, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conexion->prepare($sqlDetalle);
            $stmt->bind_param("iiiids", $id_venta, $item->id_producto, $item->id_talla, $item->cantidad, $item->precio, $item->subtotal);
            $stmt->execute();
            $stmt->close();
            
            // Actualizar stock
            $sqlStock = "UPDATE producto_tallas SET stock = stock - ? WHERE id_producto = ? AND id_talla = ?";
            $stmt = $conexion->prepare($sqlStock);
            $stmt->bind_param("iii", $item->cantidad, $item->id_producto, $item->id_talla);
            $stmt->execute();
            $stmt->close();
        }
        
        // 4. Limpiar carrito del usuario
        $sqlLimpiarCarrito = "DELETE FROM carrito WHERE id_usuario = ?";
        $stmt = $conexion->prepare($sqlLimpiarCarrito);
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $stmt->close();
        
        // Confirmar transacción
        $conexion->commit();
        $conexion->autocommit(TRUE);
        
        // Redirigir a página de éxito
        header("Location: venta_exitosa.php?venta=" . $id_venta);
        exit();
        
    } catch (Exception $e) {
        // Revertir transacción en caso de error
        $conexion->rollback();
        $conexion->autocommit(TRUE);
        $error_mensaje = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - JERSEYKING</title>
    <link rel="stylesheet" href="prueba.css">
    <style>
        .checkout-container {
            max-width: 900px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .checkout-header {
            text-align: center;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .checkout-header h1 {
            font-size: 2.5em;
            color: #111;
            margin: 0;
            font-weight: 700;
        }

        .resumen-pedido {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
            border: 1px solid #e9ecef;
        }

        .resumen-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #dee2e6;
        }

        .resumen-item:last-child {
            border-bottom: none;
        }

        .item-info {
            flex: 1;
        }

        .item-nombre {
            font-weight: 600;
            color: #111;
            margin-bottom: 5px;
        }

        .item-detalles {
            font-size: 0.9em;
            color: #666;
        }

        .item-cantidad {
            margin: 0 20px;
            font-weight: 600;
            color: #111;
        }

        .item-precio {
            font-weight: 700;
            color: #111;
            min-width: 80px;
            text-align: right;
        }

        .total-seccion {
            background: linear-gradient(135deg, #111 0%, #333 100%);
            color: white;
            padding: 25px;
            border-radius: 10px;
            margin: 30px 0;
        }

        .total-linea {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .total-final {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 1.5em;
            font-weight: 800;
            padding-top: 15px;
            border-top: 1px solid rgba(255,255,255,0.3);
            margin-top: 15px;
        }

        .form-grupo {
            margin-bottom: 25px;
        }

        .form-grupo label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #111;
        }

        .form-grupo input,
        .form-grupo select,
        .form-grupo textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            box-sizing: border-box;
        }

        .form-grupo input:focus,
        .form-grupo select:focus,
        .form-grupo textarea:focus {
            outline: none;
            border-color: #111;
            box-shadow: 0 0 0 2px rgba(17,17,17,0.1);
        }

        .checkout-acciones {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin-top: 40px;
        }

        .btn-checkout {
            padding: 15px 40px;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-volver {
            background: white;
            border: 2px solid #111;
            color: #111;
        }

        .btn-volver:hover {
            background: #111;
            color: white;
            transform: translateY(-2px);
        }

        .btn-confirmar {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border: 2px solid #28a745;
        }

        .btn-confirmar:hover {
            background: linear-gradient(135deg, #218838 0%, #1e7e34 100%);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }

        .error-mensaje {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }

        /* ===== MODAL DE ERROR ELEGANTE ===== */
        .error-modal {
            position: fixed;
            top: 0; left: 0;
            width: 100vw; height: 100vh;
            background: rgba(0,0,0,0.7);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .error-modal-content {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            max-width: 500px;
            width: 90%;
            overflow: hidden;
            animation: slideIn 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }

        @keyframes slideIn {
            from { 
                transform: scale(0.8) translateY(-50px); 
                opacity: 0; 
            }
            to { 
                transform: scale(1) translateY(0); 
                opacity: 1; 
            }
        }

        .error-header {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            padding: 20px 25px;
            display: flex;
            align-items: center;
            position: relative;
        }

        .error-icon {
            font-size: 24px;
            margin-right: 12px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        .error-header h3 {
            flex: 1;
            margin: 0;
            font-size: 1.3em;
            font-weight: 700;
        }

        .close-error {
            font-size: 28px;
            cursor: pointer;
            color: rgba(255,255,255,0.8);
            transition: color 0.2s;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .close-error:hover {
            color: white;
            background-color: rgba(255,255,255,0.1);
        }

        .error-body {
            padding: 25px;
            text-align: center;
        }

        .error-body p {
            margin: 0;
            font-size: 16px;
            color: #333;
            line-height: 1.5;
        }

        .error-footer {
            padding: 20px 25px;
            background: #f8f9fa;
            text-align: center;
            border-top: 1px solid #dee2e6;
        }

        .btn-error-ok {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-error-ok:hover {
            background: linear-gradient(135deg, #c82333 0%, #a71e2a 100%);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.4);
        }

        @media (max-width: 768px) {
            .checkout-acciones {
                flex-direction: column;
            }
            
            .btn-checkout {
                width: 100%;
            }
            
            .resumen-item {
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }
        }
    </style>
</head>
<body>

<header class="header">
    <nav class="navbar">
        <div class="logo">
            <a href="proyecto.php">
                <img src="img/logo_icono.ico.jpg" class="logo-img">
            </a>
        </div>
    </nav>
</header>

<div class="checkout-container">
    <div class="checkout-header">
        <h1>🛒 Finalizar Compra</h1>
    </div>

    <?php if (isset($error_mensaje)): ?>
    <!-- Modal de Error -->
    <div class="error-modal" id="errorModal" style="display: flex;">
        <div class="error-modal-content">
            <div class="error-header">
                <span class="error-icon">⚠️</span>
                <h3>Error en la Compra</h3>
                <span class="close-error" onclick="cerrarError()">&times;</span>
            </div>
            <div class="error-body">
                <p><?= htmlspecialchars($error_mensaje) ?></p>
            </div>
            <div class="error-footer">
                <button class="btn-error-ok" onclick="cerrarError()">Entendido</button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="resumen-pedido">
        <h3 style="margin-top: 0; margin-bottom: 20px; color: #111;">📋 Resumen del Pedido</h3>
        
        <?php foreach ($productos_carrito as $item): ?>
        <div class="resumen-item">
            <div class="item-info">
                <div class="item-nombre"><?= htmlspecialchars($item->nombre) ?></div>
                <div class="item-detalles">
                    <?= htmlspecialchars($item->marca) ?> | Talla: <?= htmlspecialchars($item->talla) ?>
                </div>
            </div>
            <div class="item-cantidad">x<?= $item->cantidad ?></div>
            <div class="item-precio">$<?= number_format($item->subtotal, 2) ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="total-seccion">
        <div class="total-linea">
            <span>Subtotal (<?= $total_items ?> productos):</span>
            <span>$<?= number_format($total, 2) ?></span>
        </div>
        <div class="total-linea">
            <span>Envío:</span>
            <span>Gratis</span>
        </div>
        <div class="total-final">
            <span>Total a pagar:</span>
            <span>$<?= number_format($total, 2) ?></span>
        </div>
    </div>

    <form method="POST" onsubmit="return confirmarCompra()">
        <div style="background: #f8f9fa; padding: 25px; border-radius: 10px; margin-bottom: 30px;">
            <h3 style="margin-top: 0; color: #111;">📧 Información de Entrega</h3>
            
            <div class="form-grupo">
                <label for="direccion">Dirección de Entrega:</label>
                <textarea id="direccion" name="direccion" rows="3" placeholder="Ingresa tu dirección completa..." required></textarea>
            </div>
            
            <div style="display: flex; gap: 20px;">
                <div class="form-grupo" style="flex: 1;">
                    <label for="telefono">Teléfono:</label>
                    <input type="tel" id="telefono" name="telefono" placeholder="Ej: 7890-1234" 
                        pattern="[2,6,7][0-9]{3}-[0-9]{4}" 
                        title="Debe iniciar con 2, 6 o 7. Formato: 7890-1234" 
                        maxlength="9" required>       

                </div>



                
                <div class="form-grupo" style="flex: 1;">
                        <label for="metodo_pago">Método de Pago:</label>
                        <input type="hidden" name="metodo_pago" value="efectivo">
                        <input type="text" value="Pago en Efectivo (Contrareembolso)" disabled 
                        style="background: #e9ecef; cursor: not-allowed; width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 16px;">
                </div>
            </div>
        </div>

        <div class="checkout-acciones">
            <a href="carrito.php" class="btn-checkout btn-volver">⬅ Volver al Carrito</a>
            <button type="submit" name="confirmar_compra" class="btn-checkout btn-confirmar">
                ✅ Confirmar Compra
            </button>
        </div>
    </form>
</div>









<script>
function confirmarCompra() {
    return confirm('¿Estás seguro de que deseas confirmar esta compra?\n\nEsta acción no se puede deshacer.');
}

function cerrarError() {
    const modal = document.getElementById('errorModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

// Cerrar modal al hacer clic fuera de él
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('errorModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                cerrarError();
            }
        });
        
        // Cerrar con tecla Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modal.style.display === 'flex') {
                cerrarError();
            }
        });
    }
});
</script>

</body>
</html>