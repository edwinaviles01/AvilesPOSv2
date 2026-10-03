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

$id = (int)($_POST['id'] ?? 0);
$nombre = trim($_POST['nombre'] ?? '');

if ($id <= 0 || $nombre === '') {
    echo json_encode(['ok' => false, 'error' => 'Datos inválidos']);
    exit;
}

if (mb_strlen($nombre) > 100) {
    echo json_encode(['ok' => false, 'error' => 'El nombre es demasiado largo']);
    exit;
}

$stmt = $conexion->prepare("SELECT id FROM departamentos WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    echo json_encode(['ok' => false, 'error' => 'El departamento no existe']);
    exit;
}
$stmt->close();

$stmt = $conexion->prepare("SELECT id FROM departamentos WHERE nombre = ? AND id != ? LIMIT 1");
$stmt->bind_param("si", $nombre, $id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo json_encode(['ok' => false, 'error' => 'Ya existe otro departamento con ese nombre']);
    exit;
}
$stmt->close();

$stmt = $conexion->prepare("UPDATE departamentos SET nombre = ? WHERE id = ?");
$stmt->bind_param("si", $nombre, $id);

if ($stmt->execute()) {
    echo json_encode(['ok' => true, 'id' => $id, 'nombre' => $nombre]);
} else {
    echo json_encode(['ok' => false, 'error' => 'Error al actualizar']);
}
$stmt->close();