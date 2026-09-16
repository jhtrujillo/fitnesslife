<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Fetch all products
$stmt = $pdo->query("SELECT id, name, item_no, series, price, img, media_json FROM productos ORDER BY id DESC LIMIT 500");
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario - Fitness Life</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background: #f4f6f8; margin: 0; color: #2d3748; }
        .sidebar { width: 250px; background: white; height: 100vh; position: fixed; border-right: 1px solid #e2e8f0; }
        .sidebar-header { padding: 24px; border-bottom: 1px solid #e2e8f0; text-align: center; }
        .sidebar-header img { max-width: 150px; }
        .nav-item { display: block; padding: 16px 24px; color: #4a5568; text-decoration: none; font-weight: 500; border-bottom: 1px solid #f7fafc; transition: background 0.2s; }
        .nav-item:hover, .nav-item.active { background: #f7fafc; color: #c92026; border-left: 4px solid #c92026; padding-left: 20px; }
        .main-content { margin-left: 250px; padding: 40px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .header h1 { margin: 0; font-size: 24px; }
        
        .btn { padding: 10px 20px; background: linear-gradient(135deg, #c92026, #b11b21); color: white; border: none; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn:hover { opacity: 0.9; }
        
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.02); border: 1px solid #e2e8f0; }
        th, td { padding: 16px; text-align: left; border-bottom: 1px solid #e2e8f0; font-size: 14px; }
        th { background: #f7fafc; font-weight: 600; color: #718096; text-transform: uppercase; font-size: 12px; letter-spacing: 0.05em; }
        tr:last-child td { border-bottom: none; }
        .item-img { width: 40px; height: 40px; object-fit: cover; border-radius: 4px; background: #edf2f7; vertical-align: middle; margin-right: 12px; }
        .action-link { color: #3182ce; text-decoration: none; font-weight: 600; margin-right: 12px; }
        .action-link:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-header">
        <img src="../assets/logo.png" alt="Fitness Life">
    </div>
    <a href="index.php" class="nav-item">Dashboard</a>
    <a href="productos.php" class="nav-item active">Inventario (Productos)</a>
    <a href="cotizaciones.php" class="nav-item">Buzón Cotizaciones</a>
</div>

<div class="main-content">
    <div class="header">
        <h1>Inventario de Máquinas</h1>
        <a href="producto_form.php" class="btn">+ Agregar Equipo</a>
    </div>
    
    <table>
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
                        <?php if ($img): ?>
                            <img src="../<?= htmlspecialchars($img) ?>" class="item-img" onerror="this.onerror=null; this.removeAttribute('src'); this.style.display='none'; this.nextElementSibling.style.display='inline-block';" />
                            <div class="item-img" style="display:none; text-align:center; line-height:40px; color:#a0aec0; font-size:10px;">IMG</div>
                        <?php else: ?>
                            <div class="item-img" style="display:inline-block"></div>
                        <?php endif; ?>
                        <strong><?= htmlspecialchars($p['name'] ?? '') ?></strong>
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

</body>
</html>
