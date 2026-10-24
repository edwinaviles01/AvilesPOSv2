document.addEventListener('DOMContentLoaded', function () {

    const inputCosto = document.getElementById('precio_costo');
    const inputPorcentaje = document.getElementById('porcentaje_ganancia');
    const inputPrecio = document.getElementById('precio');

    function calcularPrecioDesdeGanancia() {
        const costo = parseFloat(inputCosto.value) || 0;
        const porcentaje = parseFloat(inputPorcentaje.value) || 0;
        if (costo <= 0) return;
        const precio = costo * (1 + porcentaje / 100);
        inputPrecio.value = precio.toFixed(2);
    }

    function calcularGananciaDesdePrecio() {
        const costo = parseFloat(inputCosto.value) || 0;
        const precio = parseFloat(inputPrecio.value) || 0;
        if (costo <= 0 || precio <= 0) {
            inputPorcentaje.value = '';
            return;
        }
        const porcentaje = ((precio - costo) / costo) * 100;
        inputPorcentaje.value = porcentaje.toFixed(2);
    }

    if (inputCosto && inputPorcentaje && inputPrecio) {
        inputPorcentaje.addEventListener('input', calcularPrecioDesdeGanancia);
        inputPrecio.addEventListener('input', calcularGananciaDesdePrecio);
        inputCosto.addEventListener('input', function () {
            if (inputPrecio.value) {
                calcularGananciaDesdePrecio();
            } else if (inputPorcentaje.value) {
                calcularPrecioDesdeGanancia();
            }
        });
    }

    const selectDepto = document.getElementById('id_departamento');
    const selectCat = document.getElementById('id_categoria');
    const categorias = window.CATEGORIAS || [];
    const categoriaSeleccionada = window.CATEGORIA_SELECCIONADA || '';

    function cargarCategorias() {
        const idDepto = selectDepto.value;
        selectCat.innerHTML = '';

        if (!idDepto) {
            const opt = document.createElement('option');
            opt.value = '';
            opt.textContent = '— Seleccionar departamento primero —';
            selectCat.appendChild(opt);
            return;
        }

        const filtradas = categorias.filter(c => String(c.id_departamento) === String(idDepto));

        if (filtradas.length === 0) {
            const opt = document.createElement('option');
            opt.value = '';
            opt.textContent = '— Sin categorías en este departamento —';
            selectCat.appendChild(opt);
            return;
        }

        const optDefault = document.createElement('option');
        optDefault.value = '';
        optDefault.textContent = '— Seleccionar —';
        selectCat.appendChild(optDefault);

        filtradas.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c.id;
            opt.textContent = c.nombre;
            if (String(c.id) === String(categoriaSeleccionada)) {
                opt.selected = true;
            }
            selectCat.appendChild(opt);
        });
    }

    if (selectDepto) {
        selectDepto.addEventListener('change', cargarCategorias);
        if (selectDepto.value) cargarCategorias();
    }

    const inputFoto = document.getElementById('foto');
    const fotoPreview = document.getElementById('fotoPreview');
    const btnQuitarFoto = document.getElementById('btnQuitarFoto');

    if (inputFoto && fotoPreview) {
        inputFoto.addEventListener('change', function () {
            const file = this.files[0];
            if (!file) return;

            if (file.size > 2 * 1024 * 1024) {
                alert('La imagen no debe pesar más de 2MB');
                inputFoto.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = function (e) {
                fotoPreview.innerHTML = '<img src="' + e.target.result + '" alt="Vista previa">';
                fotoPreview.appendChild(inputFoto);
                btnQuitarFoto.classList.add('visible');
            };
            reader.readAsDataURL(file);
        });
    }

    if (btnQuitarFoto) {
        btnQuitarFoto.addEventListener('click', function () {
            inputFoto.value = '';
            fotoPreview.innerHTML = `
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" width="40" height="40">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                    <circle cx="8.5" cy="8.5" r="1.5"></circle>
                    <polyline points="21 15 16 10 5 21"></polyline>
                </svg>
                <span>Haz clic para subir</span>
            `;
            fotoPreview.appendChild(inputFoto);
            btnQuitarFoto.classList.remove('visible');
        });
    }

    const inputCodigo = document.getElementById('codigo');
    const barcodeSvg = document.getElementById('barcodeSvg');
    const barcodeTexto = document.getElementById('barcodeTexto');

    function dibujarBarcode(texto) {
        barcodeSvg.innerHTML = '';
        if (!texto) {
            barcodeTexto.textContent = '—';
            return;
        }

        barcodeTexto.textContent = texto;

        const totalBarras = texto.length * 8 + 10;
        const anchoBarra = 200 / totalBarras;
        let x = 0;

        for (let i = 0; i < 3; i++) {
            const rect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
            rect.setAttribute('x', x);
            rect.setAttribute('y', 0);
            rect.setAttribute('width', anchoBarra);
            rect.setAttribute('height', 60);
            rect.setAttribute('fill', '#000');
            barcodeSvg.appendChild(rect);
            x += anchoBarra;
        }

        for (let i = 0; i < texto.length; i++) {
            const code = texto.charCodeAt(i);
            for (let b = 0; b < 8; b++) {
                const bit = (code >> b) & 1;
                if (bit) {
                    const rect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
                    rect.setAttribute('x', x);
                    rect.setAttribute('y', 0);
                    rect.setAttribute('width', anchoBarra);
                    rect.setAttribute('height', 60);
                    rect.setAttribute('fill', '#000');
                    barcodeSvg.appendChild(rect);
                }
                x += anchoBarra;
            }
        }

        for (let i = 0; i < 3; i++) {
            const rect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
            rect.setAttribute('x', x);
            rect.setAttribute('y', 0);
            rect.setAttribute('width', anchoBarra);
            rect.setAttribute('height', 60);
            rect.setAttribute('fill', '#000');
            barcodeSvg.appendChild(rect);
            x += anchoBarra;
        }
    }

    if (inputCodigo) {
        inputCodigo.addEventListener('input', function () {
            dibujarBarcode(this.value.trim());
        });
        if (inputCodigo.value) {
            dibujarBarcode(inputCodigo.value.trim());
        }
    }

});