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
$items = $input['items'] ?? [];
$metodo_pago = trim($input['metodo_pago'] ?? 'efectivo');
$referencia = trim($input['referencia'] ?? '');
$motivo = trim($input['motivo'] ?? '');

if ($id_venta <= 0) {
    echo json_encode(['ok' => false, 'error' => 'Venta inválida']);
    exit;
}
if (empty($items)) {
    echo json_encode(['ok' => false, 'error' => 'No hay items para devolver']);
    exit;
}
if (!in_array($metodo_pago, ['efectivo', 'tarjeta', 'transferencia', 'ajuste_credito'])) {
    echo json_encode(['ok' => false, 'error' => 'Método inválido']);
    exit;
}
if ($motivo === '') {
    echo json_encode(['ok' => false, 'error' => 'El motivo es obligatorio']);
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
    echo json_encode(['ok' => false, 'error' => 'No tienes un turno abierto']);
    exit;
}
$id_turno = (int)$res['id'];

// Cargar venta
$stmt = $conexion->prepare("
    SELECT id, numero_factura, id_cliente, estado, metodo_pago 
    FROM ventas WHERE id = ? LIMIT 1
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
    echo json_encode(['ok' => false, 'error' => 'No se puede devolver una venta anulada']);
    exit;
}

// Cargar items originales de la venta
$stmt = $conexion->prepare("
    SELECT id_producto, cantidad, precio_unitario 
    FROM ventas_detalle 
    WHERE id_venta = ?
");
$stmt->bind_param("i", $id_venta);
$stmt->execute();
$items_originales_raw = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Indexar por id_producto
$items_originales = [];
foreach ($items_originales_raw as $io) {
    $items_originales[(int)$io['id_producto']] = [
        'cantidad' => (int)$io['cantidad'],
        'precio_unitario' => (float)$io['precio_unitario']
    ];
}

// Cargar devoluciones previas de esta venta (para no devolver más de lo vendido)
$stmt = $conexion->prepare("
    SELECT dd.id_producto, SUM(dd.cantidad) AS total_devuelto
    FROM devoluciones_detalle dd
    INNER JOIN devoluciones d ON dd.id_devolucion = d.id
    WHERE d.id_venta = ?
    GROUP BY dd.id_producto
");
$stmt->bind_param("i", $id_venta);
$stmt->execute();
$devoluciones_previas_raw = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$devoluciones_previas = [];
foreach ($devoluciones_previas_raw as $dp) {
    $devoluciones_previas[(int)$dp['id_producto']] = (int)$dp['total_devuelto'];
}

// Validar items
$items_validados = [];
$monto_total = 0;

foreach ($items as $it) {
    $id_prod = (int)($it['id_producto'] ?? 0);
    $cantidad = (int)($it['cantidad'] ?? 0);

    if ($id_prod <= 0 || $cantidad <= 0) continue;

    if (!isset($items_originales[$id_prod])) {
        echo json_encode(['ok' => false, 'error' => 'Producto no pertenece a esta venta']);
        exit;
    }

    $cant_original = $items_originales[$id_prod]['cantidad'];
    $cant_ya_devuelta = $devoluciones_previas[$id_prod] ?? 0;
    $cant_disponible = $cant_original - $cant_ya_devuelta;

    if ($cantidad > $cant_disponible) {
        echo json_encode([
            'ok' => false, 
            'error' => "No se pueden devolver $cantidad unidades. Disponibles para devolver: $cant_disponible"
        ]);
        exit;
    }

    $precio = $items_originales[$id_prod]['precio_unitario'];
    // Sumamos IVA incluido para el monto de devolución
    $subtotal_con_iva = $precio * $cantidad;

    $items_validados[] = [
        'id_producto' => $id_prod,
        'cantidad' => $cantidad,
        'precio_unitario' => $precio,
        'subtotal' => $subtotal_con_iva
    ];

    $monto_total += $subtotal_con_iva;
}

if (empty($items_validados)) {
    echo json_encode(['ok' => false, 'error' => 'No hay items válidos']);
    exit;
}

// Validaciones específicas por método
if ($metodo_pago === 'ajuste_credito') {
    if (!$venta['id_cliente']) {
        echo json_encode(['ok' => false, 'error' => 'Esta venta no tiene cliente para ajustar saldo']);
        exit;
    }
}

$referencia_final = $referencia !== '' ? $referencia : null;

// Transacción
$conexion->begin_transaction();

try {
    // 1. Insertar devolución
    $stmt = $conexion->prepare("
        INSERT INTO devoluciones 
        (id_venta, id_usuario, id_turno, monto_total, metodo_pago, referencia, motivo)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param(
        "iiidsss",
        $id_venta, $id_usuario, $id_turno, $monto_total, $metodo_pago, $referencia_final, $motivo
    );
    $stmt->execute();
    $id_devolucion = $stmt->insert_id;
    $stmt->close();

    // 2. Insertar detalle + devolver stock
    foreach ($items_validados as $iv) {
        $stmt = $conexion->prepare("
            INSERT INTO devoluciones_detalle 
            (id_devolucion, id_producto, cantidad, precio_unitario, subtotal)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "iiidd",
            $id_devolucion, $iv['id_producto'], $iv['cantidad'], 
            $iv['precio_unitario'], $iv['subtotal']
        );
        $stmt->execute();
        $stmt->close();

        // Devolver stock
        $stmt = $conexion->prepare("UPDATE productos SET stock = stock + ? WHERE id = ?");
        $stmt->bind_param("ii", $iv['cantidad'], $iv['id_producto']);
        $stmt->execute();
        $stmt->close();
    }

    // 3. Según el método, hacer el ajuste correspondiente
    if ($metodo_pago === 'efectivo') {
        // Salida de caja (se devuelve efectivo)
        $motivo_mov = 'Devolución venta #' . $venta['numero_factura'];
        $stmt = $conexion->prepare("
            INSERT INTO movimientos_caja (id_turno, id_usuario, tipo, monto, motivo)
            VALUES (?, ?, 'salida', ?, ?)
        ");
        $stmt->bind_param("iids", $id_turno, $id_usuario, $monto_total, $motivo_mov);
        $stmt->execute();
        $stmt->close();
    } elseif ($metodo_pago === 'ajuste_credito' && $venta['id_cliente']) {
        // Restar del saldo del cliente
        $stmt = $conexion->prepare("
            UPDATE clientes SET saldo_actual = GREATEST(saldo_actual - ?, 0) WHERE id = ?
        ");
        $stmt->bind_param("di", $monto_total, $venta['id_cliente']);
        $stmt->execute();
        $stmt->close();
    }
    // tarjeta y transferencia: solo queda registrado (se procesa fuera del sistema)

    $conexion->commit();

    echo json_encode([
        'ok' => true,
        'id_devolucion' => $id_devolucion,
        'monto_total' => $monto_total,
        'items_count' => count($items_validados)
    ]);

} catch (Exception $e) {
    $conexion->rollback();
    echo json_encode(['ok' => false, 'error' => 'Error al guardar: ' . $e->getMessage()]);
}