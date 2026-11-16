# Instalación en el ambiente de pruebas (QA) y revisión antes de la sesión

**Sistema de Bitácora de Mantenimiento de Equipos** · Servicio de Salud Aysén

Esta guía cubre el paso 2 de la puesta en marcha: la instalación del sistema en el ambiente de
pruebas, con apoyo del desarrollador al Sysadmin, y la revisión conjunta del sistema instalado
antes de presentarlo al requirente. La instalación sigue la [guía de despliegue](DESPLIEGUE.md);
aquí se indica solo lo propio de QA. La sesión con el requirente se guía con la
[pauta de la prueba de aceptación](PAUTA-PRUEBA-ACEPTACION.md).

---

## Contenido

1. [Responsables y secuencia](#1-responsables-y-secuencia)
2. [Solicitud al Sysadmin](#2-solicitud-al-sysadmin)
3. [Diferencias entre QA y producción](#3-diferencias-entre-qa-y-producción)
4. [Instalación](#4-instalación)
5. [Preparación de los datos de prueba](#5-preparación-de-los-datos-de-prueba)
6. [Problemas conocidos](#6-problemas-conocidos)
7. [Revisión conjunta antes de la sesión](#7-revisión-conjunta-antes-de-la-sesión)
8. [Volver a dejar QA en su estado inicial](#8-volver-a-dejar-qa-en-su-estado-inicial)
9. [Registro de la revisión](#9-registro-de-la-revisión)

---

## 1. Responsables y secuencia

| Paso | Responsable | Apoyo | Resultado |
|---|---|---|---|
| 1. Solicitud de la instalación (sección 2) | Encargado de la Sección Desarrollo de Sistemas de Información | Desarrollador | Servidor, dominio y base de datos de QA asignados. |
| 2. Instalación (sección 4) | Sysadmin | Desarrollador, en línea durante la instalación | Sistema accesible en `https://<dominio-qa>` y verificación de la sección 9 de `DESPLIEGUE.md` aprobada. |
| 3. Datos de prueba (sección 5) | Desarrollador | Sysadmin (acceso a la consola del servidor) | Catastro importado, cuentas de prueba y equipos de referencia. |
| 4. Revisión conjunta (sección 7) | Encargado de la Sección y desarrollador | | Decisión de presentar el sistema al requirente. |
| 5. Sesión de capacitación y prueba de aceptación | Desarrollador | Encargado de la Sección | Acta de la pauta. |

**Duración estimada**: instalación, 2 horas; datos de prueba, 30 minutos; revisión conjunta, 1 hora
y media. Conviene dejar al menos un día hábil entre la revisión y la sesión, para corregir lo que
aparezca.

---

## 2. Solicitud al Sysadmin

Datos que se piden al Sysadmin, o que él define, antes de instalar:

| Tema | Qué se necesita |
|---|---|
| Servidor | Linux con PHP 8.4.27 o superior y las extensiones de `DESPLIEGUE.md` §1, Composer 2 y Nginx (o Apache). Puede ser el mismo servidor que se usará en producción, con un sitio y una base aparte. |
| Dominio | Dirección de QA con HTTPS, por ejemplo `https://bitacora-qa.<dominio-institucional>`, accesible desde la red de los participantes de la sesión. |
| Base de datos | PostgreSQL 16 o superior, con una base y un usuario dedicados a QA (por ejemplo, `bitacora_mantenimiento_qa`), distintos de los de producción. |
| Código | Acceso de salida a GitHub para clonar `https://github.com/J4t1x/mantenimiento-equipos`, o el repositorio del GitLab del Servicio. |
| Correo saliente | Los datos SMTP de `DESPLIEGUE.md` §2. En QA es opcional: sin correo, los casos S-04 y S-05 de la pauta quedan **No aplica** y se prueban en producción. |
| Proxy | Si el sitio queda detrás de un proxy o balanceador, su dirección, para configurar los proxies de confianza (`DESPLIEGUE.md` §4). |
| Apoyo del desarrollador | Una sesión compartida (Zoom o similar) durante la instalación, y acceso de lectura a `storage/logs/` o, si lo permite la política del Servicio, a una consola del servidor para la sección 5. |
| Fecha | Fecha y hora de la instalación, y de la revisión conjunta. |

---

## 3. Diferencias entre QA y producción

QA se instala igual que producción ([DESPLIEGUE.md](DESPLIEGUE.md), secciones 1 a 3), con estas
diferencias:

| Tema | QA | Producción |
|---|---|---|
| `APP_ENV` | `staging` | `production` |
| `APP_URL` | `https://<dominio-qa>` | `https://<dominio-institucional>` |
| Base de datos | `bitacora_mantenimiento_qa` | `bitacora_mantenimiento` |
| Datos | Catastro del Hospital Regional Coyhaique y datos de prueba (`app:preparar-qa`). Lo registrado en QA **no** pasa a producción. | Datos reales entregados por el requirente. |
| Cuentas | Cuentas de prueba por perfil (`qa.*@prueba.invalid`) | Usuarios reales (`app:crear-usuario` o el panel) |
| Correo saliente | Opcional (`MAIL_MAILER=log` deja los correos en `storage/logs/`) | Obligatorio |
| Respaldos | No se requieren | Diarios (`DESPLIEGUE.md` §8) |
| Versión | El mismo commit que se instalará en producción | Igual |

Con `APP_ENV=staging` el sistema funciona igual que en producción: `APP_DEBUG=false`, sin usuarios
de desarrollo y con los mismos controles de seguridad. La única diferencia es que permite ejecutar
`app:preparar-qa`, que se niega a correr con `APP_ENV=production`.

---

## 4. Instalación

Seguir [DESPLIEGUE.md](DESPLIEGUE.md) §3, pasos 3.1 a 3.8, con los valores de la sección 3 de esta
guía. Anotar el commit instalado: es la versión que se presenta al requirente y la que figura en
el acta.

```bash
git -C /var/www/mantenimiento-equipos-qa rev-parse --short HEAD
```

En el paso 3.7, el primer administrador es una cuenta del Sysadmin o del Encargado de la Sección,
no una cuenta de prueba.

Al terminar, el Sysadmin y el desarrollador hacen juntos la verificación de `DESPLIEGUE.md` §9.
Los pasos 6 y 7 (correo y registro de accesos) se marcan como pendientes si QA no tiene correo
saliente.

---

## 5. Preparación de los datos de prueba

```bash
cd /var/www/mantenimiento-equipos-qa

# 5.1 Catastro y plan 2026 del Hospital Regional Coyhaique (planilla incluida en el repositorio)
php artisan app:importar-catastro-coyhaique

# 5.2 Cuentas de prueba, Recinto de prueba, proveedor y equipos de referencia
php artisan app:preparar-qa --correo-seguridad=<correo-con-bandeja-accesible>

# 5.3 Si se cambió algún valor de .env después del paso 3.8
php artisan optimize
```

`app:preparar-qa` deja lo que pide la sección 3 de la pauta:

- Una cuenta por perfil (`qa.admin`, `qa.mantencion`, `qa.tecnico`, `qa.convenios` y
  `qa.jefatura`, con el dominio `prueba.invalid`, que no recibe correos). El Encargado de
  Mantención y el Técnico Interno quedan asignados al Hospital Regional Coyhaique; los demás ven
  todos los recintos.
- Una **cuenta de seguridad** (perfil Jefatura) para los casos S-03 a S-05: bloqueo y recuperación
  de contraseña. Así no se bloquea la cuenta de un participante durante la sesión. Con
  `--correo-seguridad` se le asigna un correo con bandeja accesible durante la sesión; sin él,
  S-04 y S-05 quedan **No aplica**.
- El **Recinto de prueba**, con un equipo (`QA-0001`, Equipo D), y el **Proveedor de prueba (QA)**.
- El Técnico Interno de prueba como **Responsable interno** de dos planes: Equipo A (crítico sin
  garantía vigente) y Equipo B (con meses programados ya pasados). Elige además un Equipo C, sin
  responsable, de otro tipo de equipo.
- Imprime la tabla de cuentas, con sus contraseñas, y la de equipos de referencia. **Las
  contraseñas se muestran una sola vez**: se copian a la sección 3 de la pauta (sin las
  contraseñas) y se entregan a cada participante por un canal distinto del Zoom.

Se puede volver a ejecutar sin duplicar datos: reutiliza las cuentas y las asignaciones, y no
cambia las contraseñas salvo con `--nuevas-contrasenas` (por ejemplo, si se perdió la tabla).
Opciones: `--recinto` (por defecto, Hospital Regional Coyhaique), `--anio` (por defecto, el año en
curso) y `--dominio` (por defecto, `prueba.invalid`).

---

## 6. Problemas conocidos

| Síntoma | Causa | Solución |
|---|---|---|
| El formulario de ingreso no responde al presionar **Entrar**, sin errores en el servidor. | Assets de Filament desactualizados en `public/`. | `php artisan filament:upgrade` (`DESPLIEGUE.md` §7). |
| Un cambio en `.env` no tiene efecto. | Configuración en caché. | `php artisan optimize`. |
| Error 419 (**Página expirada**) al ingresar. | Cookie de sesión segura con el sitio en HTTP, o `APP_URL` distinta de la dirección usada. | Usar HTTPS y la dirección de `APP_URL`. |
| Error 500 en todas las páginas. | Sin permisos de escritura en `storage/` o `bootstrap/cache/`. | `DESPLIEGUE.md` §3, permisos de carpetas. Revisar `storage/logs/`. |
| Los mensajes de validación aparecen como `validation.required`. | `APP_LOCALE` distinto de `es`. | `APP_LOCALE=es` y `php artisan optimize`. |
| No llega el correo de recuperación. | Sin correo saliente, o `MAIL_MAILER=log`. | Revisar `storage/logs/`; en QA, S-04 y S-05 quedan **No aplica**. |
| Todos los participantes quedan bloqueados a la vez. | Sitio detrás de un proxy sin proxies de confianza: todos comparten la IP del proxy. | Configurar `trustProxies` (`DESPLIEGUE.md` §4). Para seguir la sesión: `php artisan cache:clear`. |
| `app:preparar-qa` indica que el recinto no tiene equipos. | Falta el paso 5.1. | `php artisan app:importar-catastro-coyhaique`. |

---

## 7. Revisión conjunta antes de la sesión

El Encargado de la Sección y el desarrollador revisan el sistema instalado en QA, en una sesión
compartida, antes de presentarlo al requirente. El desarrollador conduce y el Encargado de la
Sección valida cada punto.

### 7.1 Instalación

- [ ] La verificación de `DESPLIEGUE.md` §9 está aprobada (pasos 6 y 7 pendientes si QA no tiene
      correo).
- [ ] `APP_ENV=staging` y `APP_DEBUG=false`: una dirección inexistente, como
      `https://<dominio-qa>/admin/no-existe`, muestra una página de error sin detalles internos.
- [ ] El sitio responde con HTTPS y el certificado es válido en el navegador de los participantes.
- [ ] El commit instalado es el que se presentará y el que se instalará en producción.
- [ ] `composer audit` no informa vulnerabilidades.

### 7.2 Datos

- [ ] Con el administrador, **Equipos** muestra 470 equipos: los 469 del Hospital Regional
      Coyhaique y el `QA-0001` del Recinto de prueba. Con `qa.mantencion`, solo los 469.
- [ ] **Bitácora anual** muestra los planes 2026 con sus 12 meses.
- [ ] La alerta **Planes con meses inconsistentes** del Escritorio muestra 12 planes: los siete
      equipos de laboratorio sin meses y los cinco equipos cuyos meses no coinciden con la
      frecuencia (anexo de la solicitud al requirente del 10-11-2026). Es lo esperado mientras el requirente no responda las preguntas 4 a) y 4 b);
      conviene anticiparlo en la sesión.
- [ ] La tabla de equipos de referencia de `app:preparar-qa` está copiada en la sección 3 de la
      pauta.

### 7.3 Ensayo de la sesión

Con las cuentas de prueba, recorrer la pauta tal como se hará con el requirente. No es necesario
ejecutar los 35 casos: basta con uno o dos por perfil que muestren el flujo completo.

- [ ] Ingreso con cada una de las seis cuentas de prueba: cada una ve el menú de su perfil.
- [ ] S-03 con la cuenta de seguridad: el bloqueo aparece al quinto intento. Si hay correo, S-04
      completo.
- [ ] EM-01 y EM-12: alerta del Escritorio y Versión imprimible del informe de cumplimiento; el
      Encargado de Mantención no ve el equipo `QA-0001`.
- [ ] TI-01, TI-02 y TI-04: el Técnico Interno ve sus pendientes, registra un mes del Equipo B y
      no puede registrar en el Equipo C.
- [ ] EC-01 y EC-02: convenio con el Proveedor de prueba (QA) y una ejecución mensual.
- [ ] J-04: la Jefatura no ve botones para crear ni editar.
- [ ] A-04: el Registro de accesos muestra los ingresos y el bloqueo del ensayo.
- [ ] La Guía de usuario se abre desde el panel.

Lo que se registre en el ensayo modifica los datos de prueba. Antes de la sesión, volver a dejar
QA en su estado inicial (sección 8).

### 7.4 Material de la sesión

- [ ] Pauta de la prueba de aceptación con las secciones 3 (referencias) y 9 (versión del sistema)
      completas, compartida con el Encargado de la Sección para que registre los resultados.
- [ ] Contraseñas de las cuentas de prueba listas para entregar por un canal distinto del Zoom.
- [ ] Bandeja del correo de seguridad abierta, si se harán S-04 y S-05.
- [ ] Respuestas del requirente a la solicitud del 10-11-2026 revisadas: el caso TI-05 y la
      alerta de meses inconsistentes dependen de ellas.
- [ ] Enlace del Zoom enviado y participantes por perfil confirmados.

### 7.5 Decisión

- **Listo para presentar**: las secciones 7.1 a 7.4 están conformes.
- **Listo con observaciones**: hay puntos menores que no afectan el ensayo; se anotan en la
  sección 9 con su responsable.
- **No está listo**: algún punto de 7.1 o del ensayo falla. Se corrige y se repite la revisión de
  lo afectado.

---

## 8. Volver a dejar QA en su estado inicial

Después del ensayo, o entre dos sesiones de prueba, se borra lo registrado y se cargan de nuevo
los datos de prueba. **Solo en QA**: el primer comando borra todas las tablas.

```bash
cd /var/www/mantenimiento-equipos-qa
php artisan migrate:fresh --force
php artisan db:seed --force
php artisan app:crear-usuario --rol=super_admin    # administrador del Sysadmin o de la Sección
php artisan app:importar-catastro-coyhaique
php artisan app:preparar-qa --correo-seguridad=<correo-con-bandeja-accesible>
php artisan cache:clear                            # bloqueos de inicio de sesión vigentes
```

`app:preparar-qa` genera contraseñas nuevas, ya que las cuentas se vuelven a crear, y puede elegir
equipos de referencia distintos: actualizar la sección 3 de la pauta con la tabla nueva.

---

## 9. Registro de la revisión

**Fecha**: ______________  **Commit instalado en QA**: ______________

**Dirección de QA**: ______________________________

| Sección | Resultado (Conforme / Con observaciones / No conforme) |
|---|---|
| 7.1 Instalación | |
| 7.2 Datos | |
| 7.3 Ensayo de la sesión | |
| 7.4 Material de la sesión | |

**Decisión** (marcar una): [ ] Listo para presentar  [ ] Listo con observaciones  [ ] No está listo

| N° | Observación | Responsable | Fecha comprometida |
|---|---|---|---|
| 1 | | | |
| 2 | | | |
| 3 | | | |

| | Nombre | Firma |
|---|---|---|
| Sección Desarrollo de Sistemas de Información | | |
| Desarrollador | | |
