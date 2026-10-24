document.addEventListener('DOMContentLoaded', function () {

    /* ============================================
       ESTADO GLOBAL
       ============================================ */
    let departamentos = (window.DEPARTAMENTOS || []).slice();
    let categorias = (window.CATEGORIAS || []).slice();
    let proveedores = (window.PROVEEDORES || []).slice();

    /* ============================================
       HELPERS
       ============================================ */
    function esc(str) {
        return String(str ?? '').replace(/[&<>"']/g, c => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        }[c]));
    }

    function abrirModal(id) {
        const modal = document.getElementById(id);
        if (!modal) return;
        modal.classList.add('visible');
    }

    function cerrarModal(modal) {
        modal.classList.remove('visible');
        // Resetear a la vista lista
        modal.querySelectorAll('.modal-view').forEach(v => v.style.display = 'none');
        const lista = modal.querySelector('.modal-view[id$="Lista"]');
        if (lista) lista.style.display = 'block';
        // Limpiar errores
        modal.querySelectorAll('.modal-error').forEach(e => e.textContent = '');
    }

    // Cerrar solo con X o Cancelar (NO con clic fuera)
    document.querySelectorAll('.modal-overlay').forEach(modal => {
        modal.querySelectorAll('[data-close]').forEach(btn => {
            btn.addEventListener('click', () => cerrarModal(modal));
        });
    });

    // ESC para cerrar
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay.visible').forEach(m => cerrarModal(m));
        }
    });

    /* ============================================
       RENDERIZADO DE LISTAS
       ============================================ */
    function renderDepartamentos() {
        const cont = document.getElementById('listaDepartamentos');
        if (!cont) return;
        if (departamentos.length === 0) {
            cont.innerHTML = '';
            return;
        }
        cont.innerHTML = departamentos.map(d => `
            <div class="lista-item">
                <div class="lista-item-info">
                    <strong>${esc(d.nombre)}</strong>
                </div>
                <div class="lista-item-actions">
                    <button type="button" class="btn-icon-sm edit" data-accion="editar-depto" data-id="${d.id}" title="Editar">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                    </button>
                    <button type="button" class="btn-icon-sm delete" data-accion="eliminar-depto" data-id="${d.id}" title="Eliminar">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        </svg>
                    </button>
                </div>
            </div>
        `).join('');
    }

    function renderCategorias(filtroDepto) {
        const cont = document.getElementById('listaCategorias');
        if (!cont) return;

        let lista = categorias;
        if (filtroDepto) {
            lista = categorias.filter(c => String(c.id_departamento) === String(filtroDepto));
        }

        if (lista.length === 0) {
            cont.innerHTML = '';
            return;
        }

        cont.innerHTML = lista.map(c => {
            const depto = departamentos.find(d => String(d.id) === String(c.id_departamento));
            const deptoNombre = depto ? depto.nombre : '—';
            return `
                <div class="lista-item">
                    <div class="lista-item-info">
                        <strong>${esc(c.nombre)}</strong>
                        <span>${esc(deptoNombre)}</span>
                    </div>
                    <div class="lista-item-actions">
                        <button type="button" class="btn-icon-sm edit" data-accion="editar-cat" data-id="${c.id}" title="Editar">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                        </button>
                        <button type="button" class="btn-icon-sm delete" data-accion="eliminar-cat" data-id="${c.id}" title="Eliminar">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14">
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            `;
        }).join('');
    }

    function renderProveedores() {
        const cont = document.getElementById('listaProveedores');
        if (!cont) return;
        if (proveedores.length === 0) {
            cont.innerHTML = '';
            return;
        }
        cont.innerHTML = proveedores.map(p => `
            <div class="lista-item">
                <div class="lista-item-info">
                    <strong>${esc(p.nombre)}</strong>
                    <span>${esc(p.contacto || '')}${p.telefono ? ' · ' + esc(p.telefono) : ''}</span>
                </div>
                <div class="lista-item-actions">
                    <button type="button" class="btn-icon-sm edit" data-accion="editar-prov" data-id="${p.id}" title="Editar">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                    </button>
                    <button type="button" class="btn-icon-sm delete" data-accion="eliminar-prov" data-id="${p.id}" title="Eliminar">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        </svg>
                    </button>
                </div>
            </div>
        `).join('');
    }

    /* ============================================
       POBLAR SELECTS DESDE EL ESTADO
       ============================================ */
    function poblarSelectDeptos(selectEl, incluirVacio = true, valorSeleccionado = null) {
        const valorAntes = valorSeleccionado ?? selectEl.value;
        selectEl.innerHTML = '';
        if (incluirVacio) {
            const opt = document.createElement('option');
            opt.value = '';
            opt.textContent = '— Seleccionar —';
            selectEl.appendChild(opt);
        }
        departamentos.forEach(d => {
            const opt = document.createElement('option');
            opt.value = d.id;
            opt.textContent = d.nombre;
            if (String(d.id) === String(valorAntes)) opt.selected = true;
            selectEl.appendChild(opt);
        });
    }

    function poblarSelectCatDepto() {
        // Rellena el select de filtro del modal de categorías
        const filtro = document.getElementById('filtro_categoria_depto');
        if (!filtro) return;
        const valorAntes = filtro.value;
        filtro.innerHTML = '';
        const opt0 = document.createElement('option');
        opt0.value = '';
        opt0.textContent = '— Todas —';
        filtro.appendChild(opt0);
        departamentos.forEach(d => {
            const opt = document.createElement('option');
            opt.value = d.id;
            opt.textContent = d.nombre;
            if (String(d.id) === String(valorAntes)) opt.selected = true;
            filtro.appendChild(opt);
        });
        // Y el select del formulario
        const selectForm = document.getElementById('cat_id_departamento');
        if (selectForm) {
            poblarSelectDeptos(selectForm, true);
        }
    }

    /* ============================================
       ABRIR MODALES desde botones "+"
       ============================================ */
    document.querySelectorAll('.btn-add-mini').forEach(btn => {
        btn.addEventListener('click', function () {
            const tipo = this.dataset.modal;

            if (tipo === 'departamento') {
                renderDepartamentos();
                mostrarVista('modalDepartamento', 'Lista');
                abrirModal('modalDepartamento');
            } else if (tipo === 'categoria') {
                poblarSelectCatDepto();
                const selectDepto = document.getElementById('id_departamento');
                const filtro = document.getElementById('filtro_categoria_depto');
                if (selectDepto.value && filtro) {
                    filtro.value = selectDepto.value;
                }
                renderCategorias(filtro ? filtro.value : '');
                mostrarVista('modalCategoria', 'Lista');
                abrirModal('modalCategoria');
            } else if (tipo === 'proveedor') {
                renderProveedores();
                mostrarVista('modalProveedor', 'Lista');
                abrirModal('modalProveedor');
            }
        });
    });

    function mostrarVista(modalId, vista) {
        const modal = document.getElementById(modalId);
        if (!modal) return;
        modal.querySelectorAll('.modal-view').forEach(v => v.style.display = 'none');
        const target = modal.querySelector('.modal-view[id$="' + vista + '"]');
        if (target) target.style.display = 'block';
    }

    /* ============================================
       NAVEGACIÓN LISTA <-> FORMULARIO
       ============================================ */
    // Departamento
    document.getElementById('btnNuevoDepto')?.addEventListener('click', () => {
        document.getElementById('formModalDepartamento').reset();
        document.getElementById('depto_id').value = '';
        document.getElementById('errorDepartamento').textContent = '';
        document.getElementById('btnGuardarDepto').textContent = 'Crear departamento';
        mostrarVista('modalDepartamento', 'Form');
        document.getElementById('depto_nombre').focus();
    });
    document.getElementById('btnVolverDepto')?.addEventListener('click', () => {
        renderDepartamentos();
        mostrarVista('modalDepartamento', 'Lista');
    });

    // Categoría
    document.getElementById('btnNuevaCat')?.addEventListener('click', () => {
        document.getElementById('formModalCategoria').reset();
        document.getElementById('cat_id').value = '';
        document.getElementById('errorCategoria').textContent = '';
        document.getElementById('btnGuardarCat').textContent = 'Crear categoría';
        const filtro = document.getElementById('filtro_categoria_depto');
        if (filtro && filtro.value) {
            document.getElementById('cat_id_departamento').value = filtro.value;
        }
        mostrarVista('modalCategoria', 'Form');
        document.getElementById('cat_nombre').focus();
    });
    document.getElementById('btnVolverCat')?.addEventListener('click', () => {
        const filtro = document.getElementById('filtro_categoria_depto');
        renderCategorias(filtro ? filtro.value : '');
        mostrarVista('modalCategoria', 'Lista');
    });

    // Proveedor
    document.getElementById('btnNuevoProv')?.addEventListener('click', () => {
        document.getElementById('formModalProveedor').reset();
        document.getElementById('prov_id').value = '';
        document.getElementById('errorProveedor').textContent = '';
        document.getElementById('btnGuardarProv').textContent = 'Crear proveedor';
        mostrarVista('modalProveedor', 'Form');
        document.getElementById('prov_nombre').focus();
    });
    document.getElementById('btnVolverProv')?.addEventListener('click', () => {
        renderProveedores();
        mostrarVista('modalProveedor', 'Lista');
    });

    // Filtro de categorías
    document.getElementById('filtro_categoria_depto')?.addEventListener('change', function () {
        renderCategorias(this.value);
    });

    /* ============================================
       ENVÍO: DEPARTAMENTO (crear + editar)
       ============================================ */
    const formDepto = document.getElementById('formModalDepartamento');
    formDepto?.addEventListener('submit', function (e) {
        e.preventDefault();
        const errorBox = document.getElementById('errorDepartamento');
        errorBox.textContent = '';

        const id = document.getElementById('depto_id').value;
        const esEdicion = id !== '';
        const url = esEdicion
            ? '/AvilesPOSv2/api/editar_departamento.php'
            : '/AvilesPOSv2/api/crear_departamento.php';

        const btn = document.getElementById('btnGuardarDepto');
        btn.disabled = true;
        btn.textContent = 'Guardando...';

        fetch(url, { method: 'POST', body: new FormData(formDepto) })
            .then(r => r.json())
            .then(data => {
                if (data.ok) {
                    if (esEdicion) {
                        const idx = departamentos.findIndex(d => String(d.id) === String(data.id));
                        if (idx >= 0) departamentos[idx].nombre = data.nombre;
                        actualizarSelectPrincipal('id_departamento', data.id, data.nombre);
                        poblarSelectCatDepto();
                    } else {
                        departamentos.push({ id: data.id, nombre: data.nombre });
                        departamentos.sort((a, b) => a.nombre.localeCompare(b.nombre));
                        agregarOptionSelect('id_departamento', data.id, data.nombre, true);
                        poblarSelectCatDepto();
                        // Disparamos cambio para actualizar categorías en el form principal
                        document.getElementById('id_departamento').dispatchEvent(new Event('change'));
                    }
                    renderDepartamentos();
                    mostrarVista('modalDepartamento', 'Lista');
                } else {
                    errorBox.textContent = data.error || 'Error desconocido';
                }
            })
            .catch(() => errorBox.textContent = 'Error de conexión')
            .finally(() => {
                btn.disabled = false;
                btn.textContent = esEdicion ? 'Guardar cambios' : 'Crear departamento';
            });
    });

    /* ============================================
       ENVÍO: CATEGORÍA (crear + editar)
       ============================================ */
    const formCat = document.getElementById('formModalCategoria');
    formCat?.addEventListener('submit', function (e) {
        e.preventDefault();
        const errorBox = document.getElementById('errorCategoria');
        errorBox.textContent = '';

        const id = document.getElementById('cat_id').value;
        const esEdicion = id !== '';
        const url = esEdicion
            ? '/AvilesPOSv2/api/editar_categoria.php'
            : '/AvilesPOSv2/api/crear_categoria.php';

        const btn = document.getElementById('btnGuardarCat');
        btn.disabled = true;
        btn.textContent = 'Guardando...';

        fetch(url, { method: 'POST', body: new FormData(formCat) })
            .then(r => r.json())
            .then(data => {
                if (data.ok) {
                    if (esEdicion) {
                        const idx = categorias.findIndex(c => String(c.id) === String(data.id));
                        if (idx >= 0) categorias[idx].nombre = data.nombre;
                        // Actualizar el select principal si es la categoría actual del form
                        actualizarSelectPrincipal('id_categoria', data.id, data.nombre);
                        // Y window.CATEGORIAS para que el form principal lo sepa
                        if (window.CATEGORIAS) {
                            const wIdx = window.CATEGORIAS.findIndex(c => String(c.id) === String(data.id));
                            if (wIdx >= 0) window.CATEGORIAS[wIdx].nombre = data.nombre;
                        }
                    } else {
                        categorias.push({
                            id: data.id,
                            id_departamento: data.id_departamento,
                            nombre: data.nombre
                        });
                        categorias.sort((a, b) => a.nombre.localeCompare(b.nombre));
                        // Agregar a window.CATEGORIAS para que el form principal lo sepa
                        if (window.CATEGORIAS) {
                            window.CATEGORIAS.push({
                                id: data.id,
                                id_departamento: data.id_departamento,
                                nombre: data.nombre
                            });
                        }
                        // Si el departamento del form principal coincide, actualizamos el select
                        const selectDepto = document.getElementById('id_departamento');
                        if (String(selectDepto.value) === String(data.id_departamento)) {
                            agregarOptionSelect('id_categoria', data.id, data.nombre, true);
                        }
                    }
                    const filtro = document.getElementById('filtro_categoria_depto');
                    renderCategorias(filtro ? filtro.value : '');
                    mostrarVista('modalCategoria', 'Lista');
                } else {
                    errorBox.textContent = data.error || 'Error desconocido';
                }
            })
            .catch(() => errorBox.textContent = 'Error de conexión')
            .finally(() => {
                btn.disabled = false;
                btn.textContent = esEdicion ? 'Guardar cambios' : 'Crear categoría';
            });
    });

    /* ============================================
       ENVÍO: PROVEEDOR (crear + editar)
       ============================================ */
    const formProv = document.getElementById('formModalProveedor');
    formProv?.addEventListener('submit', function (e) {
        e.preventDefault();
        const errorBox = document.getElementById('errorProveedor');
        errorBox.textContent = '';

        const id = document.getElementById('prov_id').value;
        const esEdicion = id !== '';
        const url = esEdicion
            ? '/AvilesPOSv2/api/editar_proveedor.php'
            : '/AvilesPOSv2/api/crear_proveedor.php';

        const btn = document.getElementById('btnGuardarProv');
        btn.disabled = true;
        btn.textContent = 'Guardando...';

        fetch(url, { method: 'POST', body: new FormData(formProv) })
            .then(r => r.json())
            .then(data => {
                if (data.ok) {
                    const fd = new FormData(formProv);
                    const contacto = fd.get('contacto') || '';
                    const telefono = fd.get('telefono') || '';

                    if (esEdicion) {
                        const idx = proveedores.findIndex(p => String(p.id) === String(data.id));
                        if (idx >= 0) {
                            proveedores[idx].nombre = data.nombre;
                            proveedores[idx].contacto = contacto;
                            proveedores[idx].telefono = telefono;
                        }
                        actualizarSelectPrincipal('id_proveedor', data.id, data.nombre);
                    } else {
                        proveedores.push({
                            id: data.id,
                            nombre: data.nombre,
                            contacto: contacto,
                            telefono: telefono
                        });
                        proveedores.sort((a, b) => a.nombre.localeCompare(b.nombre));
                        agregarOptionSelect('id_proveedor', data.id, data.nombre, true);
                    }
                    renderProveedores();
                    mostrarVista('modalProveedor', 'Lista');
                } else {
                    errorBox.textContent = data.error || 'Error desconocido';
                }
            })
            .catch(() => errorBox.textContent = 'Error de conexión')
            .finally(() => {
                btn.disabled = false;
                btn.textContent = esEdicion ? 'Guardar cambios' : 'Crear proveedor';
            });
    });

    /* ============================================
       DELEGACIÓN DE EVENTOS: EDITAR / ELIMINAR
       ============================================ */
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('[data-accion]');
        if (!btn) return;

        const accion = btn.dataset.accion;
        const id = btn.dataset.id;

        if (accion === 'editar-depto') {
            const d = departamentos.find(x => String(x.id) === String(id));
            if (!d) return;
            document.getElementById('depto_id').value = d.id;
            document.getElementById('depto_nombre').value = d.nombre;
            document.getElementById('errorDepartamento').textContent = '';
            document.getElementById('btnGuardarDepto').textContent = 'Guardar cambios';
            mostrarVista('modalDepartamento', 'Form');
            document.getElementById('depto_nombre').focus();
        }

        else if (accion === 'eliminar-depto') {
            const d = departamentos.find(x => String(x.id) === String(id));
            if (!d) return;
            if (!confirm('¿Eliminar el departamento "' + d.nombre + '"?')) return;
            fetch('/AvilesPOSv2/api/eliminar_departamento.php', {
                method: 'POST',
                body: new URLSearchParams({ id: id })
            })
            .then(r => r.json())
            .then(data => {
                if (data.ok) {
                    departamentos = departamentos.filter(x => String(x.id) !== String(id));
                    eliminarOptionSelect('id_departamento', id);
                    poblarSelectCatDepto();
                    renderDepartamentos();
                } else {
                    alert(data.error || 'Error al eliminar');
                }
            })
            .catch(() => alert('Error de conexión'));
        }

        else if (accion === 'editar-cat') {
            const c = categorias.find(x => String(x.id) === String(id));
            if (!c) return;
            document.getElementById('cat_id').value = c.id;
            document.getElementById('cat_nombre').value = c.nombre;
            document.getElementById('cat_id_departamento').value = c.id_departamento;
            document.getElementById('errorCategoria').textContent = '';
            document.getElementById('btnGuardarCat').textContent = 'Guardar cambios';
            mostrarVista('modalCategoria', 'Form');
            document.getElementById('cat_nombre').focus();
        }

        else if (accion === 'eliminar-cat') {
            const c = categorias.find(x => String(x.id) === String(id));
            if (!c) return;
            if (!confirm('¿Eliminar la categoría "' + c.nombre + '"?')) return;
            fetch('/AvilesPOSv2/api/eliminar_categoria.php', {
                method: 'POST',
                body: new URLSearchParams({ id: id })
            })
            .then(r => r.json())
            .then(data => {
                if (data.ok) {
                    categorias = categorias.filter(x => String(x.id) !== String(id));
                    if (window.CATEGORIAS) {
                        window.CATEGORIAS = window.CATEGORIAS.filter(x => String(x.id) !== String(id));
                    }
                    eliminarOptionSelect('id_categoria', id);
                    const filtro = document.getElementById('filtro_categoria_depto');
                    renderCategorias(filtro ? filtro.value : '');
                } else {
                    alert(data.error || 'Error al eliminar');
                }
            })
            .catch(() => alert('Error de conexión'));
        }

        else if (accion === 'editar-prov') {
            const p = proveedores.find(x => String(x.id) === String(id));
            if (!p) return;
            document.getElementById('prov_id').value = p.id;
            document.getElementById('prov_nombre').value = p.nombre;
            document.getElementById('prov_contacto').value = p.contacto || '';
            document.getElementById('prov_telefono').value = p.telefono || '';
            document.getElementById('errorProveedor').textContent = '';
            document.getElementById('btnGuardarProv').textContent = 'Guardar cambios';
            mostrarVista('modalProveedor', 'Form');
            document.getElementById('prov_nombre').focus();
        }

        else if (accion === 'eliminar-prov') {
            const p = proveedores.find(x => String(x.id) === String(id));
            if (!p) return;
            if (!confirm('¿Eliminar el proveedor "' + p.nombre + '"?')) return;
            fetch('/AvilesPOSv2/api/eliminar_proveedor.php', {
                method: 'POST',
                body: new URLSearchParams({ id: id })
            })
            .then(r => r.json())
            .then(data => {
                if (data.ok) {
                    proveedores = proveedores.filter(x => String(x.id) !== String(id));
                    eliminarOptionSelect('id_proveedor', id);
                    renderProveedores();
                } else {
                    alert(data.error || 'Error al eliminar');
                }
            })
            .catch(() => alert('Error de conexión'));
        }
    });

    /* ============================================
       HELPERS PARA SELECTS DEL FORM PRINCIPAL
       ============================================ */
    function agregarOptionSelect(selectId, id, nombre, seleccionar = false) {
        const select = document.getElementById(selectId);
        if (!select) return;
        const opt = document.createElement('option');
        opt.value = id;
        opt.textContent = nombre;
        if (seleccionar) opt.selected = true;
        select.appendChild(opt);
    }

    function actualizarSelectPrincipal(selectId, id, nuevoNombre) {
        const select = document.getElementById(selectId);
        if (!select) return;
        const opt = select.querySelector('option[value="' + id + '"]');
        if (opt) opt.textContent = nuevoNombre;
    }

    function eliminarOptionSelect(selectId, id) {
        const select = document.getElementById(selectId);
        if (!select) return;
        const opt = select.querySelector('option[value="' + id + '"]');
        if (opt) {
            // Si estaba seleccionada, quitamos la selección
            if (opt.selected) select.value = '';
            opt.remove();
        }
    }

});