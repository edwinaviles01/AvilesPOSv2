document.addEventListener('DOMContentLoaded', function () {

    /* ============================================
       DATOS
       ============================================ */
    const PRODUCTOS = window.INV_PRODUCTOS || [];
    const DEPARTAMENTOS = window.INV_DEPARTAMENTOS || [];
    const PRODUCTO_INICIAL = window.INV_PRODUCTO_SELECCIONADO || '';

    let deptoActual = '';
    let busqueda = '';
    let idSeleccionado = PRODUCTO_INICIAL ? String(PRODUCTO_INICIAL) : '';

    /* ============================================
       ELEMENTOS
       ============================================ */
    const invBuscador = document.getElementById('invBuscador');
    const invBuscadorClear = document.getElementById('invBuscadorClear');
    const invTabs = document.getElementById('invTabs');
    const invGrid = document.getElementById('invGrid');
    const invSeleccionado = document.getElementById('invSeleccionado');
    const hiddenProducto = document.getElementById('id_producto');

    const radiosTipo = document.querySelectorAll('input[name="tipo"]');
    const inputCantidad = document.getElementById('cantidad');
    const resultadoStock = document.getElementById('resultado_stock');

    const seccionProveedor = document.getElementById('seccion_proveedor');
    const seccionPrecios = document.getElementById('seccion_precios');
    const precioCostoActual = document.getElementById('precio_costo_actual');
    const precioVentaActual = document.getElementById('precio_venta_actual');

    const reqComentario = document.getElementById('req_comentario');
    const helpComentario = document.getElementById('help_comentario');
    const textareaComentario = document.getElementById('comentario');

    /* ============================================
       HELPERS
       ============================================ */
    function esc(str) {
        return String(str ?? '').replace(/[&<>"']/g, c => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        }[c]));
    }

    function formatC(n) {
        return 'C$ ' + Number(n).toFixed(2);
    }

    function getProductoSeleccionado() {
        if (!idSeleccionado) return null;
        return PRODUCTOS.find(p => String(p.id) === idSeleccionado) || null;
    }

    /* ============================================
       TABS
       ============================================ */
    function renderTabs() {
        if (!invTabs) return;

        let html = `<button type="button" class="inv-prod-tab ${deptoActual === '' ? 'activo' : ''}" data-depto="">Todos</button>`;

        DEPARTAMENTOS.forEach(d => {
            const activo = String(deptoActual) === String(d.id) ? 'activo' : '';
            html += `<button type="button" class="inv-prod-tab ${activo}" data-depto="${d.id}">${esc(d.nombre)}</button>`;
        });

        invTabs.innerHTML = html;
    }

    /* ============================================
       GRID DE PRODUCTOS
       ============================================ */
     /* ============================================
       CONSTANTES DE AGRUPACIÓN
       ============================================ */
    const LIMITE_POR_SECCION_INV = 5;
    const LIMITE_POR_PAGINA_MODAL_INV = 12;

    let verMasDeptoActualInv = null;
    let verMasPaginaActualInv = 1;

    // Modal Ver más
    const modalVerMasInv = document.getElementById('modalVerMasInv');
    const modalVerMasInvTitle = document.getElementById('modalVerMasInvTitle');
    const modalVerMasInvGrid = document.getElementById('modalVerMasInvGrid');
    const modalVerMasInvPaginacion = document.getElementById('modalVerMasInvPaginacion');
    const modalVerMasInvInfo = document.getElementById('modalVerMasInvInfo');

    /* ============================================
       TARJETA DE PRODUCTO (reutilizable)
       ============================================ */
    function tarjetaProductoInv(p) {
        const stock = parseInt(p.stock) || 0;
        const seleccionado = String(p.id) === idSeleccionado ? 'seleccionado' : '';
        const tieneFoto = p.foto ? true : false;

        return `
            <button type="button" class="inv-prod-item ${seleccionado}" data-id="${p.id}">
                <div class="inv-prod-item-img">
                    ${tieneFoto 
                        ? `<img src="/AvilesPOSv2/assets/img/productos/${esc(p.foto)}" alt="${esc(p.nombre)}">`
                        : `<svg class="sin-foto" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" width="30" height="30">
                               <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                               <circle cx="8.5" cy="8.5" r="1.5"></circle>
                               <polyline points="21 15 16 10 5 21"></polyline>
                           </svg>`}
                </div>
                <div class="inv-prod-item-info">
                    <div class="inv-prod-item-nombre">${esc(p.nombre)}</div>
                    <div class="inv-prod-item-stock">
                        <span>Stock:</span>
                        <strong>${stock}</strong>
                    </div>
                </div>
            </button>
        `;
    }

    /* ============================================
       RENDER GRID CON AGRUPACIÓN
       ============================================ */
    function renderGrid() {
        if (!invGrid) return;

        const q = busqueda.trim().toLowerCase();

        // SI HAY BÚSQUEDA: mostrar plano
        if (q !== '') {
            const filtrados = PRODUCTOS.filter(p => {
                if (deptoActual !== '' && String(p.id_departamento) !== String(deptoActual)) {
                    return false;
                }
                return p.nombre.toLowerCase().includes(q) ||
                       String(p.codigo).toLowerCase().includes(q);
            });

            if (filtrados.length === 0) {
                invGrid.innerHTML = '<div class="inv-prod-vacio">No se encontraron productos</div>';
                return;
            }

            invGrid.innerHTML = `
                <div class="inv-seccion" style="grid-column: 1 / -1;">
                    <div class="inv-seccion-header">
                        <div class="inv-seccion-titulo">
                            Resultados
                            <span class="inv-seccion-count">(${filtrados.length})</span>
                        </div>
                    </div>
                    <div class="inv-prod-grid" style="padding:0; max-height:none;">
                        ${filtrados.map(tarjetaProductoInv).join('')}
                    </div>
                </div>
            `;
            return;
        }

        // SIN BÚSQUEDA: agrupar por departamento
        let productosFiltrados = PRODUCTOS;
        if (deptoActual !== '') {
            productosFiltrados = PRODUCTOS.filter(p => String(p.id_departamento) === String(deptoActual));
        }

        if (productosFiltrados.length === 0) {
            invGrid.innerHTML = '<div class="inv-prod-vacio">No hay productos en este departamento</div>';
            return;
        }

        // Agrupar por departamento
        const grupos = {};
        productosFiltrados.forEach(p => {
            const key = p.id_departamento ? String(p.id_departamento) : 'sin_depto';
            if (!grupos[key]) {
                grupos[key] = {
                    nombre: p.id_departamento ? (p.departamento_nombre || 'Sin nombre') : 'Sin departamento',
                    productos: []
                };
            }
            grupos[key].productos.push(p);
        });

        const keysOrdenadas = Object.keys(grupos).sort((a, b) => {
            if (a === 'sin_depto') return 1;
            if (b === 'sin_depto') return -1;
            return grupos[a].nombre.localeCompare(grupos[b].nombre);
        });

        let html = '';
        keysOrdenadas.forEach(key => {
            const grupo = grupos[key];
            const total = grupo.productos.length;
            const visibles = grupo.productos.slice(0, LIMITE_POR_SECCION_INV);
            const hayMas = total > LIMITE_POR_SECCION_INV;

            html += `
                <div class="inv-seccion" style="grid-column: 1 / -1;">
                    <div class="inv-seccion-header">
                        <div class="inv-seccion-titulo">
                            ${esc(grupo.nombre)}
                            <span class="inv-seccion-count">(${total})</span>
                        </div>
                        ${hayMas ? `
                            <button type="button" class="inv-seccion-vermas" data-vermas="${key}">
                                Ver todos (${total})
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="11" height="11">
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                    <polyline points="12 5 19 12 12 19"></polyline>
                                </svg>
                            </button>
                        ` : ''}
                    </div>
                    <div class="inv-prod-grid" style="padding:0; max-height:none;">
                        ${visibles.map(tarjetaProductoInv).join('')}
                    </div>
                </div>
            `;
        });

        invGrid.innerHTML = html;
    }

    /* ============================================
       MODAL VER MÁS
       ============================================ */
    function abrirVerMasInv(deptoKey) {
        let productosDepto;
        let titulo;

        if (deptoKey === 'sin_depto') {
            productosDepto = PRODUCTOS.filter(p => !p.id_departamento);
            titulo = 'Sin departamento';
        } else {
            productosDepto = PRODUCTOS.filter(p => String(p.id_departamento) === String(deptoKey));
            const depto = DEPARTAMENTOS.find(d => String(d.id) === String(deptoKey));
            titulo = depto ? depto.nombre : 'Departamento';
        }

        verMasDeptoActualInv = {
            key: deptoKey,
            productos: productosDepto
        };
        verMasPaginaActualInv = 1;

        modalVerMasInvTitle.textContent = titulo + ' — ' + productosDepto.length + ' producto' + (productosDepto.length !== 1 ? 's' : '');
        renderPaginaModalInv();
        modalVerMasInv.classList.add('visible');
    }

    function renderPaginaModalInv() {
        if (!verMasDeptoActualInv) return;

        const todos = verMasDeptoActualInv.productos;
        const totalPaginas = Math.ceil(todos.length / LIMITE_POR_PAGINA_MODAL_INV);
        const inicio = (verMasPaginaActualInv - 1) * LIMITE_POR_PAGINA_MODAL_INV;
        const fin = inicio + LIMITE_POR_PAGINA_MODAL_INV;
        const pagProductos = todos.slice(inicio, fin);

        modalVerMasInvGrid.innerHTML = pagProductos.map(tarjetaProductoInv).join('');

        const mostrandoInicio = inicio + 1;
        const mostrandoFin = Math.min(fin, todos.length);
        modalVerMasInvInfo.textContent = `Mostrando ${mostrandoInicio}–${mostrandoFin} de ${todos.length}`;

        if (totalPaginas <= 1) {
            modalVerMasInvPaginacion.innerHTML = '';
            modalVerMasInvPaginacion.style.display = 'none';
            return;
        }
        modalVerMasInvPaginacion.style.display = 'flex';

        let pag = '';
        pag += `<button type="button" class="modal-pagina-btn nav" data-pagina="${verMasPaginaActualInv - 1}" ${verMasPaginaActualInv === 1 ? 'disabled' : ''}>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </button>`;

        for (let i = 1; i <= totalPaginas; i++) {
            pag += `<button type="button" class="modal-pagina-btn ${i === verMasPaginaActualInv ? 'activo' : ''}" data-pagina="${i}">${i}</button>`;
        }

        pag += `<button type="button" class="modal-pagina-btn nav" data-pagina="${verMasPaginaActualInv + 1}" ${verMasPaginaActualInv === totalPaginas ? 'disabled' : ''}>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14">
                <polyline points="9 18 15 12 9 6"></polyline>
            </svg>
        </button>`;

        modalVerMasInvPaginacion.innerHTML = pag;
    }

    /* ============================================
       INFO DEL PRODUCTO SELECCIONADO
       ============================================ */
    function renderSeleccionado() {
        const p = getProductoSeleccionado();

        if (!p) {
            invSeleccionado.innerHTML = `
                <div class="inv-prod-vacio-side">
                    Selecciona un producto<br>del catálogo
                </div>
            `;
            precioCostoActual.textContent = '—';
            precioVentaActual.textContent = '—';
            return;
        }

        const stock = parseInt(p.stock) || 0;
        const tieneFoto = p.foto ? true : false;

        invSeleccionado.innerHTML = `
            <div class="inv-prod-sel-card">
                <div class="inv-prod-sel-img">
                    ${tieneFoto 
                        ? `<img src="/AvilesPOSv2/assets/img/productos/${esc(p.foto)}" alt="${esc(p.nombre)}">`
                        : `<svg class="sin-foto" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" width="50" height="50">
                               <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                               <circle cx="8.5" cy="8.5" r="1.5"></circle>
                               <polyline points="21 15 16 10 5 21"></polyline>
                           </svg>`}
                </div>
                <div class="inv-prod-sel-nombre">${esc(p.nombre)}</div>
                <div class="inv-prod-sel-codigo">${esc(p.codigo)}</div>
                <div class="inv-prod-sel-stock">
                    <div class="inv-prod-sel-stock-label">Stock actual</div>
                    <div class="inv-prod-sel-stock-valor">${stock}</div>
                </div>
            </div>
        `;

        precioCostoActual.textContent = formatC(p.precio_costo);
        precioVentaActual.textContent = formatC(p.precio);
    }

    /* ============================================
       RESULTADO DEL MOVIMIENTO
       ============================================ */
    function getTipoActual() {
        const checked = document.querySelector('input[name="tipo"]:checked');
        return checked ? checked.value : 'entrada';
    }

    function actualizarResultado() {
        const p = getProductoSeleccionado();
        const cantidad = parseInt(inputCantidad.value) || 0;
        const tipo = getTipoActual();

        if (!p) {
            resultadoStock.textContent = 'Selecciona producto y cantidad';
            resultadoStock.style.color = '#666';
            return;
        }
        if (cantidad <= 0) {
            resultadoStock.textContent = 'Ingresa una cantidad';
            resultadoStock.style.color = '#666';
            return;
        }

        // FORZAR conversión a número (evita que se concatenen strings)
        const stockActual = parseInt(p.stock) || 0;
        const stockNuevo = tipo === 'entrada' ? stockActual + cantidad : stockActual - cantidad;

        if (tipo === 'salida' && stockNuevo < 0) {
            resultadoStock.innerHTML = `<span style="color:#b00020; font-weight:600;">No hay suficiente stock (disponible: ${stockActual})</span>`;
        } else {
            const color = tipo === 'entrada' ? '#1e7a3f' : '#b00020';
            resultadoStock.innerHTML = `Stock: <strong>${stockActual}</strong> → <strong style="color:${color};">${stockNuevo}</strong>`;
        }
    }

    /* ============================================
       MOSTRAR / OCULTAR SECCIONES SEGÚN TIPO
       ============================================ */
    function actualizarVistaPorTipo() {
        const tipo = getTipoActual();

        if (tipo === 'entrada') {
            seccionProveedor.style.display = 'block';
            seccionPrecios.style.display = 'block';
            reqComentario.style.display = 'none';
            helpComentario.textContent = 'Opcional';
            textareaComentario.removeAttribute('required');
        } else {
            seccionProveedor.style.display = 'none';
            seccionPrecios.style.display = 'none';
            reqComentario.style.display = 'inline';
            helpComentario.textContent = 'Obligatorio para salidas: explica la razón';
            textareaComentario.setAttribute('required', 'required');
            document.getElementById('id_proveedor').value = '';
            document.getElementById('precio_costo_nuevo').value = '';
            document.getElementById('precio_venta_nuevo').value = '';
        }

        actualizarResultado();
    }

    /* ============================================
       SELECCIONAR PRODUCTO
       ============================================ */
    function seleccionarProducto(id) {
        idSeleccionado = String(id);
        if (hiddenProducto) hiddenProducto.value = idSeleccionado;
        renderGrid();
        renderSeleccionado();
        actualizarResultado();
    }

    /* ============================================
       EVENTOS
       ============================================ */
    // Grid principal (seleccionar o abrir ver más)
    if (invGrid) {
        invGrid.addEventListener('click', function (e) {
            // Ver más
            const btnVermas = e.target.closest('[data-vermas]');
            if (btnVermas) {
                abrirVerMasInv(btnVermas.dataset.vermas);
                return;
            }
            // Seleccionar
            const item = e.target.closest('.inv-prod-item');
            if (!item) return;
            seleccionarProducto(item.dataset.id);
        });
    }

    // Modal Ver más: seleccionar producto y cerrar
    if (modalVerMasInvGrid) {
        modalVerMasInvGrid.addEventListener('click', function (e) {
            const item = e.target.closest('.inv-prod-item');
            if (!item) return;
            seleccionarProducto(item.dataset.id);
            modalVerMasInv.classList.remove('visible');
        });
    }

    // Paginación del modal
    if (modalVerMasInvPaginacion) {
        modalVerMasInvPaginacion.addEventListener('click', function (e) {
            const btn = e.target.closest('[data-pagina]');
            if (!btn || btn.disabled) return;
            const p = parseInt(btn.dataset.pagina);
            if (isNaN(p) || p < 1) return;
            verMasPaginaActualInv = p;
            renderPaginaModalInv();
            modalVerMasInvGrid.parentElement.scrollTop = 0;
        });
    }

    // Cerrar modal
    if (modalVerMasInv) {
        modalVerMasInv.querySelectorAll('[data-close]').forEach(btn => {
            btn.addEventListener('click', () => modalVerMasInv.classList.remove('visible'));
        });
    }

    // ESC para cerrar
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modalVerMasInv && modalVerMasInv.classList.contains('visible')) {
            modalVerMasInv.classList.remove('visible');
        }
    });

    // Tabs
    if (invTabs) {
        invTabs.addEventListener('click', function (e) {
            const tab = e.target.closest('.inv-prod-tab');
            if (!tab) return;
            deptoActual = tab.dataset.depto;
            renderTabs();
            renderGrid();
        });
    }

    // Buscador
    if (invBuscador) {
        invBuscador.addEventListener('input', function () {
            busqueda = this.value;
            if (this.value.length > 0) {
                invBuscadorClear.classList.add('visible');
            } else {
                invBuscadorClear.classList.remove('visible');
            }
            renderGrid();
        });

        invBuscador.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const q = this.value.trim().toLowerCase();
                if (q === '') return;
                let match = PRODUCTOS.find(p => String(p.codigo).toLowerCase() === q);
                if (!match) match = PRODUCTOS.find(p => p.nombre.toLowerCase() === q);
                if (!match) {
                    const filtrados = PRODUCTOS.filter(p =>
                        p.nombre.toLowerCase().includes(q) ||
                        String(p.codigo).toLowerCase().includes(q)
                    );
                    if (filtrados.length === 1) match = filtrados[0];
                }
                if (match) {
                    seleccionarProducto(match.id);
                    this.value = '';
                    busqueda = '';
                    invBuscadorClear.classList.remove('visible');
                    renderGrid();
                }
            }
        });
    }

    if (invBuscadorClear) {
        invBuscadorClear.addEventListener('click', function () {
            invBuscador.value = '';
            busqueda = '';
            invBuscadorClear.classList.remove('visible');
            renderGrid();
            invBuscador.focus();
        });
    }

    // Cantidad
    if (inputCantidad) {
        inputCantidad.addEventListener('input', actualizarResultado);
    }

    // Tipo
    radiosTipo.forEach(r => {
        r.addEventListener('change', actualizarVistaPorTipo);
    });

    /* ============================================
       INICIALIZAR
       ============================================ */
    renderTabs();
    renderGrid();
    renderSeleccionado();
    actualizarVistaPorTipo();

});