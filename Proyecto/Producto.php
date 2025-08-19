<?php
session_start();
include('conexion.php');

// Función mejorada con prepared statements
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
        $types = str_repeat('i', count($params)); // 'i' para enteros
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

// Obtener ID del producto desde la URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    header("Location: PROYECTO.php");
    exit("ID de producto inválido.");
}

// Obtener datos del producto con prepared statement
$sqlProducto = "SELECT p.*, 
                       g.nombre AS genero, 
                       u.nombre AS uso, 
                       d.nombre AS deporte
                FROM productos p
                LEFT JOIN generos g ON p.id_genero = g.id_genero
                LEFT JOIN usos u ON p.id_uso = u.id_uso
                LEFT JOIN deportes d ON p.id_deporte = d.id_deporte
                WHERE p.id_producto = ?";

$productos = ejecutarSQL("select", $sqlProducto, [$id]);
$producto = $productos ? $productos[0] : null;

if (!$producto) {
    header("Location: PROYECTO.php");
    exit("Producto no encontrado.");
}

// Obtener tallas disponibles con stock
$sqlTallas = "SELECT t.id_talla, t.talla, pt.stock
              FROM producto_tallas pt
              JOIN tallas t ON pt.id_talla = t.id_talla
              WHERE pt.id_producto = ? AND pt.stock > 0
              ORDER BY t.id_talla";
$tallas = ejecutarSQL("select", $sqlTallas, [$id]);

// Productos relacionados (misma categoría o deporte)
$sqlRelacionados = "SELECT p.id_producto, p.nombre, p.marca, p.precio, p.imagen_url
                    FROM productos p
                    JOIN producto_tallas pt ON p.id_producto = pt.id_producto
                    WHERE p.id_producto != ? 
                    AND pt.stock > 0
                    AND (p.id_genero = ? OR p.id_uso = ? OR p.id_deporte = ?)
                    GROUP BY p.id_producto
                    ORDER BY RAND()
                    LIMIT 4";
$relacionados = ejecutarSQL("select", $sqlRelacionados, [$id, $producto->id_genero, $producto->id_uso, $producto->id_deporte]);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($producto->nombre) ?> - JERSEYKING</title>
    <link rel="stylesheet" href="prueba.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
            color: #111;
        }

        /* Header simple para navegación */
        .header-simple {
            background: #000;
            padding: 15px 0;
            margin-bottom: 40px;
        }
        
        .header-simple .navbar {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }
        
        .header-simple .logo a {
            color: white;
            text-decoration: none;
            font-size: 24px;
            font-weight: bold;
        }
        
        .header-simple .nav-back a {
            color: white;
            text-decoration: none;
            font-size: 16px;
        }
        
        .header-simple .nav-back a:hover {
            text-decoration: underline;
        }

        /* Breadcrumb */
        .breadcrumb {
            max-width: 1200px;
            margin: 0 auto 30px;
            padding: 0 40px;
            font-size: 14px;
            color: #666;
        }
        
        .breadcrumb a {
            color: #007bff;
            text-decoration: none;
        }
        
        .breadcrumb a:hover {
            text-decoration: underline;
        }

        /* Contenedor principal del producto */
        .detalle-container {
            display: flex;
            max-width: 1200px;
            margin: 0 auto;
            gap: 60px;
            align-items: flex-start;
            padding: 0 40px;
        }

        .detalle-imagen {
            flex: 1;
            display: flex;
            justify-content: center;
        }

        .detalle-imagen img {
            width: 100%;
            max-width: 500px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .detalle-info {
            flex: 1;
            max-width: 500px;
        }

        .categorias-info {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            font-size: 13px;
            color: #666;
        }

        .categoria-tag {
            background: #f0f0f0;
            padding: 4px 8px;
            border-radius: 4px;
        }

        .marca {
            text-transform: uppercase;
            font-size: 14px;
            color: #888;
            margin-bottom: 10px;
            font-weight: bold;
        }

        h1 {
            font-size: 32px;
            margin-bottom: 15px;
            line-height: 1.2;
        }

        .descripcion {
            font-size: 16px;
            color: #333;
            margin-bottom: 25px;
            line-height: 1.5;
        }

        .precio {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 30px;
            color: #000;
        }

        .form-producto {
            background: #f9f9f9;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        label {
            font-weight: bold;
            margin-bottom: 8px;
            display: block;
            font-size: 16px;
        }

        select {
            padding: 12px;
            font-size: 16px;
            width: 100%;
            margin-bottom: 20px;
            border-radius: 6px;
            border: 2px solid #ddd;
            background: white;
        }

        select:focus {
            border-color: #007bff;
            outline: none;
        }

        .cantidad-container {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
        }

        .cantidad-input {
            width: 80px;
            padding: 8px;
            text-align: center;
            border: 2px solid #ddd;
            border-radius: 4px;
        }

        button {
            background-color: #000;
            color: white;
            border: none;
            padding: 16px 24px;
            font-size: 16px;
            border-radius: 6px;
            width: 100%;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: #333;
        }

        button:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }

        .no-tallas {
            background: #ffe6e6;
            color: #d63384;
            padding: 15px;
            border-radius: 6px;
            text-align: center;
            font-weight: bold;
        }

        /* Productos relacionados */
        .relacionados {
            max-width: 1200px;
            margin: 80px auto 40px;
            padding: 0 40px;
        }

        .relacionados h2 {
            text-align: center;
            margin-bottom: 40px;
            font-size: 28px;
        }

        .relacionados-contenedor {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
        }

        .relacionado-item {
            background: #fafafa;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .relacionado-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .relacionado-item img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .relacionado-item .contenido {
            padding: 15px;
        }

        .relacionado-item h4 {
            margin: 0 0 8px;
            font-size: 16px;
            color: #000;
        }

        .relacionado-item .marca-rel {
            font-size: 14px;
            color: #666;
            margin-bottom: 8px;
        }

        .relacionado-item .precio-rel {
            font-weight: bold;
            color: #000;
            font-size: 18px;
        }

        .relacionado-item a {
            text-decoration: none;
            color: inherit;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .detalle-container {
                flex-direction: column;
                gap: 30px;
                padding: 0 20px;
            }
            
            h1 {
                font-size: 24px;
            }
            
            .precio {
                font-size: 24px;
            }
            
            .relacionados {
                padding: 0 20px;
            }
        }
    </style>
</head>
<body>

<!-- Header simple -->
<header class="header-simple">
    <nav class="navbar">
        <div class="logo">
            <a href="PROYECTO.php">JERSEYKING</a>
        </div>
        <div class="nav-back">
            <a href="javascript:history.back()">← Volver</a>
        </div>
    </nav>
</header>

<!-- Breadcrumb -->
<div class="breadcrumb">
    <a href="PROYECTO.php">Inicio</a> > 
    <a href="productos.php?genero=<?= $producto->id_genero ?>"><?= htmlspecialchars($producto->genero) ?></a> > 
    <a href="productos.php?genero=<?= $producto->id_genero ?>&uso=<?= $producto->id_uso ?>"><?= htmlspecialchars($producto->uso) ?></a> > 
    <span><?= htmlspecialchars($producto->nombre) ?></span>
</div>

<!-- Producto principal -->
<div class="detalle-container">
    <div class="detalle-imagen">
        <img src="<?= htmlspecialchars($producto->imagen_url) ?>" 
             alt="<?= htmlspecialchars($producto->nombre) ?>"
             onerror="this.src='img/placeholder.jpg'">
    </div>
    
    <div class="detalle-info">
        <!-- Información de categorías -->
        <div class="categorias-info">
            <span class="categoria-tag"><?= htmlspecialchars($producto->genero) ?></span>
            <span class="categoria-tag"><?= htmlspecialchars($producto->uso) ?></span>
            <span class="categoria-tag"><?= htmlspecialchars($producto->deporte) ?></span>
        </div>
        
        <div class="marca"><?= htmlspecialchars($producto->marca) ?></div>
        <h1><?= htmlspecialchars($producto->nombre) ?></h1>
        <div class="descripcion"><?= htmlspecialchars($producto->descripcion) ?></div>
        <div class="precio">$<?= number_format($producto->precio, 2) ?></div>

        <!-- Formulario de compra -->
        <div class="form-producto">
            <?php if ($tallas && count($tallas) > 0): ?>
                <label for="talla">Seleccionar talla:</label>
                <select id="talla" name="talla" required>
                    <option value="">Elige una talla</option>
                    <?php foreach ($tallas as $t): ?>
                        <option value="<?= $t->id_talla ?>" data-stock="<?= $t->stock ?>">
                            <?= htmlspecialchars($t->talla) ?> (<?= $t->stock ?> disponibles)
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <div class="cantidad-container">
                    <label for="cantidad">Cantidad:</label>
                    <input type="number" id="cantidad" name="cantidad" value="1" min="1" max="1" class="cantidad-input">
                </div>
                
                <button type="button" class="btn-carrito" onclick="agregarAlCarrito()" disabled>
                    🛒 Agregar al carrito
                </button>
            <?php else: ?>
                <div class="no-tallas">
                    ❌ Este producto no tiene tallas disponibles actualmente
                </div>
                <button type="button" disabled>
                    Producto no disponible
                </button>
            <?php endif; ?>
        </div>
        
        <!-- Botón para añadir a wishlist -->
        <button type="button" style="background: #28a745; margin-top: 10px;" onclick="agregarAWishlist()">
            ❤️ Añadir a lista de deseos
        </button>
    </div>
</div>

<!-- Productos relacionados -->
<?php if ($relacionados && count($relacionados) > 0): ?>
<div class="relacionados">
    <h2>Productos relacionados</h2>
    <div class="relacionados-contenedor">
        <?php foreach ($relacionados as $p): ?>
            <div class="relacionado-item">
                <a href="Producto.php?id=<?= $p->id_producto ?>">
                    <img src="<?= htmlspecialchars($p->imagen_url) ?>" 
                         alt="<?= htmlspecialchars($p->nombre) ?>"
                         onerror="this.src='img/placeholder.jpg'">
                    <div class="contenido">
                        <h4><?= htmlspecialchars($p->nombre) ?></h4>
                        <div class="marca-rel"><?= htmlspecialchars($p->marca) ?></div>
                        <div class="precio-rel">$<?= number_format($p->precio, 2) ?></div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<script>
const tallaSelect = document.getElementById('talla');
const cantidadInput = document.getElementById('cantidad');
const btnCarrito = document.querySelector('.btn-carrito');

// Actualizar cantidad máxima cuando se selecciona talla
if (tallaSelect) {
    tallaSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const stock = parseInt(selectedOption.dataset.stock) || 0;
        
        if (this.value && stock > 0) {
            cantidadInput.max = stock;
            cantidadInput.value = Math.min(cantidadInput.value, stock);
            btnCarrito.disabled = false;
        } else {
            cantidadInput.max = 1;
            cantidadInput.value = 1;
            btnCarrito.disabled = true;
        }
    });
}

// Función para agregar al carrito
function agregarAlCarrito() {
    const talla = tallaSelect?.value;
    const cantidad = cantidadInput?.value || 1;
    
    if (!talla) {
        alert('Por favor selecciona una talla');
        return;
    }
    
    // Aquí la lógica para agregar al carrito
    
    const datosCarrito = {
        producto_id: <?= $id ?>,
        talla_id: talla,
        cantidad: cantidad
    };
    
    console.log('Agregando al carrito:', datosCarrito);
    alert(`Producto agregado al carrito!\nTalla: ${tallaSelect.options[tallaSelect.selectedIndex].text}\nCantidad: ${cantidad}`);
}

// Función para agregar a wishlist
function agregarAWishlist() {
    const datosWishlist = {
        producto_id: <?= $id ?>
    };
    
    console.log('Agregando a wishlist:', datosWishlist);
    alert('¡Producto agregado a tu lista de deseos!');
}
</script>

</body>
</html>