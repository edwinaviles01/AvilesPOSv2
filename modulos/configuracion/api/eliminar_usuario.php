<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['ok' => false, 'error' => 'Sesión no válida']);
    exit;
}

if ($_SESSION['usuario_rol'] !== 'administrador') {
    echo json_encode(['ok' => false, 'error' => 'No tienes permisos para esta acción']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
    exit;
}

require __DIR__ . '/../../../config/db.php';

$input = json_decode(file_get_contents('php://input'), true);
$id = (int)($input['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['ok' => false, 'error' => 'ID inválido']);
    exit;
}

// No se puede eliminar a sí mismo
if ($id === (int)$_SESSION['usuario_id']) {
    echo json_encode(['ok' => false, 'error' => 'No puedes eliminar tu propio usuario']);
    exit;
}

// Verificar que exista
$stmt = $conexion->prepare("SELECT id, nombre FROM usuarios WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    echo json_encode(['ok' => false, 'error' => 'Usuario no encontrado']);
    exit;
}

// ¿Tiene registros asociados?
$tiene_registros = false;

// Verificar ventas
$stmt = $conexion->prepare("SELECT COUNT(*) AS t FROM ventas WHERE id_usuario = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
if ((int)$stmt->get_result()->fetch_assoc()['t'] > 0) $tiene_registros = true;
$stmt->close();

// Verificar turnos
if (!$tiene_registros) {
    $stmt = $conexion->prepare("SELECT COUNT(*) AS t FROM turnos WHERE id_usuario_apertura = ? OR id_usuario_cierre = ?");
    $stmt->bind_param("ii", $id, $id);
    $stmt->execute();
    if ((int)$stmt->get_result()->fetch_assoc()['t'] > 0) $tiene_registros = true;
    $stmt->close();
}

// Verificar movimientos_caja
if (!$tiene_registros) {
    $stmt = $conexion->prepare("SELECT COUNT(*) AS t FROM movimientos_caja WHERE id_usuario = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    if ((int)$stmt->get_result()->fetch_assoc()['t'] > 0) $tiene_registros = true;
    $stmt->close();
}

// Verificar abonos
if (!$tiene_registros) {
    $stmt = $conexion->prepare("SELECT COUNT(*) AS t FROM abonos WHERE id_usuario = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    if ((int)$stmt->get_result()->fetch_assoc()['t'] > 0) $tiene_registros = true;
    $stmt->close();
}

// Verificar movimientos_inventario
if (!$tiene_registros) {
    $stmt = $conexion->prepare("SELECT COUNT(*) AS t FROM movimientos_inventario WHERE id_usuario = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    if ((int)$stmt->get_result()->fetch_assoc()['t'] > 0) $tiene_registros = true;
    $stmt->close();
}

if ($tiene_registros) {
    // Solo desactivar
    $stmt = $conexion->prepare("UPDATE usuarios SET activo = 0 WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    echo json_encode([
        'ok' => true,
        'accion' => 'desactivado',
        'mensaje' => 'El usuario tiene registros asociados. Se desactivó en lugar de eliminarse.'
    ]);
} else {
    // Eliminar definitivamente
    $stmt = $conexion->prepare("DELETE FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    echo json_encode([
        'ok' => true,
        'accion' => 'eliminado',
        'mensaje' => 'Usuario eliminado correctamente.'
    ]);
}