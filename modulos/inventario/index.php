<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: /AvilesPOSv2/index.php");
    exit;
}

require __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/permisos.php';
requiere_permiso('inventario_ver');

// Traemos los movimientos con datos del producto y del usuario
$sql = "SELECT m.id, m.tipo, m.cantidad, m.stock_anterior, m.stock_nuevo,
               m.precio_costo_anterior, m.precio_costo_nuevo,
               m.precio_venta_anterior, m.precio_venta_nuevo,
               m.comentario, m.fecha,
               p.nombre AS producto, p.codigo AS codigo_producto,
               u.nombre AS usuario,
               pr.nombre AS proveedor
        FROM movimientos_inventario m
        INNER JOIN productos p ON m.id_producto = p.id
        INNER JOIN usuarios u ON m.id_usuario = u.id
        LEFT JOIN proveedores pr ON m.id_proveedor = pr.id
        ORDER BY m.fecha DESC
        LIMIT 100";
$resultado = $conexion->query($sql);
$movimientos = $resultado->fetch_all(MYSQLI_ASSOC);

$mensaje_exito = $_SESSION['inventario_mensaje'] ?? null;
unset($_SESSION['inventario_mensaje']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movimientos de Inventario - AvilesPOS</title>
    <link rel="stylesheet" href="/AvilesPOSv2/assets/css/estilos.css">
</head>
<body class="dash-body">

    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="page-header">
            <div class="page-header-left">
                <h1>Movimientos de Inventario</h1>
                <p>Registra entradas y salidas de productos</p>
            </div>
            <a href="nuevo.php" class="btn btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Registrar movimiento
            </a>
        </div>

        <?php if ($mensaje_exito): ?>
            <div class="alert alert-success"><?= htmlspecialchars($mensaje_exito) ?></div>
        <?php endif; ?>

        <?php if (count($movimientos) === 0): ?>

            <div class="empty-state">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" width="64" height="64">
                    <polyline points="23 4 23 10 17 10"></polyline>
                    <polyline points="1 20 1 14 7 14"></polyline>
                    <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                </svg>
                <h3>No hay movimientos registrados</h3>
                <p>Registra la primera entrada o salida de inventario</p>
                <a href="nuevo.php" class="btn btn-primary">Registrar movimiento</a>
            </div>

        <?php else: ?>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Producto</th>
                            <th>Tipo</th>
                            <th>Cantidad</th>
                            <th>Stock</th>
                            <th>Usuario</th>
                            <th>Proveedor</th>
                            <th>Comentario</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($movimientos as $m): ?>
                            <tr>
                                <td><?= date('d/m/Y H:i', strtotime($m['fecha'])) ?></td>
                                <td>
                                    <?= htmlspecialchars($m['producto']) ?>
                                    <span style="display:block; font-size:11px; color:#888;">
                                        <?= htmlspecialchars($m['codigo_producto']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($m['tipo'] === 'entrada'): ?>
                                        <span class="badge badge-success">Entrada</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Salida</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= (int)$m['cantidad'] ?></td>
                                <td style="font-size:12px; color:#666;">
                                    <?= (int)$m['stock_anterior'] ?> → <strong><?= (int)$m['stock_nuevo'] ?></strong>
                                </td>
                                <td><?= htmlspecialchars($m['usuario']) ?></td>
                                <td style="font-size:12px; color:#666;">
                                    <?= htmlspecialchars($m['proveedor'] ?: '—') ?>
                                </td>
                                <td style="font-size:12px; color:#666; max-width:250px;">
                                    <?= htmlspecialchars($m['comentario'] ?: '—') ?>
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