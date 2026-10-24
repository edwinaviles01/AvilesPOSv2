<?php
set_time_limit(300);
ob_start();

session_start();
header('Content-Type: application/json; charset=utf-8');

function enviarError($msg) {
    if (ob_get_length()) ob_clean();
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

if (!isset($_SESSION['usuario_id'])) {
    enviarError('Sesión no válida');
}

require __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../includes/permisos.php';

// Solo el administrador puede hacer esto
if ($_SESSION['usuario_rol'] !== 'administrador' || !tiene_permiso('config_bd')) {
    enviarError('No tienes permisos para esta acción');
}

// Deshabilitar FKs para poder borrar todas las tablas sin problemas de orden
$conexion->query("SET FOREIGN_KEY_CHECKS = 0");

// Lista de tablas a eliminar (en cualquier orden gracias al FK check)
$tablas = [
    'devoluciones_detalle', 'devoluciones', 'abonos', 'movimientos_caja', 
    'ventas_detalle', 'ventas', 'movimientos_inventario', 'turnos', 'cajas',
    'producto_precios', 'productos', 'categorias', 'departamentos', 'proveedores',
    'clientes', 'usuarios_permisos', 'roles_permisos', 'permisos', 'roles', 
    'usuarios', 'configuracion'
];

foreach ($tablas as $t) {
    $conexion->query("DROP TABLE IF EXISTS `$t`");
}

// ==========================================
// RECREAR EL ESQUEMA DE LA BASE DE DATOS
// ==========================================

$queries = [];

// 1. Usuarios
$queries[] = "CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol VARCHAR(30) NOT NULL DEFAULT 'cajero',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    ultimo_acceso TIMESTAMP NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

// 2. Roles y Permisos
$queries[] = "CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(30) NOT NULL UNIQUE,
    nombre VARCHAR(50) NOT NULL,
    descripcion VARCHAR(200),
    protegido TINYINT(1) NOT NULL DEFAULT 0,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

$queries[] = "CREATE TABLE permisos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(50) NOT NULL UNIQUE,
    grupo VARCHAR(30) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    descripcion VARCHAR(200)
)";

$queries[] = "CREATE TABLE roles_permisos (
    id_rol INT NOT NULL,
    id_permiso INT NOT NULL,
    PRIMARY KEY (id_rol, id_permiso),
    FOREIGN KEY (id_rol) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (id_permiso) REFERENCES permisos(id) ON DELETE CASCADE
)";

$queries[] = "CREATE TABLE usuarios_permisos (
    id_usuario INT NOT NULL,
    id_permiso INT NOT NULL,
    tipo ENUM('extra', 'denegado') NOT NULL,
    PRIMARY KEY (id_usuario, id_permiso, tipo),
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (id_permiso) REFERENCES permisos(id) ON DELETE CASCADE
)";

// 3. Inventario base
$queries[] = "CREATE TABLE departamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

$queries[] = "CREATE TABLE categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_departamento INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_departamento) REFERENCES departamentos(id),
    UNIQUE KEY unique_cat_por_depto (id_departamento, nombre)
)";

$queries[] = "CREATE TABLE proveedores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    contacto VARCHAR(150),
    telefono VARCHAR(30),
    email VARCHAR(150),
    direccion VARCHAR(255),
    activo TINYINT(1) NOT NULL DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

$queries[] = "CREATE TABLE productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT,
    foto VARCHAR(255),
    id_departamento INT,
    id_categoria INT,
    id_proveedor INT,
    precio_costo DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    precio DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    stock INT NOT NULL DEFAULT 0,
    stock_minimo INT NOT NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_departamento) REFERENCES departamentos(id),
    FOREIGN KEY (id_categoria) REFERENCES categorias(id),
    FOREIGN KEY (id_proveedor) REFERENCES proveedores(id)
)";

$queries[] = "CREATE TABLE producto_precios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_producto INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    precio DECIMAL(10,2) NOT NULL,
    fecha_inicio DATE,
    fecha_fin DATE,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_producto) REFERENCES productos(id) ON DELETE CASCADE
)";

$queries[] = "CREATE TABLE movimientos_inventario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_producto INT NOT NULL,
    id_proveedor INT NULL,
    tipo VARCHAR(20) NOT NULL,
    cantidad INT NOT NULL,
    stock_anterior INT NOT NULL,
    stock_nuevo INT NOT NULL,
    precio_costo_anterior DECIMAL(10,2) DEFAULT NULL,
    precio_costo_nuevo DECIMAL(10,2) DEFAULT NULL,
    precio_venta_anterior DECIMAL(10,2) DEFAULT NULL,
    precio_venta_nuevo DECIMAL(10,2) DEFAULT NULL,
    comentario VARCHAR(500),
    id_usuario INT NOT NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_producto) REFERENCES productos(id),
    FOREIGN KEY (id_proveedor) REFERENCES proveedores(id),
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id)
)";

// 4. Clientes y ventas
$queries[] = "CREATE TABLE clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL,
    cedula VARCHAR(30),
    ruc VARCHAR(30),
    telefono VARCHAR(30),
    email VARCHAR(150),
    direccion VARCHAR(300),
    tipo ENUM('natural', 'juridica') NOT NULL DEFAULT 'natural',
    permite_credito TINYINT(1) NOT NULL DEFAULT 0,
    limite_credito DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    saldo_actual DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    notas TEXT,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

$queries[] = "CREATE TABLE cajas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    activa TINYINT(1) NOT NULL DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

$queries[] = "CREATE TABLE turnos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_caja INT NOT NULL,
    id_usuario_apertura INT NOT NULL,
    id_usuario_cierre INT,
    monto_inicial DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    compartido TINYINT(1) NOT NULL DEFAULT 0,
    fecha_apertura TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    monto_final_contado DECIMAL(10,2),
    monto_final_sistema DECIMAL(10,2),
    diferencia DECIMAL(10,2),
    total_ventas DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    numero_ventas INT NOT NULL DEFAULT 0,
    estado ENUM('abierto', 'cerrado') NOT NULL DEFAULT 'abierto',
    fecha_cierre TIMESTAMP NULL,
    observaciones TEXT,
    FOREIGN KEY (id_caja) REFERENCES cajas(id),
    FOREIGN KEY (id_usuario_apertura) REFERENCES usuarios(id),
    FOREIGN KEY (id_usuario_cierre) REFERENCES usuarios(id)
)";

$queries[] = "CREATE TABLE ventas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_factura INT NOT NULL UNIQUE,
    id_cliente INT,
    id_usuario INT NOT NULL,
    id_turno INT NOT NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    subtotal DECIMAL(10,2) NOT NULL,
    iva DECIMAL(10,2) NOT NULL,
    descuento DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    descuento_porcentaje DECIMAL(5,2) DEFAULT NULL,
    total DECIMAL(10,2) NOT NULL,
    metodo_pago VARCHAR(20) NOT NULL,
    referencia_pago VARCHAR(100) NULL,
    monto_recibido_cordobas DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    monto_recibido_usd DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    tipo_cambio DECIMAL(10,4) DEFAULT NULL,
    vuelto DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    observaciones TEXT,
    estado ENUM('completada', 'anulada') NOT NULL DEFAULT 'completada',
    fecha_anulacion TIMESTAMP NULL,
    id_usuario_anulacion INT NULL,
    motivo_anulacion VARCHAR(300) NULL,
    FOREIGN KEY (id_cliente) REFERENCES clientes(id),
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id),
    FOREIGN KEY (id_turno) REFERENCES turnos(id),
    FOREIGN KEY (id_usuario_anulacion) REFERENCES usuarios(id)
)";

$queries[] = "CREATE TABLE ventas_detalle (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_venta INT NOT NULL,
    id_producto INT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    iva DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (id_venta) REFERENCES ventas(id) ON DELETE CASCADE,
    FOREIGN KEY (id_producto) REFERENCES productos(id)
)";

// 5. Movimientos de caja, abonos, devoluciones
$queries[] = "CREATE TABLE movimientos_caja (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_turno INT NOT NULL,
    id_usuario INT NOT NULL,
    tipo ENUM('entrada', 'salida') NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    motivo VARCHAR(300) NOT NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_turno) REFERENCES turnos(id),
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id)
)";

$queries[] = "CREATE TABLE abonos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT NOT NULL,
    id_usuario INT NOT NULL,
    id_turno INT NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    metodo_pago ENUM('efectivo', 'transferencia', 'tarjeta') NOT NULL DEFAULT 'efectivo',
    referencia VARCHAR(100),
    observaciones VARCHAR(300),
    saldo_anterior DECIMAL(10,2) NOT NULL,
    saldo_nuevo DECIMAL(10,2) NOT NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_cliente) REFERENCES clientes(id),
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id),
    FOREIGN KEY (id_turno) REFERENCES turnos(id)
)";

$queries[] = "CREATE TABLE devoluciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_venta INT NOT NULL,
    id_usuario INT NOT NULL,
    id_turno INT NOT NULL,
    monto_total DECIMAL(10,2) NOT NULL,
    metodo_pago ENUM('efectivo', 'tarjeta', 'transferencia', 'ajuste_credito') NOT NULL,
    referencia VARCHAR(100),
    motivo VARCHAR(300) NOT NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_venta) REFERENCES ventas(id),
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id),
    FOREIGN KEY (id_turno) REFERENCES turnos(id)
)";

$queries[] = "CREATE TABLE devoluciones_detalle (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_devolucion INT NOT NULL,
    id_producto INT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (id_devolucion) REFERENCES devoluciones(id) ON DELETE CASCADE,
    FOREIGN KEY (id_producto) REFERENCES productos(id)
)";

// 6. Configuración
$queries[] = "CREATE TABLE configuracion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(50) NOT NULL UNIQUE,
    valor VARCHAR(255) NOT NULL,
    descripcion VARCHAR(255),
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

// Ejecutar todas las creaciones
foreach ($queries as $q) {
    if (!$conexion->query($q)) {
        enviarError("Error creando tablas: " . $conexion->error);
    }
}

// ==========================================
// INSERTAR DATOS POR DEFECTO
// ==========================================

// 1. Configuración básica
$conexion->query("INSERT INTO configuracion (clave, valor, descripcion) VALUES
('tipo_cambio_usd', '36.50', 'Tipo de cambio del dólar a córdobas'),
('iva_porcentaje', '15', 'Porcentaje de IVA aplicado a los productos'),
('nombre_negocio', 'AvilesPOS', 'Nombre del negocio'),
('moneda', 'C$', 'Símbolo de la moneda local'),
('empresa_nombre', 'Mi Negocio', 'Nombre comercial'),
('empresa_direccion', '', 'Dirección física'),
('empresa_telefono', '', 'Teléfono'),
('empresa_ruc', '', 'RUC'),
('empresa_email', '', 'Email'),
('empresa_logo', '', 'Logo')
");

// 2. Caja por defecto
$conexion->query("INSERT INTO cajas (nombre, activa) VALUES ('Caja Principal', 1)");

// 3. Roles
$conexion->query("INSERT INTO roles (clave, nombre, descripcion, protegido) VALUES
('administrador', 'Administrador', 'Dueño del negocio. Acceso total al sistema.', 1),
('gerente', 'Gerente', 'Responsable principal. Puede gestionar casi todo.', 1),
('supervisor', 'Supervisor', 'Solo consulta y reportes.', 1),
('cajero', 'Cajero', 'Encargado de las ventas.', 1)
");

// 4. Permisos
$conexion->query("INSERT INTO permisos (clave, grupo, nombre, descripcion) VALUES
('dashboard_ver', 'dashboard', 'Ver dashboard', 'Acceso al panel de inicio'),
('productos_ver', 'productos', 'Ver productos', 'Ver catálogo'),
('productos_crear', 'productos', 'Crear productos', 'Agregar productos'),
('productos_editar', 'productos', 'Editar productos', 'Modificar productos'),
('productos_eliminar', 'productos', 'Eliminar productos', 'Eliminar productos'),
('inventario_ver', 'inventario', 'Ver inventario', 'Ver movimientos'),
('inventario_registrar', 'inventario', 'Registrar movimiento', 'Entradas y salidas'),
('clientes_ver', 'clientes', 'Ver clientes', 'Ver catálogo de clientes'),
('clientes_crear', 'clientes', 'Crear clientes', 'Agregar clientes'),
('clientes_editar', 'clientes', 'Editar clientes', 'Modificar clientes'),
('clientes_eliminar', 'clientes', 'Eliminar clientes', 'Eliminar clientes'),
('ventas_vender', 'ventas', 'Vender', 'Acceso al punto de venta'),
('ventas_historial_propio', 'ventas', 'Ver historial propio', 'Ver sus ventas'),
('ventas_historial_completo', 'ventas', 'Ver historial completo', 'Ver todas las ventas'),
('ventas_anular', 'ventas', 'Anular ventas', 'Anular ventas'),
('ventas_devolucion', 'ventas', 'Registrar devoluciones', 'Devoluciones'),
('ventas_abono', 'ventas', 'Registrar abonos', 'Pagos de clientes'),
('ventas_movimientos_caja', 'ventas', 'Movimientos de caja', 'Entradas y salidas de efectivo'),
('turnos_abrir', 'turnos', 'Abrir/cerrar turno', 'Gestión de turnos'),
('reportes_ver', 'reportes', 'Ver reportes', 'Acceso a reportes'),
('config_empresa', 'configuracion', 'Configurar empresa', 'Datos del negocio'),
('config_cajas', 'configuracion', 'Configurar cajas', 'Gestión de cajas'),
('config_usuarios', 'configuracion', 'Configurar usuarios', 'Gestión de usuarios'),
('config_impuestos', 'configuracion', 'Configurar impuestos', 'Gestión de IVA'),
('config_bd', 'configuracion', 'Configurar base de datos', 'Respaldos y restauración')
");

// 5. Asignar permisos a roles
// Admin -> todos
$conexion->query("INSERT INTO roles_permisos (id_rol, id_permiso) SELECT r.id, p.id FROM roles r, permisos p WHERE r.clave = 'administrador'");
// Gerente -> todos menos usuarios y bd
$conexion->query("INSERT INTO roles_permisos (id_rol, id_permiso) SELECT r.id, p.id FROM roles r, permisos p WHERE r.clave = 'gerente' AND p.clave NOT IN ('config_usuarios', 'config_bd')");
// Supervisor
$conexion->query("INSERT INTO roles_permisos (id_rol, id_permiso) SELECT r.id, p.id FROM roles r, permisos p WHERE r.clave = 'supervisor' AND p.clave IN ('dashboard_ver', 'productos_ver', 'inventario_ver', 'clientes_ver', 'ventas_historial_completo', 'reportes_ver')");
// Cajero
$conexion->query("INSERT INTO roles_permisos (id_rol, id_permiso) SELECT r.id, p.id FROM roles r, permisos p WHERE r.clave = 'cajero' AND p.clave IN ('dashboard_ver', 'productos_ver', 'inventario_ver', 'clientes_ver', 'clientes_crear', 'clientes_editar', 'ventas_vender', 'ventas_historial_propio', 'ventas_abono', 'ventas_movimientos_caja', 'turnos_abrir')");

// 6. Crear Admin por defecto
$hash = password_hash('admin', PASSWORD_DEFAULT);
$stmt = $conexion->prepare("INSERT INTO usuarios (nombre, usuario, password, rol, activo) VALUES ('Administrador', 'admin', ?, 'administrador', 1)");
$stmt->bind_param("s", $hash);
$stmt->execute();
$stmt->close();

// Volver a habilitar FKs
$conexion->query("SET FOREIGN_KEY_CHECKS = 1");

if (ob_get_length()) ob_clean();
echo json_encode(['ok' => true]);
exit;