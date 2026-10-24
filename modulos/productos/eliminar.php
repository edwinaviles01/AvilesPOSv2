<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: /AvilesPOSv2/index.php");
    exit;
}

require __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/permisos.php';
requiere_permiso('productos_eliminar');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: index.php");
    exit;
}

// Cargamos el producto
$stmt = $conexion->prepare("SELECT id, nombre, foto FROM productos WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$producto = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$producto) {
    header("Location: index.php");
    exit;
}

// Si confirma, borramos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar'])) {
    // Borramos la foto del disco
    if ($producto['foto']) {
        $ruta_foto = __DIR__ . '/../../assets/img/productos/' . $producto['foto'];
        if (file_exists($ruta_foto)) {
            unlink($ruta_foto);
        }
    }

    // Borramos los precios adicionales (ON DELETE CASCADE lo haría, pero por claridad)
    // (la FK de producto_precios tiene ON DELETE CASCADE)

    $stmt = $conexion->prepare("DELETE FROM productos WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $_SESSION['producto_mensaje'] = "Producto '{$producto['nombre']}' eliminado.";
        header("Location: index.php");
        exit;
    } else {
        $_SESSION['producto_error'] = "Error al eliminar: " . $stmt->error;
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
    <title>Eliminar producto - AvilesPOS</title>
    <link rel="stylesheet" href="/AvilesPOSv2/assets/css/estilos.css">
</head>
<body class="dash-body">

    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="page-header">
            <div class="page-header-left">
                <h1>Eliminar producto</h1>
                <p>Esta acción no se puede deshacer</p>
            </div>
            <a href="index.php" class="btn btn-secondary">Volver</a>
        </div>

        <div class="form-container" style="max-width:600px;">
            <p style="margin-bottom:20px; font-size:15px; color:#555;">
                ¿Estás seguro que quieres eliminar el producto <strong><?= htmlspecialchars($producto['nombre']) ?></strong>?
            </p>
            <p style="margin-bottom:24px; font-size:13px; color:#888;">
                Si prefieres dejar de venderlo sin borrar el historial, puedes editarlo y desmarcar "Producto activo".
            </p>

            <form method="POST">
                <div class="form-actions">
                    <button type="submit" name="confirmar" value="1" class="btn btn-primary" style="background:#b00020;">
                        Sí, eliminar
                    </button>
                    <a href="index.php" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </main>

</body>
</html>