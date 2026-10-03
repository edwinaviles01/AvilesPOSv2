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
$hist_tab = 'devoluciones';

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

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_desde)) $fecha_desde = date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_hasta)) $fecha_hasta = date('Y-m-d');

$sql = "SELECT d.id, d.monto_total, d.metodo_pago, d.referencia, d.motivo, d.fecha,
               v.numero_factura,
               c.nombre AS cliente_nombre, c.cedula AS cliente_cedula,
               u.nombre AS usuario,
               cj.nombre AS caja_nombre
        FROM devoluciones d
        INNER JOIN ventas v ON d.id_venta = v.id
        LEFT JOIN clientes c ON v.id_cliente = c.id
        INNER JOIN usuarios u ON d.id_usuario = u.id
        INNER JOIN turnos t ON d.id_turno = t.id
        INNER JOIN cajas cj ON t.id_caja = cj.id
        WHERE DATE(d.fecha) BETWEEN ? AND ?";

$params = [$fecha_desde, $fecha_hasta];
$tipos = "ss";

if ($filtro_turno > 0) {
    $sql .= " AND d.id_turno = ?";
    $params[] = $filtro_turno;
    $tipos .= "i";
}

if ($busqueda !== '') {
    $sql .= " AND (CAST(v.numero_factura AS CHAR) LIKE ? OR c.nombre LIKE ?)";
    $like = '%' . $busqueda . '%';
    $params[] = $like;
    $params[] = $like;
    $tipos .= "ss";
}

$sql .= " ORDER BY d.fecha DESC LIMIT 500";

$stmt = $conexion->prepare($sql);
$stmt->bind_param($tipos, ...$params);
$stmt->execute();
$devoluciones = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Resumen
$total_devuelto = 0;
foreach ($devoluciones as $d) {
    $total_devuelto += (float)$d['monto_total'];
}

// Turnos para el filtro
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
    <title>Historial - Devoluciones - AvilesPOS</title>
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
                <a href="devoluciones.php" class="btn btn-secondary">Limpiar</a>
                <button type="submit" class="btn btn-primary">Filtrar</button>
            </div>
        </form>

        <?php if (count($devoluciones) > 0): ?>
            <div class="hist-resumen">
                <div class="hist-resumen-item">
                    <div class="hist-resumen-label">Devoluciones</div>
                    <div class="hist-resumen-valor neutral"><?= count($devoluciones) ?></div>
                </div>
                <div class="hist-resumen-item">
                    <div class="hist-resumen-label">Total devuelto</div>
                    <div class="hist-resumen-valor red">C$ <?= number_format($total_devuelto, 2) ?></div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (count($devoluciones) === 0): ?>
            <div class="hist-tabla-container">
                <div class="hist-vacio">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" width="60" height="60">
                        <polyline points="9 14 4 9 9 4"></polyline>
                        <path d="M20 20v-7a4 4 0 0 0-4-4H4"></path>
                    </svg>
                    <div>No hay devoluciones en este rango</div>
                </div>
            </div>
        <?php else: ?>
            <div class="hist-tabla-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Factura</th>
                            <th>Cliente</th>
                            <th>Caja</th>
                            <th>Usuario</th>
                            <th>Método</th>
                            <th>Motivo</th>
                            <th style="text-align:right;">Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($devoluciones as $d): ?>
                            <tr>
                                <td style="font-size:12px; color:#666;">
                                    <?= date('d/m/Y H:i', strtotime($d['fecha'])) ?>
                                </td>
                                <td style="font-weight:600;">
                                    #<?= (int)$d['numero_factura'] ?>
                                </td>
                                <td style="font-size:13px;">
                                    <?= htmlspecialchars($d['cliente_nombre'] ?? 'Consumidor final') ?>
                                </td>
                                <td style="font-size:12px; color:#888;">
                                    <?= htmlspecialchars($d['caja_nombre']) ?>
                                </td>
                                <td style="font-size:12px; color:#888;">
                                    <?= htmlspecialchars($d['usuario']) ?>
                                </td>
                                <td>
                                    <?php
                                    $metodos = [
                                        'efectivo' => 'Efectivo',
                                        'tarjeta' => 'Tarjeta',
                                        'transferencia' => 'Transf.',
                                        'ajuste_credito' => 'Saldo'
                                    ];
                                    ?>
                                    <span class="badge-metodo <?= $d['metodo_pago'] === 'ajuste_credito' ? 'credito' : htmlspecialchars($d['metodo_pago']) ?>">
                                        <?= $metodos[$d['metodo_pago']] ?? $d['metodo_pago'] ?>
                                    </span>
                                </td>
                                <td style="font-size:12px; color:#666; max-width:280px;">
                                    <?= htmlspecialchars($d['motivo']) ?>
                                </td>
                                <td style="text-align:right; font-weight:700; color:#b00020;">
                                    −C$ <?= number_format($d['monto_total'], 2) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>

</body>
</html>