<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: /AvilesPOSv2/index.php");
    exit;
}

require __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/permisos.php';
requiere_permiso('config_bd');

$mensaje = $_SESSION['bd_mensaje'] ?? null;
$error = $_SESSION['bd_error'] ?? null;
unset($_SESSION['bd_mensaje'], $_SESSION['bd_error']);

// Obtener tamaño aproximado de la BD
$res_db = $conexion->query("
    SELECT 
        ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb,
        COUNT(*) AS num_tablas
    FROM information_schema.TABLES 
    WHERE table_schema = DATABASE()
")->fetch_assoc();
$db_size = (float)($res_db['size_mb'] ?? 0);
$db_tablas = (int)($res_db['num_tablas'] ?? 0);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Base de datos - AvilesPOS</title>
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

        <!-- Info de la BD -->
        <div class="bd-info-box">
            <div class="bd-info-item">
                <div class="bd-info-label">Tamaño actual</div>
                <div class="bd-info-valor"><?= $db_size ?> MB</div>
            </div>
            <div class="bd-info-item">
                <div class="bd-info-label">Tablas</div>
                <div class="bd-info-valor"><?= $db_tablas ?></div>
            </div>
            <div class="bd-info-item">
                <div class="bd-info-label">Último respaldo</div>
                <div class="bd-info-valor" style="font-size:14px;">
                    <?php
                    $ultimo = $conexion->query("SELECT valor FROM configuracion WHERE clave = 'ultimo_backup' LIMIT 1")->fetch_assoc();
                    if ($ultimo && $ultimo['valor']) {
                        echo date('d/m/Y H:i', strtotime($ultimo['valor']));
                    } else {
                        echo 'Nunca';
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- Layout 2 columnas -->
        <div class="bd-grid">

            <!-- Columna: Crear respaldo -->
            <div class="bd-card">
                <div class="bd-card-header">
                    <div class="bd-card-icon verde">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="24" height="24">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7 10 12 15 17 10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                        </svg>
                    </div>
                    <h3>Crear respaldo</h3>
                </div>
                <p class="bd-card-desc">
                    Descarga un archivo <code>.sql</code> con toda la información del sistema:
                    productos, ventas, clientes, usuarios y configuración.
                </p>
                <button type="button" class="btn btn-primary" id="btnDescargar" style="width:100%; background:#1e7a3f;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="7 10 12 15 17 10"></polyline>
                        <line x1="12" y1="15" x2="12" y2="3"></line>
                    </svg>
                    Descargar respaldo
                </button>
                <p class="bd-card-hint">
                    Recomendado: guarda una copia antes de hacer cambios importantes.
                </p>
            </div>

            <!-- Columna: Restaurar respaldo -->
            <div class="bd-card">
                <div class="bd-card-header">
                    <div class="bd-card-icon rojo">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="24" height="24">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="17 8 12 3 7 8"></polyline>
                            <line x1="12" y1="3" x2="12" y2="15"></line>
                        </svg>
                    </div>
                    <h3>Restaurar respaldo</h3>
                </div>
                <div class="bd-warning">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                        <line x1="12" y1="9" x2="12" y2="13"></line>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                    <span>Esto <strong>borrará todos los datos actuales</strong> y los reemplazará con los del archivo.</span>
                </div>

                <input type="file" id="inputArchivo" accept=".sql" style="display:none;">
                <button type="button" class="btn btn-secondary" id="btnSeleccionarArchivo" style="width:100%;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="17 8 12 3 7 8"></polyline>
                        <line x1="12" y1="3" x2="12" y2="15"></line>
                    </svg>
                    <span id="archivoNombre">Seleccionar archivo .sql</span>
                </button>

                <div id="archivoSeleccionado" style="display:none; margin-top:10px;">
                    <div class="bd-archivo-info">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                        </svg>
                        <span id="archivoTamaño"></span>
                    </div>
                    <button type="button" class="btn btn-primary" id="btnRestaurar" style="width:100%; background:#b00020; margin-top:8px;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                            <line x1="12" y1="9" x2="12" y2="13"></line>
                            <line x1="12" y1="17" x2="12.01" y2="17"></line>
                        </svg>
                        Restaurar base de datos
                    </button>
                </div>

                <p class="bd-card-hint">
                    Solo archivos <code>.sql</code> generados por este sistema.
                </p>
            </div>

        </div>

        <!-- Sección: Reiniciar a valores de fábrica -->
        <div class="bd-card" style="margin-top: 24px; border-left: 4px solid #b00020;">
            <div class="bd-card-header">
                <div class="bd-card-icon rojo">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="24" height="24">
                        <path d="M3 6h18"></path>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg>
                </div>
                <h3 style="color: #b00020;">Reiniciar sistema (Valores de fábrica)</h3>
            </div>
            <p class="bd-card-desc" style="max-width: 800px;">
                Esta opción eliminará <strong>absolutamente todos los datos</strong> del sistema: productos, ventas, clientes, historial, movimientos, etc. 
                La base de datos quedará limpia y solo conservará el usuario administrador y los roles básicos del sistema.
            </p>
            <button type="button" class="btn btn-primary" id="btnAbrirReset" style="background:#b00020;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                    <path d="M3 6h18"></path>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                </svg>
                Borrar todos los datos y reiniciar
            </button>
        </div>

        <div class="modal-error" id="bdError" style="max-width:600px; margin-top:20px;"></div>

    </main>

    <!-- MODAL: Confirmar restauración -->
    <div class="modal-overlay" id="modalConfirmarRestaurar">
        <div class="modal" style="max-width:460px;">
            <div class="modal-header">
                <h3>Confirmar restauración</h3>
                <button type="button" class="modal-close" id="btnCancelarRestaurarX">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <div class="modal-body" style="text-align:center; padding:24px;">
                <div style="width:56px; height:56px; margin:0 auto 16px; border-radius:50%; background:#ffe0e0; display:flex; align-items:center; justify-content:center;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#b00020" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="28" height="28">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                        <line x1="12" y1="9" x2="12" y2="13"></line>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                </div>
                <p style="font-size:15px; color:#333; font-weight:600; line-height:1.5; margin-bottom:8px;">
                    ¿Restaurar la base de datos?
                </p>
                <p style="font-size:13px; color:#666; line-height:1.6;">
                    Esto <strong>borrará todos los datos actuales</strong> y los reemplazará por los del archivo seleccionado.<br><br>
                    Esta acción no se puede deshacer.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="btnCancelarRestaurar">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnConfirmarRestaurar" style="background:#b00020;">
                    Sí, restaurar
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL: Éxito de restauración -->
    <div class="modal-overlay" id="modalRestaurarExito">
        <div class="modal" style="max-width:420px;">
            <div class="modal-header">
                <h3>Restauración exitosa</h3>
                <button type="button" class="modal-close" id="btnCerrarExitoX">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <div class="modal-body" style="text-align:center; padding:28px 24px;">
                <div style="width:64px; height:64px; margin:0 auto 16px; border-radius:50%; background:#e0f5e8; display:flex; align-items:center; justify-content:center;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#1e7a3f" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="32" height="32">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                </div>
                <p style="font-size:16px; color:#333; font-weight:600; line-height:1.5; margin-bottom:8px;">
                    ¡Base de datos restaurada!
                </p>
                <p style="font-size:13px; color:#666; line-height:1.6;">
                    Los datos han sido reemplazados correctamente por los del respaldo.
                </p>
            </div>
            <div class="modal-footer" style="justify-content:center;">
                <button type="button" class="btn btn-primary" id="btnCerrarExito" style="background:#1e7a3f; min-width: 120px;">
                    Entendido
                </button>
            </div>
        </div>
    </div>

        <!-- MODAL: Confirmar Reinicio Total -->
    <div class="modal-overlay" id="modalResetBD">
        <div class="modal" style="max-width:480px;">
            <div class="modal-header" style="background: #ffe0e0;">
                <h3 style="color: #b00020;">⚠️ PELIGRO: Borrar todo el sistema</h3>
                <button type="button" class="modal-close" id="btnCancelarResetX">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <div class="modal-body" style="padding:24px;">
                <p style="font-size:14px; color:#333; line-height:1.6; margin-bottom:16px;">
                    Estás a punto de <strong>borrar permanentemente todos los datos</strong> de tu sistema. Esta acción no se puede deshacer.
                </p>
                <p style="font-size:13px; color:#666; line-height:1.6; margin-bottom:16px;">
                    Si estás seguro, escribe la palabra <strong>REINICIAR</strong> en el campo de abajo para habilitar el botón rojo.
                </p>
                
                <div class="form-group" style="margin-bottom: 0;">
                    <input type="text" id="inputConfirmReset" placeholder="Escribe REINICIAR aquí..." style="text-align:center; font-size:16px; font-weight:bold; text-transform:uppercase; padding: 12px;">
                </div>
                
                <div id="resetError" class="modal-error" style="margin-top:16px; display:none;"></div>
            </div>
            <div class="modal-footer" style="justify-content:center;">
                <button type="button" class="btn btn-secondary" id="btnCancelarReset">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnEjecutarReset" style="background:#b00020; opacity: 0.5; cursor: not-allowed;" disabled>
                    Borrar todo y reiniciar
                </button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const btnDescargar = document.getElementById('btnDescargar');
            const btnSeleccionarArchivo = document.getElementById('btnSeleccionarArchivo');
            const inputArchivo = document.getElementById('inputArchivo');
            const archivoNombre = document.getElementById('archivoNombre');
            const archivoSeleccionado = document.getElementById('archivoSeleccionado');
            const archivoTamaño = document.getElementById('archivoTamaño');
            const btnRestaurar = document.getElementById('btnRestaurar');
            const bdError = document.getElementById('bdError');

                        // 0. Detectar si venimos de una restauración exitosa
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('restaurado') === '1') {
                const modalExito = document.getElementById('modalRestaurarExito');
                if (modalExito) modalExito.classList.add('visible');
            }

            // Lógica para cerrar el modal de éxito
            const modalRestaurarExito = document.getElementById('modalRestaurarExito');
            const btnCerrarExito = document.getElementById('btnCerrarExito');
            const btnCerrarExitoX = document.getElementById('btnCerrarExitoX');

            function cerrarModalExito() {
                if (modalRestaurarExito) modalRestaurarExito.classList.remove('visible');
                // Limpiar la URL para que al recargar no vuelva a salir
                window.history.replaceState({}, document.title, window.location.pathname);
            }

            if (btnCerrarExito) btnCerrarExito.addEventListener('click', cerrarModalExito);
            if (btnCerrarExitoX) btnCerrarExitoX.addEventListener('click', cerrarModalExito);

            // Elementos del modal
            const modalConfirmarRestaurar = document.getElementById('modalConfirmarRestaurar');
            const btnCancelarRestaurar = document.getElementById('btnCancelarRestaurar');
            const btnCancelarRestaurarX = document.getElementById('btnCancelarRestaurarX');
            const btnConfirmarRestaurar = document.getElementById('btnConfirmarRestaurar');

            // 1. Descargar respaldo
            btnDescargar.addEventListener('click', function () {
                bdError.textContent = '';
                btnDescargar.disabled = true;
                btnDescargar.innerHTML = 'Generando respaldo...';

                window.location.href = '/AvilesPOSv2/modulos/configuracion/api/backup.php';

                setTimeout(function () {
                    btnDescargar.disabled = false;
                    btnDescargar.innerHTML = `
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7 10 12 15 17 10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                        </svg>
                        Descargar respaldo
                    `;
                }, 3000);
            });

            // 2. Seleccionar archivo (El clic que no funcionaba)
            btnSeleccionarArchivo.addEventListener('click', function () {
                inputArchivo.click();
            });

            inputArchivo.addEventListener('change', function () {
                const file = this.files[0];
                if (!file) return;

                bdError.textContent = '';

                archivoNombre.textContent = file.name;
                const sizeMB = (file.size / 1024 / 1024).toFixed(2);
                archivoTamaño.textContent = file.name + ' (' + sizeMB + ' MB)';
                archivoSeleccionado.style.display = 'block';
            });

            // 3. Abrir modal de confirmación
            btnRestaurar.addEventListener('click', function () {
                const file = inputArchivo.files[0];
                if (!file) return;
                modalConfirmarRestaurar.classList.add('visible');
            });

            // 4. Cerrar modal
            function cerrarModalConfirmar() {
                modalConfirmarRestaurar.classList.remove('visible');
            }

            btnCancelarRestaurar.addEventListener('click', cerrarModalConfirmar);
            btnCancelarRestaurarX.addEventListener('click', cerrarModalConfirmar);

            // 5. Ejecutar restauración desde el modal
            btnConfirmarRestaurar.addEventListener('click', function () {
                const file = inputArchivo.files[0];
                if (!file) return;

                cerrarModalConfirmar();
                bdError.textContent = '';
                btnRestaurar.disabled = true;
                btnRestaurar.innerHTML = 'Restaurando... (no cierres la ventana)';
                btnConfirmarRestaurar.disabled = true;

                const formData = new FormData();
                formData.append('archivo', file);

                fetch('/AvilesPOSv2/modulos/configuracion/api/restaurar.php', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(data => {
                    if (data.ok) {
                        // Recargar indicando éxito por la URL
                        window.location.href = '/AvilesPOSv2/modulos/configuracion/bd.php?restaurado=1';
                    } else {
                        bdError.textContent = data.error || 'Error al restaurar.';
                        btnRestaurar.disabled = false;
                        btnRestaurar.innerHTML = `
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                                <line x1="12" y1="9" x2="12" y2="13"></line>
                                <line x1="12" y1="17" x2="12.01" y2="17"></line>
                            </svg>
                            Restaurar base de datos
                        `;
                        btnConfirmarRestaurar.disabled = false;
                    }
                })
                .catch(err => {
                    bdError.textContent = 'Error de conexión: ' + err.message;
                    btnRestaurar.disabled = false;
                    btnRestaurar.innerHTML = 'Restaurar base de datos';
                    btnConfirmarRestaurar.disabled = false;
                });
            });

                        // ==========================================
            // LÓGICA PARA REINICIAR LA BASE DE DATOS
            // ==========================================
            const btnAbrirReset = document.getElementById('btnAbrirReset');
            const modalResetBD = document.getElementById('modalResetBD');
            const btnCancelarResetX = document.getElementById('btnCancelarResetX');
            const btnCancelarReset = document.getElementById('btnCancelarReset');
            const inputConfirmReset = document.getElementById('inputConfirmReset');
            const btnEjecutarReset = document.getElementById('btnEjecutarReset');
            const resetError = document.getElementById('resetError');

            function abrirReset() {
                inputConfirmReset.value = '';
                btnEjecutarReset.disabled = true;
                btnEjecutarReset.style.opacity = '0.5';
                btnEjecutarReset.style.cursor = 'not-allowed';
                resetError.style.display = 'none';
                modalResetBD.classList.add('visible');
                setTimeout(() => inputConfirmReset.focus(), 100);
            }

            function cerrarReset() {
                modalResetBD.classList.remove('visible');
            }

            if (btnAbrirReset) btnAbrirReset.addEventListener('click', abrirReset);
            if (btnCancelarReset) btnCancelarReset.addEventListener('click', cerrarReset);
            if (btnCancelarResetX) btnCancelarResetX.addEventListener('click', cerrarReset);

            // Validar que escriba "REINICIAR"
            if (inputConfirmReset) {
                inputConfirmReset.addEventListener('input', function() {
                    if (this.value.trim().toUpperCase() === 'REINICIAR') {
                        btnEjecutarReset.disabled = false;
                        btnEjecutarReset.style.opacity = '1';
                        btnEjecutarReset.style.cursor = 'pointer';
                    } else {
                        btnEjecutarReset.disabled = true;
                        btnEjecutarReset.style.opacity = '0.5';
                        btnEjecutarReset.style.cursor = 'not-allowed';
                    }
                });
            }

            // Ejecutar la acción
            if (btnEjecutarReset) {
                btnEjecutarReset.addEventListener('click', function() {
                    cerrarReset();
                    bdError.textContent = '';
                    btnAbrirReset.disabled = true;
                    btnAbrirReset.innerHTML = 'Borrando base de datos...';

                    fetch('/AvilesPOSv2/modulos/configuracion/api/reset_bd.php', {
                        method: 'POST'
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.ok) {
                            // Mostrar modal de éxito reutilizando el que ya tenemos
                            // o simplemente redirigir al login
                            alert('Sistema reiniciado correctamente. Serás redirigido al login.');
                            window.location.href = '/AvilesPOSv2/logout.php';
                        } else {
                            bdError.textContent = data.error || 'Error al reiniciar el sistema.';
                            btnAbrirReset.disabled = false;
                            btnAbrirReset.innerHTML = 'Borrar todos los datos y reiniciar';
                        }
                    })
                    .catch(err => {
                        bdError.textContent = 'Error de conexión: ' + err.message;
                        btnAbrirReset.disabled = false;
                        btnAbrirReset.innerHTML = 'Borrar todos los datos y reiniciar';
                    });
                });
            }
        });
    </script>

</body>
</html>