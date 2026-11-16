# Pauta de la prueba de aceptación

**Sistema de Bitácora de Mantenimiento de Equipos** · Servicio de Salud Aysén

Esta pauta guía la capacitación y la prueba de aceptación del sistema con el requirente, en una
sesión por Zoom sobre el ambiente de pruebas (QA). Se apoya en las prácticas guiadas de la
[Ruta de capacitación por perfil](MANUAL-USUARIO.md#ruta-de-capacitación-por-perfil) del manual de
usuario, y agrega los casos que comprueban las reglas del sistema.

---

## Contenido

1. [Objetivo y alcance](#1-objetivo-y-alcance)
2. [Participantes](#2-participantes)
3. [Preparación del ambiente de pruebas](#3-preparación-del-ambiente-de-pruebas)
4. [Desarrollo de la sesión](#4-desarrollo-de-la-sesión)
5. [Cómo registrar los resultados](#5-cómo-registrar-los-resultados)
6. [Casos de prueba](#6-casos-de-prueba)
7. [Criterio de aceptación](#7-criterio-de-aceptación)
8. [Registro de observaciones](#8-registro-de-observaciones)
9. [Acta de aceptación](#9-acta-de-aceptación)

---

## 1. Objetivo y alcance

**Objetivo**: que el requirente confirme que el sistema responde a lo solicitado, y que cada
perfil sepa hacer su trabajo diario en él.

**Alcance**: las funciones que usará cada perfil (Técnico Interno, Encargado de Mantención,
Encargado de Convenios, Jefatura y Administrador) y los controles del inicio de sesión. Cada caso
indica los requisitos del SRS que comprueba (columna **RF**).

**Datos**: la prueba se hace en el ambiente de pruebas, con el catastro y el plan 2026 del Hospital
Regional Coyhaique y cuentas de prueba. **Nada de lo que se registre en la sesión pasa a
producción**: producción parte con la carga de los datos reales.

**Fuera de alcance**: el rendimiento, los respaldos y la instalación, que se verifican antes de la
sesión según [INSTALACION-QA.md](INSTALACION-QA.md). Las reglas que el sistema ya comprueba con sus
pruebas automatizadas (por ejemplo, el bloqueo por dirección IP) no se repiten en la sesión.

---

## 2. Participantes

| Rol en la sesión | Persona | Tarea |
|---|---|---|
| Requirente | Jefe del Subdepartamento de Mantención y Operaciones | Ejecuta o valida los casos y firma el acta. |
| Usuarios por perfil | Una persona por perfil, idealmente | Ejecutan los casos de su perfil. |
| Desarrollador | | Capacita, guía la sesión y registra los resultados y observaciones. |
| Sección Desarrollo de Sistemas de Información | Encargado de la sección | Coordina la sesión y valida el acta. |

Si un perfil no tiene participante, el requirente ejecuta sus casos con la cuenta de prueba de
ese perfil.

---

## 3. Preparación del ambiente de pruebas

La instalación en QA, la preparación de los datos y la revisión conjunta del sistema instalado
(desarrollador y Sección de Desarrollo) se describen en
[INSTALACION-QA.md](INSTALACION-QA.md). La sesión con el requirente se hace solo con la decisión
**Listo para presentar** de esa revisión y con QA en su estado inicial (sección 8 de esa guía).

**Datos**: el comando `php artisan app:preparar-qa`, después de importar el catastro, deja listo:
- [ ] Catastro y plan 2026 del Hospital Regional Coyhaique importados
      (`php artisan app:importar-catastro-coyhaique`).
- [ ] El **Recinto de prueba**, con el equipo `QA-0001`, para comprobar que cada usuario ve solo su
      establecimiento (caso EM-12).
- [ ] El **Proveedor de prueba (QA)**, para los casos de convenios.
- [ ] Las cuentas de prueba y los equipos de referencia de las tablas siguientes. El Técnico
      Interno de prueba queda como **Responsable interno** de los Equipos A y B.

**Cuentas de prueba** (las crea `app:preparar-qa`; copiar los correos de su salida):

| Perfil | Correo | Recinto |
|---|---|---|
| Administrador (`super_admin`) | qa.admin@prueba.invalid | Todos |
| Encargado de Mantención | qa.mantencion@prueba.invalid | Hospital Regional Coyhaique |
| Técnico Interno | qa.tecnico@prueba.invalid | Hospital Regional Coyhaique |
| Encargado de Convenios | qa.convenios@prueba.invalid | Todos |
| Jefatura | qa.jefatura@prueba.invalid | Todos |
| Cuenta de seguridad (Jefatura), solo para S-03 a S-05 | | Todos |

- [ ] La cuenta de seguridad tiene un correo cuya bandeja se puede abrir durante la sesión
      (`--correo-seguridad`). Los casos de bloqueo se hacen con ella para no bloquear la cuenta de
      un participante.
- [ ] El correo saliente está configurado y probado (paso 6 de la verificación de
      `DESPLIEGUE.md` §9). Si aún no lo está, los casos S-04 y S-05 se marcan **No aplica** y
      quedan pendientes para producción.

**Equipos de referencia** (copiar la tabla que imprime `app:preparar-qa`, para no buscarlos durante
la sesión):

| Referencia | Uso | N° de inventario |
|---|---|---|
| Equipo A | Equipo crítico **sin garantía vigente**, con plan 2026 asignado al técnico | |
| Equipo B | Otro equipo con plan 2026 asignado al técnico, con meses programados ya pasados | |
| Equipo C | Equipo con plan 2026 **no** asignado al técnico | |
| Equipo D | Equipo del **Recinto de prueba** | QA-0001 |
| Servicio clínico en uso | Servicio del Equipo A (caso EM-11) | |

Las contraseñas de las cuentas de prueba se muestran una sola vez al ejecutar el comando. Se
entregan a cada participante por un canal distinto del Zoom (no se muestran en pantalla) y no se
copian en esta pauta.

**Alerta esperada**: mientras el requirente no responda las preguntas 4 a) y 4 b) de la solicitud
del 10-11-2026, el Escritorio muestra **Planes con meses inconsistentes** con 12 planes de la
planilla (7 sin meses y 5 con meses distintos a su frecuencia). No es un error del sistema: se explica al mostrar el Escritorio.

---

## 4. Desarrollo de la sesión

Duración estimada: **60 minutos**.

| Tiempo | Bloque | Contenido |
|---|---|---|
| 5 min | Presentación | Objetivo de la sesión, organización de la pantalla y cómo abrir la Guía de usuario. |
| 5 min | Ingreso y seguridad | Casos S-01 a S-05. |
| 15 min | Encargado de Mantención | Demostración y casos EM-01 a EM-12. |
| 10 min | Técnico Interno | Demostración y casos TI-01 a TI-05. |
| 8 min | Encargado de Convenios | Demostración y casos EC-01 a EC-05. |
| 5 min | Jefatura | Casos J-01 a J-04. |
| 5 min | Administrador | Casos A-01 a A-04. |
| 7 min | Cierre | Revisión de observaciones, resultado y acta. |

En cada bloque, el desarrollador muestra primero el flujo (capacitación) y luego el participante
ejecuta los casos en su propia sesión. Si el tiempo no alcanza, los casos pendientes se completan
en una segunda sesión antes de firmar el acta.

---

## 5. Cómo registrar los resultados

En la columna **Resultado** de cada caso:

| Resultado | Cuándo |
|---|---|
| **C** — Conforme | El sistema hace lo indicado en **Resultado esperado**. |
| **NC** — No conforme | El sistema no hace lo esperado. Se anota una observación (sección 8). |
| **NA** — No aplica | El caso no se pudo ejecutar por una causa ajena al sistema, por ejemplo, sin correo saliente en QA. Se anota la causa. |

Cada observación se clasifica:

| Tipo | Significado |
|---|---|
| **Bloqueante** | Impide el trabajo diario o produce datos incorrectos. Debe corregirse antes del paso a producción. |
| **No bloqueante** | Error que tiene una forma alternativa de hacerlo. Se corrige con una fecha comprometida. |
| **Mejora** | Cambio deseable, que no es un error frente a lo solicitado. Se evalúa como requerimiento nuevo. |

---

## 6. Casos de prueba

Los casos parten de las prácticas guiadas de la
[Ruta de capacitación por perfil](MANUAL-USUARIO.md#ruta-de-capacitación-por-perfil) (también en la
Guía de usuario del panel): cada práctica tiene al menos un caso, y los demás casos agregan las
reglas que el sistema debe hacer cumplir. Así, lo que el participante practica en la capacitación es
lo mismo que luego valida.

| Ruta del manual | Práctica guiada | Caso |
|---|---|---|
| Técnico Interno | Revisar las mantenciones **Pendientes** del mes | TI-01 |
| | Buscar un equipo en **Bitácora anual** y registrarlo como **Realizado**; comprobar **√** | TI-02 |
| | Registrar una falla y buscarla en la ficha del equipo | TI-05 |
| Encargado de Mantención | Abrir una alerta de **Requiere atención** | EM-01 |
| | Revisar la ficha de un equipo crítico | EM-03 |
| | Asignar un **Responsable interno** y observar la vista previa de los meses | EM-06 |
| | Revisar la ficha del recinto | EM-11 |
| | Abrir la **Versión imprimible** del informe de cumplimiento | EM-12 |
| Encargado de Convenios | Revisar **Vigencia** y **% de ejecución** | EC-01, EC-02 |
| | Registrar una ejecución mensual y ver el cambio del % | EC-02 |
| | Descargar el Excel de **Convenios y ejecución** | EC-05 |
| Jefatura | Cambiar el **Período** del Escritorio | J-01 |
| | Abrir la alerta **MP atrasada** | J-02 |
| | Guardar como PDF el informe de equipos críticos | J-03 |
| Administrador | Crear un usuario Técnico Interno | A-01 |
| | Asignarlo como **Responsable interno** | A-02 |
| | Desactivar su cuenta y comprobar que no ingresa | A-03 |
| | Encontrar el intento en **Registro de accesos** | A-04 |

Casos que agregan reglas del sistema, sin práctica en el manual: S-01 a S-05 (inicio de sesión),
EM-02, EM-04, EM-05, EM-07 a EM-10, TI-03, TI-04, EC-03, EC-04 y J-04.

### 6.1 Ingreso y seguridad (todos los perfiles)

| ID | Caso | Pasos | Resultado esperado | RF | Resultado |
|---|---|---|---|---|---|
| S-01 | Ingreso y salida | Ingresar con la cuenta de prueba de su perfil. Luego, círculo con las iniciales → **Salir**. | Muestra el Escritorio con las secciones de su perfil. Al salir vuelve a la pantalla de ingreso. | RF-34, RF-35 | |
| S-02 | Contraseña incorrecta | Ingresar con una contraseña equivocada. | Muestra un mensaje de credenciales incorrectas y no ingresa. | RF-87 | |
| S-03 | Bloqueo por intentos fallidos | Con la **cuenta de seguridad**, equivocar la contraseña 5 veces. Luego escribir la contraseña correcta. | Al quinto fallo aparece "Por seguridad, el acceso quedó bloqueado temporalmente…" con los minutos restantes. La contraseña correcta también se rechaza. | RF-87 | |
| S-04 | Recuperación de contraseña | **¿Ha olvidado su contraseña?** → correo de la cuenta de seguridad (bloqueada en S-03) → **Enviar email**. Abrir el correo **Recuperación de contraseña** → **Crear contraseña nueva**. Probar primero una de menos de 12 caracteres y luego una válida. Ingresar con ella. | Aparece **Revise su correo**. Llega el correo con el enlace. La contraseña corta se rechaza. Con la válida ingresa, aunque la cuenta estaba bloqueada. | RF-88 | |
| S-05 | Enlace de un solo uso y correo inexistente | Abrir otra vez el enlace del correo de S-04. Luego pedir recuperación con un correo que no exista en el sistema. | El enlace usado muestra que no es válido o ya venció. Con el correo inexistente aparece el mismo mensaje **Revise su correo** y no llega ningún correo. | RF-88 | |

### 6.2 Encargado de Mantención

Basado en la práctica de la ruta **Encargado de Mantención** del manual.

| ID | Caso | Pasos | Resultado esperado | RF | Resultado |
|---|---|---|---|---|---|
| EM-01 | Alertas del Escritorio | En **Requiere atención**, hacer clic en una alerta (por ejemplo, **MP atrasada**). | Se abre el listado con los casos de esa alerta, ya filtrado. | RF-43, RF-80 | |
| EM-02 | Indicadores por período | Cambiar el **Período** del Escritorio a un trimestre. | Los indicadores de cumplimiento (total, críticos, relevantes, IM ≥ 12) y los gráficos se recalculan para ese trimestre. | RF-21, RF-22, RF-67 | |
| EM-03 | Ficha de un equipo crítico | **Equipos** → buscar el Equipo A → **Ver**. | Muestra sus datos, la vida útil residual, el cumplimiento del año, sus planes, sus correctivos y sus retiros de uso. | RF-09, RF-26, RF-37 | |
| EM-04 | Crear un equipo | **Crear equipo** sin completar los campos obligatorios y guardar. Luego completar recinto, servicio clínico, nombre, n° de inventario y criticidad, y guardar. | Primero indica en rojo los campos obligatorios y no guarda. Luego crea el equipo y aparece en el listado. | RF-06, RF-08 | |
| EM-05 | Tipo de equipo crítico de la norma | Editar el equipo creado en EM-04: tipo **Máquina de diálisis** con criticidad **Relevante**, y guardar. | Rechaza con "Este tipo de equipo es crítico según la Res. Ex. 1341/2017 del MINSAL: su criticidad debe ser "Crítico"." | RF-75 | |
| EM-06 | Crear un plan y su vista previa | **Planificación MP** → **Crear plan de mantenimiento** para el equipo de EM-04, año 2026, frecuencia 4, tipo **Interno**, con el técnico de prueba como **Responsable interno**. Observar **Meses que se programarán** antes de crear. | La vista previa muestra 4 meses repartidos en el año. Al crear, el plan tiene sus 12 meses con esos 4 programados (**X**). | RF-13, RF-14, RF-16, RF-83 | |
| EM-07 | Mínimo de mantenciones en críticos | Editar el plan del Equipo A y cambiar la frecuencia a 1. | Rechaza con "Un equipo crítico debe tener mantenimiento preventivo al menos 2 veces al año, …". | RF-65, RF-69 | |
| EM-08 | Reprogramar exige causa | En la **Bitácora mensual** del plan del Equipo B, clic en un mes programado → **Reprogramado** sin observaciones → **Guardar**. Luego escribir la causa y un n° de documento, y guardar. | Primero rechaza con "Indica la causa de la reprogramación en observaciones (Res. Ex. 1341/2017).". Luego el mes queda **ꓣ** (Reprogramado). | RF-17, RF-66, RF-74 | |
| EM-09 | Correctivo y gasto | **Correctivo** → **Crear mantenimiento correctivo** para el Equipo A, con fecha de hoy y costo $100.000. Revisar el gasto correctivo del Escritorio. | El correctivo aparece en la ficha del equipo y el gasto correctivo del período aumenta en $100.000. | RF-24, RF-25, RF-26 | |
| EM-10 | Retiro de uso y reingreso | En la ficha del equipo de EM-04, **Retirar de uso** con fecha, motivo y n° de documento. Luego **Registrar reingreso**. | El equipo queda inactivo y luego vuelve a quedar activo. Ambos movimientos quedan en **Retiros de uso**. | RF-73 | |
| EM-11 | Ficha del recinto y catálogos | **Catálogos → Recintos → Ver** (Hospital Regional Coyhaique): **Editar responsable de MP**, y crear el **Programa anual de MP** y el **Gasto programado anual** 2026. Luego intentar eliminar el servicio clínico en uso. | Los datos quedan en la ficha y el Escritorio compara el gasto con lo programado. El servicio clínico no se elimina: el sistema indica que debe desactivarse. | RF-04, RF-70, RF-72, RF-76 | |
| EM-12 | Reportes y alcance por recinto | **Reportes**: elegir el primer semestre, **Descargar Excel** de **Catastro + plan anual** y abrir la **Versión imprimible** de **Cumplimiento de equipos críticos**. Luego buscar el Equipo D en **Equipos**. | El Excel tiene el formato de la planilla, con los 12 meses. La versión imprimible muestra el informe de la norma con encabezado y firmas. El Equipo D (otro recinto) no aparece. | RF-31, RF-63, RF-68, RF-77 | |

### 6.3 Técnico Interno

Basado en la práctica de la ruta **Técnico Interno** del manual.

| ID | Caso | Pasos | Resultado esperado | RF | Resultado |
|---|---|---|---|---|---|
| TI-01 | Mi trabajo del mes | Revisar **Mi trabajo del mes** en el Escritorio y hacer clic en **Pendientes**. | Muestra sus mantenciones pendientes, realizadas y reprogramadas del mes, y abre el listado de sus pendientes. | RF-45 | |
| TI-02 | Registrar una mantención realizada | **Bitácora anual** → buscar el Equipo B → clic en un mes programado ya pasado → **Realizado**, con la fecha real → **Guardar**. Abrir de nuevo el mes. | El mes queda **√** con la fecha, y el pie del mes suma la mantención realizada. | RF-17, RF-19, RF-79 | |
| TI-03 | Fecha futura y fecha obligatoria | En otro mes programado del Equipo A, elegir **Realizado** sin fecha y guardar. Luego indicar una fecha de mañana. | Primero exige la fecha real. Luego rechaza con "La fecha no puede ser posterior a hoy.". | RF-17 | |
| TI-04 | Equipo no asignado | En **Bitácora anual**, buscar el Equipo C. | Ve el plan, pero sus meses no se pueden abrir para registrar. | RF-20 | |
| TI-05 | Registrar una falla | **Correctivo** → **Crear mantenimiento correctivo** para el Equipo B. Luego buscarlo en la ficha del equipo. | El correctivo queda registrado y aparece en la ficha del equipo. | RF-24, RF-26 | |

> TI-05 depende de la pregunta 5 al requirente (quién registra los correctivos). Hoy el técnico
> puede registrar correctivos de cualquier equipo de su establecimiento. Si el requirente define
> que debe limitarse a sus equipos asignados, se agrega ese caso a la pauta.

### 6.4 Encargado de Convenios

Basado en la práctica de la ruta **Encargado de Convenios** del manual.

| ID | Caso | Pasos | Resultado esperado | RF | Resultado |
|---|---|---|---|---|---|
| EC-01 | Registrar un convenio | **Convenios** → **Crear convenio** con fecha de expiración anterior a la de resolución, y guardar. Corregir la fecha (vigente por 1 mes), monto anual $1.000.000, y guardar. | Primero rechaza la fecha de expiración. Luego crea el convenio y el listado muestra su **Vigencia** como *Vence en N días*. | RF-27, RF-86 | |
| EC-02 | Ejecución mensual | En la ficha del convenio, **Registrar ejecución mensual**: mes actual, n° de orden de compra y $400.000. | El registro aparece en la ficha y el **% de ejecución** del listado pasa a 40 %. | RF-28, RF-30 | |
| EC-03 | Sobregiro | Registrar otra ejecución de $700.000 en el mismo convenio. | Guarda el registro y muestra la notificación **Convenio sobregirado**, que queda en la campana. El % de ejecución aparece en rojo. | RF-29, RF-47 | |
| EC-04 | Alertas de convenios | Revisar **Requiere atención** en el Escritorio. | Aparecen **Convenios sobregirados** y **Convenios por vencer** con el convenio de prueba. | RF-43, RF-44 | |
| EC-05 | Reporte de convenios | **Reportes** → **Convenios y ejecución** → **Descargar Excel**. | El Excel incluye el convenio de prueba, su monto anual, la ejecución y el detalle por orden de compra. | RF-82 | |

### 6.5 Jefatura

Basado en la práctica de la ruta **Jefatura** del manual.

| ID | Caso | Pasos | Resultado esperado | RF | Resultado |
|---|---|---|---|---|---|
| J-01 | Indicadores por período | Cambiar el **Período** del Escritorio a un trimestre. | Los indicadores y gráficos se recalculan, y el texto bajo el título indica el período. | RF-57, RF-67 | |
| J-02 | MP atrasada | Clic en la alerta **MP atrasada**. | Se abre el listado de los equipos con meses programados vencidos. | RF-80 | |
| J-03 | Informe para la Dirección | **Reportes** → **Cumplimiento de equipos críticos** → **Versión imprimible** → **Imprimir** → **Guardar como PDF**. | Se genera el PDF del informe de la norma. | RF-68, RF-77 | |
| J-04 | Solo lectura | Abrir **Equipos**, **Planificación MP** y **Bitácora anual**. | Ve toda la información, pero no hay botones para crear o editar, y los meses de la bitácora no se pueden abrir para registrar. | RF-35 | |

### 6.6 Administrador

Basado en la práctica de la ruta **Administrador** del manual.

| ID | Caso | Pasos | Resultado esperado | RF | Resultado |
|---|---|---|---|---|---|
| A-01 | Crear un usuario | **Administración → Usuarios** → **Crear usuario** con perfil **Técnico Interno** y recinto Hospital Regional Coyhaique. Probar primero una contraseña de menos de 12 caracteres. | Rechaza la contraseña corta. Con una válida, crea el usuario. | RF-34, RF-63 | |
| A-02 | Asignar un equipo | Editar el plan del Equipo C y asignar el usuario de A-01 como **Responsable interno**. | El plan queda con el nuevo responsable. | RF-16, RF-20 | |
| A-03 | Desactivar una cuenta | Desmarcar **Activo** en el usuario de A-01 e intentar ingresar con él. | No puede ingresar. | RF-34 | |
| A-04 | Registro de accesos | **Administración → Registro de accesos**, filtrar por **Evento** y por la fecha de hoy. | Muestra los ingresos de la sesión, el intento de la cuenta desactivada, el bloqueo de S-03 y la recuperación de S-04, con fecha, correo, IP y detalle. No permite modificar ni eliminar registros. | RF-89 | |

---

## 7. Criterio de aceptación

El sistema se **acepta** cuando:
- todos los casos quedan **Conformes** o **No aplica** con causa ajena al sistema, y
- no hay observaciones **bloqueantes** abiertas.

El sistema se **acepta con observaciones** cuando solo quedan observaciones **no bloqueantes** o
**mejoras**: cada una se registra con su responsable y fecha comprometida, y el paso a producción
puede seguir.

El sistema **no se acepta** si queda alguna observación **bloqueante**. Después de corregirla, se
repiten los casos afectados.

Los casos **No aplica** por el correo saliente (S-04 y S-05) se repiten en producción, en la
verificación posterior a la instalación.

---

## 8. Registro de observaciones

| N° | Caso | Descripción | Tipo (Bloqueante / No bloqueante / Mejora) | Responsable | Fecha comprometida |
|---|---|---|---|---|---|
| 1 | | | | | |
| 2 | | | | | |
| 3 | | | | | |
| 4 | | | | | |
| 5 | | | | | |

---

## 9. Acta de aceptación

**Sistema**: Bitácora de Mantenimiento de Equipos
**Ambiente**: pruebas (QA), commit ______________ (el anotado en la revisión de `INSTALACION-QA.md`)
**Fecha de la sesión**: ______________

**Resumen de resultados**:

| Conformes | No conformes | No aplica | Total |
|---|---|---|---|
| | | | 35 |

**Resultado** (marcar uno):
- [ ] Aceptado
- [ ] Aceptado con observaciones (detalle en la sección 8)
- [ ] No aceptado

**Autorización para el paso a producción**: [ ] Sí  [ ] No

| | Nombre | Cargo | Firma |
|---|---|---|---|
| Requirente | | Jefe (S) Subdepartamento de Mantención y Operaciones | |
| Sección Desarrollo de Sistemas de Información | | | |
| Desarrollador | | | |
