document.addEventListener('DOMContentLoaded', function () {

    const modal = document.getElementById('modalCerrarTurno');
    if (!modal) return;

    const btnAbrir = document.getElementById('btnAbrirCierre');
    const btnCerrarX = document.getElementById('cierreCerrarX');
    const btnCancelar = document.getElementById('cierreCancelar');
    const btnConfirmar = document.getElementById('cierreConfirmar');
    const inputContado = document.getElementById('cierreMontoContado');
    const diferencia = document.getElementById('cierreDiferencia');
    const difValor = document.getElementById('cierreDifValor');
    const observaciones = document.getElementById('cierreObservaciones');
    const errorBox = document.getElementById('cierreError');

    const DATOS = window.CIERRE_DATOS || {};
    const esperado = parseFloat(inputContado.dataset.esperado) || 0;

    function abrirModal() {
        inputContado.value = '';
        observaciones.value = '';
        errorBox.textContent = '';
        btnConfirmar.disabled = false;
        btnConfirmar.innerHTML = `
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
            </svg>
            Confirmar cierre
        `;

        actualizarDiferencia();

        modal.classList.add('visible');
        setTimeout(() => inputContado.focus(), 100);
    }

    function cerrarModal() {
        modal.classList.remove('visible');
    }

    function actualizarDiferencia() {
        const valor = inputContado.value.trim();
        diferencia.classList.remove('exacto', 'sobrante', 'faltante');

        if (valor === '') {
            difValor.textContent = 'Ingresa el monto contado';
            return;
        }

        const contado = parseFloat(valor) || 0;
        const dif = contado - esperado;

        if (Math.abs(dif) < 0.01) {
            diferencia.classList.add('exacto');
            difValor.textContent = 'Cuadre exacto';
        } else if (dif > 0) {
            diferencia.classList.add('sobrante');
            difValor.textContent = '+ C$ ' + dif.toFixed(2) + ' (sobrante)';
        } else {
            diferencia.classList.add('faltante');
            difValor.textContent = '− C$ ' + Math.abs(dif).toFixed(2) + ' (faltante)';
        }
    }

    // Abrir
    if (btnAbrir) btnAbrir.addEventListener('click', abrirModal);

    // Cerrar
    if (btnCerrarX) btnCerrarX.addEventListener('click', cerrarModal);
    if (btnCancelar) btnCancelar.addEventListener('click', cerrarModal);

    // ESC
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.classList.contains('visible')) {
            cerrarModal();
        }
    });

    // Cálculo en tiempo real
    if (inputContado) inputContado.addEventListener('input', actualizarDiferencia);

    // Confirmar
    if (btnConfirmar) {
        btnConfirmar.addEventListener('click', function () {
            errorBox.textContent = '';

            const valor = inputContado.value.trim();
            if (valor === '') {
                errorBox.textContent = 'Debes ingresar el monto contado.';
                return;
            }
            const contado = parseFloat(valor) || 0;
            if (contado < 0) {
                errorBox.textContent = 'El monto contado no puede ser negativo.';
                return;
            }

            btnConfirmar.disabled = true;
            btnConfirmar.textContent = 'Cerrando...';

            fetch('/AvilesPOSv2/modulos/ventas/turnos/guardar_cierre.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    id: DATOS.id_turno,
                    monto_final_contado: contado,
                    observaciones: observaciones.value.trim()
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.ok) {
                    // Éxito: recargar la página con mensaje
                    window.location.href = '/AvilesPOSv2/modulos/ventas/turnos/index.php?cerrado=1';
                } else {
                    errorBox.textContent = data.error || 'Error al cerrar el turno.';
                    btnConfirmar.disabled = false;
                    btnConfirmar.textContent = 'Confirmar cierre';
                }
            })
            .catch(() => {
                errorBox.textContent = 'Error de conexión.';
                btnConfirmar.disabled = false;
                btnConfirmar.textContent = 'Confirmar cierre';
            });
        });
    }

});