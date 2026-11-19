{{--
    SPEC-ME-023: cuerpo del calendario de mantenciones, compartido por la página del recurso
    (CalendarioPlanes) y el widget del Escritorio (CalendarioMantencionesWidget). Lo arma el trait
    App\Filament\Support\CalendarioDeMantenciones.

    Lo programado (pendiente) se dibuja con borde punteado y lo ejecutado con relleno verde y ✓,
    para distinguirlos de un vistazo. Colores institucionales: azul #0060AF (programada), rojo
    #E2001A (atrasada), ámbar (reprogramada) y verde (realizada). Los estilos van en la vista porque
    el panel no compila un tema propio; las clases llevan el prefijo "cal-" para no chocar con
    Filament. Los comentarios van en Blade y no dentro del <style> (ReportesTest).
--}}
<style>
    .cal { --cal-azul: #0060AF; --cal-rojo: #E2001A; --cal-ambar: #D97706; --cal-verde: #059669; --cal-borde: rgb(226 232 240); --cal-fondo: #FFFFFF; --cal-suave: rgb(248 250 252); --cal-texto: rgb(15 23 42); --cal-tenue: rgb(100 116 139); display: flex; flex-direction: column; gap: 1.25rem; }
    .dark .cal { --cal-borde: rgb(255 255 255 / 0.1); --cal-fondo: rgb(15 23 42); --cal-suave: rgb(30 41 59 / 0.6); --cal-texto: rgb(241 245 249); --cal-tenue: rgb(148 163 184); }
    .cal-barra { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem; padding: 0.75rem; border-radius: 0.875rem; background: var(--cal-fondo); box-shadow: 0 0 0 1px var(--cal-borde), 0 1px 2px rgb(0 0 0 / 0.04); }
    .cal-nav { display: flex; align-items: center; gap: 0.5rem; }
    .cal-nav-titulo { min-width: 11rem; text-align: center; font-size: 1.125rem; font-weight: 700; color: var(--cal-texto); letter-spacing: -0.01em; }
    .cal-btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.35rem; height: 2.25rem; min-width: 2.25rem; padding: 0 0.75rem; border-radius: 0.6rem; font-size: 0.85rem; font-weight: 600; color: var(--cal-texto); background: var(--cal-suave); box-shadow: inset 0 0 0 1px var(--cal-borde); transition: background-color 120ms, box-shadow 120ms; cursor: pointer; }
    .cal-btn:hover { background: rgb(0 96 175 / 0.08); box-shadow: inset 0 0 0 1px rgb(0 96 175 / 0.35); }
    .cal-btn svg { width: 1rem; height: 1rem; }
    .cal-filtros { display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; }
    .cal-filtros select { height: 2.25rem; border-radius: 0.6rem; border: 0; padding: 0 2rem 0 0.75rem; font-size: 0.85rem; color: var(--cal-texto); background-color: var(--cal-suave); box-shadow: inset 0 0 0 1px var(--cal-borde); max-width: 13rem; }
    .cal-limpiar { font-size: 0.8rem; font-weight: 600; color: var(--cal-azul); cursor: pointer; }
    .cal-leyenda { display: flex; flex-wrap: wrap; gap: 1rem; font-size: 0.8rem; color: var(--cal-tenue); }
    .cal-leyenda span { display: inline-flex; align-items: center; gap: 0.4rem; }
    .cal-muestra { width: 0.95rem; height: 0.95rem; border-radius: 0.3rem; border: 2px dashed currentColor; }
    .cal-muestra-llena { border-style: solid; background: currentColor; }
    .cal-c-programada { color: var(--cal-azul); } .cal-c-atrasada { color: var(--cal-rojo); } .cal-c-reprogramada { color: var(--cal-ambar); } .cal-c-realizada { color: var(--cal-verde); }
    .cal-cargando { transition: opacity 150ms; }
    .cal-cargando[data-cargando] { opacity: 0.55; pointer-events: none; }

    .cal-anio { display: grid; gap: 1rem; grid-template-columns: repeat(1, minmax(0, 1fr)); }
    @media (min-width: 640px) { .cal-anio { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (min-width: 1024px) { .cal-anio { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    @media (min-width: 1280px) { .cal-anio { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
    .cal-mes { position: relative; display: flex; flex-direction: column; gap: 0.75rem; padding: 1rem 1.1rem; text-align: left; border-radius: 0.875rem; background: var(--cal-fondo); box-shadow: 0 0 0 1px var(--cal-borde), 0 1px 2px rgb(0 0 0 / 0.04); transition: transform 150ms, box-shadow 150ms; cursor: pointer; overflow: hidden; }
    .cal-mes:hover { transform: translateY(-2px); box-shadow: 0 0 0 1px rgb(0 96 175 / 0.4), 0 10px 24px -12px rgb(0 60 113 / 0.35); }
    .cal-mes-actual { box-shadow: 0 0 0 2px var(--cal-azul), 0 10px 24px -14px rgb(0 60 113 / 0.45); }
    .cal-mes-actual::before { content: ""; position: absolute; inset: 0 0 auto 0; height: 4px; background: linear-gradient(90deg, #003C71, #0060AF 60%, #E2001A); }
    .cal-mes-cab { display: flex; align-items: baseline; justify-content: space-between; gap: 0.5rem; }
    .cal-mes-nombre { font-size: 1rem; font-weight: 700; color: var(--cal-texto); }
    .cal-mes-insignia { font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #FFFFFF; background: var(--cal-azul); border-radius: 9999px; padding: 0.15rem 0.5rem; }
    .cal-mes-total { font-size: 1.75rem; font-weight: 800; line-height: 1; color: var(--cal-texto); letter-spacing: -0.02em; }
    .cal-mes-total small { font-size: 0.78rem; font-weight: 500; color: var(--cal-tenue); letter-spacing: 0; margin-left: 0.35rem; }
    .cal-avance { height: 0.4rem; border-radius: 9999px; background: var(--cal-suave); box-shadow: inset 0 0 0 1px var(--cal-borde); overflow: hidden; }
    .cal-avance > div { height: 100%; border-radius: 9999px; background: linear-gradient(90deg, #10B981, var(--cal-verde)); }
    .cal-cifras { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.35rem 0.75rem; font-size: 0.8rem; color: var(--cal-tenue); }
    .cal-cifras span { display: flex; align-items: center; justify-content: space-between; gap: 0.4rem; }
    .cal-cifras b { color: var(--cal-texto); font-weight: 700; }
    .cal-punto { width: 0.55rem; height: 0.55rem; border-radius: 0.2rem; border: 1.5px dashed currentColor; flex: none; }
    .cal-punto-lleno { border-style: solid; background: currentColor; }
    .cal-cifras .cal-etq { display: inline-flex; align-items: center; gap: 0.35rem; justify-content: flex-start; }

    .cal-mesvista { display: grid; gap: 1rem; grid-template-columns: minmax(0, 1fr); }
    @media (min-width: 1280px) { .cal-mesvista { grid-template-columns: minmax(0, 1fr) 22rem; } }
    .cal-resumen { display: flex; flex-wrap: wrap; gap: 0.5rem; }
    .cal-resumen > span { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.35rem 0.7rem; border-radius: 9999px; font-size: 0.8rem; font-weight: 600; background: var(--cal-fondo); box-shadow: 0 0 0 1px var(--cal-borde); color: var(--cal-texto); }
    .cal-grilla { border-radius: 0.875rem; background: var(--cal-fondo); box-shadow: 0 0 0 1px var(--cal-borde), 0 1px 2px rgb(0 0 0 / 0.04); overflow: hidden; }
    .cal-dias-cab, .cal-semana { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); }
    .cal-dias-cab div { padding: 0.6rem 0.5rem; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #FFFFFF; background: linear-gradient(90deg, #002B55, #003C71 50%, #0060AF); text-align: center; }
    .cal-dia { min-height: 6.5rem; padding: 0.4rem; border-right: 1px solid var(--cal-borde); border-bottom: 1px solid var(--cal-borde); display: flex; flex-direction: column; gap: 0.25rem; }
    .cal-semana .cal-dia:last-child { border-right: 0; }
    .cal-dia-fuera { background: var(--cal-suave); }
    .cal-dia-fuera .cal-dia-num { opacity: 0.4; }
    .cal-dia-num { font-size: 0.78rem; font-weight: 600; color: var(--cal-tenue); width: 1.6rem; height: 1.6rem; display: inline-flex; align-items: center; justify-content: center; border-radius: 9999px; }
    .cal-dia-hoy .cal-dia-num { color: #FFFFFF; background: var(--cal-rojo); }
    .cal-ev { display: flex; align-items: center; gap: 0.3rem; padding: 0.15rem 0.4rem; border-radius: 0.4rem; font-size: 0.72rem; font-weight: 600; line-height: 1.3; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #FFFFFF; background: var(--cal-verde); }
    .cal-ev:hover { filter: brightness(1.08); }
    .cal-mas { font-size: 0.7rem; font-weight: 600; color: var(--cal-tenue); padding-left: 0.3rem; }

    .cal-panel { display: flex; flex-direction: column; gap: 1rem; }
    .cal-caja { border-radius: 0.875rem; background: var(--cal-fondo); box-shadow: 0 0 0 1px var(--cal-borde), 0 1px 2px rgb(0 0 0 / 0.04); overflow: hidden; }
    .cal-caja h3 { display: flex; align-items: center; justify-content: space-between; padding: 0.75rem 1rem; font-size: 0.85rem; font-weight: 700; color: var(--cal-texto); border-bottom: 1px solid var(--cal-borde); }
    .cal-caja h3 small { font-size: 0.75rem; font-weight: 600; color: var(--cal-tenue); }
    .cal-lista { max-height: 26rem; overflow-y: auto; padding: 0.5rem; display: flex; flex-direction: column; gap: 0.4rem; }
    .cal-item { display: flex; flex-direction: column; gap: 0.15rem; padding: 0.5rem 0.65rem; border-radius: 0.55rem; border: 1.5px dashed currentColor; background: color-mix(in srgb, currentColor 6%, transparent); transition: background-color 120ms; }
    .cal-item:hover { background: color-mix(in srgb, currentColor 12%, transparent); }
    .cal-item-realizada { border-style: solid; }
    .cal-item-fila { display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; }
    .cal-item-equipo { font-size: 0.8rem; font-weight: 600; color: var(--cal-texto); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .cal-item-detalle { font-size: 0.72rem; color: var(--cal-tenue); }
    .cal-item-estado { font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; flex: none; }
    .cal-critico { display: inline-block; width: 0.45rem; height: 0.45rem; border-radius: 9999px; background: var(--cal-rojo); margin-right: 0.3rem; vertical-align: middle; }
    .cal-vacio { padding: 1rem; font-size: 0.8rem; color: var(--cal-tenue); text-align: center; }
</style>

<div class="cal">
    <div class="cal-barra">
        <div class="cal-nav">
            @if ($this->mes === null)
                @if ($this->puedeCambiarDeAnio())
                    <button type="button" class="cal-btn" wire:click="$set('anio', {{ $this->anioCalendario() - 1 }})" title="Año anterior">
                        <x-filament::icon :icon="\Filament\Support\Icons\Heroicon::ChevronLeft" />
                    </button>
                @endif
                <div class="cal-nav-titulo">Año {{ $this->anioCalendario() }}</div>
                @if ($this->puedeCambiarDeAnio())
                    <button type="button" class="cal-btn" wire:click="$set('anio', {{ $this->anioCalendario() + 1 }})" title="Año siguiente">
                        <x-filament::icon :icon="\Filament\Support\Icons\Heroicon::ChevronRight" />
                    </button>
                @endif
            @else
                <button type="button" class="cal-btn" wire:click="verAnio">
                    <x-filament::icon :icon="\Filament\Support\Icons\Heroicon::Squares2x2" />
                    Año {{ $this->anioCalendario() }}
                </button>
                <button type="button" class="cal-btn" wire:click="mesAnterior" title="Mes anterior">
                    <x-filament::icon :icon="\Filament\Support\Icons\Heroicon::ChevronLeft" />
                </button>
                <div class="cal-nav-titulo">{{ $this->nombreMes($this->mes) }} {{ $this->anioCalendario() }}</div>
                <button type="button" class="cal-btn" wire:click="mesSiguiente" title="Mes siguiente">
                    <x-filament::icon :icon="\Filament\Support\Icons\Heroicon::ChevronRight" />
                </button>
            @endif
        </div>

        <div class="cal-filtros">
            <select wire:model.live="servicio" aria-label="Servicio clínico">
                <option value="">Todos los servicios</option>
                @foreach ($this->opcionesServicio() as $id => $nombre)
                    <option value="{{ $id }}">{{ $nombre }}</option>
                @endforeach
            </select>
            <select wire:model.live="criticidad" aria-label="Criticidad">
                <option value="">Toda criticidad</option>
                @foreach ($this->opcionesCriticidad() as $valor => $etiqueta)
                    <option value="{{ $valor }}">{{ $etiqueta }}</option>
                @endforeach
            </select>
            <select wire:model.live="tipo" aria-label="Tipo de mantenimiento">
                <option value="">Interno y externo</option>
                @foreach ($this->opcionesTipo() as $valor => $etiqueta)
                    <option value="{{ $valor }}">{{ $etiqueta }}</option>
                @endforeach
            </select>
            @if ($this->hayFiltros())
                <button type="button" class="cal-limpiar" wire:click="limpiarFiltros">Limpiar filtros</button>
            @endif
        </div>
    </div>

    <div class="cal-leyenda">
        <span class="cal-c-programada"><i class="cal-muestra"></i><span style="color: var(--cal-tenue)">Programada</span></span>
        <span class="cal-c-atrasada"><i class="cal-muestra"></i><span style="color: var(--cal-tenue)">Atrasada</span></span>
        <span class="cal-c-reprogramada"><i class="cal-muestra"></i><span style="color: var(--cal-tenue)">Reprogramada</span></span>
        <span class="cal-c-realizada"><i class="cal-muestra cal-muestra-llena"></i><span style="color: var(--cal-tenue)">Realizada (ejecutada)</span></span>
    </div>

    <div class="cal-cargando" wire:loading.attr="data-cargando">
        @if ($this->mes === null)
            <div class="cal-anio">
                @foreach ($this->resumenAnual() as $resumen)
                    <button type="button" wire:click="verMes({{ $resumen['mes'] }})" @class(['cal-mes', 'cal-mes-actual' => $resumen['esActual']])>
                        <div class="cal-mes-cab">
                            <span class="cal-mes-nombre">{{ $resumen['nombre'] }}</span>
                            @if ($resumen['esActual'])
                                <span class="cal-mes-insignia">Mes actual</span>
                            @endif
                        </div>
                        <div class="cal-mes-total">
                            {{ $resumen['total'] }}<small>{{ $resumen['total'] === 1 ? 'mantención' : 'mantenciones' }}</small>
                        </div>
                        <div class="cal-avance" title="{{ $resumen['avance'] }}% realizado">
                            <div style="width: {{ $resumen['avance'] }}%"></div>
                        </div>
                        <div class="cal-cifras">
                            <span><span class="cal-etq cal-c-realizada"><i class="cal-punto cal-punto-lleno"></i><span style="color: var(--cal-tenue)">Realizadas</span></span><b>{{ $resumen['realizadas'] }}</b></span>
                            <span><span class="cal-etq cal-c-programada"><i class="cal-punto"></i><span style="color: var(--cal-tenue)">Programadas</span></span><b>{{ $resumen['programadas'] }}</b></span>
                            <span><span class="cal-etq cal-c-atrasada"><i class="cal-punto"></i><span style="color: var(--cal-tenue)">Atrasadas</span></span><b>{{ $resumen['atrasadas'] }}</b></span>
                            <span><span class="cal-etq cal-c-reprogramada"><i class="cal-punto"></i><span style="color: var(--cal-tenue)">Reprogram.</span></span><b>{{ $resumen['reprogramadas'] }}</b></span>
                        </div>
                    </button>
                @endforeach
            </div>
        @else
            @php($detalle = $this->detalleDelMes())

            <div class="cal-resumen" style="margin-bottom: 1rem">
                <span><i class="cal-punto cal-punto-lleno cal-c-realizada"></i><span>{{ $detalle['totales']['realizadas'] }} realizadas</span></span>
                <span><i class="cal-punto cal-c-programada"></i><span>{{ $detalle['totales']['programadas'] }} programadas</span></span>
                <span><i class="cal-punto cal-c-atrasada"></i><span>{{ $detalle['totales']['atrasadas'] }} atrasadas</span></span>
                <span><i class="cal-punto cal-c-reprogramada"></i><span>{{ $detalle['totales']['reprogramadas'] }} reprogramadas</span></span>
            </div>

            <div class="cal-mesvista">
                <div class="cal-grilla">
                    <div class="cal-dias-cab">
                        @foreach (['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'] as $diaSemana)
                            <div>{{ $diaSemana }}</div>
                        @endforeach
                    </div>
                    @foreach ($this->semanasDelMes() as $semana)
                        <div class="cal-semana">
                            @foreach ($semana as $dia)
                                <div @class(['cal-dia', 'cal-dia-fuera' => ! $dia['delMes'], 'cal-dia-hoy' => $dia['esHoy']])>
                                    <span class="cal-dia-num">{{ $dia['fecha']->day }}</span>
                                    @foreach ($dia['realizadas']->take(3) as $item)
                                        <a href="{{ $item['url'] }}" class="cal-ev" title="Realizada: {{ $item['inventario'] }} · {{ $item['equipo'] }}{{ $item['servicio'] ? ' · '.$item['servicio'] : '' }}">✓ {{ $item['inventario'] }} {{ $item['equipo'] }}</a>
                                    @endforeach
                                    @if ($dia['realizadas']->count() > 3)
                                        <span class="cal-mas" title="{{ $dia['realizadas']->skip(3)->map(fn ($item) => $item['inventario'].' · '.$item['equipo'])->implode("\n") }}">+{{ $dia['realizadas']->count() - 3 }} más</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>

                <div class="cal-panel">
                    <div class="cal-caja">
                        <h3>Pendientes del mes <small>{{ $detalle['pendientes']->count() }}</small></h3>
                        <div class="cal-lista">
                            @forelse ($detalle['pendientes'] as $item)
                                <a href="{{ $item['url'] }}" class="cal-item cal-c-{{ $item['tipo'] }}">
                                    <div class="cal-item-fila">
                                        <span class="cal-item-equipo">@if ($item['critico'])<i class="cal-critico" title="Equipo crítico"></i>@endif{{ $item['inventario'] }} · {{ $item['equipo'] }}</span>
                                        <span class="cal-item-estado">{{ $item['etiqueta'] }}</span>
                                    </div>
                                    @if ($item['servicio'])
                                        <span class="cal-item-detalle">{{ $item['servicio'] }}</span>
                                    @endif
                                </a>
                            @empty
                                <div class="cal-vacio">No hay mantenciones pendientes este mes.</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="cal-caja">
                        <h3>Realizadas sin fecha registrada <small>{{ $detalle['sinFecha']->count() }}</small></h3>
                        <div class="cal-lista">
                            @forelse ($detalle['sinFecha'] as $item)
                                <a href="{{ $item['url'] }}" class="cal-item cal-item-realizada cal-c-realizada">
                                    <div class="cal-item-fila">
                                        <span class="cal-item-equipo">@if ($item['critico'])<i class="cal-critico" title="Equipo crítico"></i>@endif{{ $item['inventario'] }} · {{ $item['equipo'] }}</span>
                                        <span class="cal-item-estado">✓ Realizada</span>
                                    </div>
                                    @if ($item['servicio'])
                                        <span class="cal-item-detalle">{{ $item['servicio'] }}</span>
                                    @endif
                                </a>
                            @empty
                                <div class="cal-vacio">No hay realizadas sin fecha este mes.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
