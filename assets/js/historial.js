document.addEventListener('DOMContentLoaded', function () {

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

    function formatFecha(f) {
        if (!f) return '—';
        const d = new Date(f.replace(' ', 'T'));
        const dia = String(d.getDate()).padStart(2, '0');
        const mes = String(d.getMonth() + 1).padStart(2, '0');
        const anio = d.getFullYear();
        const hora = String(d.getHours()).padStart(2, '0');
        const min = String(d.getMinutes()).padStart(2, '0');
        return `${dia}/${mes}/${anio} ${hora}:${min}`;
    }

    // Modal
    function abrirModal(id) {
        const m = document.getElementById(id);
        if (m) m.classList.add('visible');
    }

    function cerrarModal(m) {
        m.classList.remove('visible');
    }

    // Cerrar con X o clic en [data-close]
    document.querySelectorAll('.modal-overlay').forEach(modal => {
        modal.querySelectorAll('[data-close]').forEach(btn => {
            btn.addEventListener('click', () => cerrarModal(modal));
        });
    });

    // ESC
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay.visible').forEach(m => cerrarModal(m));
        }
    });

    /* ============================================
       IMPRIMIR TICKET (con iframe oculto)
       ============================================ */
    function imprimirTicket(idVenta) {
        const iframe = document.createElement('iframe');
        iframe.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;opacity:0;pointer-events:none;';
        iframe.src = '/AvilesPOSv2/modulos/ventas/ticket.php?id=' + idVenta + '&print=1';
        document.body.appendChild(iframe);
        setTimeout(() => {
            if (iframe.parentNode) iframe.parentNode.removeChild(iframe);
        }, 30000);
    }

    /* ============================================
       MODAL: VER DETALLE
       ============================================ */
    const modalDetalle = document.getElementById('modalDetalleVenta');
    const detalleTitle = document.getElementById('detalleVentaTitle');
    const detalleBody = document.getElementById('detalleVentaBody');
    const detallePrint = document.getElementById('detalleVentaPrint');
    let idVentaDetalleActual = null;

    function renderDetalleVenta(data) {
        const v = data.venta;
        const items = data.items;

        // Título
        detalleTitle.textContent = 'Venta #' + parseInt(v.numero_factura);

        // Botón reimprimir
        idVentaDetalleActual = v.id;
        detallePrint.style.display = v.estado === 'anulada' ? 'none' : '';

        const metodoLabel = {
            efectivo: 'Efectivo',
            tarjeta: 'Tarjeta',
            transferencia: 'Transferencia',
            credito: 'Crédito'
        }[v.metodo_pago] || v.metodo_pago;

        // Construir HTML
        let html = '<div class="detalle-venta-layout">';

        // Header con datos
        html += `
            <div class="detalle-venta-header">
                <div class="detalle-venta-header-item">
                    <div class="detalle-venta-header-label">Fecha</div>
                    <div class="detalle-venta-header-valor">${formatFecha(v.fecha)}</div>
                </div>
                <div class="detalle-venta-header-item">
                    <div class="detalle-venta-header-label">Cajero</div>
                    <div class="detalle-venta-header-valor">${esc(v.cajero_nombre)}</div>
                </div>
                <div class="detalle-venta-header-item">
                    <div class="detalle-venta-header-label">Caja</div>
                    <div class="detalle-venta-header-valor">${esc(v.caja_nombre)}</div>
                </div>
                <div class="detalle-venta-header-item">
                    <div class="detalle-venta-header-label">Estado</div>
                    <div class="detalle-venta-header-valor">
                        ${v.estado === 'anulada' 
                            ? '<span class="badge badge-anulada">Anulada</span>' 
                            : '<span class="badge badge-completada">Completada</span>'}
                    </div>
                </div>
                <div class="detalle-venta-header-item">
                    <div class="detalle-venta-header-label">Cliente</div>
                    <div class="detalle-venta-header-valor">${esc(v.cliente_nombre || 'Consumidor final')}</div>
                </div>
                <div class="detalle-venta-header-item">
                    <div class="detalle-venta-header-label">Método</div>
                    <div class="detalle-venta-header-valor">
                        <span class="badge-metodo ${esc(v.metodo_pago)}">${metodoLabel}</span>
                    </div>
                </div>
                ${v.referencia_pago ? `
                <div class="detalle-venta-header-item">
                    <div class="detalle-venta-header-label">Referencia</div>
                    <div class="detalle-venta-header-valor">${esc(v.referencia_pago)}</div>
                </div>
                ` : ''}
                ${v.observaciones ? `
                <div class="detalle-venta-header-item" style="grid-column: 1 / -1;">
                    <div class="detalle-venta-header-label">Observaciones</div>
                    <div class="detalle-venta-header-valor">${esc(v.observaciones)}</div>
                </div>
                ` : ''}
            </div>
        `;

        // Aviso de anulación
        if (v.estado === 'anulada') {
            html += `
                <div class="cobro-info warning">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                        <line x1="12" y1="9" x2="12" y2="13"></line>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                    <span>
                        <strong>Anulada</strong> el ${formatFecha(v.fecha_anulacion)} por ${esc(v.anulada_por || 'N/D')}.<br>
                        Motivo: ${esc(v.motivo_anulacion || 'Sin motivo')}
                    </span>
                </div>
            `;
        }

        // Items
        html += `
            <div class="detalle-venta-body">
                <div class="detalle-venta-items">
                    <table class="data-table">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Producto</th>
                            <th style="text-align:center;">Cant.</th>
                            <th style="text-align:right;">P. Unit.</th>
                            <th style="text-align:right;">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

        items.forEach(it => {
            const subConIva = parseFloat(it.subtotal) + parseFloat(it.iva);
            html += `
                <tr>
                    <td style="font-size:12px; color:#888;">${esc(it.producto_codigo)}</td>
                    <td>${esc(it.producto_nombre)}</td>
                    <td style="text-align:center;">${it.cantidad}</td>
                    <td style="text-align:right;">${formatC(it.precio_unitario)}</td>
                    <td style="text-align:right; font-weight:600; color:#0e5dac;">${formatC(subConIva)}</td>
                </tr>
            `;
        });

        html += `
                    </tbody>
                </table>
            </div>
        `;

        // Totales
        html += `
            <div class="detalle-venta-totales">
                <div class="linea">
                    <span>Subtotal</span>
                    <span>${formatC(v.subtotal)}</span>
                </div>
                <div class="linea">
                    <span>IVA incluido</span>
                    <span>${formatC(v.iva)}</span>
                </div>
        `;

        if (parseFloat(v.descuento) > 0) {
            html += `
                <div class="linea" style="color:#b00020;">
                    <span>Descuento</span>
                    <span>− ${formatC(v.descuento)}</span>
                </div>
            `;
        }

        html += `
                <div class="linea total">
                    <span>Total</span>
                    <span>${formatC(v.total)}</span>
                </div>
        `;

        // Detalle de pago en efectivo
        if (v.metodo_pago === 'efectivo') {
            if (parseFloat(v.monto_recibido_cordobas) > 0) {
                html += `
                    <div class="linea" style="font-size:12px; margin-top:6px;">
                        <span>Recibido (C$)</span>
                        <span>${formatC(v.monto_recibido_cordobas)}</span>
                    </div>
                `;
            }
            if (parseFloat(v.monto_recibido_usd) > 0) {
                html += `
                    <div class="linea" style="font-size:12px;">
                        <span>Recibido (USD)</span>
                        <span>$ ${Number(v.monto_recibido_usd).toFixed(2)} × ${Number(v.tipo_cambio).toFixed(2)}</span>
                    </div>
                `;
            }
            html += `
                <div class="linea" style="font-size:12px; color:#1e7a3f; font-weight:600;">
                    <span>Cambio</span>
                    <span>${formatC(v.vuelto)}</span>
                </div>
            `;
        }

        html += `
            </div>
            </div>
        `;

        html += '</div>';

        detalleBody.innerHTML = html;
    }

    // Clic en botón "Ver detalle"
    document.querySelectorAll('[data-ver-venta]').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.dataset.verVenta;
            detalleBody.innerHTML = '<p style="text-align:center; color:#888; padding:40px;">Cargando...</p>';
            abrirModal('modalDetalleVenta');

            fetch('/AvilesPOSv2/modulos/ventas/api/detalle_venta.php?id=' + id)
                .then(r => r.json())
                .then(data => {
                    if (data.ok) {
                        renderDetalleVenta(data);
                    } else {
                        detalleBody.innerHTML = '<p style="text-align:center; color:#b00020; padding:40px;">' + esc(data.error) + '</p>';
                    }
                })
                .catch(() => {
                    detalleBody.innerHTML = '<p style="text-align:center; color:#b00020; padding:40px;">Error de conexión</p>';
                });
        });
    });

    // Botón reimprimir dentro del modal
    if (detallePrint) {
        detallePrint.addEventListener('click', function () {
            if (idVentaDetalleActual) {
                imprimirTicket(idVentaDetalleActual);
            }
        });
    }

    // Clic en botón "Reimprimir" de la tabla
    document.querySelectorAll('[data-print-venta]').forEach(btn => {
        btn.addEventListener('click', function () {
            imprimirTicket(this.dataset.printVenta);
        });
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
                    window.location.reload();
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
       MODAL: ANULAR VENTA
       ============================================ */
    const modalAnular = document.getElementById('modalAnularVenta');
    const anularNumeroFactura = document.getElementById('anularNumeroFactura');
    const anularMotivo = document.getElementById('anularMotivo');
    const anularError = document.getElementById('anularError');
    const anularConfirmar = document.getElementById('anularConfirmar');
    let idVentaAnularActual = null;

    document.querySelectorAll('[data-anular-venta]').forEach(btn => {
        btn.addEventListener('click', function () {
            if (this.disabled) return;
            const id = this.dataset.anularVenta;
            const fila = this.closest('tr');
            const numFactura = fila.querySelector('td').textContent.trim();

            idVentaAnularActual = id;
            anularNumeroFactura.textContent = '#' + numFactura;
            anularMotivo.value = '';
            anularError.textContent = '';
            anularConfirmar.disabled = false;
            anularConfirmar.textContent = 'Sí, anular venta';

            abrirModal('modalAnularVenta');
            setTimeout(() => anularMotivo.focus(), 100);
        });
    });

    if (anularConfirmar) {
        anularConfirmar.addEventListener('click', function () {
            anularError.textContent = '';

            const motivo = anularMotivo.value.trim();
            if (motivo === '') {
                anularError.textContent = 'El motivo es obligatorio.';
                return;
            }

            anularConfirmar.disabled = true;
            anularConfirmar.textContent = 'Anulando...';

            fetch('/AvilesPOSv2/modulos/ventas/api/anular_venta.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_venta: idVentaAnularActual, motivo: motivo })
            })
            .then(r => r.json())
            .then(data => {
                if (data.ok) {
                    cerrarModal(modalAnular);
                    // Recargar la página para reflejar los cambios
                    window.location.reload();
                } else {
                    anularError.textContent = data.error || 'Error al anular.';
                    anularConfirmar.disabled = false;
                    anularConfirmar.textContent = 'Sí, anular venta';
                }
            })
            .catch(() => {
                anularError.textContent = 'Error de conexión.';
                anularConfirmar.disabled = false;
                anularConfirmar.textContent = 'Sí, anular venta';
            });

            
        });
    }

    /* ============================================
       ABRIR DEVOLUCIÓN DESDE LA TABLA DEL HISTORIAL
       ============================================ */
    document.querySelectorAll('[data-devolver-venta]').forEach(btn => {
        btn.addEventListener('click', function () {
            if (this.disabled) return;
            const idVenta = this.dataset.devolverVenta;
            abrirDevolucionDesdeHistorial(idVenta);
        });
    });

    function abrirDevolucionDesdeHistorial(idVenta) {
        // Resetear el modal
        devBusquedaVenta.value = '';
        devError.textContent = '';
        devResultadosBusqueda.innerHTML = '';
        devPasoBuscar.style.display = 'none';
        devPasoSeleccionar.style.display = 'block';
        devGuardar.style.display = 'inline-flex';
        devVentaActual = null;
        devItemsActuales = [];

        // Cargar directo la venta
        fetch('/AvilesPOSv2/modulos/ventas/api/items_venta.php?id=' + idVenta)
            .then(r => r.json())
            .then(data => {
                if (!data.ok) {
                    // Si falla, volver al paso de buscar
                    devPasoBuscar.style.display = 'block';
                    devPasoSeleccionar.style.display = 'none';
                    devGuardar.style.display = 'none';
                    devError.textContent = data.error;
                    modalDevolucion.classList.add('visible');
                    return;
                }
                devVentaActual = data.venta;
                devItemsActuales = data.items.map(it => ({
                    ...it,
                    cantDevolver: 0
                }));
                renderPasoSeleccionar();
                modalDevolucion.classList.add('visible');
            })
            .catch(() => {
                devPasoBuscar.style.display = 'block';
                devPasoSeleccionar.style.display = 'none';
                devGuardar.style.display = 'none';
                devError.textContent = 'Error de conexión';
                modalDevolucion.classList.add('visible');
            });
    }

});