<?php
/**
 * Fitness Life S.A.S — Script de Respaldo Automático Programado (Cron Job)
 * Puede ejecutarse desde CLI: php cron_backup.php
 * O vía HTTPS con token: cron_backup.php?token=FitLifeSecureBackup2026
 */

$isCli = (php_sapi_name() === 'cli');
$secretToken = 'FitLifeSecureBackup2026';

if (!$isCli) {
    $providedToken = $_GET['token'] ?? '';
    if (!hash_equals($secretToken, $providedToken)) {
        header('HTTP/1.1 403 Forbidden');
        die("Acceso denegado. Token no válido.");
    }
}

require_once __DIR__ . '/config.php';

$dbDir = __DIR__ . '/../backups/db/';
if (!is_dir($dbDir)) {
    @mkdir($dbDir, 0755, true);
}

// Cargar función de exportación
require_once __DIR__ . '/backup_helper.php';

try {
    $sqlContent = exportDatabaseSQL($pdo, $dbname);
    $filename = "fitnesslife_db_auto_" . date('Y-m-d_His') . ".sql";
    $filePath = $dbDir . $filename;
    
    file_put_contents($filePath, $sqlContent);
    $sizeKb = round(strlen($sqlContent) / 1024, 1);
    
    // Rotación: eliminar respaldos automáticos con más de 30 días
    $files = scandir($dbDir);
    $deletedCount = 0;
    $now = time();
    $maxAge = 30 * 86400; // 30 días
    
    foreach ($files as $f) {
        if (strpos($f, 'fitnesslife_db_auto_') === 0 && pathinfo($f, PATHINFO_EXTENSION) === 'sql') {
            $fPath = $dbDir . $f;
            if (($now - filemtime($fPath)) > $maxAge) {
                @unlink($fPath);
                $deletedCount++;
            }
        }
    }
    
    $result = [
        'ok' => true,
        'message' => 'Respaldo automático generado con éxito.',
        'file' => $filename,
        'size_kb' => $sizeKb,
        'timestamp' => date('Y-m-d H:i:s'),
        'purged_old_backups' => $deletedCount
    ];
    
    if ($isCli) {
        echo "[OK] Respaldo generado: {$filename} ({$sizeKb} KB). Depurados antiguos: {$deletedCount}\n";
    } else {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
} catch (Exception $e) {
    if ($isCli) {
        echo "[ERROR] " . $e->getMessage() . "\n";
    } else {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
}
