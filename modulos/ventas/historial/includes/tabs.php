<?php
$hist_tab_activo = $hist_tab ?? '';
?>

<div class="hist-tabs">
    <a href="/AvilesPOSv2/modulos/ventas/historial/index.php"
       class="hist-tab <?= $hist_tab_activo === 'ventas' ? 'activo' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14">
            <circle cx="9" cy="21" r="1"></circle>
            <circle cx="20" cy="21" r="1"></circle>
            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
        </svg>
        Ventas
    </a>

    <a href="/AvilesPOSv2/modulos/ventas/historial/movimientos.php"
       class="hist-tab <?= $hist_tab_activo === 'movimientos' ? 'activo' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14">
            <polyline points="23 4 23 10 17 10"></polyline>
            <polyline points="1 20 1 14 7 14"></polyline>
            <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
        </svg>
        Movimientos de caja
    </a>

    <a href="/AvilesPOSv2/modulos/ventas/historial/abonos.php"
       class="hist-tab <?= $hist_tab_activo === 'abonos' ? 'activo' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14">
            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
            <line x1="1" y1="10" x2="23" y2="10"></line>
        </svg>
        Abonos
    </a>

    <a href="/AvilesPOSv2/modulos/ventas/historial/devoluciones.php"
       class="hist-tab <?= $hist_tab_activo === 'devoluciones' ? 'activo' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14">
            <polyline points="9 14 4 9 9 4"></polyline>
            <path d="M20 20v-7a4 4 0 0 0-4-4H4"></path>
        </svg>
        Devoluciones
    </a>
</div>