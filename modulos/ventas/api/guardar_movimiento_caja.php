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

require __DIR__ . '/../../../config/db.php';

$id_usuario = (int)$_SESSION['usuario_id'];

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['ok' => false, 'error' => 'Datos inválidos']);
    exit;
}

$tipo = trim($input['tipo'] ?? '');
$monto = (float)($input['monto'] ?? 0);
$motivo = trim($input['motivo'] ?? '');

// Validaciones
if (!in_array($tipo, ['entrada', 'salida'])) {
    echo json_encode(['ok' => false, 'error' => 'Tipo inválido.']);
    exit;
}
if ($monto <= 0) {
    echo json_encode(['ok' => false, 'error' => 'El monto debe ser mayor a 0.']);
    exit;
}
if ($motivo === '') {
    echo json_encode(['ok' => false, 'error' => 'El motivo es obligatorio.']);
    exit;
}

// Verificar turno abierto del usuario
$stmt = $conexion->prepare("
    SELECT id FROM turnos 
    WHERE estado = 'abierto'
      AND (id_usuario_apertura = ? OR (compartido = 1 AND id_usuario_apertura != ?))
    ORDER BY fecha_apertura ASC
    LIMIT 1
");
$stmt->bind_param("ii", $id_usuario, $id_usuario);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$res) {
    echo json_encode(['ok' => false, 'error' => 'No tienes un turno abierto.']);
    exit;
}
$id_turno = (int)$res['id'];

// Insertar
$stmt = $conexion->prepare("
    INSERT INTO movimientos_caja (id_turno, id_usuario, tipo, monto, motivo)
    VALUES (?, ?, ?, ?, ?)
");
$stmt->bind_param("iisds", $id_turno, $id_usuario, $tipo, $monto, $motivo);

if ($stmt->execute()) {
    echo json_encode([
        'ok' => true,
        'id' => $stmt->insert_id,
        'tipo' => $tipo,
        'monto' => $monto
    ]);
} else {
    echo json_encode(['ok' => false, 'error' => 'Error al guardar: ' . $stmt->error]);
}
$stmt->close();