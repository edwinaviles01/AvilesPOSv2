<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: /AvilesPOSv2/index.php");
    exit;
}

require __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/permisos.php';
requiere_permiso('dashboard_ver');

// ================= CONSULTAS =================

$id_usuario = (int)$_SESSION['usuario_id'];
$hoy = date('Y-m-d');
$ayer = date('Y-m-d', strtotime('-1 day'));

// ---------- 1. Productos ----------
$total_productos = (int)$conexion->query("SELECT COUNT(*) AS t FROM productos WHERE activo = 1")->fetch_assoc()['t'];

// Productos agotados
$productos_agotados = $conexion->query("
    SELECT id, codigo, nombre, precio 
    FROM productos 
    WHERE activo = 1 AND stock = 0 
    ORDER BY nombre ASC
")->fetch_all(MYSQLI_ASSOC);
$total_agotados = count($productos_agotados);

// Productos con stock bajo
$productos_bajo = $conexion->query("
    SELECT id, codigo, nombre, stock, stock_minimo 
    FROM productos 
    WHERE activo = 1 AND stock > 0 AND stock_minimo > 0 AND stock <= stock_minimo 
    ORDER BY (stock - stock_minimo) ASC
")->fetch_all(MYSQLI_ASSOC);
$total_bajo_stock = count($productos_bajo);

// Todos los productos activos (para modal KPI)
$todos_productos = $conexion->query("
    SELECT p.id, p.codigo, p.nombre, p.precio, p.stock,
           d.nombre AS departamento
    FROM productos p
    LEFT JOIN departamentos d ON p.id_departamento = d.id
    WHERE p.activo = 1
    ORDER BY p.nombre ASC
")->fetch_all(MYSQLI_ASSOC);

// ---------- 2. Ventas de HOY ----------
$stmt = $conexion->prepare("
    SELECT COUNT(*) AS total_ventas, COALESCE(SUM(total), 0) AS monto_total
    FROM ventas 
    WHERE DATE(fecha) = ? AND estado = 'completada'
");
$stmt->bind_param("s", $hoy);
$stmt->execute();
$res_ventas = $stmt->get_result()->fetch_assoc();
$stmt->close();
$ventas_hoy_count = (int)$res_ventas['total_ventas'];
$ventas_hoy_monto = (float)$res_ventas['monto_total'];

// Detalle de ventas por método de pago
$stmt = $conexion->prepare("
    SELECT metodo_pago, COUNT(*) AS cantidad, COALESCE(SUM(total), 0) AS monto
    FROM ventas 
    WHERE DATE(fecha) = ? AND estado = 'completada'
    GROUP BY metodo_pago
");
$stmt->bind_param("s", $hoy);
$stmt->execute();
$res_metodos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$ventas_por_metodo = [
    'efectivo' => ['cantidad' => 0, 'monto' => 0],
    'tarjeta' => ['cantidad' => 0, 'monto' => 0],
    'transferencia' => ['cantidad' => 0, 'monto' => 0],
    'credito' => ['cantidad' => 0, 'monto' => 0]
];

foreach ($res_metodos as $rm) {
    if (isset($ventas_por_metodo[$rm['metodo_pago']])) {
        $ventas_por_metodo[$rm['metodo_pago']]['cantidad'] = (int)$rm['cantidad'];
        $ventas_por_metodo[$rm['metodo_pago']]['monto'] = (float)$rm['monto'];
    }
}

// ---------- 3. Ventas de AYER (para comparativa) ----------
$stmt = $conexion->prepare("
    SELECT COALESCE(SUM(total), 0) AS monto_total
    FROM ventas 
    WHERE DATE(fecha) = ? AND estado = 'completada'
");
$stmt->bind_param("s", $ayer);
$stmt->execute();
$res_ayer = $stmt->get_result()->fetch_assoc();
$stmt->close();
$ventas_ayer_monto = (float)$res_ayer['monto_total'];

// Calcular porcentaje de cambio
if ($ventas_ayer_monto > 0) {
    $cambio_porcentaje = (($ventas_hoy_monto - $ventas_ayer_monto) / $ventas_ayer_monto) * 100;
} else {
    $cambio_porcentaje = $ventas_hoy_monto > 0 ? 100 : 0;
}

// ---------- 4. Turnos abiertos (cajas activas) ----------
$turnos_abiertos = $conexion->query("
    SELECT t.id, t.monto_inicial, t.compartido, t.fecha_apertura, t.numero_ventas, t.total_ventas,
           c.nombre AS caja_nombre,
           u.nombre AS usuario_nombre
    FROM turnos t
    INNER JOIN cajas c ON t.id_caja = c.id
    INNER JOIN usuarios u ON t.id_usuario_apertura = u.id
    WHERE t.estado = 'abierto'
    ORDER BY t.fecha_apertura ASC
")->fetch_all(MYSQLI_ASSOC);
$total_turnos_abiertos = count($turnos_abiertos);

// ---------- 5. Clientes con saldo pendiente ----------
$res_clientes_saldo = $conexion->query("
    SELECT COUNT(*) AS total, COALESCE(SUM(saldo_actual), 0) AS monto
    FROM clientes
    WHERE activo = 1 AND saldo_actual > 0
")->fetch_assoc();
$clientes_con_saldo = (int)$res_clientes_saldo['total'];
$monto_por_cobrar = (float)$res_clientes_saldo['monto'];

// Detalle de clientes con saldo (para modal)
$clientes_saldo_detalle = $conexion->query("
    SELECT id, nombre, cedula, telefono, saldo_actual, limite_credito
    FROM clientes
    WHERE activo = 1 AND saldo_actual > 0
    ORDER BY saldo_actual DESC
")->fetch_all(MYSQLI_ASSOC);

// ---------- 6. Detalle de ventas de hoy (para modal) ----------
$stmt = $conexion->prepare("
    SELECT v.id, v.numero_factura, v.total, v.metodo_pago, v.fecha,
           c.nombre AS cliente_nombre,
           u.nombre AS cajero_nombre
    FROM ventas v
    LEFT JOIN clientes c ON v.id_cliente = c.id
    INNER JOIN usuarios u ON v.id_usuario = u.id
    WHERE DATE(v.fecha) = ? AND v.estado = 'completada'
    ORDER BY v.fecha DESC
");
$stmt->bind_param("s", $hoy);
$stmt->execute();
$ventas_hoy_detalle = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ---------- 7. Actividad reciente (mezcla) ----------
$actividad = [];

// Últimas 5 ventas del día
$stmt = $conexion->prepare("
    SELECT v.id, v.numero_factura, v.total, v.fecha,
           u.nombre AS usuario, 
           COALESCE(c.nombre, 'Consumidor final') AS cliente
    FROM ventas v
    INNER JOIN usuarios u ON v.id_usuario = u.id
    LEFT JOIN clientes c ON v.id_cliente = c.id
    WHERE v.estado = 'completada'
    ORDER BY v.fecha DESC
    LIMIT 5
");
$stmt->execute();
$res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

foreach ($res as $r) {
    $actividad[] = [
        'tipo' => 'venta',
        'titulo' => 'Venta #' . (int)$r['numero_factura'] . ' — ' . $r['cliente'],
        'detalle' => 'Cajero: ' . $r['usuario'],
        'monto' => (float)$r['total'],
        'monto_mostrar' => '+' . number_format((float)$r['total'], 2),
        'fecha' => $r['fecha']
    ];
}

// Últimos 3 abonos
$stmt = $conexion->prepare("
    SELECT a.id, a.monto, a.fecha,
           c.nombre AS cliente,
           u.nombre AS usuario
    FROM abonos a
    INNER JOIN clientes c ON a.id_cliente = c.id
    INNER JOIN usuarios u ON a.id_usuario = u.id
    ORDER BY a.fecha DESC
    LIMIT 3
");
$stmt->execute();
$res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

foreach ($res as $r) {
    $actividad[] = [
        'tipo' => 'abono',
        'titulo' => 'Abono — ' . $r['cliente'],
        'detalle' => 'Registrado por: ' . $r['usuario'],
        'monto' => (float)$r['monto'],
        'monto_mostrar' => '+' . number_format((float)$r['monto'], 2),
        'fecha' => $r['fecha']
    ];
}

// Últimas 3 devoluciones
$stmt = $conexion->prepare("
    SELECT d.id, d.monto_total, d.fecha,
           v.numero_factura,
           c.nombre AS cliente
    FROM devoluciones d
    INNER JOIN ventas v ON d.id_venta = v.id
    LEFT JOIN clientes c ON v.id_cliente = c.id
    ORDER BY d.fecha DESC
    LIMIT 3
");
$stmt->execute();
$res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

foreach ($res as $r) {
    $actividad[] = [
        'tipo' => 'devolucion',
        'titulo' => 'Devolución — Factura #' . (int)$r['numero_factura'],
        'detalle' => $r['cliente'] ? 'Cliente: ' . $r['cliente'] : 'Consumidor final',
        'monto' => (float)$r['monto_total'],
        'monto_mostrar' => '−' . number_format((float)$r['monto_total'], 2),
        'fecha' => $r['fecha']
    ];
}

// Últimos 3 movimientos de caja
$stmt = $conexion->prepare("
    SELECT m.id, m.tipo, m.monto, m.motivo, m.fecha,
           u.nombre AS usuario
    FROM movimientos_caja m
    INNER JOIN usuarios u ON m.id_usuario = u.id
    ORDER BY m.fecha DESC
    LIMIT 3
");
$stmt->execute();
$res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

foreach ($res as $r) {
    $signo = $r['tipo'] === 'entrada' ? '+' : '−';
    $actividad[] = [
        'tipo' => 'movimiento',
        'titulo' => ucfirst($r['tipo']) . ' de caja — ' . $r['motivo'],
        'detalle' => 'Por: ' . $r['usuario'],
        'monto' => (float)$r['monto'],
        'monto_mostrar' => $signo . number_format((float)$r['monto'], 2),
        'fecha' => $r['fecha']
    ];
}

// Ordenar actividad por fecha descendente y tomar solo 10
usort($actividad, function($a, $b) {
    return strtotime($b['fecha']) - strtotime($a['fecha']);
});
$actividad = array_slice($actividad, 0, 10);

// ---------- Helper: tiempo relativo ----------
function tiempo_relativo($fecha) {
    $ahora = time();
    $t = strtotime($fecha);
    $diff = $ahora - $t;

    if ($diff < 60) return 'Hace menos de 1 min';
    if ($diff < 3600) return 'Hace ' . floor($diff / 60) . ' min';
    if ($diff < 86400) return 'Hace ' . floor($diff / 3600) . ' h';
    if ($diff < 172800) return 'Ayer';
    return date('d/m/Y', $t);
}

$dias_semana = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
$meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
$fecha_hoy_texto = $dias_semana[(int)date('w')] . ', ' . (int)date('j') . ' de ' . $meses[(int)date('n')] . ' ' . date('Y');
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio - AvilesPOS</title>
    <link rel="stylesheet" href="/AvilesPOSv2/assets/css/estilos.css">
</head>
<body class="dash-body">

    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="page-header">
            <div class="page-header-left">
                <h1>Inicio</h1>
                <p>Bienvenido, <strong><?= htmlspecialchars($_SESSION['usuario_nombre']) ?></strong> · <?= $fecha_hoy_texto ?></p>
            </div>
        </div>

        <!-- ==========================================
             SECCIÓN 1: AVISOS Y ALERTAS
             ========================================== -->
        <div class="dash-section">
            <div class="dash-section-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                </svg>
                Avisos y alertas
            </div>

            <!-- KPIs -->
            <div class="kpi-grid">
                <?php if (tiene_permiso('productos_ver')): ?>
                <button class="kpi-card" data-modal="modalProductos">
                    <div class="kpi-icon blue">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="24" height="24">
                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                        </svg>
                    </div>
                    <div class="kpi-info">
                        <div class="kpi-value"><?= $total_productos ?></div>
                        <div class="kpi-label">Productos activos</div>
                    </div>
                </button>
                <?php endif; ?>

                <?php if (tiene_permiso('ventas_vender') || tiene_permiso('ventas_historial_propio') || tiene_permiso('ventas_historial_completo')): ?>
                <button class="kpi-card" data-modal="modalVentasHoy">
                    <div class="kpi-icon green">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="24" height="24">
                            <circle cx="9" cy="21" r="1"></circle>
                            <circle cx="20" cy="21" r="1"></circle>
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                        </svg>
                    </div>
                    <div class="kpi-info">
                        <div class="kpi-value"><?= $ventas_hoy_count ?></div>
                        <div class="kpi-label">Ventas hoy</div>
                    </div>
                </button>
                <?php endif; ?>

                <?php if (tiene_permiso('productos_ver')): ?>
                <button class="kpi-card" data-modal="modalStockBajo">
                    <div class="kpi-icon yellow">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="24" height="24">
                            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                            <line x1="12" y1="9" x2="12" y2="13"></line>
                            <line x1="12" y1="17" x2="12.01" y2="17"></line>
                        </svg>
                    </div>
                    <div class="kpi-info">
                        <div class="kpi-value"><?= $total_bajo_stock ?></div>
                        <div class="kpi-label">Stock bajo</div>
                    </div>
                </button>

                <button class="kpi-card" data-modal="modalAgotados">
                    <div class="kpi-icon red">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="24" height="24">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                    </div>
                    <div class="kpi-info">
                        <div class="kpi-value"><?= $total_agotados ?></div>
                        <div class="kpi-label">Agotados</div>
                    </div>
                </button>
                <?php endif; ?>
            </div>

            <!-- Avisos -->
            <div class="avisos-list">
                <!-- Créditos pendientes -->
                <?php if (tiene_permiso('clientes_ver')): ?>
                <button type="button" class="aviso-item" data-modal="modalClientesSaldo" style="border:none; background:none; width:100%; text-align:left; cursor:pointer;">
                    <div class="aviso-icon <?= $clientes_con_saldo > 0 ? 'warning' : 'success' ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20">
                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                            <line x1="1" y1="10" x2="23" y2="10"></line>
                        </svg>
                    </div>
                    <div class="aviso-content">
                        <div class="aviso-title">Créditos pendientes por cobrar</div>
                        <div class="aviso-desc">
                            <?php if ($clientes_con_saldo > 0): ?>
                                <?= $clientes_con_saldo ?> cliente<?= $clientes_con_saldo !== 1 ? 's' : '' ?> con saldo pendiente
                            <?php else: ?>
                                Todo en orden
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="aviso-count <?= $monto_por_cobrar > 0 ? 'warning' : 'neutral' ?>">
                        <?= $clientes_con_saldo ?>
                    </div>
                    <svg class="aviso-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </button>
                <?php endif; ?>

                <!-- Turnos abiertos -->
                <?php if (tiene_permiso('turnos_abrir') || tiene_permiso('ventas_vender')): ?>
                <div class="aviso-item" style="cursor:default;">
                    <div class="aviso-icon <?= $total_turnos_abiertos > 0 ? 'info' : 'neutral' ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20">
                            <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                        </svg>
                    </div>
                    <div class="aviso-content">
                        <div class="aviso-title">Cajas abiertas actualmente</div>
                        <div class="aviso-desc"><?= $total_turnos_abiertos > 0 ? 'Cajeros con turno activo' : 'Ningún turno abierto' ?></div>
                    </div>
                    <div class="aviso-count <?= $total_turnos_abiertos > 0 ? 'info' : 'neutral' ?>"><?= $total_turnos_abiertos ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ==========================================
             SECCIÓN 2: CAJAS ACTIVAS + VENTAS DEL DÍA
             ========================================== -->
        <div class="dash-grid-2col">

            <!-- CAJAS ACTIVAS -->
            <?php if (tiene_permiso('turnos_abrir') || tiene_permiso('ventas_vender')): ?>
            <div class="dash-section" style="margin-bottom:0;">
                <div class="dash-section-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                        <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                        <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                    </svg>
                    Cajas activas
                </div>

                <?php if (count($turnos_abiertos) === 0): ?>
                    <div class="caja-vacia">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" width="48" height="48">
                            <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                        </svg>
                        <div>No hay cajas abiertas actualmente</div>
                    </div>
                <?php else: ?>
                    <?php foreach ($turnos_abiertos as $t): ?>
                        <div class="caja-activa-card">
                            <div class="caja-activa-info">
                                <div class="caja-activa-nombre">
                                    <?= htmlspecialchars($t['caja_nombre']) ?>
                                    <?php if ($t['compartido']): ?>
                                        <span class="badge badge-warning" style="font-size:9px;">Compartido</span>
                                    <?php endif; ?>
                                </div>
                                <div class="caja-activa-meta">
                                    <span class="user-caja"><?= htmlspecialchars($t['usuario_nombre']) ?></span>
                                    · Abierto <?= date('H:i', strtotime($t['fecha_apertura'])) ?>
                                </div>
                                <div class="caja-activa-monto">
                                    Inicial: C$ <?= number_format($t['monto_inicial'], 2) ?>
                                    · <?= (int)$t['numero_ventas'] ?> venta<?= (int)$t['numero_ventas'] !== 1 ? 's' : '' ?>
                                    · C$ <?= number_format($t['total_ventas'], 2) ?>
                                </div>
                            </div>
                            <?php if (tiene_permiso('ventas_vender')): ?>
                            <a href="/AvilesPOSv2/modulos/ventas/index.php" class="caja-activa-btn">
                                Ir a vender
                            </a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- VENTAS DEL DÍA -->
            <?php if (tiene_permiso('ventas_vender') || tiene_permiso('ventas_historial_completo')): ?>
            <div class="dash-section" style="margin-bottom:0;">
                <div class="dash-section-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                        <line x1="18" y1="20" x2="18" y2="10"></line>
                        <line x1="12" y1="20" x2="12" y2="4"></line>
                        <line x1="6" y1="20" x2="6" y2="14"></line>
                    </svg>
                    Ventas del día
                </div>

                <div class="ventas-dia-total">
                    <div>
                        <div class="ventas-dia-total-label">Total vendido</div>
                        <div class="ventas-dia-total-valor">C$ <?= number_format($ventas_hoy_monto, 2) ?></div>
                    </div>
                    <div class="ventas-dia-count">
                        <div class="ventas-dia-count-num"><?= $ventas_hoy_count ?></div>
                        <div class="ventas-dia-count-label">Ventas</div>
                    </div>
                </div>

                <div class="ventas-metodos-lista">
                    <div class="ventas-metodo-item">
                        <div class="ventas-metodo-icono efectivo">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                <line x1="1" y1="10" x2="23" y2="10"></line>
                            </svg>
                        </div>
                        <div class="ventas-metodo-info">
                            <div class="ventas-metodo-label">Efectivo</div>
                            <div class="ventas-metodo-valor">C$ <?= number_format($ventas_por_metodo['efectivo']['monto'], 2) ?></div>
                        </div>
                    </div>

                    <div class="ventas-metodo-item">
                        <div class="ventas-metodo-icono tarjeta">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                <line x1="1" y1="10" x2="23" y2="10"></line>
                            </svg>
                        </div>
                        <div class="ventas-metodo-info">
                            <div class="ventas-metodo-label">Tarjeta</div>
                            <div class="ventas-metodo-valor">C$ <?= number_format($ventas_por_metodo['tarjeta']['monto'], 2) ?></div>
                        </div>
                    </div>

                    <div class="ventas-metodo-item">
                        <div class="ventas-metodo-icono transferencia">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                                <polyline points="17 1 21 5 17 9"></polyline>
                                <path d="M3 11V9a4 4 0 0 1 4-4h14"></path>
                                <polyline points="7 23 3 19 7 15"></polyline>
                                <path d="M21 13v2a4 4 0 0 1-4 4H3"></path>
                            </svg>
                        </div>
                        <div class="ventas-metodo-info">
                            <div class="ventas-metodo-label">Transfer.</div>
                            <div class="ventas-metodo-valor">C$ <?= number_format($ventas_por_metodo['transferencia']['monto'], 2) ?></div>
                        </div>
                    </div>

                    <div class="ventas-metodo-item">
                        <div class="ventas-metodo-icono credito">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                            </svg>
                        </div>
                        <div class="ventas-metodo-info">
                            <div class="ventas-metodo-label">Crédito</div>
                            <div class="ventas-metodo-valor">C$ <?= number_format($ventas_por_metodo['credito']['monto'], 2) ?></div>
                        </div>
                    </div>
                </div>

                <?php if ($ventas_ayer_monto > 0 || $ventas_hoy_monto > 0): ?>
                <div class="ventas-comparativa">
                    <span>Comparado con ayer (C$ <?= number_format($ventas_ayer_monto, 2) ?>)</span>
                    <span class="comparativa-valor <?= $cambio_porcentaje > 0.5 ? 'arriba' : ($cambio_porcentaje < -0.5 ? 'abajo' : 'igual') ?>">
                        <?php if ($cambio_porcentaje > 0.5): ?>
                            +<?= number_format($cambio_porcentaje, 1) ?>%
                        <?php elseif ($cambio_porcentaje < -0.5): ?>
                            <?= number_format($cambio_porcentaje, 1) ?>%
                        <?php else: ?>
                            Sin cambio
                        <?php endif; ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

        </div>

        <!-- ==========================================
             SECCIÓN 3: ACTIVIDAD RECIENTE
             ========================================== -->
        <?php if (count($actividad) > 0): ?>
        <div class="dash-section">
            <div class="dash-section-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                </svg>
                Actividad reciente
            </div>

            <div class="actividad-lista">
                <?php foreach ($actividad as $a): ?>
                    <div class="actividad-item">
                        <div class="actividad-icono <?= htmlspecialchars($a['tipo']) ?>">
                            <?php if ($a['tipo'] === 'venta'): ?>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                                    <circle cx="9" cy="21" r="1"></circle>
                                    <circle cx="20" cy="21" r="1"></circle>
                                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                                </svg>
                            <?php elseif ($a['tipo'] === 'abono'): ?>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                    <line x1="1" y1="10" x2="23" y2="10"></line>
                                </svg>
                            <?php elseif ($a['tipo'] === 'devolucion'): ?>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                                    <polyline points="9 14 4 9 9 4"></polyline>
                                    <path d="M20 20v-7a4 4 0 0 0-4-4H4"></path>
                                </svg>
                            <?php else: ?>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                                    <polyline points="23 4 23 10 17 10"></polyline>
                                    <polyline points="1 20 1 14 7 14"></polyline>
                                    <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                                </svg>
                            <?php endif; ?>
                        </div>
                        <div class="actividad-info">
                            <div class="actividad-titulo"><?= htmlspecialchars($a['titulo']) ?></div>
                            <div class="actividad-detalle"><?= htmlspecialchars($a['detalle']) ?></div>
                        </div>
                        <div style="text-align:right; flex-shrink:0;">
                            <div class="actividad-monto <?= $a['tipo'] === 'abono' ? 'monto-abono' : ($a['tipo'] === 'devolucion' ? 'monto-devolucion' : ($a['tipo'] === 'movimiento' && strpos($a['monto_mostrar'], '−') === 0 ? 'monto-salida' : '')) ?>">
                                <?= $a['monto_mostrar'] ?>
                            </div>
                            <div class="actividad-tiempo"><?= tiempo_relativo($a['fecha']) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- ==========================================
             SECCIÓN 4: ACCESOS RÁPIDOS
             ========================================== -->
        <div class="dash-section">
            <div class="dash-section-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                    <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                </svg>
                Accesos rápidos
            </div>

            <div class="quick-actions">
                <?php if (tiene_permiso('productos_crear')): ?>
                <a href="/AvilesPOSv2/modulos/productos/crear.php" class="quick-action">
                    <div class="quick-action-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                    </div>
                    <span>Nuevo producto</span>
                </a>
                <?php endif; ?>

                <?php if (tiene_permiso('inventario_registrar')): ?>
                <a href="/AvilesPOSv2/modulos/inventario/nuevo.php" class="quick-action">
                    <div class="quick-action-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20">
                            <polyline points="23 4 23 10 17 10"></polyline>
                            <polyline points="1 20 1 14 7 14"></polyline>
                            <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                        </svg>
                    </div>
                    <span>Registrar movimiento</span>
                </a>
                <?php endif; ?>

                <?php if (tiene_permiso('ventas_vender')): ?>
                <a href="/AvilesPOSv2/modulos/ventas/index.php" class="quick-action">
                    <div class="quick-action-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20">
                            <circle cx="9" cy="21" r="1"></circle>
                            <circle cx="20" cy="21" r="1"></circle>
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                        </svg>
                    </div>
                    <span>Ir a vender</span>
                </a>
                <?php endif; ?>

                <?php if (tiene_permiso('clientes_crear')): ?>
                <a href="/AvilesPOSv2/modulos/clientes/crear.php" class="quick-action">
                    <div class="quick-action-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="8.5" cy="7" r="4"></circle>
                            <line x1="20" y1="8" x2="20" y2="14"></line>
                            <line x1="23" y1="11" x2="17" y2="11"></line>
                        </svg>
                    </div>
                    <span>Nuevo cliente</span>
                </a>
                <?php endif; ?>

                <?php if (tiene_permiso('ventas_historial_propio') || tiene_permiso('ventas_historial_completo')): ?>
                <a href="/AvilesPOSv2/modulos/ventas/historial/index.php" class="quick-action">
                    <div class="quick-action-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                        </svg>
                    </div>
                    <span>Historial de ventas</span>
                </a>
                <?php endif; ?>
            </div>
        </div>

    </main>

    <!-- ==========================================
         MODALES DE DETALLE
         ========================================== -->

    <!-- Modal: Productos activos -->
    <?php if (tiene_permiso('productos_ver')): ?>
    <div class="modal-overlay" id="modalProductos">
        <div class="modal modal-lg">
            <div class="modal-header">
                <h3>Productos activos (<?= $total_productos ?>)</h3>
                <button type="button" class="modal-close" data-close>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <div class="modal-body" style="max-height:400px; overflow-y:auto; padding:0;">
                <?php if (count($todos_productos) === 0): ?>
                    <p style="text-align:center; color:#888; padding:40px 20px;">No hay productos registrados</p>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Nombre</th>
                                <th>Departamento</th>
                                <th>Precio</th>
                                <th>Stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($todos_productos as $p): ?>
                                <tr>
                                    <td style="font-size:12px; color:#888;"><?= htmlspecialchars($p['codigo']) ?></td>
                                    <td><?= htmlspecialchars($p['nombre']) ?></td>
                                    <td style="font-size:12px; color:#888;"><?= htmlspecialchars($p['departamento'] ?? '—') ?></td>
                                    <td style="font-weight:600; color:#0e5dac;">C$ <?= number_format($p['precio'], 2) ?></td>
                                    <td>
                                        <?php if ((int)$p['stock'] === 0): ?>
                                            <span class="badge badge-danger"><?= (int)$p['stock'] ?></span>
                                        <?php else: ?>
                                            <?= (int)$p['stock'] ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close>Cerrar</button>
                <a href="/AvilesPOSv2/modulos/productos/index.php" class="btn btn-primary">Ir a Productos</a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Modal: Ventas de hoy -->
    <?php if (tiene_permiso('ventas_vender') || tiene_permiso('ventas_historial_completo')): ?>
    <div class="modal-overlay" id="modalVentasHoy">
        <div class="modal modal-lg">
            <div class="modal-header">
                <h3>Ventas de hoy (<?= $ventas_hoy_count ?>)</h3>
                <button type="button" class="modal-close" data-close>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <div class="modal-body" style="max-height:400px; overflow-y:auto; padding:0;">
                <div style="padding:14px 22px; border-bottom:1px solid #eef1f4; font-size:13px; color:#666;">
                    Total vendido hoy: <strong style="color:#0e5dac; font-size:15px;">C$ <?= number_format($ventas_hoy_monto, 2) ?></strong>
                </div>
                <?php if (count($ventas_hoy_detalle) === 0): ?>
                    <p style="text-align:center; color:#888; padding:40px 20px;">Sin ventas registradas hoy</p>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Hora</th>
                                <th>Cliente</th>
                                <th>Cajero</th>
                                <th>Método</th>
                                <th style="text-align:right;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ventas_hoy_detalle as $v): ?>
                                <tr>
                                    <td style="font-size:12px;">#<?= (int)$v['numero_factura'] ?></td>
                                    <td style="font-size:12px; color:#888;"><?= date('H:i', strtotime($v['fecha'])) ?></td>
                                    <td><?= htmlspecialchars($v['cliente_nombre'] ?? 'Consumidor final') ?></td>
                                    <td style="font-size:12px; color:#888;"><?= htmlspecialchars($v['cajero_nombre']) ?></td>
                                    <td style="font-size:12px;">
                                        <?php
                                        $m_label = [
                                            'efectivo' => 'Efectivo',
                                            'tarjeta' => 'Tarjeta',
                                            'transferencia' => 'Transf.',
                                            'credito' => 'Crédito'
                                        ];
                                        ?>
                                        <span class="badge" style="background:#e6f0fb; color:#0e5dac;">
                                            <?= $m_label[$v['metodo_pago']] ?? $v['metodo_pago'] ?>
                                        </span>
                                    </td>
                                    <td style="text-align:right; font-weight:600; color:#0e5dac;">C$ <?= number_format($v['total'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close>Cerrar</button>
                <a href="/AvilesPOSv2/modulos/ventas/historial/index.php" class="btn btn-primary">Ver historial</a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Modal: Stock bajo -->
    <?php if (tiene_permiso('productos_ver')): ?>
    <div class="modal-overlay" id="modalStockBajo">
        <div class="modal modal-lg">
            <div class="modal-header">
                <h3>Productos con stock bajo (<?= $total_bajo_stock ?>)</h3>
                <button type="button" class="modal-close" data-close>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <div class="modal-body" style="max-height:400px; overflow-y:auto; padding:0;">
                <?php if (count($productos_bajo) === 0): ?>
                    <p style="text-align:center; color:#888; padding:40px 20px;">No hay productos con stock bajo</p>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Nombre</th>
                                <th>Stock actual</th>
                                <th>Stock mínimo</th>
                                <th style="text-align:right;">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($productos_bajo as $p): ?>
                                <tr>
                                    <td style="font-size:12px; color:#888;"><?= htmlspecialchars($p['codigo']) ?></td>
                                    <td><?= htmlspecialchars($p['nombre']) ?></td>
                                    <td><span class="badge badge-warning"><?= (int)$p['stock'] ?></span></td>
                                    <td><?= (int)$p['stock_minimo'] ?></td>
                                    <td style="text-align:right;">
                                        <?php if (tiene_permiso('inventario_registrar')): ?>
                                            <a href="/AvilesPOSv2/modulos/inventario/nuevo.php" class="btn btn-secondary btn-sm">Reabastecer</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close>Cerrar</button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Modal: Agotados -->
    <?php if (tiene_permiso('productos_ver')): ?>
    <div class="modal-overlay" id="modalAgotados">
        <div class="modal modal-lg">
            <div class="modal-header">
                <h3>Productos agotados (<?= $total_agotados ?>)</h3>
                <button type="button" class="modal-close" data-close>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <div class="modal-body" style="max-height:400px; overflow-y:auto; padding:0;">
                <?php if (count($productos_agotados) === 0): ?>
                    <p style="text-align:center; color:#888; padding:40px 20px;">No hay productos agotados. Todo en orden.</p>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Nombre</th>
                                <th>Precio</th>
                                <th style="text-align:right;">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($productos_agotados as $p): ?>
                                <tr>
                                    <td style="font-size:12px; color:#888;"><?= htmlspecialchars($p['codigo']) ?></td>
                                    <td><?= htmlspecialchars($p['nombre']) ?></td>
                                    <td style="font-weight:600; color:#0e5dac;">C$ <?= number_format($p['precio'], 2) ?></td>
                                    <td style="text-align:right;">
                                        <?php if (tiene_permiso('inventario_registrar')): ?>
                                            <a href="/AvilesPOSv2/modulos/inventario/nuevo.php" class="btn btn-secondary btn-sm">Reabastecer</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close>Cerrar</button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Modal: Clientes con saldo -->
    <?php if (tiene_permiso('clientes_ver')): ?>
    <div class="modal-overlay" id="modalClientesSaldo">
        <div class="modal modal-lg">
            <div class="modal-header">
                <h3>Clientes con saldo pendiente (<?= $clientes_con_saldo ?>)</h3>
                <button type="button" class="modal-close" data-close>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <div class="modal-body" style="max-height:400px; overflow-y:auto; padding:0;">
                <?php if ($monto_por_cobrar > 0): ?>
                    <div style="padding:14px 22px; border-bottom:1px solid #eef1f4; font-size:13px; color:#666;">
                        Total por cobrar: <strong style="color:#a05a00; font-size:15px;">C$ <?= number_format($monto_por_cobrar, 2) ?></strong>
                    </div>
                <?php endif; ?>

                <?php if (count($clientes_saldo_detalle) === 0): ?>
                    <p style="text-align:center; color:#888; padding:40px 20px;">No hay clientes con saldo pendiente</p>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>Teléfono</th>
                                <th>Límite</th>
                                <th style="text-align:right;">Saldo actual</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clientes_saldo_detalle as $c): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($c['nombre']) ?></strong>
                                        <?php if ($c['cedula']): ?>
                                            <span style="display:block; font-size:11px; color:#888;"><?= htmlspecialchars($c['cedula']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-size:13px; color:#666;"><?= htmlspecialchars($c['telefono'] ?: '—') ?></td>
                                    <td style="font-size:13px; color:#666;">C$ <?= number_format($c['limite_credito'], 2) ?></td>
                                    <td style="text-align:right; font-weight:700; color:#b00020;">
                                        C$ <?= number_format($c['saldo_actual'], 2) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close>Cerrar</button>
                <a href="/AvilesPOSv2/modulos/clientes/index.php" class="btn btn-primary">Ir a Clientes</a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-modal]').forEach(el => {
                el.addEventListener('click', function () {
                    const modal = document.getElementById(this.dataset.modal);
                    if (modal) modal.classList.add('visible');
                });
            });

            document.querySelectorAll('.modal-overlay').forEach(modal => {
                modal.querySelectorAll('[data-close]').forEach(btn => {
                    btn.addEventListener('click', () => modal.classList.remove('visible'));
                });
            });

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    document.querySelectorAll('.modal-overlay.visible').forEach(m => m.classList.remove('visible'));
                }
            });
        });
    </script>

</body>
</html>