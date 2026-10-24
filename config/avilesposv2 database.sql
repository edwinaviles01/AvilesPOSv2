-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 03-10-2026 a las 18:26:03
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `avilesposv2`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `abonos`
--

CREATE TABLE `abonos` (
  `id` int(11) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_turno` int(11) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `metodo_pago` enum('efectivo','transferencia','tarjeta') NOT NULL DEFAULT 'efectivo',
  `referencia` varchar(100) DEFAULT NULL,
  `observaciones` varchar(300) DEFAULT NULL,
  `saldo_anterior` decimal(10,2) NOT NULL,
  `saldo_nuevo` decimal(10,2) NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `abonos`
--

INSERT INTO `abonos` (`id`, `id_cliente`, `id_usuario`, `id_turno`, `monto`, `metodo_pago`, `referencia`, `observaciones`, `saldo_anterior`, `saldo_nuevo`, `fecha`) VALUES
(1, 4, 1, 2, 200.00, 'efectivo', NULL, 'dice que está pendiente de lo que le falta. PRUEBA!!', 540.00, 340.00, '2026-10-02 06:45:29'),
(2, 4, 1, 2, 140.00, 'efectivo', NULL, NULL, 340.00, 200.00, '2026-10-02 06:53:52'),
(3, 4, 1, 3, 50.00, 'efectivo', NULL, 'su hermano vino a abonar', 200.00, 150.00, '2026-10-03 05:59:16');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cajas`
--

CREATE TABLE `cajas` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cajas`
--

INSERT INTO `cajas` (`id`, `nombre`, `activa`, `fecha_creacion`) VALUES
(1, 'Caja 1', 1, '2026-09-30 15:39:03'),
(2, 'Caja 2', 0, '2026-09-30 15:39:03'),
(4, 'Caja 3', 0, '2026-09-30 16:52:59');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `id` int(11) NOT NULL,
  `id_departamento` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id`, `id_departamento`, `nombre`, `activo`, `fecha_creacion`) VALUES
(17, 7, 'Agua', 1, '2026-09-28 21:04:16'),
(18, 7, 'Gaseosa', 1, '2026-09-28 21:04:20'),
(20, 7, 'Cerveza', 1, '2026-09-30 16:17:01'),
(21, 11, 'Alimentos', 1, '2026-09-30 16:21:34'),
(23, 11, 'Higiene y Accesorios', 1, '2026-09-30 16:21:47'),
(24, 7, 'Energizante', 1, '2026-09-30 23:33:49'),
(28, 12, 'Sueros', 1, '2026-10-02 02:24:02'),
(30, 13, 'Cuidado de la Piel', 1, '2026-10-02 02:43:39'),
(31, 9, 'Leche', 1, '2026-10-02 02:44:37'),
(32, 7, 'Alcohol', 1, '2026-10-02 02:47:06'),
(33, 7, 'Vino', 1, '2026-10-02 02:47:10'),
(34, 8, 'Lavandería', 1, '2026-10-02 02:47:52'),
(35, 8, 'Superficies', 1, '2026-10-02 02:47:56'),
(36, 8, 'Utensilios y Desechables', 1, '2026-10-02 02:48:15'),
(37, 13, 'Cuidado del Cabello', 1, '2026-10-02 02:49:04'),
(38, 13, 'Higiene Oral', 1, '2026-10-02 02:49:14'),
(39, 13, 'Bebés', 1, '2026-10-02 02:49:28'),
(40, 13, 'Higiene Femenina', 1, '2026-10-02 02:49:48'),
(41, 9, 'Derivados', 1, '2026-10-02 02:50:15'),
(42, 9, 'Huevos', 1, '2026-10-02 02:50:28'),
(43, 10, 'Snacks', 1, '2026-10-02 02:52:34'),
(44, 10, 'Pastas', 1, '2026-10-02 02:52:59'),
(45, 10, 'Salsas', 1, '2026-10-02 02:53:02'),
(46, 10, 'Enlatados', 1, '2026-10-02 02:53:08'),
(47, 10, 'Granos Básicos', 1, '2026-10-02 02:53:50'),
(48, 10, 'Abarrotes', 1, '2026-10-02 02:54:36'),
(49, 14, 'Pescado', 1, '2026-10-02 02:55:44'),
(50, 14, 'Pollo', 1, '2026-10-02 02:55:48'),
(51, 14, 'Res', 1, '2026-10-02 02:55:52'),
(52, 14, 'Cerdo', 1, '2026-10-02 02:55:58'),
(53, 8, 'Sanitario', 1, '2026-10-02 03:06:44');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `id` int(11) NOT NULL,
  `nombre` varchar(200) NOT NULL,
  `cedula` varchar(30) DEFAULT NULL,
  `ruc` varchar(30) DEFAULT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `direccion` varchar(300) DEFAULT NULL,
  `tipo` enum('natural','juridica') NOT NULL DEFAULT 'natural',
  `permite_credito` tinyint(1) NOT NULL DEFAULT 0,
  `limite_credito` decimal(10,2) NOT NULL DEFAULT 0.00,
  `saldo_actual` decimal(10,2) NOT NULL DEFAULT 0.00,
  `notas` text DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`id`, `nombre`, `cedula`, `ruc`, `telefono`, `email`, `direccion`, `tipo`, `permite_credito`, `limite_credito`, `saldo_actual`, `notas`, `activo`, `fecha_creacion`) VALUES
(1, 'Eduardo Cisnero', NULL, NULL, NULL, NULL, 'San Isidro, Matagalpa', 'natural', 1, 3000.00, 0.00, 'Cliente suele solicitar crédito en cantidad alta, por eso el límite es C$3,000', 1, '2026-09-30 15:20:44'),
(2, 'Walmart - Supermercado Palí San Isidro, Matagalpa', NULL, NULL, NULL, NULL, 'San Isidro, Matagalpa', 'juridica', 0, 0.00, 0.00, NULL, 1, '2026-09-30 15:24:30'),
(4, 'Ocdin Torrez', NULL, NULL, NULL, NULL, 'San Isidro, Matagalpa', 'natural', 1, 1000.00, 150.00, NULL, 1, '2026-10-01 00:01:32'),
(5, 'Edwin Avilés', '444-221201-1000E', NULL, '8835-2792', 'edwinavilesroque@gmail.com', 'San Isidro, Matagalpa', 'natural', 0, 0.00, 0.00, NULL, 1, '2026-10-02 03:11:11');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuracion`
--

CREATE TABLE `configuracion` (
  `id` int(11) NOT NULL,
  `clave` varchar(50) NOT NULL,
  `valor` varchar(255) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `configuracion`
--

INSERT INTO `configuracion` (`id`, `clave`, `valor`, `descripcion`, `fecha_actualizacion`) VALUES
(1, 'tipo_cambio_usd', '36.50', 'Tipo de cambio del dólar a córdobas', '2026-09-30 17:21:36'),
(2, 'iva_porcentaje', '15', 'Porcentaje de IVA aplicado a los productos', '2026-09-30 17:21:36'),
(3, 'nombre_negocio', 'AvilesPOS', 'Nombre del negocio', '2026-09-30 17:21:36'),
(4, 'moneda', 'C$', 'Símbolo de la moneda local', '2026-09-30 17:21:36'),
(5, 'empresa_nombre', 'Super Selectos', 'Nombre comercial del negocio', '2026-10-03 05:21:36'),
(6, 'empresa_direccion', 'San Isidro, Matagalpa. Nicaragua', 'Dirección física', '2026-10-03 05:21:36'),
(7, 'empresa_telefono', '', 'Teléfono del negocio', '2026-10-03 05:21:36'),
(8, 'empresa_ruc', '', 'RUC del negocio', '2026-10-03 05:21:36'),
(9, 'empresa_email', 'superselectos.sv@nicaragua.com', 'Email del negocio', '2026-10-03 05:21:36'),
(18, 'empresa_logo', 'empresa_1791004896_36573236.jpeg', NULL, '2026-10-03 05:21:36');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `departamentos`
--

CREATE TABLE `departamentos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `departamentos`
--

INSERT INTO `departamentos` (`id`, `nombre`, `activo`, `fecha_creacion`) VALUES
(7, 'Bebidas', 1, '2026-09-28 21:03:23'),
(8, 'Cuidado del Hogar', 1, '2026-09-28 21:03:35'),
(9, 'Lacteos & Empaquetados', 1, '2026-09-28 21:03:40'),
(10, 'Despensa', 1, '2026-09-30 16:14:33'),
(11, 'Mascotas', 1, '2026-09-30 16:21:26'),
(12, 'Medicina', 1, '2026-10-02 02:23:56'),
(13, 'Higiene Personal', 1, '2026-10-02 02:43:10'),
(14, 'Carnicería', 1, '2026-10-02 02:55:20'),
(15, 'Panadería', 1, '2026-10-02 02:56:10');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `devoluciones`
--

CREATE TABLE `devoluciones` (
  `id` int(11) NOT NULL,
  `id_venta` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_turno` int(11) NOT NULL,
  `monto_total` decimal(10,2) NOT NULL,
  `metodo_pago` enum('efectivo','tarjeta','transferencia','ajuste_credito') NOT NULL,
  `referencia` varchar(100) DEFAULT NULL,
  `motivo` varchar(300) NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `devoluciones`
--

INSERT INTO `devoluciones` (`id`, `id_venta`, `id_usuario`, `id_turno`, `monto_total`, `metodo_pago`, `referencia`, `motivo`, `fecha`) VALUES
(1, 6, 1, 2, 44.00, 'efectivo', NULL, 'PRUEBA', '2026-10-02 08:04:36'),
(2, 14, 1, 3, 240.00, 'efectivo', NULL, 'No eran de 2L las gaseosas. Eran de 3L. PRUEBA', '2026-10-03 06:00:56');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `devoluciones_detalle`
--

CREATE TABLE `devoluciones_detalle` (
  `id` int(11) NOT NULL,
  `id_devolucion` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `devoluciones_detalle`
--

INSERT INTO `devoluciones_detalle` (`id`, `id_devolucion`, `id_producto`, `cantidad`, `precio_unitario`, `subtotal`) VALUES
(1, 1, 5, 2, 22.00, 44.00),
(2, 2, 16, 4, 60.00, 240.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `movimientos_caja`
--

CREATE TABLE `movimientos_caja` (
  `id` int(11) NOT NULL,
  `id_turno` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `tipo` enum('entrada','salida') NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `motivo` varchar(300) NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `movimientos_caja`
--

INSERT INTO `movimientos_caja` (`id`, `id_turno`, `id_usuario`, `tipo`, `monto`, `motivo`, `fecha`) VALUES
(1, 2, 1, 'salida', 500.00, 'pago de agua', '2026-10-02 06:32:13'),
(2, 2, 1, 'entrada', 200.00, 'Abono de cliente: Ocdin Torrez', '2026-10-02 06:45:29'),
(3, 2, 1, 'entrada', 140.00, 'Abono de cliente: Ocdin Torrez', '2026-10-02 06:53:52'),
(4, 2, 1, 'salida', 44.00, 'Devolución venta #6', '2026-10-02 08:04:36'),
(5, 3, 1, 'entrada', 500.00, 'para dar cambio. PRUEBA', '2026-10-03 05:58:43'),
(6, 3, 1, 'salida', 118.00, 'pago a cargotrans. PRUEBA', '2026-10-03 05:58:59'),
(7, 3, 1, 'entrada', 50.00, 'Abono de cliente: Ocdin Torrez', '2026-10-03 05:59:16'),
(8, 3, 1, 'salida', 240.00, 'Devolución venta #14', '2026-10-03 06:00:56');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `movimientos_inventario`
--

CREATE TABLE `movimientos_inventario` (
  `id` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `id_proveedor` int(11) DEFAULT NULL,
  `tipo` varchar(20) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `stock_anterior` int(11) NOT NULL,
  `stock_nuevo` int(11) NOT NULL,
  `precio_costo_anterior` decimal(10,2) DEFAULT NULL,
  `precio_costo_nuevo` decimal(10,2) DEFAULT NULL,
  `precio_venta_anterior` decimal(10,2) DEFAULT NULL,
  `precio_venta_nuevo` decimal(10,2) DEFAULT NULL,
  `comentario` varchar(500) DEFAULT NULL,
  `id_usuario` int(11) NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `movimientos_inventario`
--

INSERT INTO `movimientos_inventario` (`id`, `id_producto`, `id_proveedor`, `tipo`, `cantidad`, `stock_anterior`, `stock_nuevo`, `precio_costo_anterior`, `precio_costo_nuevo`, `precio_venta_anterior`, `precio_venta_nuevo`, `comentario`, `id_usuario`, `fecha`) VALUES
(1, 4, 4, 'entrada', 30, 50, 80, 20.00, NULL, 25.00, NULL, 'Ajuste de inventario fisico. PRUEBA!!', 1, '2026-09-29 04:28:58'),
(2, 3, NULL, 'salida', 20, 100, 80, 12.00, NULL, 15.00, NULL, 'No llegó este ultimo paquete de 20. PRUEBA!!', 1, '2026-09-29 04:29:45'),
(11, 9, NULL, 'salida', 10, 30, 20, 126.00, NULL, 166.00, NULL, 'Devolucion al proveedor. PRUEBA', 1, '2026-10-02 02:11:44');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permisos`
--

CREATE TABLE `permisos` (
  `id` int(11) NOT NULL,
  `clave` varchar(50) NOT NULL,
  `grupo` varchar(30) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `permisos`
--

INSERT INTO `permisos` (`id`, `clave`, `grupo`, `nombre`, `descripcion`) VALUES
(1, 'dashboard_ver', 'dashboard', 'Ver dashboard', 'Acceso al panel de inicio con KPIs'),
(2, 'productos_ver', 'productos', 'Ver productos', 'Puede ver el catálogo de productos'),
(3, 'productos_crear', 'productos', 'Crear productos', 'Puede agregar nuevos productos'),
(4, 'productos_editar', 'productos', 'Editar productos', 'Puede modificar productos existentes'),
(5, 'productos_eliminar', 'productos', 'Eliminar productos', 'Puede eliminar productos del catálogo'),
(6, 'inventario_ver', 'inventario', 'Ver inventario', 'Puede ver el historial de movimientos'),
(7, 'inventario_registrar', 'inventario', 'Registrar movimiento', 'Puede hacer entradas y salidas de inventario'),
(8, 'clientes_ver', 'clientes', 'Ver clientes', 'Puede ver el catálogo de clientes'),
(9, 'clientes_crear', 'clientes', 'Crear clientes', 'Puede agregar nuevos clientes'),
(10, 'clientes_editar', 'clientes', 'Editar clientes', 'Puede modificar clientes existentes'),
(11, 'clientes_eliminar', 'clientes', 'Eliminar clientes', 'Puede eliminar clientes'),
(12, 'ventas_vender', 'ventas', 'Vender', 'Acceso al punto de venta'),
(13, 'ventas_historial_propio', 'ventas', 'Ver historial propio', 'Ver solo sus propias ventas y turnos'),
(14, 'ventas_historial_completo', 'ventas', 'Ver historial completo', 'Ver ventas y turnos de todos los usuarios'),
(15, 'ventas_anular', 'ventas', 'Anular ventas', 'Puede anular ventas completadas'),
(16, 'ventas_devolucion', 'ventas', 'Registrar devoluciones', 'Puede procesar devoluciones de ventas'),
(17, 'ventas_abono', 'ventas', 'Registrar abonos', 'Puede registrar pagos de clientes con crédito'),
(18, 'ventas_movimientos_caja', 'ventas', 'Movimientos de caja', 'Puede hacer entradas y salidas de efectivo'),
(19, 'turnos_abrir', 'turnos', 'Abrir/cerrar turno', 'Puede abrir y cerrar su propio turno'),
(20, 'reportes_ver', 'reportes', 'Ver reportes', 'Acceso a la sección de reportes'),
(21, 'config_empresa', 'configuracion', 'Configurar empresa', 'Puede modificar los datos del negocio'),
(22, 'config_cajas', 'configuracion', 'Configurar cajas', 'Puede crear y editar cajas'),
(23, 'config_usuarios', 'configuracion', 'Configurar usuarios', 'Puede crear y editar usuarios del sistema'),
(24, 'config_impuestos', 'configuracion', 'Configurar impuestos', 'Puede modificar IVA y otros impuestos'),
(25, 'config_bd', 'configuracion', 'Configurar base de datos', 'Puede hacer respaldos y restauraciones');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id` int(11) NOT NULL,
  `codigo` varchar(50) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `id_departamento` int(11) DEFAULT NULL,
  `id_categoria` int(11) DEFAULT NULL,
  `id_proveedor` int(11) DEFAULT NULL,
  `precio_costo` decimal(10,2) NOT NULL DEFAULT 0.00,
  `precio` decimal(10,2) NOT NULL DEFAULT 0.00,
  `stock` int(11) NOT NULL DEFAULT 0,
  `stock_minimo` int(11) NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id`, `codigo`, `nombre`, `descripcion`, `foto`, `id_departamento`, `id_categoria`, `id_proveedor`, `precio_costo`, `precio`, `stock`, `stock_minimo`, `activo`, `fecha_creacion`) VALUES
(3, 'B001', 'Fuente Pura - 600ml', 'Botella de agua purificada', 'prod_1790656320_69b25d4b.png', 7, 17, 4, 18.00, 24.00, 72, 20, 1, '2026-09-29 03:23:35'),
(4, 'B002', 'Coca Cola - 355ml', 'Gaseosa', 'prod_1790656419_de907249.jfif', 7, 18, 4, 19.00, 25.00, 71, 10, 1, '2026-09-29 03:24:26'),
(5, 'D001', 'Takis Fuego - 60g', '', 'prod_1790784957_af9be652.png', 10, 43, 4, 16.00, 22.00, 33, 10, 1, '2026-09-30 16:15:44'),
(6, 'B003', 'Miller Lite - 355ml', '', 'prod_1790785118_4e086b99.png', 7, 20, 4, 40.00, 54.00, 77, 20, 1, '2026-09-30 16:18:06'),
(7, 'M001', 'Felix Granja - 1kg', 'Comida seca para gato adulto', 'prod_1790785364_770bb170.jpeg', 11, 21, 4, 115.00, 150.00, 28, 5, 1, '2026-09-30 16:22:44'),
(8, 'M002', 'Whiskas - 85g', 'Comida humeda para gato adulto', 'prod_1790785586_86f588e2.jfif', 11, 21, 4, 38.00, 50.00, 22, 10, 1, '2026-09-30 16:26:26'),
(9, 'M003', 'Gati Gatitos - 1kg', 'Comida seca para gato pequeño', 'prod_1790786312_e7c13895.webp', 11, 21, 4, 126.00, 166.00, 20, 5, 1, '2026-09-30 16:38:32'),
(10, 'B004', 'Dr Pepper - Lata 355ml', '', 'prod_1790810585_52a3dd5e.webp', 7, 18, 4, 40.00, 50.00, 20, 5, 1, '2026-09-30 23:23:05'),
(11, 'B005', 'Toña - Lata 350ml', '', 'prod_1790810797_8917356f.jfif', 7, 20, 4, 35.00, 45.00, 32, 10, 1, '2026-09-30 23:26:37'),
(12, 'B006', 'Toña Ultra - Lata 350ml', '', 'prod_1790810911_37d51b58.webp', 7, 20, 4, 32.00, 42.00, 44, 10, 1, '2026-09-30 23:28:31'),
(13, 'B007', 'Raptor - 600ml', '', 'prod_1790811276_2af6555e.webp', 7, 24, 4, 27.00, 35.00, 50, 10, 1, '2026-09-30 23:34:36'),
(14, 'B008', 'Raptor - 300ml', '', 'prod_1790811439_eba2d65b.webp', 7, 24, 4, 16.00, 20.00, 50, 10, 1, '2026-09-30 23:37:19'),
(15, 'B009', 'Coca Cola - 3L', '', 'prod_1790811581_1d62d96f.webp', 7, 18, 4, 62.00, 78.00, 44, 5, 1, '2026-09-30 23:39:36'),
(16, 'B010', 'Coca Cola - 2L', '', 'prod_1790811651_ae9d135e.jfif', 7, 18, 4, 48.00, 60.00, 50, 5, 1, '2026-09-30 23:40:44'),
(17, 'B011', 'Canada Dry Ginger Ale - 2L', '', 'prod_1790811786_dafb3f6b.webp', 7, 18, 4, 148.00, 185.00, 10, 3, 1, '2026-09-30 23:43:00'),
(18, 'L001', 'Leche Pinito - 350g', 'Leche en polvo \"Dos Pinos\"', 'prod_1790906852_dc913ef7.png', 9, 31, 4, 125.00, 161.00, 19, 5, 1, '2026-10-02 02:07:32'),
(19, 'M004', 'Gati Atún - 1kg', 'Comida seca para gato adulto', 'prod_1790907029_6c627b44.webp', 11, 21, 4, 95.00, 120.00, 19, 5, 1, '2026-10-02 02:10:29'),
(20, 'CH002', 'Bolsa Vikingo Mediana - 9und', 'Bolsas negras biodegradables', 'prod_1790907719_550fa9f1.jpg', 8, 36, 4, 17.00, 22.75, 29, 5, 1, '2026-10-02 02:21:59'),
(21, 'MD001', 'Electrolit Manzana - 625ml', 'Suero rehidratante', 'prod_1790907973_c3b31a8c.webp', 12, 28, 4, 72.00, 98.00, 98, 10, 1, '2026-10-02 02:25:39'),
(22, 'HP001', 'Nivea Men B&W -  Spray 150ml', 'Desodorante Nivea', 'prod_1790908156_e865e579.webp', 13, 30, 4, 80.00, 100.00, 19, 5, 1, '2026-10-02 02:29:16'),
(23, 'CH001', 'Azistín Desinfectante - 450ml', '', 'prod_1790909924_477730c0.webp', 8, 35, 4, 26.00, 33.25, 16, 5, 1, '2026-10-02 02:58:44'),
(24, 'L002', 'Huevos Barranca - 1/2 caj', '', 'prod_1790910281_101cf8bf.webp', 9, 42, NULL, 94.00, 116.50, 29, 5, 1, '2026-10-02 03:04:41'),
(25, 'CH003', 'Saniodor Pastilla - 60g', '', 'prod_1790910443_9f4d500e.webp', 8, 53, 4, 22.00, 29.00, 49, 10, 1, '2026-10-02 03:07:15');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto_precios`
--

CREATE TABLE `producto_precios` (
  `id` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedores`
--

CREATE TABLE `proveedores` (
  `id` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `contacto` varchar(150) DEFAULT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `proveedores`
--

INSERT INTO `proveedores` (`id`, `nombre`, `contacto`, `telefono`, `email`, `direccion`, `activo`, `fecha_creacion`) VALUES
(4, 'PriceSmart Managua', 'Gerente', '2772-0000', NULL, NULL, 1, '2026-09-28 21:06:36');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `clave` varchar(30) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(200) DEFAULT NULL,
  `protegido` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id`, `clave`, `nombre`, `descripcion`, `protegido`, `fecha_creacion`) VALUES
(1, 'administrador', 'Administrador', 'Dueño del negocio. Acceso total al sistema.', 1, '2026-10-03 06:16:06'),
(2, 'gerente', 'Gerente', 'Responsable principal del negocio. Puede gestionar casi todo excepto usuarios y BD.', 1, '2026-10-03 06:16:06'),
(3, 'supervisor', 'Supervisor', 'Solo consulta. Puede ver estadísticas y reportes pero no vender ni modificar.', 1, '2026-10-03 06:16:06'),
(4, 'cajero', 'Cajero', 'Encargado de las ventas. Solo puede vender, abrir turno, ver clientes y hacer movimientos de caja.', 1, '2026-10-03 06:16:06');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles_permisos`
--

CREATE TABLE `roles_permisos` (
  `id_rol` int(11) NOT NULL,
  `id_permiso` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `roles_permisos`
--

INSERT INTO `roles_permisos` (`id_rol`, `id_permiso`) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(1, 5),
(1, 6),
(1, 7),
(1, 8),
(1, 9),
(1, 10),
(1, 11),
(1, 12),
(1, 13),
(1, 14),
(1, 15),
(1, 16),
(1, 17),
(1, 18),
(1, 19),
(1, 20),
(1, 21),
(1, 22),
(1, 23),
(1, 24),
(1, 25),
(2, 1),
(2, 2),
(2, 3),
(2, 4),
(2, 5),
(2, 6),
(2, 7),
(2, 8),
(2, 9),
(2, 10),
(2, 11),
(2, 12),
(2, 13),
(2, 14),
(2, 15),
(2, 16),
(2, 17),
(2, 18),
(2, 19),
(2, 20),
(2, 21),
(2, 22),
(2, 24),
(3, 1),
(3, 2),
(3, 6),
(3, 8),
(3, 14),
(3, 20),
(4, 1),
(4, 2),
(4, 6),
(4, 8),
(4, 9),
(4, 10),
(4, 12),
(4, 13),
(4, 17),
(4, 18),
(4, 19);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `turnos`
--

CREATE TABLE `turnos` (
  `id` int(11) NOT NULL,
  `id_caja` int(11) NOT NULL,
  `id_usuario_apertura` int(11) NOT NULL,
  `id_usuario_cierre` int(11) DEFAULT NULL,
  `monto_inicial` decimal(10,2) NOT NULL DEFAULT 0.00,
  `compartido` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_apertura` timestamp NOT NULL DEFAULT current_timestamp(),
  `monto_final_contado` decimal(10,2) DEFAULT NULL,
  `monto_final_sistema` decimal(10,2) DEFAULT NULL,
  `diferencia` decimal(10,2) DEFAULT NULL,
  `total_ventas` decimal(10,2) NOT NULL DEFAULT 0.00,
  `numero_ventas` int(11) NOT NULL DEFAULT 0,
  `estado` enum('abierto','cerrado') NOT NULL DEFAULT 'abierto',
  `fecha_cierre` timestamp NULL DEFAULT NULL,
  `observaciones` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `turnos`
--

INSERT INTO `turnos` (`id`, `id_caja`, `id_usuario_apertura`, `id_usuario_cierre`, `monto_inicial`, `compartido`, `fecha_apertura`, `monto_final_contado`, `monto_final_sistema`, `diferencia`, `total_ventas`, `numero_ventas`, `estado`, `fecha_cierre`, `observaciones`) VALUES
(1, 1, 1, 1, 1000.00, 1, '2026-09-30 16:59:49', 1000.00, 1000.00, 0.00, 0.00, 0, 'cerrado', '2026-09-30 17:08:27', 'Prueba 1 de cierre'),
(2, 1, 1, 1, 500.00, 0, '2026-09-30 17:25:50', 1735.00, 1730.75, 4.25, 3557.25, 11, 'cerrado', '2026-10-03 05:45:00', 'PRUEBA DE CIERRE 1'),
(3, 1, 1, 1, 1000.00, 1, '2026-10-03 05:46:59', 3454.00, 3454.00, 0.00, 2262.00, 4, 'cerrado', '2026-10-03 06:02:21', 'tercer cierre de caja. PRUEBA');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` varchar(20) NOT NULL DEFAULT 'cajero',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `ultimo_acceso` timestamp NULL DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `usuario`, `password`, `rol`, `activo`, `ultimo_acceso`, `fecha_creacion`) VALUES
(1, 'Edwin UCN', 'edwin', '$2y$10$IP1QuQCHFSBf05G0PZdKsO85LF9832SawOWwZmMIzHvm3ECc4yF9y', 'administrador', 1, '2026-10-03 15:28:52', '2026-09-27 06:12:59'),
(2, 'Cristy UCN', 'cristy', '$2y$10$zcodLEApKgtSgN3enWc.bO7AMg1hCRirBQ5kVgGwfxOMjassq75zO', 'gerente', 1, '2026-10-03 07:18:26', '2026-10-03 06:34:33'),
(3, 'Hayder UCN', 'hayder', '$2y$10$h8XCuHC1Jmh6BrV62ywZTuEtzAwNgNCyzIzX5RP4W4aAlOSJSO30G', 'cajero', 1, '2026-10-03 07:16:17', '2026-10-03 06:35:47'),
(4, 'Ing. David UCN', 'david', '$2y$10$CvTo/FNmvfiBSzmyna28juCIu5nW/AcDE3GsSq.pklr6x1wBd2zSy', 'supervisor', 1, '2026-10-03 15:28:22', '2026-10-03 06:36:07'),
(6, 'Ing. Sadrac UCN', 'sadrac', '$2y$10$7oUOVrjhfyBaDZJYNNnczepYCHg4iu8221/CCJLLwyNAyrZGuRLK6', 'supervisor', 1, '2026-10-03 15:25:50', '2026-10-03 15:24:33');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios_permisos`
--

CREATE TABLE `usuarios_permisos` (
  `id_usuario` int(11) NOT NULL,
  `id_permiso` int(11) NOT NULL,
  `tipo` enum('extra','denegado') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios_permisos`
--

INSERT INTO `usuarios_permisos` (`id_usuario`, `id_permiso`, `tipo`) VALUES
(3, 2, 'denegado'),
(3, 6, 'denegado'),
(3, 9, 'denegado'),
(3, 10, 'denegado');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ventas`
--

CREATE TABLE `ventas` (
  `id` int(11) NOT NULL,
  `numero_factura` int(11) NOT NULL,
  `id_cliente` int(11) DEFAULT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_turno` int(11) NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  `subtotal` decimal(10,2) NOT NULL,
  `iva` decimal(10,2) NOT NULL,
  `descuento` decimal(10,2) NOT NULL DEFAULT 0.00,
  `descuento_porcentaje` decimal(5,2) DEFAULT NULL,
  `total` decimal(10,2) NOT NULL,
  `metodo_pago` varchar(20) NOT NULL,
  `referencia_pago` varchar(100) DEFAULT NULL,
  `monto_recibido_cordobas` decimal(10,2) NOT NULL DEFAULT 0.00,
  `monto_recibido_usd` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tipo_cambio` decimal(10,4) DEFAULT NULL,
  `vuelto` decimal(10,2) NOT NULL DEFAULT 0.00,
  `observaciones` text DEFAULT NULL,
  `estado` enum('completada','anulada') NOT NULL DEFAULT 'completada',
  `fecha_anulacion` timestamp NULL DEFAULT NULL,
  `id_usuario_anulacion` int(11) DEFAULT NULL,
  `motivo_anulacion` varchar(300) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ventas`
--

INSERT INTO `ventas` (`id`, `numero_factura`, `id_cliente`, `id_usuario`, `id_turno`, `fecha`, `subtotal`, `iva`, `descuento`, `descuento_porcentaje`, `total`, `metodo_pago`, `referencia_pago`, `monto_recibido_cordobas`, `monto_recibido_usd`, `tipo_cambio`, `vuelto`, `observaciones`, `estado`, `fecha_anulacion`, `id_usuario_anulacion`, `motivo_anulacion`) VALUES
(1, 1, NULL, 1, 2, '2026-09-30 22:10:23', 297.39, 44.61, 0.00, NULL, 342.00, 'efectivo', NULL, 400.00, 0.00, 0.0000, 58.00, NULL, 'completada', NULL, NULL, NULL),
(2, 2, NULL, 1, 2, '2026-09-30 22:14:35', 108.70, 16.30, 0.00, NULL, 125.00, 'efectivo', NULL, 200.00, 0.00, 0.0000, 75.00, NULL, 'completada', NULL, NULL, NULL),
(3, 3, NULL, 1, 2, '2026-09-30 22:31:43', 427.83, 64.17, 0.00, NULL, 492.00, 'efectivo', NULL, 500.00, 0.00, 0.0000, 8.00, NULL, 'completada', NULL, NULL, NULL),
(4, 4, NULL, 1, 2, '2026-09-30 22:32:52', 269.57, 40.43, 14.00, NULL, 310.00, 'efectivo', NULL, 400.00, 0.00, 0.0000, 90.00, NULL, 'completada', NULL, NULL, NULL),
(5, 5, NULL, 1, 2, '2026-09-30 22:35:45', 163.48, 24.52, 0.00, NULL, 188.00, 'tarjeta', '0123456789', 0.00, 0.00, 0.0000, 0.00, NULL, 'completada', NULL, NULL, NULL),
(6, 6, NULL, 1, 2, '2026-09-30 22:52:42', 57.39, 8.61, 0.00, NULL, 66.00, 'efectivo', NULL, 66.00, 0.00, 0.0000, 0.00, NULL, 'completada', NULL, NULL, NULL),
(7, 7, NULL, 1, 2, '2026-09-30 23:54:37', 210.43, 31.57, 10.00, NULL, 242.00, 'efectivo', NULL, 300.00, 0.00, 0.0000, 58.00, NULL, 'anulada', '2026-10-02 07:54:01', 1, 'PRUEBA'),
(8, 8, 4, 1, 2, '2026-10-01 00:02:18', 469.57, 70.43, 0.00, NULL, 540.00, 'credito', NULL, 0.00, 0.00, 0.0000, 0.00, NULL, 'completada', NULL, NULL, NULL),
(9, 9, 5, 1, 2, '2026-10-02 03:12:57', 798.70, 119.80, 60.00, NULL, 918.50, 'tarjeta', NULL, 0.00, 0.00, 0.0000, 0.00, NULL, 'completada', NULL, NULL, NULL),
(10, 10, NULL, 1, 2, '2026-10-02 03:32:27', 203.48, 30.52, 0.00, NULL, 234.00, 'efectivo', NULL, 500.00, 0.00, 0.0000, 266.00, NULL, 'anulada', '2026-10-02 07:09:50', 1, 'PRUEBA 1'),
(11, 11, NULL, 1, 2, '2026-10-03 05:21:55', 86.74, 13.01, 0.00, NULL, 99.75, 'efectivo', NULL, 100.00, 0.00, 0.0000, 0.25, NULL, 'completada', NULL, NULL, NULL),
(12, 12, NULL, 1, 3, '2026-10-03 05:47:39', 1017.39, 152.61, 0.00, NULL, 1170.00, 'efectivo', NULL, 1500.00, 0.00, 0.0000, 330.00, NULL, 'completada', NULL, NULL, NULL),
(13, 13, NULL, 1, 3, '2026-10-03 05:49:29', 469.57, 70.43, 0.00, NULL, 540.00, 'efectivo', NULL, 0.00, 20.00, 36.5000, 190.00, NULL, 'completada', NULL, NULL, NULL),
(14, 14, NULL, 1, 3, '2026-10-03 05:59:46', 208.70, 31.30, 0.00, NULL, 240.00, 'efectivo', NULL, 500.00, 0.00, 0.0000, 260.00, NULL, 'completada', NULL, NULL, NULL),
(15, 15, NULL, 1, 3, '2026-10-03 06:01:07', 271.30, 40.70, 0.00, NULL, 312.00, 'efectivo', NULL, 500.00, 0.00, 0.0000, 188.00, NULL, 'completada', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ventas_detalle`
--

CREATE TABLE `ventas_detalle` (
  `id` int(11) NOT NULL,
  `id_venta` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `iva` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ventas_detalle`
--

INSERT INTO `ventas_detalle` (`id`, `id_venta`, `id_producto`, `cantidad`, `precio_unitario`, `subtotal`, `iva`) VALUES
(1, 1, 3, 3, 24.00, 62.61, 9.39),
(2, 1, 6, 5, 54.00, 234.78, 35.22),
(3, 2, 4, 5, 25.00, 108.70, 16.30),
(4, 3, 7, 2, 150.00, 260.87, 39.13),
(5, 3, 8, 4, 48.00, 166.96, 25.04),
(6, 4, 6, 6, 54.00, 269.57, 40.43),
(7, 5, 4, 4, 25.00, 86.96, 13.04),
(8, 5, 5, 4, 22.00, 76.52, 11.48),
(9, 6, 5, 3, 22.00, 57.39, 8.61),
(10, 7, 12, 6, 42.00, 210.43, 31.57),
(11, 8, 11, 12, 45.00, 469.57, 70.43),
(12, 9, 8, 4, 50.00, 163.25, 24.49),
(13, 9, 18, 1, 161.00, 131.42, 19.71),
(14, 9, 19, 1, 120.00, 97.95, 14.69),
(15, 9, 20, 1, 22.75, 18.57, 2.79),
(16, 9, 21, 2, 98.00, 159.98, 24.00),
(17, 9, 22, 1, 100.00, 81.62, 12.24),
(18, 9, 23, 1, 33.25, 27.14, 4.07),
(19, 9, 24, 1, 116.50, 95.09, 14.26),
(20, 9, 25, 1, 29.00, 23.67, 3.55),
(21, 10, 15, 3, 78.00, 203.48, 30.52),
(22, 11, 23, 3, 33.25, 86.74, 13.01),
(23, 12, 6, 12, 54.00, 563.48, 84.52),
(24, 12, 11, 6, 45.00, 234.78, 35.22),
(25, 12, 12, 6, 42.00, 219.13, 32.87),
(26, 13, 3, 5, 24.00, 104.35, 15.65),
(27, 13, 5, 12, 22.00, 229.57, 34.43),
(28, 13, 15, 2, 78.00, 135.65, 20.35),
(29, 14, 16, 4, 60.00, 208.70, 31.30),
(30, 15, 15, 4, 78.00, 271.30, 40.70);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `abonos`
--
ALTER TABLE `abonos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_cliente` (`id_cliente`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `id_turno` (`id_turno`);

--
-- Indices de la tabla `cajas`
--
ALTER TABLE `cajas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_cat_por_depto` (`id_departamento`,`nombre`);

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `configuracion`
--
ALTER TABLE `configuracion`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `clave` (`clave`);

--
-- Indices de la tabla `departamentos`
--
ALTER TABLE `departamentos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `devoluciones`
--
ALTER TABLE `devoluciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_venta` (`id_venta`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `id_turno` (`id_turno`);

--
-- Indices de la tabla `devoluciones_detalle`
--
ALTER TABLE `devoluciones_detalle`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_devolucion` (`id_devolucion`),
  ADD KEY `id_producto` (`id_producto`);

--
-- Indices de la tabla `movimientos_caja`
--
ALTER TABLE `movimientos_caja`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_turno` (`id_turno`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `movimientos_inventario`
--
ALTER TABLE `movimientos_inventario`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_producto` (`id_producto`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `id_proveedor` (`id_proveedor`);

--
-- Indices de la tabla `permisos`
--
ALTER TABLE `permisos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `clave` (`clave`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `id_departamento` (`id_departamento`),
  ADD KEY `id_categoria` (`id_categoria`),
  ADD KEY `id_proveedor` (`id_proveedor`);

--
-- Indices de la tabla `producto_precios`
--
ALTER TABLE `producto_precios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_producto` (`id_producto`);

--
-- Indices de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `clave` (`clave`);

--
-- Indices de la tabla `roles_permisos`
--
ALTER TABLE `roles_permisos`
  ADD PRIMARY KEY (`id_rol`,`id_permiso`),
  ADD KEY `id_permiso` (`id_permiso`);

--
-- Indices de la tabla `turnos`
--
ALTER TABLE `turnos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_caja` (`id_caja`),
  ADD KEY `id_usuario_apertura` (`id_usuario_apertura`),
  ADD KEY `id_usuario_cierre` (`id_usuario_cierre`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario` (`usuario`);

--
-- Indices de la tabla `usuarios_permisos`
--
ALTER TABLE `usuarios_permisos`
  ADD PRIMARY KEY (`id_usuario`,`id_permiso`,`tipo`),
  ADD KEY `id_permiso` (`id_permiso`);

--
-- Indices de la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_factura` (`numero_factura`),
  ADD KEY `id_cliente` (`id_cliente`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `id_turno` (`id_turno`),
  ADD KEY `id_usuario_anulacion` (`id_usuario_anulacion`);

--
-- Indices de la tabla `ventas_detalle`
--
ALTER TABLE `ventas_detalle`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_venta` (`id_venta`),
  ADD KEY `id_producto` (`id_producto`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `abonos`
--
ALTER TABLE `abonos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `cajas`
--
ALTER TABLE `cajas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `configuracion`
--
ALTER TABLE `configuracion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT de la tabla `departamentos`
--
ALTER TABLE `departamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `devoluciones`
--
ALTER TABLE `devoluciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `devoluciones_detalle`
--
ALTER TABLE `devoluciones_detalle`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `movimientos_caja`
--
ALTER TABLE `movimientos_caja`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `movimientos_inventario`
--
ALTER TABLE `movimientos_inventario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `permisos`
--
ALTER TABLE `permisos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT de la tabla `producto_precios`
--
ALTER TABLE `producto_precios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `turnos`
--
ALTER TABLE `turnos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `ventas`
--
ALTER TABLE `ventas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `ventas_detalle`
--
ALTER TABLE `ventas_detalle`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `abonos`
--
ALTER TABLE `abonos`
  ADD CONSTRAINT `abonos_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id`),
  ADD CONSTRAINT `abonos_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `abonos_ibfk_3` FOREIGN KEY (`id_turno`) REFERENCES `turnos` (`id`);

--
-- Filtros para la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD CONSTRAINT `categorias_ibfk_1` FOREIGN KEY (`id_departamento`) REFERENCES `departamentos` (`id`);

--
-- Filtros para la tabla `devoluciones`
--
ALTER TABLE `devoluciones`
  ADD CONSTRAINT `devoluciones_ibfk_1` FOREIGN KEY (`id_venta`) REFERENCES `ventas` (`id`),
  ADD CONSTRAINT `devoluciones_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `devoluciones_ibfk_3` FOREIGN KEY (`id_turno`) REFERENCES `turnos` (`id`);

--
-- Filtros para la tabla `devoluciones_detalle`
--
ALTER TABLE `devoluciones_detalle`
  ADD CONSTRAINT `devoluciones_detalle_ibfk_1` FOREIGN KEY (`id_devolucion`) REFERENCES `devoluciones` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `devoluciones_detalle_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id`);

--
-- Filtros para la tabla `movimientos_caja`
--
ALTER TABLE `movimientos_caja`
  ADD CONSTRAINT `movimientos_caja_ibfk_1` FOREIGN KEY (`id_turno`) REFERENCES `turnos` (`id`),
  ADD CONSTRAINT `movimientos_caja_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `movimientos_inventario`
--
ALTER TABLE `movimientos_inventario`
  ADD CONSTRAINT `movimientos_inventario_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id`),
  ADD CONSTRAINT `movimientos_inventario_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `movimientos_inventario_ibfk_3` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedores` (`id`);

--
-- Filtros para la tabla `productos`
--
ALTER TABLE `productos`
  ADD CONSTRAINT `productos_ibfk_1` FOREIGN KEY (`id_departamento`) REFERENCES `departamentos` (`id`),
  ADD CONSTRAINT `productos_ibfk_2` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id`),
  ADD CONSTRAINT `productos_ibfk_3` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedores` (`id`);

--
-- Filtros para la tabla `producto_precios`
--
ALTER TABLE `producto_precios`
  ADD CONSTRAINT `producto_precios_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `roles_permisos`
--
ALTER TABLE `roles_permisos`
  ADD CONSTRAINT `roles_permisos_ibfk_1` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `roles_permisos_ibfk_2` FOREIGN KEY (`id_permiso`) REFERENCES `permisos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `turnos`
--
ALTER TABLE `turnos`
  ADD CONSTRAINT `turnos_ibfk_1` FOREIGN KEY (`id_caja`) REFERENCES `cajas` (`id`),
  ADD CONSTRAINT `turnos_ibfk_2` FOREIGN KEY (`id_usuario_apertura`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `turnos_ibfk_3` FOREIGN KEY (`id_usuario_cierre`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `usuarios_permisos`
--
ALTER TABLE `usuarios_permisos`
  ADD CONSTRAINT `usuarios_permisos_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `usuarios_permisos_ibfk_2` FOREIGN KEY (`id_permiso`) REFERENCES `permisos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD CONSTRAINT `ventas_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id`),
  ADD CONSTRAINT `ventas_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `ventas_ibfk_3` FOREIGN KEY (`id_turno`) REFERENCES `turnos` (`id`),
  ADD CONSTRAINT `ventas_ibfk_4` FOREIGN KEY (`id_usuario_anulacion`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `ventas_detalle`
--
ALTER TABLE `ventas_detalle`
  ADD CONSTRAINT `ventas_detalle_ibfk_1` FOREIGN KEY (`id_venta`) REFERENCES `ventas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ventas_detalle_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
