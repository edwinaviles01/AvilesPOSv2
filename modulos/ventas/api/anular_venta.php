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

$id_venta = (int)($input['id_venta'] ?? 0);
$motivo = trim($input['motivo'] ?? '');

if ($id_venta <= 0) {
    echo json_encode(['ok' => false, 'error' => 'ID de venta inválido']);
    exit;
}

if ($motivo === '') {
    echo json_encode(['ok' => false, 'error' => 'El motivo es obligatorio']);
    exit;
}

// Cargar venta
$stmt = $conexion->prepare("
    SELECT id, numero_factura, id_cliente, metodo_pago, total, estado 
    FROM ventas 
    WHERE id = ? LIMIT 1
");
$stmt->bind_param("i", $id_venta);
$stmt->execute();
$venta = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$venta) {
    echo json_encode(['ok' => false, 'error' => 'Venta no encontrada']);
    exit;
}

if ($venta['estado'] === 'anulada') {
    echo json_encode(['ok' => false, 'error' => 'Esta venta ya está anulada']);
    exit;
}

// Cargar items
$stmt = $conexion->prepare("
    SELECT id_producto, cantidad 
    FROM ventas_detalle 
    WHERE id_venta = ?
");
$stmt->bind_param("i", $id_venta);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Transacción
$conexion->begin_transaction();

try {
    // 1. Marcar venta como anulada
    $stmt = $conexion->prepare("
        UPDATE ventas 
        SET estado = 'anulada',
            fecha_anulacion = NOW(),
            id_usuario_anulacion = ?,
            motivo_anulacion = ?
        WHERE id = ?
    ");
    $stmt->bind_param("isi", $id_usuario, $motivo, $id_venta);
    $stmt->execute();
    $stmt->close();

    // 2. Devolver stock de cada item
    foreach ($items as $item) {
        $stmt = $conexion->prepare("
            UPDATE productos SET stock = stock + ? WHERE id = ?
        ");
        $stmt->bind_param("ii", $item['cantidad'], $item['id_producto']);
        $stmt->execute();
        $stmt->close();
    }

        // 2b. Actualizar el turno (restar del total y número de ventas)
    $stmt = $conexion->prepare("
        UPDATE turnos 
        SET total_ventas = GREATEST(total_ventas - ?, 0),
            numero_ventas = GREATEST(numero_ventas - 1, 0)
        WHERE id = (SELECT id_turno FROM ventas WHERE id = ?)
    ");
    $stmt->bind_param("di", $venta['total'], $id_venta);
    $stmt->execute();
    $stmt->close();
    
    // 3. Si fue al crédito, descontar del saldo del cliente
    if ($venta['metodo_pago'] === 'credito' && $venta['id_cliente']) {
        $stmt = $conexion->prepare("
            UPDATE clientes 
            SET saldo_actual = GREATEST(saldo_actual - ?, 0) 
            WHERE id = ?
        ");
        $stmt->bind_param("di", $venta['total'], $venta['id_cliente']);
        $stmt->execute();
        $stmt->close();
    }

    $conexion->commit();

    echo json_encode([
        'ok' => true,
        'numero_factura' => $venta['numero_factura'],
        'items_devueltos' => count($items)
    ]);

} catch (Exception $e) {
    $conexion->rollback();
    echo json_encode(['ok' => false, 'error' => 'Error al anular: ' . $e->getMessage()]);
}