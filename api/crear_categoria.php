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
$id_departamento = (int)($_POST['id_departamento'] ?? 0);

if ($nombre === '') {
    echo json_encode(['ok' => false, 'error' => 'El nombre es obligatorio']);
    exit;
}

if ($id_departamento <= 0) {
    echo json_encode(['ok' => false, 'error' => 'Debes seleccionar un departamento']);
    exit;
}

if (mb_strlen($nombre) > 100) {
    echo json_encode(['ok' => false, 'error' => 'El nombre es demasiado largo']);
    exit;
}

$stmt = $conexion->prepare("SELECT id FROM departamentos WHERE id = ? AND activo = 1 LIMIT 1");
$stmt->bind_param("i", $id_departamento);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    echo json_encode(['ok' => false, 'error' => 'El departamento no existe']);
    exit;
}
$stmt->close();

$stmt = $conexion->prepare("SELECT id FROM categorias WHERE nombre = ? AND id_departamento = ? LIMIT 1");
$stmt->bind_param("si", $nombre, $id_departamento);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo json_encode(['ok' => false, 'error' => 'Ya existe esa categoría en el departamento']);
    exit;
}
$stmt->close();

$stmt = $conexion->prepare("INSERT INTO categorias (id_departamento, nombre) VALUES (?, ?)");
$stmt->bind_param("is", $id_departamento, $nombre);

if ($stmt->execute()) {
    echo json_encode([
        'ok' => true,
        'id' => $stmt->insert_id,
        'nombre' => $nombre,
        'id_departamento' => $id_departamento
    ]);
} else {
    echo json_encode(['ok' => false, 'error' => 'Error al guardar']);
}
$stmt->close();