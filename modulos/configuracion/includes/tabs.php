<?php
if (!function_exists('tiene_permiso')) {
    require_once __DIR__ . '/../../../includes/permisos.php';
}

$tab_activo = basename($_SERVER['PHP_SELF'], '.php');
?>

<div class="tabs">

    <?php if (tiene_permiso('config_empresa')): ?>
    <a href="/AvilesPOSv2/modulos/configuracion/empresa.php"
       class="tab <?= $tab_activo === 'empresa' ? 'activo' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
            <path d="M3 21h18"></path>
            <path d="M5 21V7l8-4v18"></path>
            <path d="M19 21V11l-6-4"></path>
        </svg>
        Empresa
    </a>
    <?php endif; ?>

    <?php if (tiene_permiso('config_usuarios')): ?>
    <a href="/AvilesPOSv2/modulos/configuracion/usuarios.php"
       class="tab <?= $tab_activo === 'usuarios' ? 'activo' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
            <circle cx="9" cy="7" r="4"></circle>
            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
        </svg>
        Usuarios
    </a>
    <?php endif; ?>

    <?php if (tiene_permiso('config_cajas')): ?>
    <a href="/AvilesPOSv2/modulos/configuracion/cajas.php"
       class="tab <?= $tab_activo === 'cajas' ? 'activo' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
            <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
        </svg>
        Cajas
    </a>
    <?php endif; ?>

    <?php if (tiene_permiso('config_impuestos')): ?>
    <a href="/AvilesPOSv2/modulos/configuracion/impuestos.php"
       class="tab <?= $tab_activo === 'impuestos' ? 'activo' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
            <line x1="12" y1="1" x2="12" y2="23"></line>
            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
        </svg>
        Impuestos
    </a>
    <?php endif; ?>

    <?php if (tiene_permiso('config_bd')): ?>
    <a href="/AvilesPOSv2/modulos/configuracion/bd.php"
       class="tab <?= $tab_activo === 'bd' ? 'activo' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
            <ellipse cx="12" cy="5" rx="9" ry="3"></ellipse>
            <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path>
            <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path>
        </svg>
        Base de datos
    </a>
    <?php endif; ?>

</div>