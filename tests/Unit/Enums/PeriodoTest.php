<?php

namespace Tests\Unit\Enums;

use App\Enums\Periodo;
use PHPUnit\Framework\TestCase;

/**
 * RF-67: meses, fechas límite y textos de cada período del Escritorio y los Reportes.
 */
class PeriodoTest extends TestCase
{
    public function test_cada_periodo_cubre_sus_meses(): void
    {
        $this->assertSame(range(1, 12), Periodo::Anual->meses());
        $this->assertSame(range(7, 12), Periodo::SegundoSemestre->meses());
        $this->assertSame([4, 5, 6], Periodo::SegundoTrimestre->meses());
        $this->assertSame([11], Periodo::Noviembre->meses());
        $this->assertSame(0.25, Periodo::CuartoTrimestre->fraccionDelAnio());
    }

    public function test_inicio_y_fin_del_periodo(): void
    {
        $this->assertSame('2026-07-01', Periodo::SegundoSemestre->inicio(2026)->toDateString());
        $this->assertSame('2026-12-31', Periodo::SegundoSemestre->fin(2026)->toDateString());
        $this->assertSame('2028-02-29', Periodo::Febrero->fin(2028)->toDateString(), 'Año bisiesto.');
    }

    public function test_textos_y_codigos(): void
    {
        $this->assertSame('2026', Periodo::Anual->descripcion(2026));
        $this->assertSame('el 1er trimestre de 2026', Periodo::PrimerTrimestre->descripcion(2026));
        $this->assertNull(Periodo::Anual->codigo());
        $this->assertSame('T3', Periodo::TercerTrimestre->codigo());
    }
}
