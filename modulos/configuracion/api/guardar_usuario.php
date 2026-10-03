<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['ok' => false, 'error' => 'Sesión no válida']);
    exit;
}

// Solo el rol administrador puede gestionar usuarios
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
if (!$input) {
    echo json_encode(['ok' => false, 'error' => 'Datos inválidos']);
    exit;
}

$id = (int)($input['id'] ?? 0);
$nombre = trim($input['nombre'] ?? '');
$usuario = trim($input['usuario'] ?? '');
$rol = trim($input['rol'] ?? '');
$activo = (int)($input['activo'] ?? 1);
$password = $input['password'] ?? null;
$permisos_extra = $input['permisos_extra'] ?? [];
$permisos_denegados = $input['permisos_denegados'] ?? [];

// Si es administrador: forzar activo = 1 y sin permisos custom
// (es superusuario y no se puede desactivar ni quitarle funciones)
if ($rol === 'administrador') {
    $activo = 1;
    $permisos_extra = [];
    $permisos_denegados = [];
}

// Validaciones
if ($nombre === '' || $usuario === '' || $rol === '') {
    echo json_encode(['ok' => false, 'error' => 'Nombre, usuario y rol son obligatorios']);
    exit;
}

if (!preg_match('/^[a-zA-Z0-9_]+$/', $usuario)) {
    echo json_encode(['ok' => false, 'error' => 'El usuario solo puede tener letras, números y guiones bajos']);
    exit;
}

// Verificar que el rol existe
$stmt = $conexion->prepare("SELECT id FROM roles WHERE clave = ? LIMIT 1");
$stmt->bind_param("s", $rol);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    echo json_encode(['ok' => false, 'error' => 'El rol especificado no existe']);
    exit;
}
$stmt->close();

// Verificar usuario duplicado
if ($id > 0) {
    $stmt = $conexion->prepare("SELECT id FROM usuarios WHERE usuario = ? AND id != ? LIMIT 1");
    $stmt->bind_param("si", $usuario, $id);
} else {
    $stmt = $conexion->prepare("SELECT id FROM usuarios WHERE usuario = ? LIMIT 1");
    $stmt->bind_param("s", $usuario);
}
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo json_encode(['ok' => false, 'error' => 'Ya existe un usuario con ese nombre de usuario']);
    exit;
}
$stmt->close();

// Transacción
$conexion->begin_transaction();

try {
    if ($id > 0) {
        // EDITAR
        // Verificar que exista
        $stmt = $conexion->prepare("SELECT id FROM usuarios WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 0) {
            throw new Exception('El usuario no existe');
        }
        $stmt->close();

        $stmt = $conexion->prepare("
            UPDATE usuarios SET nombre = ?, usuario = ?, rol = ?, activo = ?
            WHERE id = ?
        ");
        $stmt->bind_param("sssii", $nombre, $usuario, $rol, $activo, $id);
        $stmt->execute();
        $stmt->close();

        // Si viene nueva contraseña, actualizarla
        if ($password !== null && $password !== '') {
            if (strlen($password) < 3) {
                throw new Exception('La contraseña debe tener al menos 3 caracteres');
            }
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conexion->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hash, $id);
            $stmt->execute();
            $stmt->close();
        }

        // Borrar los permisos personalizados anteriores
        $stmt = $conexion->prepare("DELETE FROM usuarios_permisos WHERE id_usuario = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

    } else {
        // CREAR
        if ($password === null || strlen($password) < 3) {
            throw new Exception('La contraseña debe tener al menos 3 caracteres');
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conexion->prepare("
            INSERT INTO usuarios (nombre, usuario, password, rol, activo)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("ssssi", $nombre, $usuario, $hash, $rol, $activo);
        $stmt->execute();
        $id = $stmt->insert_id;
        $stmt->close();
    }

    // Insertar permisos extra
    if (!empty($permisos_extra)) {
        $stmt = $conexion->prepare("
            INSERT INTO usuarios_permisos (id_usuario, id_permiso, tipo)
            SELECT ?, id, 'extra' FROM permisos WHERE clave = ?
        ");
        foreach ($permisos_extra as $clave) {
            $stmt->bind_param("is", $id, $clave);
            $stmt->execute();
        }
        $stmt->close();
    }

    // Insertar permisos denegados
    if (!empty($permisos_denegados)) {
        $stmt = $conexion->prepare("
            INSERT INTO usuarios_permisos (id_usuario, id_permiso, tipo)
            SELECT ?, id, 'denegado' FROM permisos WHERE clave = ?
        ");
        foreach ($permisos_denegados as $clave) {
            $stmt->bind_param("is", $id, $clave);
            $stmt->execute();
        }
        $stmt->close();
    }

    $conexion->commit();

    echo json_encode(['ok' => true, 'id' => $id]);

} catch (Exception $e) {
    $conexion->rollback();
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}