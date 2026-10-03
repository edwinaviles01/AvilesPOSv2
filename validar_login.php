<?php
session_start();
require "config/db.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$usuario = trim($_POST['usuario'] ?? '');
$password = $_POST['password'] ?? '';

if ($usuario === '' || $password === '') {
    header("Location: index.php?error=1");
    exit;
}

$sql = "SELECT id, nombre, usuario, password, rol, activo FROM usuarios WHERE usuario = ? LIMIT 1";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $usuario);
$stmt->execute();
$resultado = $stmt->get_result();

// Si no existe el usuario, mismo mensaje genérico (sin revelar)
if ($resultado->num_rows === 0) {
    header("Location: index.php?error=1");
    exit;
}

$user = $resultado->fetch_assoc();

// Verificar que esté activo
if (isset($user['activo']) && $user['activo'] == 0) {
    header("Location: index.php?error=1");
    exit;
}

if (!password_verify($password, $user['password'])) {
    header("Location: index.php?error=1");
    exit;
}

// Login correcto: regeneramos el ID de sesión (evita fijación de sesión)
session_regenerate_id(true);

// Guardamos datos en la sesión
$_SESSION['usuario_id'] = $user['id'];
$_SESSION['usuario_nombre'] = $user['nombre'];
$_SESSION['usuario_rol'] = $user['rol'];

// Cargar permisos del usuario
require_once __DIR__ . '/includes/permisos.php';
cargar_permisos_usuario($conexion, $user['id'], $user['rol']);

// Actualizar último acceso
$stmt_upd = $conexion->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?");
$stmt_upd->bind_param("i", $user['id']);
$stmt_upd->execute();
$stmt_upd->close();

// Redirigimos al dashboard
header("Location: dashboard.php");
exit;