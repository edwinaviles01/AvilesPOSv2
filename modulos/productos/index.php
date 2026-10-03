<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: /AvilesPOSv2/index.php");
    exit;
}

require __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/permisos.php';
requiere_permiso('productos_ver');

// Cargar todos los productos activos e inactivos
$sql = "SELECT p.id, p.codigo, p.nombre, p.foto, p.precio, p.precio_costo, 
               p.stock, p.stock_minimo, p.activo,
               p.id_departamento,
               d.nombre AS departamento,
               c.nombre AS categoria,
               pr.nombre AS proveedor
        FROM productos p
        LEFT JOIN departamentos d ON p.id_departamento = d.id
        LEFT JOIN categorias c ON p.id_categoria = c.id
        LEFT JOIN proveedores pr ON p.id_proveedor = pr.id
        ORDER BY p.nombre ASC";
$productos = $conexion->query($sql)->fetch_all(MYSQLI_ASSOC);

// Departamentos activos
$departamentos = $conexion->query("
    SELECT id, nombre FROM departamentos WHERE activo = 1 ORDER BY nombre ASC
")->fetch_all(MYSQLI_ASSOC);

$mensaje_exito = $_SESSION['producto_mensaje'] ?? null;
unset($_SESSION['producto_mensaje']);

// Contar productos sin departamento para mostrarlos en su propia sección
$sin_depto_count = 0;
foreach ($productos as $p) {
    if (empty($p['id_departamento'])) $sin_depto_count++;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos - AvilesPOS</title>
    <link rel="stylesheet" href="/AvilesPOSv2/assets/css/estilos.css">
</head>
<body class="dash-body">

    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="page-header">
            <div class="page-header-left">
                <h1>Productos</h1>
                <p>Administra el catálogo de productos del sistema</p>
            </div>
            <a href="crear.php" class="btn btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Nuevo producto
            </a>
        </div>

        <?php if ($mensaje_exito): ?>
            <div class="alert alert-success"><?= htmlspecialchars($mensaje_exito) ?></div>
        <?php endif; ?>

        <?php if (count($productos) === 0): ?>

            <div class="empty-state">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" width="64" height="64">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                    <line x1="12" y1="22.08" x2="12" y2="12"></line>
                </svg>
                <h3>No hay productos todavía</h3>
                <p>Empieza agregando tu primer producto al catálogo</p>
                <a href="crear.php" class="btn btn-primary">Agregar producto</a>
            </div>

        <?php else: ?>

            <!-- Buscador -->
            <div class="prod-buscador">
                <svg class="prod-buscador-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="text" id="prodBuscador" placeholder="Buscar por nombre, código o escanea...">
                <button type="button" class="prod-buscador-clear" id="prodBuscadorClear" title="Limpiar búsqueda">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>

            <!-- Tabs de departamentos -->
            <div class="prod-depto-tabs" id="prodDeptoTabs">
                <!-- Renderizado por JS -->
            </div>

            <!-- Contenedor de secciones -->
            <div id="prodContenedor">
                <!-- Renderizado por JS -->
            </div>

        <?php endif; ?>
    </main>

    <!-- MODAL: Ver todos los productos de un departamento -->
    <div class="modal-overlay" id="modalDeptoProductos">
        <div class="modal modal-xl">
            <div class="modal-header">
                <h3 id="modalDeptoTitle">Productos</h3>
                <button type="button" class="modal-close" data-close>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>

            <!-- Tabs de páginas -->
            <div class="modal-paginacion" id="modalDeptoPaginacion"></div>

            <div class="modal-body" style="max-height:520px; overflow-y:auto;">
                <div class="productos-grid" id="modalDeptoGrid"></div>
            </div>

            <div class="modal-footer">
                <span style="font-size:12px; color:#888; margin-right:auto;" id="modalDeptoInfo"></span>
                <button type="button" class="btn btn-secondary" data-close>Cerrar</button>
            </div>
        </div>
    </div>

    <script>
        window.PRODUCTOS_LISTA = <?= json_encode($productos) ?>;
        window.DEPARTAMENTOS_LISTA = <?= json_encode($departamentos) ?>;
        window.MONEDA = "<?= $config_moneda ?? 'C$' ?>";
    </script>
    <script src="/AvilesPOSv2/assets/js/productos-lista.js?v=<?= filemtime(__DIR__ . '/../../assets/js/productos-lista.js') ?>"></script>

</body>
</html>