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

$id     = (int)($_POST['id'] ?? 0);
$nombre = trim($_POST['nombre'] ?? '');
$activa = isset($_POST['activa']) ? 1 : 0;

if ($nombre === '') {
    echo json_encode(['ok' => false, 'error' => 'El nombre es obligatorio']);
    exit;
}

if (mb_strlen($nombre) > 50) {
    echo json_encode(['ok' => false, 'error' => 'El nombre es demasiado largo (máx 50)']);
    exit;
}

// Verificar duplicado (excluyendo la propia caja si es edición)
if ($id > 0) {
    $stmt = $conexion->prepare("SELECT id FROM cajas WHERE nombre = ? AND id != ? LIMIT 1");
    $stmt->bind_param("si", $nombre, $id);
} else {
    $stmt = $conexion->prepare("SELECT id FROM cajas WHERE nombre = ? LIMIT 1");
    $stmt->bind_param("s", $nombre);
}
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo json_encode(['ok' => false, 'error' => 'Ya existe una caja con ese nombre']);
    exit;
}
$stmt->close();

if ($id > 0) {
    // Editar
    $stmt = $conexion->prepare("SELECT id FROM cajas WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows === 0) {
        echo json_encode(['ok' => false, 'error' => 'La caja no existe']);
        exit;
    }
    $stmt->close();

    $stmt = $conexion->prepare("UPDATE cajas SET nombre = ?, activa = ? WHERE id = ?");
    $stmt->bind_param("sii", $nombre, $activa, $id);

    if ($stmt->execute()) {
        echo json_encode(['ok' => true, 'id' => $id, 'nombre' => $nombre]);
    } else {
        echo json_encode(['ok' => false, 'error' => 'Error al actualizar']);
    }
    $stmt->close();
} else {
    // Crear
    $stmt = $conexion->prepare("INSERT INTO cajas (nombre, activa) VALUES (?, ?)");
    $stmt->bind_param("si", $nombre, $activa);

    if ($stmt->execute()) {
        echo json_encode(['ok' => true, 'id' => $stmt->insert_id, 'nombre' => $nombre]);
    } else {
        echo json_encode(['ok' => false, 'error' => 'Error al crear la caja']);
    }
    $stmt->close();
}