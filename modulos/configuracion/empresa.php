<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: /AvilesPOSv2/index.php");
    exit;
}

require __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/permisos.php';
requiere_permiso('config_empresa');

// Cargar configuración actual
$config = [];
$res = $conexion->query("SELECT clave, valor FROM configuracion");
while ($row = $res->fetch_assoc()) {
    $config[$row['clave']] = $row['valor'];
}

$mensaje = $_SESSION['empresa_mensaje'] ?? null;
$error = $_SESSION['empresa_error'] ?? null;
unset($_SESSION['empresa_mensaje'], $_SESSION['empresa_error']);

// Valores actuales con defaults
$empresa_nombre    = $config['empresa_nombre'] ?? '';
$empresa_ruc       = $config['empresa_ruc'] ?? '';
$empresa_direccion = $config['empresa_direccion'] ?? '';
$empresa_telefono  = $config['empresa_telefono'] ?? '';
$empresa_email     = $config['empresa_email'] ?? '';
$moneda            = $config['moneda'] ?? 'C$';
$iva_porcentaje    = $config['iva_porcentaje'] ?? '15';
$tipo_cambio_usd   = $config['tipo_cambio_usd'] ?? '36.50';
$empresa_logo      = $config['empresa_logo'] ?? '';

// ¿Existe el logo?
$tiene_logo = $empresa_logo !== '' && file_exists(__DIR__ . '/../../assets/img/logos/' . $empresa_logo);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Empresa - AvilesPOS</title>
    <link rel="stylesheet" href="/AvilesPOSv2/assets/css/estilos.css">
</head>
<body class="dash-body">

    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="page-header">
            <div class="page-header-left">
                <h1>Configuración</h1>
                <p>Administra los parámetros del sistema</p>
            </div>
            <div class="page-header-tabs">
                <?php include __DIR__ . '/includes/tabs.php'; ?>
            </div>
        </div>

        <?php if ($mensaje): ?>
            <div class="alert alert-success"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="guardar_empresa.php" method="POST" enctype="multipart/form-data" autocomplete="off">
            <div class="form-layout">

                <!-- Columna izquierda: datos -->
                <div class="form-container">

                    <!-- Sección: Datos de la empresa -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                                <path d="M3 21h18"></path>
                                <path d="M5 21V7l8-4v18"></path>
                                <path d="M19 21V11l-6-4"></path>
                            </svg>
                            Datos de la empresa
                        </div>
                        <div class="form-grid">

                            <div class="form-group full">
                                <label for="empresa_nombre">Nombre del negocio <span class="req">*</span></label>
                                <input type="text" name="empresa_nombre" id="empresa_nombre" required maxlength="150"
                                       value="<?= htmlspecialchars($empresa_nombre) ?>"
                                       placeholder="Ej: Distribuidora Avilés">
                                <span class="help">Este nombre aparecerá en los tickets</span>
                            </div>

                            <div class="form-group">
                                <label for="empresa_ruc">RUC</label>
                                <input type="text" name="empresa_ruc" id="empresa_ruc" maxlength="30"
                                       value="<?= htmlspecialchars($empresa_ruc) ?>"
                                       placeholder="Ej: J0310000000000">
                            </div>

                            <div class="form-group">
                                <label for="empresa_telefono">Teléfono</label>
                                <input type="text" name="empresa_telefono" id="empresa_telefono" maxlength="30"
                                       value="<?= htmlspecialchars($empresa_telefono) ?>"
                                       placeholder="Ej: 8888-1111">
                            </div>

                            <div class="form-group full">
                                <label for="empresa_email">Email</label>
                                <input type="text" name="empresa_email" id="empresa_email" maxlength="150"
                                       value="<?= htmlspecialchars($empresa_email) ?>"
                                       placeholder="Ej: contacto@negocio.com">
                            </div>

                            <div class="form-group full">
                                <label for="empresa_direccion">Dirección</label>
                                <input type="text" name="empresa_direccion" id="empresa_direccion" maxlength="300"
                                       value="<?= htmlspecialchars($empresa_direccion) ?>"
                                       placeholder="Ej: De la rotonda 2c al sur, Managua">
                            </div>

                        </div>
                    </div>

                    <!-- Sección: Configuración general -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                                <circle cx="12" cy="12" r="3"></circle>
                                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                            </svg>
                            Configuración general
                        </div>
                        <div class="form-grid">

                            <div class="form-group">
                                <label for="moneda">Símbolo de moneda <span class="req">*</span></label>
                                <input type="text" name="moneda" id="moneda" required maxlength="5"
                                       value="<?= htmlspecialchars($moneda) ?>"
                                       placeholder="C$">
                            </div>

                            <div class="form-group">
                                <label for="iva_porcentaje">IVA (%) <span class="req">*</span></label>
                                <div class="input-suffix">
                                    <input type="number" name="iva_porcentaje" id="iva_porcentaje" required 
                                           step="0.01" min="0" max="100"
                                           value="<?= htmlspecialchars($iva_porcentaje) ?>">
                                    <span class="suffix">%</span>
                                </div>
                                <span class="help">Impuesto aplicado a las ventas</span>
                            </div>

                            <div class="form-group full">
                                <label for="tipo_cambio_usd">Tipo de cambio (USD → C$) <span class="req">*</span></label>
                                <div class="input-suffix">
                                    <input type="number" name="tipo_cambio_usd" id="tipo_cambio_usd" required
                                           step="0.0001" min="0"
                                           value="<?= htmlspecialchars($tipo_cambio_usd) ?>">
                                    <span class="suffix">C$</span>
                                </div>
                                <span class="help">Cuántos córdobas vale 1 dólar actualmente</span>
                            </div>

                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Guardar cambios</button>
                    </div>

                </div>

                <!-- Columna derecha: Logo -->
                <div class="form-side">
                    <div class="side-card">
                        <h3>Logo de la empresa</h3>

                        <label for="logo" class="foto-preview" id="logoPreview">
                            <?php if ($tiene_logo): ?>
                                <img src="/AvilesPOSv2/assets/img/logos/<?= htmlspecialchars($empresa_logo) ?>" alt="Logo actual">
                            <?php else: ?>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" width="40" height="40">
                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                    <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                    <polyline points="21 15 16 10 5 21"></polyline>
                                </svg>
                                <span>Haz clic para subir</span>
                            <?php endif; ?>
                            <input type="file" name="logo" id="logo" accept="image/*">
                        </label>

                        <p class="foto-hint">PNG o JPG, máx 2MB</p>

                        <p class="foto-hint" style="margin-top:10px; color:#666; text-align:left; line-height:1.5;">
                            Este logo aparecerá en el ticket. Si no subes uno, se usa el del sistema.
                        </p>
                    </div>
                </div>

            </div>
        </form>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const input = document.getElementById('logo');
            const preview = document.getElementById('logoPreview');

            if (input && preview) {
                input.addEventListener('change', function () {
                    const file = this.files[0];
                    if (!file) return;

                    if (file.size > 2 * 1024 * 1024) {
                        alert('La imagen no debe pesar más de 2MB');
                        input.value = '';
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = function (e) {
                        preview.innerHTML = '<img src="' + e.target.result + '" alt="Nuevo logo">';
                        preview.appendChild(input);
                    };
                    reader.readAsDataURL(file);
                });
            }
        });
    </script>

</body>
</html>