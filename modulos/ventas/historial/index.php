<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: /AvilesPOSv2/index.php");
    exit;
}

require __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../includes/permisos.php';
if (!tiene_permiso('ventas_historial_propio') && !tiene_permiso('ventas_historial_completo')) {
    header("Location: /AvilesPOSv2/dashboard.php");
    exit;
}

$ventas_tab = 'historial';
$hist_tab = 'ventas';

// ---------- Filtros ----------
$fecha_desde = $_GET['desde'] ?? date('Y-m-d');
$fecha_hasta = $_GET['hasta'] ?? date('Y-m-d');
$busqueda = trim($_GET['q'] ?? '');

// Si el usuario no ha tocado el filtro de turno, auto-seleccionar su turno abierto
$filtro_turno = 0;
if (isset($_GET['turno'])) {
    $filtro_turno = (int)$_GET['turno'];
} else {
    // Buscar turno abierto del usuario (o compartido)
    $id_usuario_actual = (int)$_SESSION['usuario_id'];
    $stmt = $conexion->prepare("
        SELECT id FROM turnos 
        WHERE estado = 'abierto'
          AND (id_usuario_apertura = ? OR (compartido = 1 AND id_usuario_apertura != ?))
        ORDER BY fecha_apertura ASC
        LIMIT 1
    ");
    $stmt->bind_param("ii", $id_usuario_actual, $id_usuario_actual);
    $stmt->execute();
    $turno_abierto = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($turno_abierto) {
        $filtro_turno = (int)$turno_abierto['id'];
    }
}

// Validar fechas
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_desde)) $fecha_desde = date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_hasta)) $fecha_hasta = date('Y-m-d');

// ---------- Construir consulta ----------
$sql = "SELECT v.id, v.numero_factura, v.fecha, v.total, v.subtotal, v.iva,
               v.descuento, v.metodo_pago, v.referencia_pago, v.estado,
               v.monto_recibido_cordobas, v.monto_recibido_usd, v.tipo_cambio, v.vuelto,
               v.observaciones,
               c.nombre AS cliente_nombre, c.cedula AS cliente_cedula,
               u.nombre AS cajero_nombre,
               t.id AS turno_id,
               cj.nombre AS caja_nombre
        FROM ventas v
        LEFT JOIN clientes c ON v.id_cliente = c.id
        INNER JOIN usuarios u ON v.id_usuario = u.id
        INNER JOIN turnos t ON v.id_turno = t.id
        INNER JOIN cajas cj ON t.id_caja = cj.id
        WHERE DATE(v.fecha) BETWEEN ? AND ?";

$params = [$fecha_desde, $fecha_hasta];
$tipos = "ss";

if ($filtro_turno > 0) {
    $sql .= " AND v.id_turno = ?";
    $params[] = $filtro_turno;
    $tipos .= "i";
}

if ($busqueda !== '') {
    $sql .= " AND (v.numero_factura LIKE ? OR c.nombre LIKE ?)";
    $like = '%' . $busqueda . '%';
    $params[] = $like;
    $params[] = $like;
    $tipos .= "ss";
}

$sql .= " ORDER BY v.fecha DESC LIMIT 500";

$stmt = $conexion->prepare($sql);
$stmt->bind_param($tipos, ...$params);
$stmt->execute();
$ventas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ---------- Resumen ----------
$resumen_total = 0;
$resumen_count = 0;
$resumen_efectivo = 0;
$resumen_tarjeta = 0;
$resumen_transferencia = 0;
$resumen_credito = 0;
$resumen_anuladas = 0;

foreach ($ventas as $v) {
    if ($v['estado'] === 'anulada') {
        $resumen_anuladas++;
        continue;
    }
    $resumen_total += (float)$v['total'];
    $resumen_count++;
    switch ($v['metodo_pago']) {
        case 'efectivo': $resumen_efectivo += (float)$v['total']; break;
        case 'tarjeta': $resumen_tarjeta += (float)$v['total']; break;
        case 'transferencia': $resumen_transferencia += (float)$v['total']; break;
        case 'credito': $resumen_credito += (float)$v['total']; break;
    }
}

// ---------- Turnos para el filtro ----------
$turnos_filtro = $conexion->query("
    SELECT t.id, t.fecha_apertura, t.estado, c.nombre AS caja_nombre
    FROM turnos t
    INNER JOIN cajas c ON t.id_caja = c.id
    WHERE DATE(t.fecha_apertura) BETWEEN '$fecha_desde' AND '$fecha_hasta'
       OR t.estado = 'abierto'
    ORDER BY t.fecha_apertura DESC
    LIMIT 50
")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de ventas - AvilesPOS</title>
    <link rel="stylesheet" href="/AvilesPOSv2/assets/css/estilos.css">
</head>
<body class="dash-body">

    <?php include __DIR__ . '/../../../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="page-header">
            <div class="page-header-left">
                <h1>Ventas</h1>
                <p>Punto de venta</p>
            </div>
            <div class="page-header-tabs">
                <?php include __DIR__ . '/../includes/tabs.php'; ?>
            </div>
        </div>

        <?php include __DIR__ . '/includes/tabs.php'; ?>

        <!-- Filtros -->
        <form method="GET" class="hist-filtros">
            <div class="hist-filtro">
                <label for="desde">Desde</label>
                <input type="date" name="desde" id="desde" value="<?= htmlspecialchars($fecha_desde) ?>">
            </div>

            <div class="hist-filtro">
                <label for="hasta">Hasta</label>
                <input type="date" name="hasta" id="hasta" value="<?= htmlspecialchars($fecha_hasta) ?>">
            </div>

            <div class="hist-filtro">
                <label for="turno">Turno</label>
                <select name="turno" id="turno">
                    <option value="">Todos</option>
                    <?php foreach ($turnos_filtro as $t): ?>
                        <option value="<?= (int)$t['id'] ?>" <?= $filtro_turno == $t['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($t['caja_nombre']) ?>
                            · <?= date('d/m H:i', strtotime($t['fecha_apertura'])) ?>
                            <?= $t['estado'] === 'abierto' ? '(abierto)' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="hist-filtro" style="flex:1; min-width:200px;">
                <label for="q">Buscar</label>
                <input type="text" name="q" id="q" placeholder="N° factura o cliente..." value="<?= htmlspecialchars($busqueda) ?>">
            </div>

            <div class="hist-filtro-acciones">
                <a href="index.php" class="btn btn-secondary">Limpiar</a>
                <button type="submit" class="btn btn-primary">Filtrar</button>
            </div>
        </form>

        <!-- Resumen -->
        <?php if ($resumen_count > 0 || $resumen_anuladas > 0): ?>
            <div class="hist-resumen">
                <div class="hist-resumen-item">
                    <div class="hist-resumen-label">Ventas</div>
                    <div class="hist-resumen-valor neutral"><?= $resumen_count ?></div>
                </div>
                <div class="hist-resumen-item">
                    <div class="hist-resumen-label">Total</div>
                    <div class="hist-resumen-valor">C$ <?= number_format($resumen_total, 2) ?></div>
                </div>
                <div class="hist-resumen-item">
                    <div class="hist-resumen-label">Efectivo</div>
                    <div class="hist-resumen-valor green">C$ <?= number_format($resumen_efectivo, 2) ?></div>
                </div>
                <div class="hist-resumen-item">
                    <div class="hist-resumen-label">Tarjeta</div>
                    <div class="hist-resumen-valor">C$ <?= number_format($resumen_tarjeta, 2) ?></div>
                </div>
                <div class="hist-resumen-item">
                    <div class="hist-resumen-label">Transferencia</div>
                    <div class="hist-resumen-valor">C$ <?= number_format($resumen_transferencia, 2) ?></div>
                </div>
                <div class="hist-resumen-item">
                    <div class="hist-resumen-label">Crédito</div>
                    <div class="hist-resumen-valor" style="color:#a05a00;">C$ <?= number_format($resumen_credito, 2) ?></div>
                </div>
                <?php if ($resumen_anuladas > 0): ?>
                    <div class="hist-resumen-item">
                        <div class="hist-resumen-label">Anuladas</div>
                        <div class="hist-resumen-valor red"><?= $resumen_anuladas ?></div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Tabla -->
        <?php if (count($ventas) === 0): ?>

            <div class="hist-tabla-container">
                <div class="hist-vacio">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" width="60" height="60">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                    </svg>
                    <div>No hay ventas en este rango</div>
                </div>
            </div>

        <?php else: ?>

            <div class="hist-tabla-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Fecha</th>
                            <th>Cliente</th>
                            <th>Cajero</th>
                            <th>Caja</th>
                            <th>Método</th>
                            <th style="text-align:right;">Total</th>
                            <th>Estado</th>
                            <th class="col-actions">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ventas as $v): ?>
                            <tr>
                                <td style="font-size:12px; font-weight:600;">
                                    <?= (int)$v['numero_factura'] ?>
                                </td>
                                <td style="font-size:12px; color:#666;">
                                    <?= date('d/m/Y H:i', strtotime($v['fecha'])) ?>
                                </td>
                                <td style="font-size:13px;">
                                    <?= htmlspecialchars($v['cliente_nombre'] ?? 'Consumidor final') ?>
                                </td>
                                <td style="font-size:12px; color:#888;">
                                    <?= htmlspecialchars($v['cajero_nombre']) ?>
                                </td>
                                <td style="font-size:12px; color:#888;">
                                    <?= htmlspecialchars($v['caja_nombre']) ?>
                                </td>
                                <td>
                                    <span class="badge-metodo <?= htmlspecialchars($v['metodo_pago']) ?>">
                                        <?php
                                        $labels = [
                                            'efectivo' => 'Efectivo',
                                            'tarjeta' => 'Tarjeta',
                                            'transferencia' => 'Transf.',
                                            'credito' => 'Crédito'
                                        ];
                                        echo $labels[$v['metodo_pago']] ?? $v['metodo_pago'];
                                        ?>
                                    </span>
                                </td>
                                <td style="text-align:right;">
                                    <span class="hist-total <?= $v['estado'] === 'anulada' ? 'hist-total-tachado' : '' ?>">
                                        C$ <?= number_format($v['total'], 2) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($v['estado'] === 'anulada'): ?>
                                        <span class="badge badge-anulada">Anulada</span>
                                    <?php else: ?>
                                        <span class="badge badge-completada">Completada</span>
                                    <?php endif; ?>
                                </td>
                                <td class="col-actions">
                                    <div class="hist-acciones">
                                        <button type="button" class="hist-btn-accion ver" data-ver-venta="<?= (int)$v['id'] ?>" title="Ver detalle">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                <circle cx="12" cy="12" r="3"></circle>
                                            </svg>
                                        </button>
                                        <button type="button" class="hist-btn-accion print" data-print-venta="<?= (int)$v['id'] ?>" title="Reimprimir ticket">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                                                <polyline points="6 9 6 2 18 2 18 9"></polyline>
                                                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                                                <rect x="6" y="14" width="12" height="8"></rect>
                                            </svg>
                                        </button>
                                        <?php if (tiene_permiso('ventas_devolucion')): ?>
                                        <button type="button" class="hist-btn-accion devolver" data-devolver-venta="<?= (int)$v['id'] ?>" title="Registrar devolución"
                                                <?= $v['estado'] === 'anulada' ? 'disabled' : '' ?>>
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                                                <polyline points="9 14 4 9 9 4"></polyline>
                                                <path d="M20 20v-7a4 4 0 0 0-4-4H4"></path>
                                            </svg>
                                        </button>
                                        <?php endif; ?>
                                        <?php if (tiene_permiso('ventas_anular')): ?>
                                        <button type="button" class="hist-btn-accion anular" data-anular-venta="<?= (int)$v['id'] ?>" title="Anular venta"
                                                <?= $v['estado'] === 'anulada' ? 'disabled' : '' ?>>
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                                                <circle cx="12" cy="12" r="10"></circle>
                                                <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line>
                                            </svg>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>

    </main>

    <!-- MODAL: Detalle de venta -->
    <div class="modal-overlay" id="modalDetalleVenta">
        <div class="modal modal-xl">
            <div class="modal-header">
                <h3 id="detalleVentaTitle">Detalle de venta</h3>
                <button type="button" class="modal-close" data-close>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <div class="modal-body" id="detalleVentaBody">
                <p style="text-align:center; color:#888; padding:40px;">Cargando...</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close>Cerrar</button>
                <button type="button" class="btn btn-primary" id="detalleVentaPrint">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                        <polyline points="6 9 6 2 18 2 18 9"></polyline>
                        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                        <rect x="6" y="14" width="12" height="8"></rect>
                    </svg>
                    Reimprimir
                </button>
            </div>
        </div>
    </div>

        <!-- MODAL: Devolución -->
    <div class="modal-overlay" id="modalDevolucion">
        <div class="modal modal-xl">
            <div class="modal-header">
                <h3>Registrar devolución</h3>
                <button type="button" class="modal-close" id="devCerrarX">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>

            <div class="modal-body">

                <!-- PASO 1: BUSCAR -->
                <div id="devPasoBuscar">
                    <div class="form-group full" style="margin-bottom:16px;">
                        <label for="devBusquedaVenta">Número de factura o cédula del cliente</label>
                        <div style="display:flex; gap:8px;">
                            <input type="text" id="devBusquedaVenta" placeholder="Ej: 12 o 001-010190-0001A" style="flex:1;">
                            <button type="button" class="btn btn-primary" id="devBuscarBtn">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                </svg>
                                Buscar
                            </button>
                        </div>
                        <span class="help">Solo se puede devolver ventas completadas (no anuladas)</span>
                    </div>

                    <div class="modal-error" id="devError"></div>

                    <div id="devResultadosBusqueda"></div>
                </div>

                <!-- PASO 2: SELECCIONAR -->
                <div id="devPasoSeleccionar" style="display:none;">

                    <!-- Info de la venta -->
                    <div class="dev-venta-info" id="devVentaInfo"></div>

                    <!-- Layout horizontal: items izq, detalles der -->
                    <div class="dev-layout">

                        <!-- Columna izquierda: items -->
                        <div class="dev-col-items">
                            <div class="dev-col-titulo">Productos de la venta</div>
                            <div class="dev-items-lista" id="devItemsLista"></div>
                        </div>

                        <!-- Columna derecha: detalles y resumen -->
                        <div class="dev-col-detalles">
                            <div class="dev-col-titulo">Detalles de la devolución</div>

                            <div class="dev-form-grupo">
                                <label for="devMetodo">Método <span class="req">*</span></label>
                                <select id="devMetodo">
                                    <option value="efectivo">Efectivo</option>
                                    <option value="tarjeta">Tarjeta</option>
                                    <option value="transferencia">Transferencia</option>
                                    <option value="ajuste_credito">Ajustar saldo del cliente</option>
                                </select>
                                <span class="help" id="devMetodoHelp"></span>
                            </div>

                            <div class="dev-form-grupo" id="devGrupoReferencia" style="display:none;">
                                <label for="devReferencia">Referencia / Comprobante</label>
                                <input type="text" id="devReferencia" maxlength="100" placeholder="Ej: 123456">
                            </div>

                            <div class="dev-form-grupo">
                                <label for="devMotivo">Motivo <span class="req">*</span></label>
                                <textarea id="devMotivo" maxlength="300" placeholder="Explica el motivo de la devolución..." style="min-height:70px;"></textarea>
                            </div>

                            <div class="dev-total-box">
                                <div class="dev-total-label">Total a devolver</div>
                                <div class="dev-total-valor" id="devTotalValor">C$ 0.00</div>
                            </div>

                            <button type="button" class="btn btn-secondary" id="devVolverBuscar" style="width:100%; margin-top:10px;">
                                ← Buscar otra venta
                            </button>
                        </div>

                    </div>

                </div>

            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="devCancelar">Cancelar</button>
                <button type="button" class="btn btn-primary" id="devGuardar" style="background:#b00020; display:none;">
                    Procesar devolución
                </button>
            </div>
        </div>
    </div>
    
    <!-- MODAL: Confirmar anulación -->
    <div class="modal-overlay" id="modalAnularVenta">
        <div class="modal" style="max-width:460px;">
            <div class="modal-header">
                <h3>Anular venta</h3>
                <button type="button" class="modal-close" data-close>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <div class="modal-error" id="anularError"></div>
                <div style="text-align:center; padding:8px 0 16px;">
                    <div style="width:56px; height:56px; margin:0 auto 12px; border-radius:50%; background:#ffe0e0; display:flex; align-items:center; justify-content:center;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="#b00020" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="28" height="28">
                            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                            <line x1="12" y1="9" x2="12" y2="13"></line>
                            <line x1="12" y1="17" x2="12.01" y2="17"></line>
                        </svg>
                    </div>
                    <p style="font-size:14px; color:#555; line-height:1.5; margin-bottom:6px;">
                        ¿Anular la venta <strong id="anularNumeroFactura"></strong>?
                    </p>
                    <p style="font-size:12px; color:#888; line-height:1.5;">
                        El stock de los productos será devuelto. Si fue al crédito, se descontará del saldo del cliente.
                    </p>
                </div>
                <div class="form-group full">
                    <label for="anularMotivo">Motivo de anulación <span class="req">*</span></label>
                    <textarea id="anularMotivo" maxlength="300" placeholder="Explica el motivo de la anulación..." style="min-height:70px;"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close>Cancelar</button>
                <button type="button" class="btn btn-primary" id="anularConfirmar" style="background:#b00020;">
                    Sí, anular venta
                </button>
            </div>
        </div>
    </div>

    <script>
        window.VENTAS_HIST = <?= json_encode($ventas) ?>;
    </script>
    <script src="/AvilesPOSv2/assets/js/historial.js?v=<?= filemtime(__DIR__ . '/../../../assets/js/historial.js') ?>"></script>

</body>
</html>