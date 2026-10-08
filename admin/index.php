<?php
require_once 'config.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
$stats = [
    'productos' => $pdo->query("SELECT COUNT(*) FROM productos")->fetchColumn(),
    'cotizaciones' => $pdo->query("SELECT COUNT(*) FROM solicitudes_cotizacion")->fetchColumn(),
    'marcas' => $pdo->query("SELECT COUNT(*) FROM marcas")->fetchColumn(),
    'categorias' => $pdo->query("SELECT COUNT(*) FROM categorias")->fetchColumn()
];
include 'header.php';
?>
<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:20px;">
    <div class="panel-card" style="text-align:center;">
        <h3 style="margin:0;color:#718096;font-size:14px;text-transform:uppercase;">Equipos</h3>
        <div style="font-size:36px;font-weight:700;color:#1d3557;margin-top:10px;"><?= $stats['productos'] ?></div>
    </div>
    <div class="panel-card" style="text-align:center;">
        <h3 style="margin:0;color:#718096;font-size:14px;text-transform:uppercase;">Leads (Cotizaciones)</h3>
        <div style="font-size:36px;font-weight:700;color:#e63946;margin-top:10px;"><?= $stats['cotizaciones'] ?></div>
    </div>
    <div class="panel-card" style="text-align:center;">
        <h3 style="margin:0;color:#718096;font-size:14px;text-transform:uppercase;">Categorías</h3>
        <div style="font-size:36px;font-weight:700;color:#1d3557;margin-top:10px;"><?= $stats['categorias'] ?></div>
    </div>
    <div class="panel-card" style="text-align:center;">
        <h3 style="margin:0;color:#718096;font-size:14px;text-transform:uppercase;">Marcas</h3>
        <div style="font-size:36px;font-weight:700;color:#1d3557;margin-top:10px;"><?= $stats['marcas'] ?></div>
    </div>
</div>
</body></html>
