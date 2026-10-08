<?php
require_once 'config.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
$id = $_GET['id'] ?? null;
$producto = ['name' => '', 'item_no' => '', 'series' => '', 'categoria_id' => '', 'price' => 0, 'img' => '', 'media_json' => '[]'];
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $item_no = $_POST['item_no'] ?? '';
    $series = $_POST['series'] ?? '';
    $categoria_id = $_POST['categoria_id'] ?? null;
    if ($categoria_id === '') $categoria_id = null;
    $price = $_POST['price'] ?? 0;
    $img = $_POST['img'] ?? '';
    $media_json = json_encode([['url' => $img, 'type' => 'image']]);
    if ($id) {
        $stmt = $pdo->prepare("UPDATE productos SET name=:name, item_no=:item_no, series=:series, categoria_id=:cat, price=:price, img=:img, media_json=:media_json WHERE id=:id");
        $stmt->execute(['name'=>$name, 'item_no'=>$item_no, 'series'=>$series, 'cat'=>$categoria_id, 'price'=>$price, 'img'=>$img, 'media_json'=>$media_json, 'id'=>$id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO productos (name, item_no, series, categoria_id, price, img, media_json) VALUES (:name, :item_no, :series, :cat, :price, :img, :media_json)");
        $stmt->execute(['name'=>$name, 'item_no'=>$item_no, 'series'=>$series, 'cat'=>$categoria_id, 'price'=>$price, 'img'=>$img, 'media_json'=>$media_json]);
        $id = $pdo->lastInsertId();
    }
    $success = true;
}
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM productos WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $producto = $stmt->fetch(PDO::FETCH_ASSOC) ?: $producto;
}
$stmt = $pdo->query("SELECT * FROM categorias ORDER BY pos ASC");
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt = $pdo->query("SELECT * FROM marcas ORDER BY name ASC");
$marcas = $stmt->fetchAll(PDO::FETCH_ASSOC);
include 'header.php';
?>
<div class="panel-card" style="max-width:600px; margin: 0 auto;">
    <h2 style="margin-top:0;font-family:Oswald;text-transform:uppercase;color:#1d3557;border-bottom:1px solid #eee;padding-bottom:12px"><?= $id ? 'Editar Equipo' : 'Nuevo Equipo' ?></h2>
    <?php if ($success): ?><div style="background:#e6fffa;color:#234e52;padding:12px;border-radius:6px;margin-bottom:20px;border:1px solid #b2f5ea;">✅ Los cambios se han guardado correctamente.</div><?php endif; ?>
    <form method="POST" action="">
        <div class="form-group">
            <label>Nombre del Equipo</label>
            <input type="text" name="name" value="<?= htmlspecialchars($producto['name'] ?? '') ?>" required>
        </div>
        <div style="display:flex; gap:16px; flex-wrap:wrap;">
            <div class="form-group" style="flex:1;">
                <label>SKU (Código)</label>
                <input type="text" name="item_no" value="<?= htmlspecialchars($producto['item_no'] ?? '') ?>">
            </div>
            <div class="form-group" style="flex:1;">
                <label>Precio</label>
                <input type="number" step="0.01" name="price" value="<?= htmlspecialchars($producto['price'] ?? 0) ?>">
            </div>
        </div>
        <div style="display:flex; gap:16px; flex-wrap:wrap;">
            <div class="form-group" style="flex:1;">
                <label>Marca (Series)</label>
                <select name="series">
                    <option value="">-- Sin Marca --</option>
                    <?php foreach ($marcas as $m): ?>
                        <option value="<?= htmlspecialchars($m['name']) ?>" <?= ($producto['series']===$m['name']) ? 'selected' : '' ?>><?= htmlspecialchars($m['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <div style="font-size:11px;color:#718096;margin-top:4px;">* Las marcas se crean en el panel de Marcas</div>
            </div>
            <div class="form-group" style="flex:1;">
                <label>Categoría Web</label>
                <select name="categoria_id">
                    <option value="">-- Sin Categoría --</option>
                    <?php foreach ($categorias as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($producto['categoria_id']==$c['id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <div style="font-size:11px;color:#718096;margin-top:4px;">* Para el catálogo de la página web</div>
            </div>
        </div>
        <div class="form-group">
            <label>Ruta de la Imagen</label>
            <input type="text" name="img" value="<?= htmlspecialchars($producto['img'] ?? '') ?>" placeholder="ej: uploads/productos/imagen.jpg">
            <div style="font-size:12px; color:#718096; margin-top:6px;">Ruta relativa a la carpeta <code>v1/cotizaciones/</code>.</div>
            <?php 
            $imgSrc = $producto['img'] ?? '';
            if ($imgSrc && strpos($imgSrc, 'http') !== 0 && strpos($imgSrc, 'v1/cotizaciones/') !== 0) $imgSrc = '../v1/cotizaciones/' . $imgSrc;
            else if (strpos($imgSrc, 'http') !== 0) $imgSrc = '../' . $imgSrc;
            ?>
            <?php if ($producto['img']): ?><img src="<?= htmlspecialchars($imgSrc) ?>" style="margin-top:10px;max-width:150px;border-radius:6px;border:1px solid #e2e8f0;" onerror="this.style.display='none'"><?php endif; ?>
        </div>
        <div style="margin-top: 24px; display:flex; gap:12px;">
            <button type="submit" class="btn-primary">Guardar Cambios</button>
            <a href="productos.php" class="btn-nav">Volver al Inventario</a>
        </div>
    </form>
</div>
</body></html>
