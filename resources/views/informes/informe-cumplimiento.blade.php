@php
    $etiquetaPeriodo = $periodo->esAnual() ? "Año {$anio} (enero a diciembre)" : "{$periodo->getLabel()} {$anio}";
    $pesos = fn (?float $monto): string => $monto === null ? 'Sin dato' : '$'.number_format($monto, 0, ',', '.');
    $porcentaje = fn (?float $valor): string => $valor === null ? 'Sin datos' : number_format($valor, 1, ',', '.').'%';
    $etiquetasCortes = [
        'total' => 'Total',
        \App\Enums\Criticidad::Critico->value => 'Equipos críticos (EQC)',
        \App\Enums\Criticidad::Relevante->value => 'Equipos relevantes (EQR)',
        \App\Enums\Criticidad::ImMayorIgual12->value => 'IM ≥ 12',
    ];
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Informe de cumplimiento y gasto de mantenimiento — {{ $etiquetaPeriodo }}</title>
    @include('informes.partials.estilos')
</head>
<body>
    <div class="acciones">
        <button type="button" onclick="window.print()">Imprimir o guardar como PDF</button>
    </div>

    <header>
        <div class="institucion">Servicio de Salud Aysén · Sistema de Bitácora de Mantenimiento de Equipos</div>
        <h1>Informe de cumplimiento y gasto de mantenimiento de equipos médicos</h1>
        <div class="subtitulo">{{ $etiquetaPeriodo }}</div>
    </header>

    <table class="datos">
        <tr><td>Período</td><td>{{ $etiquetaPeriodo }}</td></tr>
        <tr><td>Establecimiento</td><td>{{ $recinto?->nombre ?? 'Todos los establecimientos' }}</td></tr>
        <tr><td>Servicio clínico</td><td>{{ $servicioClinico?->nombre ?? 'Todos' }}</td></tr>
        <tr><td>Responsable de mantenimiento preventivo</td><td>{{ $responsablesMp ?: 'Sin designar' }}</td></tr>
        <tr><td>Fecha de emisión</td><td>{{ now()->format('d-m-Y') }}</td></tr>
    </table>

    <h2>I. Cumplimiento del mantenimiento preventivo</h2>
    <table>
        <thead>
            <tr><th>Corte</th><th class="numero">Programadas</th><th class="numero">Ejecutadas</th><th class="numero">% cumplimiento</th></tr>
        </thead>
        <tbody>
            @foreach ($etiquetasCortes as $clave => $etiqueta)
                <tr>
                    <td>{{ $etiqueta }}</td>
                    <td class="numero">{{ $cortes[$clave]['programados'] }}</td>
                    <td class="numero">{{ $cortes[$clave]['ejecutados'] }}</td>
                    <td class="numero">{{ $porcentaje($cortes[$clave]['porcentaje']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <p class="formula">Mantenciones ejecutadas sobre mantenciones programadas en el período. Una mantención reprogramada cuenta como programada y no ejecutada.</p>

    <h2>II. Equipos críticos (Res. Ex. N° 1341/2017 del MINSAL)</h2>
    <table>
        <tr><td style="width: 62%">Equipos críticos con MP programada en el período</td><td class="numero">{{ $criticos['con_mp_programada'] }}</td></tr>
        <tr><td>Equipos críticos con MP ejecutada en el período</td><td class="numero">{{ $criticos['con_mp_ejecutada'] }}</td></tr>
        <tr><td><strong>Indicador de la norma</strong></td><td class="numero"><strong>{{ $porcentaje($criticos['porcentaje']) }}</strong></td></tr>
        <tr><td>Reprogramaciones del período</td><td class="numero">{{ $criticos['reprogramaciones'] }}</td></tr>
        <tr><td>Equipos críticos activos sin plan del año</td><td class="numero">{{ $criticos['criticos_sin_plan'] }}</td></tr>
    </table>
    <p class="formula">El detalle por equipo y las causas de cada reprogramación están en el informe de cumplimiento de equipos críticos.</p>

    <h2>III. Gasto de mantenimiento</h2>
    <table>
        <thead>
            <tr><th>Tipo</th><th class="numero">Programado</th><th class="numero">Ejecutado</th><th class="numero">% ejecución</th></tr>
        </thead>
        <tbody>
            <tr>
                <td>Mantenimiento preventivo (MP)</td>
                <td class="numero">{{ $pesos($gasto['mp']['programado']) }}</td>
                <td class="numero">{{ $pesos($gasto['mp']['ejecutado']) }}</td>
                <td class="numero">{{ $porcentaje($gasto['mp']['porcentaje']) }}</td>
            </tr>
            <tr>
                <td>Mantenimiento correctivo (MC)</td>
                <td class="numero">{{ $pesos($gasto['mc']['programado']) }}</td>
                <td class="numero">{{ $pesos($gasto['mc']['ejecutado']) }}</td>
                <td class="numero">{{ $porcentaje($gasto['mc']['porcentaje']) }}</td>
            </tr>
        </tbody>
    </table>
    <p class="formula">
        Programado: {{ $gasto['mp']['fuente'] === 'presupuesto' ? 'gasto programado anual del establecimiento' : 'suma de los costos de referencia de los planes' }}{{ $periodo->esAnual() ? '' : ', proporcional a los meses del período' }}.
        El gasto preventivo ejecutado corresponde a los convenios asociados a los planes del alcance.
    </p>

    <div class="firmas">
        <div class="firma"><div class="linea">Elaborado por<br>Encargado de mantención</div></div>
        <div class="firma"><div class="linea">Revisado por<br>Jefatura</div></div>
    </div>

    <div class="pie">
        Emitido el {{ now()->format('d-m-Y H:i') }}@if ($emitidoPor) por {{ $emitidoPor }}@endif.
    </div>
</body>
</html>
