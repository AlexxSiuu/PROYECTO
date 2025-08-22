<?php 
session_start(); 
include ('conexion.php');

// Verificar si el usuario está logueado
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: PROYECTO.php#login");
    exit();
}

// Verificar que se proporcione el ID de venta
if (!isset($_GET['venta'])) {
    header("Location: PROYECTO.php");
    exit();
}

$id_venta = $_GET['venta'];
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

// Obtener datos de la venta
$sqlVenta = "
    SELECT v.*, u.nombre as nombre_usuario 
    FROM ventas v 
    JOIN usuarios u ON v.id_usuario = u.id_usuario 
    WHERE v.id_venta = ? AND v.id_usuario = ?
";

$venta = ejecutarSQL("select", $sqlVenta, [$id_venta, $id_usuario]);

if (!$venta || count($venta) === 0) {
    header("Location: PROYECTO.php");
    exit();
}

$venta = $venta[0];

// Obtener detalles de la venta
$sqlDetalles = "
    SELECT 
        dv.*,
        p.nombre,
        p.imagen_url,
        p.marca,
        t.talla
    FROM detalle_ventas dv
    JOIN productos p ON dv.id_producto = p.id_producto
    JOIN tallas t ON dv.id_talla = t.id_talla
    WHERE dv.id_venta = ?
    ORDER BY p.nombre
";

$detalles = ejecutarSQL("select", $sqlDetalles, [$id_venta]);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>¡Compra Exitosa! - JERSEYKING</title>
    <link rel="stylesheet" href="prueba.css">
    <style>
        .exito-container {
            max-width: 900px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.1);
            text-align: center;
        }

        .exito-header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 40px 30px;
            border-radius: 12px;
            margin-bottom: 40px;
        }

        .exito-icono {
            font-size: 80px;
            margin-bottom: 20px;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }

        .exito-titulo {
            font-size: 2.5em;
            font-weight: 800;
            margin-bottom: 15px;
        }

        .exito-mensaje {
            font-size: 1.2em;
            opacity: 0.9;
        }

        .venta-info {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            border: 1px solid #e9ecef;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .info-item {
            text-align: left;
        }

        .info-label {
            font-size: 0.9em;
            color: #666;
            margin-bottom: 5px;
        }

        .info-valor {
            font-weight: 700;
            color: #111;
            font-size: 1.1em;
        }

        .productos-vendidos {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 12px;
            margin-bottom: 30px;
            overflow: hidden;
        }

        .productos-header {
            background: #111;
            color: white;
            padding: 20px;
            font-weight: 700;
            font-size: 1.2em;
        }

        .producto-vendido {
            display: flex;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #f0f0f0;
        }

        .producto-vendido:last-child {
            border-bottom: none;
        }

        .producto-imagen {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            margin-right: 20px;
            border: 1px solid #e9ecef;
        }

        .producto-info {
            flex: 1;
            text-align: left;
        }

        .producto-nombre {
            font-weight: 600;
            color: #111;
            margin-bottom: 5px;
        }

        .producto-detalles {
            font-size: 0.9em;
            color: #666;
        }

        .producto-cantidad {
            font-weight: 600;
            color: #111;
            margin: 0 20px;
        }

        .producto-precio {
            font-weight: 700;
            color: #111;
        }

        .total-final-exito {
            background: linear-gradient(135deg, #111 0%, #333 100%);
            color: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 40px;
        }

        .total-amount {
            font-size: 2em;
            font-weight: 800;
        }

        .acciones-exito {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-exito {
            padding: 15px 30px;
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

        .btn-inicio {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
        }

        .btn-inicio:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.3);
        }

        .btn-seguir {
            background: white;
            border: 2px solid #111;
            color: #111;
        }

        .btn-seguir:hover {
            background: #111;
            color: white;
            transform: translateY(-2px);
        }

        .mensaje-adicional {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 20px;
            border-radius: 8px;
            margin-top: 30px;
        }

        .mensaje-adicional h4 {
            margin: 0 0 10px 0;
            color: #856404;
        }

        @media (max-width: 768px) {
            .acciones-exito {
                flex-direction: column;
                align-items: center;
            }
            
            .btn-exito {
                width: 280px;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .producto-vendido {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
        }
    </style>
</head>
<body>

<header class="header">
    <nav class="navbar">
        <div class="logo"><a href="PROYECTO.php">JERSEYKING</a></div>
        <ul class="nav-links">
            <li><a href="PROYECTO.php">Inicio</a></li>
        </ul>
        <div class="nav-icons">
            <span>👤 <?php echo htmlspecialchars($_SESSION['nombre']); ?></span>
            <a href="logout.php" style="color:white;">Cerrar sesión</a>
        </div>
    </nav>
</header>

<div class="exito-container">
    <div class="exito-header">
        <div class="exito-icono">✅</div>
        <h1 class="exito-titulo">¡Compra Exitosa!</h1>
        <p class="exito-mensaje">Gracias por tu compra. Tu pedido ha sido procesado correctamente.</p>
    </div>

    <div class="venta-info">
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Número de Pedido:</div>
                <div class="info-valor">#<?= str_pad($venta->id_venta, 6, '0', STR_PAD_LEFT) ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Fecha de Compra:</div>
                <div class="info-valor"><?= date('d/m/Y H:i', strtotime($venta->fecha_venta)) ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Cliente:</div>
                <div class="info-valor"><?= htmlspecialchars($venta->nombre_usuario) ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Estado:</div>
                <div class="info-valor" style="color: #28a745;">✅ <?= ucfirst($venta->estado) ?></div>
            </div>
        </div>
    </div>

    <?php if ($detalles && count($detalles) > 0): ?>
    <div class="productos-vendidos">
        <div class="productos-header">
            📦 Productos Comprados
        </div>
        
        <?php foreach ($detalles as $detalle): ?>
        <div class="producto-vendido">
            <img src="<?= htmlspecialchars($detalle->imagen_url) ?>" 
                 alt="<?= htmlspecialchars($detalle->nombre) ?>" 
                 class="producto-imagen">
            
            <div class="producto-info">
                <div class="producto-nombre"><?= htmlspecialchars($detalle->nombre) ?></div>
                <div class="producto-detalles">
                    <?= htmlspecialchars($detalle->marca) ?> | Talla: <?= htmlspecialchars($detalle->talla) ?>
                </div>
            </div>
            
            <div class="producto-cantidad">x<?= $detalle->cantidad ?></div>
            <div class="producto-precio">$<?= number_format($detalle->subtotal, 2) ?></div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="total-final-exito">
        <div style="margin-bottom: 10px;">Total Pagado:</div>
        <div class="total-amount">$<?= number_format($venta->total, 2) ?></div>
    </div>

    <div class="acciones-exito">
        <a href="PROYECTO.php" class="btn-exito btn-inicio">🏠 Volver al Inicio</a>
        <a href="PROYECTO.php" class="btn-exito btn-seguir">🛍️ Seguir Comprando</a>
    </div>

    <div class="mensaje-adicional">
        <h4>📧 Información Importante:</h4>
        <p>
            • Recibirás un correo de confirmación con los detalles de tu pedido<br>
            • El tiempo estimado de entrega es de 3-5 días hábiles<br>
            • Puedes contactarnos si tienes alguna pregunta sobre tu pedido<br>
            • Conserva este número de pedido para futuras referencias: <strong>#<?= str_pad($venta->id_venta, 6, '0', STR_PAD_LEFT) ?></strong>
        </p>
    </div>
</div>

</body>
</html>