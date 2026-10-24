<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: /AvilesPOSv2/index.php");
    exit;
}

require __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../includes/permisos.php';
requiere_permiso('turnos_abrir');

$ventas_tab = 'turnos';
$id_usuario = (int)$_SESSION['usuario_id'];

// 1. ¿El usuario tiene un turno abierto?
$stmt = $conexion->prepare("
    SELECT t.*, c.nombre AS caja_nombre
    FROM turnos t
    INNER JOIN cajas c ON t.id_caja = c.id
    WHERE t.id_usuario_apertura = ? AND t.estado = 'abierto'
    LIMIT 1
");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$mi_turno = $stmt->get_result()->fetch_assoc();
$stmt->close();

// 1b. Si tiene turno abierto, calcular datos de cierre
$cierre_datos = null;
if ($mi_turno) {
    $id_turno_actual = (int)$mi_turno['id'];

    // Ventas en efectivo del turno (solo completadas)
    $stmt = $conexion->prepare("
        SELECT COALESCE(SUM(total), 0) AS total_efectivo
        FROM ventas 
        WHERE id_turno = ? AND metodo_pago = 'efectivo' AND estado = 'completada'
    ");
    $stmt->bind_param("i", $id_turno_actual);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $ventas_efectivo_cierre = (float)$res['total_efectivo'];

    // Movimientos de caja (entradas/salidas)
    $stmt = $conexion->prepare("
        SELECT 
            COALESCE(SUM(CASE WHEN tipo = 'entrada' THEN monto ELSE 0 END), 0) AS entradas,
            COALESCE(SUM(CASE WHEN tipo = 'salida' THEN monto ELSE 0 END), 0) AS salidas
        FROM movimientos_caja 
        WHERE id_turno = ?
    ");
    $stmt->bind_param("i", $id_turno_actual);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $entradas_caja_cierre = (float)$res['entradas'];
    $salidas_caja_cierre = (float)$res['salidas'];

    $efectivo_esperado = (float)$mi_turno['monto_inicial'] 
                       + $ventas_efectivo_cierre 
                       + $entradas_caja_cierre 
                       - $salidas_caja_cierre;

    // Número de ventas activas (no anuladas)
    $stmt = $conexion->prepare("
        SELECT COUNT(*) AS total FROM ventas 
        WHERE id_turno = ? AND estado = 'completada'
    ");
    $stmt->bind_param("i", $id_turno_actual);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $ventas_activas_cierre = (int)$res['total'];

    $cierre_datos = [
        'id_turno' => $id_turno_actual,
        'monto_inicial' => (float)$mi_turno['monto_inicial'],
        'ventas_efectivo' => $ventas_efectivo_cierre,
        'entradas_caja' => $entradas_caja_cierre,
        'salidas_caja' => $salidas_caja_cierre,
        'efectivo_esperado' => $efectivo_esperado,
        'numero_ventas_activas' => $ventas_activas_cierre,
        'total_vendido' => (float)$mi_turno['total_ventas']
    ];
}

// 2. Turnos abiertos compartidos (que no son míos)
$stmt = $conexion->prepare("
    SELECT t.*, c.nombre AS caja_nombre, u.nombre AS usuario_nombre
    FROM turnos t
    INNER JOIN cajas c ON t.id_caja = c.id
    INNER JOIN usuarios u ON t.id_usuario_apertura = u.id
    WHERE t.estado = 'abierto' AND t.compartido = 1 AND t.id_usuario_apertura != ?
    ORDER BY t.fecha_apertura DESC
");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$turnos_compartidos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// 3. Últimos turnos cerrados (historial rápido)
$stmt = $conexion->prepare("
    SELECT t.*, c.nombre AS caja_nombre, u.nombre AS usuario_nombre
    FROM turnos t
    INNER JOIN cajas c ON t.id_caja = c.id
    INNER JOIN usuarios u ON t.id_usuario_apertura = u.id
    WHERE t.estado = 'cerrado'
    ORDER BY t.fecha_cierre DESC
    LIMIT 10
");
$stmt->execute();
$turnos_cerrados = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$mensaje_exito = $_SESSION['turno_mensaje'] ?? null;
$mensaje_error = $_SESSION['turno_error'] ?? null;
unset($_SESSION['turno_mensaje'], $_SESSION['turno_error']);

// Helper para formatear
function fmt($n) {
    return 'C$ ' . number_format((float)$n, 2);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Turnos - AvilesPOS</title>
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

        <?php if (!$mi_turno): ?>
            <div style="margin-bottom:16px; display:flex; justify-content:flex-end;">
                <a href="abrir.php" class="btn btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    Abrir turno
                </a>
            </div>
        <?php endif; ?>

        <?php if ($mensaje_exito): ?>
            <div class="alert alert-success"><?= htmlspecialchars($mensaje_exito) ?></div>
        <?php endif; ?>

        <?php if ($mensaje_error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($mensaje_error) ?></div>
        <?php endif; ?>

        <!-- MI TURNO ACTIVO -->
        <?php if ($mi_turno): ?>
            <div class="dash-section">
                <div class="dash-section-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    Mi turno activo
                </div>

                <div class="turno-card">
                    <div class="turno-card-header">
                        <div>
                            <h3><?= htmlspecialchars($mi_turno['caja_nombre']) ?></h3>
                            <span class="turno-estado-badge abierto">Abierto</span>
                            <?php if ($mi_turno['compartido']): ?>
                                <span class="turno-estado-badge compartido">Compartido</span>
                            <?php endif; ?>
                        </div>
                        <div class="turno-card-fecha">
                            Abierto <?= date('d/m/Y H:i', strtotime($mi_turno['fecha_apertura'])) ?>
                        </div>
                    </div>

                    <div class="turno-card-stats">
                        <div class="turno-stat">
                            <div class="turno-stat-label">Monto inicial</div>
                            <div class="turno-stat-valor"><?= fmt($mi_turno['monto_inicial']) ?></div>
                        </div>
                        <div class="turno-stat">
                            <div class="turno-stat-label">Ventas</div>
                            <div class="turno-stat-valor"><?= (int)$mi_turno['numero_ventas'] ?></div>
                        </div>
                        <div class="turno-stat">
                            <div class="turno-stat-label">Total vendido</div>
                            <div class="turno-stat-valor"><?= fmt($mi_turno['total_ventas']) ?></div>
                        </div>
                    </div>

                    <div class="turno-card-actions">
                        <button type="button" class="btn btn-secondary" id="btnAbrirCierre">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                            </svg>
                            Cerrar turno
                        </button>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- TURNOS COMPARTIDOS DISPONIBLES -->
        <?php if (!$mi_turno && count($turnos_compartidos) > 0): ?>
            <div class="dash-section">
                <div class="dash-section-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                    Turnos compartidos disponibles
                </div>

                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Caja</th>
                                <th>Abierto por</th>
                                <th>Apertura</th>
                                <th>Monto inicial</th>
                                <th class="col-actions">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($turnos_compartidos as $t): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($t['caja_nombre']) ?></strong></td>
                                    <td><?= htmlspecialchars($t['usuario_nombre']) ?></td>
                                    <td style="font-size:13px; color:#666;">
                                        <?= date('d/m/Y H:i', strtotime($t['fecha_apertura'])) ?>
                                    </td>
                                    <td class="col-precio"><?= fmt($t['monto_inicial']) ?></td>
                                    <td class="col-actions">
                                        <a href="/AvilesPOSv2/modulos/ventas/index.php" class="btn btn-primary btn-sm">
                                            Usar para vender
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- SIN TURNO -->
        <?php if (!$mi_turno && count($turnos_compartidos) === 0): ?>
            <div class="dash-section">
                <div class="empty-state">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" width="64" height="64">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    <h3>No tienes un turno abierto</h3>
                    <p>Para vender necesitas abrir un turno en alguna caja</p>
                    <a href="abrir.php" class="btn btn-primary">Abrir turno</a>
                </div>
            </div>
        <?php endif; ?>

        <!-- HISTORIAL RECIENTE -->
        <?php if (count($turnos_cerrados) > 0): ?>
            <div class="dash-section">
                <div class="dash-section-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                    </svg>
                    Últimos turnos cerrados
                </div>

                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Caja</th>
                                <th>Usuario</th>
                                <th>Apertura</th>
                                <th>Cierre</th>
                                <th>Ventas</th>
                                <th>Total</th>
                                <th>Diferencia</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($turnos_cerrados as $t): 
                                $dif = (float)$t['diferencia'];
                                $dif_clase = abs($dif) < 0.01 ? '' : ($dif > 0 ? 'badge-success' : 'badge-danger');
                            ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($t['caja_nombre']) ?></strong></td>
                                    <td style="font-size:13px;"><?= htmlspecialchars($t['usuario_nombre']) ?></td>
                                    <td style="font-size:12px; color:#666;">
                                        <?= date('d/m H:i', strtotime($t['fecha_apertura'])) ?>
                                    </td>
                                    <td style="font-size:12px; color:#666;">
                                        <?= $t['fecha_cierre'] ? date('d/m H:i', strtotime($t['fecha_cierre'])) : '—' ?>
                                    </td>
                                    <td><?= (int)$t['numero_ventas'] ?></td>
                                    <td class="col-precio"><?= fmt($t['total_ventas']) ?></td>
                                    <td>
                                        <?php if (abs($dif) < 0.01): ?>
                                            <span class="badge badge-success">Exacto</span>
                                        <?php else: ?>
                                            <span class="badge <?= $dif_clase ?>">
                                                <?= $dif > 0 ? '+' : '' ?><?= number_format($dif, 2) ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

    </main>

    <?php if ($cierre_datos): ?>
    <!-- MODAL: Cerrar turno -->
    <div class="modal-overlay" id="modalCerrarTurno">
        <div class="modal modal-xl">
            <div class="modal-header">
                <h3>Cerrar turno</h3>
                <button type="button" class="modal-close" id="cierreCerrarX">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>

            <div class="modal-body">

                <div class="cierre-layout">

                    <!-- Columna izquierda: resumen -->
                    <div class="cierre-col-izq">

                        <div class="cierre-seccion">
                            <div class="cierre-seccion-titulo">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                                Resumen del turno
                            </div>
                            <div class="cierre-info-box">
                                <div class="cierre-linea">
                                    <span>Caja</span>
                                    <strong><?= htmlspecialchars($mi_turno['caja_nombre']) ?></strong>
                                </div>
                                <div class="cierre-linea">
                                    <span>Apertura</span>
                                    <strong><?= date('d/m/Y H:i', strtotime($mi_turno['fecha_apertura'])) ?></strong>
                                </div>
                                <div class="cierre-linea">
                                    <span>Número de ventas</span>
                                    <strong><?= $cierre_datos['numero_ventas_activas'] ?></strong>
                                </div>
                                <div class="cierre-linea">
                                    <span>Total vendido</span>
                                    <strong>C$ <?= number_format($cierre_datos['total_vendido'], 2) ?></strong>
                                </div>
                            </div>
                        </div>

                        <div class="cierre-seccion">
                            <div class="cierre-seccion-titulo">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                                    <line x1="12" y1="1" x2="12" y2="23"></line>
                                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                                </svg>
                                Desglose de efectivo
                            </div>
                            <div class="cierre-efectivo-box">
                                <div class="cierre-linea">
                                    <span>Monto inicial</span>
                                    <strong>C$ <?= number_format($cierre_datos['monto_inicial'], 2) ?></strong>
                                </div>
                                <div class="cierre-linea">
                                    <span>Ventas en efectivo</span>
                                    <strong class="verde">+ C$ <?= number_format($cierre_datos['ventas_efectivo'], 2) ?></strong>
                                </div>
                                <?php if ($cierre_datos['entradas_caja'] > 0): ?>
                                <div class="cierre-linea">
                                    <span>Entradas de caja</span>
                                    <strong class="verde">+ C$ <?= number_format($cierre_datos['entradas_caja'], 2) ?></strong>
                                </div>
                                <?php endif; ?>
                                <?php if ($cierre_datos['salidas_caja'] > 0): ?>
                                <div class="cierre-linea">
                                    <span>Salidas de caja</span>
                                    <strong class="rojo">− C$ <?= number_format($cierre_datos['salidas_caja'], 2) ?></strong>
                                </div>
                                <?php endif; ?>
                                <div class="cierre-linea total">
                                    <span>Efectivo esperado</span>
                                    <strong>C$ <?= number_format($cierre_datos['efectivo_esperado'], 2) ?></strong>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Columna derecha: cuadre + acciones -->
                    <div class="cierre-col-der">

                        <div class="cierre-seccion">
                            <div class="cierre-seccion-titulo">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                                    <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                                </svg>
                                Cuadre de caja
                            </div>

                            <div class="form-group full" style="margin-bottom:14px;">
                                <label for="cierreMontoContado">Monto contado físicamente <span class="req">*</span></label>
                                <div class="input-suffix">
                                    <input type="number" id="cierreMontoContado" step="0.01" min="0" placeholder="0.00"
                                           data-esperado="<?= number_format($cierre_datos['efectivo_esperado'], 2, '.', '') ?>">
                                    <span class="suffix">C$</span>
                                </div>
                                <span class="help">Cuenta el efectivo físico en la caja</span>
                            </div>

                            <div class="cierre-diferencia" id="cierreDiferencia">
                                <div class="cierre-dif-label">Diferencia</div>
                                <div class="cierre-dif-valor" id="cierreDifValor">Ingresa el monto contado</div>
                            </div>
                        </div>

                        <div class="cierre-seccion">
                            <div class="cierre-seccion-titulo">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                                </svg>
                                Observaciones
                            </div>
                            <div class="form-group full">
                                <textarea id="cierreObservaciones" maxlength="500" placeholder="Notas del cierre (opcional, pero recomendado si hay diferencia)..." style="min-height:70px;"></textarea>
                            </div>
                        </div>

                    </div>

                </div>

                <div class="modal-error" id="cierreError" style="margin-top:14px;"></div>

            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="cierreCancelar">Cancelar</button>
                <button type="button" class="btn btn-primary" id="cierreConfirmar" style="background:#b00020;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                    Confirmar cierre
                </button>
            </div>

        </div>
    </div>
    <?php endif; ?>

    <script>
        window.CIERRE_DATOS = <?= json_encode($cierre_datos) ?>;
    </script>
    <script src="/AvilesPOSv2/assets/js/cerrar-turno.js?v=<?= filemtime(__DIR__ . '/../../../assets/js/cerrar-turno.js') ?>"></script>

</body>
</html>