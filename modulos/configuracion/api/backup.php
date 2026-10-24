<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: /AvilesPOSv2/index.php");
    exit;
}

require __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../includes/permisos.php';

if (!tiene_permiso('config_bd')) {
    header("Location: /AvilesPOSv2/dashboard.php");
    exit;
}

// Nombre del archivo
$nombre_archivo = 'backup_avilespos_' . date('Y-m-d_H-i-s') . '.sql';

// Headers para descarga
header('Content-Type: application/sql; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $nombre_archivo . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Iniciar buffer
$output = "-- ============================================\n";
$output .= "-- Respaldo de AvilesPOS\n";
$output .= "-- Generado: " . date('d/m/Y H:i:s') . "\n";
$output .= "-- Por: " . ($_SESSION['usuario_nombre'] ?? 'N/A') . "\n";
$output .= "-- ============================================\n\n";

$output .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
$output .= "SET time_zone = \"-06:00\";\n";
$output .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

// Obtener todas las tablas
$tables_result = $conexion->query("SHOW TABLES");
$tables = [];
while ($row = $tables_result->fetch_array()) {
    $tables[] = $row[0];
}

foreach ($tables as $table) {
    // Estructura de la tabla
    $output .= "--\n-- Estructura de la tabla `$table`\n--\n\n";
    $output .= "DROP TABLE IF EXISTS `$table`;\n";

    $create_result = $conexion->query("SHOW CREATE TABLE `$table`")->fetch_row();
    $output .= $create_result[1] . ";\n\n";

    // Datos
    $rows_result = $conexion->query("SELECT * FROM `$table`");
    $num_rows = $rows_result->num_rows;

    if ($num_rows > 0) {
        $output .= "--\n-- Datos de la tabla `$table`\n--\n\n";

        while ($row = $rows_result->fetch_assoc()) {
            $keys = array_keys($row);
            $values = array_values($row);

            $escaped_values = [];
            foreach ($values as $val) {
                if ($val === null) {
                    $escaped_values[] = 'NULL';
                } else {
                    $escaped = $conexion->real_escape_string($val);
                    $escaped_values[] = "'" . $escaped . "'";
                }
            }

            $output .= "INSERT INTO `$table` (`" . implode("`, `", $keys) . "`) VALUES (" . implode(", ", $escaped_values) . ");\n";
        }
        $output .= "\n";
    }
}

$output .= "SET FOREIGN_KEY_CHECKS = 1;\n";

echo $output;

// Registrar en configuracion que se hizo un backup
$stmt = $conexion->prepare("
    INSERT INTO configuracion (clave, valor) VALUES ('ultimo_backup', ?)
    ON DUPLICATE KEY UPDATE valor = VALUES(valor)
");
$fecha = date('Y-m-d H:i:s');
$stmt->bind_param("s", $fecha);
$stmt->execute();
$stmt->close();

exit;