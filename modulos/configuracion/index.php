<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: /AvilesPOSv2/index.php");
    exit;
}

require __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/permisos.php';

// Redirigir a la primera pestaña que tenga permiso
if (tiene_permiso('config_empresa')) {
    header("Location: empresa.php");
} elseif (tiene_permiso('config_cajas')) {
    header("Location: cajas.php");
} elseif (tiene_permiso('config_usuarios')) {
    header("Location: usuarios.php");
} elseif (tiene_permiso('config_impuestos')) {
    header("Location: impuestos.php");
} elseif (tiene_permiso('config_bd')) {
    header("Location: bd.php");
} else {
    header("Location: /AvilesPOSv2/dashboard.php");
}
exit;