# Bitácora de Mantenimiento de Equipos Médicos

**Servicio de Salud Aysén** · Subdepartamento de Tecnologías de la Información

Sistema web que reemplaza la planilla Excel `PLANILLA MANTENIMIENTO EQUIPOS MÉDICOS 2026` con la
que hoy se lleva el catastro de equipos médicos, la planificación y bitácora del mantenimiento
preventivo, el mantenimiento correctivo, los convenios con proveedores y sus indicadores.

## Funcionalidades

| Módulo | Qué permite |
|---|---|
| Catálogos | Recintos, servicios clínicos, clases/subclases de equipo y proveedores. Un valor referenciado por un equipo no se puede eliminar, solo desactivar. |
| Equipos (catastro) | Ficha completa de cada equipo, con vida útil residual, garantía, criticidad y estado. Filtros, búsqueda global y exportación a Excel del listado. |
| Planificación MP | Plan anual por equipo: al definir la frecuencia, el sistema genera automáticamente los meses programados (RN-01). |
| Bitácora | Grilla de 12 meses por plan. Un mes solo se marca "Realizado" o "Reprogramado" si estaba programado (RN-02); el técnico interno solo registra sus equipos asignados. |
| Mantenimiento correctivo | Registro de fallas y su costo, que suma automáticamente al gasto del período (RN-04). |
| Convenios y gasto | Convenios con su ejecución mensual por orden de compra; alerta sin bloquear cuando se supera el monto anual (RN-05). |
| Escritorio | Alertas por rol, cumplimiento de MP en 4 cortes (total, EQC, EQR, IM≥12, RN-03), gráficos mensuales de cumplimiento y gasto, composición del catastro. |
| Reportes | Catastro + plan anual, indicador de cumplimiento y detalle de gasto, en Excel con el formato de la planilla vigente, filtrables por año, recinto y servicio clínico. |
| Usuarios y permisos | Roles, permisos por pantalla, notificaciones y auditoría de cambios. |

### Roles

| Rol | Alcance |
|---|---|
| Encargado de Mantención | Catálogos, catastro, planificación, bitácora, correctivos y reportes; convenios en lectura. |
| Técnico Interno | Registro de la bitácora y correctivos de sus equipos asignados. |
| Encargado de Convenios | Convenios, su ejecución mensual, proveedores y reportes. |
| Jefatura | Lectura de todo el sistema y reportes. |
| `super_admin` | Administración completa, incluidos usuarios y roles. |

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
con las marcas mensuales reales de la planilla). Acepta otra ruta como argumento y no vuelve a
importar si ya hay equipos cargados, salvo con `--force`. Los servicios clínicos escritos de
distintas formas en la planilla se normalizan con `resources/data/mapa_servicios_clinicos_coyhaique.php`.

## Pruebas

```bash
./vendor/bin/sail artisan test
```

La suite cubre las reglas de negocio (RN-01 a RN-05), el alcance del técnico interno, el acceso por
rol a cada pantalla, la protección de catálogos, el Escritorio, los reportes y la importación de la
planilla.

## Documentación

| Documento | Contenido |
|---|---|
| [`docs/BRIEF.md`](docs/BRIEF.md) ([PDF](docs/BRIEF.pdf)) | Análisis: problema, reglas de negocio, historias de usuario y preguntas abiertas. |
| [`docs/SRS.md`](docs/SRS.md) | Requisitos funcionales (RF-01 a RF-62) y no funcionales, con trazabilidad. |
| [`docs/SAD.md`](docs/SAD.md) | Arquitectura y decisiones (ADR). |
| [`docs/MODELO-DATOS.md`](docs/MODELO-DATOS.md) | Modelo de datos. |
| [`docs/DESCRIPCION-SISTEMA.md`](docs/DESCRIPCION-SISTEMA.md) | Descripción funcional del sistema. |
| [`docs/MOCKUP-SISTEMA.html`](docs/MOCKUP-SISTEMA.html) | Mockup de pantallas (referencia visual, no diseño final). |

## Pendientes

- **Acceso de proveedores externos** a la bitácora (parte de RF-20): depende de la pregunta
  abierta 7 del BRIEF.
- **Carga del resto de recintos y de los convenios reales**: depende de las preguntas abiertas del
  BRIEF (listado de recintos, servicios clínicos y proveedores).
- **Selector de período global del Escritorio** (RF-21): bloqueado por una incompatibilidad de
  Filament 5.7.8 + Livewire 4.4.3. Mientras tanto, el gráfico de cumplimiento mensual del
  Escritorio y la página de Reportes permiten elegir el año.
