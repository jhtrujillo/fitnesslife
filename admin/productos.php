<?php
require_once 'config.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$stmt = $pdo->query("SELECT id, name, item_no, series, price, img, media_json FROM productos ORDER BY id DESC LIMIT 500");
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'header.php';
?>
<div class="panel-card">
    <div style="display:flex; justify-content:space-between; margin-bottom:20px; align-items:center;">
        <h2 style="margin:0;font-family:Oswald;text-transform:uppercase;color:#1d3557;">Inventario de Máquinas</h2>
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
            </tbody>
        </table>
    </div>
</div>
</body></html>
