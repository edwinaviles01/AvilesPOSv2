<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: /AvilesPOSv2/index.php");
    exit;
}

require __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/permisos.php';
requiere_permiso('reportes_ver');

// ---------- Filtros ----------
$tab = $_GET['tab'] ?? 'resumen';
if (!in_array($tab, ['resumen', 'productos', 'usuarios', 'inventario'])) {
    $tab = 'resumen';
}

$fecha_desde = $_GET['desde'] ?? date('Y-m-01');
$fecha_hasta = $_GET['hasta'] ?? date('Y-m-d');

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_desde)) $fecha_desde = date('Y-m-01');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_hasta)) $fecha_hasta = date('Y-m-d');

// =============================================
// DATOS PARA EL TAB ACTIVO
// =============================================

// ------ Variables compartidas para resumen ------
$total_ventas = 0;
$monto_total = 0;
$ticket_promedio = 0;
$ventas_por_dia = [];
$ventas_por_metodo = [];

if ($tab === 'resumen') {
    // Ventas del rango
    $stmt = $conexion->prepare("
        SELECT COUNT(*) AS total_ventas, COALESCE(SUM(total), 0) AS monto_total
        FROM ventas
        WHERE estado = 'completada' AND DATE(fecha) BETWEEN ? AND ?
    ");
    $stmt->bind_param("ss", $fecha_desde, $fecha_hasta);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $total_ventas = (int)$res['total_ventas'];
    $monto_total = (float)$res['monto_total'];
    $ticket_promedio = $total_ventas > 0 ? $monto_total / $total_ventas : 0;

    // Ventas agrupadas por día
    $stmt = $conexion->prepare("
        SELECT DATE(fecha) AS dia, COUNT(*) AS cantidad, SUM(total) AS monto
        FROM ventas
        WHERE estado = 'completada' AND DATE(fecha) BETWEEN ? AND ?
        GROUP BY DATE(fecha)
        ORDER BY dia DESC
    ");
    $stmt->bind_param("ss", $fecha_desde, $fecha_hasta);
    $stmt->execute();
    $ventas_por_dia = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Ventas por método de pago
    $stmt = $conexion->prepare("
        SELECT metodo_pago, COUNT(*) AS cantidad, SUM(total) AS monto
        FROM ventas
        WHERE estado = 'completada' AND DATE(fecha) BETWEEN ? AND ?
        GROUP BY metodo_pago
    ");
    $stmt->bind_param("ss", $fecha_desde, $fecha_hasta);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    foreach ($res as $r) {
        $ventas_por_metodo[$r['metodo_pago']] = $r;
    }
}

// ------ Variables para productos ------
$top_productos = [];
$total_ganancia_estimada = 0;

if ($tab === 'productos') {
    $stmt = $conexion->prepare("
        SELECT p.id, p.codigo, p.nombre, p.precio_costo,
               SUM(vd.cantidad) AS qty,
               SUM(vd.subtotal + vd.iva) AS total_vendido,
               SUM((vd.subtotal + vd.iva) - (p.precio_costo * vd.cantidad)) AS ganancia
        FROM ventas_detalle vd
        INNER JOIN ventas v ON vd.id_venta = v.id
        INNER JOIN productos p ON vd.id_producto = p.id
        WHERE v.estado = 'completada' AND DATE(v.fecha) BETWEEN ? AND ?
        GROUP BY p.id
        ORDER BY qty DESC
        LIMIT 100
    ");
    $stmt->bind_param("ss", $fecha_desde, $fecha_hasta);
    $stmt->execute();
    $top_productos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($top_productos as $tp) {
        $total_ganancia_estimada += (float)$tp['ganancia'];
    }
}

// ------ Variables para usuarios ------
$rendimiento_usuarios = [];

if ($tab === 'usuarios') {
    $stmt = $conexion->prepare("
        SELECT u.id, u.nombre, u.rol,
               COUNT(v.id) AS cantidad_ventas,
               COALESCE(SUM(v.total), 0) AS monto_total
        FROM usuarios u
        LEFT JOIN ventas v ON u.id = v.id_usuario AND v.estado = 'completada' AND DATE(v.fecha) BETWEEN ? AND ?
        WHERE u.activo = 1
        GROUP BY u.id
        ORDER BY monto_total DESC
    ");
    $stmt->bind_param("ss", $fecha_desde, $fecha_hasta);
    $stmt->execute();
    $rendimiento_usuarios = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// ------ Variables para inventario ------
$inv_total_productos = 0;
$inv_valor_costo = 0;
$inv_valor_venta = 0;
$inv_ganancia_potencial = 0;
$inventario_detalle = [];

if ($tab === 'inventario') {
    $res = $conexion->query("
        SELECT COUNT(*) AS total,
               COALESCE(SUM(stock * precio_costo), 0) AS valor_costo,
               COALESCE(SUM(stock * precio), 0) AS valor_venta,
               COALESCE(SUM(stock * (precio - precio_costo)), 0) AS ganancia
        FROM productos
        WHERE activo = 1
    ")->fetch_assoc();

    $inv_total_productos = (int)$res['total'];
    $inv_valor_costo = (float)$res['valor_costo'];
    $inv_valor_venta = (float)$res['valor_venta'];
    $inv_ganancia_potencial = (float)$res['ganancia'];

    $inventario_detalle = $conexion->query("
        SELECT p.id, p.codigo, p.nombre, p.stock, p.precio_costo, p.precio,
               d.nombre AS departamento,
               (p.stock * p.precio_costo) AS valor_costo,
               (p.stock * p.precio) AS valor_venta
        FROM productos p
        LEFT JOIN departamentos d ON p.id_departamento = d.id
        WHERE p.activo = 1
        ORDER BY (p.stock * p.precio_costo) DESC
    ")->fetch_all(MYSQLI_ASSOC);
}

// Función helper para URL de tabs conservando fechas
function urlTab($tab_nombre, $desde, $hasta) {
    return '?tab=' . $tab_nombre . '&desde=' . urlencode($desde) . '&hasta=' . urlencode($hasta);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - AvilesPOS</title>
    <link rel="stylesheet" href="/AvilesPOSv2/assets/css/estilos.css">
</head>
<body class="dash-body">

    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="page-header">
            <div class="page-header-left">
                <h1>Reportes</h1>
                <p>Análisis de ventas e inventario</p>
            </div>
        </div>

        <!-- Filtro de fechas -->
        <form method="GET" class="rep-filtros">
            <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">

            <div class="rep-filtro">
                <label for="desde">Desde</label>
                <input type="date" name="desde" id="desde" value="<?= htmlspecialchars($fecha_desde) ?>">
            </div>

            <div class="rep-filtro">
                <label for="hasta">Hasta</label>
                <input type="date" name="hasta" id="hasta" value="<?= htmlspecialchars($fecha_hasta) ?>">
            </div>

            <div class="rep-filtro-acciones">
                <a href="?tab=<?= htmlspecialchars($tab) ?>" class="btn btn-secondary">Limpiar</a>
                <button type="submit" class="btn btn-primary">Aplicar</button>
            </div>
        </form>

        <!-- Tabs internas -->
        <div class="rep-tabs">
            <a href="<?= urlTab('resumen', $fecha_desde, $fecha_hasta) ?>" class="rep-tab <?= $tab === 'resumen' ? 'activo' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                    <line x1="18" y1="20" x2="18" y2="10"></line>
                    <line x1="12" y1="20" x2="12" y2="4"></line>
                    <line x1="6" y1="20" x2="6" y2="14"></line>
                </svg>
                Resumen
            </a>

            <a href="<?= urlTab('productos', $fecha_desde, $fecha_hasta) ?>" class="rep-tab <?= $tab === 'productos' ? 'activo' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                </svg>
                Productos
            </a>

            <a href="<?= urlTab('usuarios', $fecha_desde, $fecha_hasta) ?>" class="rep-tab <?= $tab === 'usuarios' ? 'activo' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                </svg>
                Usuarios
            </a>

            <a href="<?= urlTab('inventario', $fecha_desde, $fecha_hasta) ?>" class="rep-tab <?= $tab === 'inventario' ? 'activo' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                </svg>
                Inventario
            </a>
        </div>

        <!-- =========================================
             TAB: RESUMEN
             ========================================= -->
        <?php if ($tab === 'resumen'): ?>

            <div class="rep-kpis">
                <div class="rep-kpi">
                    <div class="rep-kpi-label">Total vendido</div>
                    <div class="rep-kpi-valor">C$ <?= number_format($monto_total, 2) ?></div>
                    <div class="rep-kpi-sub"><?= $total_ventas ?> venta<?= $total_ventas !== 1 ? 's' : '' ?> en el rango</div>
                </div>

                <div class="rep-kpi verde">
                    <div class="rep-kpi-label">Ticket promedio</div>
                    <div class="rep-kpi-valor">C$ <?= number_format($ticket_promedio, 2) ?></div>
                    <div class="rep-kpi-sub">Por venta</div>
                </div>

                <div class="rep-kpi morado">
                    <div class="rep-kpi-label">Días con ventas</div>
                    <div class="rep-kpi-valor"><?= count($ventas_por_dia) ?></div>
                    <div class="rep-kpi-sub">En el rango seleccionado</div>
                </div>

                <div class="rep-kpi amarillo">
                    <div class="rep-kpi-label">Método más usado</div>
                    <div class="rep-kpi-valor" style="font-size: 18px;">
                        <?php
                        $metodo_top = '—';
                        $cant_metodo = 0;
                        $labels_met = ['efectivo' => 'Efectivo', 'tarjeta' => 'Tarjeta', 'transferencia' => 'Transferencia', 'credito' => 'Crédito'];
                        foreach ($ventas_por_metodo as $key => $data) {
                            if ((int)$data['cantidad'] > $cant_metodo) {
                                $cant_metodo = (int)$data['cantidad'];
                                $metodo_top = $labels_met[$key] ?? $key;
                            }
                        }
                        echo $metodo_top;
                        ?>
                    </div>
                    <div class="rep-kpi-sub">
                        <?= $cant_metodo > 0 ? $cant_metodo . ' venta' . ($cant_metodo !== 1 ? 's' : '') : 'Sin ventas' ?>
                    </div>
                </div>
            </div>

            <div class="rep-grid-2col">

                <!-- Ventas por día -->
                <div class="rep-card">
                    <div class="rep-card-header">
                        Ventas por día
                        <span class="count"><?= count($ventas_por_dia) ?></span>
                    </div>
                    <div class="rep-card-body">
                        <?php if (count($ventas_por_dia) === 0): ?>
                            <div class="rep-vacio">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" width="40" height="40">
                                    <line x1="18" y1="20" x2="18" y2="10"></line>
                                    <line x1="12" y1="20" x2="12" y2="4"></line>
                                    <line x1="6" y1="20" x2="6" y2="14"></line>
                                </svg>
                                <div>Sin ventas en el rango</div>
                            </div>
                        <?php else: ?>
                            <table class="rep-table">
                                <thead>
                                    <tr>
                                        <th>Día</th>
                                        <th class="num">Ventas</th>
                                        <th class="num">Monto</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $max_dia = 0;
                                    foreach ($ventas_por_dia as $vd) {
                                        if ((float)$vd['monto'] > $max_dia) $max_dia = (float)$vd['monto'];
                                    }
                                    foreach ($ventas_por_dia as $vd):
                                        $porcentaje = $max_dia > 0 ? ((float)$vd['monto'] / $max_dia) * 100 : 0;
                                    ?>
                                        <tr>
                                            <td>
                                                <div style="font-weight:600;">
                                                    <?= date('d/m/Y', strtotime($vd['dia'])) ?>
                                                </div>
                                                <div style="font-size:10px; color:#888; margin-top:4px; max-width:120px;">
                                                    <div class="rep-barra">
                                                        <div class="rep-barra-fill verde" style="width:<?= $porcentaje ?>%;"></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="num"><?= (int)$vd['cantidad'] ?></td>
                                            <td class="num azul">C$ <?= number_format($vd['monto'], 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Métodos de pago -->
                <div class="rep-card">
                    <div class="rep-card-header">
                        Métodos de pago
                    </div>
                    <div class="rep-card-body">
                        <?php if (count($ventas_por_metodo) === 0): ?>
                            <div class="rep-vacio">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" width="40" height="40">
                                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                    <line x1="1" y1="10" x2="23" y2="10"></line>
                                </svg>
                                <div>Sin datos</div>
                            </div>
                        <?php else: ?>
                            <table class="rep-table">
                                <thead>
                                    <tr>
                                        <th>Método</th>
                                        <th class="num">Ventas</th>
                                        <th class="num">Monto</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $metodos_orden = ['efectivo', 'tarjeta', 'transferencia', 'credito'];
                                    foreach ($metodos_orden as $metodo):
                                        $data = $ventas_por_metodo[$metodo] ?? ['cantidad' => 0, 'monto' => 0];
                                        $es_cero = (int)$data['cantidad'] === 0;
                                    ?>
                                        <tr>
                                            <td style="font-weight:600;">
                                                <?= $labels_met[$metodo] ?>
                                            </td>
                                            <td class="num"><?= (int)$data['cantidad'] ?></td>
                                            <td class="num <?= $es_cero ? '' : 'verde' ?>">
                                                C$ <?= number_format($data['monto'], 2) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

        <?php endif; ?>

        <!-- =========================================
             TAB: PRODUCTOS
             ========================================= -->
        <?php if ($tab === 'productos'): ?>

            <div class="rep-kpis">
                <div class="rep-kpi">
                    <div class="rep-kpi-label">Productos vendidos</div>
                    <div class="rep-kpi-valor"><?= count($top_productos) ?></div>
                    <div class="rep-kpi-sub">Con ventas en el rango</div>
                </div>

                <div class="rep-kpi verde">
                    <div class="rep-kpi-label">Ganancia estimada</div>
                    <div class="rep-kpi-valor">C$ <?= number_format($total_ganancia_estimada, 2) ?></div>
                    <div class="rep-kpi-sub">(venta − costo actual)</div>
                </div>
            </div>

            <div class="rep-card">
                <div class="rep-card-header">
                    Productos más vendidos
                    <span class="count"><?= count($top_productos) ?></span>
                </div>
                <div class="rep-card-body">
                    <?php if (count($top_productos) === 0): ?>
                        <div class="rep-vacio">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" width="40" height="40">
                                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                            </svg>
                            <div>No se vendieron productos en este rango</div>
                        </div>
                    <?php else: ?>
                        <table class="rep-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Código</th>
                                    <th>Producto</th>
                                    <th class="num">Cant. vendida</th>
                                    <th class="num">Total vendido</th>
                                    <th class="num">Ganancia</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_productos as $i => $tp): ?>
                                    <tr>
                                        <td style="font-size:11px; color:#888; font-weight:600;">
                                            <?= $i + 1 ?>
                                        </td>
                                        <td style="font-size:11px; color:#888;">
                                            <?= htmlspecialchars($tp['codigo']) ?>
                                        </td>
                                        <td style="font-weight:600;">
                                            <?= htmlspecialchars($tp['nombre']) ?>
                                        </td>
                                        <td class="num" style="font-weight:600;"><?= (int)$tp['qty'] ?></td>
                                        <td class="num azul">C$ <?= number_format($tp['total_vendido'], 2) ?></td>
                                        <td class="num <?= (float)$tp['ganancia'] >= 0 ? 'verde' : 'rojo' ?>">
                                            C$ <?= number_format($tp['ganancia'], 2) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

        <?php endif; ?>

        <!-- =========================================
             TAB: USUARIOS
             ========================================= -->
        <?php if ($tab === 'usuarios'): ?>

            <div class="rep-card">
                <div class="rep-card-header">
                    Rendimiento por cajero
                    <span class="count"><?= count($rendimiento_usuarios) ?></span>
                </div>
                <div class="rep-card-body">
                    <?php if (count($rendimiento_usuarios) === 0): ?>
                        <div class="rep-vacio">No hay usuarios activos</div>
                    <?php else: ?>
                        <?php
                        $max_monto_usuario = 0;
                        foreach ($rendimiento_usuarios as $ru) {
                            if ((float)$ru['monto_total'] > $max_monto_usuario) $max_monto_usuario = (float)$ru['monto_total'];
                        }
                        ?>
                        <table class="rep-table">
                            <thead>
                                <tr>
                                    <th>Usuario</th>
                                    <th>Rol</th>
                                    <th class="num">Ventas</th>
                                    <th class="num">Total vendido</th>
                                    <th style="width: 30%;">Participación</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rendimiento_usuarios as $ru):
                                    $porcentaje = $max_monto_usuario > 0 ? ((float)$ru['monto_total'] / $max_monto_usuario) * 100 : 0;
                                    $roles_label = [
                                        'administrador' => 'Admin',
                                        'gerente' => 'Gerente',
                                        'supervisor' => 'Supervisor',
                                        'cajero' => 'Cajero'
                                    ];
                                ?>
                                    <tr>
                                        <td style="font-weight:600;">
                                            <?= htmlspecialchars($ru['nombre']) ?>
                                        </td>
                                        <td style="font-size:11px; color:#666;">
                                            <?= $roles_label[$ru['rol']] ?? $ru['rol'] ?>
                                        </td>
                                        <td class="num"><?= (int)$ru['cantidad_ventas'] ?></td>
                                        <td class="num azul">C$ <?= number_format($ru['monto_total'], 2) ?></td>
                                        <td>
                                            <div class="rep-barra">
                                                <div class="rep-barra-fill" style="width:<?= $porcentaje ?>%;"></div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

        <?php endif; ?>

        <!-- =========================================
             TAB: INVENTARIO
             ========================================= -->
        <?php if ($tab === 'inventario'): ?>

            <div class="rep-kpis">
                <div class="rep-kpi">
                    <div class="rep-kpi-label">Productos activos</div>
                    <div class="rep-kpi-valor"><?= $inv_total_productos ?></div>
                    <div class="rep-kpi-sub">En el catálogo</div>
                </div>

                <div class="rep-kpi amarillo">
                    <div class="rep-kpi-label">Valor al costo</div>
                    <div class="rep-kpi-valor">C$ <?= number_format($inv_valor_costo, 2) ?></div>
                    <div class="rep-kpi-sub">Lo que pagaste por el stock</div>
                </div>

                <div class="rep-kpi">
                    <div class="rep-kpi-label">Valor a la venta</div>
                    <div class="rep-kpi-valor">C$ <?= number_format($inv_valor_venta, 2) ?></div>
                    <div class="rep-kpi-sub">Si vendes todo al precio actual</div>
                </div>

                <div class="rep-kpi verde">
                    <div class="rep-kpi-label">Ganancia potencial</div>
                    <div class="rep-kpi-valor">C$ <?= number_format($inv_ganancia_potencial, 2) ?></div>
                    <div class="rep-kpi-sub">Diferencia si vendes todo</div>
                </div>
            </div>

            <div class="rep-card">
                <div class="rep-card-header">
                    Detalle de inventario
                    <span class="count"><?= count($inventario_detalle) ?></span>
                </div>
                <div class="rep-card-body">
                    <?php if (count($inventario_detalle) === 0): ?>
                        <div class="rep-vacio">No hay productos activos</div>
                    <?php else: ?>
                        <table class="rep-table">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Producto</th>
                                    <th>Departamento</th>
                                    <th class="num">Stock</th>
                                    <th class="num">Costo unit.</th>
                                    <th class="num">Valor costo</th>
                                    <th class="num">Valor venta</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($inventario_detalle as $inv): ?>
                                    <tr>
                                        <td style="font-size:11px; color:#888;">
                                            <?= htmlspecialchars($inv['codigo']) ?>
                                        </td>
                                        <td style="font-weight:600;">
                                            <?= htmlspecialchars($inv['nombre']) ?>
                                        </td>
                                        <td style="font-size:11px; color:#666;">
                                            <?= htmlspecialchars($inv['departamento'] ?? '—') ?>
                                        </td>
                                        <td class="num">
                                            <?php if ((int)$inv['stock'] === 0): ?>
                                                <span class="badge badge-danger">0</span>
                                            <?php else: ?>
                                                <?= (int)$inv['stock'] ?>
                                            <?php endif; ?>
                                        </td>
                                        <td class="num">C$ <?= number_format($inv['precio_costo'], 2) ?></td>
                                        <td class="num azul">C$ <?= number_format($inv['valor_costo'], 2) ?></td>
                                        <td class="num verde">C$ <?= number_format($inv['valor_venta'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

        <?php endif; ?>

    </main>

</body>
</html>