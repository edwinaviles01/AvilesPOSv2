<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: /AvilesPOSv2/index.php");
    exit;
}

require __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/permisos.php';
requiere_permiso('productos_crear');

$departamentos = $conexion->query("SELECT id, nombre FROM departamentos WHERE activo = 1 ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);
$categorias    = $conexion->query("SELECT id, id_departamento, nombre FROM categorias WHERE activo = 1 ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);
$proveedores   = $conexion->query("SELECT id, nombre, contacto, telefono FROM proveedores WHERE activo = 1 ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);

$old = $_SESSION['producto_old'] ?? [];
$error = $_SESSION['producto_error'] ?? null;
unset($_SESSION['producto_old'], $_SESSION['producto_error']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo producto - AvilesPOS</title>
    <link rel="stylesheet" href="/AvilesPOSv2/assets/css/estilos.css">
</head>
<body class="dash-body">

    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="main-content">
        
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

        <div class="page-header">
            <div class="page-header-left">
                <h1>Nuevo producto</h1>
                <p>Ingresa los datos del producto que deseas agregar</p>
            </div>
            <a href="index.php" class="btn btn-secondary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                Volver
            </a>
        </div>
            <form action="guardar.php" method="POST" enctype="multipart/form-data" autocomplete="off" id="formProducto">
                <div class="form-layout">

                    <div class="form-container">

                        <div class="form-section">
                            <div class="form-section-title">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="12" y1="16" x2="12" y2="12"></line>
                                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                                </svg>
                                Información básica
                            </div>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="codigo">Código / Código de barras <span class="req">*</span></label>
                                    <input type="text" name="codigo" id="codigo" required maxlength="50"
                                           value="<?= htmlspecialchars($old['codigo'] ?? '') ?>">
                                </div>
                                <div class="form-group">
                                    <label for="nombre">Nombre <span class="req">*</span></label>
                                    <input type="text" name="nombre" id="nombre" required maxlength="150"
                                           value="<?= htmlspecialchars($old['nombre'] ?? '') ?>">
                                </div>
                                <div class="form-group full">
                                    <label for="descripcion">Descripción</label>
                                    <textarea name="descripcion" id="descripcion" maxlength="500"><?= htmlspecialchars($old['descripcion'] ?? '') ?></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <div class="form-section-title">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                                    <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                                </svg>
                                Clasificación
                            </div>
                            <div class="form-grid">
                                <div class="form-group">
                                    <div class="label-row">
                                        <label for="id_departamento">Departamento</label>
                                        <button type="button" class="btn-add-mini" data-modal="departamento" title="Crear departamento">+</button>
                                    </div>
                                    <select name="id_departamento" id="id_departamento">
                                        <option value="">— Seleccionar —</option>
                                        <?php foreach ($departamentos as $d): ?>
                                            <option value="<?= (int)$d['id'] ?>"
                                                <?= (($old['id_departamento'] ?? '') == $d['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($d['nombre']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <div class="label-row">
                                        <label for="id_categoria">Categoría</label>
                                        <button type="button" class="btn-add-mini" data-modal="categoria" title="Crear categoría">+</button>
                                    </div>
                                    <select name="id_categoria" id="id_categoria">
                                        <option value="">— Seleccionar departamento primero —</option>
                                    </select>
                                </div>
                                <div class="form-group full">
                                    <div class="label-row">
                                        <label for="id_proveedor">Proveedor</label>
                                        <button type="button" class="btn-add-mini" data-modal="proveedor" title="Crear proveedor">+</button>
                                    </div>
                                    <select name="id_proveedor" id="id_proveedor">
                                        <option value="">— Seleccionar —</option>
                                        <?php foreach ($proveedores as $p): ?>
                                            <option value="<?= (int)$p['id'] ?>"
                                                <?= (($old['id_proveedor'] ?? '') == $p['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($p['nombre']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <div class="form-section-title">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                                    <line x1="12" y1="1" x2="12" y2="23"></line>
                                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                                </svg>
                                Precios
                            </div>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="precio_costo">Precio de compra <span class="req">*</span></label>
                                    <div class="input-suffix">
                                        <input type="number" name="precio_costo" id="precio_costo" required step="0.01" min="0"
                                               value="<?= htmlspecialchars($old['precio_costo'] ?? '') ?>">
                                        <span class="suffix">C$</span>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="porcentaje_ganancia">% Ganancia</label>
                                    <div class="input-suffix">
                                        <input type="number" name="porcentaje_ganancia" id="porcentaje_ganancia" step="0.01" min="0"
                                               value="<?= htmlspecialchars($old['porcentaje_ganancia'] ?? '') ?>">
                                        <span class="suffix">%</span>
                                    </div>
                                </div>
                                <div class="form-group full">
                                    <label for="precio">Precio de venta <span class="req">*</span></label>
                                    <div class="input-suffix">
                                        <input type="number" name="precio" id="precio" required step="0.01" min="0"
                                               value="<?= htmlspecialchars($old['precio'] ?? '') ?>">
                                        <span class="suffix">C$</span>
                                    </div>
                                    <span class="help">Este precio ya incluye IVA (15%)</span>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <div class="form-section-title">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                                </svg>
                                Inventario
                            </div>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="stock">Stock actual <span class="req">*</span></label>
                                    <input type="number" name="stock" id="stock" required step="1" min="0"
                                           value="<?= htmlspecialchars($old['stock'] ?? '0') ?>">
                                </div>
                                <div class="form-group">
                                    <label for="stock_minimo">Stock mínimo</label>
                                    <input type="number" name="stock_minimo" id="stock_minimo" step="1" min="0"
                                           value="<?= htmlspecialchars($old['stock_minimo'] ?? '0') ?>">
                                    <span class="help">Se avisará cuando baje de este valor</span>
                                </div>
                                <div class="form-group full">
                                    <div class="form-check">
                                        <input type="checkbox" name="activo" id="activo" value="1" checked>
                                        <label for="activo">Producto activo (visible para la venta)</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Guardar producto</button>
                            <a href="index.php" class="btn btn-secondary">Cancelar</a>
                        </div>
                    </div>

                    <div class="form-side">

                        <div class="side-card">
                            <h3>Foto del producto</h3>
                            <label for="foto" class="foto-preview" id="fotoPreview">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" width="40" height="40">
                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                    <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                    <polyline points="21 15 16 10 5 21"></polyline>
                                </svg>
                                <span>Haz clic para subir</span>
                                <input type="file" name="foto" id="foto" accept="image/*">
                            </label>
                            <button type="button" class="btn-quitar-foto" id="btnQuitarFoto">Quitar foto</button>
                            <p class="foto-hint">JPG o PNG, máx 2MB</p>
                        </div>

                        <div class="side-card">
                            <h3>Código de barras</h3>
                            <div class="barcode-preview">
                                <svg id="barcodeSvg" viewBox="0 0 200 60" preserveAspectRatio="none"></svg>
                                <div class="barcode-text" id="barcodeTexto">—</div>
                            </div>
                        </div>

                    </div>

                </div>
            </form>

        </div>
    </main>

    <script>
        window.DEPARTAMENTOS = <?= json_encode($departamentos) ?>;
        window.CATEGORIAS = <?= json_encode($categorias) ?>;
        window.PROVEEDORES = <?= json_encode($proveedores) ?>;
        window.CATEGORIA_SELECCIONADA = "<?= htmlspecialchars($old['id_categoria'] ?? '') ?>";
    </script>
    <script src="/AvilesPOSv2/assets/js/producto-form.js?v=3"></script>
    <script src="/AvilesPOSv2/assets/js/producto-modales.js?v=5"></script>
    
     <!-- MODAL: Departamentos -->
    <div class="modal-overlay" id="modalDepartamento">
        <div class="modal">
            <div class="modal-header">
                <h3 id="modalDeptoTitle">Departamentos</h3>
                <button type="button" class="modal-close" data-close>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>

            <!-- Vista LISTA -->
            <div class="modal-view" id="modalDeptoLista">
                <div class="modal-body">
                    <button type="button" class="btn btn-primary btn-block" id="btnNuevoDepto">
                        + Nuevo departamento
                    </button>
                    <div class="lista-items" id="listaDepartamentos"></div>
                </div>
            </div>

            <!-- Vista FORMULARIO -->
            <div class="modal-view" id="modalDeptoForm" style="display:none;">
                <form id="formModalDepartamento">
                    <input type="hidden" name="id" id="depto_id">
                    <div class="modal-body">
                        <div class="modal-error" id="errorDepartamento"></div>
                        <div class="form-group">
                            <label for="depto_nombre">Nombre <span class="req">*</span></label>
                            <input type="text" id="depto_nombre" name="nombre" required maxlength="100">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" id="btnVolverDepto">Volver</button>
                        <button type="submit" class="btn btn-primary" id="btnGuardarDepto">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL: Categorías -->
    <div class="modal-overlay" id="modalCategoria">
        <div class="modal">
            <div class="modal-header">
                <h3 id="modalCatTitle">Categorías</h3>
                <button type="button" class="modal-close" data-close>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>

            <!-- Vista LISTA -->
            <div class="modal-view" id="modalCatLista">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="filtro_categoria_depto">Departamento</label>
                        <select id="filtro_categoria_depto"></select>
                    </div>
                    <button type="button" class="btn btn-primary btn-block" id="btnNuevaCat">
                        + Nueva categoría
                    </button>
                    <div class="lista-items" id="listaCategorias"></div>
                </div>
            </div>

            <!-- Vista FORMULARIO -->
            <div class="modal-view" id="modalCatForm" style="display:none;">
                <form id="formModalCategoria">
                    <input type="hidden" name="id" id="cat_id">
                    <div class="modal-body">
                        <div class="modal-error" id="errorCategoria"></div>
                        <div class="form-group">
                            <label for="cat_id_departamento">Departamento <span class="req">*</span></label>
                            <select id="cat_id_departamento" name="id_departamento" required></select>
                        </div>
                        <div class="form-group">
                            <label for="cat_nombre">Nombre <span class="req">*</span></label>
                            <input type="text" id="cat_nombre" name="nombre" required maxlength="100">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" id="btnVolverCat">Volver</button>
                        <button type="submit" class="btn btn-primary" id="btnGuardarCat">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL: Proveedores -->
    <div class="modal-overlay" id="modalProveedor">
        <div class="modal">
            <div class="modal-header">
                <h3 id="modalProvTitle">Proveedores</h3>
                <button type="button" class="modal-close" data-close>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>

            <!-- Vista LISTA -->
            <div class="modal-view" id="modalProvLista">
                <div class="modal-body">
                    <button type="button" class="btn btn-primary btn-block" id="btnNuevoProv">
                        + Nuevo proveedor
                    </button>
                    <div class="lista-items" id="listaProveedores"></div>
                </div>
            </div>

            <!-- Vista FORMULARIO -->
            <div class="modal-view" id="modalProvForm" style="display:none;">
                <form id="formModalProveedor">
                    <input type="hidden" name="id" id="prov_id">
                    <div class="modal-body">
                        <div class="modal-error" id="errorProveedor"></div>
                        <div class="form-group">
                            <label for="prov_nombre">Nombre <span class="req">*</span></label>
                            <input type="text" id="prov_nombre" name="nombre" required maxlength="150">
                        </div>
                        <div class="form-group">
                            <label for="prov_contacto">Contacto</label>
                            <input type="text" id="prov_contacto" name="contacto" maxlength="150">
                        </div>
                        <div class="form-group">
                            <label for="prov_telefono">Teléfono</label>
                            <input type="text" id="prov_telefono" name="telefono" maxlength="30">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" id="btnVolverProv">Volver</button>
                        <button type="submit" class="btn btn-primary" id="btnGuardarProv">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</body>
</html>