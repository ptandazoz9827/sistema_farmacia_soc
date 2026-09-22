# FarmaGest — Sistema de Gestión de Farmacia con enfoque SOC

Sistema de gestión de farmacia en PHP/MySQL (basado en AdminLTE), reforzado con un
módulo de seguridad tipo **SOC (Security Operations Center)**: registro de eventos,
detección de fuerza bruta, gestión de incidentes y endurecimiento de sesiones.

## Índice

- [Módulo de negocio](#módulo-de-negocio)
- [Módulo SOC / Seguridad](#módulo-soc--seguridad)
- [Arquitectura de credenciales](#arquitectura-de-credenciales)
- [Esquema de base de datos](#esquema-de-base-de-datos)
- [Instalación](#instalación)
- [Estructura del proyecto](#estructura-del-proyecto)

## Módulo de negocio

- Proveedores/Marcas: alta, listado, edición y eliminación (`brand.php`, `add-brand.php`, `editbrand.php`)
- Categorías: alta, listado, edición y eliminación (`categories.php`, `add-category.php`, `editcategory.php`)
- Productos/Medicinas: alta, listado, edición, eliminación, imagen y fecha de vencimiento (`product.php`, `add-product.php`, `editproduct.php`)
- Pedidos/Facturación: alta, listado, edición, eliminación e impresión de facturas (`Order.php`, `add-order.php`, `editorder.php`, `invoiceprint.php`)
- Reportes: ventas, productos y productos vencidos por rango de fechas (`salesreport.php`, `productreport.php`, `expreport.php`)
- Usuarios: alta, edición, cambio de contraseña/usuario (`users.php`, `edituser.php`, `change-password.php`)

## Módulo SOC / Seguridad

El proyecto original (código heredado, conservado como referencia en los archivos `*.php.bak`)
usaba credenciales de base de datos embebidas en el código y contraseñas en MD5 sin control de
intentos de acceso. La capa `constant/security.php` reemplaza ese flujo con controles activos
de detección y respuesta:

### Registro de eventos (`security_events`)

Toda autenticación, expiración de sesión y cambio de estado de incidente queda auditado con
severidad (`INFO` / `WARNING` / `CRITICAL`), IP de origen, user-agent y marca de tiempo
(`security_log()` en `constant/security.php`). Visible en tiempo real en `security_dashboard.php`
(accesos exitosos/fallidos del día, eventos críticos, incidentes abiertos y las 15 últimas
entradas).

### Detección de fuerza bruta (`login_attempts`)

`login.php` registra cada intento de autenticación (`security_record_attempt()`). Si una misma
combinación email + IP acumula **5 intentos fallidos en una ventana de 15 minutos**
(`security_failed_count()`), el acceso se bloquea temporalmente
(`security_is_locked()`) y se abre automáticamente un incidente `BRUTE_FORCE_DETECTED`
(`security_open_bruteforce_incident()`), evitando duplicados mientras el incidente siga abierto.

### Gestión de incidentes (`security_incidents`)

`security_incidents.php` (acceso restringido al usuario administrador, `user_id = 1`) lista los
incidentes abiertos y permite cambiar su estado (`OPEN` → `INVESTIGATING` → `RESOLVED`) mediante
formulario protegido con token CSRF. Cada cambio de estado queda registrado como evento
`INCIDENT_STATUS_CHANGED`.

### Migración de contraseñas heredadas

`security_verify_password_and_migrate()` valida tanto los hashes MD5 heredados como los nuevos
hashes `password_hash()` (bcrypt), y re-hashea automáticamente a bcrypt en el primer login exitoso
sin exponer nunca la contraseña en texto plano.

### Endurecimiento de sesión (`constant/session.php`, `constant/check.php`)

- Cookies de sesión `HttpOnly`, `SameSite=Lax`, `Secure` cuando la conexión es HTTPS, y
  `session.use_strict_mode` activo.
- `session_regenerate_id(true)` en cada login exitoso (mitiga session fixation).
- Expiración de sesión por inactividad a los 15 minutos, con evento `SESSION_EXPIRED` y
  redirección forzada a login.

### Inventario técnico defensivo (`security_osint.php`)

Panel de referencia (solo lectura, no ejecuta escaneos activos) que documenta las versiones de
PHP/Apache/MySQL/dependencias front-end instaladas y enlaza a fuentes públicas (NVD) para
contrastar CVE conocidos — pensado como checklist de higiene de parches, no como herramienta
ofensiva.

### Cabeceras y superficie de ataque (`.htaccess`)

`display_errors` desactivado, listado de directorios deshabilitado (`Options -Indexes`) y bloqueo
de acceso web directo a extensiones sensibles (`.bak`, `.sql`, `.ini`, `.log`, `.sh`).

## Arquitectura de credenciales

La contraseña de base de datos **no vive en el repositorio**. `constant/connect.php` la carga en
tiempo de ejecución desde `/etc/farmagest/database.php` (fuera del `document root`, permisos
`640`, propietario `root:www-data`), generada durante la instalación con 24 bytes aleatorios
(`bin2hex(random_bytes(24))`). Los archivos `*.php.bak` conservados como referencia histórica
solo contienen credenciales de desarrollo local (`root` sin contraseña, `localhost`), nunca
credenciales de producción.

## Esquema de base de datos

- `farmagest-setup/00_business_schema.sql` — tablas de negocio (`users`, `brands`, `categories`,
  `product`, `orders`, `order_item`, `manage_website`, `tbl_email_config`, …)
- `farmagest-setup/01_security_module.sql` — tablas del módulo SOC: `login_attempts`,
  `security_events`, `security_incidents`

## Instalación

Requiere Ubuntu/Debian con Apache, MySQL/MariaDB y PHP con `mysqli`. El instalador
(`farmagest-setup/install.sh`, ejecutar con `sudo`):

1. Respalda la base de datos `farmacia` existente (si la hay) en `/var/backups/farmagest/`.
2. Crea la base de datos y aplica el esquema de negocio y el módulo de seguridad.
3. Genera la credencial de base de datos en `/etc/farmagest/database.php` y crea el usuario
   MySQL `farmagest` con esa contraseña.
4. Crea la cuenta administrativa inicial (`farmagest-setup/seed_admin.php`) con una contraseña
   aleatoria, guardada una única vez en `/etc/farmagest/acceso-inicial.txt` (permisos `600`).
5. Configura Apache (`farmagest-setup/configure_apache.py`) y ajusta permisos de archivos.

```bash
sudo bash /var/www/html/farmagest-setup/install.sh
```

## Estructura del proyecto

```
farmagest/
├── constant/           # Conexión BD, sesión, seguridad (SOC) y layout compartido
│   ├── connect.php     # Carga credenciales desde /etc/farmagest/database.php
│   ├── security.php    # Logging de eventos, fuerza bruta, incidentes
│   ├── session.php     # Cookies endurecidas
│   └── check.php       # Guard de autenticación + expiración de sesión
├── php_action/         # Controladores CRUD (productos, marcas, pedidos, usuarios…)
├── app/, custom/       # Vistas y assets propios de la aplicación
├── assets/             # AdminLTE, librerías JS/CSS de terceros
├── security_dashboard.php   # Panel SOC: eventos y contadores del día
├── security_incidents.php   # Gestión de incidentes abiertos/resueltos
├── security_events.php      # Listado de eventos de seguridad
├── security_osint.php       # Inventario técnico defensivo (versiones + NVD)
└── *.php.bak            # Versiones previas a la capa de seguridad, como referencia histórica
```

---

Basado en el proyecto original *Sistema de Gestión de Farmacia en PHP MySQL*
([configuroweb.com](https://www.configuroweb.com/sistema-de-gestion-de-farmacia/)), con la capa
SOC añadida sobre esa base.
