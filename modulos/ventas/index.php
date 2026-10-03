<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: /AvilesPOSv2/index.php");
    exit;
}

require __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/permisos.php';
requiere_permiso('ventas_vender');

$id_usuario = (int)$_SESSION['usuario_id'];

// Verificar turno abierto
$stmt = $conexion->prepare("
    SELECT t.id, t.monto_inicial, t.compartido, c.nombre AS caja_nombre
    FROM turnos t
    INNER JOIN cajas c ON t.id_caja = c.id
    WHERE t.id_usuario_apertura = ? AND t.estado = 'abierto'
    LIMIT 1
");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$turno = $stmt->get_result()->fetch_assoc();
$stmt->close();

$turno_compartido = null;
if (!$turno) {
    $stmt = $conexion->prepare("
        SELECT t.id, t.monto_inicial, t.compartido, c.nombre AS caja_nombre
        FROM turnos t
        INNER JOIN cajas c ON t.id_caja = c.id
        WHERE t.estado = 'abierto' AND t.compartido = 1 AND t.id_usuario_apertura != ?
        ORDER BY t.fecha_apertura ASC
        LIMIT 1
    ");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $turno_compartido = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if (!$turno && !$turno_compartido) {
    $_SESSION['turno_error'] = "Necesitas tener un turno abierto para vender.";
    header("Location: turnos/index.php");
    exit;
}

$turno_activo = $turno ?: $turno_compartido;
$es_compartido = !$turno;

// Configuración
$config = [];
$res = $conexion->query("SELECT clave, valor FROM configuracion");
while ($row = $res->fetch_assoc()) {
    $config[$row['clave']] = $row['valor'];
}
$iva_porcentaje = (float)($config['iva_porcentaje'] ?? 15);
$tipo_cambio    = (float)($config['tipo_cambio_usd'] ?? 36.50);

// Clientes
$clientes = $conexion->query("SELECT id, nombre FROM clientes WHERE activo = 1 ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);

// Clientes con saldo (para abonos)
$clientes_saldo = $conexion->query("
    SELECT id, nombre, saldo_actual, limite_credito 
    FROM clientes 
    WHERE activo = 1 AND saldo_actual > 0 
    ORDER BY nombre ASC
")->fetch_all(MYSQLI_ASSOC);

// Productos activos
$productos = $conexion->query("
    SELECT id, codigo, nombre, precio, stock, foto, id_departamento
    FROM productos 
    WHERE activo = 1
    ORDER BY nombre ASC
")->fetch_all(MYSQLI_ASSOC);

$ventas_tab = 'nueva';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva venta - AvilesPOS</title>
    <link rel="stylesheet" href="/AvilesPOSv2/assets/css/estilos.css">
</head>
<body class="dash-body">

    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="page-header">
            <div class="page-header-left">
                <h1>Nueva venta</h1>
                <p>
                    Caja: <strong><?= htmlspecialchars($turno_activo['caja_nombre']) ?></strong>
                    <?php if ($es_compartido): ?>
                        <span class="badge badge-warning" style="margin-left:6px;">Turno compartido</span>
                    <?php endif; ?>
                </p>
            </div>
            <div class="page-header-tabs">
                <?php include __DIR__ . '/includes/tabs.php'; ?>
            </div>
        </div>

        <div class="nv-layout">

            <!-- Buscador -->
            <div class="nv-buscador-wrap">
                <div class="nv-buscador">
                    <svg class="nv-buscador-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input type="text" id="posBuscador" placeholder="Escanea el código de barras o busca por nombre / código..." autofocus autocomplete="off">
                    <button type="button" class="nv-buscador-clear" id="posBuscadorClear" title="Limpiar">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>

                <!-- Dropdown de resultados -->
                <div class="nv-resultados" id="posResultados"></div>
            </div>

            <!-- Carrito -->
            <div class="nv-carrito">
                <div class="nv-carrito-header">
                    <div class="nv-carrito-titulo">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                            <circle cx="9" cy="21" r="1"></circle>
                            <circle cx="20" cy="21" r="1"></circle>
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                        </svg>
                        <h3>Carrito de venta</h3>
                        <span class="nv-carrito-count" id="carritoCount">0</span>
                    </div>
                    <button type="button" class="nv-carrito-vaciar" id="btnVaciarCarrito" style="display:none;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        </svg>
                        Vaciar
                    </button>
                </div>

                <div class="nv-items" id="carritoLista">
                    <!-- Items por JS -->
                </div>

                <div class="nv-carrito-footer">

                    <!-- Totales secundarios -->
                    <div class="nv-totales">
                        <div class="nv-total-linea">
                            <span>Subtotal</span>
                            <span id="posSubtotal">C$ 0.00</span>
                        </div>
                        <div class="nv-total-linea">
                            <span>IVA incluido (<?= $iva_porcentaje ?>%)</span>
                            <span id="posIva">C$ 0.00</span>
                        </div>
                        <div class="nv-total-linea nv-total-descuento">
                            <span>Descuento</span>
                            <div class="nv-descuento-input">
                                <div class="nv-descuento-toggle">
                                    <button type="button" id="descTipoMonto" class="activo">C$</button>
                                    <button type="button" id="descTipoPorcentaje">%</button>
                                </div>
                                <input type="number" id="descuentoInput" step="0.01" min="0" placeholder="0.00">
                            </div>
                        </div>
                        <div class="nv-total-linea descuento" id="posLineaDescuento" style="display:none;">
                            <span>Descuento aplicado</span>
                            <span id="posDescuento">−C$ 0.00</span>
                        </div>
                    </div>

                    <!-- Fila: Total + Botón Cobrar -->
                    <div class="nv-footer-accion">
                        <div class="nv-acciones-sec">
                            <?php if (tiene_permiso('ventas_movimientos_caja')): ?>
                            <button type="button" class="nv-accion-btn entrada" id="btnEntradaDinero" title="Entrada de dinero a la caja">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="14" height="14">
                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                                Entrada
                            </button>
                            <button type="button" class="nv-accion-btn salida" id="btnSalidaDinero" title="Salida de dinero de la caja">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="14" height="14">
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                                Salida
                            </button>
                            <?php endif; ?>

                            <?php if (tiene_permiso('ventas_abono')): ?>
                            <button type="button" class="nv-accion-btn abono" id="btnAbonar" title="Registrar abono de cliente">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="14" height="14">
                                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                    <line x1="1" y1="10" x2="23" y2="10"></line>
                                </svg>
                                Abonar
                            </button>
                            <?php endif; ?>

                            <?php if (tiene_permiso('ventas_devolucion')): ?>
                            <button type="button" class="nv-accion-btn devolucion" id="btnDevolucion" title="Registrar devolución de venta">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="14" height="14">
                                    <polyline points="9 14 4 9 9 4"></polyline>
                                    <path d="M20 20v-7a4 4 0 0 0-4-4H4"></path>
                                </svg>
                                Devolución
                            </button>
                            <?php endif; ?>
                        </div>
                        <div class="nv-total-final">
                            <span>Total</span>
                            <span class="valor" id="posTotal">C$ 0.00</span>
                        </div>
                        <button type="button" class="nv-btn-cobrar" id="btnCobrar" disabled>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                                <line x1="12" y1="1" x2="12" y2="23"></line>
                                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                            </svg>
                            Cobrar
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <!-- ============================================
         MODAL DE COBRO
         ============================================ -->
    <div class="modal-overlay" id="modalCobro">
        <div class="modal modal-xl">

            <div class="modal-header">
                <h3>Cobrar venta</h3>
                <button type="button" class="modal-close" id="btnCerrarCobro">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>

            <div class="modal-body cobro-layout">

                <div class="cobro-col-izq">

                    <div class="cobro-seccion">
                        <label class="cobro-seccion-label">Método de pago</label>
                        <div class="cobro-metodos">
                            <button type="button" class="cobro-metodo activo" data-metodo="efectivo">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                    <line x1="1" y1="10" x2="23" y2="10"></line>
                                </svg>
                                <span>Efectivo</span>
                            </button>
                            <button type="button" class="cobro-metodo" data-metodo="tarjeta">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                    <line x1="1" y1="10" x2="23" y2="10"></line>
                                </svg>
                                <span>Tarjeta</span>
                            </button>
                            <button type="button" class="cobro-metodo" data-metodo="transferencia">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                                    <polyline points="17 1 21 5 17 9"></polyline>
                                    <path d="M3 11V9a4 4 0 0 1 4-4h14"></path>
                                    <polyline points="7 23 3 19 7 15"></polyline>
                                    <path d="M21 13v2a4 4 0 0 1-4 4H3"></path>
                                </svg>
                                <span>Transferencia</span>
                            </button>
                            <button type="button" class="cobro-metodo" data-metodo="credito">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <polyline points="14 2 14 8 20 8"></polyline>
                                </svg>
                                <span>Crédito</span>
                            </button>
                        </div>
                    </div>

                    <div id="seccionEfectivo">
                        <div class="cobro-seccion">
                            <label class="cobro-seccion-label">Pago recibido</label>

                            <div class="form-group full" id="grupoPagoCordobas" style="margin-bottom:12px;">
                                <div class="input-suffix">
                                    <input type="number" id="pagoCordobas" step="0.01" min="0" placeholder="Córdobas">
                                    <span class="suffix">C$</span>
                                </div>
                            </div>

                            <div class="form-check" style="margin-bottom:12px;">
                                <input type="checkbox" id="chkCobrarUSD" value="1">
                                <label for="chkCobrarUSD">Cobrar en dólares (USD)</label>
                            </div>

                            <div class="form-group full" id="grupoPagoDolares" style="display:none;">
                                <div class="input-suffix">
                                    <input type="number" id="pagoDolares" step="0.01" min="0" placeholder="Dólares">
                                    <span class="suffix">$</span>
                                </div>
                                <span class="help">Tipo de cambio: C$ <span id="tcMostrado">36.50</span> por $1</span>
                            </div>
                        </div>
                    </div>

                    <div id="seccionTarjeta" style="display:none;">
                        <div class="cobro-seccion">
                            <div class="cobro-info">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="12" y1="16" x2="12" y2="12"></line>
                                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                                </svg>
                                <span>Cobra el total exacto en la terminal.</span>
                            </div>
                            <div class="form-group full" style="margin-top:12px;">
                                <label for="referenciaTarjeta">Número de referencia / voucher</label>
                                <input type="text" id="referenciaTarjeta" maxlength="100" placeholder="Ej: 123456">
                            </div>
                        </div>
                    </div>

                    <div id="seccionTransferencia" style="display:none;">
                        <div class="cobro-seccion">
                            <div class="cobro-info">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="12" y1="16" x2="12" y2="12"></line>
                                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                                </svg>
                                <span>Verifica que la transferencia se haya recibido.</span>
                            </div>
                            <div class="form-group full" style="margin-top:12px;">
                                <label for="referenciaTransferencia">Número de referencia</label>
                                <input type="text" id="referenciaTransferencia" maxlength="100" placeholder="Ej: 987654321">
                            </div>
                        </div>
                    </div>

                    <div id="seccionCredito" style="display:none;">
                        <div class="cobro-seccion">
                            <div id="creditoClienteInfo" class="cobro-info">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                                <span>Selecciona un cliente para vender al crédito.</span>
                            </div>
                        </div>
                    </div>

                    <div class="cobro-vuelto" id="cobroVuelto">
                        <div>
                            <div class="cobro-vuelto-label">Cambio (en córdobas)</div>
                            <div class="cobro-vuelto-valor" id="vueltoValor">C$ 0.00</div>
                        </div>
                        <div class="cobro-vuelto-detalle" id="vueltoDetalle"></div>
                    </div>

                </div>

                <div class="cobro-col-der">

                    <div class="cobro-total-box">
                        <div class="cobro-total-label">Total a cobrar</div>
                        <div class="cobro-total-valor" id="cobroTotal">C$ 0.00</div>
                    </div>

                    <div class="cobro-seccion" style="margin-top:16px;">
                        <label class="cobro-seccion-label" for="posClienteModal">Cliente</label>
                        <select id="posClienteModal">
                            <option value="">Consumidor final</option>
                            <?php foreach ($clientes as $c): ?>
                                <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="cobro-seccion" style="margin-top:16px;">
                        <div class="form-check">
                            <input type="checkbox" id="imprimirTicket" value="1" checked>
                            <label for="imprimirTicket">Imprimir ticket</label>
                        </div>
                    </div>

                    <div class="cobro-seccion" style="margin-top:16px;">
                        <label class="cobro-seccion-label" for="ventaObservaciones">Comentario</label>
                        <textarea id="ventaObservaciones" maxlength="500" placeholder="Nota interna (no aparece en el ticket)..." style="width:100%; padding:10px 12px; border:1px solid #ccc; border-radius:6px; font-family:inherit; font-size:13px; min-height:60px; resize:vertical;"></textarea>
                    </div>

                    <div class="modal-error" id="errorCobro" style="margin-top:16px;"></div>

                </div>

            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="btnCancelarCobro">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnConfirmarCobro" style="background:#1e7a3f;">
                    Confirmar cobro
                </button>
            </div>

        </div>
    </div>

    <!-- MODAL: Devolución -->
    <div class="modal-overlay" id="modalDevolucion">
        <div class="modal modal-xl">
            <div class="modal-header">
                <h3>Registrar devolución</h3>
                <button type="button" class="modal-close" id="devCerrarX">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>

            <div class="modal-body">

                <!-- PASO 1: BUSCAR -->
                <div id="devPasoBuscar">
                    <div class="form-group full" style="margin-bottom:16px;">
                        <label for="devBusquedaVenta">Número de factura o cédula del cliente</label>
                        <div style="display:flex; gap:8px;">
                            <input type="text" id="devBusquedaVenta" placeholder="Ej: 12 o 001-010190-0001A" style="flex:1;">
                            <button type="button" class="btn btn-primary" id="devBuscarBtn">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                </svg>
                                Buscar
                            </button>
                        </div>
                        <span class="help">Solo se puede devolver ventas completadas (no anuladas)</span>
                    </div>

                    <div class="modal-error" id="devError"></div>

                    <div id="devResultadosBusqueda"></div>
                </div>

                <!-- PASO 2: SELECCIONAR -->
                <div id="devPasoSeleccionar" style="display:none;">

                    <!-- Info de la venta -->
                    <div class="dev-venta-info" id="devVentaInfo"></div>

                    <!-- Layout horizontal: items izq, detalles der -->
                    <div class="dev-layout">

                        <!-- Columna izquierda: items -->
                        <div class="dev-col-items">
                            <div class="dev-col-titulo">Productos de la venta</div>
                            <div class="dev-items-lista" id="devItemsLista"></div>
                        </div>

                        <!-- Columna derecha: detalles y resumen -->
                        <div class="dev-col-detalles">
                            <div class="dev-col-titulo">Detalles de la devolución</div>

                            <div class="dev-form-grupo">
                                <label for="devMetodo">Método <span class="req">*</span></label>
                                <select id="devMetodo">
                                    <option value="efectivo">Efectivo</option>
                                    <option value="tarjeta">Tarjeta</option>
                                    <option value="transferencia">Transferencia</option>
                                    <option value="ajuste_credito">Ajustar saldo del cliente</option>
                                </select>
                                <span class="help" id="devMetodoHelp"></span>
                            </div>

                            <div class="dev-form-grupo" id="devGrupoReferencia" style="display:none;">
                                <label for="devReferencia">Referencia / Comprobante</label>
                                <input type="text" id="devReferencia" maxlength="100" placeholder="Ej: 123456">
                            </div>

                            <div class="dev-form-grupo">
                                <label for="devMotivo">Motivo <span class="req">*</span></label>
                                <textarea id="devMotivo" maxlength="300" placeholder="Explica el motivo de la devolución..." style="min-height:70px;"></textarea>
                            </div>

                            <div class="dev-total-box">
                                <div class="dev-total-label">Total a devolver</div>
                                <div class="dev-total-valor" id="devTotalValor">C$ 0.00</div>
                            </div>

                            <button type="button" class="btn btn-secondary" id="devVolverBuscar" style="width:100%; margin-top:10px;">
                                ← Buscar otra venta
                            </button>
                        </div>

                    </div>

                </div>

            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="devCancelar">Cancelar</button>
                <button type="button" class="btn btn-primary" id="devGuardar" style="background:#b00020; display:none;">
                    Procesar devolución
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL: Abonar -->
    <div class="modal-overlay" id="modalAbonar">
        <div class="modal modal-abonar">
            <div class="modal-header">
                <h3>Registrar abono</h3>
                <button type="button" class="modal-close" id="abonarCerrarX">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>

            <div class="modal-body">
                <div class="modal-error" id="abonarError"></div>

                <div class="abonar-layout">

                    <!-- Columna izquierda: Cliente + Saldo -->
                    <div class="abonar-col-izq">
                        <div class="form-group full" style="margin-bottom:14px;">
                            <label for="abonarCliente">Cliente <span class="req">*</span></label>
                            <select id="abonarCliente">
                                <option value="">— Seleccionar —</option>
                                <?php foreach ($clientes as $c): ?>
                                    <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div id="abonarInfoCliente" style="display:none;">
                            <div class="abono-info-box">
                                <div class="abono-info-label">Saldo actual</div>
                                <div class="abono-info-valor" id="abonarSaldoActual">C$ 0.00</div>
                            </div>
                        </div>
                    </div>

                    <!-- Columna derecha: Detalles del abono -->
                    <div class="abonar-col-der">

                        <div class="abonar-grid">
                            <div class="form-group">
                                <label for="abonarMonto">Monto <span class="req">*</span></label>
                                <div class="input-suffix">
                                    <input type="number" id="abonarMonto" step="0.01" min="0.01" placeholder="0.00">
                                    <span class="suffix">C$</span>
                                </div>
                                <span class="help" id="abonarHelpMonto"></span>
                            </div>

                            <div class="form-group">
                                <label for="abonarMetodo">Método <span class="req">*</span></label>
                                <select id="abonarMetodo">
                                    <option value="efectivo">Efectivo</option>
                                    <option value="transferencia">Transferencia</option>
                                    <option value="tarjeta">Tarjeta</option>
                                </select>
                            </div>

                            <div class="form-group full" id="abonarGrupoReferencia" style="display:none;">
                                <label for="abonarReferencia">Referencia / Comprobante</label>
                                <input type="text" id="abonarReferencia" maxlength="100" placeholder="Ej: 123456 (opcional)">
                            </div>

                            <div class="form-group full">
                                <label for="abonarObservaciones">Observaciones</label>
                                <input type="text" id="abonarObservaciones" maxlength="300" placeholder="Notas adicionales (opcional)">
                            </div>
                        </div>

                    </div>

                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="abonarCancelar">Cancelar</button>
                <button type="button" class="btn btn-primary" id="abonarGuardar" style="background:#1e7a3f;">
                    Registrar abono
                </button>
            </div>
        </div>
    </div>

        <!-- MODAL: Movimiento de caja (Entrada/Salida) -->
    <div class="modal-overlay" id="modalMovCaja">
        <div class="modal" style="max-width:440px;">
            <div class="modal-header">
                <h3 id="movCajaTitulo">Entrada de dinero</h3>
                <button type="button" class="modal-close" id="movCajaCerrarX">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>

            <div class="modal-body">
                <div class="modal-error" id="movCajaError"></div>

                <div id="movCajaInfo" class="cobro-info" style="margin-bottom:16px;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="16" x2="12" y2="12"></line>
                        <line x1="12" y1="8" x2="12.01" y2="8"></line>
                    </svg>
                    <span id="movCajaInfoText"></span>
                </div>

                <div class="form-group full" style="margin-bottom:14px;">
                    <label for="movCajaMonto">Monto <span class="req">*</span></label>
                    <div class="input-suffix">
                        <input type="number" id="movCajaMonto" step="0.01" min="0.01" placeholder="0.00">
                        <span class="suffix">C$</span>
                    </div>
                </div>

                <div class="form-group full">
                    <label for="movCajaMotivo">Motivo <span class="req">*</span></label>
                    <textarea id="movCajaMotivo" maxlength="300" placeholder="Explica el motivo del movimiento..."></textarea>
                    <span class="help">Obligatorio para dejar registro</span>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="movCajaCancelar">Cancelar</button>
                <button type="button" class="btn btn-primary" id="movCajaGuardar">Guardar</button>
            </div>
        </div>
    </div>

    <!-- MODAL: Confirmación genérica -->
    <div class="modal-overlay" id="modalConfirmar">
        <div class="modal" style="max-width:400px;">
            <div class="modal-header">
                <h3 id="confirmTitulo">Confirmar acción</h3>
                <button type="button" class="modal-close" id="confirmCerrarX">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <div class="modal-body" style="text-align:center; padding:28px 24px;">
                <div style="width:56px; height:56px; margin:0 auto 16px; border-radius:50%; background:#fff4e0; display:flex; align-items:center; justify-content:center;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#a05a00" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="28" height="28">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                        <line x1="12" y1="9" x2="12" y2="13"></line>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                </div>
                <p id="confirmMensaje" style="font-size:14px; color:#555; line-height:1.5;">¿Estás seguro?</p>
            </div>
            <div class="modal-footer" style="justify-content:center;">
                <button type="button" class="btn btn-secondary" id="confirmCancelar">Cancelar</button>
                <button type="button" class="btn btn-primary" id="confirmAceptar">Confirmar</button>
            </div>
        </div>
    </div>

    <script>
        window.PRODUCTOS = <?= json_encode($productos) ?>;
        window.IVA_PORCENTAJE = <?= $iva_porcentaje ?>;
        window.TIPO_CAMBIO = <?= $tipo_cambio ?>;
        window.TURNO_ACTIVO_ID = <?= (int)$turno_activo['id'] ?>;
        window.CLIENTES_SALDO = <?= json_encode($clientes_saldo) ?>;
    </script>
    <script src="/AvilesPOSv2/assets/js/pos.js?v=<?= filemtime(__DIR__ . '/../../assets/js/pos.js') ?>"></script>

</body>
</html>