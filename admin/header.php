<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fitness Life Admin</title>
    <?php include 'css_patch.php'; ?>
</head>
<body>
    <div class="app-header">
        <h1><img src="../assets/logo.png" style="height:32px" alt="Logo"> Admin</h1>
        <div class="header-actions">
            <?php $curr = basename($_SERVER['PHP_SELF']); ?>
            <a href="productos.php" class="btn-nav <?= $curr=='productos.php'||$curr=='producto_form.php' ? 'active' : '' ?>">Productos</a>
            <a href="categorias.php" class="btn-nav <?= $curr=='categorias.php'||$curr=='categoria_form.php' ? 'active' : '' ?>">Categorías</a>
            <a href="marcas.php" class="btn-nav <?= $curr=='marcas.php'||$curr=='marca_form.php' ? 'active' : '' ?>">Marcas</a>
            <a href="cotizaciones.php" class="btn-nav <?= $curr=='cotizaciones.php' ? 'active' : '' ?>">Cotizaciones</a>
            <a href="logout.php" class="btn-nav" style="color:#e53e3e;border-color:#feb2b2;background:#fff5f5">Salir</a>
        </div>
    </div>
    <div class="main-container">
