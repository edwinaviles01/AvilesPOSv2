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
$id_producto    = (int)($_POST['id_producto'] ?? 0);
$tipo           = trim($_POST['tipo'] ?? '');
$cantidad       = (int)($_POST['cantidad'] ?? 0);
$id_proveedor   = (int)($_POST['id_proveedor'] ?? 0);
$precio_costo_nuevo = trim($_POST['precio_costo_nuevo'] ?? '');
$precio_venta_nuevo = trim($_POST['precio_venta_nuevo'] ?? '');
$comentario     = trim($_POST['comentario'] ?? '');
$id_usuario     = (int)$_SESSION['usuario_id'];

// -------- Validaciones --------
$errores = [];

if ($id_producto <= 0) {
    $errores[] = "Debes seleccionar un producto.";
}

if (!in_array($tipo, ['entrada', 'salida'])) {
    $errores[] = "Tipo de movimiento inválido.";
}

if ($cantidad <= 0) {
    $errores[] = "La cantidad debe ser mayor a 0.";
}

if ($tipo === 'salida' && $comentario === '') {
    $errores[] = "El comentario es obligatorio para las salidas.";
}

if ($tipo === 'entrada') {
    if ($precio_costo_nuevo !== '' && (!is_numeric($precio_costo_nuevo) || $precio_costo_nuevo < 0)) {
        $errores[] = "El precio de compra nuevo es inválido.";
    }
    if ($precio_venta_nuevo !== '' && (!is_numeric($precio_venta_nuevo) || $precio_venta_nuevo < 0)) {
        $errores[] = "El precio de venta nuevo es inválido.";
    }
} else {
    $precio_costo_nuevo = '';
    $precio_venta_nuevo = '';
    $id_proveedor = 0;
}

// -------- Cargar producto --------
$stmt = $conexion->prepare("SELECT id, nombre, stock, precio_costo, precio FROM productos WHERE id = ? AND activo = 1 LIMIT 1");
$stmt->bind_param("i", $id_producto);
$stmt->execute();
$producto = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$producto) {
    $errores[] = "El producto no existe o está inactivo.";
} else {
    if ($tipo === 'salida' && $cantidad > (int)$producto['stock']) {
        $errores[] = "No hay suficiente stock. Disponible: {$producto['stock']} unidades.";
    }
}

if (!empty($errores)) {
    $_SESSION['inventario_error'] = implode(' ', $errores);
    $_SESSION['inventario_old'] = $_POST;
    header("Location: nuevo.php");
    exit;
}

// -------- Cálculos --------
$stock_anterior = (int)$producto['stock'];
$stock_nuevo = ($tipo === 'entrada')
    ? $stock_anterior + $cantidad
    : $stock_anterior - $cantidad;

$precio_costo_anterior = $producto['precio_costo'];
$precio_venta_anterior = $producto['precio'];

$precio_costo_final = null;
if ($tipo === 'entrada' && $precio_costo_nuevo !== '') {
    $precio_costo_final = (float)$precio_costo_nuevo;
}

$precio_venta_final = null;
if ($tipo === 'entrada' && $precio_venta_nuevo !== '') {
    $precio_venta_final = (float)$precio_venta_nuevo;
}

$comentario_mov = $comentario !== '' ? $comentario : null;
$id_proveedor_mov = ($tipo === 'entrada' && $id_proveedor > 0) ? $id_proveedor : null;

// -------- Insertar movimiento --------
$sql = "INSERT INTO movimientos_inventario 
        (id_producto, id_proveedor, tipo, cantidad, stock_anterior, stock_nuevo,
         precio_costo_anterior, precio_costo_nuevo,
         precio_venta_anterior, precio_venta_nuevo,
         comentario, id_usuario)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conexion->prepare($sql);
$stmt->bind_param(
    "iisiiiddddsi",
    $id_producto, $id_proveedor_mov, $tipo, $cantidad, $stock_anterior, $stock_nuevo,
    $precio_costo_anterior, $precio_costo_final,
    $precio_venta_anterior, $precio_venta_final,
    $comentario_mov, $id_usuario
);

if (!$stmt->execute()) {
    $_SESSION['inventario_error'] = "Error al registrar el movimiento: " . $stmt->error;
    $_SESSION['inventario_old'] = $_POST;
    header("Location: nuevo.php");
    exit;
}
$stmt->close();

// -------- Actualizar producto (stock y precios) --------
if ($tipo === 'entrada') {
    // Actualizamos stock y, si vienen, los precios
    if ($precio_costo_final !== null && $precio_venta_final !== null) {
        $sql_upd = "UPDATE productos SET stock = ?, precio_costo = ?, precio = ? WHERE id = ?";
        $stmt = $conexion->prepare($sql_upd);
        $stmt->bind_param("iddi", $stock_nuevo, $precio_costo_final, $precio_venta_final, $id_producto);
    } elseif ($precio_costo_final !== null) {
        $sql_upd = "UPDATE productos SET stock = ?, precio_costo = ? WHERE id = ?";
        $stmt = $conexion->prepare($sql_upd);
        $stmt->bind_param("idi", $stock_nuevo, $precio_costo_final, $id_producto);
    } elseif ($precio_venta_final !== null) {
        $sql_upd = "UPDATE productos SET stock = ?, precio = ? WHERE id = ?";
        $stmt = $conexion->prepare($sql_upd);
        $stmt->bind_param("idi", $stock_nuevo, $precio_venta_final, $id_producto);
    } else {
        $sql_upd = "UPDATE productos SET stock = ? WHERE id = ?";
        $stmt = $conexion->prepare($sql_upd);
        $stmt->bind_param("ii", $stock_nuevo, $id_producto);
    }
} else {
    // Salida: solo actualizamos stock
    $sql_upd = "UPDATE productos SET stock = ? WHERE id = ?";
    $stmt = $conexion->prepare($sql_upd);
    $stmt->bind_param("ii", $stock_nuevo, $id_producto);
}

if (!$stmt->execute()) {
    $_SESSION['inventario_error'] = "El movimiento se registró, pero hubo un error al actualizar el producto: " . $stmt->error;
    header("Location: index.php");
    exit;
}
$stmt->close();

// -------- Mensaje de éxito --------
$accion = $tipo === 'entrada' ? 'Entrada' : 'Salida';
$_SESSION['inventario_mensaje'] = "{$accion} de {$cantidad} unidad(es) de '{$producto['nombre']}' registrada correctamente.";
header("Location: index.php");
exit;