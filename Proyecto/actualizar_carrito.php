<?php
session_start();
include('conexion.php');

// Verificar si el usuario está logueado
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión', 'type' => 'error']);
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

// Obtener datos del POST
$accion = $_POST['accion'] ?? '';
$id_carrito = $_POST['id_carrito'] ?? 0;

if (!$accion || !$id_carrito) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos', 'type' => 'error']);
    exit();
}

// Verificar que el item del carrito pertenece al usuario
$sqlVerificar = "SELECT c.*, pt.stock, p.nombre FROM carrito c 
                 JOIN producto_tallas pt ON (c.id_producto = pt.id_producto AND c.id_talla = pt.id_talla)
                 JOIN productos p ON c.id_producto = p.id_producto
                 WHERE c.id_carrito = ? AND c.id_usuario = ?";
$item = ejecutarSQL("select", $sqlVerificar, [$id_carrito, $id_usuario]);

if (!$item || count($item) === 0) {
    echo json_encode(['success' => false, 'message' => 'Producto no encontrado', 'type' => 'error']);
    exit();
}

$item = $item[0];

try {
    switch ($accion) {
        case 'aumentar':
            // Verificar stock disponible
            if ($item->cantidad >= $item->stock) {
                echo json_encode([
                    'success' => false, 
                    'message' => 'No hay suficiente stock disponible para ' . $item->nombre,
                    'type' => 'warning'
                ]);
                exit();
            }
            
            $sqlActualizar = "UPDATE carrito SET cantidad = cantidad + 1 WHERE id_carrito = ?";
            $resultado = ejecutarSQL("update", $sqlActualizar, [$id_carrito]);
            $mensaje = 'Cantidad aumentada';
            break;
            
        case 'disminuir':
            if ($item->cantidad <= 1) {
                // Si cantidad es 1, eliminar el producto
                $sqlEliminar = "DELETE FROM carrito WHERE id_carrito = ?";
                $resultado = ejecutarSQL("delete", $sqlEliminar, [$id_carrito]);
                $mensaje = $item->nombre . ' eliminado del carrito';
            } else {
                $sqlActualizar = "UPDATE carrito SET cantidad = cantidad - 1 WHERE id_carrito = ?";
                $resultado = ejecutarSQL("update", $sqlActualizar, [$id_carrito]);
                $mensaje = 'Cantidad reducida';
            }
            break;
            
        case 'eliminar':
            $sqlEliminar = "DELETE FROM carrito WHERE id_carrito = ?";
            $resultado = ejecutarSQL("delete", $sqlEliminar, [$id_carrito]);
            $mensaje = $item->nombre . ' eliminado del carrito';
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida', 'type' => 'error']);
            exit();
    }
    
    if ($resultado) {
        // Obtener nuevo conteo del carrito
        $sqlConteo = "SELECT SUM(cantidad) as total FROM carrito WHERE id_usuario = ?";
        $conteo = ejecutarSQL("select", $sqlConteo, [$id_usuario]);
        $cart_count = $conteo && count($conteo) > 0 ? (int)$conteo[0]->total : 0;
        
        echo json_encode([
            'success' => true, 
            'message' => $mensaje,
            'cart_count' => $cart_count,
            'type' => 'success'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar el carrito', 'type' => 'error']);
    }
    
} catch (Exception $e) {
    error_log("Error en actualizar_carrito.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor', 'type' => 'error']);
}
?>