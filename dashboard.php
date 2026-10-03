<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: /AvilesPOSv2/index.php");
    exit;
}

require __DIR__ . '/config/db.php';

// ================= CONSULTAS =================

// 1. Productos totales
$total_productos = (int)$conexion->query("SELECT COUNT(*) AS t FROM productos WHERE activo = 1")->fetch_assoc()['t'];
$total_inactivos = (int)$conexion->query("SELECT COUNT(*) AS t FROM productos WHERE activo = 0")->fetch_assoc()['t'];

// 2. Movimientos de hoy
$hoy = date('Y-m-d');
$stmt = $conexion->prepare("SELECT 
    SUM(CASE WHEN tipo = 'entrada' THEN 1 ELSE 0 END) AS entradas,
    SUM(CASE WHEN tipo = 'salida' THEN 1 ELSE 0 END) AS salidas
    FROM movimientos_inventario WHERE DATE(fecha) = ?");
$stmt->bind_param("s", $hoy);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$stmt->close();
$mov_entradas = (int)($res['entradas'] ?? 0);
$mov_salidas = (int)($res['salidas'] ?? 0);
$mov_hoy = $mov_entradas + $mov_salidas;

// 2b. Ventas de hoy
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

// 2c. Detalle de ventas de hoy (para el modal)
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

// 3. Productos agotados (lista completa)
$productos_agotados = $conexion->query("
    SELECT id, codigo, nombre, precio 
    FROM productos 
    WHERE activo = 1 AND stock = 0 
    ORDER BY nombre ASC
")->fetch_all(MYSQLI_ASSOC);
$total_agotados = count($productos_agotados);

// 4. Productos con stock bajo (lista completa)
$productos_bajo = $conexion->query("
    SELECT id, codigo, nombre, stock, stock_minimo 
    FROM productos 
    WHERE activo = 1 AND stock > 0 AND stock_minimo > 0 AND stock <= stock_minimo 
    ORDER BY (stock - stock_minimo) ASC
")->fetch_all(MYSQLI_ASSOC);
$total_bajo_stock = count($productos_bajo);

// 5. Todos los productos activos (para el modal del KPI Productos)
$todos_productos = $conexion->query("
    SELECT p.id, p.codigo, p.nombre, p.precio, p.stock,
           d.nombre AS departamento
    FROM productos p
    LEFT JOIN departamentos d ON p.id_departamento = d.id
    WHERE p.activo = 1
    ORDER BY p.nombre ASC
")->fetch_all(MYSQLI_ASSOC);

// 6. Movimientos de hoy (detalle, para el modal)
$stmt = $conexion->prepare("
    SELECT m.tipo, m.cantidad, m.stock_anterior, m.stock_nuevo, m.fecha,
           p.nombre AS producto, p.codigo AS codigo_producto,
           u.nombre AS usuario
    FROM movimientos_inventario m
    INNER JOIN productos p ON m.id_producto = p.id
    INNER JOIN usuarios u ON m.id_usuario = u.id
    WHERE DATE(m.fecha) = ?
    ORDER BY m.fecha DESC
");
$stmt->bind_param("s", $hoy);
$stmt->execute();
$movimientos_hoy = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
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
                <p>Bienvenido al sistema de punto de venta</p>
            </div>
        </div>

        <div class="dash-section">
            <div class="dash-section-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                </svg>
                Avisos y alertas
         </div>

        <!-- ============ KPI CARDS ============ -->
        <div class="dash-section">
            <div class="kpi-grid">
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

                <button class="kpi-card" data-modal="modalMovimientos">
                    <div class="kpi-icon green">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="24" height="24">
                            <polyline points="23 4 23 10 17 10"></polyline>
                            <polyline points="1 20 1 14 7 14"></polyline>
                            <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                        </svg>
                    </div>
                    <div class="kpi-info">
                        <div class="kpi-value"><?= $mov_hoy ?></div>
                        <div class="kpi-label">Mov. hoy</div>
                    </div>
                </button>

                <button class="kpi-card" data-modal="modalVentasHoy">
                    <div class="kpi-icon blue">
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
            </div>
        </div>

        <!-- ============ AVISOS (solo pendientes) ============ -->
            <div class="avisos-list">
                <div class="aviso-item" style="cursor:default;">
                    <div class="aviso-icon neutral">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20">
                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                            <line x1="1" y1="10" x2="23" y2="10"></line>
                        </svg>
                    </div>
                    <div class="aviso-content">
                        <div class="aviso-title">Ventas al crédito vencidas</div>
                        <div class="aviso-desc">Se activará al construir el módulo de Ventas</div>
                    </div>
                    <div class="aviso-count neutral">0</div>
                </div>

                <div class="aviso-item" style="cursor:default;">
                    <div class="aviso-icon neutral">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                        </svg>
                    </div>
                    <div class="aviso-content">
                        <div class="aviso-title">Cuentas por pagar</div>
                        <div class="aviso-desc">Se activará al construir el módulo de Compras</div>
                    </div>
                    <div class="aviso-count neutral">0</div>
                </div>
            </div>
        </div>

        <!-- ============ CAJAS ACTIVAS ============ -->
        <div class="dash-section">
            <div class="dash-section-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                    <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                </svg>
                Cajas activas
            </div>

            <div class="placeholder-card">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" width="48" height="48">
                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                    <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                </svg>
                <h4>No hay cajas abiertas</h4>
                <p>Aquí aparecerán las cajas activas con su vendedor, hora de apertura y turno. Desde aquí podrás acceder directo para vender.</p>
                <span class="placeholder-badge">Módulo pendiente</span>
            </div>
        </div>

        <!-- ============ VENTAS POR TURNO ============ -->
        <div class="dash-section">
            <div class="dash-section-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                    <line x1="18" y1="20" x2="18" y2="10"></line>
                    <line x1="12" y1="20" x2="12" y2="4"></line>
                    <line x1="6" y1="20" x2="6" y2="14"></line>
                </svg>
                Ventas por turno
            </div>

            <div class="placeholder-card">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" width="48" height="48">
                    <line x1="18" y1="20" x2="18" y2="10"></line>
                    <line x1="12" y1="20" x2="12" y2="4"></line>
                    <line x1="6" y1="20" x2="6" y2="14"></line>
                </svg>
                <h4>Sin turnos registrados</h4>
                <p>Aquí verás las estadísticas de ventas de cada turno abierto: total vendido, número de facturas, promedio por venta y más.</p>
                <span class="placeholder-badge">Módulo pendiente</span>
            </div>
        </div>

        <!-- ============ ACCESOS RÁPIDOS ============ -->
        <div class="dash-section">
            <div class="dash-section-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                    <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                </svg>
                Accesos rápidos
            </div>

            <div class="quick-actions">
                <a href="/AvilesPOSv2/modulos/productos/crear.php" class="quick-action">
                    <div class="quick-action-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                    </div>
                    <span>Nuevo producto</span>
                </a>

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

                <a href="/AvilesPOSv2/modulos/productos/index.php" class="quick-action">
                    <div class="quick-action-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20">
                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                        </svg>
                    </div>
                    <span>Ver catálogo</span>
                </a>

                <a href="/AvilesPOSv2/modulos/inventario/index.php" class="quick-action">
                    <div class="quick-action-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                        </svg>
                    </div>
                    <span>Historial inventario</span>
                </a>
            </div>
        </div>

    </main>

    <!-- ============================================
         MODALES DE DETALLE
         ============================================ -->

    <!-- Modal: Productos activos -->
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

    <!-- Modal: Movimientos de hoy -->
    <div class="modal-overlay" id="modalMovimientos">
        <div class="modal modal-lg">
            <div class="modal-header">
                <h3>Movimientos de hoy (<?= $mov_hoy ?>)</h3>
                <button type="button" class="modal-close" data-close>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <div class="modal-body" style="max-height:400px; overflow-y:auto; padding:0;">
                <div style="padding:14px 22px; border-bottom:1px solid #eef1f4; font-size:12px; color:#888;">
                    Entradas: <strong style="color:#1e7a3f;"><?= $mov_entradas ?></strong> &nbsp;·&nbsp;
                    Salidas: <strong style="color:#b00020;"><?= $mov_salidas ?></strong>
                </div>
                <?php if (count($movimientos_hoy) === 0): ?>
                    <p style="text-align:center; color:#888; padding:40px 20px;">Sin movimientos hoy</p>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Hora</th>
                                <th>Producto</th>
                                <th>Tipo</th>
                                <th>Cant.</th>
                                <th>Stock</th>
                                <th>Usuario</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($movimientos_hoy as $m): ?>
                                <tr>
                                    <td style="font-size:12px; color:#888;"><?= date('H:i', strtotime($m['fecha'])) ?></td>
                                    <td><?= htmlspecialchars($m['producto']) ?></td>
                                    <td>
                                        <?php if ($m['tipo'] === 'entrada'): ?>
                                            <span class="badge badge-success">Entrada</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Salida</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= (int)$m['cantidad'] ?></td>
                                    <td style="font-size:12px;"><?= (int)$m['stock_anterior'] ?> → <strong><?= (int)$m['stock_nuevo'] ?></strong></td>
                                    <td style="font-size:12px; color:#888;"><?= htmlspecialchars($m['usuario']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close>Cerrar</button>
                <a href="/AvilesPOSv2/modulos/inventario/index.php" class="btn btn-primary">Ver historial</a>
            </div>
        </div>
    </div>

    <!-- Modal: Stock bajo -->
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
                                        <a href="/AvilesPOSv2/modulos/inventario/nuevo.php" class="btn btn-secondary btn-sm">Reabastecer</a>
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

    <!-- Modal: Agotados -->
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
                                        <a href="/AvilesPOSv2/modulos/inventario/nuevo.php" class="btn btn-secondary btn-sm">Reabastecer</a>
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

        <!-- Modal: Ventas de hoy -->
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
                                            'transferencia' => 'Transfer.',
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
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Abrir modales al hacer clic en KPIs
            document.querySelectorAll('[data-modal]').forEach(el => {
                el.addEventListener('click', function () {
                    const modal = document.getElementById(this.dataset.modal);
                    if (modal) modal.classList.add('visible');
                });
            });

            // Cerrar con X, Cancelar o ESC (NO con clic fuera)
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