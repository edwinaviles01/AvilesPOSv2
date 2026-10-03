<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: /AvilesPOSv2/index.php");
    exit;
}

require __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/permisos.php';
requiere_permiso('config_usuarios');

$mensaje = $_SESSION['user_mensaje'] ?? null;
$error = $_SESSION['user_error'] ?? null;
unset($_SESSION['user_mensaje'], $_SESSION['user_error']);

// Listado de usuarios con el nombre del rol
$usuarios = $conexion->query("
    SELECT u.id, u.nombre, u.usuario, u.rol, u.activo, u.ultimo_acceso, u.fecha_creacion,
           r.nombre AS rol_nombre
    FROM usuarios u
    LEFT JOIN roles r ON u.rol = r.clave
    ORDER BY u.activo DESC, u.nombre ASC
")->fetch_all(MYSQLI_ASSOC);

// Roles para el selector
$roles = $conexion->query("SELECT clave, nombre FROM roles ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);

// Permisos agrupados
$permisos_raw = $conexion->query("
    SELECT id, clave, grupo, nombre, descripcion
    FROM permisos
    ORDER BY grupo, id
")->fetch_all(MYSQLI_ASSOC);

$permisos_agrupados = [];
foreach ($permisos_raw as $p) {
    $permisos_agrupados[$p['grupo']][] = $p;
}

// Cargar permisos por rol (mapa: rol_clave => [permisos_clave...])
$permisos_por_rol = [];
$res_rp = $conexion->query("
    SELECT r.clave AS rol_clave, p.clave AS permiso_clave
    FROM roles r
    INNER JOIN roles_permisos rp ON r.id = rp.id_rol
    INNER JOIN permisos p ON rp.id_permiso = p.id
");
while ($row = $res_rp->fetch_assoc()) {
    if (!isset($permisos_por_rol[$row['rol_clave']])) {
        $permisos_por_rol[$row['rol_clave']] = [];
    }
    $permisos_por_rol[$row['rol_clave']][] = $row['permiso_clave'];
}

// Cargar permisos personalizados por usuario (extra/denegado)
$permisos_usuario = [];
$res_up = $conexion->query("
    SELECT up.id_usuario, up.tipo, p.clave AS permiso_clave
    FROM usuarios_permisos up
    INNER JOIN permisos p ON up.id_permiso = p.id
");
while ($row = $res_up->fetch_assoc()) {
    $uid = (int)$row['id_usuario'];
    if (!isset($permisos_usuario[$uid])) {
        $permisos_usuario[$uid] = ['extra' => [], 'denegado' => []];
    }
    $permisos_usuario[$uid][$row['tipo']][] = $row['permiso_clave'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios - AvilesPOS</title>
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

        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:12px;">
            <div style="font-size:13px; color:#888;">
                <?= count($usuarios) ?> usuario<?= count($usuarios) !== 1 ? 's' : '' ?> registrado<?= count($usuarios) !== 1 ? 's' : '' ?>
            </div>
            <button type="button" class="btn btn-primary" id="btnNuevoUsuario">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Nuevo usuario
            </button>
        </div>

        <?php if ($mensaje): ?>
            <div class="alert alert-success"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (count($usuarios) === 0): ?>

            <div class="empty-state">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" width="64" height="64">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                <h3>No hay usuarios</h3>
                <p>Empieza agregando usuarios al sistema</p>
            </div>

        <?php else: ?>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Usuario</th>
                            <th>Rol</th>
                            <th>Último acceso</th>
                            <th>Estado</th>
                            <th class="col-actions">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $u): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($u['nombre']) ?></strong>
                                </td>
                                <td style="font-size:13px; color:#666;">
                                    <?= htmlspecialchars($u['usuario']) ?>
                                </td>
                                <td>
                                    <span class="badge-rol <?= htmlspecialchars($u['rol']) ?>">
                                        <?= htmlspecialchars($u['rol_nombre'] ?? $u['rol']) ?>
                                    </span>
                                </td>
                                <td style="font-size:12px; color:#888;">
                                    <?= $u['ultimo_acceso'] ? date('d/m/Y H:i', strtotime($u['ultimo_acceso'])) : 'Nunca' ?>
                                </td>
                                <td>
                                    <span class="user-row-estado <?= $u['activo'] ? 'activo' : 'inactivo' ?>">
                                        <span class="dot"></span>
                                        <?= $u['activo'] ? 'Activo' : 'Inactivo' ?>
                                    </span>
                                </td>
                                <td class="col-actions">
                                    <div class="acciones">
                                        <button type="button" class="btn-icon edit"
                                                data-user-id="<?= (int)$u['id'] ?>"
                                                title="Editar">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                            </svg>
                                        </button>
                                        <button type="button" class="btn-icon delete"
                                                data-user-eliminar-id="<?= (int)$u['id'] ?>"
                                                data-user-eliminar-nombre="<?= htmlspecialchars($u['nombre']) ?>"
                                                data-user-id-sesion="<?= (int)$_SESSION['usuario_id'] ?>"
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

    <!-- MODAL: Crear/Editar usuario -->
    <div class="modal-overlay" id="modalUsuario">
        <div class="modal modal-usuario">
            <div class="modal-header">
                <h3 id="modalUsuarioTitle">Nuevo usuario</h3>
                <button type="button" class="modal-close" data-close>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>

            <form id="formUsuario">
                <input type="hidden" name="id" id="user_id">

                <div class="modal-body">

                    <div class="user-layout">

                        <!-- Columna izquierda: datos -->
                        <div class="user-col">
                            <div class="user-col-titulo">Datos del usuario</div>

                            <div class="user-form-grupo">
                                <label for="user_nombre">Nombre completo <span class="req">*</span></label>
                                <input type="text" id="user_nombre" name="nombre" required maxlength="100" placeholder="Ej: Juan Pérez">
                            </div>

                            <div class="user-form-grupo">
                                <label for="user_usuario">Nombre de usuario <span class="req">*</span></label>
                                <input type="text" id="user_usuario" name="usuario" required maxlength="50" placeholder="Ej: juanp">
                                <span class="help">Solo letras, números y guiones bajos</span>
                            </div>

                            <div class="user-form-grupo" id="grupoPassword">
                                <label for="user_password">Contraseña <span class="req">*</span></label>
                                <div class="user-input-password">
                                    <input type="password" id="user_password" name="password" maxlength="100" placeholder="Contraseña inicial">
                                    <button type="button" class="user-toggle-pass" data-toggle-for="user_password" title="Mostrar contraseña">
                                        <svg class="icon-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                        <svg class="icon-eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                            <line x1="1" y1="1" x2="23" y2="23"></line>
                                        </svg>
                                    </button>
                                </div>
                                <div class="user-caps-warning" data-caps-for="user_password">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="12" height="12">
                                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                                        <line x1="12" y1="9" x2="12" y2="13"></line>
                                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                                    </svg>
                                    Mayús está activado
                                </div>
                                <span class="help">Mínimo 3 caracteres</span>
                            </div>

                            <div class="user-form-grupo">
                                <label for="user_rol">Rol <span class="req">*</span></label>
                                <select id="user_rol" name="rol" required>
                                    <?php foreach ($roles as $r): ?>
                                        <option value="<?= htmlspecialchars($r['clave']) ?>">
                                            <?= htmlspecialchars($r['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="user-form-grupo">
                                <div class="form-check">
                                    <input type="checkbox" id="user_activo" name="activo" value="1" checked>
                                    <label for="user_activo">Usuario activo (puede iniciar sesión)</label>
                                </div>
                            </div>

                            <div class="user-pass-section" id="passSection" style="display:none;">
                                <button type="button" class="user-pass-btn" id="btnCambiarPass">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14">
                                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                    </svg>
                                    Cambiar contraseña
                                </button>

                                <div id="passFormWrap" style="display:none; margin-top:12px;">
                                    <div class="user-form-grupo">
                                        <label for="user_newpass">Nueva contraseña</label>
                                        <div class="user-input-password">
                                            <input type="password" id="user_newpass" maxlength="100" placeholder="Nueva contraseña">
                                            <button type="button" class="user-toggle-pass" data-toggle-for="user_newpass" title="Mostrar contraseña">
                                                <svg class="icon-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                    <circle cx="12" cy="12" r="3"></circle>
                                                </svg>
                                                <svg class="icon-eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                                    <line x1="1" y1="1" x2="23" y2="23"></line>
                                                </svg>
                                            </button>
                                        </div>
                                        <div class="user-caps-warning" data-caps-for="user_newpass">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="12" height="12">
                                                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                                                <line x1="12" y1="9" x2="12" y2="13"></line>
                                                <line x1="12" y1="17" x2="12.01" y2="17"></line>
                                            </svg>
                                            Mayús está activado
                                        </div>
                                    </div>
                                    <div class="user-form-grupo">
                                        <label for="user_newpass2">Confirmar contraseña</label>
                                        <div class="user-input-password">
                                            <input type="password" id="user_newpass2" maxlength="100" placeholder="Repetir contraseña">
                                            <button type="button" class="user-toggle-pass" data-toggle-for="user_newpass2" title="Mostrar contraseña">
                                                <svg class="icon-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                    <circle cx="12" cy="12" r="3"></circle>
                                                </svg>
                                                <svg class="icon-eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                                    <line x1="1" y1="1" x2="23" y2="23"></line>
                                                </svg>
                                            </button>
                                        </div>
                                        <div class="user-caps-warning" data-caps-for="user_newpass2">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="12" height="12">
                                                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                                                <line x1="12" y1="9" x2="12" y2="13"></line>
                                                <line x1="12" y1="17" x2="12.01" y2="17"></line>
                                            </svg>
                                            Mayús está activado
                                        </div>
                                    </div>
                                    <div style="display:flex; gap:8px;">
                                        <button type="button" class="btn btn-primary" id="btnGuardarPass" style="padding:8px 14px; font-size:12px;">
                                            Guardar contraseña
                                        </button>
                                        <button type="button" class="btn btn-secondary" id="btnCancelarPass" style="padding:8px 14px; font-size:12px;">
                                            Cancelar
                                        </button>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <!-- Columna derecha: permisos -->
                        <div class="user-col">
                            <div class="user-col-titulo">Permisos del usuario</div>

                            <div class="permisos-info">
                                Los permisos se heredan del rol seleccionado.
                                Puedes <strong>agregar</strong> o <strong>quitar</strong> permisos específicos para este usuario.
                            </div>

                            <div class="permisos-lista" id="permisosLista">
                                <?php foreach ($permisos_agrupados as $grupo => $items): ?>
                                    <div class="permisos-grupo" data-grupo="<?= htmlspecialchars($grupo) ?>">
                                        <div class="permisos-grupo-titulo"><?= htmlspecialchars(ucfirst($grupo)) ?></div>
                                        <?php foreach ($items as $p): ?>
                                            <div class="permiso-item" data-permiso="<?= htmlspecialchars($p['clave']) ?>" data-id="<?= (int)$p['id'] ?>">
                                                <input type="checkbox" id="perm_<?= (int)$p['id'] ?>" data-clave="<?= htmlspecialchars($p['clave']) ?>">
                                                <label for="perm_<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['nombre']) ?></label>
                                                <span class="permiso-badge rol" data-badge style="display:none;">Rol</span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="modal-error" id="errorUsuario" style="margin-top:12px;"></div>

                        </div>

                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-close>Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnGuardarUsuario">Guardar usuario</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: Confirmar eliminación -->
    <div class="modal-overlay" id="modalEliminarUsuario">
        <div class="modal" style="max-width:460px;">
            <div class="modal-header">
                <h3>Eliminar usuario</h3>
                <button type="button" class="modal-close" data-close>
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
                <p style="font-size:14px; color:#555; line-height:1.5; margin-bottom:8px;">
                    ¿Eliminar al usuario <strong id="nombreUsuarioEliminar"></strong>?
                </p>
                <p style="font-size:12px; color:#888; line-height:1.5;" id="avisoEliminarUsuario"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close>Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnConfirmarEliminarUsuario" style="background:#b00020;">
                    Sí, eliminar
                </button>
            </div>
        </div>
    </div>

    <script>
        window.USUARIOS_LISTA = <?= json_encode($usuarios) ?>;
        window.PERMISOS_POR_ROL = <?= json_encode($permisos_por_rol ?? []) ?>;
        window.USUARIO_ACTUAL_ID = <?= (int)$_SESSION['usuario_id'] ?>;
    </script>
    <script src="/AvilesPOSv2/assets/js/usuarios.js?v=<?= filemtime(__DIR__ . '/../../assets/js/usuarios.js') ?>"></script>

</body>
</html>