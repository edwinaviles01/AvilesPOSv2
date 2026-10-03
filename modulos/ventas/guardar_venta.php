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

require __DIR__ . '/../../config/db.php';

$id_usuario = (int)$_SESSION['usuario_id'];

// -------- Recibir JSON --------
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['ok' => false, 'error' => 'Datos inválidos']);
    exit;
}

$items           = $input['items'] ?? [];
$id_cliente      = (int)($input['id_cliente'] ?? 0);
$metodo_pago     = trim($input['metodo_pago'] ?? '');
$referencia      = trim($input['referencia'] ?? '');
$monto_cordobas  = (float)($input['monto_cordobas'] ?? 0);
$monto_usd       = (float)($input['monto_usd'] ?? 0);
$tipo_cambio     = (float)($input['tipo_cambio'] ?? 0);
$descuento_monto = (float)($input['descuento_monto'] ?? 0);
$descuento_porcentaje = isset($input['descuento_porcentaje']) && $input['descuento_porcentaje'] !== ''
    ? (float)$input['descuento_porcentaje'] : null;
$imprimir        = !empty($input['imprimir']);
$observaciones   = trim($input['observaciones'] ?? '');

// -------- Validaciones básicas --------
$errores = [];

if (empty($items)) $errores[] = "El carrito está vacío.";
if (!in_array($metodo_pago, ['efectivo', 'tarjeta', 'transferencia', 'credito'])) {
    $errores[] = "Método de pago inválido.";
}

// -------- Verificar turno abierto --------
$stmt = $conexion->prepare("
    SELECT id FROM turnos
    WHERE estado = 'abierto'
      AND (id_usuario_apertura = ? OR (compartido = 1 AND id_usuario_apertura != ?))
    ORDER BY fecha_apertura ASC
    LIMIT 1
");
$stmt->bind_param("ii", $id_usuario, $id_usuario);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$res) {
    echo json_encode(['ok' => false, 'error' => 'No tienes un turno abierto.']);
    exit;
}
$id_turno = (int)$res['id'];

// -------- Validar cada item --------
$items_validados = [];

foreach ($items as $item) {
    $id_prod = (int)($item['id_producto'] ?? 0);
    $cantidad = (int)($item['cantidad'] ?? 0);

    if ($id_prod <= 0 || $cantidad <= 0) {
        $errores[] = "Item inválido en el carrito.";
        continue;
    }

    $stmt = $conexion->prepare("
        SELECT id, nombre, precio, stock 
        FROM productos 
        WHERE id = ? AND activo = 1 
        LIMIT 1
    ");
    $stmt->bind_param("i", $id_prod);
    $stmt->execute();
    $prod = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$prod) {
        $errores[] = "Producto ID $id_prod no encontrado.";
        continue;
    }

    if ((int)$prod['stock'] < $cantidad) {
        $errores[] = "Stock insuficiente de '{$prod['nombre']}'. Disponible: {$prod['stock']}.";
        continue;
    }

    $precio_unit = (float)$prod['precio'];
    $subtotal_item = $precio_unit * $cantidad;

    $items_validados[] = [
        'id_producto' => $id_prod,
        'nombre' => $prod['nombre'],
        'cantidad' => $cantidad,
        'precio_unitario' => $precio_unit,
        'subtotal' => $subtotal_item,
        'stock_actual' => (int)$prod['stock']
    ];
}

if (!empty($errores)) {
    echo json_encode(['ok' => false, 'error' => implode(' ', $errores)]);
    exit;
}

// -------- Cálculos --------
$subtotal_bruto = 0;
foreach ($items_validados as $item) {
    $subtotal_bruto += $item['subtotal'];
}

if ($descuento_monto > $subtotal_bruto) $descuento_monto = $subtotal_bruto;
if ($descuento_monto < 0) $descuento_monto = 0;

$total = $subtotal_bruto - $descuento_monto;

// IVA
$iva_porcentaje = 15.00;
$res_iva = $conexion->query("SELECT valor FROM configuracion WHERE clave = 'iva_porcentaje' LIMIT 1")->fetch_assoc();
if ($res_iva) $iva_porcentaje = (float)$res_iva['valor'];

$subtotal_sin_iva = $total / (1 + $iva_porcentaje / 100);
$iva = $total - $subtotal_sin_iva;

// -------- Validar pago --------
$vuelto = 0;

if ($metodo_pago === 'efectivo') {
    $recibido = $monto_cordobas + ($monto_usd * $tipo_cambio);
    if ($recibido < $total - 0.01) {
        echo json_encode(['ok' => false, 'error' => 'El monto recibido es menor al total.']);
        exit;
    }
    $vuelto = $recibido - $total;
}

// -------- Validar crédito --------
if ($metodo_pago === 'credito') {
    if ($id_cliente <= 0) {
        echo json_encode(['ok' => false, 'error' => 'Debes seleccionar un cliente para vender al crédito.']);
        exit;
    }

    $stmt = $conexion->prepare("
        SELECT id, nombre, permite_credito, limite_credito, saldo_actual 
        FROM clientes 
        WHERE id = ? AND activo = 1 
        LIMIT 1
    ");
    $stmt->bind_param("i", $id_cliente);
    $stmt->execute();
    $cli = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$cli) {
        echo json_encode(['ok' => false, 'error' => 'El cliente no existe o está inactivo.']);
        exit;
    }
    if (!$cli['permite_credito']) {
        echo json_encode(['ok' => false, 'error' => 'Este cliente no tiene permitido el crédito.']);
        exit;
    }

    $nuevo_saldo = (float)$cli['saldo_actual'] + $total;
    if ((float)$cli['limite_credito'] > 0 && $nuevo_saldo > (float)$cli['limite_credito']) {
        echo json_encode([
            'ok' => false,
            'error' => "Supera el límite de crédito. Límite: C$ " . number_format($cli['limite_credito'], 2) . " — Saldo actual: C$ " . number_format($cli['saldo_actual'], 2) . " — Venta: C$ " . number_format($total, 2)
        ]);
        exit;
    }
}

// -------- INICIAR TRANSACCIÓN --------
$conexion->begin_transaction();

try {
    // 1. Generar número de factura
    $res = $conexion->query("SELECT COALESCE(MAX(numero_factura), 0) + 1 AS siguiente FROM ventas FOR UPDATE");
    $numero_factura = (int)$res->fetch_assoc()['siguiente'];

    // 2. Insertar venta
    $id_cliente_final = $id_cliente > 0 ? $id_cliente : null;
    $referencia_final = $referencia !== '' ? $referencia : null;

    $observaciones_final = $observaciones !== '' ? $observaciones : null;

    $sql_venta = "INSERT INTO ventas 
        (numero_factura, id_cliente, id_usuario, id_turno,
         subtotal, iva, descuento, descuento_porcentaje, total,
         metodo_pago, referencia_pago,
         monto_recibido_cordobas, monto_recibido_usd, tipo_cambio, vuelto,
         observaciones, estado)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'completada')";

    $stmt = $conexion->prepare($sql_venta);
    $stmt->bind_param(
        "iiiidddddssdddds",
        $numero_factura, $id_cliente_final, $id_usuario, $id_turno,
        $subtotal_sin_iva, $iva, $descuento_monto, $descuento_porcentaje, $total,
        $metodo_pago, $referencia_final,
        $monto_cordobas, $monto_usd, $tipo_cambio, $vuelto,
        $observaciones_final
    );
    $stmt->execute();
    $id_venta = $stmt->insert_id;
    $stmt->close();

    // 3. Insertar items y actualizar stock
    foreach ($items_validados as $item) {
        // Calcular IVA proporcional por item
        $proporcion = $item['subtotal'] / $subtotal_bruto;
        $subtotal_item_con_desc = $item['subtotal'] - ($descuento_monto * $proporcion);
        $subtotal_item_sin_iva = $subtotal_item_con_desc / (1 + $iva_porcentaje / 100);
        $iva_item = $subtotal_item_con_desc - $subtotal_item_sin_iva;

        $sql_det = "INSERT INTO ventas_detalle 
            (id_venta, id_producto, cantidad, precio_unitario, subtotal, iva)
            VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conexion->prepare($sql_det);
        $stmt->bind_param(
            "iiiddd",
            $id_venta, $item['id_producto'], $item['cantidad'],
            $item['precio_unitario'], $subtotal_item_sin_iva, $iva_item
        );
        $stmt->execute();
        $stmt->close();

        // Actualizar stock del producto (solo el stock, sin registrar movimiento)
        $stock_nuevo = $item['stock_actual'] - $item['cantidad'];
        $stmt = $conexion->prepare("UPDATE productos SET stock = ? WHERE id = ?");
        $stmt->bind_param("ii", $stock_nuevo, $item['id_producto']);
        $stmt->execute();
        $stmt->close();
    }

    // 4. Actualizar turno
    $stmt = $conexion->prepare("
        UPDATE turnos 
        SET total_ventas = total_ventas + ?,
            numero_ventas = numero_ventas + 1
        WHERE id = ?
    ");
    $stmt->bind_param("di", $total, $id_turno);
    $stmt->execute();
    $stmt->close();

    // 5. Si es crédito, actualizar saldo del cliente
    if ($metodo_pago === 'credito') {
        $stmt = $conexion->prepare("
            UPDATE clientes SET saldo_actual = saldo_actual + ? WHERE id = ?
        ");
        $stmt->bind_param("di", $total, $id_cliente);
        $stmt->execute();
        $stmt->close();
    }

    // COMMIT
    $conexion->commit();

    echo json_encode([
        'ok' => true,
        'id_venta' => $id_venta,
        'numero_factura' => $numero_factura,
        'total' => $total,
        'vuelto' => $vuelto,
        'imprimir' => $imprimir
    ]);
    exit;

} catch (Exception $e) {
    $conexion->rollback();
    echo json_encode(['ok' => false, 'error' => 'Error al guardar la venta: ' . $e->getMessage()]);
    exit;
}