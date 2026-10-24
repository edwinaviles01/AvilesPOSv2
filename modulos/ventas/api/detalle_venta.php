<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['ok' => false, 'error' => 'Sesión no válida']);
    exit;
}

require __DIR__ . '/../../../config/db.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['ok' => false, 'error' => 'ID inválido']);
    exit;
}

// Cargar venta
$stmt = $conexion->prepare("
    SELECT v.*, 
           c.nombre AS cliente_nombre, c.cedula AS cliente_cedula,
           u.nombre AS cajero_nombre,
           ua.nombre AS anulada_por,
           cj.nombre AS caja_nombre
    FROM ventas v
    LEFT JOIN clientes c ON v.id_cliente = c.id
    INNER JOIN usuarios u ON v.id_usuario = u.id
    LEFT JOIN usuarios ua ON v.id_usuario_anulacion = ua.id
    INNER JOIN turnos t ON v.id_turno = t.id
    INNER JOIN cajas cj ON t.id_caja = cj.id
    WHERE v.id = ?
    LIMIT 1
");
$stmt->bind_param("i", $id);
$stmt->execute();
$venta = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$venta) {
    echo json_encode(['ok' => false, 'error' => 'Venta no encontrada']);
    exit;
}

// Cargar items
$stmt = $conexion->prepare("
    SELECT vd.*, p.nombre AS producto_nombre, p.codigo AS producto_codigo
    FROM ventas_detalle vd
    INNER JOIN productos p ON vd.id_producto = p.id
    WHERE vd.id_venta = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

echo json_encode([
    'ok' => true,
    'venta' => $venta,
    'items' => $items
]);