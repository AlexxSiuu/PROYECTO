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
    header("Location: proyecto.php");
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
    header("Location: proyecto.php");
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
            <a href="/proyecto.php">JERSEYKING</a>
        </div>
        <div class="nav-back">
            <a href="javascript:history.back()">← Volver</a>
        </div>
    </nav>
</header>

<!-- Breadcrumb -->
<div class="breadcrumb">
    <a href="/proyecto.php">Inicio</a> > 
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
                <?php if ($t->stock > 0): ?>
                    <option value="<?= $t->id_talla ?>" data-stock="<?= $t->stock ?>">
                        <?= htmlspecialchars($t->talla) ?>
                    </option>
                <?php else: ?>
                    <option disabled>
                        <?= htmlspecialchars($t->talla) ?> - No disponible
                    </option>
                <?php endif; ?>
            <?php endforeach; ?>
        </select>
        
        <div class="cantidad-container">
            <label for="cantidad">Cantidad:</label>
            <input type="number" id="cantidad" name="cantidad" value="1" min="1" max="1" class="cantidad-input">
        </div>
        
        <button type="button" class="btn-carrito" onclick="agregarAlCarrito()" disabled>
            Selecciona una talla
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
                <a href="producto.php?id=<?= $p->id_producto ?>">
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
        
        // AQUÍ VA EL CÓDIGO QUE MENCIONÉ
        if (this.value && stock > 0) {
            cantidadInput.max = stock;
            cantidadInput.value = Math.min(cantidadInput.value, stock);
            btnCarrito.disabled = false;
            btnCarrito.innerHTML = '🛒 Agregar al carrito';
        } else {
            cantidadInput.max = 1;
            cantidadInput.value = 1;
            btnCarrito.disabled = true;
            btnCarrito.innerHTML = 'Sin stock disponible';
        }
    });
}



// Actualizar cantidad máxima cuando se selecciona talla
if (tallaSelect) {
    tallaSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const stock = parseInt(selectedOption.dataset.stock) || 0;
        
        if (this.value && stock > 0) {
            cantidadInput.max = stock;
            cantidadInput.value = Math.min(cantidadInput.value, stock);
            btnCarrito.disabled = false;
            btnCarrito.innerHTML = '🛒 Agregar al carrito';
        } else {
            cantidadInput.max = 1;
            cantidadInput.value = 1;
            btnCarrito.disabled = true;
            btnCarrito.innerHTML = 'Selecciona una talla';
        }
    });
}

// Validar cantidad input
if (cantidadInput) {
    cantidadInput.addEventListener('change', function() {
        const max = parseInt(this.max) || 1;
        const min = parseInt(this.min) || 1;
        let value = parseInt(this.value) || 1;
        
        if (value > max) value = max;
        if (value < min) value = min;
        
        this.value = value;
    });
}

// Función mejorada para agregar al carrito
function agregarAlCarrito() {
    const talla = tallaSelect?.value;
    const cantidad = parseInt(cantidadInput?.value) || 1;
    
    if (!talla) {
        mostrarNotificacion('Por favor selecciona una talla', 'error');
        return;
    }
    
    // Deshabilitar botón temporalmente
    btnCarrito.disabled = true;
    btnCarrito.innerHTML = '⏳ Agregando...';
    
    const formData = new FormData();
    formData.append('producto_id', <?= $id ?>);
    formData.append('talla_id', talla);
    formData.append('cantidad', cantidad);
    
    fetch('agregar_carrito.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('Respuesta del servidor:', data); // Para debug
        
        if (data.success) {
            mostrarNotificacion(`${data.mensaje || 'Producto agregado al carrito'}`, 'success');

            // Actualizar contador del carrito en el header si existe
            if (window.actualizarContadorCarrito && data.cart_count) {
                window.actualizarContadorCarrito(data.cart_count);
            }
            
            // Mostrar detalles del producto agregado SOLO si los datos existen
            if (data.producto) {
                mostrarProductoAgregado(data.producto);
            }
            
        } else if (data.login_required) {
            mostrarModalLogin();
        } else {
            mostrarNotificacion(data.message || data.mensaje || 'Error al agregar al carrito', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarNotificacion('Error de conexión', 'error');
    })
    .finally(() => {
        // Reactivar botón
        btnCarrito.disabled = false;
        btnCarrito.innerHTML = '🛒 Agregar al carrito';
    });
}

// Mostrar notificaciones
function mostrarNotificacion(mensaje, tipo = 'success') {
    // Verificar que el mensaje no sea undefined
    if (!mensaje || mensaje === 'undefined') {
        mensaje = tipo === 'success' ? 'Operación exitosa' : 'Ha ocurrido un error';
    }
    
    // Crear elemento de notificación
    const notificacion = document.createElement('div');
    notificacion.className = `notificacion notificacion-${tipo}`;
    notificacion.innerHTML = `
        <a href="carrito.php" style="text-decoration:none; color:inherit;><div class="notificacion-content">
            <span class="notificacion-icon">${tipo === 'success' ? '✅' : '❌'}</span>
            <span class="notificacion-texto">${mensaje}</span>
            <button class="notificacion-close" onclick="this.parentElement.parentElement.remove()">×</button>
        </div>
        </a>
    `;
    
    // Agregar estilos si no existen
    if (!document.getElementById('notificacion-styles')) {
        const styles = document.createElement('style');
        styles.id = 'notificacion-styles';
        styles.textContent = `
            .notificacion {
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 10000;
                min-width: 300px;
                max-width: 450px;
                background: white;
                border-radius: 8px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.15);
                animation: slideInRight 0.3s ease-out;
                font-family: Arial, sans-serif;
            }
            
            .notificacion-success {
                border-left: 4px solid #28a745;
            }
            
            .notificacion-error {
                border-left: 4px solid #dc3545;
            }
            
            .notificacion-content {
                display: flex;
                align-items: center;
                padding: 15px 20px;
                gap: 10px;
            }
            
            .notificacion-icon {
                font-size: 18px;
            }
            
            .notificacion-texto {
                flex: 1;
                font-size: 14px;
                font-weight: 500;
                color: #333;
            }
            
            .notificacion-close {
                background: none;
                border: none;
                font-size: 20px;
                cursor: pointer;
                color: #999;
                padding: 0;
                width: 24px;
                height: 24px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 4px;
            }
            
            .notificacion-close:hover {
                background-color: #f0f0f0;
                color: #333;
            }
            
            @keyframes slideInRight {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            
            @media (max-width: 768px) {
                .notificacion {
                    left: 20px;
                    right: 20px;
                    min-width: auto;
                }
            }
        `;
        document.head.appendChild(styles);
    }
    
    // Agregar al DOM
    document.body.appendChild(notificacion);
    
    // Auto-eliminar después de 5 segundos
    setTimeout(() => {
        if (notificacion.parentElement) {
            notificacion.style.animation = 'slideInRight 0.3s ease-in reverse';
            setTimeout(() => notificacion.remove(), 300);
        }
    }, 5000);
}

// Mostrar detalles del producto agregado (VERSIÓN CORREGIDA)
function mostrarProductoAgregado(producto) {
    // Verificar que el objeto producto y sus propiedades existan
    if (!producto) return;
    
    // Validar cada propiedad antes de usarla
    const nombre = producto.nombre || 'Producto';
    const talla = producto.talla || producto.talla_nombre || 'N/A';
    const cantidad = producto.cantidad || 1;
    const precio = producto.precio || '0.00';
    
    const detalles = `
        <div style="margin-top: 8px; padding: 8px; background: #f8f9fa; border-radius: 4px; font-size: 12px; color: #666;">
            <strong>${nombre}</strong><br>
            Talla: ${talla} • Cantidad: ${cantidad}<br>
            Precio: $${precio}
        </div>
    `;
    
    // Si existe una notificación, agregar los detalles
    setTimeout(() => {
        const ultimaNotificacion = document.querySelector('.notificacion:last-child .notificacion-texto');
        if (ultimaNotificacion) {
            ultimaNotificacion.innerHTML += detalles;
        }
    }, 100);
}


// Mostrar modal de login si es necesario
function mostrarModalLogin() {
    mostrarNotificacion('Debes iniciar sesión para agregar productos al carrito', 'error');
    
    // Si existe el modal de login, mostrarlo
    setTimeout(() => {
        const loginIcon = document.getElementById('login-icon');
        if (loginIcon) {
            loginIcon.click();
        } else {
            // Redirigir a página de login
            window.location.href = 'proyecto.php#login';
        }
    }, 1500);
}

// Función para agregar a wishlist (mejorada)
function agregarAWishlist() {
    const formData = new FormData();
    formData.append('producto_id', <?= $id ?>);
    
    fetch('agregar_wishlist.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarNotificacion('Producto agregado a tu lista de deseos', 'success');
        } else if (data.login_required) {
            mostrarModalLogin();
        } else {
            mostrarNotificacion(data.message || 'Error al agregar a wishlist', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarNotificacion('Error de conexión', 'error');
    });
}
</script>

</body>
</html>