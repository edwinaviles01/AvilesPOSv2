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

// Verificar si hay turnos asociados
$stmt = $conexion->prepare("SELECT COUNT(*) AS total FROM turnos WHERE id_caja = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($res['total'] > 0) {
    echo json_encode([
        'ok' => false,
        'error' => 'No se puede eliminar: la caja tiene ' . $res['total'] . ' turno(s) asociado(s). Considera desactivarla en lugar de borrarla.'
    ]);
    exit;
}

// Eliminar
$stmt = $conexion->prepare("DELETE FROM cajas WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(['ok' => true, 'id' => $id]);
} else {
    echo json_encode(['ok' => false, 'error' => 'Error al eliminar']);
}
$stmt->close();