<?php
/**
 * Fitness Life S.A.S — Helper de Exportación y Respaldo de Base de Datos & Archivos
 */

function exportDatabaseSQL($pdo, $dbname) {
    $out = "-- ========================================================\n";
    $out .= "-- Fitness Life S.A.S — Copia de Seguridad Completa\n";
    $out .= "-- Base de Datos: `{$dbname}`\n";
    $out .= "-- Generado el: " . date('Y-m-d H:i:s') . "\n";
    $out .= "-- ========================================================\n\n";
    $out .= "SET FOREIGN_KEY_CHECKS=0;\n";
    $out .= "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n";
    $out .= "SET AUTOCOMMIT = 0;\n";
    $out .= "START TRANSACTION;\n";
    $out .= "SET time_zone = '+00:00';\n";
    $out .= "SET NAMES utf8mb4;\n\n";

    $tables = [];
    $stmt = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
    while ($r = $stmt->fetch(PDO::FETCH_NUM)) {
        $tables[] = $r[0];
    }

    foreach ($tables as $t) {
        $out .= "-- --------------------------------------------------------\n";
        $out .= "-- Tabla: `{$t}`\n";
        $out .= "-- --------------------------------------------------------\n\n";
        $out .= "DROP TABLE IF EXISTS `{$t}`;\n";

        $cr = $pdo->query("SHOW CREATE TABLE `{$t}`")->fetch(PDO::FETCH_ASSOC);
        $out .= $cr['Create Table'] . ";\n\n";

        $cCount = (int)$pdo->query("SELECT COUNT(*) FROM `{$t}`")->fetchColumn();
        if ($cCount > 0) {
            $chunk = 300;
            $pages = ceil($cCount / $chunk);
            for ($p = 0; $p < $pages; $p++) {
                $offset = $p * $chunk;
                $rows = $pdo->query("SELECT * FROM `{$t}` LIMIT {$chunk} OFFSET {$offset}")->fetchAll(PDO::FETCH_ASSOC);
                if (!empty($rows)) {
                    $cols = array_keys($rows[0]);
                    $escCols = array_map(function($c) { return "`" . str_replace("`", "``", $c) . "`"; }, $cols);
                    $insHeader = "INSERT INTO `{$t}` (" . implode(", ", $escCols) . ") VALUES\n";
                    $valsArr = [];
                    foreach ($rows as $row) {
                        $lineVals = [];
                        foreach ($cols as $c) {
                            $v = $row[$c];
                            if ($v === null) {
                                $lineVals[] = "NULL";
                            } else {
                                $lineVals[] = $pdo->quote($v);
                            }
                        }
                        $valsArr[] = "(" . implode(", ", $lineVals) . ")";
                    }
                    $out .= $insHeader . implode(",\n", $valsArr) . ";\n";
                }
            }
            $out .= "\n";
        }
    }
    $out .= "SET FOREIGN_KEY_CHECKS=1;\n";
    $out .= "COMMIT;\n";
    $out .= "-- Fin del Respaldo\n";
    return $out;
}
