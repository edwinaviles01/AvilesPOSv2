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

$input = json_decode(file_get_contents('php://input'), true);
$id_usuario = (int)($input['id_usuario'] ?? 0);
$password = $input['password'] ?? '';

if ($id_usuario <= 0) {
    echo json_encode(['ok' => false, 'error' => 'Usuario inválido']);
    exit;
}

if (strlen($password) < 3) {
    echo json_encode(['ok' => false, 'error' => 'La contraseña debe tener al menos 3 caracteres']);
    exit;
}

// Solo el admin o el propio usuario pueden cambiar
$es_admin = $_SESSION['usuario_rol'] === 'administrador';
$es_mismo = (int)$_SESSION['usuario_id'] === $id_usuario;

if (!$es_admin && !$es_mismo) {
    echo json_encode(['ok' => false, 'error' => 'No tienes permisos']);
    exit;
}

// Verificar que el usuario existe
$stmt = $conexion->prepare("SELECT id FROM usuarios WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    echo json_encode(['ok' => false, 'error' => 'Usuario no encontrado']);
    exit;
}
$stmt->close();

// Actualizar contraseña
$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conexion->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
$stmt->bind_param("si", $hash, $id_usuario);

if ($stmt->execute()) {
    echo json_encode(['ok' => true]);
} else {
    echo json_encode(['ok' => false, 'error' => 'Error al actualizar']);
}
$stmt->close();