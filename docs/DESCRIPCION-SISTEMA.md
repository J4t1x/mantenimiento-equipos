---
documento: Descripción del Sistema
proyecto: Sistema de Bitácora de Mantenimiento de Equipos
servicio: Servicio de Salud Aysén
establecimiento_base: Hospital Regional Coyhaique
version: 1.0
estado: entregado — documento de apoyo institucional
autor: Javier Mansilla
para: Cristian Santander Marchant
fecha: 2026-08-31
---

# Descripción del Sistema — Bitácora de Mantenimiento de Equipos

> Documento de apoyo, complementario al `BRIEF.md` (análisis técnico formal). Su objetivo es
> explicar en lenguaje llano qué es el sistema, qué problema resuelve y qué hace, para ampliar la
> documentación institucional del proyecto. El detalle técnico (historias de usuario, reglas de
> negocio, arquitectura, validaciones) está en `BRIEF.md`; aquí no se repite, se resume.

## 1. ¿Qué es el sistema?

Una aplicación web de uso interno que reemplaza la planilla Excel
`PLANILLA MANTENIMIENTO EQUIPOS MÉDICOS 2026` como herramienta con la que el Subdepartamento de
Mantención y Operaciones del Hospital Regional Coyhaique gestiona el **ciclo completo del
mantenimiento de equipos médicos**: qué equipos existen, cuándo les corresponde mantención
preventiva, si esa mantención se ejecutó a tiempo, qué fallas correctivas han tenido, y cuánto
cuesta todo eso a lo largo del año.

En una frase: es la versión digital, con reglas y controles, de lo que hoy vive en una planilla de
5.704 filas mantenida a mano.

## 2. ¿Por qué se necesita?

La planilla actual cumplió su función, pero como herramienta de gestión ya muestra signos claros
de desgaste:

- **Datos inconsistentes**: el campo "Servicio Clínico" debería completarse desde una lista fija,
  pero esa lista quedó rota (referencia a un archivo externo que ya no existe). El resultado son
  más de 110 formas distintas de escribir lo que en realidad son ~40 servicios clínicos reales
  (errores de tipeo, mayúsculas mezcladas, espacios de más, celdas con dos servicios juntos).
- **Información incompleta**: 59 de los 469 equipos activos no tienen registrado si su estado es
  bueno, regular o malo.
- **Cálculos manuales sin respaldo**: los porcentajes de cumplimiento del plan de mantención (por
  equipo crítico, relevante, etc.) se calculan con fórmulas y tablas dinámicas en una tercera hoja,
  sin que quede un rastro automático de por qué el número dio ese valor.
- **Presupuesto reconciliado a mano**: el gasto de los convenios con proveedores se lleva en otra
  hoja aparte y hay que cuadrarlo manualmente contra el resumen general.

Un sistema propio permite que estos datos se validen al ingresarlos (no después, revisando la
planilla), que los cálculos de cumplimiento sean automáticos y confiables, y que la información
quede disponible para todo el equipo sin depender de un único archivo Excel compartido.

## 3. ¿Qué hace el sistema? (funcionalidad, en lenguaje simple)

**Inventario de equipos.** Cada equipo médico queda registrado con su ficha completa: dónde está
(recinto y servicio clínico), qué es (marca, modelo, serie, número de inventario), de quién es
(propio, arrendado, en comodato o prestado), qué tan crítico es, y en qué estado se encuentra. Los
campos que hoy son texto libre (servicio clínico, recinto, tipo de equipo) pasan a elegirse de una
lista administrable, para que no se repita el problema de las 110 variantes.

**Plan anual de mantenimiento preventivo.** A cada equipo se le asigna cuántas veces al año debe
recibir mantención (1, 2, 3, 4, 6 o 12 veces). El sistema calcula solo en qué meses corresponde esa
mantención, en vez de que alguien lo marque a mano mes por mes como hoy.

**Bitácora de ejecución.** Mes a mes, el técnico interno o el proveedor externo registra si la
mantención programada se realizó o se reprogramó. Esto es literalmente la "bitácora" del nombre del
proyecto: un historial que se va completando durante el año, igual que hoy, pero validado por el
sistema en vez de quedar como una marca libre en una celda.

**Mantenimiento correctivo.** Cuando un equipo falla fuera de su plan preventivo, ese evento
(qué pasó, cuándo, cuánto costó) queda registrado asociado al equipo, para tener su historial
completo — no solo lo programado, también lo imprevisto.

**Convenios y gasto.** Los convenios con proveedores externos (monto anual, vigencia, número de
resolución) y su ejecución mes a mes quedan en el sistema, para que el gasto de mantenimiento se
pueda seguir sin cruzar planillas a mano.

**Indicadores y reportes.** Un panel muestra el porcentaje de cumplimiento del plan de mantención
—en total y separado por equipos críticos, relevantes y otros— además del gasto programado versus
ejecutado. También se puede exportar un reporte con el mismo formato que hoy se usa para reportar a
SSA/MINSAL, mientras el sistema nuevo se valida en paralelo con el proceso actual.

## 4. ¿Quiénes lo usan?

- El **equipo del Subdepartamento de Mantención y Operaciones** (Edgon Pérez y su equipo), como
  dueños del proceso, administrando catálogos, catastro y plan de mantención.
- Los **responsables técnicos de cada recinto**, validando el catastro y el plan de su área.
- Los **técnicos internos y proveedores externos**, registrando la ejecución mensual de cada
  mantención.
- El **encargado de convenios y presupuesto**, llevando el seguimiento del gasto.
- La **jefatura de servicio clínico**, como referente del equipamiento asignado a su servicio.
- **Cristian Santander**, como sponsor técnico que valida el análisis y amplía con esto la
  documentación institucional del proyecto.

## 5. ¿Con qué se construye?

Sobre el stack tecnológico oficial definido en el TRA del marco de ingeniería, sin excepciones:
**Laravel 13 + Livewire 4 + Filament 5** para el panel de gestión, **PHP 8.4**, base de datos
**PostgreSQL 16+**, y entorno de desarrollo en **Docker (Laravel Sail)**. Al ser un sistema de uso
interno (panel administrativo), Filament cubre todas las pantallas previstas sin necesidad de
desarrollo de interfaz a medida.

## 6. Qué no incluye este sistema (por ahora)

- **No reemplaza el paso a producción**: eso lo ejecuta el Sysadmin una vez entregado el sistema
  funcional (fuera del alcance de este encargo).
- **No integra automáticamente con SIGFE**: por ahora solo registra el código de referencia; una
  integración real es una de las preguntas abiertas al requirente.
- **No decide todavía el criterio de criticidad** (qué hace que un equipo sea "crítico" o
  "relevante"): hoy se migra el resultado ya asignado en la planilla, pero la regla de fondo no
  está documentada y se debe confirmar con el requirente.

## 7. Relación con el resto de la entrega

Este documento es descriptivo y de apoyo institucional. El análisis técnico completo — historias
de usuario con criterios de aceptación, reglas de negocio con su nivel de confianza, arquitectura
de módulos, validaciones esperadas y la lista formal de preguntas abiertas al requirente — está en
`BRIEF.md`, entregado junto con este documento.

## 8. Estado y próximo paso

Etapa 1 (Análisis) queda cerrada con la entrega de ambos documentos. El siguiente paso depende de
Cristian y del requirente: validar el análisis y resolver las preguntas abiertas del `BRIEF.md`
antes de iniciar la Etapa 2 (Construcción).
