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
$id = (int)($_POST['id'] ?? 0);
$monto_final_contado = trim($_POST['monto_final_contado'] ?? '');
$observaciones = trim($_POST['observaciones'] ?? '');

// Validaciones
if ($id <= 0) {
    echo json_encode(['ok' => false, 'error' => 'Turno inválido.']);
    exit;
}
if ($monto_final_contado === '' || !is_numeric($monto_final_contado) || $monto_final_contado < 0) {
    echo json_encode(['ok' => false, 'error' => 'El monto contado debe ser un número mayor o igual a 0.']);
    exit;
}

// Cargar el turno
$stmt = $conexion->prepare("
    SELECT * FROM turnos 
    WHERE id = ? AND id_usuario_apertura = ? AND estado = 'abierto'
    LIMIT 1
");
$stmt->bind_param("ii", $id, $id_usuario);
$stmt->execute();
$turno = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$turno) {
    echo json_encode(['ok' => false, 'error' => 'El turno no existe, ya está cerrado, o no te pertenece.']);
    exit;
}

// ---------- Cálculos de efectivo ----------

// Ventas en efectivo
$stmt = $conexion->prepare("
    SELECT COALESCE(SUM(total), 0) AS total_efectivo
    FROM ventas 
    WHERE id_turno = ? AND metodo_pago = 'efectivo' AND estado = 'completada'
");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$stmt->close();
$ventas_efectivo = (float)$res['total_efectivo'];

// Movimientos de caja
$stmt = $conexion->prepare("
    SELECT 
        COALESCE(SUM(CASE WHEN tipo = 'entrada' THEN monto ELSE 0 END), 0) AS entradas,
        COALESCE(SUM(CASE WHEN tipo = 'salida' THEN monto ELSE 0 END), 0) AS salidas
    FROM movimientos_caja 
    WHERE id_turno = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$stmt->close();
$entradas_caja = (float)$res['entradas'];
$salidas_caja = (float)$res['salidas'];

$monto_final_sistema = (float)$turno['monto_inicial'] + $ventas_efectivo + $entradas_caja - $salidas_caja;
$monto_final_contado = (float)$monto_final_contado;
$diferencia = $monto_final_contado - $monto_final_sistema;

$observaciones = $observaciones !== '' ? $observaciones : null;

// Actualizar turno
$sql = "UPDATE turnos SET
        estado = 'cerrado',
        fecha_cierre = NOW(),
        monto_final_contado = ?,
        monto_final_sistema = ?,
        diferencia = ?,
        id_usuario_cierre = ?,
        observaciones = ?
        WHERE id = ?";

$stmt = $conexion->prepare($sql);
$stmt->bind_param(
    "dddisi",
    $monto_final_contado, $monto_final_sistema, $diferencia,
    $id_usuario, $observaciones, $id
);

if ($stmt->execute()) {
    $msg = "Turno cerrado correctamente.";
    if (abs($diferencia) < 0.01) {
        $msg .= " Cuadre exacto.";
    } elseif ($diferencia > 0) {
        $msg .= " Sobrante de C$ " . number_format($diferencia, 2) . ".";
    } else {
        $msg .= " Faltante de C$ " . number_format(abs($diferencia), 2) . ".";
    }

    echo json_encode([
        'ok' => true,
        'mensaje' => $msg,
        'diferencia' => $diferencia,
        'monto_sistema' => $monto_final_sistema
    ]);
} else {
    echo json_encode(['ok' => false, 'error' => 'Error al cerrar el turno: ' . $stmt->error]);
}
$stmt->close();