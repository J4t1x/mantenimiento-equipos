# Bitácora de Mantenimiento de Equipos Médicos

**Servicio de Salud Aysén** · Subdepartamento de Tecnologías de la Información

Sistema web que reemplaza la planilla Excel `PLANILLA MANTENIMIENTO EQUIPOS MÉDICOS 2026` con la
que hoy se lleva el catastro de equipos médicos, la planificación y bitácora del mantenimiento
preventivo, el mantenimiento correctivo, los convenios con proveedores y sus indicadores.

## Funcionalidades

| Módulo | Qué permite |
|---|---|
| Catálogos | Recintos (con su responsable de mantenimiento preventivo designado y su gasto programado anual), servicios clínicos, clases/subclases de equipo y proveedores. Un valor referenciado por un equipo no se puede eliminar, solo desactivar. |
| Equipos (catastro) | Ficha completa de cada equipo, con vida útil residual, garantía, criticidad y estado, y registro del retiro de uso y reingreso con su evidencia. Alerta de equipos críticos sin plan del año. Filtros, búsqueda global y exportación a Excel del listado. |
| Planificación MP | Plan anual por equipo: al definir la frecuencia, el sistema genera automáticamente los meses programados (RN-01). Los equipos críticos exigen al menos 2 mantenciones al año, salvo con garantía vigente, en que se puede usar la periodicidad del fabricante (Res. Ex. 1341/2017). |
| Bitácora | Grilla de 12 meses por plan. Un mes solo se marca "Realizado" o "Reprogramado" si estaba programado (RN-02); reprogramar exige indicar la causa (y admite el n° del documento de justificación), y los equipos críticos con una reprogramación sin realizar tras 30 días aparecen como alerta en el Escritorio. El técnico interno solo registra sus equipos asignados. |
| Mantenimiento correctivo | Registro de fallas y su costo, que suma automáticamente al gasto del período (RN-04). |
| Convenios y gasto | Convenios con su ejecución mensual por orden de compra; alerta sin bloquear cuando se supera el monto anual (RN-05). |
| Escritorio | Alertas por rol, cumplimiento de MP en 4 cortes (total, EQC, EQR, IM≥12, RN-03) con selector de año y período (año, semestre, trimestre o mes), gráficos mensuales de cumplimiento y gasto, composición del catastro. |
| Reportes | Catastro + plan anual, indicador de cumplimiento y detalle de gasto, en Excel con el formato de la planilla vigente, filtrables por año, recinto y servicio clínico. El cumplimiento y el gasto también se pueden acotar a un semestre o trimestre. Incluye el informe de cumplimiento de equipos críticos de la Res. Ex. 1341/2017 del MINSAL (indicador por equipos, detalle por equipo y reprogramaciones con su causa), en Excel y en versión imprimible para guardar como PDF y firmar. El gasto programado sale del presupuesto anual de cada recinto. |
| Usuarios y permisos | Roles, permisos por pantalla, recintos asignados por usuario, notificaciones y auditoría de cambios. |

### Roles

| Rol | Alcance |
|---|---|
| Encargado de Mantención | Catálogos, catastro, planificación, bitácora, correctivos y reportes; convenios en lectura. |
| Técnico Interno | Registro de la bitácora y correctivos de sus equipos asignados. |
| Encargado de Convenios | Convenios, su ejecución mensual, proveedores y reportes. |
| Jefatura | Lectura de todo el sistema y reportes. |
| `super_admin` | Administración completa, incluidos usuarios y roles. |

Además del rol, cada usuario puede tener recintos asignados. Sin recintos (personal del
subdepartamento del SSA) ve todos los establecimientos. Con recintos asignados solo ve y registra
catastro, planes, bitácora, correctivos, indicadores y reportes de esos recintos, y no puede
modificar el catálogo de Recintos. Los convenios son comunes a todos los recintos.

## Stack

Laravel 13 · Filament 5 · Livewire 4 · PHP 8.4 · PostgreSQL 18 · Laravel Sail (Docker).

## Puesta en marcha (desarrollo)

Requisitos: Docker, y PHP 8.4 con Composer para el `composer install` inicial.

```bash
cp .env.example .env
composer install
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
./vendor/bin/sail artisan app:importar-catastro-coyhaique   # opcional: carga los 469 equipos reales
```

Panel: <http://localhost:8010/admin>

El seeder crea los roles con sus permisos y dos usuarios `super_admin` de desarrollo
(`admin@saludaysen.cl` y `test@example.com`, contraseña `password`). Deben reemplazarse antes de
cualquier despliegue.

### Carga de datos reales

`app:importar-catastro-coyhaique` carga el catastro y el plan de MP del Hospital Regional Coyhaique
desde `resources/data/planilla-mantenimiento-equipos-medicos-2026.xlsx` (469 equipos,
con las marcas mensuales reales de la planilla). Acepta otra ruta como argumento. Con
`--recinto="Hospital de Puerto Aysén"` carga una planilla de otro establecimiento con la misma
estructura en ese recinto. No vuelve a importar en un recinto que ya tiene equipos, salvo con
`--force`. Los servicios clínicos escritos de
distintas formas en la planilla se normalizan con `resources/data/mapa_servicios_clinicos_coyhaique.php`.

### Clasificación de equipos críticos de la norma

`app:clasificar-tipos-criticos` sugiere, a partir del nombre, el tipo de equipo crítico de la Res.
Ex. 1341/2017 (ventilador, desfibrilador, incubadora, anestesia, diálisis). Sin opciones solo
muestra lo que haría. Con `--aplicar` asigna el tipo a los equipos sin clasificar que ya son
críticos, y lista sin modificarlos a los que la norma exige críticos pero tienen otra criticidad.

## Pruebas

```bash
./vendor/bin/sail artisan test
```

La suite cubre las reglas de negocio (RN-01 a RN-05 y la frecuencia mínima de críticos), el
alcance del técnico interno y por recinto, el acceso por rol a cada pantalla, la protección de catálogos, el Escritorio, los reportes y la importación de la
planilla.

## Documentación

| Documento | Contenido |
|---|---|
| [`docs/BRIEF.md`](docs/BRIEF.md) ([PDF](docs/BRIEF.pdf)) | Análisis: problema, reglas de negocio, historias de usuario y preguntas abiertas. |
| [`docs/SRS.md`](docs/SRS.md) | Requisitos funcionales (RF-01 a RF-68) y no funcionales, con trazabilidad. |
| [`docs/SAD.md`](docs/SAD.md) | Arquitectura y decisiones (ADR). |
| [`docs/MODELO-DATOS.md`](docs/MODELO-DATOS.md) | Modelo de datos. |
| [`docs/DESCRIPCION-SISTEMA.md`](docs/DESCRIPCION-SISTEMA.md) | Descripción funcional del sistema. |
| [`docs/MOCKUP-SISTEMA.html`](docs/MOCKUP-SISTEMA.html) | Mockup de pantallas (referencia visual, no diseño final). |
| [`docs/normativa/NORMA-MP-EQUIPAMIENTO-CRITICO.md`](docs/normativa/NORMA-MP-EQUIPAMIENTO-CRITICO.md) ([PDF](docs/normativa/Resolucion-Exenta-1341-2017-MINSAL-Norma-MP-Equipamiento-Medico-Critico.pdf)) | Síntesis de la Res. Ex. 1341/2017 del MINSAL (MP de equipamiento médico crítico) y su aplicación al sistema. |

## Pendientes

- **Clasificación de equipos críticos (RF-75)**: falta revisar a mano los equipos sin sugerencia
  por nombre (entre ellos los monitores multiparámetros con monitorización hemodinámica invasiva)
  y decidir la criticidad de las 25 máquinas de diálisis marcadas Relevante en la planilla 2026.
- **Carga del resto de recintos**: el importador ya acepta el recinto destino (RF-64). Falta la
  planilla de cada establecimiento; la siguiente es la del Hospital de Puerto Aysén.
