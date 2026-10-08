<?php
require_once 'config.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$q = $_GET['q'] ?? '';
$cat = $_GET['cat'] ?? '';

$sql = "SELECT id, name, item_no, series, categoria_id, price, img, media_json FROM productos WHERE 1=1";
$params = [];

if ($q !== '') {
    $sql .= " AND (name LIKE ? OR item_no LIKE ? OR series LIKE ?)";
    $params[] = "%$q%";
    $params[] = "%$q%";
    $params[] = "%$q%";
}
if ($cat !== '') {
    $sql .= " AND categoria_id = ?";
    $params[] = $cat;
}

$sql .= " ORDER BY id DESC LIMIT 500";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$cats = $pdo->query("SELECT id, name FROM categorias ORDER BY pos ASC")->fetchAll(PDO::FETCH_ASSOC);

include 'header.php';
?>
<div class="panel-card">
    <div style="display:flex; justify-content:space-between; margin-bottom:20px; align-items:flex-end; flex-wrap:wrap; gap:16px;">
        <h2 style="margin:0;font-family:Oswald;text-transform:uppercase;color:#1d3557;">Inventario de Máquinas</h2>
        
        <form method="GET" style="display:flex; gap:12px; align-items:center;">
            <select name="cat" class="form-group" style="margin:0; padding:8px 12px; background:#f7f7f7; border:1px solid #e5e5e5; border-radius:6px; outline:none; font-size:13px;" onchange="this.form.submit()">
                <option value="">Todas las categorías</option>
                <?php foreach ($cats as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $cat == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
            
            <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar equipo o SKU..." style="padding:8px 12px; background:#f7f7f7; border:1px solid #e5e5e5; border-radius:6px; outline:none; font-size:13px; width:200px;">
            <button type="submit" class="btn-primary" style="padding:8px 16px;">Buscar</button>
            <?php if($q !== '' || $cat !== ''): ?>
                <a href="productos.php" class="btn-nav">Limpiar</a>
            <?php endif; ?>
        </form>
        
        <a href="producto_form.php" class="btn-primary">+ Agregar Equipo</a>
    </div>
    
    <div class="table-wrapper">
        <table class="products-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Equipo</th>
                    <th>SKU</th>
                    <th>Marca (Serie)</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($productos as $p): ?>
                    <tr>
                        <td><?= $p['id'] ?></td>
                        <td>
                            <?php 
                            $img = $p['img'] ?? '';
                            if (empty($img) && !empty($p['media_json'])) {
                                $media = json_decode($p['media_json'], true);
                                if (is_array($media) && count($media) > 0 && isset($media[0]['url'])) {
                                    $img = $media[0]['url'];
                                }
                            }
                            if ($img && strpos($img, 'http') !== 0 && strpos($img, 'v1/cotizaciones/') !== 0) {
                                $img = 'v1/cotizaciones/' . $img;
                            }
                            ?>
                            <div style="display:flex; align-items:center; gap:12px;">
                                <?php if ($img): ?>
                                    <img src="../<?= htmlspecialchars($img) ?>" style="width:40px;height:40px;object-fit:cover;border-radius:4px;background:#edf2f7;" onerror="this.style.display='none'" />
                                <?php else: ?>
                                    <div style="width:40px;height:40px;border-radius:4px;background:#edf2f7;"></div>
                                <?php endif; ?>
                                <strong><?= htmlspecialchars($p['name'] ?? '') ?></strong>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($p['item_no'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($p['series'] ?? '-') ?></td>
                        <td>
                            <a href="producto_form.php?id=<?= $p['id'] ?>" class="action-link">Editar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($productos) === 0): ?>
                    <tr><td colspan="5" style="text-align:center; padding:40px; color:#718096;">No se encontraron productos con esos filtros.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body></html>
