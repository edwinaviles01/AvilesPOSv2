<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['ok' => false, 'error' => 'Sesión no válida']);
    exit;
}

require __DIR__ . '/../../../config/db.php';

$q = trim($_GET['q'] ?? '');

if ($q === '') {
    echo json_encode(['ok' => true, 'ventas' => []]);
    exit;
}

// Buscar por número de factura o cédula
$sql = "SELECT v.id, v.numero_factura, v.fecha, v.total, v.estado,
               c.nombre AS cliente_nombre, c.cedula AS cliente_cedula,
               u.nombre AS cajero_nombre
        FROM ventas v
        LEFT JOIN clientes c ON v.id_cliente = c.id
        INNER JOIN usuarios u ON v.id_usuario = u.id
        WHERE v.estado = 'completada'
          AND (
              CAST(v.numero_factura AS CHAR) = ? 
              OR CAST(v.numero_factura AS CHAR) LIKE ?
              OR c.cedula LIKE ?
          )
        ORDER BY v.fecha DESC
        LIMIT 10";

$stmt = $conexion->prepare($sql);
$like = '%' . $q . '%';
$stmt->bind_param("sss", $q, $like, $like);
$stmt->execute();
$ventas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

echo json_encode(['ok' => true, 'ventas' => $ventas]);