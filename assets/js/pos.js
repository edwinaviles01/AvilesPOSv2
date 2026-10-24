document.addEventListener('DOMContentLoaded', function () {

    /* ============================================
       DATOS GLOBALES
       ============================================ */
    const PRODUCTOS = window.PRODUCTOS || [];
    const IVA = window.IVA_PORCENTAJE || 15;
    const TIPO_CAMBIO = window.TIPO_CAMBIO || 36.50;

    const carrito = {};
    let descuentoTipo = 'monto';
    let metodoPago = 'efectivo';

    /* ============================================
       ELEMENTOS
       ============================================ */
    const buscador = document.getElementById('posBuscador');
    const buscadorClear = document.getElementById('posBuscadorClear');
    const resultados = document.getElementById('posResultados');
    const carritoLista = document.getElementById('carritoLista');
    const carritoCount = document.getElementById('carritoCount');
    const btnVaciarCarrito = document.getElementById('btnVaciarCarrito');

    const btnEntradaDinero = document.getElementById('btnEntradaDinero');
    const btnSalidaDinero = document.getElementById('btnSalidaDinero');
    const modalMovCaja = document.getElementById('modalMovCaja');
    const movCajaTitulo = document.getElementById('movCajaTitulo');
    const movCajaInfo = document.getElementById('movCajaInfo');
    const movCajaInfoText = document.getElementById('movCajaInfoText');
    const movCajaMonto = document.getElementById('movCajaMonto');
    const movCajaMotivo = document.getElementById('movCajaMotivo');
    const movCajaError = document.getElementById('movCajaError');
    const movCajaGuardar = document.getElementById('movCajaGuardar');
    const movCajaCancelar = document.getElementById('movCajaCancelar');
    const movCajaCerrarX = document.getElementById('movCajaCerrarX');
    let movCajaTipo = 'entrada';

        // Modal abonar
    const btnAbonar = document.getElementById('btnAbonar');
    const modalAbonar = document.getElementById('modalAbonar');
    const abonarCerrarX = document.getElementById('abonarCerrarX');
    const abonarCancelar = document.getElementById('abonarCancelar');
    const abonarGuardar = document.getElementById('abonarGuardar');
    const abonarCliente = document.getElementById('abonarCliente');
    const abonarInfoCliente = document.getElementById('abonarInfoCliente');
    const abonarSaldoActual = document.getElementById('abonarSaldoActual');
    const abonarMonto = document.getElementById('abonarMonto');
    const abonarMetodo = document.getElementById('abonarMetodo');
    const abonarGrupoReferencia = document.getElementById('abonarGrupoReferencia');
    const abonarReferencia = document.getElementById('abonarReferencia');
    const abonarObservaciones = document.getElementById('abonarObservaciones');
    const abonarError = document.getElementById('abonarError');
    const abonarHelpMonto = document.getElementById('abonarHelpMonto');
    const CLIENTES_SALDO = window.CLIENTES_SALDO || [];

    const descuentoInput = document.getElementById('descuentoInput');
    const descTipoMonto = document.getElementById('descTipoMonto');
    const descTipoPorcentaje = document.getElementById('descTipoPorcentaje');
    const posLineaDescuento = document.getElementById('posLineaDescuento');

    const posSubtotal = document.getElementById('posSubtotal');
    const posDescuento = document.getElementById('posDescuento');
    const posIva = document.getElementById('posIva');
    const posTotal = document.getElementById('posTotal');
    const btnCobrar = document.getElementById('btnCobrar');

    // Modal cobro
    const modalCobro = document.getElementById('modalCobro');
    const btnCerrarCobro = document.getElementById('btnCerrarCobro');
    const btnCancelarCobro = document.getElementById('btnCancelarCobro');
    const btnConfirmarCobro = document.getElementById('btnConfirmarCobro');
    const cobroTotal = document.getElementById('cobroTotal');
    const cobroMetodos = document.querySelectorAll('.cobro-metodo');
    const seccionEfectivo = document.getElementById('seccionEfectivo');
    const seccionTarjeta = document.getElementById('seccionTarjeta');
    const seccionTransferencia = document.getElementById('seccionTransferencia');
    const seccionCredito = document.getElementById('seccionCredito');
    const pagoCordobas = document.getElementById('pagoCordobas');
    const pagoDolares = document.getElementById('pagoDolares');
    const chkCobrarUSD = document.getElementById('chkCobrarUSD');
    const grupoPagoCordobas = document.getElementById('grupoPagoCordobas');
    const grupoPagoDolares = document.getElementById('grupoPagoDolares');
    const cobroVuelto = document.getElementById('cobroVuelto');
    const vueltoValor = document.getElementById('vueltoValor');
    const vueltoDetalle = document.getElementById('vueltoDetalle');
    const tcMostrado = document.getElementById('tcMostrado');
    const creditoClienteInfo = document.getElementById('creditoClienteInfo');
    const posClienteModal = document.getElementById('posClienteModal');
    const referenciaTarjeta = document.getElementById('referenciaTarjeta');
    const referenciaTransferencia = document.getElementById('referenciaTransferencia');
    const imprimirTicket = document.getElementById('imprimirTicket');
    const ventaObservaciones = document.getElementById('ventaObservaciones');
    const errorCobro = document.getElementById('errorCobro');

    // Modal confirmación
    const modalConfirmar = document.getElementById('modalConfirmar');
    const confirmTitulo = document.getElementById('confirmTitulo');
    const confirmMensaje = document.getElementById('confirmMensaje');
    const confirmAceptar = document.getElementById('confirmAceptar');
    const confirmCancelar = document.getElementById('confirmCancelar');
    const confirmCerrarX = document.getElementById('confirmCerrarX');
    let confirmCallback = null;

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

    function getProducto(id) {
        return PRODUCTOS.find(p => String(p.id) === String(id)) || null;
    }

    function getStockDisponible(p) {
        const stock = parseInt(p.stock) || 0;
        const enCarrito = carrito[p.id] ? carrito[p.id].cantidad : 0;
        return stock - enCarrito;
    }

    /* ============================================
       BUSCADOR CON DROPDOWN
       ============================================ */
    function filtrarProductos(q) {
        const query = q.trim().toLowerCase();
        if (query === '') return [];

        return PRODUCTOS.filter(p => {
            return p.nombre.toLowerCase().includes(query) ||
                   String(p.codigo).toLowerCase().includes(query);
        }).slice(0, 20);
    }

    function renderResultados() {
        const q = buscador.value.trim();

        if (q === '') {
            resultados.classList.remove('visible');
            resultados.innerHTML = '';
            return;
        }

        const filtrados = filtrarProductos(q);

        if (filtrados.length === 0) {
            resultados.innerHTML = '<div class="nv-resultados-vacio">No se encontraron productos</div>';
            resultados.classList.add('visible');
            return;
        }

        resultados.innerHTML = filtrados.map(p => {
            const stockDisp = getStockDisponible(p);
            const sinStock = stockDisp <= 0;
            const claseStock = sinStock ? 'agotado' : (stockDisp <= 5 ? 'bajo' : '');
            const tieneFoto = p.foto ? true : false;

            return `
                <button type="button" class="nv-resultado-item" data-id="${p.id}" ${sinStock ? 'disabled style="opacity:0.5; cursor:not-allowed;"' : ''}>
                    <div class="nv-resultado-img">
                        ${tieneFoto 
                            ? `<img src="/AvilesPOSv2/assets/img/productos/${esc(p.foto)}" alt="${esc(p.nombre)}">`
                            : `<svg class="sin-foto" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" width="20" height="20">
                                   <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                   <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                   <polyline points="21 15 16 10 5 21"></polyline>
                               </svg>`}
                    </div>
                    <div class="nv-resultado-info">
                        <div class="nv-resultado-nombre">${esc(p.nombre)}</div>
                        <div class="nv-resultado-codigo">${esc(p.codigo)}</div>
                    </div>
                    <div class="nv-resultado-precio">${formatC(p.precio)}</div>
                    <div class="nv-resultado-stock ${claseStock}">${stockDisp} disp.</div>
                </button>
            `;
        }).join('') + '<div class="nv-resultados-hint">Enter para agregar · Esc para cerrar</div>';

        resultados.classList.add('visible');
    }

    function ocultarResultados() {
        resultados.classList.remove('visible');
        resultados.innerHTML = '';
    }

    // Buscador: input
    buscador.addEventListener('input', function () {
        renderResultados();
        buscadorClear.classList.toggle('visible', this.value.length > 0);
    });

    // Buscador: click en un resultado
    resultados.addEventListener('click', function (e) {
        const item = e.target.closest('.nv-resultado-item');
        if (!item || item.disabled) return;
        agregarProducto(item.dataset.id);
        buscador.value = '';
        buscadorClear.classList.remove('visible');
        ocultarResultados();
        buscador.focus();
    });

    // Buscador: Enter
    buscador.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            ocultarResultados();
            this.value = '';
            buscadorClear.classList.remove('visible');
            return;
        }

        if (e.key === 'Enter') {
            e.preventDefault();
            const q = this.value.trim();
            if (q === '') return;

            const query = q.toLowerCase();

            // 1. Match exacto por código (lo que hace un lector de barras)
            let match = PRODUCTOS.find(p => String(p.codigo).toLowerCase() === query);

            // 2. Match exacto por nombre
            if (!match) match = PRODUCTOS.find(p => p.nombre.toLowerCase() === query);

            // 3. Si solo hay un resultado, lo usamos
            if (!match) {
                const filtrados = filtrarProductos(q);
                if (filtrados.length === 1) match = filtrados[0];
            }

            if (match) {
                agregarProducto(match.id);
                this.value = '';
                buscadorClear.classList.remove('visible');
                ocultarResultados();
            }
        }
    });

    // Cerrar dropdown al hacer click fuera
    document.addEventListener('click', function (e) {
        if (!e.target.closest('.nv-buscador-wrap')) {
            ocultarResultados();
        }
    });

    // Botón limpiar buscador
    buscadorClear.addEventListener('click', function () {
        buscador.value = '';
        buscadorClear.classList.remove('visible');
        ocultarResultados();
        buscador.focus();
    });

    /* ============================================
       AGREGAR PRODUCTO AL CARRITO
       ============================================ */
    function agregarProducto(id) {
        const p = getProducto(id);
        if (!p) return;

        const stockDisp = getStockDisponible(p);

        if (stockDisp <= 0) {
            alert('No hay más stock disponible de este producto.');
            return;
        }

        if (carrito[id]) {
            carrito[id].cantidad++;
        } else {
            carrito[id] = { cantidad: 1, producto: p };
        }

        renderCarrito();
    }

    /* ============================================
       RENDER CARRITO
       ============================================ */
    function renderCarrito() {
        const items = Object.values(carrito);

        if (items.length === 0) {
            carritoLista.innerHTML = `
                <div class="nv-vacio">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" width="60" height="60">
                        <circle cx="9" cy="21" r="1"></circle>
                        <circle cx="20" cy="21" r="1"></circle>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                    </svg>
                    <div>El carrito está vacío</div>
                    <div style="font-size:12px; margin-top:6px;">Escanea o busca un producto para empezar</div>
                </div>
            `;
            carritoCount.textContent = '0';
            btnVaciarCarrito.style.display = 'none';
            actualizarTotales();
            return;
        }

        carritoLista.innerHTML = items.map(item => {
            const p = item.producto;
            const subtotal = item.cantidad * parseFloat(p.precio);
            const stock = parseInt(p.stock) || 0;
            const tieneFoto = p.foto ? true : false;

            return `
                <div class="nv-item" data-id="${p.id}">
                    <div class="nv-item-img">
                        ${tieneFoto 
                            ? `<img src="/AvilesPOSv2/assets/img/productos/${esc(p.foto)}" alt="${esc(p.nombre)}">`
                            : `<svg class="sin-foto" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" width="28" height="28">
                                   <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                   <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                   <polyline points="21 15 16 10 5 21"></polyline>
                               </svg>`}
                    </div>
                    <div class="nv-item-info">
                        <div class="nv-item-nombre">${esc(p.nombre)}</div>
                        <div class="nv-item-codigo">${esc(p.codigo)}</div>
                    </div>
                    <div class="nv-item-precio">
                        <strong>${formatC(p.precio)}</strong>
                        c/u
                    </div>
                    <div class="nv-item-cantidad">
                        <div class="nv-cantidad-control">
                            <button type="button" data-restar="${p.id}" ${item.cantidad <= 1 ? 'disabled' : ''}>−</button>
                            <input type="number" value="${item.cantidad}" data-cantidad="${p.id}" min="1" max="${stock}">
                            <button type="button" data-sumar="${p.id}" ${item.cantidad >= stock ? 'disabled' : ''}>+</button>
                        </div>
                    </div>
                    <div class="nv-item-subtotal">${formatC(subtotal)}</div>
                    <button type="button" class="nv-item-eliminar" data-eliminar="${p.id}" title="Eliminar">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        </svg>
                    </button>
                </div>
            `;
        }).join('');

        const totalItems = items.reduce((s, i) => s + i.cantidad, 0);
        carritoCount.textContent = totalItems;
        btnVaciarCarrito.style.display = 'flex';

        actualizarTotales();
    }

    /* ============================================
       EVENTOS DEL CARRITO
       ============================================ */
    carritoLista.addEventListener('click', function (e) {
        const btnEliminar = e.target.closest('[data-eliminar]');
        const btnSumar = e.target.closest('[data-sumar]');
        const btnRestar = e.target.closest('[data-restar]');

        if (btnEliminar) {
            const id = btnEliminar.dataset.eliminar;
            delete carrito[id];
            renderCarrito();
            return;
        }

        if (btnSumar) {
            const id = btnSumar.dataset.sumar;
            const p = getProducto(id);
            const stock = parseInt(p.stock) || 0;
            if (carrito[id] && carrito[id].cantidad < stock) {
                carrito[id].cantidad++;
                renderCarrito();
            }
            return;
        }

        if (btnRestar) {
            const id = btnRestar.dataset.restar;
            if (carrito[id] && carrito[id].cantidad > 1) {
                carrito[id].cantidad--;
                renderCarrito();
            }
        }
    });

    carritoLista.addEventListener('change', function (e) {
        const input = e.target.closest('[data-cantidad]');
        if (!input) return;

        const id = input.dataset.cantidad;
        const p = getProducto(id);
        if (!p || !carrito[id]) return;

        const stock = parseInt(p.stock) || 0;
        let nueva = parseInt(input.value) || 1;

        if (nueva < 1) nueva = 1;
        if (nueva > stock) {
            nueva = stock;
            alert('Máximo disponible: ' + stock);
        }

        carrito[id].cantidad = nueva;
        renderCarrito();
    });

    carritoLista.addEventListener('input', function (e) {
        const input = e.target.closest('[data-cantidad]');
        if (!input) return;
        input.value = input.value.replace(/[^0-9]/g, '');
    });

    /* ============================================
       CÁLCULOS
       ============================================ */
    function calcularTotales() {
        const items = Object.values(carrito);
        let subtotalBruto = 0;

        items.forEach(item => {
            subtotalBruto += item.cantidad * parseFloat(item.producto.precio);
        });

        const descValor = parseFloat(descuentoInput.value) || 0;
        let descuentoMonto = 0;

        if (descuentoTipo === 'monto') {
            descuentoMonto = descValor;
        } else {
            descuentoMonto = subtotalBruto * (descValor / 100);
        }

        if (descuentoMonto > subtotalBruto) descuentoMonto = subtotalBruto;
        if (descuentoMonto < 0) descuentoMonto = 0;

        const total = subtotalBruto - descuentoMonto;
        const subtotalSinIva = total / (1 + IVA / 100);
        const iva = total - subtotalSinIva;

        return { subtotalBruto, descuentoMonto, total, subtotalSinIva, iva };
    }

    function actualizarTotales() {
        const t = calcularTotales();

        posSubtotal.textContent = formatC(t.subtotalSinIva);
        posIva.textContent = formatC(t.iva);
        posTotal.textContent = formatC(t.total);

        if (t.descuentoMonto > 0.001) {
            posLineaDescuento.style.display = 'flex';
            posDescuento.textContent = '−' + formatC(t.descuentoMonto);
        } else {
            posLineaDescuento.style.display = 'none';
        }

        const hayItems = Object.keys(carrito).length > 0;
        btnCobrar.disabled = !hayItems;

        const svg = btnCobrar.querySelector('svg');
        btnCobrar.innerHTML = '';
        if (svg) btnCobrar.appendChild(svg);
        btnCobrar.appendChild(document.createTextNode(' Cobrar'));
    }

    /* ============================================
       DESCUENTO
       ============================================ */
    descTipoMonto.addEventListener('click', function () {
        descuentoTipo = 'monto';
        descTipoMonto.classList.add('activo');
        descTipoPorcentaje.classList.remove('activo');
        descuentoInput.placeholder = '0.00';
        actualizarTotales();
    });

    descTipoPorcentaje.addEventListener('click', function () {
        descuentoTipo = 'porcentaje';
        descTipoPorcentaje.classList.add('activo');
        descTipoMonto.classList.remove('activo');
        descuentoInput.placeholder = '0';
        actualizarTotales();
    });

    descuentoInput.addEventListener('input', function () {
        if (descuentoTipo === 'porcentaje') {
            let v = parseFloat(this.value) || 0;
            if (v > 100) this.value = 100;
            if (v < 0) this.value = 0;
        }
        actualizarTotales();
    });

    /* ============================================
       MODAL DE CONFIRMACIÓN
       ============================================ */
    function abrirConfirmacion(titulo, mensaje, callback, textoAceptar) {
        confirmTitulo.textContent = titulo;
        confirmMensaje.textContent = mensaje;
        confirmAceptar.textContent = textoAceptar || 'Confirmar';
        confirmCallback = callback;
        modalConfirmar.classList.add('visible');
    }

    function cerrarConfirmacion() {
        modalConfirmar.classList.remove('visible');
        confirmCallback = null;
    }

    confirmAceptar.addEventListener('click', function () {
        const cb = confirmCallback;
        cerrarConfirmacion();
        if (cb) cb();
    });

    confirmCancelar.addEventListener('click', cerrarConfirmacion);
    confirmCerrarX.addEventListener('click', cerrarConfirmacion);

    /* ============================================
       VACIAR CARRITO
       ============================================ */
    btnVaciarCarrito.addEventListener('click', function () {
        if (Object.keys(carrito).length === 0) return;
        const totalItems = Object.values(carrito).reduce((s, i) => s + i.cantidad, 0);

        abrirConfirmacion(
            'Vaciar carrito',
            `Se eliminarán ${totalItems} producto${totalItems !== 1 ? 's' : ''} del carrito. ¿Continuar?`,
            function () {
                Object.keys(carrito).forEach(k => delete carrito[k]);
                renderCarrito();
            },
            'Sí, vaciar'
        );
    });

        /* ============================================
       MOVIMIENTO DE CAJA (Entrada/Salida)
       ============================================ */
    function abrirMovCaja(tipo) {
        movCajaTipo = tipo;

        if (tipo === 'entrada') {
            movCajaTitulo.textContent = 'Entrada de dinero';
            movCajaInfo.className = 'cobro-info success';
            movCajaInfoText.textContent = 'Registra el ingreso de efectivo a la caja.';
            movCajaGuardar.textContent = 'Registrar entrada';
            movCajaGuardar.style.background = '#1e7a3f';
        } else {
            movCajaTitulo.textContent = 'Salida de dinero';
            movCajaInfo.className = 'cobro-info warning';
            movCajaInfoText.textContent = 'Registra la salida de efectivo. Es obligatorio el motivo.';
            movCajaGuardar.textContent = 'Registrar salida';
            movCajaGuardar.style.background = '#b00020';
        }

        movCajaMonto.value = '';
        movCajaMotivo.value = '';
        movCajaError.textContent = '';
        movCajaGuardar.disabled = false;

        modalMovCaja.classList.add('visible');
        setTimeout(() => movCajaMonto.focus(), 100);
    }

    function cerrarMovCaja() {
        modalMovCaja.classList.remove('visible');
    }

    if (btnEntradaDinero) {
        btnEntradaDinero.addEventListener('click', () => abrirMovCaja('entrada'));
    }

    if (btnSalidaDinero) {
        btnSalidaDinero.addEventListener('click', () => abrirMovCaja('salida'));
    }

    if (movCajaCancelar) movCajaCancelar.addEventListener('click', cerrarMovCaja);
    if (movCajaCerrarX) movCajaCerrarX.addEventListener('click', cerrarMovCaja);

    if (movCajaGuardar) {
        movCajaGuardar.addEventListener('click', function () {
            movCajaError.textContent = '';

            const monto = parseFloat(movCajaMonto.value) || 0;
            const motivo = movCajaMotivo.value.trim();

            if (monto <= 0) {
                movCajaError.textContent = 'El monto debe ser mayor a 0.';
                return;
            }
            if (motivo === '') {
                movCajaError.textContent = 'El motivo es obligatorio.';
                return;
            }

            movCajaGuardar.disabled = true;
            movCajaGuardar.textContent = 'Guardando...';

            fetch('/AvilesPOSv2/modulos/ventas/api/guardar_movimiento_caja.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ tipo: movCajaTipo, monto: monto, motivo: motivo })
            })
            .then(r => r.json())
            .then(data => {
                if (data.ok) {
                    cerrarMovCaja();
                    // Toast de confirmación
                    const toast = document.createElement('div');
                    toast.style.cssText = 'position:fixed;bottom:20px;left:50%;transform:translateX(-50%);color:#fff;padding:12px 22px;border-radius:8px;font-weight:600;font-size:14px;box-shadow:0 10px 30px rgba(0,0,0,0.2);z-index:9999;';
                    toast.style.background = movCajaTipo === 'entrada' ? '#1e7a3f' : '#b00020';
                    toast.textContent = (movCajaTipo === 'entrada' ? 'Entrada de ' : 'Salida de ') + formatC(monto) + ' registrada';
                    document.body.appendChild(toast);
                    setTimeout(() => toast.remove(), 3000);
                } else {
                    movCajaError.textContent = data.error || 'Error al guardar.';
                    movCajaGuardar.disabled = false;
                    movCajaGuardar.textContent = movCajaTipo === 'entrada' ? 'Registrar entrada' : 'Registrar salida';
                }
            })
            .catch(() => {
                movCajaError.textContent = 'Error de conexión.';
                movCajaGuardar.disabled = false;
                movCajaGuardar.textContent = movCajaTipo === 'entrada' ? 'Registrar entrada' : 'Registrar salida';
            });
        });

            /* ============================================
       MODAL ABONAR
       ============================================ */
    function abrirAbonar() {
        // Repoblar el select de clientes con saldo
        abonarCliente.innerHTML = '<option value="">— Seleccionar cliente con saldo —</option>';
        CLIENTES_SALDO.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c.id;
            opt.textContent = c.nombre;
            opt.dataset.saldo = c.saldo_actual;
            abonarCliente.appendChild(opt);
        });

        abonarCliente.value = '';
        abonarInfoCliente.style.display = 'none';
        abonarMonto.value = '';
        abonarMetodo.value = 'efectivo';
        abonarReferencia.value = '';
        abonarGrupoReferencia.style.display = 'none';
        abonarObservaciones.value = '';
        abonarError.textContent = '';
        abonarHelpMonto.textContent = '';
        abonarGuardar.disabled = false;
        abonarGuardar.textContent = 'Registrar abono';

        modalAbonar.classList.add('visible');
        setTimeout(() => abonarCliente.focus(), 100);
    }

    function cerrarAbonar() {
        modalAbonar.classList.remove('visible');
    }

    if (btnAbonar) {
        btnAbonar.addEventListener('click', abrirAbonar);
    }

    if (abonarCancelar) abonarCancelar.addEventListener('click', cerrarAbonar);
    if (abonarCerrarX) abonarCerrarX.addEventListener('click', cerrarAbonar);

    // Al cambiar el cliente, mostrar saldo actual
    if (abonarCliente) {
        abonarCliente.addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            const saldo = parseFloat(opt.dataset.saldo) || 0;

            if (!this.value) {
                abonarInfoCliente.style.display = 'none';
                abonarHelpMonto.textContent = '';
                return;
            }

            abonarInfoCliente.style.display = 'block';
            abonarSaldoActual.textContent = formatC(saldo);
            abonarHelpMonto.textContent = 'Saldo pendiente: ' + formatC(saldo);
            abonarMonto.max = saldo;
            abonarMonto.focus();
        });
    }

    // Validar monto en tiempo real
    if (abonarMonto) {
        abonarMonto.addEventListener('input', function () {
            const opt = abonarCliente.options[abonarCliente.selectedIndex];
            const saldo = parseFloat(opt?.dataset?.saldo) || 0;
            const monto = parseFloat(this.value) || 0;

            if (monto > saldo) {
                abonarHelpMonto.innerHTML = '<span style="color:#b00020;">El monto supera el saldo pendiente (' + formatC(saldo) + ')</span>';
            } else if (monto > 0) {
                const resto = saldo - monto;
                abonarHelpMonto.textContent = 'Saldo después del abono: ' + formatC(resto);
            } else {
                abonarHelpMonto.textContent = 'Saldo pendiente: ' + formatC(saldo);
            }
        });
    }

        // Mostrar/ocultar el campo de referencia según el método
    if (abonarMetodo) {
        abonarMetodo.addEventListener('change', function () {
            if (this.value === 'efectivo') {
                abonarGrupoReferencia.style.display = 'none';
                abonarReferencia.value = '';
            } else {
                abonarGrupoReferencia.style.display = 'block';
            }
        });
    }
    
    if (abonarGuardar) {
        abonarGuardar.addEventListener('click', function () {
            abonarError.textContent = '';

            const idCliente = abonarCliente.value;
            const monto = parseFloat(abonarMonto.value) || 0;

            if (!idCliente) {
                abonarError.textContent = 'Selecciona un cliente.';
                return;
            }
            if (monto <= 0) {
                abonarError.textContent = 'El monto debe ser mayor a 0.';
                return;
            }

            const opt = abonarCliente.options[abonarCliente.selectedIndex];
            const saldo = parseFloat(opt.dataset.saldo) || 0;

            if (monto > saldo) {
                abonarError.textContent = 'El monto supera el saldo pendiente (' + formatC(saldo) + ').';
                return;
            }

            abonarGuardar.disabled = true;
            abonarGuardar.textContent = 'Registrando...';

            fetch('/AvilesPOSv2/modulos/ventas/api/guardar_abono.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id_cliente: parseInt(idCliente),
                    monto: monto,
                    metodo_pago: abonarMetodo.value,
                    referencia: abonarReferencia.value.trim(),
                    observaciones: abonarObservaciones.value.trim()
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.ok) {
                    cerrarAbonar();
                    // Actualizar el saldo en memoria
                    const idx = CLIENTES_SALDO.findIndex(c => String(c.id) === String(idCliente));
                    if (idx >= 0) {
                        CLIENTES_SALDO[idx].saldo_actual = data.saldo_nuevo;
                        if (data.saldo_nuevo <= 0) {
                            CLIENTES_SALDO.splice(idx, 1);
                        }
                    }
                    // Toast
                    const toast = document.createElement('div');
                    toast.style.cssText = 'position:fixed;bottom:20px;left:50%;transform:translateX(-50%);background:#1e7a3f;color:#fff;padding:12px 22px;border-radius:8px;font-weight:600;font-size:14px;box-shadow:0 10px 30px rgba(0,0,0,0.2);z-index:9999;';
                    toast.textContent = 'Abono de ' + formatC(monto) + ' registrado. Nuevo saldo: ' + formatC(data.saldo_nuevo);
                    document.body.appendChild(toast);
                    setTimeout(() => toast.remove(), 3500);
                } else {
                    abonarError.textContent = data.error || 'Error al guardar.';
                    abonarGuardar.disabled = false;
                    abonarGuardar.textContent = 'Registrar abono';
                }
            })
            .catch(() => {
                abonarError.textContent = 'Error de conexión.';
                abonarGuardar.disabled = false;
                abonarGuardar.textContent = 'Registrar abono';
            });
        });
    }
    
    }

    /* ============================================
       MODAL DE COBRO
       ============================================ */
    btnCobrar.addEventListener('click', function () {
        pagoCordobas.value = '';
        pagoDolares.value = '';
        chkCobrarUSD.checked = false;
        grupoPagoCordobas.style.display = 'flex';
        grupoPagoDolares.style.display = 'none';
        referenciaTarjeta.value = '';
        referenciaTransferencia.value = '';
        errorCobro.textContent = '';
        imprimirTicket.checked = true;
        ventaObservaciones.value = '';

        setMetodo('efectivo');

        const t = calcularTotales();
        cobroTotal.textContent = formatC(t.total);
        tcMostrado.textContent = TIPO_CAMBIO.toFixed(2);

        calcularVuelto();
        modalCobro.classList.add('visible');
        setTimeout(() => pagoCordobas.focus(), 100);
    });

    function cerrarCobro() {
        modalCobro.classList.remove('visible');
    }

    btnCerrarCobro.addEventListener('click', cerrarCobro);
    btnCancelarCobro.addEventListener('click', cerrarCobro);

    function setMetodo(metodo) {
        metodoPago = metodo;

        cobroMetodos.forEach(btn => {
            btn.classList.toggle('activo', btn.dataset.metodo === metodo);
        });

        seccionEfectivo.style.display = metodo === 'efectivo' ? 'block' : 'none';
        seccionTarjeta.style.display = metodo === 'tarjeta' ? 'block' : 'none';
        seccionTransferencia.style.display = metodo === 'transferencia' ? 'block' : 'none';
        seccionCredito.style.display = metodo === 'credito' ? 'block' : 'none';

        if (metodo === 'credito') {
            validarCredito();
        }

        calcularVuelto();
    }

    cobroMetodos.forEach(btn => {
        btn.addEventListener('click', function () {
            setMetodo(this.dataset.metodo);
        });
    });

    function calcularVuelto() {
        const t = calcularTotales();

        if (metodoPago !== 'efectivo') {
            cobroVuelto.style.display = 'none';
            return;
        }

        cobroVuelto.style.display = 'flex';

        let recibido = 0;
        if (chkCobrarUSD.checked) {
            recibido = (parseFloat(pagoDolares.value) || 0) * TIPO_CAMBIO;
        } else {
            recibido = parseFloat(pagoCordobas.value) || 0;
        }

        const vuelto = recibido - t.total;

        cobroVuelto.classList.remove('faltante', 'exacto');

        if (recibido === 0) {
            vueltoValor.textContent = 'C$ 0.00';
            vueltoDetalle.innerHTML = '';
            cobroVuelto.classList.add('exacto');
        } else if (Math.abs(vuelto) < 0.01) {
            cobroVuelto.classList.add('exacto');
            vueltoValor.textContent = 'C$ 0.00';
            vueltoDetalle.innerHTML = 'Pago exacto';
        } else if (vuelto > 0) {
            vueltoValor.textContent = formatC(vuelto);
            if (chkCobrarUSD.checked) {
                const dolares = parseFloat(pagoDolares.value) || 0;
                vueltoDetalle.innerHTML = `${dolares.toFixed(2)} USD × ${TIPO_CAMBIO.toFixed(2)} = ${formatC(dolares * TIPO_CAMBIO)}`;
            } else {
                vueltoDetalle.innerHTML = '';
            }
        } else {
            cobroVuelto.classList.add('faltante');
            vueltoValor.textContent = '−' + formatC(Math.abs(vuelto));
            vueltoDetalle.innerHTML = 'Falta efectivo';
        }
    }

    pagoCordobas.addEventListener('input', calcularVuelto);
    pagoDolares.addEventListener('input', calcularVuelto);

    chkCobrarUSD.addEventListener('change', function () {
        if (this.checked) {
            grupoPagoCordobas.style.display = 'none';
            pagoCordobas.value = '';
            grupoPagoDolares.style.display = 'flex';
            setTimeout(() => pagoDolares.focus(), 100);
        } else {
            grupoPagoCordobas.style.display = 'flex';
            grupoPagoDolares.style.display = 'none';
            pagoDolares.value = '';
        }
        calcularVuelto();
    });

    function validarCredito() {
        const idCliente = posClienteModal.value;
        if (!idCliente) {
            creditoClienteInfo.className = 'cobro-info warning';
            creditoClienteInfo.innerHTML = `
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                    <line x1="12" y1="9" x2="12" y2="13"></line>
                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg>
                <span>Debes seleccionar un cliente para vender al crédito.</span>
            `;
            return false;
        }

        const nombreCliente = posClienteModal.options[posClienteModal.selectedIndex].text;
        creditoClienteInfo.className = 'cobro-info success';
        creditoClienteInfo.innerHTML = `
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
            </svg>
            <span><strong>${esc(nombreCliente)}</strong> — se cargará el total a su cuenta.</span>
        `;
        return true;
    }

    posClienteModal.addEventListener('change', function () {
        if (metodoPago === 'credito') validarCredito();
    });

        /* ============================================
       MODAL DEVOLUCIÓN
       ============================================ */
    const btnDevolucion = document.getElementById('btnDevolucion');
    const modalDevolucion = document.getElementById('modalDevolucion');
    const devCerrarX = document.getElementById('devCerrarX');
    const devCancelar = document.getElementById('devCancelar');
    const devGuardar = document.getElementById('devGuardar');
    const devBusquedaVenta = document.getElementById('devBusquedaVenta');
    const devBuscarBtn = document.getElementById('devBuscarBtn');
    const devError = document.getElementById('devError');
    const devResultadosBusqueda = document.getElementById('devResultadosBusqueda');
    const devPasoBuscar = document.getElementById('devPasoBuscar');
    const devPasoSeleccionar = document.getElementById('devPasoSeleccionar');
    const devVentaInfo = document.getElementById('devVentaInfo');
    const devItemsLista = document.getElementById('devItemsLista');
    const devTotalValor = document.getElementById('devTotalValor');
    const devMetodo = document.getElementById('devMetodo');
    const devMetodoHelp = document.getElementById('devMetodoHelp');
    const devGrupoReferencia = document.getElementById('devGrupoReferencia');
    const devReferencia = document.getElementById('devReferencia');
    const devMotivo = document.getElementById('devMotivo');
    const devVolverBuscar = document.getElementById('devVolverBuscar');

    let devVentaActual = null;
    let devItemsActuales = [];

    function abrirDevolucion() {
        devBusquedaVenta.value = '';
        devError.textContent = '';
        devResultadosBusqueda.innerHTML = '';
        devPasoBuscar.style.display = 'block';
        devPasoSeleccionar.style.display = 'none';
        devGuardar.style.display = 'none';
        devVentaActual = null;
        devItemsActuales = [];

        modalDevolucion.classList.add('visible');
        setTimeout(() => devBusquedaVenta.focus(), 100);
    }

    function cerrarDevolucion() {
        modalDevolucion.classList.remove('visible');
    }

    if (btnDevolucion) btnDevolucion.addEventListener('click', abrirDevolucion);
    if (devCancelar) devCancelar.addEventListener('click', cerrarDevolucion);
    if (devCerrarX) devCerrarX.addEventListener('click', cerrarDevolucion);

    // Buscar venta
    function buscarVenta() {
        const q = devBusquedaVenta.value.trim();
        if (q === '') return;

        devError.textContent = '';
        devResultadosBusqueda.innerHTML = '<p style="text-align:center; color:#888; padding:20px;">Buscando...</p>';

        fetch('/AvilesPOSv2/modulos/ventas/api/buscar_venta.php?q=' + encodeURIComponent(q))
            .then(r => r.json())
            .then(data => {
                if (!data.ok) {
                    devError.textContent = data.error;
                    devResultadosBusqueda.innerHTML = '';
                    return;
                }

                if (data.ventas.length === 0) {
                    devResultadosBusqueda.innerHTML = '<div class="dev-sin-resultados">No se encontraron ventas completadas</div>';
                    return;
                }

                devResultadosBusqueda.innerHTML = data.ventas.map(v => {
                    const fecha = new Date(v.fecha.replace(' ', 'T'));
                    const fechaStr = String(fecha.getDate()).padStart(2,'0') + '/' +
                                     String(fecha.getMonth()+1).padStart(2,'0') + '/' +
                                     fecha.getFullYear() + ' ' +
                                     String(fecha.getHours()).padStart(2,'0') + ':' +
                                     String(fecha.getMinutes()).padStart(2,'0');

                    return `
                        <button type="button" class="dev-resultado-venta" data-id-venta="${v.id}">
                            <div class="info">
                                <div class="titulo">Factura #${parseInt(v.numero_factura)}</div>
                                <div class="detalle">${fechaStr} · ${esc(v.cliente_nombre || 'Consumidor final')} · Cajero: ${esc(v.cajero_nombre)}</div>
                            </div>
                            <div class="total">${formatC(v.total)}</div>
                        </button>
                    `;
                }).join('');
            })
            .catch(() => {
                devError.textContent = 'Error de conexión';
                devResultadosBusqueda.innerHTML = '';
            });
    }

    if (devBuscarBtn) devBuscarBtn.addEventListener('click', buscarVenta);
    if (devBusquedaVenta) {
        devBusquedaVenta.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                buscarVenta();
            }
        });
    }

    // Clic en un resultado
    if (devResultadosBusqueda) {
        devResultadosBusqueda.addEventListener('click', function (e) {
            const btn = e.target.closest('[data-id-venta]');
            if (!btn) return;

            const idVenta = btn.dataset.idVenta;
            devError.textContent = '';

            fetch('/AvilesPOSv2/modulos/ventas/api/items_venta.php?id=' + idVenta)
                .then(r => r.json())
                .then(data => {
                    if (!data.ok) {
                        devError.textContent = data.error;
                        return;
                    }
                    devVentaActual = data.venta;
                    devItemsActuales = data.items.map(it => ({
                        ...it,
                        cantDevolver: 0
                    }));
                    renderPasoSeleccionar();
                })
                .catch(() => {
                    devError.textContent = 'Error de conexión';
                });
        });
    }

    function renderPasoSeleccionar() {
        const v = devVentaActual;

        // Info venta
        const fecha = new Date(v.fecha.replace(' ', 'T'));
        const fechaStr = String(fecha.getDate()).padStart(2,'0') + '/' +
                         String(fecha.getMonth()+1).padStart(2,'0') + '/' +
                         fecha.getFullYear();

        devVentaInfo.innerHTML = `
            <div class="dev-venta-info-item">
                <div class="dev-venta-info-label">Factura</div>
                <div class="dev-venta-info-valor">#${parseInt(v.numero_factura)}</div>
            </div>
            <div class="dev-venta-info-item">
                <div class="dev-venta-info-label">Fecha</div>
                <div class="dev-venta-info-valor">${fechaStr}</div>
            </div>
            <div class="dev-venta-info-item">
                <div class="dev-venta-info-label">Cliente</div>
                <div class="dev-venta-info-valor">${esc(v.cliente_nombre || 'Consumidor final')}</div>
            </div>
            <div class="dev-venta-info-item">
                <div class="dev-venta-info-label">Total venta</div>
                <div class="dev-venta-info-valor">${formatC(v.total)}</div>
            </div>
        `;

        // Items
        devItemsLista.innerHTML = devItemsActuales.map((it, idx) => {
            const disponible = it.disponible;
            const sinDisponible = disponible <= 0;

            return `
                <div class="dev-item ${sinDisponible ? 'cancelado' : ''}" data-idx="${idx}">
                    <div class="dev-item-info">
                        <div class="dev-item-nombre">${esc(it.producto_nombre)}</div>
                        <div class="dev-item-codigo">${esc(it.producto_codigo)}</div>
                    </div>
                    <div class="dev-item-vendido">
                        Vendido: <strong>${it.cantidad}</strong>
                        ${it.ya_devuelto > 0 ? `<br><span style="font-size:10px; color:#a05a00;">Devuelto: ${it.ya_devuelto}</span>` : ''}
                    </div>
                    <div class="dev-item-precio">${formatC(it.precio_unitario)} c/u</div>
                    <div class="dev-item-cantidad">
                        <label>Devolver:</label>
                        <input type="number" class="dev-cant-input" data-idx="${idx}" 
                               value="0" min="0" max="${disponible}" 
                               ${sinDisponible ? 'disabled' : ''}>
                    </div>
                </div>
            `;
        }).join('');

        // Método: si la venta fue al crédito, preseleccionar ajuste_credito
        if (v.metodo_pago === 'credito' && v.cliente_nombre) {
            devMetodo.value = 'ajuste_credito';
        } else if (v.metodo_pago === 'efectivo') {
            devMetodo.value = 'efectivo';
        } else if (v.metodo_pago === 'tarjeta') {
            devMetodo.value = 'tarjeta';
        } else if (v.metodo_pago === 'transferencia') {
            devMetodo.value = 'transferencia';
        }

        devReferencia.value = '';
        devMotivo.value = '';
        devMetodoHelp.textContent = '';
        actualizarMetodoDev();
        actualizarTotalDev();

        // Mostrar paso 2
        devPasoBuscar.style.display = 'none';
        devPasoSeleccionar.style.display = 'block';
        devGuardar.style.display = 'inline-flex';
    }

    function actualizarMetodoDev() {
        const m = devMetodo.value;
        if (m === 'efectivo') {
            devGrupoReferencia.style.display = 'none';
            devMetodoHelp.textContent = 'Se registrará como salida de caja';
        } else if (m === 'ajuste_credito') {
            devGrupoReferencia.style.display = 'none';
            devMetodoHelp.textContent = 'Se descontará del saldo del cliente';
        } else {
            devGrupoReferencia.style.display = 'block';
            devMetodoHelp.textContent = m === 'tarjeta' ? 'Devolución a tarjeta' : 'Devolución por transferencia';
        }
    }

    if (devMetodo) devMetodo.addEventListener('change', actualizarMetodoDev);

    function actualizarTotalDev() {
        let total = 0;
        devItemsActuales.forEach(it => {
            total += (it.cantDevolver || 0) * parseFloat(it.precio_unitario);
        });
        devTotalValor.textContent = formatC(total);
    }

    // Cambio de cantidad
    if (devItemsLista) {
        devItemsLista.addEventListener('input', function (e) {
            const input = e.target.closest('.dev-cant-input');
            if (!input) return;

            const idx = parseInt(input.dataset.idx);
            const it = devItemsActuales[idx];
            if (!it) return;

            let val = parseInt(input.value) || 0;
            if (val < 0) val = 0;
            if (val > it.disponible) {
                val = it.disponible;
                input.value = val;
            }

            it.cantDevolver = val;
            actualizarTotalDev();
        });
    }

    // Volver a buscar
    if (devVolverBuscar) {
        devVolverBuscar.addEventListener('click', function () {
            devPasoBuscar.style.display = 'block';
            devPasoSeleccionar.style.display = 'none';
            devGuardar.style.display = 'none';
            devVentaActual = null;
            devItemsActuales = [];
        });
    }

    // Guardar devolución
    if (devGuardar) {
        devGuardar.addEventListener('click', function () {
            devError.textContent = '';

            const items = devItemsActuales
                .filter(it => it.cantDevolver > 0)
                .map(it => ({
                    id_producto: parseInt(it.id_producto),
                    cantidad: it.cantDevolver
                }));

            if (items.length === 0) {
                devError.textContent = 'Debes indicar al menos un producto a devolver.';
                return;
            }

            const motivo = devMotivo.value.trim();
            if (motivo === '') {
                devError.textContent = 'El motivo es obligatorio.';
                return;
            }

            devGuardar.disabled = true;
            devGuardar.textContent = 'Procesando...';

            fetch('/AvilesPOSv2/modulos/ventas/api/guardar_devolucion.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id_venta: devVentaActual.id,
                    items: items,
                    metodo_pago: devMetodo.value,
                    referencia: devReferencia.value.trim(),
                    motivo: motivo
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.ok) {
                    cerrarDevolucion();
                    const toast = document.createElement('div');
                    toast.style.cssText = 'position:fixed;bottom:20px;left:50%;transform:translateX(-50%);background:#1e7a3f;color:#fff;padding:12px 22px;border-radius:8px;font-weight:600;font-size:14px;box-shadow:0 10px 30px rgba(0,0,0,0.2);z-index:9999;';
                    toast.textContent = '✓ Devolución de ' + formatC(data.monto_total) + ' registrada';
                    document.body.appendChild(toast);
                    setTimeout(() => toast.remove(), 3500);
                } else {
                    devError.textContent = data.error || 'Error al procesar.';
                    devGuardar.disabled = false;
                    devGuardar.textContent = 'Procesar devolución';
                }
            })
            .catch(() => {
                devError.textContent = 'Error de conexión.';
                devGuardar.disabled = false;
                devGuardar.textContent = 'Procesar devolución';
            });
        });
    }

    /* ============================================
       IMPRESIÓN DIRECTA DE TICKET
       ============================================ */
    function imprimirTicketDirecto(idVenta) {
        const iframe = document.createElement('iframe');
        iframe.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;opacity:0;pointer-events:none;';
        iframe.src = '/AvilesPOSv2/modulos/ventas/ticket.php?id=' + idVenta + '&print=1';
        document.body.appendChild(iframe);

        setTimeout(function () {
            if (iframe.parentNode) iframe.parentNode.removeChild(iframe);
        }, 30000);
    }

    /* ============================================
       CONFIRMAR COBRO
       ============================================ */
    btnConfirmarCobro.addEventListener('click', function () {
        errorCobro.textContent = '';

        const t = calcularTotales();

        if (metodoPago === 'efectivo') {
            let recibido = 0;
            if (chkCobrarUSD.checked) {
                recibido = (parseFloat(pagoDolares.value) || 0) * TIPO_CAMBIO;
            } else {
                recibido = parseFloat(pagoCordobas.value) || 0;
            }
            if (recibido < t.total - 0.01) {
                errorCobro.textContent = 'El monto recibido es menor al total.';
                return;
            }
        } else if (metodoPago === 'credito') {
            if (!validarCredito()) {
                errorCobro.textContent = 'Debes seleccionar un cliente para vender al crédito.';
                return;
            }
        }

        const items = Object.values(carrito).map(item => ({
            id_producto: parseInt(item.producto.id),
            cantidad: item.cantidad
        }));

        let monto_cordobas = 0;
        let monto_usd = 0;
        let tipo_cambio_enviado = 0;

        if (metodoPago === 'efectivo') {
            if (chkCobrarUSD.checked) {
                monto_usd = parseFloat(pagoDolares.value) || 0;
                tipo_cambio_enviado = TIPO_CAMBIO;
            } else {
                monto_cordobas = parseFloat(pagoCordobas.value) || 0;
            }
        }

        let referencia = '';
        if (metodoPago === 'tarjeta') referencia = referenciaTarjeta.value.trim();
        if (metodoPago === 'transferencia') referencia = referenciaTransferencia.value.trim();

        const payload = {
            items: items,
            id_cliente: posClienteModal.value || null,
            metodo_pago: metodoPago,
            referencia: referencia,
            monto_cordobas: monto_cordobas,
            monto_usd: monto_usd,
            tipo_cambio: tipo_cambio_enviado,
            descuento_monto: t.descuentoMonto,
            descuento_porcentaje: descuentoTipo === 'porcentaje' ? (parseFloat(descuentoInput.value) || 0) : null,
            imprimir: imprimirTicket.checked,
            observaciones: ventaObservaciones.value.trim()
        };

        btnConfirmarCobro.disabled = true;
        btnConfirmarCobro.textContent = 'Procesando...';

        fetch('/AvilesPOSv2/modulos/ventas/guardar_venta.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                if (data.imprimir) {
                    imprimirTicketDirecto(data.id_venta);
                }
                setTimeout(function () {
                    window.location.href = '/AvilesPOSv2/modulos/ventas/index.php?venta_ok=' + data.numero_factura;
                }, 300);
            } else {
                errorCobro.textContent = data.error || 'Error al procesar la venta.';
                btnConfirmarCobro.disabled = false;
                btnConfirmarCobro.textContent = 'Confirmar cobro';
            }
        })
        .catch(err => {
            errorCobro.textContent = 'Error de conexión: ' + err.message;
            btnConfirmarCobro.disabled = false;
            btnConfirmarCobro.textContent = 'Confirmar cobro';
        });
    });

    /* ============================================
       ESC GLOBAL
       ============================================ */
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            if (modalConfirmar && modalConfirmar.classList.contains('visible')) {
                cerrarConfirmacion();
            } else if (modalDevolucion && modalDevolucion.classList.contains('visible')) {
                cerrarDevolucion();
            } else if (modalAbonar && modalAbonar.classList.contains('visible')) {
                cerrarAbonar();
            } else if (modalMovCaja && modalMovCaja.classList.contains('visible')) {
                cerrarMovCaja();
            } else if (modalCobro && modalCobro.classList.contains('visible')) {
                cerrarCobro();
            }
        }
    });

    /* ============================================
       INICIALIZAR
       ============================================ */
    renderCarrito();

    // Si hay un mensaje de éxito (venta recién hecha), mostrarlo
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('venta_ok')) {
        // Toast simple
        const toast = document.createElement('div');
        toast.style.cssText = 'position:fixed;bottom:20px;left:50%;transform:translateX(-50%);background:#1e7a3f;color:#fff;padding:14px 24px;border-radius:8px;font-weight:600;font-size:14px;box-shadow:0 10px 30px rgba(0,0,0,0.2);z-index:9999;';
        toast.textContent = '✓ Venta #' + urlParams.get('venta_ok') + ' registrada correctamente';
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);

        // Limpiar URL
        window.history.replaceState({}, '', '/AvilesPOSv2/modulos/ventas/index.php');
    }

});