<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['ok' => false, 'error' => 'Sesión no válida']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
    exit;
}

require __DIR__ . '/../config/db.php';

$nombre = trim($_POST['nombre'] ?? '');

if ($nombre === '') {
    echo json_encode(['ok' => false, 'error' => 'El nombre es obligatorio']);
    exit;
}

if (mb_strlen($nombre) > 100) {
    echo json_encode(['ok' => false, 'error' => 'El nombre es demasiado largo']);
    exit;
}

$stmt = $conexion->prepare("SELECT id FROM departamentos WHERE nombre = ? LIMIT 1");
$stmt->bind_param("s", $nombre);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo json_encode(['ok' => false, 'error' => 'Ya existe un departamento con ese nombre']);
    exit;
}
$stmt->close();

$stmt = $conexion->prepare("INSERT INTO departamentos (nombre) VALUES (?)");
$stmt->bind_param("s", $nombre);

if ($stmt->execute()) {
    echo json_encode([
        'ok' => true,
        'id' => $stmt->insert_id,
        'nombre' => $nombre
    ]);
} else {
    echo json_encode(['ok' => false, 'error' => 'Error al guardar']);
}
$stmt->close();