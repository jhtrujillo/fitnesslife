<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id = $_GET['id'] ?? null;
$producto = [
    'name' => '', 'item_no' => '', 'series' => '', 'price' => 0, 'img' => '', 'media_json' => '[]'
];

$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $item_no = $_POST['item_no'] ?? '';
    $series = $_POST['series'] ?? '';
    $price = $_POST['price'] ?? 0;
    $img = $_POST['img'] ?? '';
    
    // Si queremos actualizar el media_json para que coincida con img, podemos hacerlo
    $media_json = json_encode([['url' => $img, 'type' => 'image']]);

    if ($id) {
        $stmt = $pdo->prepare("UPDATE productos SET name = :name, item_no = :item_no, series = :series, price = :price, img = :img, media_json = :media_json WHERE id = :id");
        $stmt->execute([
            'name' => $name,
            'item_no' => $item_no,
            'series' => $series,
            'price' => $price,
            'img' => $img,
            'media_json' => $media_json,
            'id' => $id
        ]);
        $success = true;
    } else {
        $stmt = $pdo->prepare("INSERT INTO productos (name, item_no, series, price, img, media_json) VALUES (:name, :item_no, :series, :price, :img, :media_json)");
        $stmt->execute([
            'name' => $name,
            'item_no' => $item_no,
            'series' => $series,
            'price' => $price,
            'img' => $img,
            'media_json' => $media_json
        ]);
        $id = $pdo->lastInsertId();
        $success = true;
    }
}

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM productos WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $producto = $stmt->fetch(PDO::FETCH_ASSOC) ?: $producto;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Equipo</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background: #f4f6f8; margin:0; padding:40px; }
        .card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); max-width: 600px; margin: 0 auto; }
        h1 { margin-top: 0; }
        .form-group { margin-bottom: 20px; }
        label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 14px; }
        input[type="text"], input[type="number"] { width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 6px; box-sizing: border-box; }
        .btn { padding: 10px 20px; background: #c92026; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; text-decoration: none; }
        .alert { background: #c6f6d5; color: #22543d; padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; font-weight: 500; font-size: 14px; }
        .img-preview { margin-top: 10px; max-width: 150px; border-radius: 6px; border: 1px solid #e2e8f0; }
        @media (max-width: 768px) {
            body { padding: 15px; }
            .card { padding: 20px; }
        }
    </style>
</head>
<body>
    <div class="card">
        <h1><?= $id ? 'Editar Equipo' : 'Nuevo Equipo' ?></h1>
        
        <?php if ($success): ?>
            <div class="alert">✅ Los cambios se han guardado correctamente.</div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>Nombre del Equipo</label>
                <input type="text" name="name" value="<?= htmlspecialchars($producto['name'] ?? '') ?>" required>
            </div>
            
            <div style="display:flex; gap:20px; flex-wrap:wrap;">
                <div class="form-group" style="flex:1;">
                    <label>SKU (Código)</label>
                    <input type="text" name="item_no" value="<?= htmlspecialchars($producto['item_no'] ?? '') ?>">
                </div>
                <div class="form-group" style="flex:1;">
                    <label>Marca (Series)</label>
                    <input type="text" name="series" value="<?= htmlspecialchars($producto['series'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Precio</label>
                <input type="number" step="0.01" name="price" value="<?= htmlspecialchars($producto['price'] ?? 0) ?>">
            </div>

            <div class="form-group">
                <label>Ruta de la Imagen</label>
                <input type="text" name="img" value="<?= htmlspecialchars($producto['img'] ?? '') ?>" placeholder="ej: uploads/productos/imagen.jpg">
                <div style="font-size:12px; color:#718096; margin-top:6px;">Esta ruta es relativa a la carpeta <code>v1/cotizaciones/</code>. Si dejas la imagen en esa carpeta, pon aquí su ruta.</div>
                
                <?php 
                $imgSrc = $producto['img'] ?? '';
                if ($imgSrc && strpos($imgSrc, 'http') !== 0 && strpos($imgSrc, 'v1/cotizaciones/') !== 0) {
                    $imgSrc = '../v1/cotizaciones/' . $imgSrc;
                } else if (strpos($imgSrc, 'http') !== 0) {
                    $imgSrc = '../' . $imgSrc;
                }
                ?>
                <?php if ($producto['img']): ?>
                    <img src="<?= htmlspecialchars($imgSrc) ?>" class="img-preview" alt="Preview" onerror="this.style.display='none'">
                <?php endif; ?>
            </div>
            
            <div style="margin-top: 30px;">
                <button type="submit" class="btn">Guardar Cambios</button>
                <a href="productos.php" style="margin-left:15px; color:#718096; text-decoration:none; font-weight:600;">Volver al Inventario</a>
            </div>
        </form>
    </div>
</body>
</html>
