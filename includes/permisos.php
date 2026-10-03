<?php
/**
 * Helper de permisos - AvilesPOS
 * 
 * Uso:
 *   require_once 'includes/permisos.php';
 *   if (tiene_permiso('productos_crear')) { ... }
 */

if (!function_exists('cargar_permisos_usuario')) {
    /**
     * Carga los permisos de un usuario en la sesión
     * (se llama una vez al iniciar sesión)
     */
    function cargar_permisos_usuario($conexion, $id_usuario, $rol_clave) {
        // 1. Permisos del rol
        $stmt = $conexion->prepare("
            SELECT p.clave
            FROM permisos p
            INNER JOIN roles_permisos rp ON p.id = rp.id_permiso
            INNER JOIN roles r ON r.id = rp.id_rol
            WHERE r.clave = ?
        ");
        $stmt->bind_param("s", $rol_clave);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $permisos = [];
        foreach ($res as $row) {
            $permisos[$row['clave']] = true;
        }

        // 2. Permisos extra (agregar)
        $stmt = $conexion->prepare("
            SELECT p.clave
            FROM permisos p
            INNER JOIN usuarios_permisos up ON p.id = up.id_permiso
            WHERE up.id_usuario = ? AND up.tipo = 'extra'
        ");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        foreach ($res as $row) {
            $permisos[$row['clave']] = true;
        }

        // 3. Permisos denegados (quitar)
        $stmt = $conexion->prepare("
            SELECT p.clave
            FROM permisos p
            INNER JOIN usuarios_permisos up ON p.id = up.id_permiso
            WHERE up.id_usuario = ? AND up.tipo = 'denegado'
        ");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        foreach ($res as $row) {
            unset($permisos[$row['clave']]);
        }

        $_SESSION['permisos'] = array_keys($permisos);
    }
}

if (!function_exists('tiene_permiso')) {
    /**
     * Verifica si el usuario actual tiene un permiso
     */
    function tiene_permiso($clave) {
        if (!isset($_SESSION['permisos'])) return false;
        return in_array($clave, $_SESSION['permisos']);
    }
}

if (!function_exists('requiere_permiso')) {
    /**
     * Redirige si no tiene el permiso
     */
    function requiere_permiso($clave, $redirect = '/AvilesPOSv2/dashboard.php') {
        if (!tiene_permiso($clave)) {
            header("Location: $redirect");
            exit;
        }
    }
}