# AvilesPOS v2 - Sistema de Punto de Venta e Inventario

[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=flat-square&logo=php&logoColor=white)](https://www.php.net/)
[![Database](https://img.shields.io/badge/MySQL%20%2F%20MariaDB-10.4%2B-4479A1?style=flat-square&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![License](https://img.shields.io/badge/License-MIT-green.svg?style=flat-square)](LICENSE)

**AvilesPOS** es una solución web integral y ligera para la gestión de **Punto de Venta (POS)**, control de inventario, administración de caja por turnos, cuentas por cobrar y reportería analítica, diseñada especialmente para comercios minoristas, abarroterías y tiendas de conveniencia.

Desarrollado en **PHP nativo**, con un diseño modular, moderno y adaptable sin sobrecargas de dependencias externas.

---

## Características Principales

### Punto de Venta (POS)
* **Cobro ágil en mostrador**: Búsqueda inmediata de productos por nombre o lectura con lector de código de barras.
* **Múltiples métodos de pago**: Efectivo, Tarjeta, Transferencia y Crédito en una misma interfaz.
* **Cálculos automáticos**: Subtotal, aplicación de IVA/impuestos configurables, descuentos y cálculo de cambio en tiempo real.
* **Emisión de Tickets**: Generación de recibo térmico con formato estándar para impresoras POS.
* **Anulaciones y Devoluciones**: Reintegro automático de artículos al stock con registro de motivos.

### Seguridad y Control de Acceso (RBAC)
* Sistema de control de acceso basado en roles: **Administrador**, **Gerente**, **Supervisor** y **Cajero**.
* **Permisos granulares**: Capacidad de otorgar permisos extra o denegar permisos específicos usuario por usuario.
* Autenticación segura con hash de contraseñas mediante `password_hash()` y prevención de fijación de sesión con `session_regenerate_id()`.

### Dashboard Ejecutivo
* Indicadores clave (KPIs) en tiempo real:
  * Total de productos activos, agotados y con stock bajo (alerta de reposición).
  * Total facturado hoy y comparativa porcentual vs. día anterior.
  * Desglose de ingresos por método de pago y volumen de ventas por cajero.

### Control de Cajas y Turnos
* **Apertura de turno**: Registro de fondo inicial asignado a una caja física específica.
* **Arqueo y Cierre de caja**: Comparación automática entre el saldo esperado del sistema y el efectivo real recontado (detección de faltantes/sobrantes).
* **Movimientos de efectivo**: Registro de entradas y salidas manuales de caja con justificación (gastos menores, retiros de efectivo).

### Módulo de Reportes Analíticos
* Filtrado dinámico por rangos de fecha (`desde` y `hasta`).
* **Resumen**: Total de ventas, monto global recaudado, ticket promedio y gráfica/desglose diario.
* **Productos**: Ranking de los 100 artículos más vendidos, ingresos generados y cálculo de ganancia estimada (`margen = venta - costo`).
* **Usuarios**: Rendimiento y productividad por cajero o usuario activo.
* **Inventario**: Valorización total de la mercancía en almacén a precio de costo, a precio de venta y margen potencial.

### Gestión de Inventario y Productos
* Catálogo completo con carga de imágenes y códigos de barras.
* Clasificación jerárquica: Departamentos > Categorías > Proveedores.
* Precios escalonados y precio de costo para cálculo de márgenes reales.
* **Kardex / Movimientos**: Registro auditable de entradas por compra, mermas y ajustes manuales.

### Clientes y Ventas a Crédito
* Fichero de clientes con límite de crédito y control de saldo pendiente.
* Módulo de **Abonos** con historial de pagos, saldo anterior y nuevo saldo para cuentas por cobrar.

### Configuración y Mantenimiento de BD
* Datos fiscales de la empresa, RUC/NIT, teléfono y logotipo oficial para tickets.
* Herramientas de Base de Datos integradas:
  * **Descarga de Backup**: Exportación al vuelo de respaldos `.sql`.
  * **Restauración**: Importación guiada de archivos `.sql` con validación de sintaxis.
  * **Reinicio de Fábrica**: Limpieza y reinicialización del esquema para despliegues limpios.

---

## Stack Tecnológico

| Componente | Tecnología | Descripción |
| :--- | :--- | :--- |
| **Backend** | PHP 8.2+ | Procedural nativo y estructurado, extensiones `mysqli`, `session`, `json` |
| **Frontend** | HTML5, CSS3, JS Vanilla | Estilos CSS modulares, diseño responsivo, Fetch API nativa (sin frameworks pesados) |
| **Base de Datos** | MySQL / MariaDB 10.4+ | Motor InnoDB, codificación UTF-8 Multibyte (`utf8mb4_general_ci`) |
| **Servidor Recomendado** | Apache 2.4 | XAMPP, Laragon, LAMP o entorno Docker equivalente |

---

## Estructura del Proyecto

```text
AvilesPOSv2/
├── api/                        # Endpoints globales para categorías, departamentos, cajas
├── assets/
│   ├── css/                    # Hojas de estilo modulares (layout, POS, tablas, modales, etc.)
│   ├── img/                    # Logotipos y fotos de productos subidas
│   └── js/                     # Scripts nativos para interacción asíncrona y POS
├── config/
│   ├── avilesposv2 database.sql# Script de estructura y datos iniciales de la base de datos
│   └── db.php                  # Parámetros y conexión a la base de datos MySQL
├── includes/
│   ├── permisos.php            # Sistema de verificación de permisos y control RBAC
│   └── sidebar.php             # Menú de navegación dinámico según privilegios del usuario
├── modulos/
│   ├── clientes/               # CRUD de clientes y cuentas corrientes
│   ├── configuracion/          # Parámetros del sistema, empresa, cajas, usuarios y base de datos
│   ├── inventario/             # Kardex y registro de movimientos de stock
│   ├── productos/              # Catálogo, precios y administración de productos
│   ├── reportes/               # Análisis de ventas, rentabilidad y valorización de inventario
│   └── ventas/                 # Terminal POS, historial, devoluciones, abonos y control de turnos
├── dashboard.php               # Panel principal con KPIs e indicadores en tiempo real
├── index.php                   # Pantalla de inicio de sesión
├── validar_login.php           # Procesamiento y validación segura de credenciales
└── logout.php                  # Cierre y destrucción de sesión
```

---

## Instalación y Puesta en Marcha

### Prerrequisitos
* Tener instalado un entorno como [XAMPP](https://www.apachefriends.org/), Laragon o LAMP con **PHP 8.0 o superior** y **MariaDB / MySQL**.

### Pasos de Configuración

1. **Clonar o copiar el proyecto** dentro del directorio público del servidor web:
   ```bash
   # En XAMPP (Windows):
   cd C:\xampp\htdocs
   git clone https://github.com/edwinaviles01/AvilesPOSv2.git
   ```

2. **Iniciar los servicios**:
   * Abre el Panel de Control de XAMPP y arranca **Apache** y **MySQL**.

3. **Importar la Base de Datos**:
   * Abre tu gestor de base de datos favorito (por ejemplo, [phpMyAdmin](http://localhost/phpmyadmin/)).
   * Crea una nueva base de datos llamada `avilesposv2`:
     ```sql
     CREATE DATABASE avilesposv2 CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
     ```
   * Importa el archivo SQL ubicado en:
     `config/avilesposv2 database.sql`

4. **Configurar la conexión**:
   * Abre el archivo `config/db.php` y ajusta tus credenciales locales si es necesario:
     ```php
     $host = "localhost";
     $usuario = "root";
     $password = "";
     $base_datos = "avilesposv2";
     ```

5. **Acceder a la aplicación**:
   * Abre tu navegador e ingresa a:
     `http://localhost/AvilesPOSv2`
     *(Si tu Apache corre en un puerto alternativo como el 8080, usa `http://localhost:8080/AvilesPOSv2`)*.

---

## Credenciales por Defecto (Entorno de Pruebas)

Al importar la base de datos de ejemplo, cuentas con usuarios preconfigurados:

| Usuario | Contraseña | Rol | Acceso |
| :--- | :--- | :--- | :--- |
| `edwin` | *(definida en instalación)* | **Administrador** | Acceso total al sistema y configuración |
| `cristy`| *(definida en instalación)* | **Gerente** | Ventas, inventario, reportes y clientes |
| `hayder`| *(definida en instalación)* | **Cajero** | Ventas, turnos de caja y cobros |

---

## Autor y Contacto

Desarrollado por **Ing. Edwin Avilés**

* **WhatsApp:** [+505 8835-2792](https://wa.me/50588352792)
* **Instagram:** [@edwinaviles_](https://www.instagram.com/edwinaviles_/)
* **Podcast en Spotify:** [Canal de Spotify](https://open.spotify.com/show/0IWjrmnYxKFGitidAxHCFH)

---

## Licencia

Este proyecto está bajo la Licencia MIT. Consulta el archivo `LICENSE` para más detalles.
