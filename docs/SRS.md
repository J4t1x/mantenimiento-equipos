---
documento: SRS
proyecto: Sistema de Bitácora de Mantenimiento de Equipos
servicio: Servicio de Salud Aysén
establecimiento_base: Hospital Regional Coyhaique
perfil: estándar (expansión desde BRIEF §3, perfil lite)
version: 1.0
estado: borrador técnico — preparación de Etapa 2, no reemplaza la validación pendiente del BRIEF.md
marco: v3.4
autor: Javier Mansilla
para: Cristian Santander Marchant
fecha: 2026-08-31
---

# SRS — Especificación de Requisitos de Software

> Este documento expande y formaliza el `§3 Requisitos` del `BRIEF.md` (v1.0, entregado 31-08-2026).
> No introduce alcance nuevo: cada requisito aquí es una descomposición más granular de las 8
> historias de usuario ya presentadas. Se elabora como preparación técnica para la Etapa 2, en
> paralelo a la espera de validación del BRIEF por parte del requirente. Donde un requisito depende
> de una pregunta abierta sin resolver, queda marcado explícitamente — no se asume una respuesta.

## §1 Introducción

### 1.1 Propósito

Especificar de forma verificable qué debe hacer el sistema, para que sirva de contrato entre el
requirente (Subdepartamento de Mantención y Operaciones, vía Cristian Santander) y la
implementación en Laravel/Filament de la Etapa 2.

### 1.2 Alcance

Sistema de uso interno para el Servicio de Salud Aysén que gestiona catastro de equipos médicos,
planificación y ejecución de mantenimiento preventivo, mantenimiento correctivo, convenios de
mantenimiento y sus indicadores, en reemplazo de `PLANILLA MANTENIMIENTO EQUIPOS MÉDICOS 2026.xlsx`.
No incluye el paso a producción (Sysadmin) ni integración real con SIGFE (ver §3.4).

### 1.3 Definiciones y acrónimos

| Término | Significado |
|---|---|
| MP | Mantenimiento Preventivo |
| MC | Mantenimiento Correctivo |
| EQC / EQR | Equipo Crítico / Equipo Relevante (niveles de criticidad) |
| IM≥12 | Categoría de criticidad "Importancia mayor o igual a 12" (ver pregunta abierta 2 del BRIEF) |
| SIGFE | Sistema de Información para la Gestión Financiera del Estado |
| RF / RNF | Requisito Funcional / Requisito No Funcional |
| HU | Historia de Usuario (BRIEF §3) |
| RN | Regla de Negocio (BRIEF §2) |

### 1.4 Audiencia

Cristian Santander (validación), y el propio autor como base de la Etapa 2 (construcción).

### 1.5 Referencias

`BRIEF.md` v1.0 · `DESCRIPCION-SISTEMA.md` · `MODELO-DATOS.md` · `SAD.md` ·
`PLANILLA MANTENIMIENTO EQUIPOS MÉDICOS 2026.xlsx` · TRA §5 del marco de ingeniería v3.4.

## §2 Descripción general

### 2.1 Perspectiva del producto

Aplicación web nueva, independiente, que reemplaza un archivo Excel. No sustituye ni se integra con
otro sistema institucional existente, salvo la referencia (no integración) a códigos SIGFE.

### 2.2 Funciones principales

Catálogos administrables · Catastro de equipos · Planificación anual de MP · Bitácora de ejecución
mensual · Registro de mantenimiento correctivo · Convenios y seguimiento de gasto · Indicadores y
reportes · Gestión de usuarios y permisos.

### 2.3 Clases de usuario (ver BRIEF §2 para el detalle de actores)

| Rol | Resumen de acceso |
|---|---|
| Encargado de Mantención | Acceso total: catálogos, catastro, planificación, reportes |
| Responsable técnico de recinto | Catastro y plan de su recinto |
| Técnico interno / Proveedor externo | Registro de ejecución en Bitácora (alcance de acceso de proveedores sujeto a pregunta abierta 7) |
| Encargado de convenios | Convenios y Gasto |
| Jefatura de servicio clínico | Lectura de Equipos y Dashboard de su servicio |

### 2.4 Restricciones

Stack tecnológico oficial del TRA §5 (Laravel 13 · Livewire 4 · Filament 5 · PHP 8.4 · PostgreSQL
16+ · Tailwind CSS 4 · Docker/Sail), sin excepciones declaradas a la fecha.

### 2.5 Supuestos y dependencias

Los 12 supuestos abiertos listados en `BRIEF.md` (final del documento) condicionan el detalle de
varios requisitos de este SRS. Se referencian por número (PA-1 … PA-12) en los requisitos que
dependen de ellos.

## §3 Requisitos específicos

### 3.1 Requisitos funcionales

Agrupados por módulo, con prioridad (Alta/Media/Baja) según su cercanía al núcleo del proceso
(catastro + bitácora) frente a funciones de apoyo (reportes, convenios).

#### Módulo 1 — Catálogos (deriva de HU-01)

| ID | Descripción | Prioridad |
|---|---|---|
| RF-01 | El sistema debe permitir crear, editar y desactivar recintos/establecimientos. | Alta |
| RF-02 | El sistema debe permitir crear, editar y desactivar servicios clínicos, cada uno asociado a uno o más recintos. | Alta |
| RF-03 | El sistema debe permitir crear, editar y desactivar clases y subclases de equipo, con subclase dependiente de una clase padre. | Alta |
| RF-04 | El sistema debe impedir eliminar (solo desactivar) un valor de catálogo que esté referenciado por al menos un equipo. | Alta |
| RF-05 | El sistema debe impedir registrar un equipo con un recinto, servicio clínico o clase/subclase que no exista en el catálogo correspondiente (RN aplicada, ver HU-01 del BRIEF). | Alta |

#### Módulo 2 — Equipos / Catastro (deriva de HU-02)

| ID | Descripción | Prioridad |
|---|---|---|
| RF-06 | El sistema debe permitir registrar un equipo con: recinto, servicio clínico, clase/subclase, nombre, marca, modelo, serie, n° de inventario, año de adquisición, vida útil, propiedad, estado, criticidad, garantía (sí/no + año), y si está bajo plan de MP. | Alta |
| RF-07 | El n° de inventario debe ser único dentro de un mismo recinto. | Alta |
| RF-08 | Los campos servicio clínico, recinto, nombre, n° de inventario y criticidad son obligatorios; el sistema no debe permitir guardar el registro sin ellos. | Alta |
| RF-09 | El sistema debe calcular la vida útil residual (vida útil − (año actual − año de adquisición)) y mostrarla en la ficha, permitiendo valores negativos. *(Depende de PA-10: si el cálculo debe ser automático o editable manualmente.)* | Media |
| RF-10 | El sistema debe permitir editar la ficha completa de un equipo existente, conservando historial de cambios de estado y criticidad. | Media |
| RF-11 | El listado de equipos debe permitir filtrar por recinto, servicio clínico, criticidad y estado, y buscar por nombre o n° de inventario. | Alta |
| RF-12 | El campo "Estado (Bueno/Regular/Malo)" debe admitir explícitamente un valor "Sin evaluar" para los equipos migrados sin dato (59 de 469 en la planilla actual). *(Depende de PA-11.)* | Media |

#### Módulo 3 — Planificación MP (deriva de HU-03)

| ID | Descripción | Prioridad |
|---|---|---|
| RF-13 | Al asignar o modificar la frecuencia anual de un equipo (1, 2, 3, 4, 6 o 12), el sistema debe generar automáticamente las marcas "Programado" del año vigente, en cantidad exacta a la frecuencia (RN-01). | Alta |
| RF-14 | El sistema debe distribuir las marcas "Programado" de forma uniforme en el año (ej.: frecuencia 4 → trimestral), con el algoritmo exacto de distribución a confirmar con el requirente en Etapa 2. | Alta |
| RF-15 | El sistema debe permitir regenerar el plan de un equipo si su frecuencia cambia a mitad de año, preservando los meses ya ejecutados o reprogramados. | Media |
| RF-16 | El sistema debe registrar, junto al plan, el tipo de ejecución (interno/externo), el responsable o proveedor, el convenio asociado (si aplica) y el costo anual de referencia. | Alta |

#### Módulo 4 — Bitácora de ejecución (deriva de HU-04)

| ID | Descripción | Prioridad |
|---|---|---|
| RF-17 | El sistema debe permitir marcar un mes con plan "Programado" como "Realizado" o "Reprogramado", registrando fecha real y observaciones. | Alta |
| RF-18 | El sistema debe rechazar la transición a "Realizado" o "Reprogramado" si el mes no tiene una marca "Programado" previa (RN-02). | Alta |
| RF-19 | El sistema debe mostrar la bitácora anual de un equipo como una grilla de 12 meses con los tres estados visualmente diferenciados. | Alta |
| RF-20 | El sistema debe permitir a un técnico interno o proveedor externo registrar únicamente la ejecución de los equipos que tiene asignados. *(Depende de PA-7: acceso de proveedores externos.)* | Media |

#### Módulo 5 — Indicadores (deriva de HU-05)

| ID | Descripción | Prioridad |
|---|---|---|
| RF-21 | El sistema debe calcular el % de cumplimiento de MP como ejecutados/programados, para un período (mes o año) seleccionado (RN-03). | Alta |
| RF-22 | El sistema debe desagregar el % de cumplimiento en cuatro cortes: total, EQC, EQR e IM≥12. | Alta |
| RF-23 | El sistema debe mostrar el indicador en un panel (dashboard) accesible desde el módulo de Equipos y desde un panel general. | Media |

#### Módulo 6 — Mantenimiento correctivo (deriva de HU-06)

| ID | Descripción | Prioridad |
|---|---|---|
| RF-24 | El sistema debe permitir registrar un evento de mantenimiento correctivo asociado a un equipo: fecha, descripción de la falla, costo y tipo de gasto. *(Alcance exacto sujeto a PA-6.)* | Media |
| RF-25 | Cada evento correctivo registrado debe sumar automáticamente al gasto ejecutado de MC del período correspondiente (RN-04). | Media |
| RF-26 | El sistema debe mostrar el historial completo de eventos correctivos de un equipo, ordenado cronológicamente. | Media |

#### Módulo 7 — Convenios y gasto (deriva de HU-07)

| ID | Descripción | Prioridad |
|---|---|---|
| RF-27 | El sistema debe permitir registrar un convenio: nombre, proveedor, n° de resolución/orden de compra, fecha de resolución, fecha de expiración, monto anual y subasignación SIGFE (como referencia, ver §3.4). | Media |
| RF-28 | El sistema debe permitir registrar la ejecución mensual de un convenio: n° de orden de compra/factura y monto. | Media |
| RF-29 | El sistema debe alertar (sin bloquear) cuando la suma de los montos mensuales ejecutados de un convenio supere su monto anual (RN-05). | Media |
| RF-30 | El sistema debe mostrar, por convenio, el % de ejecución presupuestaria acumulada. | Baja |

#### Módulo 8 — Reportes (deriva de HU-08)

| ID | Descripción | Prioridad |
|---|---|---|
| RF-31 | El sistema debe exportar un reporte de catastro + plan de MP de un año dado, en formato equivalente al de la planilla vigente (mismas columnas y marcas mensuales). | Media |
| RF-32 | El sistema debe exportar el indicador de cumplimiento (§ RF-21/RF-22) en PDF o Excel, por período seleccionado. | Baja |
| RF-33 | El sistema debe exportar el detalle de gasto MP vs. MC, programado vs. ejecutado. | Baja |

#### Módulo 9 — Usuarios y permisos (no cubierto por una HU específica del BRIEF; se formaliza aquí)

| ID | Descripción | Prioridad |
|---|---|---|
| RF-34 | El sistema debe permitir crear usuarios y asignarles un rol de los definidos en §2.3. | Alta |
| RF-35 | El sistema debe restringir cada módulo según el permiso del rol conectado (formato `modulo.recurso.accion`, ver BRIEF §5). | Alta |
| RF-36 | El sistema debe registrar en un log de auditoría quién y cuándo modificó un registro de catastro, bitácora o convenio. | Media |

#### Módulo 10 — Experiencia de usuario y diseño (transversal; no cubierto por una HU específica del BRIEF, se formaliza aquí a partir de una revisión de diseño del 15-09-2026)

| ID | Descripción | Prioridad |
|---|---|---|
| RF-37 | El sistema debe organizar los campos de la ficha y el formulario de un equipo en secciones temáticas con jerarquía visual (ej. Identificación, Ubicación y clasificación, Ficha técnica, Estado y garantía, Plan de mantenimiento), en vez de una lista plana de ~20 campos sin agrupar. | Media |
| RF-38 | El sistema debe mostrar la bitácora de un equipo como una cuadrícula compacta de 12 meses con celdas coloreadas por estado (Programado/Realizado/Reprogramado/Sin programar) y una leyenda visible, en vez de la tabla vertical de 12 filas actual — esto es lo que RF-19 ya pedía ("grilla... con los tres estados visualmente diferenciados") y lo que el mockup validado (`MOCKUP-SISTEMA.html`, pantalla 5 "Bitácora") ya mostraba; hoy no se cumple en espíritu, solo en función. | Media |
| RF-39 | El sistema debe agrupar en secciones los campos de los formularios de Convenio, Planificación MP y Ejecución mensual de convenio (hoy listas planas de campos sin relación visual entre sí, mismo patrón que RF-37 mejora en Equipos). | Media |
| RF-40 | La página de Reportes debe integrar el botón de descarga de cada reporte junto a su propia descripción (con un ícono representativo por tipo de reporte), en vez de mantener las acciones de descarga en el encabezado de la página, separadas del texto explicativo de cada una en el cuerpo. | Media |
| RF-41 | Los listados de Equipos, Convenios y Planificación MP deben incorporar filtros visibles además de la búsqueda por texto: en Equipos, por recinto, servicio clínico, criticidad y estado (*ver nota*); en Convenios, por proveedor y vigencia; en Planificación MP, por año y tipo de mantenimiento. | Alta |
| RF-42 | El sistema debe habilitar la búsqueda global de Filament (barra superior) sobre Equipos (nombre, n° de inventario, serie) y Convenios (nombre), para saltar directo a un registro desde cualquier pantalla sin pasar por su listado. | Baja |

**Nota sobre RF-41**: al revisar `EquiposTable` para esta evaluación se detectó que RF-11 —marcado
✅ en `README.md` desde la entrega inicial— nunca tuvo los filtros que su propio texto pide
("filtrar por recinto, servicio clínico, criticidad y estado"); el listado solo tiene
`TrashedFilter` y búsqueda por texto en columnas individuales. RF-41 no es una mejora nueva sobre
RF-11, es su corrección: cierra una brecha real entre lo reportado y lo implementado, no solo una
mejora estética.

#### Módulo 11 — Analítica del Escritorio por rol (transversal; no cubierto por una HU específica del BRIEF, se formaliza aquí a partir de una revisión de analítica del 17-09-2026)

| ID | Descripción | Prioridad |
|---|---|---|
| RF-43 | El sistema debe mostrar en el Escritorio, para los roles Encargado de Mantención y Encargado de Convenios, un widget de alertas de convenios: cantidad de convenios sobregirados (RN-05) y cantidad de convenios cuya `fecha_expiracion` vence dentro de los próximos 60 días, reutilizando `CalcularEjecucionConvenioAction` — hoy el sobregiro (RN-05) solo es visible al entrar a la ficha o listado de un convenio en particular, no existe una vista agregada de "cuántos convenios necesitan atención" sin revisarlos uno a uno. | Alta |
| RF-44 | El sistema debe agregar un widget general de convenios al Escritorio del rol Encargado de Convenios (convenios activos, monto ejecutado del año vs. monto anual comprometido) — hoy este rol no ve ningún widget al entrar al panel: `canView()` de los dos widgets existentes exige permisos (`view.indicadores_generales_widget`, `view.cumplimiento_mp_widget`) que `RolesAndPermissionsSeeder` nunca le asigna, así que su Escritorio queda completamente vacío pese a ser la pantalla de aterrizaje del sistema. | Alta |
| RF-45 | El sistema debe mostrar al rol Técnico Interno, además del cumplimiento MP agregado que ya ve, un widget personalizado "Mis planes de este mes": ejecuciones mensuales del mes en curso de sus planes asignados (`PlanMantenimiento.responsable_interno_id` = usuario actual) agrupadas en pendientes/realizadas/reprogramadas, reutilizando el alcance de `AlcanceTecnicoInterno` — hoy el único widget que ve (`CumplimientoMpWidget`) resume el año completo de todos los equipos del sistema, no su propia carga de trabajo del mes. | Media |
| RF-46 | El sistema debe agregar al Escritorio de Encargado de Mantención y Jefatura tres indicadores de catastro hoy calculados implícitamente pero no expuestos fuera de la ficha de cada equipo: equipos en estado "Sin evaluar" (RF-12), equipos con garantía vigente que vence dentro del año en curso, y equipos con vida útil residual negativa (RF-09) — los tres son consultas directas sobre `Equipo`, sin nueva lógica de cálculo. | Media |

**Nota sobre alcance de Jefatura**: el BRIEF (§2 Actores) y este SRS (§2.3) describen a Jefatura
como lectura de "Equipos y Dashboard de **su servicio**" (clínico) — pero `User` no tiene columna
`servicio_clinico_id` ni relación equivalente, así que hoy no hay forma de acotar ningún widget al
servicio clínico del usuario conectado; en la práctica Jefatura ve las mismas cifras de todo el
sistema que Encargado de Mantención. Acotar el Escritorio al servicio clínico del usuario requiere
antes una decisión de modelo de datos (¿un usuario pertenece a un solo servicio clínico o a
varios?) que no está entre las 12 preguntas abiertas del BRIEF — queda fuera de RF-43 a RF-46 y se
documenta como dependencia a confirmar con el requirente, no como parte de esta mejora.

#### Módulo 12 — Consistencia funcional y productividad transversal (transversal; no cubierto por una HU específica del BRIEF, se formaliza aquí a partir de una auditoría de los 12 recursos Filament del panel, 17-09-2026)

Auditoría comparativa de los 12 recursos del panel (Equipos, Planificación MP, Bitácora,
Mantenimiento correctivo, Convenios, Ejecución de gasto de convenio, los 4 catálogos y Usuarios)
más la página Reportes y la configuración del panel (`AdminPanelProvider`). El criterio no fue
"qué widget nuevo se puede agregar a un módulo", sino qué patrón que ya existe en algunos recursos
falta en otros equivalentes — inconsistencias que el usuario nota al pasar de una pantalla a otra,
no carencias de una sola pantalla aislada.

| ID | Descripción | Prioridad |
|---|---|---|
| RF-47 | El sistema debe habilitar las notificaciones persistentes del panel (`->databaseNotifications()`) y enviar a la campana, además del toast actual, la alerta de sobregiro de convenio (RN-05, `NotificarSobregiroConvenioAction`) y los rechazos de borrado por integridad referencial (`AccionesEliminarProtegidas`) — hoy ambas son solo notificaciones flash: si el usuario no está mirando la pantalla exacta en el momento en que se disparan, la alerta se pierde sin dejar rastro. | Alta |
| RF-48 | Los listados de Bitácora (`EjecucionMensualResource`), Ejecución mensual de convenio (`ConvenioEjecucionMensualResource`) y Mantenimiento correctivo deben incorporar un filtro por año (o rango de fecha) y por su entidad padre (equipo o convenio); el listado de Usuarios debe incorporar un filtro por rol y por estado activo/inactivo — hoy `ConvenioEjecucionMensualResource` no tiene ningún filtro, `EjecucionMensualResource` solo filtra por estado, `MantenimientoCorrectivoResource` solo tiene `TrashedFilter`, y `UsersTable` no filtra por nada, pese a que estos 4 listados pueden crecer a cientos de filas sin más apoyo que la búsqueda por texto o el scroll. | Alta |
| RF-49 | El sistema debe unificar el punto de entrada para editar la Bitácora de un plan y la Ejecución mensual de un convenio, hoy duplicados en dos UI con reglas distintas cada uno: el grid de 12 meses de `PlanMantenimientos\RelationManagers\EjecucionesMensualesRelationManager` (sin crear/eliminar, protege RN-01) coexiste en la navegación con el recurso standalone `EjecucionMensualResource` (CRUD completo, sin esa protección); análogamente, el RelationManager tabular de `Convenios` coexiste con `ConvenioEjecucionMensualResource`. El sistema debe ocultar de la navegación el recurso que no sea el punto de entrada canónico, o alinear ambos a las mismas reglas, para que no sea posible romper la invariante de 12 meses por plan (o crear ejecuciones de convenio huérfanas) entrando por la puerta equivocada. | Alta |
| RF-50 | El sistema debe proteger el borrado de un Plan de mantenimiento con el mismo criterio que Equipo/Convenio/Mantenimiento correctivo (soft-delete, con su `TrashedFilter` y acciones de restaurar/eliminar definitivo) o, como mínimo, con una confirmación que informe cuántas ejecuciones mensuales se perderán — hoy `PlanMantenimientoResource` no tiene soft-delete ni aviso alguno, a diferencia de sus tres pares. | Media |
| RF-51 | El sistema debe agregar `ViewAction` (con su Infolist) a Plan de mantenimiento y Mantenimiento correctivo, para poder consultar un registro sin entrar a modo edición — hoy son los únicos dos recursos con formulario de varias secciones/campos que no ofrecen esa opción, a diferencia de Equipo, Convenio y Usuario. | Media |
| RF-52 | El sistema debe extender la búsqueda global (RF-42) a Proveedor (nombre, rut), y a Plan de mantenimiento y Mantenimiento correctivo a través del nombre del equipo asociado — hoy solo Equipo y Convenio son alcanzables desde la barra de búsqueda superior. | Baja |
| RF-53 | Los listados de Equipos, Convenios y Mantenimientos correctivos deben tener su propia acción de exportación a Excel sobre el resultado filtrado/buscado en pantalla, además de (no en reemplazo de) los tres reportes anuales fijos de la página Reportes (RF-31 a RF-33) — hoy la única forma de exportar cualquier dato es entrar a Reportes y descargar uno de esos tres archivos completos por año. | Media |
| RF-54 | Los tres reportes de la página Reportes (RF-31 a RF-33) deben aceptar, además del año, un filtro opcional por recinto y/o servicio clínico — hoy solo aceptan año, así que un usuario que solo necesita el catastro de su propio recinto o servicio (p. ej. Jefatura) debe descargar y filtrar manualmente el archivo completo del Servicio de Salud Aysén. | Media |
| RF-55 | El sistema debe mostrar columnas de auditoría (creado/actualizado) de forma uniforme en los 4 catálogos (hoy ausentes en Proveedores y Clases de equipo, presentes en Recintos y Servicios clínicos) y agregar un filtro para distinguir clases padre de subclases en el listado de Clases de equipo — hoy esa distinción solo se ve en una columna de texto ("— (es una clase)"), sin filtro que la aproveche. | Baja |
| RF-56 | El campo contraseña del formulario de Usuario debe ser opcional al editar (se mantiene la contraseña actual si se deja vacío) y obligatorio solo al crear — hoy `UserForm` lo exige en cada guardado, así que activar/desactivar un usuario o cambiarle el rol obliga a definirle una contraseña nueva de paso. | Media |

**Nota sobre alcance**: se descartó proponer RF sobre brechas que dependen de una decisión ya
identificada como pendiente en otro lado del proyecto — p. ej. `tipo_gasto` de Mantenimiento
correctivo sigue siendo texto libre sin catálogo (pregunta abierta 6 del BRIEF, ya documentada en
`MantenimientoCorrectivoForm`) y el acceso de Encargado de Convenios a los catálogos de
recinto/servicio clínico/clase de equipo quedó deliberadamente fuera de `RolesAndPermissionsSeeder`
(no hay evidencia de que ese rol necesite editarlos). Ninguna de las dos se trata aquí para no
duplicar una decisión que ya tiene dueño.

#### Módulo 13 — Escritorio: jerarquía visual y gráficos (transversal; no cubierto por una HU específica del BRIEF, se formaliza aquí a partir de una revisión de diseño del Escritorio del 22-09-2026)

Estado actual: los 6 widgets propios del Escritorio (`IndicadoresGeneralesWidget`,
`CumplimientoMpWidget`, `AlertasConveniosWidget`, `ConveniosEncargadoWidget`,
`MisPlanesDelMesWidget`, `CatastroAlertasWidget`) son todos `StatsOverviewWidget` — solo tarjetas
numéricas —, ninguno declara `$sort` ni `$heading`, así que el orden sale del arreglo
`->widgets([...])` de `AdminPanelProvider` y los grupos de tarjetas se suceden sin título. Encargado
de Mantención ve 13 tarjetas seguidas (4 generales, 4 de cumplimiento, 2 de convenios, 3 de
catastro), con las alertas que piden acción *debajo* de los contadores de contexto
(`AccountWidget` sí queda primero, porque Filament ya le asigna `$sort = -3`). No hay ningún gráfico: el Escritorio muestra la foto del día, pero nada
muestra tendencia (cómo va el cumplimiento o el gasto mes a mes).

| ID | Descripción | Prioridad |
|---|---|---|
| RF-57 | El sistema debe ordenar el Escritorio por jerarquía de uso, con `$sort` explícito en cada widget y un título (`$heading`) por grupo de tarjetas: (1) `AccountWidget` al inicio, como saludo (ya lo está por defecto); (2) alertas que piden acción (convenios, catastro, "Mis planes de este mes"); (3) cumplimiento MP, el indicador principal del BRIEF (RN-03); (4) gráficos (RF-58 a RF-60); (5) indicadores generales de contexto al final — hoy el orden depende del arreglo de registro del panel y las 13 tarjetas de Encargado de Mantención se leen como una sola lista plana sin agrupación. | Alta |
| RF-58 | El sistema debe mostrar en el Escritorio un gráfico de cumplimiento MP mensual del año en curso: barras de programados vs. ejecutados por mes (enero a diciembre) y una línea con el % de cumplimiento acumulado, reutilizando el criterio de `CalcularCumplimientoMpAction` (RN-03) — visible para los roles con `view.cumplimiento_mp_widget` (Encargado de Mantención, Técnico Interno, Jefatura). El año se elige con el filtro propio del gráfico (`ChartWidget::getFilters()`), mecanismo distinto del `HasFiltersForm` del Escritorio que hoy produce el error 500 de RN-03 (ver `CumplimientoMpWidget`) — si funciona, sirve también de rodeo para ese bug; a verificar al implementar. | Alta |
| RF-59 | El sistema debe mostrar en el Escritorio un gráfico de gasto mensual del año en curso: barras apiladas de MP ejecutado (suma de `ConvenioEjecucionMensual.monto` por mes) y MC ejecutado (suma de `MantenimientoCorrectivo.costo` por mes, RN-04) — mismas fuentes que el detalle de gasto de RF-33 —, visible para Encargado de Mantención, Encargado de Convenios y Jefatura con un permiso propio (`view.gasto_mensual_widget`). | Media |
| RF-60 | El sistema debe mostrar en el Escritorio un gráfico de dona con la distribución de los equipos activos del catastro por estado (Bueno/Regular/Malo/Sin evaluar, RF-12) y otro por criticidad (EQC/EQR/IM≥12/No aplica), visible para Encargado de Mantención y Jefatura con un permiso propio (`view.distribucion_catastro_widget`) — hoy la composición del catastro solo se puede conocer filtrando el listado de Equipos valor por valor. | Media |
| RF-61 | Las tarjetas del Escritorio deben ganar contexto e interacción: (a) una mini-tendencia (`Stat::chart()`, sparkline de los últimos 12 meses) en las tarjetas con dimensión temporal — "Correctivos este año" y "Cumplimiento MP total"; (b) enlace (`Stat::url()`) desde cada tarjeta de alerta al listado que la explica, con el filtro ya aplicado cuando el listado lo permita — p. ej. "Equipos sin evaluar" → Equipos filtrado por estado, "Convenios sobregirados" → Convenios, "Mis planes de este mes" → Bitácora filtrada por año — hoy las tarjetas informan un número pero no llevan a ningún lado, así que para actuar sobre una alerta hay que navegar y filtrar a mano. | Baja |

**Nota sobre alcance**: quedan fuera de este módulo (a) acotar el Escritorio de Jefatura a su
servicio clínico — sigue dependiendo de la decisión de modelo de datos documentada en la nota del
Módulo 11 — y (b) un selector de período global para todo el Escritorio, bloqueado por el bug de
framework de RN-03; RF-58 propone un filtro por gráfico precisamente para no depender de él. Los
colores de los gráficos deben reutilizar la paleta del panel (`AdminPanelProvider`) y la semántica
de umbrales ya fijada en `CumplimientoMpWidget` (≥80% éxito, 50-79% alerta, <50% crítico), sin
introducir colores nuevos.

#### Módulo 14 — Reportes: diseño y distribución (transversal; no cubierto por una HU específica del BRIEF, se formaliza aquí a partir de una revisión de diseño de la página Reportes del 23-09-2026)

Estado actual (`App\Filament\Pages\Reportes` + `reportes.blade.php`): una sección introductoria
de solo texto y, debajo, los tres reportes (RF-31 a RF-33) apilados como secciones de ancho
completo con un párrafo y un botón cada una (RF-40). Problemas: (a) los filtros de año, recinto y
servicio clínico (RF-54) están dentro de un modal *por reporte*, así que el usuario que necesita los
tres archivos del mismo alcance completa el mismo formulario tres veces; (b) se descarga "a ciegas":
nada en la página indica qué contiene el archivo para el alcance elegido (cuántos equipos, qué %
de cumplimiento, cuánto gasto) hasta abrirlo en Excel; (c) la página expone referencias internas del
proyecto al usuario final — botones "Descargar (RF-31)", textos con "RN-03", "BRIEF §3", "pregunta
abierta 6 del BRIEF"; (d) tres secciones de ancho completo con una línea de texto cada una dejan la
mayor parte de la pantalla vacía y la sección introductoria repite lo que ya dice el título.

Alternativas evaluadas: **(1) solo cosmético** — las tres secciones en una grilla de tarjetas y
textos limpios; bajo esfuerzo, pero no resuelve (a) ni (b). **(2) barra de filtros compartida +
tarjetas con resumen** — elegida: resuelve los cuatro problemas con componentes estándar de Filament
(RNF-06) y reutiliza las Actions que ya alimentan los exports, sin cálculo nuevo. **(3) visor de
reportes con vista previa tabular o gráfica por reporte** (pestañas) — descartada: duplica la tabla
de Equipos (RF-53 ya exporta el listado filtrado) y los gráficos del Escritorio (RF-58 a RF-60), con
un esfuerzo varias veces mayor.

| ID | Descripción | Prioridad |
|---|---|---|
| RF-62 | La página de Reportes debe reorganizarse en: (1) una **barra de filtros única** en la parte superior (año, recinto, servicio clínico — los mismos campos y valores por defecto de RF-54), cuyo alcance aplica a los tres reportes, en reemplazo del modal de filtros de cada botón; (2) una **grilla de tres tarjetas** (una columna en móvil, tres en pantallas anchas), una por reporte, cada una con su ícono, título, descripción en lenguaje de usuario final y un **resumen del alcance elegido** que se recalcula al cambiar los filtros, reutilizando las Actions existentes: catastro + plan → n° de equipos y de planes (`ObtenerFilasCatastroPlanAction`); cumplimiento → % total y ejecutados/programados (`CalcularCumplimientoMpAction`, con el color de umbral de `CumplimientoMpWidget`); detalle de gasto → MP y MC ejecutados en CLP (`ObtenerDetalleGastoAction`); (3) un botón **"Descargar Excel"** por tarjeta que descarga directo con el alcance de la barra, sin modal, con el nombre de archivo actual más el alcance cuando haya filtro. Se eliminan de la interfaz las referencias internas (IDs de RF/RN, secciones del BRIEF, preguntas abiertas) y la sección introductoria se reduce a una línea de subtítulo de la página. Los exports (RF-31 a RF-33) no cambian de contenido ni de formato (RNF-09). | Media |

**Nota sobre alcance**: (a) el formulario de filtros debe vivir en la propia página
(`InteractsWithSchemas`), no en `HasFiltersForm` del Escritorio, que produce el error 500 de
Filament 5.7.8 + Livewire 4.4.3 (ver `CumplimientoMpWidget`) — lo primero al implementar es
confirmar que un formulario de página no lo reproduce; si lo hiciera, se conserva el modal por
reporte de RF-54 y se implementa solo el resto de RF-62 (grilla, textos y resumen con el alcance
por defecto: año en curso, todo el Servicio). (b) Quedan fuera el cuarto reporte del mockup
(Pantalla 8, "Convenios y ejecución") y la exportación a PDF que RF-32 permite ("PDF o Excel"):
ambos son contenido nuevo, no diseño, y requerirían su propia RF.

### 3.2 Requisitos no funcionales

| ID | Categoría | Descripción |
|---|---|---|
| RNF-01 | Disponibilidad | El sistema debe operar en horario hábil del Hospital sin degradación perceptible; no se requiere alta disponibilidad 24/7 (uso interno, no asistencial crítico). |
| RNF-02 | Rendimiento | Los listados con filtros (Equipos, Bitácora) deben responder en menos de 2 segundos con el volumen actual (≈469 equipos, hasta 5.704 filas históricas). |
| RNF-03 | Seguridad | Autenticación obligatoria para todo el sistema; sin acceso anónimo. Mecanismo exacto sujeto a PA-12 (SSO/LDAP institucional vs. autenticación local). |
| RNF-04 | Integridad de datos | Toda regla de negocio (RN-01 a RN-05) debe validarse en el backend, no solo en la interfaz, para que no pueda vulnerarse vía API o edición directa. |
| RNF-05 | Auditabilidad | Cambios sobre catastro, bitácora, correctivos y convenios deben quedar trazables (usuario, fecha, valor anterior/nuevo) — ver RF-36. |
| RNF-06 | Usabilidad | Las pantallas deben seguir los patrones estándar de Filament 5 (sin componentes de UI a medida), para minimizar curva de aprendizaje del equipo de mantención. |
| RNF-07 | Portabilidad | El entorno de desarrollo y despliegue debe ser reproducible vía Docker (Laravel Sail), sin dependencias fuera del TRA §5. |
| RNF-08 | Mantenibilidad | El código debe seguir convenciones estándar de Laravel (Actions, Form Requests, Policies) para facilitar el soporte por otro desarrollador del equipo, más allá del autor. |
| RNF-09 | Compatibilidad de datos | La exportación de reportes (RF-31) debe abrir sin errores en Microsoft Excel/LibreOffice, replicando formato de columnas y símbolos (X/√/ꓣ) de la planilla actual. |
| RNF-10 | Localización | Fechas en formato DD-MM-YYYY, montos en pesos chilenos con separador de miles, idioma español (Chile). |

### 3.3 Reglas de negocio

Ver `BRIEF.md` §2 "Reglas de negocio descubiertas" (RN-01 a RN-07) — se referencian, no se
duplican, para evitar que este documento y el BRIEF diverjan con el tiempo.

### 3.4 Interfaces externas

| Interfaz | Naturaleza | Estado |
|---|---|---|
| SIGFE | Referencia manual del código de subasignación (campo de texto) | Confirmado como no-integración por ahora; integración real sujeta a PA-5 |
| Exportación a Excel/PDF | Generación de archivo, sin API externa | RF-31 a RF-33 |
| Autenticación institucional (SSO/LDAP) | Posible integración futura | Sujeto a PA-12; por defecto se asume autenticación local Laravel (Fortify/Breeze) |

### 3.5 Restricciones de diseño

Stack TRA §5 sin excepciones (ver `BRIEF.md` §4 y `SAD.md` §8). Cualquier excepción requiere ADR.

## §4 Casos de uso principales

### UC-01 — Registrar ejecución mensual de mantenimiento

- **Actor primario**: Técnico interno / Proveedor externo.
- **Precondición**: El equipo tiene un plan de MP vigente con el mes en curso marcado "Programado".
- **Flujo principal**: (1) El actor abre la ficha del equipo → Bitácora. (2) Selecciona el mes en
  curso. (3) Marca "Realizado", ingresa fecha real y observación. (4) El sistema valida RN-02 y
  guarda. (5) El indicador de cumplimiento (RF-21) se recalcula.
- **Flujo alternativo**: Si la mantención no se pudo realizar, el actor marca "Reprogramado" en
  vez de "Realizado" (paso 3).
- **Poscondición**: El mes queda con estado final y fecha real registrada.

### UC-02 — Dar de alta un equipo nuevo

- **Actor primario**: Encargado de Mantención o Responsable técnico de recinto.
- **Precondición**: Los catálogos de recinto, servicio clínico y clase/subclase requeridos ya
  existen (RF-05).
- **Flujo principal**: (1) El actor abre Equipos → Nuevo equipo. (2) Completa la ficha (RF-06).
  (3) El sistema valida unicidad de n° de inventario por recinto (RF-07) y campos obligatorios
  (RF-08). (4) Al guardar, si el equipo queda "bajo plan de MP", se solicita la frecuencia anual.
  (5) El sistema genera el plan del año vigente (RF-13).
- **Poscondición**: El equipo queda catastrado y, si corresponde, con su plan de MP generado.

### UC-03 — Registrar mantenimiento correctivo

- **Actor primario**: Encargado de Mantención.
- **Precondición**: El equipo existe en el catastro.
- **Flujo principal**: (1) El actor abre la ficha del equipo → Mant. Correctivo. (2) Registra fecha,
  falla y costo (RF-24). (3) El sistema suma el evento al gasto MC del período (RF-25).
- **Poscondición**: El evento queda visible en el historial del equipo (RF-26).

### UC-04 — Gestionar convenio y su ejecución mensual

- **Actor primario**: Encargado de convenios.
- **Flujo principal**: (1) Registra el convenio (RF-27). (2) Mes a mes, registra ejecución
  (RF-28). (3) El sistema alerta si la suma mensual supera el monto anual (RF-29).
- **Poscondición**: El convenio queda con su % de ejecución actualizado (RF-30).

### UC-05 — Exportar reporte anual equivalente a la planilla

- **Actor primario**: Encargado de Mantención.
- **Flujo principal**: (1) El actor abre Reportes. (2) Selecciona año y tipo de reporte. (3) El
  sistema genera el archivo (RF-31/RF-32/RF-33) en el formato definido.
- **Poscondición**: Archivo descargado, compatible con Excel (RNF-09).

## §5 Matriz de trazabilidad

| Épica (BRIEF) | HU (BRIEF) | RF relacionados |
|---|---|---|
| 1 — Catastro y catálogos | HU-01, HU-02 | RF-01 a RF-12 |
| 2 — Planificación y bitácora | HU-03, HU-04, HU-05 | RF-13 a RF-23 |
| 3 — Mantenimiento correctivo | HU-06 | RF-24 a RF-26 |
| 4 — Convenios y gasto | HU-07 | RF-27 a RF-30 |
| 5 — Indicadores y reportes | HU-08 | RF-31 a RF-33 |
| — (formalizado en este SRS) | — | RF-34 a RF-36 (Usuarios y permisos) |
| — (formalizado en este SRS, 15-09-2026) | — | RF-37 a RF-42 (Experiencia de usuario y diseño) |
| — (formalizado en este SRS, 17-09-2026) | — | RF-43 a RF-46 (Analítica del Escritorio por rol) |
| — (formalizado en este SRS, 17-09-2026) | — | RF-47 a RF-56 (Consistencia funcional y productividad transversal) |
| — (formalizado en este SRS, 22-09-2026) | — | RF-57 a RF-61 (Escritorio: jerarquía visual y gráficos) |
| — (formalizado en este SRS, 23-09-2026) | — | RF-62 (Reportes: diseño y distribución) |

Para la verificación esperada de cada requisito, ver `BRIEF.md` §6 "Trazabilidad mínima" (se
mantiene vigente; este SRS no la reemplaza, solo aporta el detalle de RF que la sustentan).

## §6 Preguntas abiertas relacionadas

Este SRS hereda las 12 preguntas abiertas del `BRIEF.md`. Las que condicionan directamente algún
requisito quedan marcadas en línea (PA-N). No se resuelven aquí — se resuelven con el requirente,
vía Cristian, según lo acordado en el correo del 19-08-2026.
