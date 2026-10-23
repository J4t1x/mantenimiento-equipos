{{--
    RF-38: grilla de 12 meses del plan. Los estilos van en esta vista (prefijo .bm-) y usan las
    variables de color de Filament: el panel no compila clases Tailwind propias (hallazgo del
    01-09-2026), por eso la versión anterior, hecha con utilidades Tailwind, se veía como texto
    corrido.
--}}
<div class="fi-resource-relation-manager">
    <style>
        .bm-resumen { display: flex; flex-wrap: wrap; gap: .5rem; margin-bottom: 1rem; }
        .bm-chip { display: inline-flex; align-items: center; gap: .375rem; padding: .25rem .625rem; border-radius: 9999px; font-size: .75rem; font-weight: 500; background: var(--gray-100); color: var(--gray-700); }
        .bm-chip strong { font-weight: 700; color: var(--gray-950); }
        .bm-grilla { display: grid; grid-template-columns: repeat(auto-fit, minmax(4.25rem, 1fr)); gap: .5rem; }
        .bm-celda { display: flex; flex-direction: column; align-items: center; gap: .25rem; padding: .5rem .25rem .625rem; border-radius: .75rem; border: 1px solid var(--gray-200); background: #fff; cursor: pointer; transition: box-shadow .15s, transform .15s, border-color .15s; font: inherit; }
        .bm-celda:hover:not(:disabled) { box-shadow: 0 4px 12px rgb(0 0 0 / .08); transform: translateY(-1px); border-color: var(--primary-300); }
        .bm-celda:focus-visible { outline: 2px solid var(--primary-500); outline-offset: 2px; }
        .bm-celda:disabled { cursor: default; }
        .bm-celda.bm-actual { border-color: var(--primary-500); box-shadow: 0 0 0 1px var(--primary-500); }
        .bm-mes { font-size: .6875rem; font-weight: 600; letter-spacing: .04em; text-transform: uppercase; color: var(--gray-500); }
        .bm-actual .bm-mes { color: var(--primary-600); }
        .bm-simbolo { display: flex; align-items: center; justify-content: center; width: 100%; height: 2.25rem; border-radius: .5rem; font-size: 1.125rem; font-weight: 700; }
        .bm-detalle { min-height: 1rem; font-size: .6875rem; color: var(--gray-500); white-space: nowrap; }
        .bm-sin_programar .bm-simbolo { background: var(--gray-50); color: var(--gray-400); }
        .bm-programado .bm-simbolo { background: var(--info-100); color: var(--info-700); }
        .bm-realizado .bm-simbolo { background: var(--success-100); color: var(--success-700); }
        .bm-reprogramado .bm-simbolo { background: var(--warning-100); color: var(--warning-700); }
        .bm-leyenda { display: flex; flex-wrap: wrap; gap: 1rem; margin-top: 1rem; font-size: .75rem; color: var(--gray-600); }
        .bm-leyenda span { display: inline-flex; align-items: center; gap: .375rem; }
        .bm-leyenda .bm-simbolo { width: 1.5rem; height: 1.5rem; font-size: .8125rem; border-radius: .375rem; }
        .bm-ayuda { margin-top: .75rem; font-size: .75rem; color: var(--gray-500); }
        .dark .bm-chip { background: rgb(255 255 255 / .06); color: var(--gray-300); }
        .dark .bm-chip strong { color: #fff; }
        .dark .bm-celda { background: var(--gray-900); border-color: rgb(255 255 255 / .1); }
        .dark .bm-sin_programar .bm-simbolo { background: rgb(255 255 255 / .04); color: var(--gray-500); }
        .dark .bm-programado .bm-simbolo { background: color-mix(in oklab, var(--info-500) 20%, transparent); color: var(--info-300); }
        .dark .bm-realizado .bm-simbolo { background: color-mix(in oklab, var(--success-500) 20%, transparent); color: var(--success-300); }
        .dark .bm-reprogramado .bm-simbolo { background: color-mix(in oklab, var(--warning-500) 20%, transparent); color: var(--warning-300); }
        .dark .bm-leyenda, .dark .bm-detalle, .dark .bm-ayuda { color: var(--gray-400); }
    </style>

    <x-filament::section>
        <x-slot name="heading">
            Bitácora mensual — {{ $anio }}
        </x-slot>

        <x-slot name="description">
            Ejecución mes a mes del plan. {{ $puedeGestionar ? 'Haz clic en un mes para registrar su ejecución.' : 'Solo lectura.' }}
        </x-slot>

        <div class="bm-resumen">
            <span class="bm-chip"><strong>{{ $resumen['programadas'] }}</strong> programadas</span>
            <span class="bm-chip"><strong>{{ $resumen['realizadas'] }}</strong> realizadas</span>
            <span class="bm-chip"><strong>{{ $resumen['reprogramadas'] }}</strong> reprogramadas</span>
            <span class="bm-chip">Cumplimiento <strong>{{ $resumen['porcentaje'] === null ? '—' : $resumen['porcentaje'].'%' }}</strong></span>
        </div>

        <div class="bm-grilla">
            @foreach ($mesesAbreviados as $mes => $abreviatura)
                @php
                    $ejecucion = $ejecucionesPorMes->get($mes);
                    $estado = $ejecucion?->estado ?? \App\Enums\EstadoEjecucion::SinProgramar;
                    $esMesActual = $anio === now()->year && $mes === now()->month;
                    $detalle = match (true) {
                        $estado === \App\Enums\EstadoEjecucion::Realizado && $ejecucion?->fecha_real !== null => $ejecucion->fecha_real->format('d-m'),
                        $estado === \App\Enums\EstadoEjecucion::SinProgramar => '',
                        default => $estado->getLabel(),
                    };
                @endphp

                <button
                    type="button"
                    @if ($puedeGestionar)
                        wire:click="mountAction('editarMes', { mes: {{ $mes }} })"
                    @else
                        disabled
                    @endif
                    title="{{ $mesesCompletos[$mes] }}: {{ $estado->getLabel() }}{{ $ejecucion?->fecha_real ? ' · '.$ejecucion->fecha_real->format('d-m-Y') : '' }}{{ $ejecucion?->observaciones ? ' · '.$ejecucion->observaciones : '' }}"
                    class="bm-celda bm-{{ $estado->value }} {{ $esMesActual ? 'bm-actual' : '' }}"
                >
                    <span class="bm-mes">{{ $abreviatura }}</span>
                    <span class="bm-simbolo">{{ $estado->simbolo() }}</span>
                    <span class="bm-detalle">{{ $detalle }}</span>
                </button>
            @endforeach
        </div>

        <div class="bm-leyenda">
            @foreach (\App\Enums\EstadoEjecucion::cases() as $caso)
                <span class="bm-{{ $caso->value }}">
                    <span class="bm-simbolo">{{ $caso->simbolo() }}</span>
                    {{ $caso->getLabel() }}
                </span>
            @endforeach
        </div>
    </x-filament::section>

    <x-filament-actions::modals />

    <x-filament-panels::unsaved-action-changes-alert />
</div>
