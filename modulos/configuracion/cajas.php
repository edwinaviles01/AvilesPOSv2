<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: /AvilesPOSv2/index.php");
    exit;
}

require __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/permisos.php';
requiere_permiso('config_cajas');

// Cargamos todas las cajas con contador de turnos
$sql = "SELECT c.id, c.nombre, c.activa, c.fecha_creacion,
        (SELECT COUNT(*) FROM turnos t WHERE t.id_caja = c.id) AS total_turnos,
        (SELECT COUNT(*) FROM turnos t WHERE t.id_caja = c.id AND t.estado = 'abierto') AS turnos_abiertos
        FROM cajas c
        ORDER BY c.nombre ASC";
$cajas = $conexion->query($sql)->fetch_all(MYSQLI_ASSOC);

$mensaje_exito = $_SESSION['caja_mensaje'] ?? null;
$mensaje_error = $_SESSION['caja_error'] ?? null;
unset($_SESSION['caja_mensaje'], $_SESSION['caja_error']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cajas - AvilesPOS</title>
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

        <div style="margin-bottom:20px; display:flex; justify-content:flex-end;">
            <button type="button" class="btn btn-primary" id="btnNuevaCaja">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Nueva caja
            </button>
        </div>

        <?php if ($mensaje_exito): ?>
            <div class="alert alert-success"><?= htmlspecialchars($mensaje_exito) ?></div>
        <?php endif; ?>

        <?php if ($mensaje_error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($mensaje_error) ?></div>
        <?php endif; ?>

        <?php if (count($cajas) === 0): ?>

            <div class="empty-state">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" width="64" height="64">
                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                    <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                </svg>
                <h3>No hay cajas registradas</h3>
                <p>Empieza creando tu primera caja</p>
                <button type="button" class="btn btn-primary" onclick="document.getElementById('btnNuevaCaja').click()">
                    Crear caja
                </button>
            </div>

        <?php else: ?>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Turnos</th>
                            <th>Estado</th>
                            <th class="col-actions">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cajas as $c): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($c['nombre']) ?></strong></td>
                                <td style="font-size:13px; color:#666;">
                                    <?= (int)$c['total_turnos'] ?> en total
                                    <?php if ((int)$c['turnos_abiertos'] > 0): ?>
                                        <span class="badge badge-success" style="margin-left:6px;">
                                            <?= (int)$c['turnos_abiertos'] ?> abierto<?= $c['turnos_abiertos'] > 1 ? 's' : '' ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($c['activa']): ?>
                                        <span class="badge badge-success">Activa</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Inactiva</span>
                                    <?php endif; ?>
                                </td>
                                <td class="col-actions">
                                    <div class="acciones">
                                        <button type="button" class="btn-icon edit"
                                                data-caja-id="<?= (int)$c['id'] ?>"
                                                data-caja-nombre="<?= htmlspecialchars($c['nombre']) ?>"
                                                data-caja-activa="<?= (int)$c['activa'] ?>"
                                                title="Editar">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                            </svg>
                                        </button>
                                        <button type="button" class="btn-icon delete"
                                                data-caja-eliminar-id="<?= (int)$c['id'] ?>"
                                                data-caja-eliminar-nombre="<?= htmlspecialchars($c['nombre']) ?>"
                                                data-caja-turnos="<?= (int)$c['total_turnos'] ?>"
                                                title="Eliminar">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                                                <polyline points="3 6 5 6 21 6"></polyline>
                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>
    </main>

    <!-- MODAL: Crear/Editar caja -->
    <div class="modal-overlay" id="modalCaja">
        <div class="modal">
            <div class="modal-header">
                <h3 id="modalCajaTitle">Nueva caja</h3>
                <button type="button" class="modal-close" data-close>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <form id="formCaja">
                <input type="hidden" name="id" id="caja_id">
                <div class="modal-body">
                    <div class="modal-error" id="errorCaja"></div>
                    <div class="form-group">
                        <label for="caja_nombre">Nombre de la caja <span class="req">*</span></label>
                        <input type="text" id="caja_nombre" name="nombre" required maxlength="50" placeholder="Ej: Caja Principal">
                    </div>
                    <div class="form-group">
                        <div class="form-check">
                            <input type="checkbox" name="activa" id="caja_activa" value="1" checked>
                            <label for="caja_activa">Caja activa (disponible para usar)</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-close>Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnGuardarCaja">Crear caja</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: Confirmar eliminación -->
    <div class="modal-overlay" id="modalEliminarCaja">
        <div class="modal">
            <div class="modal-header">
                <h3>Eliminar caja</h3>
                <button type="button" class="modal-close" data-close>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <p style="font-size:14px; color:#555; line-height:1.5;">
                    ¿Estás seguro de eliminar la caja <strong id="nombreCajaEliminar"></strong>?
                </p>
                <p style="font-size:13px; color:#888; margin-top:12px;" id="avisoTurnosCaja"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close>Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnConfirmarEliminarCaja" style="background:#b00020;">
                    Sí, eliminar
                </button>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {

        // ============================
        // HELPERS DE MODAL
        // ============================
        function abrirModal(id) {
            const m = document.getElementById(id);
            if (m) m.classList.add('visible');
        }

        function cerrarModal(m) {
            m.classList.remove('visible');
        }

        document.querySelectorAll('.modal-overlay').forEach(modal => {
            modal.querySelectorAll('[data-close]').forEach(btn => {
                btn.addEventListener('click', () => cerrarModal(modal));
            });
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal-overlay.visible').forEach(m => cerrarModal(m));
            }
        });

        // ============================
        // BOTÓN "NUEVA CAJA"
        // ============================
        const modalCaja = document.getElementById('modalCaja');
        const formCaja = document.getElementById('formCaja');
        const modalCajaTitle = document.getElementById('modalCajaTitle');
        const btnGuardarCaja = document.getElementById('btnGuardarCaja');

        document.getElementById('btnNuevaCaja')?.addEventListener('click', function () {
            formCaja.reset();
            document.getElementById('caja_id').value = '';
            document.getElementById('caja_activa').checked = true;
            document.getElementById('errorCaja').textContent = '';
            modalCajaTitle.textContent = 'Nueva caja';
            btnGuardarCaja.textContent = 'Crear caja';
            abrirModal('modalCaja');
            document.getElementById('caja_nombre').focus();
        });

        // ============================
        // BOTÓN EDITAR (en cada fila)
        // ============================
        document.querySelectorAll('[data-caja-id]').forEach(btn => {
            btn.addEventListener('click', function () {
                document.getElementById('caja_id').value = this.dataset.cajaId;
                document.getElementById('caja_nombre').value = this.dataset.cajaNombre;
                document.getElementById('caja_activa').checked = this.dataset.cajaActiva === '1';
                document.getElementById('errorCaja').textContent = '';
                modalCajaTitle.textContent = 'Editar caja';
                btnGuardarCaja.textContent = 'Guardar cambios';
                abrirModal('modalCaja');
                document.getElementById('caja_nombre').focus();
            });
        });

        // ============================
        // GUARDAR (crear o editar)
        // ============================
        formCaja.addEventListener('submit', function (e) {
            e.preventDefault();
            const errorBox = document.getElementById('errorCaja');
            errorBox.textContent = '';

            btnGuardarCaja.disabled = true;
            btnGuardarCaja.textContent = 'Guardando...';

            fetch('/AvilesPOSv2/api/guardar_caja.php', {
                method: 'POST',
                body: new FormData(formCaja)
            })
            .then(r => r.json())
            .then(data => {
                if (data.ok) {
                    location.reload();
                } else {
                    errorBox.textContent = data.error || 'Error desconocido';
                    btnGuardarCaja.disabled = false;
                    btnGuardarCaja.textContent = document.getElementById('caja_id').value ? 'Guardar cambios' : 'Crear caja';
                }
            })
            .catch(() => {
                errorBox.textContent = 'Error de conexión';
                btnGuardarCaja.disabled = false;
                btnGuardarCaja.textContent = 'Guardar';
            });
        });

        // ============================
        // ELIMINAR
        // ============================
        let idAEliminar = null;
        const btnConfirmarEliminar = document.getElementById('btnConfirmarEliminarCaja');

        document.querySelectorAll('[data-caja-eliminar-id]').forEach(btn => {
            btn.addEventListener('click', function () {
                idAEliminar = this.dataset.cajaEliminarId;
                const nombre = this.dataset.cajaEliminarNombre;
                const turnos = parseInt(this.dataset.cajaTurnos) || 0;

                document.getElementById('nombreCajaEliminar').textContent = nombre;

                const aviso = document.getElementById('avisoTurnosCaja');
                if (turnos > 0) {
                    aviso.textContent = 'Esta caja tiene ' + turnos + ' turno(s) asociado(s). No se podrá eliminar. Considera desactivarla en lugar de borrarla.';
                    btnConfirmarEliminar.disabled = true;
                    btnConfirmarEliminar.style.opacity = '0.5';
                } else {
                    aviso.textContent = 'Esta acción no se puede deshacer.';
                    btnConfirmarEliminar.disabled = false;
                    btnConfirmarEliminar.style.opacity = '1';
                }

                abrirModal('modalEliminarCaja');
            });
        });

        btnConfirmarEliminar.addEventListener('click', function () {
            if (!idAEliminar) return;
            btnConfirmarEliminar.disabled = true;
            btnConfirmarEliminar.textContent = 'Eliminando...';

            fetch('/AvilesPOSv2/api/eliminar_caja.php', {
                method: 'POST',
                body: new URLSearchParams({ id: idAEliminar })
            })
            .then(r => r.json())
            .then(data => {
                if (data.ok) {
                    location.reload();
                } else {
                    alert(data.error || 'Error al eliminar');
                    btnConfirmarEliminar.disabled = false;
                    btnConfirmarEliminar.textContent = 'Sí, eliminar';
                }
            })
            .catch(() => {
                alert('Error de conexión');
                btnConfirmarEliminar.disabled = false;
                btnConfirmarEliminar.textContent = 'Sí, eliminar';
            });
        });

    });
    </script>

</body>
</html>