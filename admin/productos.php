<?php
require_once 'config.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
$sql = "SELECT id, name, item_no, series, categoria_id, price, img, media_json FROM productos ORDER BY id DESC LIMIT 1000";
$stmt = $pdo->query($sql);
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
$cats = $pdo->query("SELECT id, name FROM categorias ORDER BY pos ASC")->fetchAll(PDO::FETCH_ASSOC);
include 'header.php';
?>
<div class="panel-card">
    <div style="display:flex; justify-content:space-between; margin-bottom:20px; align-items:flex-end; flex-wrap:wrap; gap:16px;">
        <h2 style="margin:0;font-family:Oswald;text-transform:uppercase;color:#1d3557;">Inventario de Máquinas</h2>
        
        <div style="display:flex; gap:12px; align-items:center;">
            <select id="catFilter" class="form-group" style="margin:0; padding:8px 12px; background:#f7f7f7; border:1px solid #e5e5e5; border-radius:6px; outline:none; font-size:13px;">
                <option value="">Todas las categorías</option>
                <?php foreach ($cats as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
            
            <input type="text" id="searchInput" placeholder="Buscar equipo o SKU..." style="padding:8px 12px; background:#f7f7f7; border:1px solid #e5e5e5; border-radius:6px; outline:none; font-size:13px; width:250px;">
        </div>
        
        <a href="producto_form.php" class="btn-primary">+ Agregar Equipo</a>
    </div>
    
    <div class="table-wrapper">
        <table class="products-table" id="productsTable">
            <thead>
                <tr>
                    <th style="width: 60px">IMG</th>
                    <th style="width: 100px">CÓDIGO</th>
                    <th style="width: 100px">SERIE</th>
                    <th>NOMBRE DEL EQUIPO</th>
                    <th style="width: 120px">PRECIO REF.</th>
                    <th style="width: 100px; text-align: right">ACCIONES</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($productos as $p): ?>
                    <tr class="product-row" data-cat="<?= htmlspecialchars($p['categoria_id'] ?? '') ?>">
                        <td class="img-cell" data-label="IMG">
                            <?php 
                            $img = $p['img'] ?? '';
                            if (empty($img) && !empty($p['media_json'])) {
                                $media = json_decode($p['media_json'], true);
                                if (is_array($media) && count($media) > 0 && isset($media[0]['url'])) $img = $media[0]['url'];
                            }
                            if ($img && strpos($img, 'http') !== 0 && strpos($img, 'v1/cotizaciones/') !== 0) $img = 'v1/cotizaciones/' . $img;
                            ?>
                            <div style="width:44px; height:34px; border:1px solid #eee; background:white; border-radius:4px; display:flex; align-items:center; justify-content:center; overflow:hidden;">
                                <?php if ($img): ?>
                                    <img src="../<?= htmlspecialchars($img) ?>" style="max-width:100%; max-height:100%; object-fit:contain;" onerror="this.style.display='none'" />
                                <?php else: ?>
                                    <span style="font-size:10px; color:#ccc">No img</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="searchable" data-label="Código"><?= htmlspecialchars($p['item_no'] ?? '-') ?></td>
                        <td class="searchable" data-label="Serie"><?= htmlspecialchars($p['series'] ?? '-') ?></td>
                        <td class="searchable" data-label="Nombre"><strong><?= htmlspecialchars($p['name'] ?? '') ?></strong></td>
                        <td data-label="Precio Ref.">$<?= number_format((float)$p['price'], 2) ?></td>
                        <td class="actions-cell" data-label="Acciones" style="text-align: right;">
                            <a href="producto_form.php?id=<?= $p['id'] ?>" class="action-link" style="margin:0;">Editar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr id="noResultsRow" style="display:none;"><td colspan="6" style="text-align:center; padding:40px; color:#718096;">No se encontraron productos.</td></tr>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const catFilter = document.getElementById('catFilter');
    const rows = document.querySelectorAll('.product-row');
    const noResultsRow = document.getElementById('noResultsRow');

    function filterTable() {
        const query = searchInput.value.toLowerCase().trim();
        const cat = catFilter.value;
        let visibleCount = 0;

        rows.forEach(row => {
            let show = true;
            
            if (cat !== '' && row.getAttribute('data-cat') !== cat) {
                show = false;
            }

            if (show && query !== '') {
                const searchData = Array.from(row.querySelectorAll('.searchable'))
                                      .map(td => td.textContent.toLowerCase())
                                      .join(' ');
                if (!searchData.includes(query)) {
                    show = false;
                }
            }

            if (show) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        noResultsRow.style.display = (visibleCount === 0) ? '' : 'none';
    }

    searchInput.addEventListener('input', filterTable);
    catFilter.addEventListener('change', filterTable);
});
</script>
</body></html>
