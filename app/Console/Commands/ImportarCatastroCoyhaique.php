<?php

namespace App\Console\Commands;

use App\Enums\Criticidad;
use App\Enums\EstadoEjecucion;
use App\Enums\EstadoEquipo;
use App\Enums\Propiedad;
use App\Enums\TipoMantenimiento;
use App\Models\ClaseEquipo;
use App\Models\Equipo;
use App\Models\PlanMantenimiento;
use App\Models\Proveedor;
use App\Models\Recinto;
use App\Models\ServicioClinico;
use DateTimeInterface;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Carga real del catastro del Hospital Regional Coyhaique desde la planilla vigente
 * (`resources/data/planilla-mantenimiento-equipos-medicos-2026.xlsx`, hoja "CATASTRO Y
 * PLANIFICACIÓN MP", fila 15 = encabezados, 469 filas con datos desde la fila 16).
 *
 * Decisiones de la carga:
 * - Recinto: uno solo, "Hospital Regional Coyhaique" (resuelve la pregunta abierta 1 del BRIEF
 *   *solo para este recinto*, no para el resto del Servicio de Salud Aysén).
 * - Servicio clínico: normalizado vía `resources/data/mapa_servicios_clinicos_coyhaique.php` (117
 *   variantes de texto libre → 55 canónicas). Pregunta abierta 3 del BRIEF sigue sin resolver
 *   formalmente — este mapeo es una simplificación práctica, no la validación oficial.
 * - Clase/Subclase: normalizadas por catálogo fijo (self::CLASES_SUBCLASES), 7 clases × sus
 *   subclases reales observadas en la planilla.
 * - Proveedor: un catálogo por nombre tal cual aparece en la planilla (sin fusionar variantes como
 *   "Andover (P)"/"Andover (G)"/"Andover (C)" — no hay forma de saber si son el mismo proveedor con
 *   distintos tipos de relación sin el listado maestro de la pregunta abierta 4).
 * - N° de inventario duplicado/placeholder ("N/A", "Comodato"): se sustituye por el N° de serie
 *   (único en esos casos); si aun así colisiona, se le agrega un sufijo numérico.
 * - Bitácora mensual: se cargan las marcas reales de la planilla (X/x = Programado, √ = Realizado;
 *   no se encontró ningún "ꓣ" Reprogramado en los datos), NO la distribución uniforme de RN-01 —
 *   por eso todo el import corre con `Model::withoutEvents()`, para que ni el observer de RN-01 ni
 *   la guarda de RN-02 (pensada para edición interactiva, no para carga masiva de historial ya
 *   ocurrido) interfieran. También evita 469+ entradas de auditoría falsas por un import, no una
 *   edición real de usuario.
 */
#[Signature('app:importar-catastro-coyhaique {archivo? : Ruta al .xlsx (por defecto, la planilla del repo)} {--force : No pedir confirmación si ya hay equipos cargados}')]
#[Description('Carga el catastro y plan de MP real del Hospital Regional Coyhaique desde la planilla Excel vigente.')]
class ImportarCatastroCoyhaique extends Command
{
    private const HOJA = 'CATASTRO Y PLANIFICACIÓN MP';

    private const FILA_ENCABEZADOS = 15;

    private const PRIMERA_FILA_DATOS = 16;

    /** Columnas 1-indexadas de la hoja, según la fila de encabezados. */
    private const COL = [
        'servicio_clinico' => 1,
        'recinto' => 2,
        'clase' => 3,
        'subclase' => 4,
        'nombre' => 5,
        'marca' => 6,
        'modelo' => 7,
        'serie' => 8,
        'n_inventario' => 9,
        'anio_adquisicion' => 10,
        'vida_util' => 11,
        'propiedad' => 13,
        'estado' => 14,
        'criticidad' => 15,
        'en_garantia' => 16,
        'garantia_vencimiento' => 17,
        'anio_ingreso_plan' => 19,
        'tipo_mantenimiento' => 20,
        'proveedor' => 21,
        'frecuencia_anual' => 24,
        'enero' => 25,
        'febrero' => 26,
        'marzo' => 27,
        'abril' => 28,
        'mayo' => 29,
        'junio' => 30,
        'julio' => 31,
        'agosto' => 32,
        'septiembre' => 33,
        'octubre' => 34,
        'noviembre' => 35,
        'diciembre' => 36,
    ];

    private const MESES = [
        'enero' => 1, 'febrero' => 2, 'marzo' => 3, 'abril' => 4, 'mayo' => 5, 'junio' => 6,
        'julio' => 7, 'agosto' => 8, 'septiembre' => 9, 'octubre' => 10, 'noviembre' => 11, 'diciembre' => 12,
    ];

    /**
     * Pares clase → lista de subclases realmente observadas en la planilla (MODELO-DATOS §3.3:
     * la subclase es una fila hija de `clases_equipo`, una por cada combinación real).
     *
     * @var array<string, array<int, string>>
     */
    private const CLASES_SUBCLASES = [
        'Monitoreo' => ['Alto Costo'],
        'Apoyo Terapéutico' => ['Alto Costo'],
        'Imagenología' => ['Alto Costo'],
        'Lab/Farmacia' => ['Mediano Costo', 'Alto Costo', 'Bajo Costo'],
        'Esterilización' => ['Alto Costo'],
        'Odontología' => ['Mediano Costo'],
        'UTIP' => ['Alto Costo'],
    ];

    /** @var array<int, ClaseEquipo> clave = "clase|subclase" normalizada */
    private array $clasesCache = [];

    /** @var array<string, ServicioClinico> */
    private array $serviciosCache = [];

    /** @var array<string, Proveedor> */
    private array $proveedoresCache = [];

    /** @var array<string, true> claves "recinto_id|n_inventario" ya usadas en este import */
    private array $inventariosUsados = [];

    /** @var array<int, array{fila: int, original: string, sustituto: string}> */
    private array $inventariosSustituidos = [];

    private int $planesCreados = 0;

    private int $ejecucionesCreadas = 0;

    public function handle(): int
    {
        $ruta = $this->argument('archivo') ?? resource_path('data/planilla-mantenimiento-equipos-medicos-2026.xlsx');

        if (! is_string($ruta) || ! file_exists($ruta)) {
            $this->error("No se encontró el archivo: {$ruta}");

            return self::FAILURE;
        }

        if (Equipo::query()->count() > 0 && ! $this->option('force')) {
            $this->error('Ya hay equipos cargados. Vuelve a correr con --force si de verdad quieres importar de nuevo (puede duplicar registros).');

            return self::FAILURE;
        }

        /** @var array<string, string> $mapaServicios */
        $mapaServicios = require resource_path('data/mapa_servicios_clinicos_coyhaique.php');

        $this->info("Leyendo {$ruta}...");
        $hoja = IOFactory::load($ruta)->getSheetByName(self::HOJA);

        if ($hoja === null) {
            $this->error('No se encontró la hoja "'.self::HOJA.'".');

            return self::FAILURE;
        }

        $filasImportadas = 0;
        $filasOmitidas = [];

        Model::withoutEvents(function () use ($hoja, $mapaServicios, &$filasImportadas, &$filasOmitidas): void {
            DB::transaction(function () use ($hoja, $mapaServicios, &$filasImportadas, &$filasOmitidas): void {
                $ultimaFila = $hoja->getHighestDataRow();

                for ($fila = self::PRIMERA_FILA_DATOS; $fila <= $ultimaFila; $fila++) {
                    $nombreEquipo = $this->valor($hoja, 'nombre', $fila);

                    if ($nombreEquipo === null || trim((string) $nombreEquipo) === '') {
                        continue;
                    }

                    try {
                        $this->importarFila($hoja, $fila, $mapaServicios);
                        $filasImportadas++;
                    } catch (\Throwable $exception) {
                        $filasOmitidas[] = "Fila {$fila}: {$exception->getMessage()}";
                    }
                }
            });
        });

        $this->newLine();
        $this->info("Filas importadas: {$filasImportadas}");
        $this->info('Recintos: '.Recinto::count().' · Servicios clínicos: '.ServicioClinico::count().' · Clases/subclases: '.ClaseEquipo::count().' · Proveedores: '.Proveedor::count());
        $this->info("Equipos: {$filasImportadas} · Planes de mantenimiento: {$this->planesCreados} · Ejecuciones mensuales: {$this->ejecucionesCreadas}");

        if ($this->inventariosSustituidos !== []) {
            $this->newLine();
            $this->warn('N° de inventario sustituido por el N° de serie (placeholder o duplicado en la planilla):');
            foreach ($this->inventariosSustituidos as $s) {
                $this->line("  Fila {$s['fila']}: \"{$s['original']}\" → \"{$s['sustituto']}\"");
            }
        }

        if ($filasOmitidas !== []) {
            $this->newLine();
            $this->error('Filas omitidas por error:');
            foreach ($filasOmitidas as $linea) {
                $this->line("  {$linea}");
            }
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<string, string>  $mapaServicios
     */
    private function importarFila(Worksheet $hoja, int $fila, array $mapaServicios): void
    {
        $recinto = $this->recinto((string) $this->valor($hoja, 'recinto', $fila));
        $servicioClinico = $this->servicioClinico((string) $this->valor($hoja, 'servicio_clinico', $fila), $mapaServicios);
        $clase = $this->clase(
            (string) $this->valor($hoja, 'clase', $fila),
            (string) $this->valor($hoja, 'subclase', $fila),
        );

        $nInventario = $this->nInventario(
            (string) $this->valor($hoja, 'n_inventario', $fila),
            (string) ($this->valor($hoja, 'serie', $fila) ?? ''),
            $recinto,
            $fila,
        );

        $estado = $this->estado((string) ($this->valor($hoja, 'estado', $fila) ?? ''));

        $equipo = Equipo::create([
            'recinto_id' => $recinto->id,
            'servicio_clinico_id' => $servicioClinico->id,
            'clase_id' => $clase['clase']->id,
            'subclase_id' => $clase['subclase']?->id,
            'nombre' => trim((string) $this->valor($hoja, 'nombre', $fila)),
            'marca' => $this->texto($hoja, 'marca', $fila),
            'modelo' => $this->texto($hoja, 'modelo', $fila),
            'serie' => $this->texto($hoja, 'serie', $fila),
            'n_inventario' => $nInventario,
            'anio_adquisicion' => $this->entero($hoja, 'anio_adquisicion', $fila),
            'vida_util_anios' => $this->entero($hoja, 'vida_util', $fila),
            'propiedad' => $this->propiedad((string) $this->valor($hoja, 'propiedad', $fila)),
            ...($estado !== null ? ['estado' => $estado] : []),
            'criticidad' => $this->criticidad((string) $this->valor($hoja, 'criticidad', $fila)),
            'en_garantia' => $this->esSi($this->valor($hoja, 'en_garantia', $fila)),
            'garantia_anio_vencimiento' => $this->anioGarantia($this->valor($hoja, 'garantia_vencimiento', $fila)),
            'bajo_plan_mp' => true,
            'anio_ingreso_plan' => $this->entero($hoja, 'anio_ingreso_plan', $fila) ?? 2026,
            'activo' => true,
        ]);

        $tipoMantenimiento = $this->tipoMantenimiento((string) $this->valor($hoja, 'tipo_mantenimiento', $fila));
        $proveedorNombre = trim((string) ($this->valor($hoja, 'proveedor', $fila) ?? ''));
        $proveedor = ($tipoMantenimiento === TipoMantenimiento::Externo && $proveedorNombre !== '' && ! $this->esInterna($proveedorNombre))
            ? $this->proveedor($proveedorNombre)
            : null;

        $frecuencia = $this->frecuencia((string) $this->valor($hoja, 'frecuencia_anual', $fila));

        if ($frecuencia === null) {
            return;
        }

        $plan = PlanMantenimiento::create([
            'equipo_id' => $equipo->id,
            'anio' => $equipo->anio_ingreso_plan,
            'frecuencia_anual' => $frecuencia,
            'tipo_mantenimiento' => $tipoMantenimiento->value,
            'proveedor_id' => $proveedor?->id,
        ]);
        $this->planesCreados++;

        foreach (self::MESES as $columna => $mes) {
            $estado = $this->estadoEjecucion($this->valor($hoja, $columna, $fila));

            if ($estado === null) {
                continue;
            }

            $plan->ejecucionesMensuales()->create([
                'mes' => $mes,
                'estado' => $estado->value,
            ]);
            $this->ejecucionesCreadas++;
        }
    }

    private function valor(Worksheet $hoja, string $columna, int $fila): mixed
    {
        $letra = Coordinate::stringFromColumnIndex(self::COL[$columna]);

        return $hoja->getCell("{$letra}{$fila}")->getValue();
    }

    private function texto(Worksheet $hoja, string $columna, int $fila): ?string
    {
        $valor = $this->valor($hoja, $columna, $fila);
        $texto = trim((string) ($valor ?? ''));

        return $texto === '' ? null : $texto;
    }

    private function entero(Worksheet $hoja, string $columna, int $fila): ?int
    {
        $valor = $this->valor($hoja, $columna, $fila);

        if ($valor === null || trim((string) $valor) === '') {
            return null;
        }

        return (int) round((float) $valor);
    }

    private function recinto(string $nombre): Recinto
    {
        $nombre = trim($nombre) !== '' ? trim($nombre) : 'Hospital Regional Coyhaique';

        return Recinto::query()->firstOrCreate(['nombre' => $nombre]);
    }

    private function servicioClinico(string $raw, array $mapa): ServicioClinico
    {
        $raw = trim($raw);
        $canonico = $mapa[$raw] ?? $raw;

        if (! isset($this->serviciosCache[$canonico])) {
            $this->serviciosCache[$canonico] = ServicioClinico::query()->firstOrCreate(['nombre' => $canonico]);
        }

        return $this->serviciosCache[$canonico];
    }

    /**
     * @return array{clase: ClaseEquipo, subclase: ?ClaseEquipo}
     */
    private function clase(string $claseRaw, string $subclaseRaw): array
    {
        $claseNombre = $this->normalizarClase(trim($claseRaw));
        $subclaseNombre = $this->normalizarSubclase(trim($subclaseRaw));

        $claveClase = "clase|{$claseNombre}";

        if (! isset($this->clasesCache[$claveClase])) {
            $this->clasesCache[$claveClase] = ClaseEquipo::query()->firstOrCreate([
                'nombre' => $claseNombre,
                'clase_padre_id' => null,
            ]);
        }

        $clase = $this->clasesCache[$claveClase];

        if ($subclaseNombre === null) {
            return ['clase' => $clase, 'subclase' => null];
        }

        $claveSubclase = "subclase|{$claseNombre}|{$subclaseNombre}";

        if (! isset($this->clasesCache[$claveSubclase])) {
            $this->clasesCache[$claveSubclase] = ClaseEquipo::query()->firstOrCreate([
                'nombre' => $subclaseNombre,
                'clase_padre_id' => $clase->id,
            ]);
        }

        return ['clase' => $clase, 'subclase' => $this->clasesCache[$claveSubclase]];
    }

    private function normalizarClase(string $raw): string
    {
        $clave = $this->claveCanonica($raw);

        return match (true) {
            $clave === 'monitoreo' => 'Monitoreo',
            str_starts_with($clave, 'apoyo terapeutico') => 'Apoyo Terapéutico',
            $clave === 'lab/farmacia' => 'Lab/Farmacia',
            str_starts_with($clave, 'imagenolog') => 'Imagenología',
            str_starts_with($clave, 'esterilizac') => 'Esterilización',
            str_starts_with($clave, 'odontolog') => 'Odontología',
            $clave === 'utip' => 'UTIP',
            default => trim($raw) !== '' ? trim($raw) : 'Sin clasificar',
        };
    }

    private function normalizarSubclase(string $raw): ?string
    {
        if (trim($raw) === '') {
            return null;
        }

        $clave = $this->claveCanonica($raw);

        return match (true) {
            $clave === 'alto costo' => 'Alto Costo',
            $clave === 'mediano costo' => 'Mediano Costo',
            $clave === 'bajo costo' => 'Bajo Costo',
            default => trim($raw),
        };
    }

    private function claveCanonica(string $s): string
    {
        $s = mb_strtolower(trim($s));
        $s = strtr($s, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u']);

        return preg_replace('/\s+/', ' ', $s);
    }

    private function nInventario(string $raw, string $serie, Recinto $recinto, int $fila): string
    {
        $raw = trim($raw);
        $esPlaceholder = in_array(mb_strtolower($raw), ['', 'n/a', 'comodato'], true);
        $valor = $esPlaceholder ? trim($serie) : $raw;

        if ($valor === '') {
            $valor = "SIN-INVENTARIO-{$fila}";
        }

        $clave = "{$recinto->id}|{$valor}";
        $sufijo = 2;
        $valorFinal = $valor;

        while (isset($this->inventariosUsados[$this->claveCanonica("{$recinto->id}|{$valorFinal}")])) {
            $valorFinal = "{$valor}-{$sufijo}";
            $sufijo++;
        }

        $this->inventariosUsados[$this->claveCanonica("{$recinto->id}|{$valorFinal}")] = true;

        if ($esPlaceholder || $valorFinal !== $raw) {
            $this->inventariosSustituidos[] = ['fila' => $fila, 'original' => $raw, 'sustituto' => $valorFinal];
        }

        return $valorFinal;
    }

    private function propiedad(string $raw): string
    {
        return match ($this->claveCanonica($raw)) {
            'arriendo' => Propiedad::Arriendo->value,
            'comodato' => Propiedad::Comodato->value,
            'prestamo', 'préstamo' => Propiedad::Prestamo->value,
            default => Propiedad::Propio->value,
        };
    }

    private function estado(?string $raw): ?string
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        return match ($this->claveCanonica($raw)) {
            'bueno' => EstadoEquipo::Bueno->value,
            'regular' => EstadoEquipo::Regular->value,
            'malo' => EstadoEquipo::Malo->value,
            default => null,
        };
    }

    private function criticidad(string $raw): string
    {
        $clave = $this->claveCanonica($raw);

        return match (true) {
            str_contains($clave, 'critico') => Criticidad::Critico->value,
            str_contains($clave, 'relevante') => Criticidad::Relevante->value,
            str_contains($clave, 'im') => Criticidad::ImMayorIgual12->value,
            default => Criticidad::NoAplica->value,
        };
    }

    private function esSi(mixed $valor): bool
    {
        return $this->claveCanonica((string) ($valor ?? '')) === 'si';
    }

    private function anioGarantia(mixed $valor): ?int
    {
        if ($valor instanceof DateTimeInterface) {
            return (int) $valor->format('Y');
        }

        $texto = trim((string) ($valor ?? ''));

        if ($texto === '') {
            return null;
        }

        // Formato observado en la planilla: "DD.MM.YY".
        if (preg_match('/\.(\d{2})$/', $texto, $m)) {
            return 2000 + (int) $m[1];
        }

        if (preg_match('/(\d{4})/', $texto, $m)) {
            return (int) $m[1];
        }

        return null;
    }

    private function tipoMantenimiento(string $raw): TipoMantenimiento
    {
        return $this->esInterna($raw) ? TipoMantenimiento::Interno : TipoMantenimiento::Externo;
    }

    private function esInterna(string $raw): bool
    {
        $clave = $this->claveCanonica($raw);

        return str_starts_with($clave, 'interno') || str_starts_with($clave, 'interna');
    }

    private function proveedor(string $nombre): Proveedor
    {
        $nombre = trim($nombre);

        if (! isset($this->proveedoresCache[$nombre])) {
            $this->proveedoresCache[$nombre] = Proveedor::query()->firstOrCreate(['nombre' => $nombre]);
        }

        return $this->proveedoresCache[$nombre];
    }

    private function frecuencia(string $raw): ?string
    {
        $valor = trim($raw);

        return in_array($valor, ['1', '2', '3', '4', '6', '12'], true) ? $valor : null;
    }

    private function estadoEjecucion(mixed $valor): ?EstadoEjecucion
    {
        $texto = trim((string) ($valor ?? ''));

        return match (true) {
            $texto === '' => null,
            mb_strtolower($texto) === 'x' => EstadoEjecucion::Programado,
            $texto === '√' => EstadoEjecucion::Realizado,
            $texto === 'ꓣ' => EstadoEjecucion::Reprogramado,
            default => null,
        };
    }
}
