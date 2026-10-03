<?php
if (!function_exists('tiene_permiso')) {
    require_once __DIR__ . '/../../../includes/permisos.php';
}

$ventas_tab_activo = $ventas_tab ?? '';
$ver_historial = tiene_permiso('ventas_historial_propio') || tiene_permiso('ventas_historial_completo');
?>

<div class="tabs">

    <?php if (tiene_permiso('ventas_vender')): ?>
    <a href="/AvilesPOSv2/modulos/ventas/index.php"
       class="tab <?= $ventas_tab_activo === 'nueva' ? 'activo' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
            <circle cx="9" cy="21" r="1"></circle>
            <circle cx="20" cy="21" r="1"></circle>
            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
        </svg>
        Nueva venta
    </a>
    <?php endif; ?>

    <?php if (tiene_permiso('turnos_abrir')): ?>
    <a href="/AvilesPOSv2/modulos/ventas/turnos/index.php"
       class="tab <?= $ventas_tab_activo === 'turnos' ? 'activo' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
            <circle cx="12" cy="12" r="10"></circle>
            <polyline points="12 6 12 12 16 14"></polyline>
        </svg>
        Turnos
    </a>
    <?php endif; ?>

    <?php if ($ver_historial): ?>
    <a href="/AvilesPOSv2/modulos/ventas/historial/index.php"
       class="tab <?= $ventas_tab_activo === 'historial' ? 'activo' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
            <polyline points="14 2 14 8 20 8"></polyline>
        </svg>
        Historial
    </a>
    <?php endif; ?>

</div>