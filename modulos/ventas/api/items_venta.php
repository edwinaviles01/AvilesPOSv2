<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['ok' => false, 'error' => 'Sesión no válida']);
    exit;
}

require __DIR__ . '/../../../config/db.php';

$id_venta = (int)($_GET['id'] ?? 0);

if ($id_venta <= 0) {
    echo json_encode(['ok' => false, 'error' => 'ID inválido']);
    exit;
}

// Cargar venta
$stmt = $conexion->prepare("
    SELECT v.id, v.numero_factura, v.fecha, v.total, v.estado, v.metodo_pago,
           c.nombre AS cliente_nombre, c.cedula AS cliente_cedula
    FROM ventas v
    LEFT JOIN clientes c ON v.id_cliente = c.id
    WHERE v.id = ? LIMIT 1
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

// Cargar items con cantidad ya devuelta
$stmt = $conexion->prepare("
    SELECT vd.id_producto, vd.cantidad, vd.precio_unitario,
           p.nombre AS producto_nombre, p.codigo AS producto_codigo,
           COALESCE((
               SELECT SUM(dd.cantidad)
               FROM devoluciones_detalle dd
               INNER JOIN devoluciones d ON dd.id_devolucion = d.id
               WHERE d.id_venta = vd.id_venta AND dd.id_producto = vd.id_producto
           ), 0) AS ya_devuelto
    FROM ventas_detalle vd
    INNER JOIN productos p ON vd.id_producto = p.id
    WHERE vd.id_venta = ?
");
$stmt->bind_param("i", $id_venta);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Convertir ya_devuelto a int y calcular disponible
foreach ($items as &$it) {
    $it['ya_devuelto'] = (int)$it['ya_devuelto'];
    $it['disponible'] = (int)$it['cantidad'] - $it['ya_devuelto'];
}
unset($it);

echo json_encode([
    'ok' => true,
    'venta' => $venta,
    'items' => $items
]);