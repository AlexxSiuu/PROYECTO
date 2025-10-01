<?php
session_start();
include 'conexion.php';

$termino_busqueda = trim($_GET['q'] ?? '');
$productos = [];

if (!empty($termino_busqueda)) {
    $termino = "%{$termino_busqueda}%";
    
    $sql = "SELECT DISTINCT p.*, 
            g.nombre as genero_nombre,
            u.nombre as uso_nombre,
            d.nombre as deporte_nombre,
            COALESCE(SUM(pt.stock), 0) as stock_total
            FROM productos p
            LEFT JOIN generos g ON p.id_genero = g.id_genero
            LEFT JOIN usos u ON p.id_uso = u.id_uso
            LEFT JOIN deportes d ON p.id_deporte = d.id_deporte
            LEFT JOIN producto_tallas pt ON p.id_producto = pt.id_producto
            WHERE p.nombre LIKE ? 
               OR p.marca LIKE ? 
               OR p.descripcion LIKE ?
               OR d.nombre LIKE ?
            GROUP BY p.id_producto
            HAVING stock_total > 0
            ORDER BY p.nombre";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ssss", $termino, $termino, $termino, $termino);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $productos[] = $row;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buscar - JERSEYKING</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        /* Header */
        .header {
            background: #111;
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .navbar {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo-img {
            height: 50px;
            width: auto;
        }

        /* Search Container */
        .search-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .search-header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .search-header h1 {
            font-size: 2em;
            color: #111;
            margin-bottom: 15px;
        }

        .search-info {
            color: #666;
            font-size: 16px;
        }

        .search-term {
            color: #667eea;
            font-weight: bold;
        }

        .back-link {
            display: inline-block;
            margin-top: 15px;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }

        .back-link:hover {
            color: #764ba2;
            transform: translateX(-5px);
        }

        /* Results Grid */
        .results-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
        }

        .product-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
        }

        .product-image {
            width: 100%;
            height: 280px;
            object-fit: cover;
            background: #f8f9fa;
        }

        .product-info {
            padding: 20px;
        }

        .product-brand {
            color: #667eea;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }

        .product-name {
            font-size: 18px;
            font-weight: 700;
            color: #111;
            margin-bottom: 10px;
            line-height: 1.3;
        }

        .product-price {
            font-size: 24px;
            font-weight: 800;
            color: #111;
            margin-bottom: 10px;
        }

        /* No Results */
        .no-results {
            background: white;
            padding: 60px 20px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .no-results h2 {
            color: #666;
            margin-bottom: 15px;
            font-size: 1.8em;
        }

        .no-results p {
            color: #999;
            font-size: 16px;
            line-height: 1.6;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .results-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 15px;
            }

            .product-image {
                height: 220px;
            }

            .search-header h1 {
                font-size: 1.5em;
            }

            .search-header {
                padding: 20px;
            }
        }
    </style>
</head>
<body>

<header class="header">
    <nav class="navbar">
        <div class="logo">
            <a href="proyecto.php">
                <img src="img/logo_icono.ico.jpg" class="logo-img" alt="JERSEYKING">
            </a>
        </div>
    </nav>
</header>

<div class="search-container">
    <div class="search-header">
        <h1>Resultados de Búsqueda</h1>
        <p class="search-info">
            Buscaste: <span class="search-term">"<?php echo htmlspecialchars($termino_busqueda); ?>"</span>
            - Se encontraron <strong><?php echo count($productos); ?></strong> productos
        </p>
        <a href="proyecto.php" class="back-link">← Volver a la tienda</a>
    </div>

    <?php if (empty($productos)): ?>
        <div class="no-results">
            <h2>No se encontraron productos</h2>
            <p>Intenta con otros términos de búsqueda como marcas (Nike, Adidas, Puma) o tipos de productos (camiseta, zapatillas, balón)</p>
        </div>
    <?php else: ?>
        <div class="results-grid">
            <?php foreach ($productos as $producto): ?>
                <a href="producto.php?id=<?php echo $producto['id_producto']; ?>" class="product-card">
                    <?php if ($producto['imagen_url']): ?>
                        <img src="<?php echo htmlspecialchars($producto['imagen_url']); ?>" 
                             alt="<?php echo htmlspecialchars($producto['nombre']); ?>" 
                             class="product-image">
                    <?php else: ?>
                        <div class="product-image" style="display: flex; align-items: center; justify-content: center; color: #999;">
                            Sin imagen
                        </div>
                    <?php endif; ?>
                    
                    <div class="product-info">
                        <div class="product-brand"><?php echo htmlspecialchars($producto['marca']); ?></div>
                        <div class="product-name"><?php echo htmlspecialchars($producto['nombre']); ?></div>
                        <div class="product-price">$<?php echo number_format($producto['precio'], 2); ?></div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

</body>
</html>