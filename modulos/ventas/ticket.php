<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: /AvilesPOSv2/index.php");
    exit;
}

require __DIR__ . '/../../config/db.php';

$id_venta = (int)($_GET['id'] ?? 0);
$modo_print = isset($_GET['print']) && $_GET['print'] == '1';

if ($id_venta <= 0) {
    header("Location: index.php");
    exit;
}

// Cargar la venta
$stmt = $conexion->prepare("
    SELECT v.*, 
           c.nombre AS cliente_nombre, c.cedula AS cliente_cedula,
           u.nombre AS cajero_nombre,
           t.id AS turno_id,
           cj.nombre AS caja_nombre
    FROM ventas v
    LEFT JOIN clientes c ON v.id_cliente = c.id
    INNER JOIN usuarios u ON v.id_usuario = u.id
    INNER JOIN turnos t ON v.id_turno = t.id
    INNER JOIN cajas cj ON t.id_caja = cj.id
    WHERE v.id = ?
    LIMIT 1
");
$stmt->bind_param("i", $id_venta);
$stmt->execute();
$venta = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$venta) {
    header("Location: index.php");
    exit;
}

// Cargar items
$stmt = $conexion->prepare("
    SELECT vd.*, p.nombre AS producto_nombre, p.codigo AS producto_codigo
    FROM ventas_detalle vd
    INNER JOIN productos p ON vd.id_producto = p.id
    WHERE vd.id_venta = ?
");
$stmt->bind_param("i", $id_venta);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Cargar configuración
$config = [];
$res = $conexion->query("SELECT clave, valor FROM configuracion");
while ($row = $res->fetch_assoc()) {
    $config[$row['clave']] = $row['valor'];
}

$empresa_nombre    = $config['empresa_nombre'] ?? 'Mi Negocio';
$empresa_direccion = $config['empresa_direccion'] ?? '';
$empresa_telefono  = $config['empresa_telefono'] ?? '';
$empresa_ruc       = $config['empresa_ruc'] ?? '';
$empresa_email     = $config['empresa_email'] ?? '';
$empresa_logo      = $config['empresa_logo'] ?? '';
$moneda = $config['moneda'] ?? 'C$';

// Determinar el logo a usar
$ruta_logo = '/AvilesPOSv2/assets/img/logo_aviles.png'; // default
if ($empresa_logo !== '' && file_exists(__DIR__ . '/../../assets/img/logos/' . $empresa_logo)) {
    $ruta_logo = '/AvilesPOSv2/assets/img/logos/' . $empresa_logo;
}

// Etiquetas de método de pago
$metodos_label = [
    'efectivo' => 'Efectivo',
    'tarjeta' => 'Tarjeta',
    'transferencia' => 'Transferencia',
    'credito' => 'Crédito'
];
$metodo_label = $metodos_label[$venta['metodo_pago']] ?? $venta['metodo_pago'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket #<?= (int)$venta['numero_factura'] ?> - AvilesPOS</title>
    <link rel="stylesheet" href="/AvilesPOSv2/assets/css/estilos.css">
</head>
<body class="ticket-body">

    <?php if (!$modo_print): ?>
    <!-- Acciones (no se imprimen) -->
    <div class="ticket-acciones">
        <button type="button" class="btn btn-primary" onclick="window.print()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                <polyline points="6 9 6 2 18 2 18 9"></polyline>
                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                <rect x="6" y="14" width="12" height="8"></rect>
            </svg>
            Imprimir
        </button>
        <a href="/AvilesPOSv2/modulos/ventas/index.php" class="btn btn-secondary">
            Nueva venta
        </a>
    </div>
    <?php endif; ?>

    <!-- Ticket -->
    <div class="ticket">

        <!-- Encabezado: datos de la empresa -->
        <div class="ticket-header">
        <img src="<?= htmlspecialchars($ruta_logo) ?>" alt="<?= htmlspecialchars($empresa_nombre) ?>" class="ticket-logo">
            <div class="ticket-negocio"><?= htmlspecialchars($empresa_nombre) ?></div>
            <div class="ticket-info-negocio">
                <?php if ($empresa_direccion): ?>
                    <?= htmlspecialchars($empresa_direccion) ?><br>
                <?php endif; ?>
                <?php if ($empresa_telefono): ?>
                    Tel: <?= htmlspecialchars($empresa_telefono) ?><br>
                <?php endif; ?>
                <?php if ($empresa_ruc): ?>
                    RUC: <?= htmlspecialchars($empresa_ruc) ?><br>
                <?php endif; ?>
                <?php if ($empresa_email): ?>
                    <?= htmlspecialchars($empresa_email) ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="ticket-titulo-doc">
            TICKET DE VENTA
        </div>

        <!-- Meta -->
        <div class="ticket-meta">
            <div class="ticket-meta-linea">
                <span>Ticket #:</span>
                <span><?= (int)$venta['numero_factura'] ?></span>
            </div>
            <div class="ticket-meta-linea">
                <span>Fecha:</span>
                <span><?= date('d/m/Y H:i', strtotime($venta['fecha'])) ?></span>
            </div>
            <div class="ticket-meta-linea">
                <span>Cajero:</span>
                <span><?= htmlspecialchars($venta['cajero_nombre']) ?></span>
            </div>
            <div class="ticket-meta-linea">
                <span>Caja:</span>
                <span><?= htmlspecialchars($venta['caja_nombre']) ?></span>
            </div>
            <div class="ticket-meta-linea">
                <span>Cliente:</span>
                <span><?= htmlspecialchars($venta['cliente_nombre'] ?? 'Consumidor final') ?></span>
            </div>
            <?php if (!empty($venta['cliente_cedula'])): ?>
                <div class="ticket-meta-linea">
                    <span>Cédula:</span>
                    <span><?= htmlspecialchars($venta['cliente_cedula']) ?></span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Items -->
        <div class="ticket-items">
            <?php foreach ($items as $item): ?>
                <div class="ticket-item">
                    <div class="ticket-item-nombre"><?= htmlspecialchars($item['producto_nombre']) ?></div>
                    <div class="ticket-item-linea">
                        <span class="ticket-item-detalle">
                            <?= (int)$item['cantidad'] ?> x <?= $moneda ?> <?= number_format($item['precio_unitario'], 2) ?>
                        </span>
                        <span><?= $moneda ?> <?= number_format($item['subtotal'] + $item['iva'], 2) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Totales -->
        <div class="ticket-totales">
            <div class="ticket-total-linea">
                <span>Subtotal:</span>
                <span><?= $moneda ?> <?= number_format($venta['subtotal'], 2) ?></span>
            </div>
            <div class="ticket-total-linea">
                <span>IVA incluido:</span>
                <span><?= $moneda ?> <?= number_format($venta['iva'], 2) ?></span>
            </div>
            <?php if ((float)$venta['descuento'] > 0): ?>
                <div class="ticket-total-linea descuento">
                    <span>Descuento:</span>
                    <span>− <?= $moneda ?> <?= number_format($venta['descuento'], 2) ?></span>
                </div>
            <?php endif; ?>
            <div class="ticket-total-linea gran-total">
                <span>TOTAL:</span>
                <span><?= $moneda ?> <?= number_format($venta['total'], 2) ?></span>
            </div>
        </div>

        <!-- Pago -->
        <div class="ticket-pago">
            <div class="ticket-pago-linea">
                <span>Método de pago:</span>
                <span><?= htmlspecialchars($metodo_label) ?></span>
            </div>

            <?php if ($venta['metodo_pago'] === 'efectivo'): ?>
                <?php if ((float)$venta['monto_recibido_usd'] > 0): ?>
                    <div class="ticket-pago-linea">
                        <span>Recibido USD:</span>
                        <span>$ <?= number_format($venta['monto_recibido_usd'], 2) ?></span>
                    </div>
                    <div class="ticket-pago-linea">
                        <span>Tipo de cambio:</span>
                        <span><?= $moneda ?> <?= number_format($venta['tipo_cambio'], 2) ?></span>
                    </div>
                <?php endif; ?>
                <?php if ((float)$venta['monto_recibido_cordobas'] > 0): ?>
                    <div class="ticket-pago-linea">
                        <span>Recibido:</span>
                        <span><?= $moneda ?> <?= number_format($venta['monto_recibido_cordobas'], 2) ?></span>
                    </div>
                <?php endif; ?>
                <div class="ticket-pago-linea destacado">
                    <span>Cambio:</span>
                    <span><?= $moneda ?> <?= number_format($venta['vuelto'], 2) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($venta['referencia_pago'])): ?>
                <div class="ticket-pago-linea">
                    <span>Referencia:</span>
                    <span><?= htmlspecialchars($venta['referencia_pago']) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($venta['metodo_pago'] === 'credito'): ?>
                <div class="ticket-pago-linea" style="margin-top:6px; font-size:11px;">
                    <span>Esta venta fue cargada a su cuenta.</span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pie -->
        <div class="ticket-pie">
            <div class="ticket-gracias">¡GRACIAS POR SU COMPRA!</div>
            <div>Conserve este ticket</div>
            <div style="margin-top:8px; font-size:10px;">
                Sistema de Punto de Venta
            </div>
        </div>

    </div>

    <?php if ($modo_print): ?>
    <script>
        window.addEventListener('load', function () {
            setTimeout(function () {
                window.print();
            }, 200);
        });
        window.addEventListener('afterprint', function () {
            window.close();
        });
    </script>
    <?php endif; ?>

</body>
</html>