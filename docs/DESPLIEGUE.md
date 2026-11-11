# Guía de despliegue y operación

**Sistema de Bitácora de Mantenimiento de Equipos** · Servicio de Salud Aysén
Dirigida al Sysadmin que instala y opera el ambiente de producción. El entorno de desarrollo
(Laravel Sail) se describe en el [README](../README.md) y **no** debe usarse en producción.

El hosting final (servidor propio, máquina virtual, contenedores) queda a criterio del Sysadmin
([SAD §5.2](SAD.md)). Esta guía asume un servidor Linux con Nginx y PHP-FPM, y es equivalente en
cualquier otra infraestructura que cumpla los requisitos.

## 1. Requisitos

| Componente | Versión / detalle |
|---|---|
| PHP | **8.4.27 o superior**. Las versiones anteriores son vulnerables a PHP2Shell (CVE-2026-109618/109619). |
| Extensiones PHP | `pdo_pgsql`, `ctype`, `dom`, `fileinfo`, `filter`, `gd`, `hash`, `iconv`, `intl`, `json`, `libxml`, `mbstring`, `openssl`, `pcre`, `session`, `simplexml`, `tokenizer`, `xml`, `xmlreader`, `xmlwriter`, `zip`, `zlib` |
| Composer | 2.x |
| Base de datos | PostgreSQL 16 o superior |
| Servidor web | Nginx (o Apache) con HTTPS, y la raíz del sitio apuntando a `public/` |
| Node.js | **No se requiere**: el panel usa los assets que publica Filament |
| Cron / colas | **No se requieren**: el sistema no tiene tareas programadas ni trabajos en cola |

Para verificar los requisitos de PHP en el servidor, después de clonar:
`composer check-platform-reqs --no-dev`.

## 2. Variables de entorno

Crear el archivo `.env` en la raíz del proyecto, a partir de `.env.example`, con estos valores de
producción. Los demás pueden quedar como en el ejemplo.

```dotenv
APP_NAME="Bitácora de Mantenimiento de Equipos"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://<dominio-institucional>
APP_KEY=                       # se genera en el paso 3.4

APP_LOCALE=es
APP_FALLBACK_LOCALE=es

LOG_CHANNEL=daily
LOG_LEVEL=warning

DB_CONNECTION=pgsql
DB_HOST=<servidor-postgresql>
DB_PORT=5432
DB_DATABASE=bitacora_mantenimiento
DB_USERNAME=<usuario-dedicado>
DB_PASSWORD=<contraseña-segura>

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true

CACHE_STORE=database
QUEUE_CONNECTION=sync

# Correo saliente (recuperación de contraseña). Datos que entrega el Sysadmin.
MAIL_MAILER=smtp
MAIL_SCHEME=null                # null: STARTTLS si el servidor lo ofrece (puerto 587); smtps: TLS directo (465)
MAIL_HOST=<servidor-smtp>
MAIL_PORT=587
MAIL_USERNAME=<usuario-smtp>    # null si el servidor autoriza el envío por IP (relay)
MAIL_PASSWORD=<contraseña-smtp>
MAIL_FROM_ADDRESS=<no-responder@dominio-institucional>
MAIL_FROM_NAME="Bitácora de Mantenimiento de Equipos"

# Seguridad del inicio de sesión (opcional; estos son los valores por defecto)
# SEGURIDAD_BLOQUEO_CUENTA_INTENTOS=5   SEGURIDAD_BLOQUEO_CUENTA_VENTANA=15   SEGURIDAD_BLOQUEO_CUENTA_MINUTOS=15
# SEGURIDAD_BLOQUEO_IP_INTENTOS=20      SEGURIDAD_BLOQUEO_IP_VENTANA=15       SEGURIDAD_BLOQUEO_IP_MINUTOS=15
# SEGURIDAD_CONTRASENA_LARGO_MINIMO=12
```

- `APP_DEBUG=false` es obligatorio. Con `true`, un error muestra detalles internos y credenciales.
- `APP_ENV=production` hace que el seeder **no** cree los usuarios de desarrollo (sección 4).
- `CACHE_STORE=database` es necesario para el bloqueo por intentos fallidos: con un caché por
  proceso (`array`) los contadores no se compartirían entre solicitudes.
- `APP_URL` debe ser la dirección pública con HTTPS: con ella se arma el enlace del correo de
  recuperación de contraseña.
- Sin correo configurado, la recuperación de contraseña no llega a los usuarios (con
  `MAIL_MAILER=log` el correo queda solo en `storage/logs/`). El ingreso funciona igual.
- El archivo `.env` no se versiona. Sus permisos deben ser `600` y su dueño, el usuario del
  servicio web.

## 3. Instalación

```bash
# 3.1 Código
git clone https://github.com/J4t1x/mantenimiento-equipos.git /var/www/mantenimiento-equipos
cd /var/www/mantenimiento-equipos

# 3.2 Dependencias de producción. Publica además los assets de Filament
#     (post-autoload-dump → filament:upgrade).
composer install --no-dev --optimize-autoloader

# 3.3 Configuración (sección 2)
cp .env.example .env    # y editar los valores de producción

# 3.4 Clave de la aplicación (una sola vez; cambiarla invalida las sesiones)
php artisan key:generate

# 3.5 Estructura de la base de datos
php artisan migrate --force

# 3.6 Roles y permisos (en producción no crea usuarios)
php artisan db:seed --force

# 3.7 Primer administrador (sección 4)
php artisan app:crear-usuario --rol=super_admin

# 3.8 Optimización (sección 6)
php artisan optimize
php artisan filament:optimize
```

**Permisos de carpetas**: el usuario del servicio web (por ejemplo, `www-data`) debe poder escribir
en `storage/` y `bootstrap/cache/`.

```bash
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache
```

**Nginx (referencial)**:

```nginx
server {
    listen 443 ssl;
    server_name <dominio-institucional>;
    root /var/www/mantenimiento-equipos/public;
    index index.php;

    # ssl_certificate / ssl_certificate_key: certificado institucional

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

## 4. Usuarios

En producción el seeder solo crea los roles. Los usuarios de desarrollo (`admin@saludaysen.cl` y
`test@example.com`, contraseña `password`) solo se crean con `APP_ENV=local`.

**Primer administrador**:

```bash
php artisan app:crear-usuario --rol=super_admin
```

El comando pide el nombre, el correo y la contraseña (mínimo 12 caracteres, no se muestra al
escribirla). Si se ejecuta sin consola interactiva (`--no-interaction`) y sin `--password`, genera
una contraseña segura y la muestra **una sola vez**.

**Resto de los usuarios**: desde el panel (Administración → Usuarios) o con el mismo comando:

```bash
php artisan app:crear-usuario --nombre="Raúl Aravena Turra" --email=raul.aravena@saludaysen.cl \
    --rol="Encargado de Mantención" --recinto="Hospital Regional Coyhaique"
```

| Rol | Alcance |
|---|---|
| `super_admin` | Administración completa, incluidos usuarios y roles |
| `Encargado de Mantención` | Catálogos, catastro, planificación, bitácora, correctivos y reportes |
| `Técnico Interno` | Bitácora y correctivos de sus equipos asignados |
| `Encargado de Convenios` | Convenios, su ejecución, proveedores y reportes |
| `Jefatura` | Lectura de todo el sistema y reportes |

`--recinto` es repetible y acepta el nombre o el id del recinto. Un usuario sin recintos ve todos
los establecimientos; con recintos, solo los suyos.

**Desactivar un usuario**: en el panel, desmarcar "Activo" en su ficha. Un usuario inactivo no
puede iniciar sesión ni recibe enlaces de recuperación de contraseña.

**Autenticación**: el ingreso es con el correo y una contraseña propios del sistema. El Servicio
no cuenta con un directorio institucional (Active Directory o LDAP), por lo que esta es la
solución definitiva (pregunta abierta 12, cerrada el 10-11-2026). Controles:

| Control | Detalle |
|---|---|
| Contraseñas | Mínimo 12 caracteres (formulario de Usuarios, recuperación y `app:crear-usuario`). Se guardan con bcrypt. |
| Bloqueo por cuenta | 5 intentos fallidos en 15 minutos bloquean ese correo por 15 minutos, exista o no la cuenta. |
| Bloqueo por IP | 20 intentos fallidos en 15 minutos bloquean la dirección IP por 15 minutos. |
| Freno de ráfagas | Además, 5 intentos por minuto por IP (Filament). |
| Recuperación | Enlace por correo de un solo uso que vence en 60 minutos; la respuesta no revela si el correo existe. Recuperar la contraseña levanta el bloqueo de la cuenta. |
| Registro de accesos | Ingresos exitosos y fallidos, bloqueos, cierres de sesión, solicitudes de recuperación, recuperaciones y cambios de contraseña, con fecha, IP y navegador (tabla `eventos_autenticacion`, solo inserción). Se consulta en Administración → Registro de accesos. |

**Desbloquear antes de tiempo** (por ejemplo, si un usuario no tiene acceso a su correo): los
bloqueos viven en el caché, así que `php artisan cache:clear` levanta todos los bloqueos vigentes.
Lo recomendado es que el usuario recupere su contraseña.

**Detrás de un proxy o balanceador**: el bloqueo por IP y el registro de accesos usan la IP del
cliente. Si Nginx no recibe la conexión directamente, configurar los proxies de confianza
(`trustProxies` en `bootstrap/app.php`); si no, todos los usuarios aparecerían con la IP del proxy
y un bloqueo por IP los afectaría a todos.

## 5. Carga de datos

**Recintos**: se crean desde el panel (Catálogos → Recintos). El importador también los crea.

**Catastro y plan de MP desde la planilla Excel**:

```bash
# Hospital Regional Coyhaique (planilla incluida en el repositorio)
php artisan app:importar-catastro-coyhaique

# Otro establecimiento, con una planilla de la misma estructura
php artisan app:importar-catastro-coyhaique /ruta/planilla.xlsx --recinto="Hospital de Puerto Aysén"
```

El importador no vuelve a cargar un recinto que ya tiene equipos, salvo con `--force`.

**Sugerencia de tipos críticos de la norma**: `php artisan app:clasificar-tipos-criticos` solo
muestra las sugerencias. Con `--aplicar` las asigna. No cambia la criticidad de ningún equipo.

**Planes cargados con una versión anterior del importador**: `php artisan app:completar-meses-planes`
muestra los planes con menos de 12 meses. Con `--aplicar` agrega los meses faltantes como "Sin
programar", sin cambiar los meses ya registrados. Se puede ejecutar más de una vez sin efecto
adicional. También lista los planes cuyos meses programados no coinciden con la frecuencia, que
se corrigen a mano (alerta "Planes con meses inconsistentes" del Escritorio).

## 6. Optimización

En producción, después de instalar o actualizar:

```bash
php artisan optimize           # configuración, rutas, eventos y vistas en caché
php artisan filament:optimize  # componentes e íconos de Filament en caché
```

Con la configuración en caché, **un cambio en `.env` no se aplica hasta volver a ejecutar
`php artisan optimize`**. Para volver a leer todo desde los archivos:
`php artisan optimize:clear && php artisan filament:optimize-clear`.

## 7. Actualización

```bash
cd /var/www/mantenimiento-equipos
php artisan down                               # página de mantenimiento

git pull origin main
composer install --no-dev --optimize-autoloader
php artisan filament:upgrade                   # ver la advertencia de abajo
php artisan migrate --force
php artisan db:seed --class=RolesAndPermissionsSeeder --force   # si hay permisos nuevos
php artisan optimize
php artisan filament:optimize

php artisan up
```

> **Importante — `filament:upgrade`**: los assets JavaScript y CSS de Filament se publican en
> `public/js` y `public/css` y no se versionan. Si se actualizan las dependencias y esos archivos
> quedan en la versión anterior, **el inicio de sesión deja de funcionar en el navegador**, sin
> errores en el servidor. `composer install` los vuelve a publicar automáticamente; el comando
> explícito es un resguardo. Ocurrió en desarrollo el 28-10-2026, después de actualizar Filament
> de 5.7.8 a 5.11.

Antes de actualizar, hacer un respaldo (sección 8).

## 8. Respaldos y restauración

Toda la información del sistema está en la base de datos PostgreSQL. No se guardan archivos
subidos por los usuarios. Basta con respaldar la base de datos y el archivo `.env`: contiene
`APP_KEY`, y si se pierde, las sesiones y las cookies vigentes dejan de ser válidas.

**Respaldo diario (cron del servidor de base de datos)**:

```bash
pg_dump -U <usuario> -Fc -f /respaldos/bitacora-$(date +%F).dump bitacora_mantenimiento
find /respaldos -name 'bitacora-*.dump' -mtime +30 -delete    # retención: 30 días
```

**Restauración**:

```bash
createdb -U <usuario> bitacora_restaurada
pg_restore -U <usuario> -d bitacora_restaurada --no-owner /respaldos/bitacora-AAAA-MM-DD.dump
# Verificar y, si corresponde, apuntar DB_DATABASE a la base restaurada y ejecutar php artisan optimize
```

Se recomienda probar una restauración al menos una vez al trimestre. El procedimiento se verificó
en desarrollo el 02-11-2026: se restauraron los 469 equipos en una base aparte.

## 9. Verificación posterior a la instalación o actualización

1. `https://<dominio>/` redirige al inicio de sesión del panel.
2. Iniciar sesión con el administrador: el formulario acepta la contraseña y muestra el Escritorio.
   Esto confirma que los assets de Filament están al día (sección 7).
3. Un error de validación se muestra en español; por ejemplo, guardar un plan sin equipo debe
   mostrar "El campo Equipo es obligatorio.".
4. Reportes → "Descargar Excel" descarga el archivo.
5. Bitácora anual muestra los 12 meses.
6. Recuperación de contraseña: en el ingreso, **¿Ha olvidado su contraseña?** con el correo del
   administrador. Debe llegar el correo **Recuperación de contraseña** con un enlace a
   `https://<dominio>/admin/password-reset/reset…`. Esto confirma la configuración de correo.
7. Administración → **Registro de accesos** muestra el ingreso del paso 2 y la solicitud del
   paso 6 con la IP real del cliente (no la del proxy).
8. `storage/logs/` no registra errores nuevos.

## 10. Reversa

Si una actualización falla:

```bash
php artisan down
git checkout <commit-anterior>
composer install --no-dev --optimize-autoloader
php artisan filament:upgrade
# Si la actualización incluyó migraciones: restaurar el respaldo previo (sección 8)
php artisan optimize && php artisan filament:optimize
php artisan up
```

## 11. Checklist de seguridad

- [ ] PHP 8.4.27 o superior (`php -v`).
- [ ] `APP_ENV=production` y `APP_DEBUG=false`.
- [ ] HTTPS con el certificado institucional y `SESSION_SECURE_COOKIE=true`.
- [ ] La raíz del sitio apunta a `public/`; `.env`, `storage/` y `vendor/` no son accesibles por la
      web.
- [ ] No existen los usuarios `admin@saludaysen.cl` ni `test@example.com` con contraseña
      `password`.
- [ ] Usuario de base de datos dedicado, con permisos solo sobre la base del sistema.
- [ ] Respaldo diario programado y una restauración probada.
- [ ] `composer audit` sin vulnerabilidades al instalar y en cada actualización.
- [ ] Autenticación con correo y contraseña propios del sistema: es la solución definitiva, ya
      que el Servicio no cuenta con directorio institucional (pregunta abierta 12, cerrada el
      10-11-2026). Los controles están en la sección 4.
- [ ] Bloqueo por intentos fallidos verificado: 5 intentos con una contraseña incorrecta muestran
      el mensaje de bloqueo (`CACHE_STORE=database`).
- [ ] Correo saliente configurado y probado con la recuperación de contraseña (sección 9, paso 6),
      con remitente institucional autorizado (SPF/DKIM) para que no llegue como no deseado.
- [ ] El Registro de accesos muestra la IP real de los clientes (proxies de confianza, si hay).
- [ ] Retención del registro de accesos definida según AUDIT §4 (la aplicación no lo borra).

## Pendiente de definir con el Sysadmin

- Servidor y dominio de producción.
- Ubicación y retención de los respaldos.
- Correo saliente para la recuperación de contraseña (sección 2): servidor SMTP, puerto y cifrado,
  credenciales o autorización por IP, y remitente institucional. Las notificaciones del sistema
  siguen mostrándose solo en el panel.
- Retención del registro de accesos.
