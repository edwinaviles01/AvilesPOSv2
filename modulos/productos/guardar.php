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

// -------- Recibir datos --------
$codigo         = trim($_POST['codigo'] ?? '');
$nombre         = trim($_POST['nombre'] ?? '');
$descripcion    = trim($_POST['descripcion'] ?? '');
$id_departamento = (int)($_POST['id_departamento'] ?? 0);
$id_categoria    = (int)($_POST['id_categoria'] ?? 0);
$id_proveedor    = (int)($_POST['id_proveedor'] ?? 0);
$precio_costo    = trim($_POST['precio_costo'] ?? '');
$precio          = trim($_POST['precio'] ?? '');
$stock           = trim($_POST['stock'] ?? '0');
$stock_minimo    = trim($_POST['stock_minimo'] ?? '0');
$activo          = isset($_POST['activo']) ? 1 : 0;

// Convertir 0 a NULL para las FKs
$id_departamento = $id_departamento > 0 ? $id_departamento : null;
$id_categoria    = $id_categoria > 0 ? $id_categoria : null;
$id_proveedor    = $id_proveedor > 0 ? $id_proveedor : null;

// -------- Validaciones --------
$errores = [];

if ($codigo === '') $errores[] = "El código es obligatorio.";
if ($nombre === '') $errores[] = "El nombre es obligatorio.";

if ($precio_costo === '' || !is_numeric($precio_costo) || $precio_costo < 0) {
    $errores[] = "El precio de compra debe ser un número mayor o igual a 0.";
}
if ($precio === '' || !is_numeric($precio) || $precio < 0) {
    $errores[] = "El precio de venta debe ser un número mayor o igual a 0.";
}
if ($stock === '' || !ctype_digit($stock)) {
    $errores[] = "El stock debe ser un número entero mayor o igual a 0.";
}
if ($stock_minimo !== '' && !ctype_digit($stock_minimo)) {
    $errores[] = "El stock mínimo debe ser un número entero.";
}

// Verificar código repetido
if (empty($errores)) {
    $stmt = $conexion->prepare("SELECT id FROM productos WHERE codigo = ? LIMIT 1");
    $stmt->bind_param("s", $codigo);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $errores[] = "El código '$codigo' ya está registrado.";
    }
    $stmt->close();
}

// -------- Si hay errores, volvemos al formulario --------
if (!empty($errores)) {
    $_SESSION['producto_error'] = implode(' ', $errores);
    $_SESSION['producto_old'] = $_POST;
    header("Location: crear.php");
    exit;
}

// -------- Subir foto (opcional) --------
$nombre_archivo = null;
if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
    $permitidos = ['image/jpeg', 'image/png', 'image/webp'];
    $tipo = mime_content_type($_FILES['foto']['tmp_name']);

    if (!in_array($tipo, $permitidos)) {
        $_SESSION['producto_error'] = "La foto debe ser JPG, PNG o WEBP.";
        $_SESSION['producto_old'] = $_POST;
        header("Location: crear.php");
        exit;
    }

    if ($_FILES['foto']['size'] > 2 * 1024 * 1024) {
        $_SESSION['producto_error'] = "La foto no puede pesar más de 2MB.";
        $_SESSION['producto_old'] = $_POST;
        header("Location: crear.php");
        exit;
    }

    // Generar nombre único
    $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
    $nombre_archivo = 'prod_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . strtolower($ext);

    $carpeta_destino = __DIR__ . '/../../assets/img/productos/';
    if (!is_dir($carpeta_destino)) {
        mkdir($carpeta_destino, 0755, true);
    }

    if (!move_uploaded_file($_FILES['foto']['tmp_name'], $carpeta_destino . $nombre_archivo)) {
        $_SESSION['producto_error'] = "Error al guardar la foto.";
        $_SESSION['producto_old'] = $_POST;
        header("Location: crear.php");
        exit;
    }
}

// -------- Insertar --------
$stock_minimo = $stock_minimo === '' ? 0 : (int)$stock_minimo;

$sql = "INSERT INTO productos 
        (codigo, nombre, descripcion, foto, id_departamento, id_categoria, id_proveedor,
         precio_costo, precio, stock, stock_minimo, activo)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conexion->prepare($sql);
$stmt->bind_param(
    "ssssiiiiddii",
    $codigo, $nombre, $descripcion, $nombre_archivo,
    $id_departamento, $id_categoria, $id_proveedor,
    $precio_costo, $precio, $stock, $stock_minimo, $activo
);

if ($stmt->execute()) {
    $_SESSION['producto_mensaje'] = "Producto '$nombre' creado correctamente.";
    header("Location: index.php");
    exit;
} else {
    $_SESSION['producto_error'] = "Error al guardar: " . $stmt->error;
    $_SESSION['producto_old'] = $_POST;
    header("Location: crear.php");
    exit;
}
$stmt->close();