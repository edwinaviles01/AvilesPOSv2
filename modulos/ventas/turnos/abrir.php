<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: /AvilesPOSv2/index.php");
    exit;
}

require __DIR__ . '/../../../config/db.php';

$id_usuario = (int)$_SESSION['usuario_id'];

// Validación 1: ¿el usuario ya tiene un turno abierto?
$stmt = $conexion->prepare("SELECT id FROM turnos WHERE id_usuario_apertura = ? AND estado = 'abierto' LIMIT 1");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$stmt->store_result();
$ya_tiene_turno = $stmt->num_rows > 0;
$stmt->close();

if ($ya_tiene_turno) {
    $_SESSION['turno_error'] = "Ya tienes un turno abierto. Debes cerrarlo antes de abrir uno nuevo.";
    header("Location: index.php");
    exit;
}

// Cargamos cajas activas que NO tengan un turno abierto
$sql = "SELECT c.id, c.nombre
        FROM cajas c
        WHERE c.activa = 1
          AND NOT EXISTS (
            SELECT 1 FROM turnos t WHERE t.id_caja = c.id AND t.estado = 'abierto'
          )
        ORDER BY c.nombre ASC";
$cajas_disponibles = $conexion->query($sql)->fetch_all(MYSQLI_ASSOC);

$error = $_SESSION['turno_error'] ?? null;
$old = $_SESSION['turno_old'] ?? [];
unset($_SESSION['turno_error'], $_SESSION['turno_old']);

$ventas_tab = 'turnos';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Abrir turno - AvilesPOS</title>
    <link rel="stylesheet" href="/AvilesPOSv2/assets/css/estilos.css">
</head>
<body class="dash-body">

    <?php include __DIR__ . '/../../../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="page-header">
            <div class="page-header-left">
                <h1>Abrir turno</h1>
                <p>Inicia una nueva sesión de caja</p>
            </div>
            <a href="index.php" class="btn btn-secondary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                Volver
            </a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (count($cajas_disponibles) === 0): ?>

            <div class="empty-state">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" width="64" height="64">
                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                    <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                </svg>
                <h3>No hay cajas disponibles</h3>
                <p>Todas las cajas activas ya tienen un turno abierto, o no hay cajas activas configuradas.</p>
                <a href="/AvilesPOSv2/modulos/configuracion/cajas.php" class="btn btn-primary">Configurar cajas</a>
            </div>

        <?php else: ?>

            <div class="form-container" style="max-width:600px;">
                <form action="guardar_apertura.php" method="POST" autocomplete="off">

                    <div class="form-section">
                        <div class="form-section-title">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                                <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                                <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                            </svg>
                            Datos del turno
                        </div>

                        <div class="form-group full">
                            <label for="id_caja">Caja a usar <span class="req">*</span></label>
                            <select name="id_caja" id="id_caja" required>
                                <option value="">— Seleccionar caja —</option>
                                <?php foreach ($cajas_disponibles as $c): ?>
                                    <option value="<?= (int)$c['id'] ?>"
                                        <?= (($old['id_caja'] ?? '') == $c['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($c['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <span class="help">Solo se muestran cajas activas sin turno abierto</span>
                        </div>

                        <div class="form-group full">
                            <label for="monto_inicial">Monto inicial en efectivo <span class="req">*</span></label>
                            <div class="input-suffix">
                                <input type="number" name="monto_inicial" id="monto_inicial" required step="0.01" min="0"
                                       placeholder="0.00"
                                       value="<?= htmlspecialchars($old['monto_inicial'] ?? '') ?>">
                                <span class="suffix">C$</span>
                            </div>
                            <span class="help">Efectivo con el que arranca la caja (para dar cambio)</span>
                        </div>

                        <div class="form-group full">
                            <div class="form-check">
                                <input type="checkbox" name="compartido" id="compartido" value="1"
                                    <?= (!empty($old['compartido'])) ? 'checked' : '' ?>>
                                <label for="compartido">Compartir este turno con otros vendedores</label>
                            </div>
                            <span class="help" style="margin-left:24px;">Si está marcado, otros usuarios podrán vender desde esta misma caja</span>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Abrir turno</button>
                        <a href="index.php" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>

        <?php endif; ?>
    </main>

</body>
</html>