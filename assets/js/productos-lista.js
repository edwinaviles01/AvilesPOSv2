document.addEventListener('DOMContentLoaded', function () {

    /* ============================================
       DATOS GLOBALES
       ============================================ */
    const PRODUCTOS = window.PRODUCTOS_LISTA || [];
    const DEPARTAMENTOS = window.DEPARTAMENTOS_LISTA || [];
    const LIMITE_POR_SECCION = 6;
    const LIMITE_POR_PAGINA_MODAL = 4;

    let deptoActual = '';       // '' = Todos
    let busqueda = '';

    /* ============================================
       ELEMENTOS
       ============================================ */
    const buscador = document.getElementById('prodBuscador');
    const btnBuscadorClear = document.getElementById('prodBuscadorClear');
    const tabs = document.getElementById('prodDeptoTabs');
    const contenedor = document.getElementById('prodContenedor');

    // Modal
    const modalDepto = document.getElementById('modalDeptoProductos');
    const modalDeptoTitle = document.getElementById('modalDeptoTitle');
    const modalDeptoGrid = document.getElementById('modalDeptoGrid');
    const modalDeptoPaginacion = document.getElementById('modalDeptoPaginacion');
    const modalDeptoInfo = document.getElementById('modalDeptoInfo');

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

    /* ============================================
       CONSTRUIR TARJETA DE PRODUCTO
       ============================================ */
    function tarjetaProducto(p) {
        const stock = parseInt(p.stock) || 0;
        const stock_minimo = parseInt(p.stock_minimo) || 0;

        let claseStock = '';
        let badgeStock = '';

        if (stock === 0) {
            claseStock = 'stock-agotado';
            badgeStock = '<span class="badge badge-agotado">Agotado</span>';
        } else if (stock_minimo > 0 && stock <= stock_minimo) {
            claseStock = 'stock-bajo';
            badgeStock = '<span class="badge badge-stock-bajo">Stock bajo</span>';
        }

        const tieneFoto = p.foto ? true : false;
        const inactivo = parseInt(p.activo) === 0;

        return `
            <div class="producto-card ${inactivo ? 'inactivo' : ''}">
                <div class="producto-img">
                    ${tieneFoto 
                        ? `<img src="/AvilesPOSv2/assets/img/productos/${esc(p.foto)}" alt="${esc(p.nombre)}">`
                        : `<div class="sin-foto">
                               <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" width="40" height="40">
                                   <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                   <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                   <polyline points="21 15 16 10 5 21"></polyline>
                               </svg>
                               <span>Sin foto</span>
                           </div>`}
                    <div class="producto-badges">
                        <div>${badgeStock}</div>
                        <div>${inactivo ? '<span class="badge badge-inactivo">Inactivo</span>' : ''}</div>
                    </div>
                </div>
                <div class="producto-info">
                    <div class="producto-codigo">${esc(p.codigo)}</div>
                    <div class="producto-nombre">${esc(p.nombre)}</div>
                    <div class="producto-depto">
                        ${esc(p.departamento || 'Sin departamento')}
                    </div>
                    <div class="producto-precio-row">
                        <div>
                            <div class="producto-precio-label">Precio</div>
                            <div class="producto-precio">${formatC(p.precio)}</div>
                        </div>
                        <div class="producto-stock ${claseStock}">
                            <div class="producto-precio-label">Stock</div>
                            <strong>${stock}</strong>
                        </div>
                    </div>
                </div>
                <div class="producto-acciones">
                    <a href="editar.php?id=${parseInt(p.id)}" class="edit" title="Editar">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                        Editar
                    </a>
                    <a href="eliminar.php?id=${parseInt(p.id)}" class="delete" title="Eliminar">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        </svg>
                        Eliminar
                    </a>
                </div>
            </div>
        `;
    }

    /* ============================================
       RENDER TABS
       ============================================ */
    function renderTabs() {
        if (!tabs) return;

        let html = `<button type="button" class="prod-depto-tab ${deptoActual === '' ? 'activo' : ''}" data-depto="">Todos</button>`;

        DEPARTAMENTOS.forEach(d => {
            const activo = String(deptoActual) === String(d.id) ? 'activo' : '';
            html += `<button type="button" class="prod-depto-tab ${activo}" data-depto="${d.id}">${esc(d.nombre)}</button>`;
        });

        tabs.innerHTML = html;
    }

    /* ============================================
       FILTRAR PRODUCTOS
       ============================================ */
    function filtrarProductos() {
        const q = busqueda.trim().toLowerCase();

        return PRODUCTOS.filter(p => {
            // Filtro por departamento
            if (deptoActual !== '' && String(p.id_departamento) !== String(deptoActual)) {
                return false;
            }
            // Filtro por búsqueda
            if (q === '') return true;
            return p.nombre.toLowerCase().includes(q) ||
                   String(p.codigo).toLowerCase().includes(q);
        });
    }

    /* ============================================
       RENDER CONTENIDO PRINCIPAL
       ============================================ */
    function renderContenido() {
        if (!contenedor) return;

        const productos = filtrarProductos();

        if (productos.length === 0) {
            contenedor.innerHTML = `
                <div class="prod-vacio">
                    ${busqueda !== '' ? 'No se encontraron productos con esa búsqueda' : 'No hay productos en este departamento'}
                </div>
            `;
            return;
        }

        // Si hay búsqueda, mostramos plano (sin agrupar)
        if (busqueda.trim() !== '') {
            contenedor.innerHTML = `
                <div class="prod-seccion">
                    <div class="prod-seccion-header">
                        <div class="prod-seccion-titulo">
                            Resultados de búsqueda
                            <span class="prod-seccion-count">(${productos.length})</span>
                        </div>
                    </div>
                    <div class="prod-grid">
                        ${productos.map(tarjetaProducto).join('')}
                    </div>
                </div>
            `;
            return;
        }

        // Sin búsqueda: agrupar por departamento
        // Agrupamos
        const grupos = {};

        productos.forEach(p => {
            const key = p.id_departamento ? String(p.id_departamento) : 'sin_depto';
            if (!grupos[key]) {
                grupos[key] = {
                    nombre: p.id_departamento ? (p.departamento || 'Sin nombre') : 'Sin departamento',
                    productos: []
                };
            }
            grupos[key].productos.push(p);
        });

        // Ordenamos: departamentos con nombre alfabético, y "Sin departamento" al final
        const keysOrdenadas = Object.keys(grupos).sort((a, b) => {
            if (a === 'sin_depto') return 1;
            if (b === 'sin_depto') return -1;
            return grupos[a].nombre.localeCompare(grupos[b].nombre);
        });

        // Si el usuario seleccionó "Todos", mostramos los grupos con límite
        // Si seleccionó un departamento específico, mostramos solo ese
        let html = '';

        keysOrdenadas.forEach(key => {
            const grupo = grupos[key];
            const total = grupo.productos.length;
            const visibles = grupo.productos.slice(0, LIMITE_POR_SECCION);
            const hayMas = total > LIMITE_POR_SECCION;

            html += `
                <div class="prod-seccion" data-seccion="${key}">
                    <div class="prod-seccion-header">
                        <div class="prod-seccion-titulo">
                            ${esc(grupo.nombre)}
                            <span class="prod-seccion-count">(${total})</span>
                        </div>
                        ${hayMas ? `
                            <button type="button" class="prod-seccion-vermas" data-vermas="${key}">
                                Ver todos (${total})
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14">
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                    <polyline points="12 5 19 12 12 19"></polyline>
                                </svg>
                            </button>
                        ` : ''}
                    </div>
                    <div class="prod-grid">
                        ${visibles.map(tarjetaProducto).join('')}
                    </div>
                </div>
            `;
        });

        contenedor.innerHTML = html;
    }

    /* ============================================
       MODAL: VER TODOS LOS PRODUCTOS DE UN DEPTO
       ============================================ */
    let deptoModalActual = null;
    let paginaModalActual = 1;

    function abrirModalDepto(deptoKey) {
        // Filtrar productos del departamento
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

        deptoModalActual = {
            key: deptoKey,
            productos: productosDepto
        };
        paginaModalActual = 1;

        modalDeptoTitle.textContent = titulo + ' — ' + productosDepto.length + ' producto' + (productosDepto.length !== 1 ? 's' : '');
        renderPaginaModal();
        modalDepto.classList.add('visible');
    }

    function renderPaginaModal() {
        if (!deptoModalActual) return;

        const todos = deptoModalActual.productos;
        const totalPaginas = Math.ceil(todos.length / LIMITE_POR_PAGINA_MODAL);
        const inicio = (paginaModalActual - 1) * LIMITE_POR_PAGINA_MODAL;
        const fin = inicio + LIMITE_POR_PAGINA_MODAL;
        const pagProductos = todos.slice(inicio, fin);

        // Grid
        modalDeptoGrid.innerHTML = pagProductos.map(tarjetaProducto).join('');

        // Info
        const mostrandoInicio = inicio + 1;
        const mostrandoFin = Math.min(fin, todos.length);
        modalDeptoInfo.textContent = `Mostrando ${mostrandoInicio}–${mostrandoFin} de ${todos.length}`;

        // Paginación
        if (totalPaginas <= 1) {
            modalDeptoPaginacion.innerHTML = '';
            modalDeptoPaginacion.style.display = 'none';
            return;
        }
        modalDeptoPaginacion.style.display = 'flex';

        let pag = '';

        // Flecha anterior
        pag += `<button type="button" class="modal-pagina-btn nav" data-pagina="${paginaModalActual - 1}" ${paginaModalActual === 1 ? 'disabled' : ''}>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </button>`;

        // Números
        for (let i = 1; i <= totalPaginas; i++) {
            pag += `<button type="button" class="modal-pagina-btn ${i === paginaModalActual ? 'activo' : ''}" data-pagina="${i}">${i}</button>`;
        }

        // Flecha siguiente
        pag += `<button type="button" class="modal-pagina-btn nav" data-pagina="${paginaModalActual + 1}" ${paginaModalActual === totalPaginas ? 'disabled' : ''}>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14">
                <polyline points="9 18 15 12 9 6"></polyline>
            </svg>
        </button>`;

        modalDeptoPaginacion.innerHTML = pag;
    }

    if (modalDeptoPaginacion) {
        modalDeptoPaginacion.addEventListener('click', function (e) {
            const btn = e.target.closest('[data-pagina]');
            if (!btn || btn.disabled) return;
            const p = parseInt(btn.dataset.pagina);
            if (isNaN(p) || p < 1) return;
            paginaModalActual = p;
            renderPaginaModal();
            // Scroll al inicio del body del modal
            modalDeptoGrid.parentElement.scrollTop = 0;
        });
    }

    // Cerrar modal
    if (modalDepto) {
        modalDepto.querySelectorAll('[data-close]').forEach(btn => {
            btn.addEventListener('click', () => modalDepto.classList.remove('visible'));
        });
    }

    // ESC
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modalDepto && modalDepto.classList.contains('visible')) {
            modalDepto.classList.remove('visible');
        }
    });

    /* ============================================
       EVENTOS
       ============================================ */

    // Tabs
    if (tabs) {
        tabs.addEventListener('click', function (e) {
            const tab = e.target.closest('.prod-depto-tab');
            if (!tab) return;
            deptoActual = tab.dataset.depto;
            renderTabs();
            renderContenido();
        });
    }

    // Botón "Ver más"
    if (contenedor) {
        contenedor.addEventListener('click', function (e) {
            const btn = e.target.closest('[data-vermas]');
            if (!btn) return;
            abrirModalDepto(btn.dataset.vermas);
        });
    }

    // Buscador
    if (buscador) {
        buscador.addEventListener('input', function () {
            busqueda = this.value;
            if (this.value.length > 0) {
                btnBuscadorClear.classList.add('visible');
            } else {
                btnBuscadorClear.classList.remove('visible');
            }
            renderContenido();
        });
    }

    if (btnBuscadorClear) {
        btnBuscadorClear.addEventListener('click', function () {
            buscador.value = '';
            busqueda = '';
            btnBuscadorClear.classList.remove('visible');
            renderContenido();
            buscador.focus();
        });
    }

    /* ============================================
       INICIALIZAR
       ============================================ */
    renderTabs();
    renderContenido();
});