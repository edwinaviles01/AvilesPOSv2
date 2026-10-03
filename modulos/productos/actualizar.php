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
$stmt = $conexion->prepare("SELECT id, foto FROM productos WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$producto_actual = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$producto_actual) {
    header("Location: index.php");
    exit;
}

// Recibir datos
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
$foto_actual     = $_POST['foto_actual'] ?? '';

$id_departamento = $id_departamento > 0 ? $id_departamento : null;
$id_categoria    = $id_categoria > 0 ? $id_categoria : null;
$id_proveedor    = $id_proveedor > 0 ? $id_proveedor : null;

// Validaciones
$errores = [];
if ($codigo === '') $errores[] = "El código es obligatorio.";
if ($nombre === '') $errores[] = "El nombre es obligatorio.";
if ($precio_costo === '' || !is_numeric($precio_costo) || $precio_costo < 0) $errores[] = "Precio de compra inválido.";
if ($precio === '' || !is_numeric($precio) || $precio < 0) $errores[] = "Precio de venta inválido.";
if ($stock === '' || !ctype_digit($stock)) $errores[] = "Stock inválido.";

// Código repetido (excluyendo este mismo)
if (empty($errores)) {
    $stmt = $conexion->prepare("SELECT id FROM productos WHERE codigo = ? AND id != ? LIMIT 1");
    $stmt->bind_param("si", $codigo, $id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $errores[] = "El código '$codigo' ya está en uso por otro producto.";
    }
    $stmt->close();
}

if (!empty($errores)) {
    $_SESSION['producto_error'] = implode(' ', $errores);
    $_SESSION['producto_old'] = $_POST;
    header("Location: editar.php?id=$id");
    exit;
}

// -------- Manejo de foto --------
$nombre_archivo = $foto_actual;

// Si pidió quitar la foto
if (isset($_POST['quitar_foto']) && $_POST['quitar_foto'] === '1') {
    if ($foto_actual && file_exists(__DIR__ . '/../../assets/img/productos/' . $foto_actual)) {
        unlink(__DIR__ . '/../../assets/img/productos/' . $foto_actual);
    }
    $nombre_archivo = null;
}

// Si subió una nueva
if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
    $permitidos = ['image/jpeg', 'image/png', 'image/webp'];
    $tipo = mime_content_type($_FILES['foto']['tmp_name']);

    if (!in_array($tipo, $permitidos)) {
        $_SESSION['producto_error'] = "La foto debe ser JPG, PNG o WEBP.";
        $_SESSION['producto_old'] = $_POST;
        header("Location: editar.php?id=$id");
        exit;
    }
    if ($_FILES['foto']['size'] > 2 * 1024 * 1024) {
        $_SESSION['producto_error'] = "La foto no puede pesar más de 2MB.";
        $_SESSION['producto_old'] = $_POST;
        header("Location: editar.php?id=$id");
        exit;
    }

    $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
    $nuevo_nombre = 'prod_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . strtolower($ext);
    $carpeta = __DIR__ . '/../../assets/img/productos/';

    if (move_uploaded_file($_FILES['foto']['tmp_name'], $carpeta . $nuevo_nombre)) {
        // Borrar la anterior
        if ($foto_actual && file_exists($carpeta . $foto_actual)) {
            unlink($carpeta . $foto_actual);
        }
        $nombre_archivo = $nuevo_nombre;
    }
}

// -------- Actualizar --------
$stock_minimo = $stock_minimo === '' ? 0 : (int)$stock_minimo;

$sql = "UPDATE productos SET
        codigo = ?, nombre = ?, descripcion = ?, foto = ?,
        id_departamento = ?, id_categoria = ?, id_proveedor = ?,
        precio_costo = ?, precio = ?, stock = ?, stock_minimo = ?, activo = ?
        WHERE id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param(
    "ssssiiiiddiii",
    $codigo, $nombre, $descripcion, $nombre_archivo,
    $id_departamento, $id_categoria, $id_proveedor,
    $precio_costo, $precio, $stock, $stock_minimo, $activo,
    $id
);

if ($stmt->execute()) {
    $_SESSION['producto_mensaje'] = "Producto '$nombre' actualizado correctamente.";
    header("Location: index.php");
    exit;
} else {
    $_SESSION['producto_error'] = "Error al actualizar: " . $stmt->error;
    $_SESSION['producto_old'] = $_POST;
    header("Location: editar.php?id=$id");
    exit;
}
$stmt->close();