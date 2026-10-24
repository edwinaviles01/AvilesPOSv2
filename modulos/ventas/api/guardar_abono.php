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

$id_cliente = (int)($input['id_cliente'] ?? 0);
$monto = (float)($input['monto'] ?? 0);
$metodo_pago = trim($input['metodo_pago'] ?? 'efectivo');
$referencia = trim($input['referencia'] ?? '');
$observaciones = trim($input['observaciones'] ?? '');

// Validaciones
if ($id_cliente <= 0) {
    echo json_encode(['ok' => false, 'error' => 'Cliente inválido.']);
    exit;
}
if ($monto <= 0) {
    echo json_encode(['ok' => false, 'error' => 'El monto debe ser mayor a 0.']);
    exit;
}
if (!in_array($metodo_pago, ['efectivo', 'transferencia', 'tarjeta'])) {
    echo json_encode(['ok' => false, 'error' => 'Método de pago inválido.']);
    exit;
}

// Verificar turno abierto
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

// Cargar cliente
$stmt = $conexion->prepare("
    SELECT id, nombre, saldo_actual FROM clientes 
    WHERE id = ? AND activo = 1 LIMIT 1
");
$stmt->bind_param("i", $id_cliente);
$stmt->execute();
$cliente = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$cliente) {
    echo json_encode(['ok' => false, 'error' => 'El cliente no existe.']);
    exit;
}

$saldo_anterior = (float)$cliente['saldo_actual'];

if ($monto > $saldo_anterior + 0.01) {
    echo json_encode([
        'ok' => false, 
        'error' => 'El monto supera el saldo pendiente (' . number_format($saldo_anterior, 2) . ').'
    ]);
    exit;
}

$saldo_nuevo = $saldo_anterior - $monto;
if ($saldo_nuevo < 0) $saldo_nuevo = 0;

$referencia_final = $referencia !== '' ? $referencia : null;
$observaciones_final = $observaciones !== '' ? $observaciones : null;

// Transacción
$conexion->begin_transaction();

try {
    // Insertar abono
    $stmt = $conexion->prepare("
        INSERT INTO abonos 
        (id_cliente, id_usuario, id_turno, monto, metodo_pago, referencia, 
         observaciones, saldo_anterior, saldo_nuevo)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param(
        "iiidsssdd",
        $id_cliente, $id_usuario, $id_turno, $monto, $metodo_pago, $referencia_final,
        $observaciones_final, $saldo_anterior, $saldo_nuevo
    );
    $stmt->execute();
    $id_abono = $stmt->insert_id;
    $stmt->close();

    // Actualizar saldo del cliente
    $stmt = $conexion->prepare("UPDATE clientes SET saldo_actual = ? WHERE id = ?");
    $stmt->bind_param("di", $saldo_nuevo, $id_cliente);
    $stmt->execute();
    $stmt->close();

    // Si fue en efectivo, registrar como entrada de caja
    if ($metodo_pago === 'efectivo') {
        $motivo_mov = 'Abono de cliente: ' . $cliente['nombre'];
        $stmt = $conexion->prepare("
            INSERT INTO movimientos_caja (id_turno, id_usuario, tipo, monto, motivo)
            VALUES (?, ?, 'entrada', ?, ?)
        ");
        $stmt->bind_param("iids", $id_turno, $id_usuario, $monto, $motivo_mov);
        $stmt->execute();
        $stmt->close();
    }

    $conexion->commit();

    echo json_encode([
        'ok' => true,
        'id_abono' => $id_abono,
        'saldo_anterior' => $saldo_anterior,
        'saldo_nuevo' => $saldo_nuevo
    ]);

} catch (Exception $e) {
    $conexion->rollback();
    echo json_encode(['ok' => false, 'error' => 'Error al guardar: ' . $e->getMessage()]);
}