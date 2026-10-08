<?php
$host = "127.0.0.1";
$port = "8889";
$dbname = "cotizacioneslifefitness";
$username = "root";
$password = "root";
$pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);

$stmt = $pdo->query("SELECT * FROM categorias");
$cats = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $cats[$row['slug']] = $row['id'];
}

$stmt = $pdo->query("SELECT id, name, series, item_no FROM productos");
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($productos as $p) {
    $search = strtolower(($p['name'] ?? '') . ' ' . ($p['series'] ?? '') . ' ' . ($p['item_no'] ?? ''));
    $cat_id = null;
    
    if (str_contains($search, 'elliptical') || str_contains($search, 'elíptica') || str_contains($search, 'eliptica') || str_contains($search, 'cross trainer')) {
        $cat_id = $cats['elipticas'] ?? null;
    } elseif (str_contains($search, 'treadmill') || str_contains($search, 'trotadora') || str_contains($search, 'caminadora') || str_contains($search, 'run')) {
        $cat_id = $cats['trotadoras'] ?? null;
    } elseif (str_contains($search, 'bike') || str_contains($search, 'bicicleta') || str_contains($search, 'spin') || str_contains($search, 'cycle') || str_contains($search, 'stair') || str_contains($search, 'escalera') || str_contains($search, 'climb') || str_contains($search, 'step')) {
        $cat_id = $cats['bicicletas'] ?? null;
    } else {
        $cat_id = $cats['pesas'] ?? null;
    }
    
    if ($cat_id) {
        $pdo->prepare("UPDATE productos SET categoria_id = ? WHERE id = ?")->execute([$cat_id, $p['id']]);
    }
}
echo "Categorias asignadas.\n";
