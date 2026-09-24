---
documento: BRIEF
proyecto: Sistema de Bitácora de Mantenimiento de Equipos
servicio: Servicio de Salud Aysén
perfil: lite
version: 1.0
estado: validado — aprobado telefónicamente por Cristian Santander (registrado 07-09-2026)
marco: v3.4
autor: Javier Mansilla
para: Cristian Santander Marchant
fecha: 2026-08-31
---

# BRIEF — Sistema de Bitácora de Mantenimiento de Equipos

> Documento único de análisis (perfil lite), elaborado según el encargo de Etapa 1 (correo del
> 19-08-2026) sobre los insumos disponibles: reunión de levantamiento con Edgon Pérez y el equipo
> del Subdepartamento de Mantención y Operaciones (ya realizada), y la planilla
> `PLANILLA MANTENIMIENTO EQUIPOS MÉDICOS 2026.xlsx` remitida en esa reunión. Fusiona ANÁLISIS +
> SRS + SAD + SPEC en un solo archivo. Las preguntas abiertas quedan listadas al final, tal como
> se solicitó.

## 1. Contexto

**Qué se construye.** Un sistema que reemplaza la planilla Excel `PLANILLA MANTENIMIENTO EQUIPOS
MÉDICOS 2026` como herramienta de gestión del **catastro de equipos médicos**, la **planificación
anual de mantenimiento preventivo (MP)**, el **registro de ejecución mes a mes (la "bitácora")**,
el **mantenimiento correctivo (MC)** y el **seguimiento presupuestario de convenios de
mantenimiento**, para el Servicio de Salud Aysén.

**Por qué.** La planilla actual concentra todo el proceso en un único archivo de 5.704 filas
(469 con datos) que ya presenta señales concretas de fatiga como herramienta de gestión:

- La lista desplegable del campo `Servicio Clínico` referencia un libro Excel externo con enlace
  roto (`#REF!`), lo que obligó a cargar ese campo como texto libre. Resultado: **más de 110
  variantes de texto** para lo que son en la práctica ~40 servicios clínicos reales (typos,
  espacios, mayúsculas inconsistentes, celdas con varios servicios separados por `/`).
- El campo `Estado (Bueno/Regular/Malo)` está vacío en 59 de los 469 equipos registrados.
- El cálculo de cumplimiento (% ejecución de MP, por criticidad y por mes) depende de fórmulas y
  tablas dinámicas manuales en una tercera hoja, sin trazabilidad automática hacia el detalle por
  equipo.
- El seguimiento de gasto de convenios vive en una hoja aparte (~228 filas), reconciliado a mano
  contra el resumen de gasto de la hoja principal.

**Encargo.** Etapa 1 de 2: este documento es el análisis a validar por el requirente antes de
iniciar la programación (Etapa 2). El paso a producción lo ejecuta el Sysadmin del Servicio de
Salud Aysén, fuera del alcance de ambas etapas.

**Perfil del proyecto.** `lite` — un único `BRIEF.md` en vez de los cuatro documentos formales
(ANÁLISIS/SRS/SAD/SPEC), acorde al tamaño del encargo. Si el sistema termina expuesto a terceros
externos al Servicio de Salud o su alcance de datos personales crece, corresponde reevaluar el
perfil a `estandar` (ver AUDIT del marco) antes del paso a producción.

## 2. Dominio

### Actores

| Actor | Rol en el proceso |
|---|---|
| Encargado(s) Subdepartamento de Mantención y Operaciones (Edgon Pérez y equipo) | Requirente; dueño del proceso de mantenimiento de equipos |
| Responsable técnico del establecimiento | Valida el catastro y el plan anual de su recinto |
| Técnico de mantenimiento interno | Ejecuta y registra mantenimientos internos |
| Proveedor externo de mantenimiento | Ejecuta mantenimientos bajo convenio |
| Encargado de convenios/presupuesto | Registra convenios, resoluciones, montos y seguimiento de gasto (SIGFE) |
| Jefatura de servicio clínico | Referente del equipo asignado a su servicio |
| Cristian Santander (Encargado Sección Desarrollo de Sistemas) | Sponsor técnico; valida el análisis y amplía la documentación institucional |

### Datos esenciales (entidades candidatas)

- **Equipo médico**: recinto, servicio clínico, clase, subclase, nombre, marca, modelo, serie,
  n° de inventario, año de adquisición, vida útil, vida útil residual, propiedad, estado,
  criticidad, garantía (SI/NO + año de vencimiento), si está bajo plan de MP, año de ingreso al
  plan.
- **Plan de mantenimiento preventivo** (por equipo y año): frecuencia anual, tipo de ejecución
  (interno/externo), proveedor o responsable interno, convenio asociado, costo anual de
  referencia.
- **Ejecución mensual / bitácora**: equipo, mes, año, estado (Programado / Realizado /
  Reprogramado), fecha real, observaciones.
- **Mantenimiento correctivo**: registro por evento (equipo, fecha, falla, costo, tipo de gasto —
  ver pregunta abierta 6).
- **Convenio de mantenimiento**: nombre, n° de resolución/orden de compra, fecha de resolución,
  fecha de expiración, monto anual, subasignación SIGFE.
- **Gasto mensual de convenio**: convenio, mes, n° de orden de compra/factura, monto.
- **Catálogos maestros**: recintos/establecimientos, servicios clínicos, clase/subclase de
  equipos — hoy texto libre en la planilla, se proponen como catálogos administrables.

### Catálogos de valores fijos verificados en la planilla

Extraídos de las listas de validación de datos reales de la planilla (`TABLA CÁLCULO`), no
inferidos:

| Campo | Valores válidos |
|---|---|
| Estado mensual de ejecución | `X` Programado · `√` Realizado · `ꓣ` Reprogramado |
| Criticidad | Crítico · Relevante · IM ≥ 12 · No aplica |
| Propiedad | Propio · Arriendo · Comodato · Préstamo |
| Estado del equipo | Bueno · Regular · Malo |
| En garantía / Bajo plan de MP | Sí · No |
| Mantenimiento | Interno · Externo |
| Año de ingreso al plan | 2023 · 2024 · 2025 · 2026 |
| Frecuencia anual de mantenimiento | 1 · 2 · 3 · 4 · 6 · 12 |

### Reglas de negocio descubiertas

Nivel de confianza entre paréntesis (alta = está en la planilla/correo; media = se infiere de la
estructura de datos; baja = se deduce y debe confirmarse).

- **RN-01** (alta): la cantidad de meses marcados como "Programado" en el año debe ser igual a la
  frecuencia anual de mantenimiento del equipo.
- **RN-02** (alta): el estado mensual de ejecución solo admite tres valores — Programado,
  Realizado, Reprogramado —; una celda vacía significa que ese mes no corresponde programación.
- **RN-03** (alta): el % de ejecución de MP (mensual y anual) se calcula como
  *mantenimientos ejecutados / mantenimientos programados*, desagregado en cuatro cortes: total,
  equipos críticos (EQC), equipos relevantes (EQR) e IM≥12.
- **RN-04** (media): el gasto se controla de forma independiente para mantenimiento preventivo
  (MP) y mantenimiento correctivo (MC), cada uno con su propio gasto programado, gasto ejecutado
  y % de ejecución presupuestaria.
- **RN-05** (media): un convenio de mantenimiento tiene un monto anual que se imputa mes a mes
  contra órdenes de compra/facturas; la suma de los 12 meses debe conciliar con el monto anual
  del convenio.
- **RN-06** (baja — a confirmar, pregunta abierta 10): vida útil residual = vida útil − (año
  actual − año de adquisición); puede resultar negativa cuando el equipo ya superó su vida útil
  (se observan valores negativos reales en la planilla, ej. `-4`).
- **RN-07** (baja — a confirmar, pregunta abierta 2): el criterio exacto para clasificar un
  equipo como Crítico / Relevante / IM≥12 / No aplica no está documentado en los insumos
  disponibles; solo se conoce el resultado ya asignado por equipo.

## 3. Requisitos

Historias de usuario mínimas agrupadas por épica, con criterios de aceptación verificables.

### Épica 1 — Catastro y catálogos

- **HU-01**: Como encargado de mantención, quiero administrar catálogos de recintos, servicios
  clínicos y clase/subclase de equipos, para eliminar la duplicidad de texto libre que hoy existe
  en la planilla.
  *Criterio de aceptación*: no es posible registrar un equipo con un servicio clínico, recinto o
  clase que no exista en el catálogo correspondiente.
- **HU-02**: Como responsable técnico, quiero registrar y editar la ficha completa de un equipo
  médico (identificación, propiedad, estado, criticidad, garantía), para mantener el inventario
  actualizado.
  *Criterio de aceptación*: los campos obligatorios de la ficha (servicio clínico, recinto,
  nombre, n° de inventario, criticidad) no pueden guardarse vacíos; n° de inventario es único por
  recinto.

### Épica 2 — Planificación y bitácora de mantenimiento preventivo

- **HU-03**: Como encargado de mantención, quiero que el sistema genere automáticamente las
  marcas "Programado" del año para un equipo según su frecuencia anual, para no calendarizar mes a
  mes de forma manual.
  *Criterio de aceptación*: al fijar la frecuencia de un equipo, el número de meses marcados como
  Programado en el plan del año coincide exactamente con esa frecuencia (RN-01).
- **HU-04**: Como técnico interno o proveedor externo, quiero registrar la ejecución de un
  mantenimiento programado de un mes (Realizado o Reprogramado), para mantener la bitácora al día.
  *Criterio de aceptación*: un mes sin marca Programado no puede pasar directamente a Realizado o
  Reprogramado (RN-02).
- **HU-05**: Como jefe de sección, quiero visualizar el % de cumplimiento de MP mensual y anual,
  total y desagregado por criticidad (EQC/EQR/IM≥12), para monitorear la gestión del plan.
  *Criterio de aceptación*: el indicador coincide con el cálculo *ejecutados / programados* del
  período y corte seleccionado (RN-03).

### Épica 3 — Mantenimiento correctivo

- **HU-06**: Como encargado de mantención, quiero registrar eventos de mantenimiento correctivo
  por equipo (falla, fecha, costo), para tener trazabilidad completa del historial del equipo más
  allá del plan preventivo.
  *Criterio de aceptación*: cada evento correctivo queda asociado a un equipo del catastro y suma
  al gasto ejecutado de MC del período (RN-04). *(Alcance exacto sujeto a pregunta abierta 6.)*

### Épica 4 — Convenios y seguimiento de gasto

- **HU-07**: Como encargado de convenios, quiero registrar un convenio de mantenimiento (monto
  anual, resolución, vigencia, subasignación SIGFE) y su ejecución mes a mes, para controlar el
  presupuesto asociado.
  *Criterio de aceptación*: la suma de los montos mensuales registrados para un convenio no puede
  superar su monto anual sin una alerta explícita (RN-05).

### Épica 5 — Indicadores y reportes

- **HU-08**: Como encargado de mantención, quiero exportar un reporte equivalente al de la
  planilla actual (catastro + plan + cumplimiento), para seguir reportando a SSA/MINSAL mientras
  el nuevo sistema se valida en paralelo.
  *Criterio de aceptación*: el reporte exportado reproduce, para un año dado, las mismas columnas
  de catastro y las mismas marcas mensuales de la planilla vigente.

## 4. Arquitectura

**Enfoque.** Monolito modular Laravel, 100% sobre el TRA vigente sin excepciones: el sistema es
de uso interno (panel de gestión), por lo que **Filament 5** cubre la totalidad de las pantallas
previstas; no se identifica, por ahora, una necesidad de UI pública a medida que justifique
componentes Livewire fuera de Filament.

**Módulos propuestos** (orden sugerido de implementación en Etapa 2):

1. **Catálogos** — recintos, servicios clínicos, clase/subclase de equipo, proveedores.
2. **Equipos** — catastro (ficha técnica completa de la Épica 1).
3. **Planificación MP** — plan anual por equipo + generación automática de marcas Programado
   (HU-03).
4. **Bitácora de ejecución** — registro mensual Realizado/Reprogramado (HU-04) y cálculo de
   indicadores (HU-05).
5. **Mantenimiento correctivo** — registro de eventos por equipo (HU-06).
6. **Convenios y gasto** — convenios, ejecución mensual, conciliación con SIGFE como referencia
   (HU-07).
7. **Reportes** — dashboard de indicadores + exportación (HU-08).
8. **Usuarios y permisos** — roles: Encargado de Mantención, Técnico Interno, Encargado de
   Convenios, Jefatura (lectura). Acceso de proveedores externos sujeto a pregunta abierta 7.

**Decisiones clave**

- Persistencia en **PostgreSQL 16+**, schema propio del proyecto, siguiendo las convenciones de
  nombrado del TRA (sección 5.1).
- No se registra ninguna excepción al TRA en esta etapa; no se requiere ADR.
- Se mantiene exportación a Excel del reporte equivalente al actual (HU-08) como puente durante la
  transición, no como funcionalidad permanente a menos que el requirente lo pida explícitamente.

**Fuera de alcance de Etapa 1 y 2**

- Infraestructura y paso a producción (Sysadmin).
- Integración real con SIGFE (se registra el código de referencia; ver pregunta abierta 5).

## 5. Spec ejecutable

Nivel de detalle mínimo para orientar la Etapa 2; se ampliará a un SPEC formal si el proyecto
cambia de perfil.

- **Validaciones de dominio esperadas** (equivalentes a `Rule::in` de Laravel):
  `frecuencia_anual: in:1,2,3,4,6,12` · `criticidad: in:critico,relevante,im_mayor_igual_12,no_aplica`
  · `estado_ejecucion_mensual: in:programado,realizado,reprogramado` ·
  `propiedad: in:propio,arriendo,comodato,prestamo` · `estado_equipo: in:bueno,regular,malo`.
- **Regla ejecutable RN-01**: al guardar `frecuencia_anual` de un equipo, un *action* recalcula y
  regenera las 12 marcas mensuales del año vigente, distribuyendo la cantidad exacta de meses
  Programado (algoritmo de distribución exacto — mensual/bimensual/trimestral — a definir en
  Etapa 2 con el requirente).
- **Permisos previstos** (formato `modulo.recurso.accion`): `catalogos.servicios.gestionar` ·
  `equipos.catastro.crear|editar|ver` · `planificacion.bitacora.ejecutar` ·
  `convenios.gasto.gestionar` · `reportes.indicadores.ver`.
- **Patrón de UI**: Filament Resources por módulo (Equipos, Convenios, etc.), con una acción
  personalizada "Marcar ejecución del mes" sobre el recurso de Bitácora, y un widget de dashboard
  para los indicadores de HU-05.

## 6. Trazabilidad mínima

| Requisito | Verificación esperada |
|---|---|
| HU-01 Catálogos | Test de validación: creación de equipo con servicio/recinto/clase inexistente falla |
| HU-02 Catastro de equipo | Test de unicidad de n° de inventario por recinto; campos obligatorios |
| HU-03 Generación automática del plan | Test: para cada frecuencia (1/2/3/4/6/12), el n° de meses Programado generado calza con RN-01 |
| HU-04 Registro de ejecución | Test: transición inválida (mes sin Programado → Realizado) es rechazada |
| HU-05 Indicador de cumplimiento | Test: cálculo de % ejecución por corte (total/EQC/EQR/IM≥12) contra fixture conocido |
| HU-06 Mantenimiento correctivo | Test: evento correctivo queda asociado a equipo y suma al gasto MC del período |
| HU-07 Convenios y gasto | Test: alerta cuando la suma mensual de un convenio supera su monto anual |
| HU-08 Exportación | Test: exportación reproduce columnas y marcas del año seleccionado |

## Preguntas abiertas (dependen del requirente)

1. **Alcance de recintos**: ¿el sistema cubre un único establecimiento o el conjunto de
   establecimientos del Servicio de Salud Aysén (hospitales, CESFAM, postas)? La planilla actual
   solo trae un recinto cargado como ejemplo, pero su encabezado dice "Servicio de Salud Aysén".
2. **Criterio de criticidad**: ¿existe una norma MINSAL o instructivo interno que defina cómo se
   clasifica un equipo como Crítico / Relevante / IM≥12 / No aplica? Hoy solo se conoce el
   resultado ya asignado, no la regla.
3. **Servicio clínico por equipo**: ¿la relación equipo–servicio clínico es 1:N (un equipo, un
   servicio) o M:N? Se observan celdas con varios servicios separados por `/` (ej. "Uci adultos /
   Bodega", "Pediatria / Urg. Pediátrica"). Se requiere además el listado oficial y depurado de
   servicios clínicos para construir el catálogo.
4. **Proveedores y convenios vigentes**: ¿existe un listado maestro actualizado de proveedores
   externos y convenios vigentes, o se migra tal cual desde la hoja `SEGUIMIENTO GASTO`?
5. **Integración con SIGFE**: ¿el sistema debe integrarse con SIGFE (código de subasignación) o
   basta con registrar el código como referencia manual, sin integración real?
6. **Detalle del mantenimiento correctivo**: ¿quién y cómo registra hoy el MC? La planilla solo
   trae el gasto programado/ejecutado agregado (sin planilla fuente por evento); la hoja de
   cálculo interna sugiere categorías ("Adquisición de repuestos/insumos/accesorios") sin datos
   asociados.
7. **Acceso de proveedores externos**: ¿tendrán usuario propio en el sistema para registrar su
   ejecución, o siempre se registra a través de un usuario interno (responsable técnico o
   encargado de mantención)?
8. **Reportes obligatorios**: ¿qué reportes/exportaciones son obligatorios para seguir cumpliendo
   con SSA/MINSAL (mismo formato de la planilla, oficios, informes trimestrales)?
9. **Migración de histórico**: la columna "Año de ingreso al plan de mantenimiento" incluye años
   2023-2025. ¿Se migra solo el año vigente (2026) o también el histórico, y en qué etapa?
10. **Fórmula de vida útil residual**: ¿se recalcula automáticamente cada año o es un valor fijo
    que se actualiza manualmente en la planilla actual?
11. **Equipos sin estado registrado**: 59 de 469 equipos no tienen dato en "Estado
    (Bueno/Regular/Malo)". ¿Se deben completar antes de migrar, o el campo puede quedar como
    "Sin evaluar"?
12. **Autenticación institucional**: ¿existe una política de SSO/LDAP del Servicio de Salud que el
    sistema deba respetar, o basta con autenticación local de Laravel (Fortify/Breeze) con roles
    internos?
