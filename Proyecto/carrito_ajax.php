<?php
session_start();
include('conexion.php');

// Verificar si el usuario está logueado
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['success' => false, 'login_required' => true]);
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

try {
    // Consulta para obtener productos del carrito
    $sqlCarrito = "
        SELECT 
            c.id_carrito,
            c.cantidad,
            p.id_producto,
            p.nombre,
            p.precio,
            p.imagen_url,
            t.talla,
            pt.stock as stock_disponible,
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
    
    $items = [];
    $total = 0;
    $total_items = 0;
    
    if ($productos_carrito) {
        foreach ($productos_carrito as $producto) {
            $items[] = [
                'id_carrito' => $producto->id_carrito,
                'nombre' => $producto->nombre,
                'precio' => '$' . number_format($producto->precio, 2),
                'imagen_url' => $producto->imagen_url,
                'talla' => $producto->talla,
                'marca' => $producto->marca,
                'cantidad' => $producto->cantidad,
                'stock_disponible' => $producto->stock_disponible,
                'subtotal' => '$' . number_format($producto->subtotal, 2)
            ];
            
            $total += $producto->subtotal;
            $total_items += $producto->cantidad;
        }
    }
    
    echo json_encode([
        'success' => true,
        'items' => $items,
        'total' => '$' . number_format($total, 2),
        'total_amount' => $total,
        'total_items' => $total_items
    ]);
    
} catch (Exception $e) {
    error_log("Error en carrito_ajax.php: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error al cargar el carrito'
    ]);
}
?>