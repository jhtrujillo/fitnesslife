<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Fetch basic stats
$stmt = $pdo->query("SELECT count(*) FROM productos");
$totalProductos = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT count(*) FROM solicitudes_cotizacion");
$totalCotizaciones = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Fitness Life</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background: #f4f6f8; margin: 0; color: #2d3748; }
        .sidebar { width: 250px; background: white; height: 100vh; position: fixed; border-right: 1px solid #e2e8f0; }
        .sidebar-header { padding: 24px; border-bottom: 1px solid #e2e8f0; text-align: center; }
        .sidebar-header img { max-width: 150px; }
        .nav-item { display: block; padding: 16px 24px; color: #4a5568; text-decoration: none; font-weight: 500; border-bottom: 1px solid #f7fafc; transition: background 0.2s; }
        .nav-item:hover, .nav-item.active { background: #f7fafc; color: #c92026; border-left: 4px solid #c92026; padding-left: 20px; }
        .main-content { margin-left: 250px; padding: 40px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; }
        .header h1 { margin: 0; font-size: 24px; }
        .user-menu { display: flex; align-items: center; gap: 16px; font-weight: 600; font-size: 14px; }
        .user-menu a { color: #e53e3e; text-decoration: none; font-size: 13px; }
        
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 24px; }
        .card { background: white; padding: 24px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); border: 1px solid #e2e8f0; }
        .card-title { font-size: 13px; font-weight: 600; color: #718096; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 12px; margin-top: 0; }
        .card-value { font-size: 36px; font-weight: 700; color: #2d3748; margin: 0; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-header">
        <img src="../assets/logo.png" alt="Fitness Life">
    </div>
    <a href="index.php" class="nav-item active">Dashboard</a>
    <a href="productos.php" class="nav-item">Inventario (Productos)</a>
    <a href="cotizaciones.php" class="nav-item">Buzón Cotizaciones</a>
</div>

<div class="main-content">
    <div class="header">
        <h1>Resumen General</h1>
        <div class="user-menu">
            Hola, <?= htmlspecialchars($_SESSION['nombre']) ?>
            <a href="logout.php">Cerrar Sesión</a>
        </div>
    </div>
    
    <div class="grid">
        <div class="card">
            <h3 class="card-title">Equipos Activos</h3>
            <p class="card-value"><?= $totalProductos ?></p>
        </div>
        <div class="card">
            <h3 class="card-title">Cotizaciones Recibidas</h3>
            <p class="card-value"><?= $totalCotizaciones ?></p>
        </div>
    </div>
</div>

</body>
</html>
