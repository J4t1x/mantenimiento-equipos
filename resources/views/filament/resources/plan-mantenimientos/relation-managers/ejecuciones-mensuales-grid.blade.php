<div class="fi-resource-relation-manager">
    <x-filament::section>
        <x-slot name="heading">
            Bitácora mensual — {{ $anio }}
        </x-slot>

        <div class="grid grid-cols-4 gap-2 sm:grid-cols-6 lg:grid-cols-12">
            @foreach ($mesesAbreviados as $mes => $abreviatura)
                @php
                    $ejecucion = $ejecucionesPorMes->get($mes);
                    $estado = $ejecucion?->estado ?? \App\Enums\EstadoEjecucion::SinProgramar;
                @endphp

                <button
                    type="button"
                    @if ($puedeGestionar)
                        wire:click="mountAction('editarMes', { mes: {{ $mes }} })"
                    @else
                        disabled
                    @endif
                    title="{{ $estado->getLabel() }}{{ $ejecucion?->fecha_real ? ' · '.$ejecucion->fecha_real->format('d-m-Y') : '' }}"
                    class="flex flex-col items-center gap-1 rounded-lg p-1 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:hover:bg-transparent dark:hover:bg-white/5"
                >
                    <span class="text-[10px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        {{ $abreviatura }}
                    </span>

                    <x-filament::badge :color="$estado->getColor()" class="h-7 w-full items-center justify-center font-bold">
                        {{ $simbolos[$estado->value] }}
                    </x-filament::badge>
                </button>
            @endforeach
        </div>

        <div class="mt-4 flex flex-wrap gap-4 text-xs text-gray-500 dark:text-gray-400">
            @foreach (\App\Enums\EstadoEjecucion::cases() as $caso)
                <span class="inline-flex items-center gap-1.5">
                    <x-filament::badge :color="$caso->getColor()" class="h-4 w-4 items-center justify-center p-0" />
                    {{ $caso->getLabel() }}
                </span>
            @endforeach
        </div>
    </x-filament::section>

    <x-filament-actions::modals />

    <x-filament-panels::unsaved-action-changes-alert />
</div>
