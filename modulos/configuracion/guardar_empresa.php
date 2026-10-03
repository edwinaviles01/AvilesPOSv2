<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: /AvilesPOSv2/index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: empresa.php");
    exit;
}

require __DIR__ . '/../../config/db.php';

$errores = [];

// Recibir datos
$empresa_nombre    = trim($_POST['empresa_nombre'] ?? '');
$empresa_ruc       = trim($_POST['empresa_ruc'] ?? '');
$empresa_telefono  = trim($_POST['empresa_telefono'] ?? '');
$empresa_email     = trim($_POST['empresa_email'] ?? '');
$empresa_direccion = trim($_POST['empresa_direccion'] ?? '');
$moneda            = trim($_POST['moneda'] ?? 'C$');
$iva_porcentaje    = trim($_POST['iva_porcentaje'] ?? '15');
$tipo_cambio_usd   = trim($_POST['tipo_cambio_usd'] ?? '36.50');

// Validaciones
if ($empresa_nombre === '') {
    $errores[] = "El nombre del negocio es obligatorio.";
}
if ($moneda === '') {
    $errores[] = "El símbolo de moneda es obligatorio.";
}
if (!is_numeric($iva_porcentaje) || $iva_porcentaje < 0 || $iva_porcentaje > 100) {
    $errores[] = "El IVA debe ser un número entre 0 y 100.";
}
if (!is_numeric($tipo_cambio_usd) || $tipo_cambio_usd <= 0) {
    $errores[] = "El tipo de cambio debe ser mayor a 0.";
}

if ($empresa_email !== '' && !filter_var($empresa_email, FILTER_VALIDATE_EMAIL)) {
    $errores[] = "El email no es válido.";
}

// Actualizar cada valor
if (empty($errores)) {
    $valores = [
        'empresa_nombre'    => $empresa_nombre,
        'empresa_ruc'       => $empresa_ruc,
        'empresa_telefono'  => $empresa_telefono,
        'empresa_email'     => $empresa_email,
        'empresa_direccion' => $empresa_direccion,
        'moneda'            => $moneda,
        'iva_porcentaje'    => $iva_porcentaje,
        'tipo_cambio_usd'   => $tipo_cambio_usd
    ];

    foreach ($valores as $clave => $valor) {
        // INSERT ... ON DUPLICATE KEY UPDATE
        $stmt = $conexion->prepare("
            INSERT INTO configuracion (clave, valor) VALUES (?, ?)
            ON DUPLICATE KEY UPDATE valor = VALUES(valor)
        ");
        $stmt->bind_param("ss", $clave, $valor);
        $stmt->execute();
        $stmt->close();
    }
}

// Manejo de logo (opcional)
if (empty($errores) && isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
    $permitidos = ['image/jpeg', 'image/png', 'image/webp'];
    $tipo = mime_content_type($_FILES['logo']['tmp_name']);

    if (!in_array($tipo, $permitidos)) {
        $errores[] = "El logo debe ser JPG, PNG o WEBP.";
    } elseif ($_FILES['logo']['size'] > 2 * 1024 * 1024) {
        $errores[] = "El logo no puede pesar más de 2MB.";
    } else {
        // Crear carpeta si no existe
        $carpeta = __DIR__ . '/../../assets/img/logos/';
        if (!is_dir($carpeta)) {
            mkdir($carpeta, 0755, true);
        }

        $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
        $nombre_archivo = 'empresa_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . strtolower($ext);

        if (move_uploaded_file($_FILES['logo']['tmp_name'], $carpeta . $nombre_archivo)) {
            // Obtener el logo anterior para borrarlo
            $logo_anterior = $conexion->query("SELECT valor FROM configuracion WHERE clave = 'empresa_logo'")->fetch_assoc();
            if ($logo_anterior && $logo_anterior['valor']) {
                $ruta_vieja = $carpeta . $logo_anterior['valor'];
                if (file_exists($ruta_vieja)) {
                    unlink($ruta_vieja);
                }
            }

            // Guardar en configuracion
            $stmt = $conexion->prepare("
                INSERT INTO configuracion (clave, valor) VALUES ('empresa_logo', ?)
                ON DUPLICATE KEY UPDATE valor = VALUES(valor)
            ");
            $stmt->bind_param("s", $nombre_archivo);
            $stmt->execute();
            $stmt->close();
        } else {
            $errores[] = "Error al guardar el logo.";
        }
    }
}

// Resultado
if (!empty($errores)) {
    $_SESSION['empresa_error'] = implode(' ', $errores);
} else {
    $_SESSION['empresa_mensaje'] = "Configuración guardada correctamente.";
}

header("Location: empresa.php");
exit;