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

require __DIR__ . '/../../../config/db.php';

$id_usuario = (int)$_SESSION['usuario_id'];

$id_caja        = (int)($_POST['id_caja'] ?? 0);
$monto_inicial  = trim($_POST['monto_inicial'] ?? '');
$compartido     = isset($_POST['compartido']) ? 1 : 0;

// -------- Validaciones --------
$errores = [];

if ($id_caja <= 0) {
    $errores[] = "Debes seleccionar una caja.";
}

if ($monto_inicial === '' || !is_numeric($monto_inicial) || $monto_inicial < 0) {
    $errores[] = "El monto inicial debe ser un número mayor o igual a 0.";
}

// Validar que el usuario no tenga turno abierto
if (empty($errores)) {
    $stmt = $conexion->prepare("SELECT id FROM turnos WHERE id_usuario_apertura = ? AND estado = 'abierto' LIMIT 1");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $errores[] = "Ya tienes un turno abierto.";
    }
    $stmt->close();
}

// Validar que la caja exista, esté activa y no tenga turno abierto
if (empty($errores)) {
    $stmt = $conexion->prepare("SELECT id, activa FROM cajas WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $id_caja);
    $stmt->execute();
    $caja = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$caja) {
        $errores[] = "La caja no existe.";
    } elseif (!$caja['activa']) {
        $errores[] = "La caja está inactiva.";
    } else {
        $stmt = $conexion->prepare("SELECT id FROM turnos WHERE id_caja = ? AND estado = 'abierto' LIMIT 1");
        $stmt->bind_param("i", $id_caja);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errores[] = "Esta caja ya tiene un turno abierto.";
        }
        $stmt->close();
    }
}

// -------- Si hay errores --------
if (!empty($errores)) {
    $_SESSION['turno_error'] = implode(' ', $errores);
    $_SESSION['turno_old'] = $_POST;
    header("Location: abrir.php");
    exit;
}

// -------- Crear turno --------
$monto_inicial = (float)$monto_inicial;

$sql = "INSERT INTO turnos 
        (id_caja, id_usuario_apertura, monto_inicial, compartido, estado)
        VALUES (?, ?, ?, ?, 'abierto')";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("iidi", $id_caja, $id_usuario, $monto_inicial, $compartido);

if ($stmt->execute()) {
    $_SESSION['turno_mensaje'] = "Turno abierto correctamente.";
    header("Location: index.php");
    exit;
} else {
    $_SESSION['turno_error'] = "Error al abrir el turno: " . $stmt->error;
    $_SESSION['turno_old'] = $_POST;
    header("Location: abrir.php");
    exit;
}
$stmt->close();