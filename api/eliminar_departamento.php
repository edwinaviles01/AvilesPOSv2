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

if ($id <= 0) {
    echo json_encode(['ok' => false, 'error' => 'ID inválido']);
    exit;
}

$stmt = $conexion->prepare("SELECT COUNT(*) AS total FROM categorias WHERE id_departamento = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($res['total'] > 0) {
    echo json_encode([
        'ok' => false,
        'error' => 'No se puede eliminar: tiene ' . $res['total'] . ' categoría(s) asociada(s). Elimina o reasigna primero.'
    ]);
    exit;
}

$stmt = $conexion->prepare("SELECT COUNT(*) AS total FROM productos WHERE id_departamento = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($res['total'] > 0) {
    echo json_encode([
        'ok' => false,
        'error' => 'No se puede eliminar: hay ' . $res['total'] . ' producto(s) asociado(s).'
    ]);
    exit;
}

$stmt = $conexion->prepare("DELETE FROM departamentos WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(['ok' => true, 'id' => $id]);
} else {
    echo json_encode(['ok' => false, 'error' => 'Error al eliminar']);
}
$stmt->close();