<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: /AvilesPOSv2/index.php");
    exit;
}

require __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/permisos.php';
requiere_permiso('clientes_editar');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: index.php");
    exit;
}

// Cargar cliente
$stmt = $conexion->prepare("SELECT * FROM clientes WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$cliente = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$cliente) {
    header("Location: index.php");
    exit;
}

$old = $_SESSION['cliente_old'] ?? $cliente;
$error = $_SESSION['cliente_error'] ?? null;
unset($_SESSION['cliente_old'], $_SESSION['cliente_error']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar cliente - AvilesPOS</title>
    <link rel="stylesheet" href="/AvilesPOSv2/assets/css/estilos.css">
</head>
<body class="dash-body">

    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="page-header">
            <div class="page-header-left">
                <h1>Editar cliente</h1>
                <p>Modifica los datos del cliente</p>
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

        <form action="actualizar.php" method="POST" autocomplete="off" id="formCliente">
            <input type="hidden" name="id" value="<?= (int)$cliente['id'] ?>">

            <div class="form-layout">

                <div class="form-container">

                    <!-- Información básica -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            Información básica
                        </div>
                        <div class="form-grid">

                            <div class="form-group full">
                                <label for="nombre">Nombre completo / Razón social <span class="req">*</span></label>
                                <input type="text" name="nombre" id="nombre" required maxlength="200"
                                       value="<?= htmlspecialchars($old['nombre'] ?? '') ?>">
                            </div>

                            <div class="form-group">
                                <label for="tipo">Tipo de cliente <span class="req">*</span></label>
                                <select name="tipo" id="tipo" required>
                                    <option value="natural" <?= (($old['tipo'] ?? 'natural') === 'natural') ? 'selected' : '' ?>>
                                        Persona natural
                                    </option>
                                    <option value="juridica" <?= (($old['tipo'] ?? '') === 'juridica') ? 'selected' : '' ?>>
                                        Persona jurídica (empresa)
                                    </option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="cedula">Cédula</label>
                                <input type="text" name="cedula" id="cedula" maxlength="30"
                                       value="<?= htmlspecialchars($old['cedula'] ?? '') ?>">
                            </div>

                            <div class="form-group">
                                <label for="ruc">RUC</label>
                                <input type="text" name="ruc" id="ruc" maxlength="30"
                                       value="<?= htmlspecialchars($old['ruc'] ?? '') ?>">
                            </div>

                        </div>
                    </div>

                    <!-- Contacto -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                            </svg>
                            Contacto
                        </div>
                        <div class="form-grid">

                            <div class="form-group">
                                <label for="telefono">Teléfono / WhatsApp</label>
                                <input type="text" name="telefono" id="telefono" maxlength="30"
                                       value="<?= htmlspecialchars($old['telefono'] ?? '') ?>">
                            </div>

                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="text" name="email" id="email" maxlength="150"
                                       value="<?= htmlspecialchars($old['email'] ?? '') ?>">
                            </div>

                            <div class="form-group full">
                                <label for="direccion">Dirección</label>
                                <input type="text" name="direccion" id="direccion" maxlength="300"
                                       value="<?= htmlspecialchars($old['direccion'] ?? '') ?>">
                            </div>

                        </div>
                    </div>

                    <!-- Crédito -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                <line x1="1" y1="10" x2="23" y2="10"></line>
                            </svg>
                            Crédito
                        </div>
                        <div class="form-grid">

                            <div class="form-group full">
                                <div class="form-check">
                                    <input type="checkbox" name="permite_credito" id="permite_credito" value="1"
                                        <?= (!empty($old['permite_credito'])) ? 'checked' : '' ?>>
                                    <label for="permite_credito">Permitir ventas al crédito a este cliente</label>
                                </div>
                            </div>

                            <div class="form-group full" id="grupo_limite" style="display:none;">
                                <label for="limite_credito">Límite de crédito (C$)</label>
                                <div class="input-suffix">
                                    <input type="number" name="limite_credito" id="limite_credito" step="0.01" min="0"
                                           value="<?= htmlspecialchars($old['limite_credito'] ?? '0') ?>">
                                    <span class="suffix">C$</span>
                                </div>
                                <span class="help">Monto máximo que se le puede fiar</span>
                            </div>

                            <?php if ((float)$cliente['saldo_actual'] > 0): ?>
                                <div class="form-group full">
                                    <label>Saldo actual</label>
                                    <div style="padding:10px 12px; background:#ffe0e0; border:1px solid #ffd0d0; border-radius:6px; font-size:14px; color:#b00020;">
                                        <strong>C$ <?= number_format($cliente['saldo_actual'], 2) ?></strong>
                                        <span style="font-size:12px; margin-left:8px; opacity:0.8;">
                                            (Se ajusta desde el módulo de Ventas)
                                        </span>
                                    </div>
                                </div>
                            <?php endif; ?>

                        </div>
                    </div>

                    <!-- Notas -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                            </svg>
                            Notas
                        </div>
                        <div class="form-group full">
                            <label for="notas">Notas internas</label>
                            <textarea name="notas" id="notas" maxlength="500"><?= htmlspecialchars($old['notas'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <div class="form-section" style="border-top:1px solid #eef1f4; padding-top:20px; margin-top:0;">
                        <div class="form-check">
                            <input type="checkbox" name="activo" id="activo" value="1" <?= (!empty($old['activo'])) ? 'checked' : '' ?>>
                            <label for="activo">Cliente activo</label>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Guardar cambios</button>
                        <a href="index.php" class="btn btn-secondary">Cancelar</a>
                    </div>
                </div>

            </div>
        </form>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const checkCredito = document.getElementById('permite_credito');
            const grupoLimite = document.getElementById('grupo_limite');

            function toggleLimite() {
                grupoLimite.style.display = checkCredito.checked ? 'flex' : 'none';
            }

            checkCredito.addEventListener('change', toggleLimite);
            toggleLimite();
        });
    </script>

</body>
</html>