<?php
session_start();
include('conexion.php');

header('Content-Type: application/json');

// Verificar si el usuario está logueado
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión para agregar productos al carrito', 'login_required' => true]);
    exit;
}

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtener y validar datos
$producto_id = isset($_POST['producto_id']) ? intval($_POST['producto_id']) : 0;
$talla_id = isset($_POST['talla_id']) ? intval($_POST['talla_id']) : 0;
$cantidad = isset($_POST['cantidad']) ? intval($_POST['cantidad']) : 1;

if ($producto_id <= 0 || $talla_id <= 0 || $cantidad <= 0) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

try {
    $conexion->begin_transaction();
    
    // Verificar que el producto existe y tiene stock
    $sqlVerificar = "SELECT pt.stock, p.nombre, p.precio, p.imagen_url, t.talla 
                     FROM producto_tallas pt 
                     JOIN productos p ON pt.id_producto = p.id_producto 
                     JOIN tallas t ON pt.id_talla = t.id_talla 
                     WHERE pt.id_producto = ? AND pt.id_talla = ?";
    
    $stmtVerificar = $conexion->prepare($sqlVerificar);
    $stmtVerificar->bind_param("ii", $producto_id, $talla_id);
    $stmtVerificar->execute();
    $resultado = $stmtVerificar->get_result();
    
    if ($resultado->num_rows === 0) {
        throw new Exception('Producto o talla no encontrados');
    }
    
    $producto = $resultado->fetch_assoc();
    
    if ($producto['stock'] < $cantidad) {
        throw new Exception('Stock insuficiente. Solo hay ' . $producto['stock'] . ' unidades disponibles');
    }
    
    // Verificar si el producto ya está en el carrito
    $sqlExistente = "SELECT id_carrito, cantidad FROM carrito 
                     WHERE id_usuario = ? AND id_producto = ? AND id_talla = ?";
    
    $stmtExistente = $conexion->prepare($sqlExistente);
    $stmtExistente->bind_param("iii", $_SESSION['id_usuario'], $producto_id, $talla_id);
    $stmtExistente->execute();
    $resultadoExistente = $stmtExistente->get_result();
    
    if ($resultadoExistente->num_rows > 0) {
        // Actualizar cantidad existente
        $carritoExistente = $resultadoExistente->fetch_assoc();
        $nuevaCantidad = $carritoExistente['cantidad'] + $cantidad;
        
        if ($nuevaCantidad > $producto['stock']) {
            throw new Exception('Stock insuficiente. Máximo disponible: ' . $producto['stock'] . ' unidades');
        }
        
        $sqlActualizar = "UPDATE carrito SET cantidad = ?, fecha_agregado = NOW() 
                         WHERE id_carrito = ?";
        $stmtActualizar = $conexion->prepare($sqlActualizar);
        $stmtActualizar->bind_param("ii", $nuevaCantidad, $carritoExistente['id_carrito']);
        $stmtActualizar->execute();
        
        $mensaje = 'Cantidad actualizada en el carrito';
    } else {
        // Agregar nuevo producto
        $sqlInsertar = "INSERT INTO carrito (id_usuario, id_producto, id_talla, cantidad) 
                       VALUES (?, ?, ?, ?)";
        $stmtInsertar = $conexion->prepare($sqlInsertar);
        $stmtInsertar->bind_param("iiii", $_SESSION['id_usuario'], $producto_id, $talla_id, $cantidad);
        $stmtInsertar->execute();
        
        $mensaje = 'Producto agregado al carrito';
    }
    
    // Obtener total de productos en el carrito
    $sqlTotal = "SELECT SUM(cantidad) as total FROM carrito WHERE id_usuario = ?";
    $stmtTotal = $conexion->prepare($sqlTotal);
    $stmtTotal->bind_param("i", $_SESSION['id_usuario']);
    $stmtTotal->execute();
    $resultadoTotal = $stmtTotal->get_result();
    $totalProductos = $resultadoTotal->fetch_assoc()['total'] ?? 0;
    
    $conexion->commit();
    
    echo json_encode([
        'success' => true,
        'message' => $mensaje,
        'cart_count' => $totalProductos,
        'producto' => [
            'nombre' => $producto['nombre'],
            'talla' => $producto['talla'],
            'cantidad' => $cantidad,
            'precio' => $producto['precio']
        ]
    ]);
    
} catch (Exception $e) {
    $conexion->rollback();
    error_log("Error agregar carrito: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>