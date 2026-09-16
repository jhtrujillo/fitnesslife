<?php
/**
 * GET api/productos.php
 * Devuelve el catálogo de productos. Solo lectura, consulta fija.
 *
 * Parámetros opcionales:
 *   ?series=Cardio     filtra por serie
 *   ?q=eliptica        busca en nombre / item_no
 *   ?limit=100         máximo de filas (por defecto 500)
 */
require __DIR__ . '/config.php';
cors($ALLOWED_ORIGINS, 'GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    respond(405, false, 'Método no permitido.');
}

$series = isset($_GET['series']) ? trim($_GET['series']) : '';
$q      = isset($_GET['q']) ? trim($_GET['q']) : '';
$limit  = isset($_GET['limit']) ? (int) $_GET['limit'] : 500;
if ($limit < 1 || $limit > 1000) $limit = 500;

$sql    = 'SELECT * FROM productos WHERE 1=1';
$params = [];

if ($series !== '') {
    $sql .= ' AND series = :series';
    $params[':series'] = $series;
}
if ($q !== '') {
    $sql .= ' AND (name LIKE :q OR item_no LIKE :q)';
    $params[':q'] = '%' . $q . '%';
}

$sql .= ' ORDER BY series ASC, pos ASC, id ASC LIMIT ' . $limit;

try {
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
    respond(200, true, 'Catálogo obtenido.', $rows);
} catch (PDOException $e) {
    error_log('productos.php: ' . $e->getMessage());
    respond(500, false, 'No se pudo obtener el catálogo.');
}
