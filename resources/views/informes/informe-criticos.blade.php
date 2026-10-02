@php
    $meses = [1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'];
    $resumen = $informe['resumen'];
    $etiquetaPeriodo = $periodo->esAnual() ? "Año {$anio} (enero a diciembre)" : "{$periodo->getLabel()} {$anio}";
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Informe de cumplimiento de equipos críticos — {{ $etiquetaPeriodo }}</title>
    @include('informes.partials.estilos')
</head>
<body>
    <div class="acciones">
        <button type="button" onclick="window.print()">Imprimir o guardar como PDF</button>
    </div>

    <header>
        <div class="institucion">Servicio de Salud Aysén · Sistema de Bitácora de Mantenimiento de Equipos</div>
        <h1>Informe de cumplimiento — Mantenimiento preventivo de equipamiento médico crítico</h1>
        <div class="subtitulo">Norma de Seguridad del Paciente y Calidad de la Atención, Res. Ex. N° 1341/2017 del MINSAL</div>
    </header>

    <table class="datos">
        <tr><td>Período (t)</td><td>{{ $etiquetaPeriodo }}</td></tr>
        <tr><td>Establecimiento</td><td>{{ $recinto?->nombre ?? 'Todos los establecimientos' }}</td></tr>
        <tr><td>Servicio clínico</td><td>{{ $servicioClinico?->nombre ?? 'Todos' }}</td></tr>
        <tr><td>Responsable de mantenimiento preventivo</td><td>{{ $informe['responsables_mp'] ?: 'Sin designar' }}</td></tr>
        <tr><td>Programa anual {{ $anio }} (definición y validación)</td><td>{{ $informe['programa_anual'] ?: 'Sin registro' }}</td></tr>
        <tr><td>Fecha de emisión</td><td>{{ now()->format('d-m-Y') }}</td></tr>
    </table>

    <h2>I. Reporte de los equipos críticos existentes</h2>
    <table>
        <tr>
            <td style="width: 38%">
                <div class="indicador">{{ $resumen['porcentaje'] === null ? 'Sin datos' : number_format($resumen['porcentaje'], 1, ',', '.').'%' }}</div>
                <div class="formula">N° de equipos críticos con MP ejecutada en el período (t) / N° total de equipos críticos con MP programada en el período (t) × 100</div>
            </td>
            <td>
                <table>
                    <tr><td>Equipos críticos existentes (activos)</td><td class="numero">{{ $resumen['equipos_criticos'] }}</td></tr>
                    <tr><td>Equipos críticos activos sin plan del año</td><td class="numero">{{ $resumen['criticos_sin_plan'] }}</td></tr>
                    <tr><td>Con MP programada en el período</td><td class="numero">{{ $resumen['con_mp_programada'] }}</td></tr>
                    <tr><td>Con MP ejecutada en el período</td><td class="numero">{{ $resumen['con_mp_ejecutada'] }}</td></tr>
                    <tr><td>Mantenciones programadas / realizadas</td><td class="numero">{{ $resumen['mantenciones_programadas'] }} / {{ $resumen['mantenciones_realizadas'] }}</td></tr>
                </table>
            </td>
        </tr>
    </table>
    <p class="formula">Un equipo cuenta como ejecutado si se realizaron todas sus mantenciones preventivas programadas del período.</p>

    <h3 style="font-size: 10.5pt; margin: 14px 0 4px;">Equipos críticos activos por tipo de la norma</h3>
    <table>
        @foreach ($informe['tipos_norma'] as $tipo => $cantidad)
            <tr><td>{{ $tipo }}</td><td class="numero">{{ $cantidad }}</td></tr>
        @endforeach
    </table>

    <h3 style="font-size: 10.5pt; margin: 14px 0 4px;">Detalle por equipo crítico</h3>
    @if ($informe['equipos']->isEmpty())
        <p class="vacio">No hay equipos críticos con mantención programada en el período.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Establecimiento</th><th>Servicio clínico</th><th>Equipo</th><th>N° inventario</th>
                    <th class="numero">Frec.</th><th class="numero">Prog.</th><th class="numero">Realiz.</th><th>Cumple</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($informe['equipos'] as $fila)
                    <tr>
                        <td>{{ $fila['recinto'] }}</td>
                        <td>{{ $fila['servicio_clinico'] }}</td>
                        <td>{{ $fila['equipo'] }}@if ($fila['periodicidad_fabricante'])<br><small>Periodicidad de fabricante (garantía)</small>@endif</td>
                        <td>{{ $fila['n_inventario'] }}</td>
                        <td class="numero">{{ $fila['frecuencia_anual'] }}</td>
                        <td class="numero">{{ $fila['programadas'] }}</td>
                        <td class="numero">{{ $fila['realizadas'] }}</td>
                        <td>{{ $fila['cumple'] ? 'Sí' : 'No' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2>II. Reprogramaciones efectuadas durante el período y sus causas</h2>
    @if ($informe['reprogramaciones']->isEmpty())
        <p class="vacio">No hubo reprogramaciones en el período.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Equipo</th><th>Mes</th><th>Causa</th><th>Documento</th><th>Plazo</th><th>Estado actual</th><th>Retiro de uso</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($informe['reprogramaciones'] as $fila)
                    <tr>
                        <td>{{ $fila['equipo'] }}<br><small>{{ $fila['recinto'] }} · {{ $fila['n_inventario'] }}</small></td>
                        <td>{{ $meses[$fila['mes']] }}</td>
                        <td>{{ $fila['causa'] }}</td>
                        <td>{{ $fila['documento'] ?? '—' }}</td>
                        <td>{{ $fila['plazo'] }}@if ($fila['vencida'])<br><small><strong>Vencido</strong></small>@endif</td>
                        <td>{{ $fila['estado']->getLabel() }}</td>
                        <td>{{ $fila['retiro_uso'] ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="firmas">
        <div class="firma"><div class="linea">Elaborado por<br>Profesional de equipos médicos</div></div>
        <div class="firma"><div class="linea">Recibido por<br>Unidad de Calidad</div></div>
        <div class="firma"><div class="linea">Toma de conocimiento<br>Dirección del establecimiento</div></div>
    </div>

    <div class="pie">
        Emitido el {{ now()->format('d-m-Y H:i') }}@if ($emitidoPor) por {{ $emitidoPor }}@endif. Información disponible para ser enviada al Ministerio de Salud o al Servicio de Salud si corresponde (Res. Ex. 1341/2017).
    </div>
</body>
</html>
