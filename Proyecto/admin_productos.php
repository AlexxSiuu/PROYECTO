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






// Función para detectar categoría automáticamente - MEJORADA CON DETECCIÓN DE EDAD
function detectarCategoria($nombre, $descripcion = '', $marca = '') {
    $texto = strtolower($nombre . ' ' . $descripcion . ' ' . $marca);
    
    // Detectar si es específicamente para niños
    $esNinos = preg_match('/\b(niño|niña|niños|niñas|infantil|kids|junior|child|bebé|bebe|baby)\b/i', $texto);
    
    // CALCETINES / MEDIAS
    if (preg_match('/\b(calcet|calceta|media|medias|sock|stocking)\b/i', $texto)) {
        return $esNinos ? 'calcetines_ninos' : 'calcetines_adultos';
    }
    
    // CALZADO (con marcas y sinónimos)
    if (preg_match('/\b(zapato|tenis|tennis|zapatilla|bota|botín|sandalia|sneaker|calzado|shoe|shoes)\b/i', $texto) ||
        preg_match('/\b(air|max|force|jordan|slides|mercurial|vapor|cloudfoam|copa|mundial|stan|smith)\b/i', $texto) ||
        preg_match('/\b(runner|running|futbol|soccer|basketball|skate)\b/i', $texto) ||
        preg_match('/\b(adidas.*(pure|copa|stan)|nike.*(air|jordan|mercurial)|puma.*(suede|rs)|converse|vans|new.*balance|reebok|under.*armour|asics|mizuno|lotto|diadora)\b/i', $texto)) {
        return $esNinos ? 'calzado_ninos' : 'calzado_adultos';
    }
    
    // ACCESORIOS (talla única)
    if (preg_match('/\b(gorra|cap|sombrero|balon|balón|pelota|ball|guante|reloj|banda|accesorio|mochila|bolsa|bag|cinturón|espinillera|rodillera|muñequera|botella)\b/i', $texto)) {
        return 'accesorios';
    }
    
    // ROPA
    if (preg_match('/\b(camiseta|camisa|polo|playera|blusa|top|jersey|shirt|tee|dri.?fit|pro|uniforme|conjunto|ropa ligera)\b/i', $texto)) {
        return $esNinos ? 'ropa_ninos' : 'ropa_adultos';
    }
    
    if (preg_match('/\b(pantalon|pantalón|short|bermuda|leggin|legging|jogger|pants|tiro)\b/i', $texto)) {
        return $esNinos ? 'ropa_ninos' : 'ropa_adultos';
    }
    
    if (preg_match('/\b(sudadera|hoodie|capucha|buzo|chaqueta|jacket|chamarra)\b/i', $texto)) {
        return $esNinos ? 'ropa_ninos' : 'ropa_adultos';
    }
    
    // Fallback
    return 'general';
}


// Función para obtener tallas según categoría detectada
function obtenerTallasPorCategoria($categoria) {
    $todas_las_tallas = [
        // CALZADO
        'calzado_ninos' => [
            'tallas' => ['23', '24', '25', '26', '27', '28', '29', '30', '31', '32', '33'],
            'descripcion' => 'Tallas numéricas para calzado infantil (23-33)'
        ],
        'calzado_adultos' => [
            'tallas' => ['35', '36', '37', '38', '39', '40', '41', '42', '43', '44', '45', '46'],
            'descripcion' => 'Tallas numéricas para calzado de adultos (35-46)'
        ],
        
        // ROPA
        'ropa_ninos' => [
            'tallas' => ['XS', 'S', 'M', 'L', 'XL'],
            'descripcion' => 'Tallas para ropa infantil (XS-XL)'
        ],
        'ropa_adultos' => [
            'tallas' => ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'],
            'descripcion' => 'Tallas para ropa de adultos (XS-XXXL)'
        ],
        
        // CALCETINES
        'calcetines_ninos' => [
            'tallas' => ['S (23-26)', 'M (27-30)', 'L (31-33)'],
            'descripcion' => 'Tallas para calcetines infantiles'
        ],
        'calcetines_adultos' => [
            'tallas' => ['S (35-38)', 'M (39-42)', 'L (43-46)'],
            'descripcion' => 'Tallas para calcetines de adultos'
        ],
        
        // ACCESORIOS
        'accesorios' => [
            'tallas' => ['ÚNICA'],
            'descripcion' => 'Talla única para accesorios (gorras, mochilas, balones, etc.)'
        ],
        
        // GENERAL -> BLOQUEADO
        'general' => [
            'tallas' => ['SIN TALLA'],
            'descripcion' => 'Categoría no reconocida. Revisar manualmente.'
        ]
    ];
    
    return $todas_las_tallas[$categoria] ?? $todas_las_tallas['general'];
}








// Función para crear tallas en BD si no existen
function crearTallaSiNoExiste($talla) {
    global $conexion;
    
    // Verificar si la talla ya existe
    $stmt = $conexion->prepare("SELECT id_talla FROM tallas WHERE talla = ?");
    $stmt->bind_param("s", $talla);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Ya existe, devolver ID
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row['id_talla'];
    } else {
        // No existe, crear nueva
        $stmt->close();
        $stmt = $conexion->prepare("INSERT INTO tallas (talla) VALUES (?)");
        $stmt->bind_param("s", $talla);
        $stmt->execute();
        $id_talla = $conexion->insert_id;
        $stmt->close();
        return $id_talla;
    }
}

// Función para subir imagen
function subirImagen($archivo) {
    $errores = [];
    $ruta_final = '';
    
    if (isset($archivo) && $archivo['error'] === UPLOAD_ERR_OK) {
        
        if ($archivo['size'] > 5000000) {
            $errores[] = "La imagen es muy grande. Máximo 5MB permitido.";
            return ['success' => false, 'error' => implode(' ', $errores), 'ruta' => ''];
        }
        
        $info_archivo = pathinfo($archivo['name']);
        $extension = strtolower($info_archivo['extension']);
        
        $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($extension, $extensiones_permitidas)) {
            $errores[] = "Formato no permitido. Use: JPG, PNG, GIF o WebP.";
            return ['success' => false, 'error' => implode(' ', $errores), 'ruta' => ''];
        }
        
        $tipo_mime = mime_content_type($archivo['tmp_name']);
        $mimes_permitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($tipo_mime, $mimes_permitidos)) {
            $errores[] = "El archivo no es una imagen válida.";
            return ['success' => false, 'error' => implode(' ', $errores), 'ruta' => ''];
        }
        
        $timestamp = time();
        $random = uniqid();
        $nombre_limpio = preg_replace("/[^a-zA-Z0-9]/", "", $info_archivo['filename']);
        $nombre_limpio = substr($nombre_limpio, 0, 20);
        $nombre_final = $timestamp . '_' . $nombre_limpio . '_' . $random . '.' . $extension;
        
        $ruta_destino = 'images/' . $nombre_final;
        
        if (move_uploaded_file($archivo['tmp_name'], $ruta_destino)) {
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

// Función para redimensionar imagen
function redimensionarImagen($ruta_imagen, $max_width = 800, $max_height = 800) {
    list($width_actual, $height_actual, $tipo) = getimagesize($ruta_imagen);
    
    if ($width_actual <= $max_width && $height_actual <= $max_height) {
        return;
    }
    
    $ratio = min($max_width / $width_actual, $max_height / $height_actual);
    $nuevo_width = round($width_actual * $ratio);
    $nuevo_height = round($height_actual * $ratio);
    
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
            return;
    }
    
    if (!$imagen_original) return;
    
    $imagen_nueva = imagecreatetruecolor($nuevo_width, $nuevo_height);
    
    if ($tipo == IMAGETYPE_PNG || $tipo == IMAGETYPE_GIF) {
        imagealphablending($imagen_nueva, false);
        imagesavealpha($imagen_nueva, true);
        $transparente = imagecolorallocatealpha($imagen_nueva, 255, 255, 255, 127);
        imagefill($imagen_nueva, 0, 0, $transparente);
    }
    
    imagecopyresampled($imagen_nueva, $imagen_original, 0, 0, 0, 0, 
                      $nuevo_width, $nuevo_height, $width_actual, $height_actual);
    
    switch ($tipo) {
        case IMAGETYPE_JPEG:
            imagejpeg($imagen_nueva, $ruta_imagen, 85);
            break;
        case IMAGETYPE_PNG:
            imagepng($imagen_nueva, $ruta_imagen, 6);
            break;
        case IMAGETYPE_GIF:
            imagegif($imagen_nueva, $ruta_imagen);
            break;
        case IMAGETYPE_WEBP:
            imagewebp($imagen_nueva, $ruta_imagen, 85);
            break;
    }
    
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

// Procesar acciones
$mensaje = "";
$error = "";
$categoria_detectada = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    
    if ($accion === 'agregar') {
        $nombre = trim($_POST['nombre']);
        $marca = trim($_POST['marca']);
        $descripcion = trim($_POST['descripcion']);
        $precio = floatval($_POST['precio']);
        $id_genero = intval($_POST['id_genero']);
        $id_uso = intval($_POST['id_uso']);
        $id_deporte = intval($_POST['id_deporte']);
        
        // Detectar categoría automáticamente
        $categoria = detectarCategoria($nombre, $descripcion, $marca);
        $tallas_categoria = obtenerTallasPorCategoria($categoria);
// 🚨 Bloqueo si no se reconoce categoría
if ($categoria === 'general') {

    // Deshabilitar el botón de agregar automáticamente
    echo '
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const btnAgregar = document.querySelector("button[type=\'submit\']");
            if(btnAgregar){
                btnAgregar.disabled = true;
                btnAgregar.style.backgroundColor = "#ccc";
                btnAgregar.style.cursor = "not-allowed";
            }
        });
    </script>
    ';

    // Mostrar mensaje flotante con redirección al cerrar
    echo '
    <div style="
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        max-width: 500px;
        padding: 20px;
        border: 2px solid #f44336;
        background-color: #ffe6e6;
        color: #b71c1c;
        border-radius: 10px;
        text-align: center;
        font-family: Arial, sans-serif;
        box-shadow: 0 4px 10px rgba(0,0,0,0.3);
        z-index: 9999;
    ">
        <h2 style="margin-bottom: 10px;">❌ Categoría no reconocida</h2>
        <p style="margin-bottom: 20px;">
            El sistema no pudo determinar la categoría de este producto.<br>
            Por favor revisa el nombre, descripción o marca antes de continuar.
        </p>
        <button onclick="window.location.href=\'admin_productos.php\';" style="
            padding: 10px 20px;
            background-color: #f44336;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        ">
            Cerrar
        </button>
    </div>
    ';

    // Detener la ejecución para que NO se inserte el producto
    exit;
}



        
        $imagen_final = '';
        $mensaje_imagen = '';
        
        // Manejo de imagen
        if (isset($_FILES['imagen_archivo']) && $_FILES['imagen_archivo']['error'] === UPLOAD_ERR_OK) {
            $resultado_upload = subirImagen($_FILES['imagen_archivo']);
            if ($resultado_upload['success']) {
                $imagen_final = $resultado_upload['ruta'];
                $mensaje_imagen = " (imagen subida correctamente)";
            } else {
                $error = "Error al subir imagen: " . $resultado_upload['error'];
            }
        } else {
            $imagen_final = '';
            $mensaje_imagen = " (sin imagen)";
        }
        
        if (empty($error)) {
            if (!empty($nombre) && !empty($marca) && $precio > 0) {
                $sql = "INSERT INTO productos (nombre, marca, descripcion, precio, imagen_url, id_genero, id_uso, id_deporte) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $resultado = ejecutarSQL("insert", $sql, [$nombre, $marca, $descripcion, $precio, $imagen_final, $id_genero, $id_uso, $id_deporte]);
                
                if ($resultado) {
                    $id_producto = $conexion->insert_id;
                    
                    // Crear stock para las tallas específicas de esta categoría
                    $stock_inicial = intval($_POST['stock_inicial'] ?? 0);
                    $tallas_creadas = 0;
                    
                    foreach ($tallas_categoria['tallas'] as $talla_nombre) {
                        // Crear talla si no existe
                        $id_talla = crearTallaSiNoExiste($talla_nombre);
                        
                        // Agregar stock si se especificó
                        if ($stock_inicial > 0) {
                            $sql_stock = "INSERT INTO producto_tallas (id_producto, id_talla, stock) VALUES (?, ?, ?)";
                            ejecutarSQL("insert", $sql_stock, [$id_producto, $id_talla, $stock_inicial]);
                        } else {
                            // Crear registro con stock 0 para tener la estructura
                            $sql_stock = "INSERT INTO producto_tallas (id_producto, id_talla, stock) VALUES (?, ?, 0)";
                            ejecutarSQL("insert", $sql_stock, [$id_producto, $id_talla]);
                        }
                        $tallas_creadas++;
                    }
                    
                    $mensaje = "Producto agregado correctamente$mensaje_imagen. Categoría detectada: <strong>$categoria</strong>. Se crearon $tallas_creadas tallas específicas: " . implode(', ', $tallas_categoria['tallas']);
                } else {
                    $error = "Error al agregar el producto en la base de datos";
                    if (!empty($imagen_final) && file_exists($imagen_final)) {
                        unlink($imagen_final);
                    }
                }
            } else {
                $error = "Por favor completa todos los campos obligatorios (nombre, marca, precio)";
                if (!empty($imagen_final) && file_exists($imagen_final)) {
                    unlink($imagen_final);
                }
            }
        }
    }
    
    // Resto de acciones (editar, eliminar)
    if ($accion === 'editar') {
        $id_producto = intval($_POST['id_producto']);
        $nombre = trim($_POST['nombre']);
        $marca = trim($_POST['marca']);
        $descripcion = trim($_POST['descripcion']);
        $precio = floatval($_POST['precio']);
        $id_genero = intval($_POST['id_genero']);
        $id_uso = intval($_POST['id_uso']);
        $id_deporte = intval($_POST['id_deporte']);
        
        $producto_actual = ejecutarSQL("select", "SELECT imagen_url FROM productos WHERE id_producto = ?", [$id_producto]);
        $imagen_actual = $producto_actual[0]['imagen_url'] ?? '';
        
        $imagen_final = $imagen_actual;
        $imagen_eliminada = '';
        $mensaje_imagen = '';
        
        if (isset($_FILES['imagen_archivo']) && $_FILES['imagen_archivo']['error'] === UPLOAD_ERR_OK) {
            $resultado_upload = subirImagen($_FILES['imagen_archivo']);
            if ($resultado_upload['success']) {
                $imagen_final = $resultado_upload['ruta'];
                $imagen_eliminada = $imagen_actual;
                $mensaje_imagen = " (imagen actualizada)";
            } else {
                $error = "Error al subir nueva imagen: " . $resultado_upload['error'];
            }
        } else if (isset($_POST['eliminar_imagen']) && $_POST['eliminar_imagen'] === '1') {
            $imagen_final = '';
            $imagen_eliminada = $imagen_actual;
            $mensaje_imagen = " (imagen eliminada)";
        }
        
        if (empty($error)) {
            $sql = "UPDATE productos SET nombre=?, marca=?, descripcion=?, precio=?, imagen_url=?, id_genero=?, id_uso=?, id_deporte=? WHERE id_producto=?";
            $resultado = ejecutarSQL("update", $sql, [$nombre, $marca, $descripcion, $precio, $imagen_final, $id_genero, $id_uso, $id_deporte, $id_producto]);
            
            if ($resultado) {
                if (!empty($imagen_eliminada) && file_exists($imagen_eliminada) && strpos($imagen_eliminada, 'images/') === 0) {
                    unlink($imagen_eliminada);
                }
                $mensaje = "Producto actualizado correctamente" . $mensaje_imagen;
            } else {
                $error = "Error al actualizar el producto";
                if (!empty($imagen_final) && $imagen_final !== $imagen_actual && file_exists($imagen_final)) {
                    unlink($imagen_final);
                }
            }
        }
    }
    
    if ($accion === 'eliminar') {
        $id_producto = intval($_POST['id_producto']);
        
        $producto_eliminar = ejecutarSQL("select", "SELECT imagen_url FROM productos WHERE id_producto = ?", [$id_producto]);
        $imagen_eliminar = $producto_eliminar[0]['imagen_url'] ?? '';
        
        ejecutarSQL("delete", "DELETE FROM producto_tallas WHERE id_producto = ?", [$id_producto]);
        
        $resultado = ejecutarSQL("delete", "DELETE FROM productos WHERE id_producto = ?", [$id_producto]);
        
        if ($resultado) {
            if (!empty($imagen_eliminar) && file_exists($imagen_eliminar) && strpos($imagen_eliminar, 'images/') === 0) {
                unlink($imagen_eliminar);
            }
            $mensaje = "Producto eliminado correctamente (incluida su imagen)";
        } else {
            $error = "Error al eliminar el producto";
        }
    }
    
    // Vista previa de categoría para AJAX
    if ($accion === 'preview_categoria') {
        $nombre = $_POST['nombre'] ?? '';
        $descripcion = $_POST['descripcion'] ?? '';
        $marca = $_POST['marca'] ?? '';
        
        $categoria = detectarCategoria($nombre, $descripcion, $marca);
        $tallas_categoria = obtenerTallasPorCategoria($categoria);
        
        header('Content-Type: application/json');
        echo json_encode([
            'categoria' => $categoria,
            'tallas' => $tallas_categoria['tallas'],
            'descripcion' => $tallas_categoria['descripcion']
        ]);
        exit;
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

// Si hay producto para editar, detectar su categoría
if ($producto_editar) {
    $categoria_detectada = detectarCategoria($producto_editar['nombre'], $producto_editar['descripcion'], $producto_editar['marca']);
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
        
        /* SECCIÓN DE CATEGORÍA AUTOMÁTICA */
        .categoria-preview {
            background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
            border: 2px dashed #667eea;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
            text-align: center;
            display: none;
            backdrop-filter: blur(10px);
        }
        
        .categoria-preview.show {
            display: block;
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .categoria-info {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .categoria-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
            text-transform: capitalize;
        }
        
        .tallas-preview {
            display: flex;
            gap: 8px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 10px;
        }
        
        .talla-chip {
            background: rgba(255, 255, 255, 0.8);
            color: #2c3e50;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            border: 1px solid rgba(102, 126, 234, 0.3);
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
        
        /* ESTILOS PARA SUBIDA DE IMAGEN */
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
        
        .image-upload-wrapper {
            position: relative;
            display: inline-block;
        }
        
        /* OCULTAR INPUT FILE COMPLETAMENTE */
        .hidden-file-input {
            position: absolute !important;
            left: -9999px !important;
            top: -9999px !important;
            opacity: 0 !important;
            width: 0.1px !important;
            height: 0.1px !important;
            overflow: hidden !important;
            z-index: -1 !important;
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
        
        .preview-container {
            margin-top: 20px;
            display: none;
        }
        
        .preview-container.show {
            display: block;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
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
        
        .btn.warning {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            color: #212529;
            box-shadow: 0 4px 15px rgba(255, 193, 7, 0.3);
        }
        
        .btn.warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 193, 7, 0.4);
        }
        
        .products-table {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
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
        
        .product-category {
            font-size: 11px;
            padding: 3px 8px;
            border-radius: 10px;
            font-weight: 600;
            text-transform: capitalize;
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
            display: inline-block;
            margin-top: 5px;
        }
        
        @media (max-width: 768px) {
            .categoria-info {
                flex-direction: column;
            }
            
            .tallas-preview {
                justify-content: center;
            }
            
            .container {
                padding: 10px;
            }
            
            .header {
                flex-direction: column;
                gap: 15px;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
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
            
            <!-- Vista previa de categoría automática -->
            <div class="categoria-preview" id="categoria-preview" <?php echo $categoria_detectada ? 'style="display: block;"' : ''; ?>>
                <div class="categoria-info">
                    <span>🤖 <strong>Categoría detectada:</strong></span>
                    <span class="categoria-badge" id="categoria-nombre"><?php echo $categoria_detectada ?: 'general'; ?></span>
                </div>
                <div class="tallas-preview" id="tallas-preview">
                    <?php if ($categoria_detectada): ?>
                        <?php $tallas_cat = obtenerTallasPorCategoria($categoria_detectada); ?>
                        <?php foreach ($tallas_cat['tallas'] as $talla): ?>
                            <span class="talla-chip"><?php echo $talla; ?></span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <p style="color: #666; font-size: 12px; margin-top: 10px;" id="categoria-descripcion">
                    <?php echo $categoria_detectada ? obtenerTallasPorCategoria($categoria_detectada)['descripcion'] : ''; ?>
                </p>
            </div>
            
            <form method="POST" action="" enctype="multipart/form-data" id="formularioProducto">
                <input type="hidden" name="accion" value="<?php echo $producto_editar ? 'editar' : 'agregar'; ?>">
                <?php if ($producto_editar): ?>
                    <input type="hidden" name="id_producto" value="<?php echo $producto_editar['id_producto']; ?>">
                <?php endif; ?>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="nombre">Nombre del Producto *</label>
                        <input type="text" id="nombre" name="nombre" required 
                               value="<?php echo $producto_editar['nombre'] ?? ''; ?>"
                               onchange="actualizarCategoriaPreview()">
                    </div>
                    
                    <div class="form-group">
                        <label for="marca">Marca *</label>
                        <input type="text" id="marca" name="marca" required 
                               value="<?php echo $producto_editar['marca'] ?? ''; ?>"
                               onchange="actualizarCategoriaPreview()">
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
                        <label for="stock_inicial">Stock Inicial (para todas las tallas detectadas)</label>
                        <input type="number" id="stock_inicial" name="stock_inicial" min="0" 
                               placeholder="Opcional: agregar stock inicial">
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Sección de imagen SIMPLIFICADA -->
                <div class="form-group">
                    <label>Imagen del Producto</label>
                    
                    <?php if ($producto_editar && !empty($producto_editar['imagen_url'])): ?>
                        <div class="current-image-section" style="margin-bottom: 20px;">
                            <strong>Imagen actual:</strong>
                            <div style="display: flex; align-items: center; gap: 15px; margin-top: 10px;">
                                <img src="<?php echo htmlspecialchars($producto_editar['imagen_url']); ?>" 
                                     alt="Imagen actual" style="max-width: 100px; max-height: 100px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                                <div>
                                    <p style="margin: 0; color: #666; font-size: 12px;">
                                        Imagen actual del producto
                                    </p>
                                    <label style="margin-top: 10px; display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                        <input type="checkbox" name="eliminar_imagen" value="1">
                                        <span style="color: #dc3545; font-size: 14px;">Eliminar imagen actual</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="image-upload-section" id="imageUploadZone">
                        <!-- INPUT FILE COMPLETAMENTE OCULTO -->
                        <input type="file" id="imagen_archivo" name="imagen_archivo" 
                               accept="image/jpeg,image/png,image/gif,image/webp" class="hidden-file-input">
                        
                        <!-- SOLO EL BOTÓN PERSONALIZADO VISIBLE -->
                        <button type="button" class="file-input-button" onclick="document.getElementById('imagen_archivo').click()">
                            📁 Seleccionar Imagen
                        </button>
                        
                        <p style="color: #666; font-size: 12px; margin-top: 15px;">
                            📋 <strong>Formatos:</strong> JPG, PNG, GIF, WebP | 
                            📏 <strong>Tamaño máximo:</strong> 5MB | 
                            🔄 <strong>Auto-redimensión:</strong> 800x800px
                        </p>
                        
                        <div class="preview-container" id="previewContainer">
                            <p style="color: #28a745; margin-bottom: 10px;"><strong>Vista previa:</strong></p>
                            <img id="previewImage" style="max-width: 200px; max-height: 200px; border-radius: 12px; box-shadow: 0 8px 25px rgba(0,0,0,0.15);">
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="descripcion">Descripción</label>
                    <textarea id="descripcion" name="descripcion" 
                              placeholder="Descripción del producto..."
                              onchange="actualizarCategoriaPreview()"><?php echo $producto_editar['descripcion'] ?? ''; ?></textarea>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="btn primary">
                        <?php echo $producto_editar ? 'Actualizar Producto' : 'Agregar Producto'; ?>
                    </button>
                    <?php if ($producto_editar): ?>
                        <a href="admin_productos.php" class="btn warning">Cancelar</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Tabla de productos con categorías -->
        <div class="products-table">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Producto</th>
                        <th>Precio</th>
                        <th>Categoría</th>
                        <th>Stock</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($productos)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px; color: #666;">
                                No hay productos registrados
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($productos as $producto): ?>
                        <tr>
                            <td><?php echo $producto['id_producto']; ?></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <?php if ($producto['imagen_url']): ?>
                                        <img src="<?php echo htmlspecialchars($producto['imagen_url']); ?>" 
                                             style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;">
                                    <?php endif; ?>
                                    <div>
                                        <strong><?php echo htmlspecialchars($producto['nombre']); ?></strong><br>
                                        <small style="color: #666;"><?php echo htmlspecialchars($producto['marca']); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><strong>$<?php echo number_format($producto['precio'], 2); ?></strong></td>
                            <td>
                                <?php 
                                $categoria_producto = detectarCategoria($producto['nombre'], $producto['descripcion'], $producto['marca']);
                                ?>
                                <span class="product-category"><?php echo $categoria_producto; ?></span>
                            </td>
                            <td>
                                <?php 
                                $stock = intval($producto['stock_total']);
                                $color = $stock > 50 ? '#28a745' : ($stock > 10 ? '#ffc107' : '#dc3545');
                                ?>
                                <span style="color: <?php echo $color; ?>; font-weight: bold;">
                                    <?php echo $stock; ?> unidades
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                    <a href="admin_productos.php?editar=<?php echo $producto['id_producto']; ?>" 
                                       class="btn warning" style="padding: 6px 12px; font-size: 12px;">Editar</a>
                                    <a href="admin_inventario.php?producto=<?php echo $producto['id_producto']; ?>" 
                                       class="btn primary" style="padding: 6px 12px; font-size: 12px;">Stock</a>
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
        // Función para actualizar vista previa de categoría
        function actualizarCategoriaPreview() {
            const nombre = document.getElementById('nombre').value;
            const marca = document.getElementById('marca').value;
            const descripcion = document.getElementById('descripcion').value;
            
            if (!nombre.trim()) {
                document.getElementById('categoria-preview').classList.remove('show');
                return;
            }
            
            // Hacer petición AJAX para obtener categoría
            const formData = new FormData();
            formData.append('accion', 'preview_categoria');
            formData.append('nombre', nombre);
            formData.append('marca', marca);
            formData.append('descripcion', descripcion);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('categoria-nombre').textContent = data.categoria;
                document.getElementById('categoria-descripcion').textContent = data.descripcion;
                
                const tallasContainer = document.getElementById('tallas-preview');
                tallasContainer.innerHTML = '';
                
                data.tallas.forEach(talla => {
                    const span = document.createElement('span');
                    span.className = 'talla-chip';
                    span.textContent = talla;
                    tallasContainer.appendChild(span);
                });
                
                document.getElementById('categoria-preview').classList.add('show');
            })
            .catch(error => {
                console.log('Error detectando categoría:', error);
            });
        }
        
        // Manejo de subida de archivos con vista previa
        const fileInput = document.getElementById('imagen_archivo');
        const previewContainer = document.getElementById('previewContainer');
        const previewImage = document.getElementById('previewImage');
        
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            
            if (file) {
                // Validar tipo de archivo
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Por favor selecciona una imagen válida (JPG, PNG, GIF, WebP)');
                    this.value = '';
                    previewContainer.classList.remove('show');
                    return;
                }
                
                // Validar tamaño (5MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert('La imagen es muy grande. Máximo 5MB permitido.');
                    this.value = '';
                    previewContainer.classList.remove('show');
                    return;
                }
                
                // Mostrar vista previa
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImage.src = e.target.result;
                    previewContainer.classList.add('show');
                };
                reader.readAsDataURL(file);
            } else {
                previewContainer.classList.remove('show');
            }
        });
        
        // Actualizar categoría al cargar si hay datos
        document.addEventListener('DOMContentLoaded', function() {
            const nombre = document.getElementById('nombre').value;
            if (nombre.trim()) {
                actualizarCategoriaPreview();
            }
        });
        
        // Drag and drop funcionalidad
        const uploadZone = document.getElementById('imageUploadZone');
        
        uploadZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.style.borderColor = '#667eea';
            this.style.backgroundColor = 'rgba(102, 126, 234, 0.1)';
        });
        
        uploadZone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.style.borderColor = '#dee2e6';
            this.style.backgroundColor = '';
        });
        
        uploadZone.addEventListener('drop', function(e) {
            e.preventDefault();
            this.style.borderColor = '#dee2e6';
            this.style.backgroundColor = '';
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                // Disparar evento change manualmente
                const event = new Event('change', { bubbles: true });
                fileInput.dispatchEvent(event);
            }
        });
    </script>
</body>
</html>