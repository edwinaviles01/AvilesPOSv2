<?php
// Cargar helper de permisos (si no se cargó antes)
if (!function_exists('tiene_permiso')) {
    require_once __DIR__ . '/permisos.php';
}

// Si no hay permisos en sesión, cargarlos
if (!isset($_SESSION['permisos']) && isset($_SESSION['usuario_id']) && isset($conexion)) {
    cargar_permisos_usuario($conexion, $_SESSION['usuario_id'], $_SESSION['usuario_rol']);
}

$archivo_actual = basename($_SERVER['PHP_SELF']);
$carpeta_actual = basename(dirname($_SERVER['PHP_SELF']));

$inicial = strtoupper(substr($_SESSION['usuario_nombre'] ?? 'U', 0, 1));

// Permiso para ver la sección de Ventas
$ver_ventas = tiene_permiso('ventas_vender') 
           || tiene_permiso('ventas_historial_propio') 
           || tiene_permiso('ventas_historial_completo');

// Permiso para ver Configuración
$ver_config = tiene_permiso('config_empresa') 
           || tiene_permiso('config_cajas') 
           || tiene_permiso('config_usuarios') 
           || tiene_permiso('config_impuestos') 
           || tiene_permiso('config_bd');
?>

<aside class="sidebar">
    <div class="sidebar-header">
        <img src="/AvilesPOSv2/assets/img/logo_aviles.png" alt="AvilesPOS" class="sidebar-logo">
        <h2>AvilesPOS</h2>
    </div>

    <nav class="sidebar-menu">

        <?php if (tiene_permiso('dashboard_ver')): ?>
        <a href="/AvilesPOSv2/dashboard.php" class="menu-item <?= ($archivo_actual === 'dashboard.php') ? 'activo' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                <polyline points="9 22 9 12 15 12 15 22"></polyline>
            </svg>
            <span>Inicio</span>
        </a>
        <?php endif; ?>

        <?php if (tiene_permiso('productos_ver')): ?>
        <a href="/AvilesPOSv2/modulos/productos/index.php" class="menu-item <?= ($carpeta_actual === 'productos') ? 'activo' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                <line x1="12" y1="22.08" x2="12" y2="12"></line>
            </svg>
            <span>Productos</span>
        </a>
        <?php endif; ?>

        <?php if (tiene_permiso('inventario_ver')): ?>
        <a href="/AvilesPOSv2/modulos/inventario/index.php" class="menu-item <?= ($carpeta_actual === 'inventario') ? 'activo' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                <polyline points="23 4 23 10 17 10"></polyline>
                <polyline points="1 20 1 14 7 14"></polyline>
                <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
            </svg>
            <span>Inventario</span>
        </a>
        <?php endif; ?>

        <?php if ($ver_ventas): ?>
        <a href="/AvilesPOSv2/modulos/ventas/index.php" class="menu-item <?= ($carpeta_actual === 'ventas') ? 'activo' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                <circle cx="9" cy="21" r="1"></circle>
                <circle cx="20" cy="21" r="1"></circle>
                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
            </svg>
            <span>Ventas</span>
        </a>
        <?php endif; ?>

        <?php if (tiene_permiso('clientes_ver')): ?>
        <a href="/AvilesPOSv2/modulos/clientes/index.php" class="menu-item <?= ($carpeta_actual === 'clientes') ? 'activo' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
            <span>Clientes</span>
        </a>
        <?php endif; ?>

        <?php if (tiene_permiso('reportes_ver')): ?>
        <a href="/AvilesPOSv2/modulos/reportes/index.php" class="menu-item <?= ($carpeta_actual === 'reportes') ? 'activo' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                <line x1="18" y1="20" x2="18" y2="10"></line>
                <line x1="12" y1="20" x2="12" y2="4"></line>
                <line x1="6" y1="20" x2="6" y2="14"></line>
            </svg>
            <span>Reportes</span>
        </a>
        <?php endif; ?>

        <?php if ($ver_config): ?>
        <a href="/AvilesPOSv2/modulos/configuracion/index.php" class="menu-item <?= ($carpeta_actual === 'configuracion') ? 'activo' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                <circle cx="12" cy="12" r="3"></circle>
                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
            </svg>
            <span>Configuración</span>
        </a>
        <?php endif; ?>

    </nav>

    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar"><?= htmlspecialchars($inicial) ?></div>
            <div class="user-details">
                <strong><?= htmlspecialchars($_SESSION['usuario_nombre'] ?? '') ?></strong>
                <span>
                    <?php
                    $roles_label = [
                        'administrador' => 'Administrador',
                        'gerente' => 'Gerente',
                        'supervisor' => 'Supervisor',
                        'cajero' => 'Cajero'
                    ];
                    echo htmlspecialchars($roles_label[$_SESSION['usuario_rol'] ?? ''] ?? ($_SESSION['usuario_rol'] ?? ''));
                    ?>
                </span>
            </div>
        </div>
        <a href="/AvilesPOSv2/logout.php" class="logout-btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            <span>Cerrar sesión</span>
        </a>
    </div>
</aside>