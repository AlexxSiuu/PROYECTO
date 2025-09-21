<?php
session_start();
include 'conexion.php';

// Verificar que sea admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['correo'] !== "admin@tienda.com") {
    header("Location: PROYECTO.php");
    exit();
}

// Crear carpeta de imágenes si no existe
if (!is_dir('images')) {
    mkdir('images', 0755, true);
}

// Función para subir imagen
function subirImagen($archivo) {
    $errores = [];
    $ruta_final = '';
    
    // Verificar si se subió un archivo
    if (isset($archivo) && $archivo['error'] === UPLOAD_ERR_OK) {
        
        // Verificar tamaño (máximo 5MB)
        if ($archivo['size'] > 5000000) {
            $errores[] = "La imagen es muy grande. Máximo 5MB permitido.";
            return ['success' => false, 'error' => implode(' ', $errores), 'ruta' => ''];
        }
        
        // Obtener información del archivo
        $info_archivo = pathinfo($archivo['name']);
        $extension = strtolower($info_archivo['extension']);
        
        // Extensiones permitidas
        $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($extension, $extensiones_permitidas)) {
            $errores[] = "Formato no permitido. Use: JPG, PNG, GIF o WebP.";
            return ['success' => false, 'error' => implode(' ', $errores), 'ruta' => ''];
        }
        
        // Verificar que realmente es una imagen
        $tipo_mime = mime_content_type($archivo['tmp_name']);
        $mimes_permitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($tipo_mime, $mimes_permitidos)) {
            $errores[] = "El archivo no es una imagen válida.";
            return ['success' => false, 'error' => implode(' ', $errores), 'ruta' => ''];
        }
        
        // Generar nombre único para evitar conflictos
        $timestamp = time();
        $random = uniqid();
        $nombre_limpio = preg_replace("/[^a-zA-Z0-9]/", "", $info_archivo['filename']);
        $nombre_limpio = substr($nombre_limpio, 0, 20); // Limitar longitud
        $nombre_final = $timestamp . '_' . $nombre_limpio . '_' . $random . '.' . $extension;
        
        // Ruta completa donde se guardará
        $ruta_destino = 'images/' . $nombre_final;
        
        // Mover archivo a destino final
        if (move_uploaded_file($archivo['tmp_name'], $ruta_destino)) {
            
            // Opcional: Redimensionar si la imagen es muy grande
            redimensionarImagen($ruta_destino, 800, 800);
            
            $ruta_final = $ruta_destino;
        } else {
            $errores[] = "Error al guardar la imagen en el servidor.";
        }
    }
    
    if (!empty($errores)) {
        return ['success' => false, 'error' => implode(' ', $errores), 'ruta' => ''];
    }
    
    return ['success' => true, 'error' => '', 'ruta' => $ruta_final];
}

// Función para redimensionar imagen si es muy grande
function redimensionarImagen($ruta_imagen, $max_width = 800, $max_height = 800) {
    // Obtener dimensiones actuales
    list($width_actual, $height_actual, $tipo) = getimagesize($ruta_imagen);
    
    // Si la imagen ya es pequeña, no hacer nada
    if ($width_actual <= $max_width && $height_actual <= $max_height) {
        return;
    }
    
    // Calcular nuevas dimensiones manteniendo proporción
    $ratio = min($max_width / $width_actual, $max_height / $height_actual);
    $nuevo_width = round($width_actual * $ratio);
    $nuevo_height = round($height_actual * $ratio);
    
    // Crear imagen desde archivo según el tipo
    switch ($tipo) {
        case IMAGETYPE_JPEG:
            $imagen_original = imagecreatefromjpeg($ruta_imagen);
            break;
        case IMAGETYPE_PNG:
            $imagen_original = imagecreatefrompng($ruta_imagen);
            break;
        case IMAGETYPE_GIF:
            $imagen_original = imagecreatefromgif($ruta_imagen);
            break;
        case IMAGETYPE_WEBP:
            $imagen_original = imagecreatefromwebp($ruta_imagen);
            break;
        default:
            return; // Tipo no soportado
    }
    
    if (!$imagen_original) return;
    
    // Crear nueva imagen redimensionada
    $imagen_nueva = imagecreatetruecolor($nuevo_width, $nuevo_height);
    
    // Mantener transparencia para PNG y GIF
    if ($tipo == IMAGETYPE_PNG || $tipo == IMAGETYPE_GIF) {
        imagealphablending($imagen_nueva, false);
        imagesavealpha($imagen_nueva, true);
        $transparente = imagecolorallocatealpha($imagen_nueva, 255, 255, 255, 127);
        imagefill($imagen_nueva, 0, 0, $transparente);
    }
    
    // Redimensionar
    imagecopyresampled($imagen_nueva, $imagen_original, 0, 0, 0, 0, 
                      $nuevo_width, $nuevo_height, $width_actual, $height_actual);
    
    // Guardar imagen redimensionada
    switch ($tipo) {
        case IMAGETYPE_JPEG:
            imagejpeg($imagen_nueva, $ruta_imagen, 85); // Calidad 85%
            break;
        case IMAGETYPE_PNG:
            imagepng($imagen_nueva, $ruta_imagen, 6); // Compresión nivel 6
            break;
        case IMAGETYPE_GIF:
            imagegif($imagen_nueva, $ruta_imagen);
            break;
        case IMAGETYPE_WEBP:
            imagewebp($imagen_nueva, $ruta_imagen, 85);
            break;
    }
    
    // Liberar memoria
    imagedestroy($imagen_original);
    imagedestroy($imagen_nueva);
}

// Función para ejecutar consultas
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

// Obtener datos para los selects
$generos = ejecutarSQL("select", "SELECT * FROM generos ORDER BY nombre");
$usos = ejecutarSQL("select", "SELECT * FROM usos ORDER BY nombre");
$deportes = ejecutarSQL("select", "SELECT * FROM deportes ORDER BY nombre");
$tallas = ejecutarSQL("select", "SELECT * FROM tallas ORDER BY talla");

// Procesar acciones
$mensaje = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    
    if ($accion === 'agregar') {
        $nombre = trim($_POST['nombre']);
        $marca = trim($_POST['marca']);
        $descripcion = trim($_POST['descripcion']);
        $precio = floatval($_POST['precio']);
        $imagen_url = trim($_POST['imagen_url']); // URL manual como respaldo
        $id_genero = intval($_POST['id_genero']);
        $id_uso = intval($_POST['id_uso']);
        $id_deporte = intval($_POST['id_deporte']);
        
        $imagen_final = '';
        
        // 1. Intentar subir imagen primero
        if (isset($_FILES['imagen_archivo']) && $_FILES['imagen_archivo']['error'] === UPLOAD_ERR_OK) {
            $resultado_upload = subirImagen($_FILES['imagen_archivo']);
            if ($resultado_upload['success']) {
                $imagen_final = $resultado_upload['ruta'];
                $mensaje_imagen = " (imagen subida correctamente)";
            } else {
                $error = "Error al subir imagen: " . $resultado_upload['error'];
            }
        }
        // 2. Si no se subió imagen, usar URL manual
        else if (!empty($imagen_url)) {
            $imagen_final = $imagen_url;
            $mensaje_imagen = " (usando URL proporcionada)";
        }
        // 3. Permitir productos sin imagen
        else {
            $imagen_final = '';
            $mensaje_imagen = " (sin imagen)";
        }
        
        // Solo continuar si no hubo errores con la imagen
        if (empty($error)) {
            if (!empty($nombre) && !empty($marca) && $precio > 0) {
                $sql = "INSERT INTO productos (nombre, marca, descripcion, precio, imagen_url, id_genero, id_uso, id_deporte) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $resultado = ejecutarSQL("insert", $sql, [$nombre, $marca, $descripcion, $precio, $imagen_final, $id_genero, $id_uso, $id_deporte]);
                
                if ($resultado) {
                    $id_producto = $conexion->insert_id;
                    
                    // Agregar stock para todas las tallas si se especificó
                    if (!empty($_POST['stock_inicial']) && $_POST['stock_inicial'] > 0) {
                        $stock_inicial = intval($_POST['stock_inicial']);
                        foreach ($tallas as $talla) {
                            $sql_stock = "INSERT INTO producto_tallas (id_producto, id_talla, stock) VALUES (?, ?, ?)";
                            ejecutarSQL("insert", $sql_stock, [$id_producto, $talla['id_talla'], $stock_inicial]);
                        }
                    }
                    
                    $mensaje = "Producto agregado correctamente" . $mensaje_imagen;
                } else {
                    $error = "Error al agregar el producto en la base de datos";
                    // Si falló la BD, eliminar imagen subida para limpiar
                    if (!empty($imagen_final) && file_exists($imagen_final)) {
                        unlink($imagen_final);
                    }
                }
            } else {
                $error = "Por favor completa todos los campos obligatorios (nombre, marca, precio)";
                // Limpiar imagen si el formulario tiene errores
                if (!empty($imagen_final) && file_exists($imagen_final)) {
                    unlink($imagen_final);
                }
            }
        }
    }
    
    if ($accion === 'editar') {
        $id_producto = intval($_POST['id_producto']);
        $nombre = trim($_POST['nombre']);
        $marca = trim($_POST['marca']);
        $descripcion = trim($_POST['descripcion']);
        $precio = floatval($_POST['precio']);
        $imagen_url = trim($_POST['imagen_url']);
        $id_genero = intval($_POST['id_genero']);
        $id_uso = intval($_POST['id_uso']);
        $id_deporte = intval($_POST['id_deporte']);
        
        // Obtener imagen actual del producto
        $producto_actual = ejecutarSQL("select", "SELECT imagen_url FROM productos WHERE id_producto = ?", [$id_producto]);
        $imagen_actual = $producto_actual[0]['imagen_url'] ?? '';
        
        $imagen_final = $imagen_actual; // Por defecto mantener la actual
        $imagen_eliminada = '';
        
        // 1. Verificar si se subió nueva imagen
        if (isset($_FILES['imagen_archivo']) && $_FILES['imagen_archivo']['error'] === UPLOAD_ERR_OK) {
            $resultado_upload = subirImagen($_FILES['imagen_archivo']);
            if ($resultado_upload['success']) {
                $imagen_final = $resultado_upload['ruta'];
                $imagen_eliminada = $imagen_actual; // Marcar para eliminar la anterior
                $mensaje_imagen = " (imagen actualizada)";
            } else {
                $error = "Error al subir nueva imagen: " . $resultado_upload['error'];
            }
        }
        // 2. Si se proporcionó nueva URL manual
        else if (!empty($imagen_url) && $imagen_url !== $imagen_actual) {
            $imagen_final = $imagen_url;
            $imagen_eliminada = $imagen_actual; // Marcar para eliminar la anterior
            $mensaje_imagen = " (URL actualizada)";
        }
        // 3. Si se marcó para eliminar imagen actual
        else if (isset($_POST['eliminar_imagen']) && $_POST['eliminar_imagen'] === '1') {
            $imagen_final = '';
            $imagen_eliminada = $imagen_actual;
            $mensaje_imagen = " (imagen eliminada)";
        } else {
            $mensaje_imagen = "";
        }
        
        // Solo continuar si no hubo errores
        if (empty($error)) {
            $sql = "UPDATE productos SET nombre=?, marca=?, descripcion=?, precio=?, imagen_url=?, id_genero=?, id_uso=?, id_deporte=? WHERE id_producto=?";
            $resultado = ejecutarSQL("update", $sql, [$nombre, $marca, $descripcion, $precio, $imagen_final, $id_genero, $id_uso, $id_deporte, $id_producto]);
            
            if ($resultado) {
                // Eliminar imagen anterior si se reemplazó y existe en el servidor
                if (!empty($imagen_eliminada) && file_exists($imagen_eliminada) && strpos($imagen_eliminada, 'images/') === 0) {
                    unlink($imagen_eliminada);
                }
                $mensaje = "Producto actualizado correctamente" . $mensaje_imagen;
            } else {
                $error = "Error al actualizar el producto";
                // Si falló la actualización, eliminar nueva imagen subida
                if (!empty($imagen_final) && $imagen_final !== $imagen_actual && file_exists($imagen_final)) {
                    unlink($imagen_final);
                }
            }
        }
    }
    
    if ($accion === 'eliminar') {
        $id_producto = intval($_POST['id_producto']);
        
        // Obtener imagen antes de eliminar el producto
        $producto_eliminar = ejecutarSQL("select", "SELECT imagen_url FROM productos WHERE id_producto = ?", [$id_producto]);
        $imagen_eliminar = $producto_eliminar[0]['imagen_url'] ?? '';
        
        // Primero eliminar el stock
        ejecutarSQL("delete", "DELETE FROM producto_tallas WHERE id_producto = ?", [$id_producto]);
        
        // Luego eliminar el producto
        $resultado = ejecutarSQL("delete", "DELETE FROM productos WHERE id_producto = ?", [$id_producto]);
        
        if ($resultado) {
            // Eliminar imagen del servidor si existe
            if (!empty($imagen_eliminar) && file_exists($imagen_eliminar) && strpos($imagen_eliminar, 'images/') === 0) {
                unlink($imagen_eliminar);
            }
            $mensaje = "Producto eliminado correctamente (incluida su imagen)";
        } else {
            $error = "Error al eliminar el producto";
        }
    }
}

// Obtener productos con información completa
$sql_productos = "
    SELECT 
        p.*,
        g.nombre as genero_nombre,
        u.nombre as uso_nombre,
        d.nombre as deporte_nombre,
        COALESCE(SUM(pt.stock), 0) as stock_total
    FROM productos p
    LEFT JOIN generos g ON p.id_genero = g.id_genero
    LEFT JOIN usos u ON p.id_uso = u.id_uso
    LEFT JOIN deportes d ON p.id_deporte = d.id_deporte
    LEFT JOIN producto_tallas pt ON p.id_producto = pt.id_producto
    GROUP BY p.id_producto
    ORDER BY p.nombre
";
$productos = ejecutarSQL("select", $sql_productos);

// Obtener producto para editar si se especifica
$producto_editar = null;
if (isset($_GET['editar'])) {
    $id_editar = intval($_GET['editar']);
    $producto_editar = ejecutarSQL("select", "SELECT * FROM productos WHERE id_producto = ?", [$id_editar])[0] ?? null;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Productos - Admin</title>
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
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
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
        
        .nav-links {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .nav-links a {
            color: #667eea;
            text-decoration: none;
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
        
        .form-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 35px;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
            margin-bottom: 30px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .form-section h2 {
            color: #2c3e50;
            margin-bottom: 25px;
            font-size: 24px;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
            font-size: 14px;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            transform: translateY(-1px);
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }
        
        /* ESTILOS ESPECIALES PARA SUBIDA DE IMAGEN */
        .image-upload-section {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 2px dashed #dee2e6;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            transition: all 0.3s;
            backdrop-filter: blur(10px);
        }
        
        .image-upload-section:hover {
            border-color: #667eea;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
            transform: translateY(-2px);
        }
        
        .image-upload-section.dragover {
            border-color: #28a745;
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.1) 0%, rgba(40, 167, 69, 0.05) 100%);
            transform: scale(1.02);
        }
        
        .upload-options {
            display: flex;
            gap: 20px;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }
        
        .file-input-wrapper {
            position: relative;
            display: inline-block;
        }
        
        .file-input-wrapper input[type=file] {
            position: absolute;
            left: -9999px;
        }
        
        .file-input-button {
            display: inline-block;
            padding: 14px 28px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            border: none;
            font-size: 14px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .file-input-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        
        .or-divider {
            color: #666;
            font-weight: 600;
            position: relative;
            background: white;
            padding: 0 15px;
        }
        
        .or-divider:before {
            content: '';
            position: absolute;
            top: 50%;
            left: -30px;
            right: -30px;
            height: 1px;
            background: #dee2e6;
            z-index: -1;
        }
        
        .preview-container {
            margin-top: 20px;
            display: none;
        }
        
        .preview-image {
            max-width: 200px;
            max-height: 200px;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .current-image-section {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border: 1px solid #ffeaa7;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            backdrop-filter: blur(10px);
        }
        
        .remove-image-btn {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 12px;
            margin-left: 10px;
            transition: all 0.3s;
        }
        
        .remove-image-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
        }
        
        .btn {
            padding: 14px 28px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-block;
            text-decoration: none;
            text-align: center;
            backdrop-filter: blur(10px);
            border: 2px solid transparent;
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
        
        .btn.danger {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
        }
        
        .btn.danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(220, 53, 69, 0.4);
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
            padding: 8px 16px;
            font-size: 12px;
        }
        
        .products-table {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 20px 15px;
            text-align: left;
            font-weight: 700;
            color: #2c3e50;
            border-bottom: 2px solid #dee2e6;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        td {
            padding: 18px 15px;
            border-bottom: 1px solid rgba(222, 226, 230, 0.5);
            backdrop-filter: blur(10px);
        }
        
        tr:hover {
            background: rgba(102, 126, 234, 0.05);
            transform: scale(1.01);
            transition: all 0.2s ease;
        }
        
        .product-img {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .stock-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            backdrop-filter: blur(10px);
        }
        
        .stock-high {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
        }
        
        .stock-medium {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            color: #856404;
        }
        
        .stock-low {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
        }
        
        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            
            .nav-links {
                justify-content: center;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .actions {
                flex-direction: column;
            }
            
            .upload-options {
                flex-direction: column;
            }
            
            table {
                font-size: 12px;
            }
            
            th, td {
                padding: 12px 8px;
            }
            
            .product-img {
                width: 50px;
                height: 50px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Gestión de Productos</h1>
            <div class="nav-links">
                <a href="dashboard.php">Dashboard</a>
                <a href="admin_inventario.php">Inventario</a>
                <a href="logout.php">Cerrar Sesión</a>
            </div>
        </div>

        <?php if ($mensaje): ?>
            <div class="alert success"><?php echo $mensaje; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert error"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Formulario para agregar/editar producto -->
        <div class="form-section">
            <h2><?php echo $producto_editar ? 'Editar Producto' : 'Agregar Nuevo Producto'; ?></h2>
            <form method="POST" action="" enctype="multipart/form-data" id="formularioProducto">
                <input type="hidden" name="accion" value="<?php echo $producto_editar ? 'editar' : 'agregar'; ?>">
                <?php if ($producto_editar): ?>
                    <input type="hidden" name="id_producto" value="<?php echo $producto_editar['id_producto']; ?>">
                <?php endif; ?>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="nombre">Nombre del Producto *</label>
                        <input type="text" id="nombre" name="nombre" required 
                               value="<?php echo $producto_editar['nombre'] ?? ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="marca">Marca *</label>
                        <input type="text" id="marca" name="marca" required 
                               value="<?php echo $producto_editar['marca'] ?? ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="precio">Precio *</label>
                        <input type="number" id="precio" name="precio" step="0.01" min="0" required 
                               value="<?php echo $producto_editar['precio'] ?? ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="id_genero">Género</label>
                        <select id="id_genero" name="id_genero" required>
                            <option value="">Seleccionar género</option>
                            <?php foreach ($generos as $genero): ?>
                                <option value="<?php echo $genero['id_genero']; ?>"
                                    <?php echo ($producto_editar && $producto_editar['id_genero'] == $genero['id_genero']) ? 'selected' : ''; ?>>
                                    <?php echo $genero['nombre']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="id_uso">Uso</label>
                        <select id="id_uso" name="id_uso" required>
                            <option value="">Seleccionar uso</option>
                            <?php foreach ($usos as $uso): ?>
                                <option value="<?php echo $uso['id_uso']; ?>"
                                    <?php echo ($producto_editar && $producto_editar['id_uso'] == $uso['id_uso']) ? 'selected' : ''; ?>>
                                    <?php echo $uso['nombre']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="id_deporte">Deporte</label>
                        <select id="id_deporte" name="id_deporte" required>
                            <option value="">Seleccionar deporte</option>
                            <?php foreach ($deportes as $deporte): ?>
                                <option value="<?php echo $deporte['id_deporte']; ?>"
                                    <?php echo ($producto_editar && $producto_editar['id_deporte'] == $deporte['id_deporte']) ? 'selected' : ''; ?>>
                                    <?php echo $deporte['nombre']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <?php if (!$producto_editar): ?>
                    <div class="form-group">
                        <label for="stock_inicial">Stock Inicial (para todas las tallas)</label>
                        <input type="number" id="stock_inicial" name="stock_inicial" min="0" 
                               placeholder="Opcional: agregar stock inicial">
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- SECCIÓN DE IMAGEN MEJORADA -->
                <div class="form-group">
                    <label>Imagen del Producto</label>
                    
                    <?php if ($producto_editar && !empty($producto_editar['imagen_url'])): ?>
                        <div class="current-image-section">
                            <strong>Imagen actual:</strong>
                            <div style="display: flex; align-items: center; gap: 15px; margin-top: 10px;">
                                <img src="<?php echo htmlspecialchars($producto_editar['imagen_url']); ?>" 
                                     alt="Imagen actual" class="preview-image" style="max-width: 100px; max-height: 100px;">
                                <div>
                                    <p style="margin: 0; color: #666; font-size: 12px;">
                                        <?php echo htmlspecialchars($producto_editar['imagen_url']); ?>
                                    </p>
                                    <label style="margin-top: 10px; display: flex; align-items: center; gap: 8px;">
                                        <input type="checkbox" name="eliminar_imagen" value="1">
                                        <span style="color: #dc3545; font-size: 14px;">Eliminar imagen actual</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="image-upload-section" id="imageUploadZone">
                        <div class="upload-options">
                            <div class="file-input-wrapper">
                                <input type="file" id="imagen_archivo" name="imagen_archivo" 
                                       accept="image/jpeg,image/png,image/gif,image/webp">
                                <label for="imagen_archivo" class="file-input-button">
                                    📁 Seleccionar Imagen
                                </label>
                            </div>
                            
                            <span class="or-divider">O</span>
                            
                            <input type="url" name="imagen_url" placeholder="Pegar URL de imagen..." 
                                   style="max-width: 300px;" value="<?php echo $producto_editar['imagen_url'] ?? ''; ?>">
                        </div>
                        
                        <p style="color: #666; font-size: 12px; margin-top: 10px;">
                            📋 <strong>Formatos:</strong> JPG, PNG, GIF, WebP | 
                            📏 <strong>Tamaño máximo:</strong> 5MB | 
                            🔄 <strong>Auto-redimensión:</strong> 800x800px
                        </p>
                        
                        <div class="preview-container" id="previewContainer">
                            <p style="color: #28a745; margin-bottom: 10px;"><strong>Vista previa:</strong></p>
                            <img id="previewImage" class="preview-image" style="display: none;">
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="descripcion">Descripción</label>
                    <textarea id="descripcion" name="descripcion" 
                              placeholder="Descripción del producto..."><?php echo $producto_editar['descripcion'] ?? ''; ?></textarea>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="btn <?php echo $producto_editar ? 'success' : 'primary'; ?>">
                        <?php echo $producto_editar ? '💾 Actualizar Producto' : '➕ Agregar Producto'; ?>
                    </button>
                    <?php if ($producto_editar): ?>
                        <a href="admin_productos.php" class="btn warning">❌ Cancelar</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Tabla de productos -->
        <div class="products-table">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Imagen</th>
                        <th>Nombre</th>
                        <th>Marca</th>
                        <th>Precio</th>
                        <th>Género</th>
                        <th>Uso</th>
                        <th>Deporte</th>
                        <th>Stock Total</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($productos)): ?>
                        <tr>
                            <td colspan="10" style="text-align: center; padding: 40px; color: #666;">
                                No hay productos registrados
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($productos as $producto): ?>
                        <tr>
                            <td><?php echo $producto['id_producto']; ?></td>
                            <td>
                                <?php if ($producto['imagen_url']): ?>
                                    <img src="<?php echo htmlspecialchars($producto['imagen_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($producto['nombre']); ?>" 
                                         class="product-img"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <div style="width: 60px; height: 60px; background: #f0f0f0; border-radius: 8px; display: none; align-items: center; justify-content: center; font-size: 12px; color: #666;">
                                        Sin imagen
                                    </div>
                                <?php else: ?>
                                    <div style="width: 60px; height: 60px; background: #f0f0f0; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 12px; color: #666;">
                                        Sin imagen
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo htmlspecialchars($producto['nombre']); ?></strong></td>
                            <td><?php echo htmlspecialchars($producto['marca']); ?></td>
                            <td>$<?php echo number_format($producto['precio'], 2); ?></td>
                            <td><?php echo htmlspecialchars($producto['genero_nombre'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($producto['uso_nombre'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($producto['deporte_nombre'] ?? 'N/A'); ?></td>
                            <td>
                                <?php 
                                $stock = intval($producto['stock_total']);
                                $class = $stock > 50 ? 'stock-high' : ($stock > 10 ? 'stock-medium' : 'stock-low');
                                ?>
                                <span class="stock-badge <?php echo $class; ?>">
                                    <?php echo $stock; ?>
                                </span>
                            </td>
                            <td>
                                <div class="actions">
                                    <a href="admin_productos.php?editar=<?php echo $producto['id_producto']; ?>" 
                                       class="btn warning small">✏️ Editar</a>
                                    <a href="admin_inventario.php?producto=<?php echo $producto['id_producto']; ?>" 
                                       class="btn primary small">📊 Stock</a>
                                    <form method="POST" style="display: inline;" 
                                          onsubmit="return confirm('¿Estás seguro de eliminar este producto? Se perderá todo el stock y la imagen asociada.')">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <input type="hidden" name="id_producto" value="<?php echo $producto['id_producto']; ?>">
                                        <button type="submit" class="btn danger small">🗑️ Eliminar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Manejo de subida de archivos con drag & drop
        const uploadZone = document.getElementById('imageUploadZone');
        const fileInput = document.getElementById('imagen_archivo');
        const previewContainer = document.getElementById('previewContainer');
        const previewImage = document.getElementById('previewImage');

        // Evento para selección de archivo
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                handleImageFile(file);
            }
        });

        // Drag and drop functionality
        uploadZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            uploadZone.classList.add('dragover');
        });

        uploadZone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            uploadZone.classList.remove('dragover');
        });

        uploadZone.addEventListener('drop', function(e) {
            e.preventDefault();
            uploadZone.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                const file = files[0];
                if (file.type.startsWith('image/')) {
                    // Asignar archivo al input file
                    const dt = new DataTransfer();
                    dt.items.add(file);
                    fileInput.files = dt.files;
                    
                    handleImageFile(file);
                } else {
                    alert('⚠️ Por favor selecciona solo archivos de imagen');
                }
            }
        });

        // Función para manejar archivo de imagen
        function handleImageFile(file) {
            // Validar tamaño (5MB = 5,000,000 bytes)
            if (file.size > 5000000) {
                alert('⚠️ La imagen es muy grande. Máximo 5MB permitido.');
                fileInput.value = '';
                hidePreview();
                return;
            }

            // Validar tipo
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                alert('⚠️ Formato no permitido. Use: JPG, PNG, GIF o WebP.');
                fileInput.value = '';
                hidePreview();
                return;
            }

            // Mostrar preview
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImage.src = e.target.result;
                previewImage.style.display = 'block';
                previewContainer.style.display = 'block';
                
                // Limpiar URL manual si se subió archivo
                document.querySelector('input[name="imagen_url"]').value = '';
            };
            reader.readAsDataURL(file);

            console.log('📁 Archivo seleccionado:', file.name, '(' + (file.size/1024/1024).toFixed(2) + ' MB)');
        }

        // Función para ocultar preview
        function hidePreview() {
            previewImage.style.display = 'none';
            previewContainer.style.display = 'none';
        }

        // Limpiar preview si se ingresa URL manual
        document.querySelector('input[name="imagen_url"]').addEventListener('input', function() {
            if (this.value) {
                fileInput.value = '';
                hidePreview();
            }
        });

        // Validación de formulario antes de enviar
        document.getElementById('formularioProducto').addEventListener('submit', function(e) {
            const nombre = document.getElementById('nombre').value.trim();
            const marca = document.getElementById('marca').value.trim();
            const precio = parseFloat(document.getElementById('precio').value);

            if (!nombre || !marca || precio <= 0) {
                e.preventDefault();
                alert('⚠️ Por favor completa todos los campos obligatorios correctamente.');
                return;
            }

            // Mostrar mensaje de carga si se está subiendo imagen
            const archivo = fileInput.files[0];
            if (archivo) {
                const loadingMsg = document.createElement('div');
                loadingMsg.innerHTML = '<div style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(0,0,0,0.8); color: white; padding: 20px; border-radius: 10px; z-index: 10000; text-align: center;"><div style="margin-bottom: 10px;">📤 Subiendo imagen...</div><div style="font-size: 12px;">No cierre esta ventana</div></div>';
                document.body.appendChild(loadingMsg);
            }
        });

        // Auto-guardar formulario en localStorage para evitar pérdidas
        const formInputs = document.querySelectorAll('#formularioProducto input, #formularioProducto select, #formularioProducto textarea');
        formInputs.forEach(input => {
            // Cargar valores guardados
            const savedValue = localStorage.getItem('form_' + input.name);
            if (savedValue && !input.value) {
                input.value = savedValue;
            }

            // Guardar al cambiar
            input.addEventListener('input', function() {
                localStorage.setItem('form_' + input.name, input.value);
            });
        });

        // Limpiar localStorage al enviar exitosamente
        <?php if ($mensaje && !$error): ?>
        formInputs.forEach(input => {
            localStorage.removeItem('form_' + input.name);
        });
        <?php endif; ?>

        console.log('✅ Sistema de subida de imágenes inicializado');
    </script>
</body>
</html>