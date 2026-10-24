document.addEventListener('DOMContentLoaded', function () {

    /* ============================================
       DATOS GLOBALES
       ============================================ */
    const USUARIOS = window.USUARIOS_LISTA || [];
    const PERMISOS_POR_ROL = window.PERMISOS_POR_ROL || {};
    const USUARIO_ACTUAL_ID = parseInt(window.USUARIO_ACTUAL_ID) || 0;

    /* ============================================
       ELEMENTOS
       ============================================ */
    const modalUsuario = document.getElementById('modalUsuario');
    const modalUsuarioTitle = document.getElementById('modalUsuarioTitle');
    const formUsuario = document.getElementById('formUsuario');
    const btnNuevoUsuario = document.getElementById('btnNuevoUsuario');
    const btnGuardarUsuario = document.getElementById('btnGuardarUsuario');
    const errorUsuario = document.getElementById('errorUsuario');

    const user_id = document.getElementById('user_id');
    const user_nombre = document.getElementById('user_nombre');
    const user_usuario = document.getElementById('user_usuario');
    const user_password = document.getElementById('user_password');
    const user_rol = document.getElementById('user_rol');
    const user_activo = document.getElementById('user_activo');
    const grupoPassword = document.getElementById('grupoPassword');
    const passSection = document.getElementById('passSection');

    // Cambio de contraseña
    const btnCambiarPass = document.getElementById('btnCambiarPass');
    const passFormWrap = document.getElementById('passFormWrap');
    const user_newpass = document.getElementById('user_newpass');
    const user_newpass2 = document.getElementById('user_newpass2');
    const btnGuardarPass = document.getElementById('btnGuardarPass');
    const btnCancelarPass = document.getElementById('btnCancelarPass');

    // Permisos
    const permisosLista = document.getElementById('permisosLista');

    // Modal eliminar
    const modalEliminar = document.getElementById('modalEliminarUsuario');
    const nombreUsuarioEliminar = document.getElementById('nombreUsuarioEliminar');
    const avisoEliminarUsuario = document.getElementById('avisoEliminarUsuario');
    const btnConfirmarEliminarUsuario = document.getElementById('btnConfirmarEliminarUsuario');
    let idUsuarioEliminar = null;

        /* ============================================
       OJITO PARA VER/OCULTAR CONTRASEÑA
       ============================================ */
    document.querySelectorAll('.user-toggle-pass').forEach(btn => {
        btn.addEventListener('click', function () {
            const inputId = this.dataset.toggleFor;
            const input = document.getElementById(inputId);
            if (!input) return;

            const esVisible = input.type === 'text';
            input.type = esVisible ? 'password' : 'text';
            this.classList.toggle('visible', !esVisible);
            this.title = esVisible ? 'Mostrar contraseña' : 'Ocultar contraseña';
            input.focus();
        });
    });

    /* ============================================
       AVISO DE CAPS LOCK
       ============================================ */
    document.querySelectorAll('.user-caps-warning').forEach(warning => {
        const inputId = warning.dataset.capsFor;
        const input = document.getElementById(inputId);
        if (!input) return;

        function chequearCaps(e) {
            if (e.getModifierState && e.getModifierState('CapsLock')) {
                warning.classList.add('visible');
            } else {
                warning.classList.remove('visible');
            }
        }

        input.addEventListener('keydown', chequearCaps);
        input.addEventListener('keyup', chequearCaps);

        // Al salir del campo, ocultar el aviso
        input.addEventListener('blur', function () {
            warning.classList.remove('visible');
        });
    });

    /* ============================================
       HELPERS DE MODAL
       ============================================ */
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

    /* ============================================
       RENDER DE PERMISOS SEGÚN ROL
       ============================================ */
    function renderPermisosSegunRol(rolClave, permisosExtra = [], permisosDenegados = []) {
        const permisosRol = PERMISOS_POR_ROL[rolClave] || [];

        document.querySelectorAll('.permiso-item').forEach(item => {
            const clave = item.dataset.permiso;
            const checkbox = item.querySelector('input[type="checkbox"]');
            const badge = item.querySelector('[data-badge]');

            // Reset de clases
            item.classList.remove('heredado', 'extra', 'denegado');
            badge.style.display = 'none';

            const esDelRol = permisosRol.includes(clave);
            const esExtra = permisosExtra.includes(clave);
            const esDenegado = permisosDenegados.includes(clave);

            // Determinar estado efectivo
            let activo = false;
            let tipo = 'ninguno';

            if (esDenegado) {
                activo = false;
                tipo = 'denegado';
            } else if (esExtra) {
                activo = true;
                tipo = 'extra';
            } else if (esDelRol) {
                activo = true;
                tipo = 'rol';
            }

            checkbox.checked = activo;

            // Aplicar clases visuales
            if (tipo === 'rol') {
                item.classList.add('heredado');
                badge.textContent = 'Rol';
                badge.className = 'permiso-badge rol';
                badge.style.display = 'inline-block';
            } else if (tipo === 'extra') {
                item.classList.add('extra');
                badge.textContent = 'Extra';
                badge.className = 'permiso-badge extra';
                badge.style.display = 'inline-block';
            } else if (tipo === 'denegado') {
                item.classList.add('denegado');
                badge.textContent = 'Quitado';
                badge.className = 'permiso-badge denegado';
                badge.style.display = 'inline-block';
            }
        });

        // Aplicar restricciones si es administrador
        aplicarRestriccionesAdmin(rolClave === 'administrador');
    }

    /* ============================================
       RESTRICCIONES PARA ROL ADMINISTRADOR
       ============================================ */
    function aplicarRestriccionesAdmin(esAdmin) {
        // Checkbox de activo: siempre activo y deshabilitado
        user_activo.disabled = esAdmin;
        if (esAdmin) user_activo.checked = true;

        // Todos los permisos: siempre marcados y deshabilitados
        document.querySelectorAll('.permiso-item input[type="checkbox"]').forEach(cb => {
            cb.disabled = esAdmin;
            if (esAdmin) cb.checked = true;
        });

        // Badges: mostrar "Rol" en todos cuando es admin
        if (esAdmin) {
            document.querySelectorAll('.permiso-item').forEach(item => {
                const badge = item.querySelector('[data-badge]');
                item.classList.remove('extra', 'denegado');
                item.classList.add('heredado');
                badge.textContent = 'Rol';
                badge.className = 'permiso-badge rol';
                badge.style.display = 'inline-block';
            });
        }
    }

    /* ============================================
       ABRIR MODAL NUEVO
       ============================================ */
    if (btnNuevoUsuario) {
        btnNuevoUsuario.addEventListener('click', function () {
            formUsuario.reset();
            user_id.value = '';
            modalUsuarioTitle.textContent = 'Nuevo usuario';
            btnGuardarUsuario.textContent = 'Crear usuario';

            // Password visible al crear
            grupoPassword.style.display = 'flex';
            user_password.required = true;
            passSection.style.display = 'none';
            passFormWrap.style.display = 'none';

            // Rol por defecto: cajero
            user_rol.value = 'cajero';
            user_activo.checked = true;

            errorUsuario.textContent = '';

            // Renderizar permisos según rol
            renderPermisosSegunRol('cajero');

            abrirModal('modalUsuario');
            setTimeout(() => user_nombre.focus(), 100);
        });
    }

    /* ============================================
       ABRIR MODAL EDITAR
       ============================================ */
    document.querySelectorAll('[data-user-id]').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = parseInt(this.dataset.userId);
            const user = USUARIOS.find(u => parseInt(u.id) === id);
            if (!user) return;

            formUsuario.reset();
            user_id.value = user.id;
            user_nombre.value = user.nombre;
            user_usuario.value = user.usuario;
            user_rol.value = user.rol;
            user_activo.checked = user.activo === '1' || user.activo === 1;

            modalUsuarioTitle.textContent = 'Editar usuario';
            btnGuardarUsuario.textContent = 'Guardar cambios';

            // Password oculto al editar (con botón separado)
            grupoPassword.style.display = 'none';
            user_password.required = false;
            passSection.style.display = 'block';
            passFormWrap.style.display = 'none';
            user_newpass.value = '';
            user_newpass2.value = '';

            errorUsuario.textContent = '';

            // Cargar permisos personalizados del usuario
            fetch('/AvilesPOSv2/modulos/configuracion/api/permisos_usuario.php?id=' + id)
                .then(r => r.json())
                .then(data => {
                    if (data.ok) {
                        renderPermisosSegunRol(user.rol, data.extra, data.denegado);
                    } else {
                        renderPermisosSegunRol(user.rol);
                    }
                })
                .catch(() => {
                    renderPermisosSegunRol(user.rol);
                });

            abrirModal('modalUsuario');
            setTimeout(() => user_nombre.focus(), 100);
        });
    });

    /* ============================================
       CAMBIO DE ROL EN EL MODAL
       ============================================ */
    if (user_rol) {
        user_rol.addEventListener('change', function () {
            // Al cambiar rol, reseteamos los permisos y aplicamos los del nuevo rol
            renderPermisosSegunRol(this.value);
        });
    }

    /* ============================================
       GUARDAR USUARIO
       ============================================ */
    if (formUsuario) {
        formUsuario.addEventListener('submit', function (e) {
            e.preventDefault();
            errorUsuario.textContent = '';

            // Validaciones
            const nombre = user_nombre.value.trim();
            const usuario = user_usuario.value.trim();
            const rol = user_rol.value;

            if (nombre === '' || usuario === '') {
                errorUsuario.textContent = 'Nombre y usuario son obligatorios.';
                return;
            }

            // Validar formato del usuario (solo letras, números, guion bajo)
            if (!/^[a-zA-Z0-9_]+$/.test(usuario)) {
                errorUsuario.textContent = 'El usuario solo puede contener letras, números y guiones bajos.';
                return;
            }

            const esNuevo = user_id.value === '';

            if (esNuevo) {
                const pass = user_password.value;
                if (pass === '' || pass.length < 3) {
                    errorUsuario.textContent = 'La contraseña debe tener al menos 3 caracteres.';
                    return;
                }
            }

            // Recolectar permisos personalizados
            const permisosExtra = [];
            const permisosDenegados = [];
            const permisosRol = PERMISOS_POR_ROL[rol] || [];

            document.querySelectorAll('.permiso-item').forEach(item => {
                const clave = item.dataset.permiso;
                const marcado = item.querySelector('input[type="checkbox"]').checked;
                const esDelRol = permisosRol.includes(clave);

                if (marcado && !esDelRol) {
                    permisosExtra.push(clave);
                } else if (!marcado && esDelRol) {
                    permisosDenegados.push(clave);
                }
            });

            // Construir payload
            const payload = {
                id: user_id.value || null,
                nombre: nombre,
                usuario: usuario,
                rol: rol,
                activo: user_activo.checked ? 1 : 0,
                password: esNuevo ? user_password.value : null,
                permisos_extra: permisosExtra,
                permisos_denegados: permisosDenegados
            };

            btnGuardarUsuario.disabled = true;
            btnGuardarUsuario.textContent = 'Guardando...';

            fetch('/AvilesPOSv2/modulos/configuracion/api/guardar_usuario.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(r => r.json())
            .then(data => {
                if (data.ok) {
                    cerrarModal(modalUsuario);
                    window.location.reload();
                } else {
                    errorUsuario.textContent = data.error || 'Error al guardar.';
                    btnGuardarUsuario.disabled = false;
                    btnGuardarUsuario.textContent = esNuevo ? 'Crear usuario' : 'Guardar cambios';
                }
            })
            .catch(() => {
                errorUsuario.textContent = 'Error de conexión.';
                btnGuardarUsuario.disabled = false;
                btnGuardarUsuario.textContent = esNuevo ? 'Crear usuario' : 'Guardar cambios';
            });
        });
    }

    /* ============================================
       CAMBIO DE CONTRASEÑA (BOTÓN SEPARADO)
       ============================================ */
    if (btnCambiarPass) {
        btnCambiarPass.addEventListener('click', function () {
            passFormWrap.style.display = 'block';
            this.style.display = 'none';
            setTimeout(() => user_newpass.focus(), 100);
        });
    }

    if (btnCancelarPass) {
        btnCancelarPass.addEventListener('click', function () {
            passFormWrap.style.display = 'none';
            btnCambiarPass.style.display = 'inline-flex';
            user_newpass.value = '';
            user_newpass2.value = '';
        });
    }

    if (btnGuardarPass) {
        btnGuardarPass.addEventListener('click', function () {
            const pass1 = user_newpass.value;
            const pass2 = user_newpass2.value;

            if (pass1 === '' || pass1.length < 3) {
                alert('La contraseña debe tener al menos 3 caracteres.');
                return;
            }

            if (pass1 !== pass2) {
                alert('Las contraseñas no coinciden.');
                return;
            }

            btnGuardarPass.disabled = true;
            btnGuardarPass.textContent = 'Guardando...';

            fetch('/AvilesPOSv2/modulos/configuracion/api/cambiar_password.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id_usuario: parseInt(user_id.value),
                    password: pass1
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.ok) {
                    alert('Contraseña actualizada correctamente.');
                    passFormWrap.style.display = 'none';
                    btnCambiarPass.style.display = 'inline-flex';
                    user_newpass.value = '';
                    user_newpass2.value = '';
                } else {
                    alert(data.error || 'Error al cambiar contraseña.');
                }
            })
            .catch(() => {
                alert('Error de conexión.');
            })
            .finally(() => {
                btnGuardarPass.disabled = false;
                btnGuardarPass.textContent = 'Guardar contraseña';
            });
        });
    }

    /* ============================================
       ELIMINAR USUARIO
       ============================================ */
    document.querySelectorAll('[data-user-eliminar-id]').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = parseInt(this.dataset.userEliminarId);
            const nombre = this.dataset.userEliminarNombre;

            if (id === USUARIO_ACTUAL_ID) {
                alert('No puedes eliminar tu propio usuario.');
                return;
            }

            idUsuarioEliminar = id;
            nombreUsuarioEliminar.textContent = nombre;
            avisoEliminarUsuario.textContent = 'Si tiene ventas registradas, se desactivará en lugar de eliminarse.';

            btnConfirmarEliminarUsuario.disabled = false;
            btnConfirmarEliminarUsuario.textContent = 'Sí, eliminar';

            abrirModal('modalEliminarUsuario');
        });
    });

    if (btnConfirmarEliminarUsuario) {
        btnConfirmarEliminarUsuario.addEventListener('click', function () {
            if (!idUsuarioEliminar) return;

            btnConfirmarEliminarUsuario.disabled = true;
            btnConfirmarEliminarUsuario.textContent = 'Procesando...';

            fetch('/AvilesPOSv2/modulos/configuracion/api/eliminar_usuario.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: idUsuarioEliminar })
            })
            .then(r => r.json())
            .then(data => {
                if (data.ok) {
                    cerrarModal(modalEliminar);
                    window.location.reload();
                } else {
                    alert(data.error || 'Error al eliminar.');
                    btnConfirmarEliminarUsuario.disabled = false;
                    btnConfirmarEliminarUsuario.textContent = 'Sí, eliminar';
                }
            })
            .catch(() => {
                alert('Error de conexión.');
                btnConfirmarEliminarUsuario.disabled = false;
                btnConfirmarEliminarUsuario.textContent = 'Sí, eliminar';
            });
        });
    }

});