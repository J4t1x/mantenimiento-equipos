{{-- SPEC-ME-023: calendario de mantenciones en el Escritorio (mismo cuerpo que la página del recurso). --}}
<x-filament-widgets::widget>
    <div style="display: flex; justify-content: flex-end; margin-bottom: 0.75rem">
        <x-filament::link :href="$this->urlCalendarioCompleto()" :icon="\Filament\Support\Icons\Heroicon::ArrowTopRightOnSquare" icon-position="after">
            Abrir calendario completo
        </x-filament::link>
    </div>

    @include('filament.calendario.cuerpo')
</x-filament-widgets::widget>
