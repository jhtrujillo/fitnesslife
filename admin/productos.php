<?php
require_once 'config.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM productos WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    header("Location: productos.php"); exit;
}

$sql = "SELECT id, name, item_no, series, categoria_id, price, img, media_json FROM productos ORDER BY id DESC ";
$stmt = $pdo->query($sql);
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
$cats = $pdo->query("SELECT id, name FROM categorias ORDER BY pos ASC")->fetchAll(PDO::FETCH_ASSOC);
$marcas = $pdo->query("SELECT name FROM marcas ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$catMap = [];
foreach ($cats as $c) { $catMap[$c['id']] = $c['name']; }
include 'header.php';
?>
<div class="panel-card">
    <div style="margin-bottom:20px;">
        <h2 style="margin:0 0 16px 0; font-family:'Oswald', sans-serif; text-transform:uppercase; color:#1d3557; font-size:24px; border-bottom:1px solid #eee; padding-bottom:12px;">📦 Catálogo de Equipos</h2>
        
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
            <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
            
                
            <div id="topFilters" style="display:none; gap:12px; align-items:center; flex-wrap:wrap;">
                <select id="catFilterTop" class="form-group" style="margin:0; padding:8px 12px; background:#f7f7f7; border:1px solid #e5e5e5; border-radius:6px; outline:none; font-size:13px;">
                    <option value="">Todas las categorías</option>
                    <option value="NONE">- Sin categoría -</option>
                    <?php foreach ($cats as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="brandFilterTop" class="form-group" style="margin:0; padding:8px 12px; background:#f7f7f7; border:1px solid #e5e5e5; border-radius:6px; outline:none; font-size:13px;">
                    <option value="">Todas las marcas</option>
                    <option value="NONE">- Sin marca -</option>
                    <?php foreach ($marcas as $m): ?>
                        <option value="<?= htmlspecialchars($m['name']) ?>"><?= htmlspecialchars($m['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

                <input type="text" id="searchInput" placeholder="Buscar equipo o SKU..." style="padding:8px 12px; background:#f7f7f7; border:1px solid #e5e5e5; border-radius:6px; outline:none; font-size:13px; width:250px;">
            </div>
            
            <div style="display:flex; gap:12px; align-items:center;">
                <button id="viewToggleBtn" style="background: #eee; color: #333; border: 1px solid #ccc; padding: 0 15px; border-radius: 6px; cursor: pointer; height: 41px; display: flex; align-items: center; font-weight: 600; font-family: 'Inter', sans-serif;">🔲 Bloques</button>
                <a href="producto_form.php" style="background: #457b9d; color: white; border: none; padding: 10px 20px; border-radius: 6px; font-family: 'Oswald', sans-serif; font-weight: 600; text-transform: uppercase; cursor: pointer; white-space: nowrap; height: 41px; box-sizing: border-box; text-decoration: none; display: flex; align-items: center;">+ Añadir</a>
            </div>
        </div>
    </div>
    
    <div class="table-wrapper" id="tableView">
        <table class="products-table" id="productsTable">
            <thead>
                <tr>
                    <th style="width: 60px">IMG</th>
                    <th style="width: 100px">CÓDIGO</th>
                    <th style="width: 180px">
                        <div style="display:flex; flex-direction:column; gap:6px;">
                            <span>CATEGORÍA</span>
                            <select id="catFilter" style="padding:4px 8px; background:#f7f7f7; border:1px solid #ccc; border-radius:4px; outline:none; font-size:11px; font-weight:normal; width:100%;">
                                <option value="">Todas (Filtro)</option>
                                <option value="NONE">- Sin marca -</option>
                                <option value="NONE">- Sin categoría -</option>
                                <?php foreach ($cats as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </th>
                    <th style="width: 160px">
                        <div style="display:flex; flex-direction:column; gap:6px;">
                            <span>MARCA</span>
                            <select id="brandFilter" style="padding:4px 8px; background:#f7f7f7; border:1px solid #ccc; border-radius:4px; outline:none; font-size:11px; font-weight:normal; width:100%;">
                                <option value="">Todas (Filtro)</option>
                                <?php foreach ($marcas as $m): ?>
                                    <option value="<?= htmlspecialchars($m['name']) ?>"><?= htmlspecialchars($m['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </th>
                    <th>NOMBRE DEL EQUIPO</th>
                    <th style="width: 120px">PRECIO REF.</th>
                    <th style="width: 120px; text-align: right">ACCIONES</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($productos as $p): ?>
                    <tr class="product-row" data-cat="<?= htmlspecialchars($p['categoria_id'] ?? '') ?>" data-brand="<?= htmlspecialchars($p['series'] ?? '') ?>">
                        <td class="img-cell" data-label="IMG">
                            <?php 
                            $img = $p['img'] ?? '';
                            if (empty($img) && !empty($p['media_json'])) {
                                $media = json_decode($p['media_json'], true);
                                if (is_array($media) && count($media) > 0 && isset($media[0]['url'])) $img = $media[0]['url'];
                            }
                            if ($img && strpos($img, 'http') !== 0 && strpos($img, 'cotizaciones/') !== 0 && strpos($img, 'v1/cotizaciones/') !== 0) $img = 'cotizaciones/' . $img;
                            ?>
                            <div style="width:44px; height:34px; border:1px solid #eee; background:white; border-radius:4px; display:flex; align-items:center; justify-content:center; overflow:hidden;">
                                <?php if ($img): ?>
                                    <img src="../<?= htmlspecialchars($img) ?>" style="max-width:100%; max-height:100%; object-fit:contain;" onerror="this.style.display='none'" />
                                <?php else: ?>
                                    <span style="font-size:10px; color:#ccc">No img</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="searchable" data-label="Código" style="font-weight: 600; color: #457b9d;"><?= htmlspecialchars($p['item_no'] ?? '-') ?></td>
                        <td class="searchable" data-label="Categoría"><span style="background:#e0ebf3; color:#1d3557; padding:2px 6px; border-radius:4px; font-size:11px; font-weight:600;"><?= htmlspecialchars($catMap[$p['categoria_id']] ?? 'Sin Categoría') ?></span></td>
                        <td class="searchable" data-label="Marca"><?= htmlspecialchars($p['series'] ?: '-') ?></td>
                        <td class="searchable" data-label="Nombre" style="font-weight: 500;"><?= htmlspecialchars($p['name'] ?? '') ?></td>
                        <td data-label="Precio Ref.">$ <?= number_format((float)$p['price'], 0, ',', '.') ?></td>
                        <td class="actions-cell" data-label="Acciones" style="text-align: right; white-space: nowrap;">
                            <a href="producto_form.php?id=<?= $p['id'] ?>" style="background: transparent; border: 1px solid #ccc; border-radius: 4px; padding: 6px 10px; cursor: pointer; margin-right: 4px; text-decoration: none; display: inline-block; color: #333;">✏️</a>
                            <a href="productos.php?delete=<?= $p['id'] ?>" style="background: transparent; border: 1px solid #ffcccc; color: #e63946; border-radius: 4px; padding: 6px 10px; cursor: pointer; text-decoration: none; display: inline-block;" onclick="return confirm('¿Estás seguro de eliminar este producto? Esta acción no se puede deshacer.');">🗑️</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr id="noResultsRow" style="display:none;"><td colspan="6" style="text-align:center; padding:40px; color:#718096;">No se encontraron productos.</td></tr>
            </tbody>
        </table>

    </div>

    <div id="gridView" style="display:none; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 24px; margin-top:20px;">
        <?php foreach ($productos as $p): ?>
            <?php 
            $img = $p['img'] ?? '';
            if (empty($img) && !empty($p['media_json'])) {
                $media = json_decode($p['media_json'], true);
                if (is_array($media) && count($media) > 0 && isset($media[0]['url'])) $img = $media[0]['url'];
            }
            if ($img && strpos($img, 'http') !== 0 && strpos($img, 'cotizaciones/') !== 0 && strpos($img, 'v1/cotizaciones/') !== 0) $img = 'cotizaciones/' . $img;
            ?>
            <div class="product-card" data-cat="<?= htmlspecialchars($p['categoria_id'] ?? '') ?>" data-brand="<?= htmlspecialchars($p['series'] ?? '') ?>" style="background: white; border: 1px solid #eee; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05); display: flex; flex-direction: column;">
                <div style="height: 220px; display: flex; align-items: center; justify-content: center; background: #fdfdfd; padding: 16px;">
                    <?php if ($img): ?>
                        <img src="../<?= htmlspecialchars($img) ?>" style="max-width: 100%; max-height: 100%; object-fit: contain;" onerror="this.style.display='none'">
                    <?php else: ?>
                        <span style="color: #ccc; font-size: 14px;">Sin imagen</span>
                    <?php endif; ?>
                </div>
                <div style="padding: 16px; display: flex; flex-direction: column; flex-grow: 1; border-top: 1px solid #f0f0f0;">
                    <div class="searchable-card" style="font-size: 12px; color: #888; margin-bottom: 6px; font-weight: 600; letter-spacing: 0.05em;">
                        <?= htmlspecialchars($catMap[$p['categoria_id']] ?? 'Sin Categoría') ?> • <?= htmlspecialchars($p['series'] ?: 'Sin Marca') ?> • <?= htmlspecialchars($p['item_no'] ?? '-') ?>
                    </div>
                    <div class="searchable-card" style="font-weight: 600; font-size: 16px; color: #1d3557; margin-bottom: 12px; line-height: 1.3;">
                        <?= htmlspecialchars($p['name'] ?? '') ?>
                    </div>
                    <div style="font-weight: 600; color: #e63946; font-size: 18px; margin-top: auto;">
                        $ <?= number_format((float)$p['price'], 0, ',', '.') ?>
                    </div>
                    <div style="display: flex; gap: 10px; margin-top: 20px;">
                        <a href="producto_form.php?id=<?= $p['id'] ?>" style="flex-grow: 1; background: #f7f7f7; border: 1px solid #ddd; padding: 8px; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 600; color: #555; text-align:center; text-decoration:none;">✏️ Editar</a>
                        <a href="productos.php?delete=<?= $p['id'] ?>" onclick="return confirm('¿Estás seguro de eliminar este producto? Esta acción no se puede deshacer.');" style="background: #fff0f0; border: 1px solid #ffcccc; color: #e63946; padding: 8px 12px; border-radius: 6px; cursor: pointer; font-size: 14px; text-decoration:none;">🗑️</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <div id="noResultsGrid" style="display:none; grid-column: 1 / -1; text-align: center; padding: 40px; color: #888;">No se encontraron productos.</div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const catFilter = document.getElementById('catFilter');
    const rows = document.querySelectorAll('.product-row');
    const cards = document.querySelectorAll('.product-card');
    const noResultsRow = document.getElementById('noResultsRow');
    const noResultsGrid = document.getElementById('noResultsGrid');
    const tableView = document.getElementById('tableView');
    const gridView = document.getElementById('gridView');
    const viewToggleBtn = document.getElementById('viewToggleBtn');
    const topFilters = document.getElementById('topFilters');
    
    const catFilterTop = document.getElementById('catFilterTop');
    const brandFilterTop = document.getElementById('brandFilterTop');
    const catFilterTable = document.getElementById('catFilter');
    const brandFilterTable = document.getElementById('brandFilter');

    let isGridView = false;

    function syncFilters(e) {
        if (e.target.id === 'catFilter') catFilterTop.value = e.target.value;
        if (e.target.id === 'catFilterTop') catFilterTable.value = e.target.value;
        if (e.target.id === 'brandFilter') brandFilterTop.value = e.target.value;
        if (e.target.id === 'brandFilterTop') brandFilterTable.value = e.target.value;
        filterTable();
    }

    catFilterTable.addEventListener('change', syncFilters);
    catFilterTop.addEventListener('change', syncFilters);
    brandFilterTable.addEventListener('change', syncFilters);
    brandFilterTop.addEventListener('change', syncFilters);

    viewToggleBtn.addEventListener('click', function() {
        isGridView = !isGridView;
        if (isGridView) {
            tableView.style.display = 'none';
            gridView.style.display = 'grid';
            viewToggleBtn.textContent = '📋 Tabla';
            topFilters.style.display = 'flex';
        } else {
            tableView.style.display = 'block';
            gridView.style.display = 'none';
            viewToggleBtn.textContent = '🔲 Bloques';
            topFilters.style.display = 'none';
        }
    });

    function filterTable() {
        const query = searchInput.value.toLowerCase().trim();
        const cat = catFilterTable.value; // both are synced
        const brand = brandFilterTable.value;
        let visibleCount = 0;

        // Filter Table Rows
        rows.forEach(row => {
            let show = true;
            if (cat === 'NONE') { if (row.getAttribute('data-cat') !== '') show = false; } else if (cat !== '' && row.getAttribute('data-cat') !== cat) show = false;
            if (brand === 'NONE') { if (row.getAttribute('data-brand') !== '') show = false; } else if (brand !== '' && row.getAttribute('data-brand') !== brand) show = false;

            if (show && query !== '') {
                const searchData = Array.from(row.querySelectorAll('.searchable'))
                                      .map(td => td.textContent.toLowerCase())
                                      .join(' ');
                if (!searchData.includes(query)) show = false;
            }

            if (show) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        noResultsRow.style.display = (visibleCount === 0) ? '' : 'none';

        // Filter Grid Cards
        let gridVisibleCount = 0;
        cards.forEach(card => {
            let show = true;
            if (cat === 'NONE') { if (card.getAttribute('data-cat') !== '') show = false; } else if (cat !== '' && card.getAttribute('data-cat') !== cat) show = false;
            if (brand === 'NONE') { if (card.getAttribute('data-brand') !== '') show = false; } else if (brand !== '' && card.getAttribute('data-brand') !== brand) show = false;

            if (show && query !== '') {
                const searchData = Array.from(card.querySelectorAll('.searchable-card'))
                                      .map(div => div.textContent.toLowerCase())
                                      .join(' ');
                if (!searchData.includes(query)) show = false;
            }

            if (show) {
                card.style.display = 'flex';
                gridVisibleCount++;
            } else {
                card.style.display = 'none';
            }
        });
        noResultsGrid.style.display = (gridVisibleCount === 0) ? 'block' : 'none';
    }

    searchInput.addEventListener('input', filterTable);
    
});
</script>
</body></html>
