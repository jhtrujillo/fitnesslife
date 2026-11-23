<?php
require_once 'config.php';
if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php"); 
    exit; 
}

// Control de acceso: solo Administradores con acceso web o superadmin
$isAdmin = (($_SESSION['rol_web'] ?? '') === 'admin' || ($_SESSION['rol'] ?? '') === 'admin');
if (!$isAdmin) {
    header("Location: index.php");
    exit;
}

$dbDir = __DIR__ . '/../backups/db/';
$filesDir = __DIR__ . '/../backups/files/';
if (!is_dir($dbDir)) { @mkdir($dbDir, 0755, true); }
if (!is_dir($filesDir)) { @mkdir($filesDir, 0755, true); }

require_once __DIR__ . '/backup_helper.php';

// Acción: Descargar archivo almacenado
if (isset($_GET['action']) && $_GET['action'] === 'download' && !empty($_GET['file'])) {
    $filename = basename($_GET['file']);
    $type = $_GET['type'] ?? 'db';
    $path = ($type === 'files') ? $filesDir . $filename : $dbDir . $filename;
    if (!file_exists($path)) {
        $path = __DIR__ . '/../backups/' . $filename;
    }

    if (file_exists($path) && is_file($path)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($path));
        flush();
        readfile($path);
        exit;
    } else {
        $_SESSION['flash_error'] = "El archivo solicitado no existe.";
        header("Location: respaldos.php");
        exit;
    }
}

// Acción: Eliminar archivo almacenado
if (isset($_GET['action']) && $_GET['action'] === 'delete' && !empty($_GET['file'])) {
    $filename = basename($_GET['file']);
    $type = $_GET['type'] ?? 'db';
    $path = ($type === 'files') ? $filesDir . $filename : $dbDir . $filename;
    if (!file_exists($path)) {
        $path = __DIR__ . '/../backups/' . $filename;
    }

    if (file_exists($path) && is_file($path)) {
        @unlink($path);
        $_SESSION['flash_success'] = "Copia '{$filename}' eliminada exitosamente.";
    } else {
        $_SESSION['flash_error'] = "No se pudo encontrar el archivo a eliminar.";
    }
    header("Location: respaldos.php");
    exit;
}

// Procesar formulario POST para generar respaldos
$msgSuccess = $_SESSION['flash_success'] ?? null;
$msgError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $act = $_POST['action'];

    // 1. Respaldo de Base de Datos
    if ($act === 'backup_db_download' || $act === 'backup_db_save') {
        try {
            $sqlContent = exportDatabaseSQL($pdo, $dbname);
            $filename = "fitnesslife_db_" . date('Y-m-d_His') . ".sql";

            if ($act === 'backup_db_download') {
                header('Content-Type: application/sql; charset=utf-8');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                header('Expires: 0');
                header('Pragma: no-cache');
                echo $sqlContent;
                exit;
            } else {
                $savedPath = $dbDir . $filename;
                file_put_contents($savedPath, $sqlContent);
                $msgSuccess = "Copia de seguridad de la base de datos guardada en el servidor: <strong>{$filename}</strong> (" . round(strlen($sqlContent) / 1024, 1) . " KB)";
            }
        } catch (Exception $e) {
            $msgError = "Error al generar respaldo de la base de datos: " . htmlspecialchars($e->getMessage());
        }
    }

    // 2. Respaldo de Archivos (Uploads / Imágenes del catálogo)
    if ($act === 'backup_files_download' || $act === 'backup_files_save') {
        if (!class_exists('ZipArchive')) {
            $msgError = "La extensión ZipArchive de PHP no está instalada o habilitada en este servidor.";
        } else {
            $zipFilename = "fitnesslife_uploads_" . date('Y-m-d_His') . ".zip";
            $tempZip = sys_get_temp_dir() . '/' . $zipFilename;
            $zip = new ZipArchive();

            if ($zip->open($tempZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                $uploadsPath = realpath(__DIR__ . '/../uploads');
                if ($uploadsPath && is_dir($uploadsPath)) {
                    $files = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($uploadsPath, RecursiveDirectoryIterator::SKIP_DOTS),
                        RecursiveIteratorIterator::LEAVES_ONLY
                    );
                    $addedCount = 0;
                    foreach ($files as $name => $file) {
                        if (!$file->isDir()) {
                            $filePath = $file->getRealPath();
                            $relativePath = 'uploads/' . substr($filePath, strlen($uploadsPath) + 1);
                            $zip->addFile($filePath, $relativePath);
                            $addedCount++;
                        }
                    }
                }
                $zip->close();

                if ($act === 'backup_files_download') {
                    header('Content-Description: File Transfer');
                    header('Content-Type: application/zip');
                    header('Content-Disposition: attachment; filename="' . $zipFilename . '"');
                    header('Expires: 0');
                    header('Cache-Control: must-revalidate');
                    header('Pragma: public');
                    header('Content-Length: ' . filesize($tempZip));
                    flush();
                    readfile($tempZip);
                    @unlink($tempZip);
                    exit;
                } else {
                    $destPath = $filesDir . $zipFilename;
                    rename($tempZip, $destPath);
                    $msgSuccess = "Respaldo de archivos guardado en el servidor: <strong>{$zipFilename}</strong> (" . round(filesize($destPath) / (1024 * 1024), 2) . " MB)";
                }
            } else {
                $msgError = "No se pudo inicializar la compresión ZIP de archivos.";
            }
        }
    }
}

// Estadísticas de la base de datos
$dbTablesCount = 0;
$dbRowsCount = 0;
try {
    $tStmt = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
    $allTables = $tStmt->fetchAll(PDO::FETCH_NUM);
    $dbTablesCount = count($allTables);
    foreach ($allTables as $tRow) {
        $dbRowsCount += (int)$pdo->query("SELECT COUNT(*) FROM `{$tRow[0]}`")->fetchColumn();
    }
} catch (Exception $e) {}

// Obtener lista de respaldos almacenados en servidor
$backupsList = [];
$scanFolders = [
    ['dir' => $dbDir, 'type' => 'db', 'label' => 'Base de Datos (SQL)'],
    ['dir' => $filesDir, 'type' => 'files', 'label' => 'Archivos (ZIP)'],
    ['dir' => __DIR__ . '/../backups/', 'type' => 'db', 'label' => 'Histórico']
];

foreach ($scanFolders as $f) {
    if (is_dir($f['dir'])) {
        $files = scandir($f['dir']);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..' || $file === '.htaccess' || $file === 'index.html' || is_dir($f['dir'] . $file)) {
                continue;
            }
            $fullPath = $f['dir'] . $file;
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $isSql = ($ext === 'sql');
            $isZip = ($ext === 'zip');
            $isJson = ($ext === 'json');
            
            $kind = $f['type'];
            if ($isZip) $kind = 'files';
            
            $backupsList[] = [
                'name' => $file,
                'path' => $fullPath,
                'type' => $kind,
                'category' => $isSql ? 'Base de Datos (SQL)' : ($isZip ? 'Imágenes y Archivos (ZIP)' : ($isJson ? 'Datos Exportados (JSON)' : 'Archivo')),
                'size' => filesize($fullPath),
                'date' => filemtime($fullPath)
            ];
        }
    }
}

// Ordenar por fecha descendente
usort($backupsList, function($a, $b) {
    return $b['date'] - $a['date'];
});

// Total de almacenamiento ocupado
$totalStorage = array_sum(array_column($backupsList, 'size'));

include 'header.php';
?>

<div class="panel-card" style="margin-bottom: 24px;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 20px;">
        <div>
            <h2 style="font-family: 'Oswald', sans-serif; font-size: 24px; text-transform: uppercase; color: #1d3557; margin: 0 0 6px 0; display:flex; align-items:center; gap:10px;">
                <span>💾 Copias de Seguridad & Respaldos</span>
            </h2>
            <p style="color: #64748b; font-size: 14px; margin: 0;">
                Sistema integral de respaldo para la base de datos (catálogo, cotizaciones, usuarios) y archivos multimedia.
            </p>
        </div>
        <div style="display: flex; gap: 10px; align-items: center;">
            <span style="background: #e0f2fe; color: #0369a1; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 700;">
                BD: <?= htmlspecialchars($dbname) ?> (<?= $dbTablesCount ?> tablas, <?= number_format($dbRowsCount) ?> registros)
            </span>
            <span style="background: #f1f5f9; color: #475569; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 700;">
                Respaldos en servidor: <?= count($backupsList) ?> (<?= round($totalStorage / (1024 * 1024), 2) ?> MB)
            </span>
        </div>
    </div>

    <?php if ($msgSuccess): ?>
    <div style="background: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 14px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 14px;">
        <?= $msgSuccess ?>
    </div>
    <?php endif; ?>

    <?php if ($msgError): ?>
    <div style="background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 14px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 14px;">
        <?= $msgError ?>
    </div>
    <?php endif; ?>

    <!-- TARJETAS DE ACCIÓN DE RESPALDO -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px; margin-bottom: 32px;">
        
        <!-- CARD 1: BASE DE DATOS -->
        <div style="border: 1px solid #cbd5e1; border-radius: 12px; padding: 22px; background: #fafcff; display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                    <span style="font-family: 'Oswald', sans-serif; font-size: 18px; text-transform: uppercase; color: #1d3557; font-weight: 700;">
                        🗄️ Base de Datos MySQL
                    </span>
                    <span style="background: #dbeafe; color: #1e40af; font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 12px;">SQL Nativo</span>
                </div>
                <p style="font-size: 13px; color: #475569; line-height: 1.5; margin-bottom: 16px;">
                    Exporta la estructura completa y todos los datos: catálogo de productos, categorías, marcas, series, cotizaciones comerciales, clientes y usuarios.
                </p>
                <div style="background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 14px; font-size: 12px; color: #64748b; margin-bottom: 18px;">
                    ✓ <?= $dbTablesCount ?> tablas verificadas<br>
                    ✓ <?= number_format($dbRowsCount) ?> registros incluidos<br>
                    ✓ Compatible con phpMyAdmin o consola MySQL
                </div>
            </div>
            <div style="display: flex; gap: 10px; flex-direction: column;">
                <form method="POST" style="margin: 0;">
                    <input type="hidden" name="action" value="backup_db_download">
                    <button type="submit" class="btn-primary" style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 12px; font-size: 14px; background: #1d3557;">
                        📥 Descargar Copia SQL a tu Computador
                    </button>
                </form>
                <form method="POST" style="margin: 0;">
                    <input type="hidden" name="action" value="backup_db_save">
                    <button type="submit" style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 10px; font-size: 13px; background: white; border: 1px solid #cbd5e1; color: #334155; border-radius: 6px; font-weight: 600; cursor: pointer;">
                        💾 Guardar Copia en la Carpeta Segura del Servidor
                    </button>
                </form>
            </div>
        </div>

        <!-- CARD 2: ARCHIVOS Y FOTOS -->
        <div style="border: 1px solid #cbd5e1; border-radius: 12px; padding: 22px; background: #fafcff; display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                    <span style="font-family: 'Oswald', sans-serif; font-size: 18px; text-transform: uppercase; color: #1d3557; font-weight: 700;">
                        📁 Archivos & Fotos (Uploads)
                    </span>
                    <span style="background: #fef3c7; color: #92400e; font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 12px;">ZIP</span>
                </div>
                <p style="font-size: 13px; color: #475569; line-height: 1.5; margin-bottom: 16px;">
                    Genera un paquete comprimido .ZIP que contiene las imágenes de productos subidas, logos y documentos adjuntos de la carpeta <code>uploads/</code>.
                </p>
                <div style="background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 14px; font-size: 12px; color: #64748b; margin-bottom: 18px;">
                    ✓ Fotos originales de equipos y series<br>
                    ✓ Archivos y anexos de cotizaciones<br>
                    ✓ Empaquetado comprimido estándar ZIP
                </div>
            </div>
            <div style="display: flex; gap: 10px; flex-direction: column;">
                <form method="POST" style="margin: 0;">
                    <input type="hidden" name="action" value="backup_files_download">
                    <button type="submit" class="btn-primary" style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 12px; font-size: 14px; background: #e63946;">
                        📥 Descargar Archivos ZIP a tu Computador
                    </button>
                </form>
                <form method="POST" style="margin: 0;">
                    <input type="hidden" name="action" value="backup_files_save">
                    <button type="submit" style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 10px; font-size: 13px; background: white; border: 1px solid #cbd5e1; color: #334155; border-radius: 6px; font-weight: 600; cursor: pointer;">
                        💾 Guardar ZIP en la Carpeta Segura del Servidor
                    </button>
                </form>
            </div>
        </div>

    </div>

    <!-- SECCIÓN: COPIAS ALMACENADAS EN EL SERVIDOR -->
    <h3 style="font-family: 'Oswald', sans-serif; font-size: 20px; text-transform: uppercase; color: #1d3557; margin-bottom: 14px; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px;">
        📦 Copias de Seguridad Almacenadas en Servidor
    </h3>

    <?php if (empty($backupsList)): ?>
    <div style="text-align: center; padding: 36px 20px; color: #64748b; background: #f8fafc; border-radius: 8px; border: 1px dashed #cbd5e1;">
        No hay copias guardadas en el servidor actualmente. Puedes generar una con los botones superiores.
    </div>
    <?php else: ?>
    <div class="table-wrapper">
        <table class="products-table">
            <thead>
                <tr>
                    <th>Nombre de Archivo</th>
                    <th>Tipo</th>
                    <th>Tamaño</th>
                    <th>Fecha de Creación</th>
                    <th style="text-align: right;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($backupsList as $b): ?>
                <tr>
                    <td data-label="Archivo" style="font-weight: 600; color: #1e293b;">
                        <?= htmlspecialchars($b['name']) ?>
                    </td>
                    <td data-label="Tipo">
                        <span style="display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 700; background: <?= $b['type']==='db'?'#e0f2fe':'#fef3c7' ?>; color: <?= $b['type']==='db'?'#0369a1':'#92400e' ?>;">
                            <?= htmlspecialchars($b['category']) ?>
                        </span>
                    </td>
                    <td data-label="Tamaño" style="color: #475569; font-size: 12px;">
                        <?= ($b['size'] > 1048576) ? round($b['size'] / 1048576, 2) . ' MB' : round($b['size'] / 1024, 1) . ' KB' ?>
                    </td>
                    <td data-label="Fecha" style="color: #475569; font-size: 12px;">
                        <?= date('Y-m-d H:i:s', $b['date']) ?>
                    </td>
                    <td class="actions-cell" style="text-align: right; white-space: nowrap;">
                        <a href="respaldos.php?action=download&type=<?= urlencode($b['type']) ?>&file=<?= urlencode($b['name']) ?>" class="action-link" style="color: #0284c7; font-weight: 700; text-decoration: none; margin-right: 14px;">
                            📥 Descargar
                        </a>
                        <a href="respaldos.php?action=delete&type=<?= urlencode($b['type']) ?>&file=<?= urlencode($b['name']) ?>" class="action-link danger" onclick="return confirm('¿Confirma que desea eliminar la copia <?= htmlspecialchars($b['name']) ?>?');" style="color: #e11d48; font-weight: 700; text-decoration: none;">
                            🗑️ Eliminar
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- SECCIÓN INFORMATIVA DE AUTOMATIZACIÓN Y SEGURIDAD -->
    <div style="margin-top: 36px; padding: 20px 24px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; font-size: 13px; color: #475569;">
        <h4 style="font-family: 'Oswald', sans-serif; font-size: 16px; text-transform: uppercase; color: #1d3557; margin: 0 0 8px 0;">
            🔒 Protección y Automatización de Respaldos
        </h4>
        <ul style="margin: 0; padding-left: 20px; line-height: 1.6;">
            <li><strong>Protección contra accesos no autorizados:</strong> La carpeta <code>/backups/</code> cuenta con directivas Apache de bloqueo total (<code>Require all denied</code>). Nadie puede descargar copias directamente mediante enlace URL sin una sesión activa de Administrador.</li>
            <li><strong>Ejecución Programada Automática (cPanel Cron Job):</strong> Se puede configurar una tarea automática nocturna en cPanel para generar copias periódicas ejecutando:
                <div style="background: #1e293b; color: #38bdf8; padding: 8px 12px; border-radius: 6px; font-family: monospace; font-size: 12px; margin: 6px 0;">
                    php /home/fitnessl/public_html/admin/cron_backup.php
                </div>
                O mediante llamada web segura con token:
                <div style="background: #1e293b; color: #38bdf8; padding: 8px 12px; border-radius: 6px; font-family: monospace; font-size: 12px; margin: 6px 0;">
                    curl -s "https://www.fitnesslifesas.com/admin/cron_backup.php?token=FitLifeSecureBackup2026"
                </div>
            </li>
            <li><strong>Rotación automática:</strong> El script de automatización conserva los últimos 30 días de respaldos y depura los más antiguos para proteger el espacio en disco del servidor.</li>
        </ul>
    </div>

</div>

</body>
</html>
