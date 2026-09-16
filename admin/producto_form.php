<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id = $_GET['id'] ?? null;
$producto = [
    'name' => '', 'item_no' => '', 'series' => '', 'price' => 0, 'status' => 'activo'
];

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
    </style>
</head>
<body>
    <div class="card">
        <h1><?= $id ? 'Editar Equipo' : 'Nuevo Equipo' ?></h1>
        <p style="color:#718096; font-size:14px; margin-bottom:30px;">(Versión preliminar del editor)</p>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Nombre del Equipo</label>
                <input type="text" name="name" value="<?= htmlspecialchars($producto['name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>SKU (Código)</label>
                <input type="text" name="item_no" value="<?= htmlspecialchars($producto['item_no'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Marca (Series)</label>
                <input type="text" name="series" value="<?= htmlspecialchars($producto['series'] ?? '') ?>">
            </div>
            
            <button type="button" class="btn" onclick="alert('Funcionalidad de guardado en construcción.')">Guardar Cambios</button>
            <a href="productos.php" style="margin-left:15px; color:#718096; text-decoration:none; font-weight:600;">Cancelar</a>
        </form>
    </div>
</body>
</html>
