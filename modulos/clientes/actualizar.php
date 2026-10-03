<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: /AvilesPOSv2/index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

require __DIR__ . '/../../config/db.php';

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    header("Location: index.php");
    exit;
}

// Verificar que exista
$stmt = $conexion->prepare("SELECT id FROM clientes WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    header("Location: index.php");
    exit;
}
$stmt->close();

// -------- Recibir datos --------
$nombre           = trim($_POST['nombre'] ?? '');
$tipo             = trim($_POST['tipo'] ?? 'natural');
$cedula           = trim($_POST['cedula'] ?? '');
$ruc              = trim($_POST['ruc'] ?? '');
$telefono         = trim($_POST['telefono'] ?? '');
$email            = trim($_POST['email'] ?? '');
$direccion        = trim($_POST['direccion'] ?? '');
$permite_credito  = isset($_POST['permite_credito']) ? 1 : 0;
$limite_credito   = trim($_POST['limite_credito'] ?? '0');
$notas            = trim($_POST['notas'] ?? '');
$activo           = isset($_POST['activo']) ? 1 : 0;

// -------- Validaciones --------
$errores = [];

if ($nombre === '') {
    $errores[] = "El nombre es obligatorio.";
}

if (!in_array($tipo, ['natural', 'juridica'])) {
    $errores[] = "Tipo de cliente inválido.";
}

if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errores[] = "El email no es válido.";
}

if ($permite_credito && $limite_credito !== '' && (!is_numeric($limite_credito) || $limite_credito < 0)) {
    $errores[] = "El límite de crédito debe ser un número mayor o igual a 0.";
}

if (!empty($errores)) {
    $_SESSION['cliente_error'] = implode(' ', $errores);
    $_SESSION['cliente_old'] = $_POST;
    header("Location: editar.php?id=$id");
    exit;
}

// -------- Normalizar --------
$cedula = $cedula !== '' ? $cedula : null;
$ruc = $ruc !== '' ? $ruc : null;
$telefono = $telefono !== '' ? $telefono : null;
$email = $email !== '' ? $email : null;
$direccion = $direccion !== '' ? $direccion : null;
$notas = $notas !== '' ? $notas : null;
$limite_credito = $permite_credito ? (float)$limite_credito : 0.00;

// -------- Actualizar --------
$sql = "UPDATE clientes SET
        nombre = ?, tipo = ?, cedula = ?, ruc = ?, telefono = ?, email = ?,
        direccion = ?, permite_credito = ?, limite_credito = ?, notas = ?, activo = ?
        WHERE id = ?";

$stmt = $conexion->prepare($sql);
$stmt->bind_param(
    "sssssssidsii",
    $nombre, $tipo, $cedula, $ruc, $telefono, $email, $direccion,
    $permite_credito, $limite_credito, $notas, $activo,
    $id
);

if ($stmt->execute()) {
    $_SESSION['cliente_mensaje'] = "Cliente '$nombre' actualizado correctamente.";
    header("Location: index.php");
    exit;
} else {
    $_SESSION['cliente_error'] = "Error al actualizar: " . $stmt->error;
    $_SESSION['cliente_old'] = $_POST;
    header("Location: editar.php?id=$id");
    exit;
}
$stmt->close();