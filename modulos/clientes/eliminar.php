<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: /AvilesPOSv2/index.php");
    exit;
}

require __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/permisos.php';
requiere_permiso('clientes_eliminar');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: index.php");
    exit;
}

// Cargar cliente
$stmt = $conexion->prepare("SELECT id, nombre, saldo_actual FROM clientes WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$cliente = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$cliente) {
    header("Location: index.php");
    exit;
}

// Si confirma, borramos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar'])) {
    // Verificar que no tenga saldo pendiente
    if ((float)$cliente['saldo_actual'] > 0) {
        $_SESSION['cliente_error'] = "No se puede eliminar: el cliente tiene un saldo pendiente de C$ " . number_format($cliente['saldo_actual'], 2);
        header("Location: index.php");
        exit;
    }

    $stmt = $conexion->prepare("DELETE FROM clientes WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $_SESSION['cliente_mensaje'] = "Cliente '{$cliente['nombre']}' eliminado.";
        header("Location: index.php");
        exit;
    } else {
        $_SESSION['cliente_error'] = "Error al eliminar: " . $stmt->error;
        header("Location: index.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eliminar cliente - AvilesPOS</title>
    <link rel="stylesheet" href="/AvilesPOSv2/assets/css/estilos.css">
</head>
<body class="dash-body">

    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="page-header">
            <div class="page-header-left">
                <h1>Eliminar cliente</h1>
                <p>Esta acción no se puede deshacer</p>
            </div>
            <a href="index.php" class="btn btn-secondary">Volver</a>
        </div>

        <div class="form-container" style="max-width:600px;">
            <p style="margin-bottom:20px; font-size:15px; color:#555;">
                ¿Estás seguro que quieres eliminar al cliente <strong><?= htmlspecialchars($cliente['nombre']) ?></strong>?
            </p>
            <p style="margin-bottom:24px; font-size:13px; color:#888;">
                Si prefieres dejar de atenderlo sin borrar el historial, puedes editarlo y desmarcar "Cliente activo".
            </p>

            <?php if ((float)$cliente['saldo_actual'] > 0): ?>
                <div class="alert alert-error" style="margin-bottom:20px;">
                    Este cliente tiene un saldo pendiente de C$ <?= number_format($cliente['saldo_actual'], 2) ?>. No se puede eliminar hasta saldarlo.
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-actions">
                    <button type="submit" name="confirmar" value="1" class="btn btn-primary" 
                            style="background:#b00020;"
                            <?= ((float)$cliente['saldo_actual'] > 0) ? 'disabled' : '' ?>>
                        Sí, eliminar
                    </button>
                    <a href="index.php" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </main>

</body>
</html>