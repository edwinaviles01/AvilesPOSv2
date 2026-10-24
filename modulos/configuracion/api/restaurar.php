<?php
set_time_limit(300);
ini_set('memory_limit', '512M');

ob_start();

session_start();
header('Content-Type: application/json; charset=utf-8');

function enviarError($msg) {
    if (ob_get_length()) {
        ob_clean();
    }
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

if (!isset($_SESSION['usuario_id'])) {
    enviarError('Sesión no válida');
}

require __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../includes/permisos.php';

if (!tiene_permiso('config_bd')) {
    enviarError('No tienes permisos');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    enviarError('Método no permitido');
}

if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
    enviarError('No se recibió ningún archivo o hubo un error en la subida');
}

$archivo = $_FILES['archivo']['tmp_name'];
$nombre = $_FILES['archivo']['name'];

if (pathinfo($nombre, PATHINFO_EXTENSION) !== 'sql') {
    enviarError('El archivo debe tener extensión .sql');
}

if ($_FILES['archivo']['size'] > 50 * 1024 * 1024) {
    enviarError('El archivo es demasiado grande (máx 50MB)');
}

$sql = file_get_contents($archivo);

if ($sql === false || trim($sql) === '') {
    enviarError('El archivo está vacío o no se pudo leer');
}

// --- LIMPIAR COMENTARIOS DEL ARCHIVO SQL ---
// Eliminar comentarios de línea (-- ...)
$sql = preg_replace('/^--.*$/m', '', $sql);
// Eliminar comentarios de bloque (/* ... */)
$sql = preg_replace('!/\*.*?\*/!s', '', $sql);

// Deshabilitar restricciones de FK
$conexion->query("SET FOREIGN_KEY_CHECKS = 0");

// Dividir el SQL por punto y coma respetando strings
$queries = [];
$buffer = '';
$in_string = false;
$string_char = '';
$len = strlen($sql);

for ($i = 0; $i < $len; $i++) {
    $char = $sql[$i];

    if (!$in_string && ($char === "'" || $char === '"')) {
        $in_string = true;
        $string_char = $char;
    } elseif ($in_string && $char === $string_char) {
        if ($i > 0 && $sql[$i - 1] === '\\') {
            // Es un escape, sigue en el string
        } else {
            $in_string = false;
        }
    }

    $buffer .= $char;

    if (!$in_string && $char === ';') {
        $trimmed = trim($buffer);
        if ($trimmed !== '' && $trimmed !== ';') {
            $queries[] = $trimmed;
        }
        $buffer = '';
    }
}

if (trim($buffer) !== '') {
    $queries[] = trim($buffer);
}

$errores = [];
$exitosas = 0;

foreach ($queries as $q) {
    if (trim($q) === '' || trim($q) === ';') continue;

    try {
        if (!$conexion->query($q)) {
            if (strpos($conexion->error, 'Unknown table') === false) {
                $errores[] = 'Error: ' . $conexion->error . ' (en query: ' . substr($q, 0, 100) . '...)';
                if (count($errores) > 5) break;
            }
        } else {
            $exitosas++;
        }
    } catch (Throwable $e) {
        $errores[] = 'Excepción: ' . $e->getMessage();
        if (count($errores) > 5) break;
    }
}

$conexion->query("SET FOREIGN_KEY_CHECKS = 1");

if (ob_get_length()) {
    ob_clean();
}

if (!empty($errores)) {
    echo json_encode([
        'ok' => false,
        'error' => 'Restauración con errores (' . $exitosas . ' queries exitosas, ' . count($errores) . ' con error): ' . implode(' | ', array_slice($errores, 0, 3))
    ]);
    exit;
}

echo json_encode([
    'ok' => true,
    'queries_ejecutadas' => $exitosas
]);
exit;