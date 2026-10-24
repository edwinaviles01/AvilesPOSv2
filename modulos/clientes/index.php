<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: /AvilesPOSv2/index.php");
    exit;
}

require __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/permisos.php';
requiere_permiso('clientes_ver');

// Filtro por búsqueda
$busqueda = trim($_GET['q'] ?? '');

$sql = "SELECT id, nombre, cedula, telefono, email, tipo, 
               permite_credito, limite_credito, saldo_actual, activo
        FROM clientes";

if ($busqueda !== '') {
    $sql .= " WHERE nombre LIKE ? OR cedula LIKE ? OR telefono LIKE ?";
}

$sql .= " ORDER BY nombre ASC";

$stmt = $conexion->prepare($sql);
if ($busqueda !== '') {
    $like = '%' . $busqueda . '%';
    $stmt->bind_param("sss", $like, $like, $like);
}
$stmt->execute();
$clientes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$mensaje_exito = $_SESSION['cliente_mensaje'] ?? null;
$mensaje_error = $_SESSION['cliente_error'] ?? null;
unset($_SESSION['cliente_mensaje'], $_SESSION['cliente_error']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes - AvilesPOS</title>
    <link rel="stylesheet" href="/AvilesPOSv2/assets/css/estilos.css">
</head>
<body class="dash-body">

    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="page-header">
            <div class="page-header-left">
                <h1>Clientes</h1>
                <p>Administra el catálogo de clientes</p>
            </div>
            <a href="crear.php" class="btn btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Nuevo cliente
            </a>
        </div>

        <?php if ($mensaje_exito): ?>
            <div class="alert alert-success"><?= htmlspecialchars($mensaje_exito) ?></div>
        <?php endif; ?>

        <?php if ($mensaje_error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($mensaje_error) ?></div>
        <?php endif; ?>

        <form method="GET" style="margin-bottom:20px; max-width:400px;">
            <input type="text" name="q" placeholder="Buscar por nombre, cédula o teléfono..." 
                   value="<?= htmlspecialchars($busqueda) ?>"
                   style="padding:10px 14px; border:1px solid #ccc; border-radius:6px; width:100%; font-family:inherit; font-size:14px;">
        </form>

        <?php if (count($clientes) === 0): ?>

            <div class="empty-state">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" width="64" height="64">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                <h3>
                    <?= $busqueda !== '' ? 'Sin resultados' : 'No hay clientes todavía' ?>
                </h3>
                <p>
                    <?= $busqueda !== '' 
                        ? 'Prueba con otra búsqueda o limpia el filtro' 
                        : 'Empieza agregando tu primer cliente' ?>
                </p>
                <?php if ($busqueda === ''): ?>
                    <a href="crear.php" class="btn btn-primary">Agregar cliente</a>
                <?php else: ?>
                    <a href="index.php" class="btn btn-secondary">Limpiar búsqueda</a>
                <?php endif; ?>
            </div>

        <?php else: ?>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Cédula</th>
                            <th>Teléfono</th>
                            <th>Tipo</th>
                            <th>Crédito</th>
                            <th>Saldo</th>
                            <th>Estado</th>
                            <th class="col-actions">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clientes as $c): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($c['nombre']) ?></strong>
                                    <?php if ($c['email']): ?>
                                        <span style="display:block; font-size:11px; color:#888;"><?= htmlspecialchars($c['email']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-size:13px; color:#666;"><?= htmlspecialchars($c['cedula'] ?: '—') ?></td>
                                <td style="font-size:13px; color:#666;"><?= htmlspecialchars($c['telefono'] ?: '—') ?></td>
                                <td>
                                    <?php if ($c['tipo'] === 'juridica'): ?>
                                        <span class="badge" style="background:#e6f0fb; color:#0e5dac;">Empresa</span>
                                    <?php else: ?>
                                        <span class="badge" style="background:#f0f2f5; color:#666;">Natural</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($c['permite_credito']): ?>
                                        <span class="badge badge-success">Sí</span>
                                    <?php else: ?>
                                        <span class="badge" style="background:#f0f2f5; color:#888;">No</span>
                                    <?php endif; ?>
                                </td>
                                <td class="col-precio">
                                    <?php if ((float)$c['saldo_actual'] > 0): ?>
                                        <span style="color:#b00020;">C$ <?= number_format($c['saldo_actual'], 2) ?></span>
                                    <?php else: ?>
                                        <span style="color:#888; font-weight:normal;">C$ 0.00</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($c['activo']): ?>
                                        <span class="badge badge-success">Activo</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="col-actions">
                                    <div class="acciones">
                                        <a href="editar.php?id=<?= (int)$c['id'] ?>" class="btn-icon edit" title="Editar">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                            </svg>
                                        </a>
                                        <a href="eliminar.php?id=<?= (int)$c['id'] ?>" class="btn-icon delete" title="Eliminar">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                                                <polyline points="3 6 5 6 21 6"></polyline>
                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                            </svg>
                                        </a>
                                    </div>
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