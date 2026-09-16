<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Fetch leads
$stmt = $pdo->query("SELECT * FROM solicitudes_cotizacion ORDER BY fecha DESC");
$cotizaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotizaciones - Fitness Life</title>
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
        
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.02); border: 1px solid #e2e8f0; }
        th, td { padding: 16px; text-align: left; border-bottom: 1px solid #e2e8f0; font-size: 14px; }
        th { background: #f7fafc; font-weight: 600; color: #718096; text-transform: uppercase; font-size: 12px; letter-spacing: 0.05em; }
        .badge { background: #e2e8f0; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; color: #4a5568; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-header">
        <img src="../assets/logo.png" alt="Fitness Life">
    </div>
    <a href="index.php" class="nav-item">Dashboard</a>
    <a href="productos.php" class="nav-item">Inventario (Productos)</a>
    <a href="cotizaciones.php" class="nav-item active">Buzón Cotizaciones</a>
</div>

<div class="main-content">
    <div class="header">
        <h1>Buzón de Cotizaciones (Leads)</h1>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Cliente</th>
                <th>Datos de Contacto</th>
                <th>Mensaje / Equipos</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($cotizaciones as $c): ?>
                <tr>
                    <td style="white-space: nowrap;"><?= htmlspecialchars($c['fecha']) ?></td>
                    <td>
                        <strong><?= htmlspecialchars($c['nombre'] ?? 'Sin nombre') ?></strong><br>
                        <span style="color:#718096; font-size:12px;"><?= htmlspecialchars($c['empresa'] ?? '') ?></span>
                    </td>
                    <td>
                        📞 <?= htmlspecialchars($c['telefono'] ?? '-') ?><br>
                        ✉️ <?= htmlspecialchars($c['email'] ?? '-') ?>
                    </td>
                    <td>
                        <?php if(!empty($c['json_productos'])): ?>
                            <span class="badge"><?= count(json_decode($c['json_productos'], true) ?: []) ?> Equipos</span>
                        <?php endif; ?>
                        <p style="margin-top:8px; font-size:13px; color:#4a5568;"><?= nl2br(htmlspecialchars($c['mensaje'] ?? '')) ?></p>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if(count($cotizaciones) === 0): ?>
                <tr><td colspan="4" style="text-align:center; color:#a0aec0;">No hay cotizaciones aún.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>
