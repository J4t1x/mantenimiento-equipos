<?php

namespace App\Enums;

use Carbon\CarbonImmutable;
use Filament\Support\Contracts\HasLabel;

/**
 * RF-67 del SRS: período de un año para los indicadores de cumplimiento y los reportes de
 * cumplimiento y gasto. Trimestre por la respuesta 8 del requirente (informes trimestrales);
 * semestre por la Res. Ex. 1341/2017 del MINSAL (§8: enero–junio y año completo).
 *
 * RF-21 (cierre, Módulo 17): se agregan los 12 meses, porque el indicador de cumplimiento del
 * Escritorio pide "un período (mes o año)".
 */
enum Periodo: string implements HasLabel
{
    case Anual = 'anual';
    case PrimerSemestre = 's1';
    case SegundoSemestre = 's2';
    case PrimerTrimestre = 't1';
    case SegundoTrimestre = 't2';
    case TercerTrimestre = 't3';
    case CuartoTrimestre = 't4';
    case Enero = 'm01';
    case Febrero = 'm02';
    case Marzo = 'm03';
    case Abril = 'm04';
    case Mayo = 'm05';
    case Junio = 'm06';
    case Julio = 'm07';
    case Agosto = 'm08';
    case Septiembre = 'm09';
    case Octubre = 'm10';
    case Noviembre = 'm11';
    case Diciembre = 'm12';

    private const NOMBRES_MESES = [
        1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril', 5 => 'mayo', 6 => 'junio',
        7 => 'julio', 8 => 'agosto', 9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
    ];

    public function getLabel(): string
    {
        return match ($this) {
            self::Anual => 'Año completo',
            self::PrimerSemestre => '1er semestre (ene–jun)',
            self::SegundoSemestre => '2º semestre (jul–dic)',
            self::PrimerTrimestre => '1er trimestre (ene–mar)',
            self::SegundoTrimestre => '2º trimestre (abr–jun)',
            self::TercerTrimestre => '3er trimestre (jul–sep)',
            self::CuartoTrimestre => '4º trimestre (oct–dic)',
            default => ucfirst(self::NOMBRES_MESES[$this->mes()]),
        };
    }

    public function esMensual(): bool
    {
        return str_starts_with($this->value, 'm');
    }

    /**
     * Mes (1–12) de un período mensual; `null` en el resto.
     */
    public function mes(): ?int
    {
        return $this->esMensual() ? (int) substr($this->value, 1) : null;
    }

    /**
     * Opciones para un `Select` de Filament, agrupadas (año, semestres, trimestres, meses).
     *
     * @return array<string, array<string, string>>
     */
    public static function opcionesAgrupadas(): array
    {
        $grupos = ['Año' => [], 'Semestres' => [], 'Trimestres' => [], 'Meses' => []];

        foreach (self::cases() as $periodo) {
            $grupo = match (true) {
                $periodo->esAnual() => 'Año',
                str_starts_with($periodo->value, 's') => 'Semestres',
                str_starts_with($periodo->value, 't') => 'Trimestres',
                default => 'Meses',
            };

            $grupos[$grupo][$periodo->value] = $periodo->getLabel();
        }

        return $grupos;
    }

    /**
     * @return array<int, int>
     */
    public function meses(): array
    {
        return match ($this) {
            self::Anual => range(1, 12),
            self::PrimerSemestre => range(1, 6),
            self::SegundoSemestre => range(7, 12),
            self::PrimerTrimestre => range(1, 3),
            self::SegundoTrimestre => range(4, 6),
            self::TercerTrimestre => range(7, 9),
            self::CuartoTrimestre => range(10, 12),
            default => [$this->mes()],
        };
    }

    public function esAnual(): bool
    {
        return $this === self::Anual;
    }

    /**
     * Fracción del año que cubre el período (p. ej. 0,25 para un trimestre).
     */
    public function fraccionDelAnio(): float
    {
        return count($this->meses()) / 12;
    }

    public function inicio(int $anio): CarbonImmutable
    {
        return CarbonImmutable::create($anio, $this->meses()[0], 1)->startOfDay();
    }

    public function fin(int $anio): CarbonImmutable
    {
        $meses = $this->meses();

        return CarbonImmutable::create($anio, end($meses), 1)->endOfMonth()->startOfDay();
    }

    /**
     * Texto para frases como "4 de 10 programadas en {descripcion}".
     */
    public function descripcion(int $anio): string
    {
        return match ($this) {
            self::Anual => (string) $anio,
            self::PrimerSemestre => "el 1er semestre de {$anio}",
            self::SegundoSemestre => "el 2º semestre de {$anio}",
            self::PrimerTrimestre => "el 1er trimestre de {$anio}",
            self::SegundoTrimestre => "el 2º trimestre de {$anio}",
            self::TercerTrimestre => "el 3er trimestre de {$anio}",
            self::CuartoTrimestre => "el 4º trimestre de {$anio}",
            default => self::NOMBRES_MESES[$this->mes()]." de {$anio}",
        };
    }

    /**
     * Código corto para nombres de archivo y de hoja de Excel (máx. 31 caracteres por hoja).
     */
    public function codigo(): ?string
    {
        return $this->esAnual() ? null : strtoupper($this->value);
    }
}
