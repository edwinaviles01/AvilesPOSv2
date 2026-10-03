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
$hist_tab = 'movimientos';

$fecha_desde = $_GET['desde'] ?? date('Y-m-d');
$fecha_hasta = $_GET['hasta'] ?? date('Y-m-d');
$filtro_tipo = $_GET['tipo'] ?? '';

// Auto-seleccionar turno abierto del usuario
$filtro_turno = 0;
if (isset($_GET['turno'])) {
    $filtro_turno = (int)$_GET['turno'];
} else {
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

$sql = "SELECT m.id, m.tipo, m.monto, m.motivo, m.fecha,
               u.nombre AS usuario,
               t.id AS turno_id,
               cj.nombre AS caja_nombre
        FROM movimientos_caja m
        INNER JOIN usuarios u ON m.id_usuario = u.id
        INNER JOIN turnos t ON m.id_turno = t.id
        INNER JOIN cajas cj ON t.id_caja = cj.id
        WHERE DATE(m.fecha) BETWEEN ? AND ?";

$params = [$fecha_desde, $fecha_hasta];
$tipos = "ss";

if ($filtro_turno > 0) {
    $sql .= " AND m.id_turno = ?";
    $params[] = $filtro_turno;
    $tipos .= "i";
}

if (in_array($filtro_tipo, ['entrada', 'salida'])) {
    $sql .= " AND m.tipo = ?";
    $params[] = $filtro_tipo;
    $tipos .= "s";
}

$sql .= " ORDER BY m.fecha DESC LIMIT 500";

$stmt = $conexion->prepare($sql);
$stmt->bind_param($tipos, ...$params);
$stmt->execute();
$movimientos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Resumen
$total_entradas = 0;
$total_salidas = 0;
$count_entradas = 0;
$count_salidas = 0;

foreach ($movimientos as $m) {
    if ($m['tipo'] === 'entrada') {
        $total_entradas += (float)$m['monto'];
        $count_entradas++;
    } else {
        $total_salidas += (float)$m['monto'];
        $count_salidas++;
    }
}
$neto = $total_entradas - $total_salidas;

// Turnos
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
    <title>Historial - Movimientos de caja - AvilesPOS</title>
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
            <div class="hist-filtro">
                <label for="tipo">Tipo</label>
                <select name="tipo" id="tipo">
                    <option value="">Todos</option>
                    <option value="entrada" <?= $filtro_tipo === 'entrada' ? 'selected' : '' ?>>Entradas</option>
                    <option value="salida" <?= $filtro_tipo === 'salida' ? 'selected' : '' ?>>Salidas</option>
                </select>
            </div>
            <div class="hist-filtro-acciones">
                <a href="movimientos.php" class="btn btn-secondary">Limpiar</a>
                <button type="submit" class="btn btn-primary">Filtrar</button>
            </div>
        </form>

        <?php if (count($movimientos) > 0): ?>
            <div class="hist-resumen">
                <div class="hist-resumen-item">
                    <div class="hist-resumen-label">Entradas</div>
                    <div class="hist-resumen-valor green">C$ <?= number_format($total_entradas, 2) ?></div>
                </div>
                <div class="hist-resumen-item">
                    <div class="hist-resumen-label">Salidas</div>
                    <div class="hist-resumen-valor red">C$ <?= number_format($total_salidas, 2) ?></div>
                </div>
                <div class="hist-resumen-item">
                    <div class="hist-resumen-label">Neto</div>
                    <div class="hist-resumen-valor <?= $neto >= 0 ? 'green' : 'red' ?>">
                        <?= $neto >= 0 ? '+' : '' ?>C$ <?= number_format($neto, 2) ?>
                    </div>
                </div>
                <div class="hist-resumen-item">
                    <div class="hist-resumen-label">Movimientos</div>
                    <div class="hist-resumen-valor neutral">
                        <?= $count_entradas + $count_salidas ?>
                        <span style="font-size:11px; color:#888; font-weight:400;">
                            (<?= $count_entradas ?> ent · <?= $count_salidas ?> sal)
                        </span>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (count($movimientos) === 0): ?>
            <div class="hist-tabla-container">
                <div class="hist-vacio">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" width="60" height="60">
                        <polyline points="23 4 23 10 17 10"></polyline>
                        <polyline points="1 20 1 14 7 14"></polyline>
                        <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                    </svg>
                    <div>No hay movimientos en este rango</div>
                </div>
            </div>
        <?php else: ?>
            <div class="hist-tabla-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Caja</th>
                            <th>Usuario</th>
                            <th>Motivo</th>
                            <th style="text-align:right;">Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($movimientos as $m): ?>
                            <tr>
                                <td style="font-size:12px; color:#666;">
                                    <?= date('d/m/Y H:i', strtotime($m['fecha'])) ?>
                                </td>
                                <td>
                                    <?php if ($m['tipo'] === 'entrada'): ?>
                                        <span class="badge badge-success">Entrada</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Salida</span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-size:12px; color:#888;">
                                    <?= htmlspecialchars($m['caja_nombre']) ?>
                                </td>
                                <td style="font-size:12px; color:#888;">
                                    <?= htmlspecialchars($m['usuario']) ?>
                                </td>
                                <td style="font-size:13px; max-width:400px;">
                                    <?= htmlspecialchars($m['motivo']) ?>
                                </td>
                                <td style="text-align:right; font-weight:700; <?= $m['tipo'] === 'entrada' ? 'color:#1e7a3f;' : 'color:#b00020;' ?>">
                                    <?= $m['tipo'] === 'entrada' ? '+' : '−' ?>C$ <?= number_format($m['monto'], 2) ?>
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