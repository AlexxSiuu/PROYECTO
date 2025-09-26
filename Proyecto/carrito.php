<?php 
session_start(); 
include ('conexion.php');

// Verificar si el usuario está logueado
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: PROYECTO.php#login");
    exit();
}

$id_usuario = $_SESSION['id_usuario'];

// Función mejorada con prepared statements (reutilizando la tuya)
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


$sqlCarrito = "
    SELECT 
        c.id_carrito,
        c.cantidad,
        p.id_producto,
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

// Calcular total
$total = 0;
if ($productos_carrito) {
    foreach ($productos_carrito as $item) {
        $total += $item->subtotal;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Carrito - JERSEYKING</title>
    <link rel="stylesheet" href="prueba.css">
    <style>
        .carrito-container {
            max-width: 1200px;
            margin: 50px auto;
            padding: 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .carrito-header {
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .carrito-header h1 {
            font-size: 2.5em;
            color: #111;
            margin: 0;
            font-weight: 700;
        }

        .carrito-vacio {
            text-align: center;
            padding: 80px 20px;
            color: #666;
        }

        .carrito-vacio h2 {
            font-size: 2em;
            margin-bottom: 15px;
            color: #999;
        }

        .carrito-vacio a {
            display: inline-block;
            background: linear-gradient(135deg, #111 0%, #333 100%);
            color: white;
            padding: 15px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 20px;
            transition: all 0.3s ease;
        }

        .carrito-vacio a:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .carrito-item {
            display: flex;
            align-items: center;
            padding: 25px;
            border: 1px solid #f0f0f0;
            border-radius: 12px;
            margin-bottom: 20px;
            background: #fafafa;
            transition: all 0.3s ease;
        }

        .carrito-item:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transform: translateY(-2px);
        }

        .item-imagen {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 10px;
            margin-right: 25px;
            border: 1px solid #eee;
        }

        .item-detalles {
            flex: 1;
        }

        .item-nombre {
            font-size: 1.4em;
            font-weight: 700;
            color: #111;
            margin-bottom: 8px;
        }

        .item-info {
            color: #666;
            font-size: 0.95em;
            margin-bottom: 10px;
        }

        .item-precio {
            font-size: 1.2em;
            font-weight: 700;
            color: #111;
        }

        .item-controles {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 15px;
        }

        .cantidad-controles {
            display: flex;
            align-items: center;
            border: 1px solid #ddd;
            border-radius: 25px;
            background: white;
            overflow: hidden;
        }

        .btn-cantidad {
            background: none;
            border: none;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            color: #666;
            transition: all 0.2s ease;
        }

        .btn-cantidad:hover:not(:disabled) {
            background-color: #f0f0f0;
            color: #111;
        }

        .btn-cantidad:disabled {
            color: #ccc;
            cursor: not-allowed;
        }

        .input-cantidad {
            width: 50px;
            border: none;
            text-align: center;
            font-size: 16px;
            font-weight: 600;
            background: transparent;
            color: #111;
        }

        .btn-eliminar {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9em;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-eliminar:hover {
            background: #c82333;
            transform: translateY(-1px);
        }

        .carrito-resumen {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 30px;
            border-radius: 12px;
            margin-top: 30px;
            border: 1px solid #dee2e6;
        }

        .total-final {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 2em;
            font-weight: 800;
            color: #111;
            margin-bottom: 25px;
            padding: 20px 0;
            border-bottom: 2px solid #dee2e6;
        }

        .carrito-acciones {
            display: flex;
            gap: 20px;
            justify-content: center;
        }

        .btn-accion {
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
            min-width: 180px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-continuar {
            background: white;
            border: 2px solid #111;
            color: #111;
        }

        .btn-continuar:hover {
            background: #111;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .btn-checkout {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border: 2px solid #28a745;
        }

        .btn-checkout:hover {
            background: linear-gradient(135deg, #218838 0%, #1e7e34 100%);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }

        @media (max-width: 768px) {
            .carrito-item {
                flex-direction: column;
                text-align: center;
            }

            .item-imagen {
                margin-right: 0;
                margin-bottom: 15px;
            }

            .item-controles {
                flex-direction: row;
                justify-content: center;
                width: 100%;
            }

            .carrito-acciones {
                flex-direction: column;
            }

            .btn-accion {
                width: 100%;
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
            <?php if (isset($_SESSION['nombre'])): ?>
                <span>👤 <?php echo htmlspecialchars($_SESSION['nombre']); ?></span>
                <a href="logout.php" style="color:white;">Cerrar sesión</a>
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


<div class="carrito-container">
    <div class="carrito-header">
        <h1>🛒 Mi Carrito</h1>
    </div>



    <?php if (!$productos_carrito || count($productos_carrito) === 0): ?>
        <div class="carrito-vacio">
            <h2>Tu carrito está vacío</h2>
            <p>¡Explora nuestra increíble colección de productos deportivos!</p>
            <a href="PROYECTO.php">Continuar Comprando</a>
        </div>
    <?php else: ?>
        




        <?php foreach ($productos_carrito as $item): ?>
    <div class="carrito-item" data-cart-id="<?= $item->id_carrito ?>">
        <img src="<?= htmlspecialchars($item->imagen_url) ?>" alt="<?= htmlspecialchars($item->nombre) ?>" class="item-imagen">
        
        <div class="item-detalles">
            <div class="item-nombre"><?= htmlspecialchars($item->nombre) ?></div>
            <div class="item-info">
                <strong>Marca:</strong> <?= htmlspecialchars($item->marca) ?> | 
                <strong>Talla:</strong> <?= htmlspecialchars($item->talla) ?>
            </div>
            <div class="item-precio">$<?= number_format($item->precio, 2) ?> c/u</div>
        </div>
        <div class="item-controles">
            <div class="cantidad-controles">
                <button class="btn-cantidad" onclick="actualizarCantidad(<?= $item->id_carrito ?>, 'disminuir')" 
                        <?= $item->cantidad <= 1 ? 'title="Eliminar producto"' : '' ?>>
                    <?= $item->cantidad <= 1 ? '🗑️' : '−' ?>
                </button>
                <input type="text" class="input-cantidad" value="<?= $item->cantidad ?>" readonly>
                <button class="btn-cantidad" onclick="actualizarCantidad(<?= $item->id_carrito ?>, 'aumentar')"
                        <?= $item->cantidad >= $item->stock ? 'disabled' : '' ?>>+</button>
            </div>
            <button class="btn-eliminar" onclick="eliminarDelCarrito(<?= $item->id_carrito ?>)">
                Eliminar
            </button>
        </div>
    </div>



        <?php endforeach; ?>

        <div class="carrito-resumen">
            <div class="total-final">
                <span>Total:</span>
                <span>$<?= number_format($total, 2) ?></span>
            </div>
            
            <div class="carrito-acciones">
                <a href="PROYECTO.php" class="btn-accion btn-continuar">Continuar Comprando</a>
                <a href="checkout.php" class="btn-accion btn-checkout">Proceder al Pago</a>
            </div>
        </div>

    <?php endif; ?>
</div>

<script>
// IMPORTANTE: Estas funciones deben usar TU estructura actual
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
            location.reload(); // Recargar página para mostrar cambios
        } else {
            alert(data.message || 'Error al actualizar el carrito');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión');
    });
}

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
            location.reload(); // Recargar página para mostrar cambios
        } else {
            alert(data.message || 'Error al eliminar producto');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión');
    });
}
</script>

</body>
</html>