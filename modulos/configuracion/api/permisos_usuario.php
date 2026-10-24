<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['ok' => false, 'error' => 'Sesión no válida']);
    exit;
}

require __DIR__ . '/../../../config/db.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['ok' => false, 'error' => 'ID inválido']);
    exit;
}

$extra = [];
$denegado = [];

$stmt = $conexion->prepare("
    SELECT up.tipo, p.clave
    FROM usuarios_permisos up
    INNER JOIN permisos p ON up.id_permiso = p.id
    WHERE up.id_usuario = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

foreach ($res as $row) {
    if ($row['tipo'] === 'extra') {
        $extra[] = $row['clave'];
    } else {
        $denegado[] = $row['clave'];
    }
}

echo json_encode([
    'ok' => true,
    'extra' => $extra,
    'denegado' => $denegado
]);