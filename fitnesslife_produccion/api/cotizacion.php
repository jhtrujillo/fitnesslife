<?php
/**
 * POST api/cotizacion.php
 * Guarda una cotización (y su cliente). No acepta SQL del cliente.
 *
 * Body JSON esperado:
 * {
 *   "cliente": {
 *     "nombre": "Gimnasio Central",
 *     "identificacion": "900123456-1",
 *     "direccion": "Cra 1 #2-3",
 *     "telefono": "3128011838",
 *     "email": "compras@ejemplo.com"
 *   },
 *   "items": [
 *     { "item_no": "INT-DC", "name": "Trotadora Integrity", "qty": 2, "price": 15800000 }
 *   ]
 * }
 */
require __DIR__ . '/config.php';
cors($ALLOWED_ORIGINS, 'POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, false, 'Método no permitido.');
}

$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) {
    respond(400, false, 'JSON inválido.');
}

$cliente = isset($body['cliente']) && is_array($body['cliente']) ? $body['cliente'] : [];
$items   = isset($body['items']) && is_array($body['items']) ? $body['items'] : [];

// --- Validación ---
$errores = [];
$nombre = trim((string) ($cliente['nombre'] ?? ''));
$ident  = trim((string) ($cliente['identificacion'] ?? ''));
$email  = trim((string) ($cliente['email'] ?? ''));
$tel    = trim((string) ($cliente['telefono'] ?? ''));
$dir    = trim((string) ($cliente['direccion'] ?? ''));

if ($nombre === '')  $errores[] = 'El nombre del cliente es obligatorio.';
if ($ident === '')   $errores[] = 'La identificación (NIT / C.C.) es obligatoria.';
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errores[] = 'El email no es válido.';
if (count($items) === 0) $errores[] = 'La cotización no tiene ítems.';
if (count($items) > 200) $errores[] = 'Demasiados ítems en una sola cotización.';

if ($errores) {
    respond(422, false, implode(' ', $errores));
}

// --- Normalizar ítems y calcular totales en el servidor ---
$limpios  = [];
$subtotal = 0.0;
foreach ($items as $it) {
    $qty   = (float) ($it['qty'] ?? 0);
    $price = (float) ($it['price'] ?? 0);
    if ($qty <= 0) continue;
    $linea = $qty * $price;
    $subtotal += $linea;
    $limpios[] = [
        'item_no' => substr(trim((string) ($it['item_no'] ?? '')), 0, 80),
        'name'    => substr(trim((string) ($it['name'] ?? '')), 0, 255),
        'qty'     => $qty,
        'price'   => $price,
        'total'   => $linea,
    ];
}

if (count($limpios) === 0) {
    respond(422, false, 'Ningún ítem tiene cantidad válida.');
}

$iva   = round($subtotal * 0.19, 2);
$total = round($subtotal + $iva, 2);

$pdo = db();

try {
    $pdo->beginTransaction();

    // Cliente: reutiliza por identificación (que es UNIQUE)
    $stmt = $pdo->prepare('SELECT id FROM clientes WHERE identificacion = :ident LIMIT 1');
    $stmt->execute([':ident' => $ident]);
    $cliente_id = $stmt->fetchColumn();

    if ($cliente_id) {
        $upd = $pdo->prepare(
            'UPDATE clientes SET nombre = :nombre, direccion = :dir, telefono = :tel, email = :email WHERE id = :id'
        );
        $upd->execute([
            ':nombre' => $nombre, ':dir' => $dir, ':tel' => $tel,
            ':email' => $email, ':id' => $cliente_id,
        ]);
    } else {
        $ins = $pdo->prepare(
            'INSERT INTO clientes (nombre, identificacion, direccion, telefono, email)
             VALUES (:nombre, :ident, :dir, :tel, :email)'
        );
        $ins->execute([
            ':nombre' => $nombre, ':ident' => $ident,
            ':dir' => $dir, ':tel' => $tel, ':email' => $email,
        ]);
        $cliente_id = $pdo->lastInsertId();
    }

    // Número de cotización consecutivo: COT-2026-0001
    $anio = date('Y');
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM cotizaciones WHERE quote_no LIKE :pfx");
    $stmt->execute([':pfx' => "COT-$anio-%"]);
    $quote_no = sprintf('COT-%s-%04d', $anio, ((int) $stmt->fetchColumn()) + 1);

    $ins = $pdo->prepare(
        'INSERT INTO cotizaciones (quote_no, cliente_id, cliente_json, items_json, subtotal, iva, total)
         VALUES (:quote_no, :cliente_id, :cliente_json, :items_json, :subtotal, :iva, :total)'
    );
    $ins->execute([
        ':quote_no'     => $quote_no,
        ':cliente_id'   => $cliente_id,
        ':cliente_json' => json_encode([
            'nombre' => $nombre, 'identificacion' => $ident, 'direccion' => $dir,
            'telefono' => $tel, 'email' => $email,
        ], JSON_UNESCAPED_UNICODE),
        ':items_json'   => json_encode($limpios, JSON_UNESCAPED_UNICODE),
        ':subtotal'     => $subtotal,
        ':iva'          => $iva,
        ':total'        => $total,
    ]);

    $pdo->commit();

    respond(201, true, 'Cotización guardada.', [
        'id'         => (int) $pdo->lastInsertId(),
        'quote_no'   => $quote_no,
        'cliente_id' => (int) $cliente_id,
        'subtotal'   => $subtotal,
        'iva'        => $iva,
        'total'      => $total,
    ]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('cotizacion.php: ' . $e->getMessage());
    respond(500, false, 'No se pudo guardar la cotización.');
}
