<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: /AvilesPOSv2/index.php");
    exit;
}

require __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/permisos.php';
requiere_permiso('inventario_registrar');

// Productos activos con departamento
$sql = "SELECT p.id, p.codigo, p.nombre, p.precio_costo, p.precio, p.stock, p.foto, p.id_departamento,
        d.nombre AS departamento_nombre
        FROM productos p
        LEFT JOIN departamentos d ON p.id_departamento = d.id
        WHERE p.activo = 1 
        ORDER BY p.nombre ASC";
$productos = $conexion->query($sql)->fetch_all(MYSQLI_ASSOC);

// Departamentos con al menos un producto
$departamentos = $conexion->query("
    SELECT DISTINCT d.id, d.nombre
    FROM departamentos d
    INNER JOIN productos p ON p.id_departamento = d.id AND p.activo = 1
    WHERE d.activo = 1
    ORDER BY d.nombre ASC
")->fetch_all(MYSQLI_ASSOC);

// Proveedores activos
$proveedores = $conexion->query("SELECT id, nombre FROM proveedores WHERE activo = 1 ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);

$error = $_SESSION['inventario_error'] ?? null;
$old = $_SESSION['inventario_old'] ?? [];
unset($_SESSION['inventario_error'], $_SESSION['inventario_old']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar movimiento - AvilesPOS</title>
    <link rel="stylesheet" href="/AvilesPOSv2/assets/css/estilos.css">
</head>
<body class="dash-body">

    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="page-header">
            <div class="page-header-left">
                <h1>Registrar movimiento</h1>
                <p>Registra una entrada o salida de inventario</p>
            </div>
            <a href="index.php" class="btn btn-secondary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                Volver
            </a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="guardar.php" method="POST" autocomplete="off" id="formMovimiento">
            <input type="hidden" name="id_producto" id="id_producto" value="<?= htmlspecialchars($old['id_producto'] ?? '') ?>">

            <div class="form-container" style="max-width:1100px;">

                <!-- SECCIÓN 1: Producto -->
                <div class="form-section">
                    <div class="form-section-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                        </svg>
                        Producto
                    </div>

                    <div class="inv-selector-producto">

                        <!-- Panel izquierdo: grid -->
                        <div class="inv-prod-panel">
                            <div class="inv-prod-buscador">
                                <svg class="inv-prod-buscador-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                </svg>
                                <input type="text" id="invBuscador" placeholder="Buscar por nombre o código..." autofocus>
                                <button type="button" class="inv-prod-buscador-clear" id="invBuscadorClear" title="Limpiar">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14">
                                        <line x1="18" y1="6" x2="6" y2="18"></line>
                                        <line x1="6" y1="6" x2="18" y2="18"></line>
                                    </svg>
                                </button>
                            </div>

                            <div class="inv-prod-tabs" id="invTabs">
                                <!-- Tabs por JS -->
                            </div>

                            <div class="inv-prod-grid" id="invGrid">
                                <!-- Grid por JS -->
                            </div>
                        </div>

                        <!-- Panel derecho: producto seleccionado -->
                        <div class="inv-prod-seleccionado">
                            <div class="inv-prod-seleccionado-label">Producto seleccionado</div>
                            <div id="invSeleccionado">
                                <div class="inv-prod-vacio-side">
                                    Selecciona un producto<br>del catálogo
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- SECCIÓN 2: Movimiento -->
                <div class="form-section">
                    <div class="form-section-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                            <polyline points="23 4 23 10 17 10"></polyline>
                            <polyline points="1 20 1 14 7 14"></polyline>
                            <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                        </svg>
                        Movimiento
                    </div>

                    <div class="form-grid">
                        <div class="form-group full">
                            <label>Tipo de movimiento <span class="req">*</span></label>
                            <div style="display:flex; gap:20px; padding-top:8px;">
                                <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-weight:500;">
                                    <input type="radio" name="tipo" value="entrada" 
                                           <?= (($old['tipo'] ?? 'entrada') === 'entrada') ? 'checked' : '' ?>>
                                    Entrada (suma stock)
                                </label>
                                <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-weight:500;">
                                    <input type="radio" name="tipo" value="salida"
                                           <?= (($old['tipo'] ?? '') === 'salida') ? 'checked' : '' ?>>
                                    Salida (resta stock)
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="cantidad">Cantidad <span class="req">*</span></label>
                            <input type="number" name="cantidad" id="cantidad" required step="1" min="1"
                                   value="<?= htmlspecialchars($old['cantidad'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label>Resultado</label>
                            <div id="resultado_stock" style="padding:10px 12px; background:#f7f9fb; border:1px solid #eef1f4; border-radius:6px; font-size:14px; color:#666;">
                                Selecciona producto y cantidad
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN 3: Proveedor -->
                <div class="form-section" id="seccion_proveedor">
                    <div class="form-section-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                            <rect x="1" y="3" width="15" height="13"></rect>
                            <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
                            <circle cx="5.5" cy="18.5" r="2.5"></circle>
                            <circle cx="18.5" cy="18.5" r="2.5"></circle>
                        </svg>
                        Proveedor
                    </div>
                    <div class="form-grid">
                        <div class="form-group full">
                            <label for="id_proveedor">Proveedor</label>
                            <select name="id_proveedor" id="id_proveedor">
                                <option value="">— Seleccionar —</option>
                                <?php foreach ($proveedores as $p): ?>
                                    <option value="<?= (int)$p['id'] ?>"
                                        <?= (($old['id_proveedor'] ?? '') == $p['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($p['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <span class="help">Opcional, pero útil para historial de compras</span>
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN 4: Precios -->
                <div class="form-section" id="seccion_precios">
                    <div class="form-section-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                            <line x1="12" y1="1" x2="12" y2="23"></line>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                        </svg>
                        Actualizar precios (opcional)
                    </div>
                    <p style="font-size:12px; color:#888; margin-bottom:16px;">
                        Solo modifica si el proveedor cambió el precio. Si dejas igual, no se actualizan.
                    </p>

                    <div class="form-grid">
                        <div class="form-group">
                            <label>Precio de compra actual</label>
                            <div id="precio_costo_actual" style="padding:10px 12px; background:#f7f9fb; border:1px solid #eef1f4; border-radius:6px; font-size:14px; color:#666;">—</div>
                        </div>
                        <div class="form-group">
                            <label for="precio_costo_nuevo">Precio de compra nuevo</label>
                            <div class="input-suffix">
                                <input type="number" name="precio_costo_nuevo" id="precio_costo_nuevo" step="0.01" min="0"
                                       value="<?= htmlspecialchars($old['precio_costo_nuevo'] ?? '') ?>">
                                <span class="suffix">C$</span>
                            </div>
                            <span class="help">Dejar vacío para no cambiar</span>
                        </div>

                        <div class="form-group">
                            <label>Precio de venta actual</label>
                            <div id="precio_venta_actual" style="padding:10px 12px; background:#f7f9fb; border:1px solid #eef1f4; border-radius:6px; font-size:14px; color:#666;">—</div>
                        </div>
                        <div class="form-group">
                            <label for="precio_venta_nuevo">Precio de venta nuevo</label>
                            <div class="input-suffix">
                                <input type="number" name="precio_venta_nuevo" id="precio_venta_nuevo" step="0.01" min="0"
                                       value="<?= htmlspecialchars($old['precio_venta_nuevo'] ?? '') ?>">
                                <span class="suffix">C$</span>
                            </div>
                            <span class="help">Dejar vacío para no cambiar</span>
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN 5: Comentario -->
                <div class="form-section">
                    <div class="form-section-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                        </svg>
                        Comentario
                    </div>
                    <div class="form-group full">
                        <label for="comentario">
                            Razón del movimiento 
                            <span class="req" id="req_comentario" style="display:none;">*</span>
                        </label>
                        <textarea name="comentario" id="comentario" maxlength="500"
                                  placeholder="Ej: Compra al proveedor, producto dañado, ajuste por conteo físico..."><?= htmlspecialchars($old['comentario'] ?? '') ?></textarea>
                        <span class="help" id="help_comentario">Opcional</span>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Registrar movimiento</button>
                    <a href="index.php" class="btn btn-secondary">Cancelar</a>
                </div>
            </div>
        </form>
            <!-- MODAL: Ver más productos por departamento -->
    <div class="modal-overlay" id="modalVerMasInv">
        <div class="modal modal-xl">
            <div class="modal-header">
                <h3 id="modalVerMasInvTitle">Productos</h3>
                <button type="button" class="modal-close" data-close>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>

            <div class="modal-paginacion" id="modalVerMasInvPaginacion"></div>

            <div class="modal-body" style="max-height:520px; overflow-y:auto;">
                <div class="inv-prod-grid" id="modalVerMasInvGrid" style="max-height:none; padding:0;"></div>
            </div>

            <div class="modal-footer">
                <span style="font-size:12px; color:#888; margin-right:auto;" id="modalVerMasInvInfo"></span>
                <button type="button" class="btn btn-secondary" data-close>Cerrar</button>
            </div>
        </div>
    </div>
    </main>

    <script>
        window.INV_PRODUCTOS = <?= json_encode($productos) ?>;
        window.INV_DEPARTAMENTOS = <?= json_encode($departamentos) ?>;
        window.INV_PRODUCTO_SELECCIONADO = "<?= htmlspecialchars($old['id_producto'] ?? '') ?>";
    </script>
    <script src="/AvilesPOSv2/assets/js/inventario-form.js?v=<?= filemtime(__DIR__ . '/../../assets/js/inventario-form.js') ?>"></script>

</body>
</html>