{{-- RF-77/RF-78: estilos propios y autocontenidos (sin Tailwind) de los informes imprimibles, con la paleta institucional del panel y del mockup. --}}
    <style>
        :root {
            --azul: #0060AF;
            --azul-oscuro: #003C71;
            --azul-tenue: #EAF3FB;
            --rojo: #E2001A;
            --tinta: #132437;
            --tinta-suave: #4B5F73;
            --borde: #D7E3EF;
        }
        @page { size: A4; margin: 18mm 15mm; }
        * { box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 10.5pt; color: var(--tinta); margin: 0 auto; max-width: 190mm; padding: 12px; }
        header { padding: 0 0 8px 62px; min-height: 58px; margin-bottom: 14px; border-bottom: 3px solid var(--azul); position: relative; }
        header .logo { position: absolute; left: 0; top: 0; width: 50px; height: 50px; }
        header::after { content: ''; position: absolute; left: 0; bottom: -6px; width: 30%; border-bottom: 3px solid var(--rojo); }
        header .institucion { font-size: 9pt; color: var(--tinta-suave); text-transform: uppercase; letter-spacing: .04em; }
        h1 { font-size: 14pt; margin: 4px 0 2px; color: var(--azul-oscuro); }
        h2 { font-size: 11.5pt; margin: 18px 0 6px; color: var(--azul); }
        .subtitulo { font-size: 9.5pt; color: var(--tinta-suave); }
        table { width: 100%; border-collapse: collapse; margin-top: 4px; }
        th, td { border: 1px solid var(--borde); padding: 4px 6px; text-align: left; vertical-align: top; }
        th { background: var(--azul-tenue); color: var(--azul-oscuro); font-weight: bold; }
        td.numero, th.numero { text-align: right; }
        .datos td:first-child { width: 38%; background: var(--azul-tenue); color: var(--azul-oscuro); font-weight: bold; }
        .indicador { font-size: 20pt; font-weight: bold; color: var(--azul); }
        .formula { font-size: 9pt; color: var(--tinta-suave); margin-top: 4px; }
        .vacio { color: var(--tinta-suave); font-style: italic; }
        .firmas { display: flex; gap: 24px; margin-top: 48px; page-break-inside: avoid; }
        .firma { flex: 1; text-align: center; font-size: 9pt; color: var(--tinta-suave); }
        .firma .linea { border-top: 1px solid var(--tinta); margin-top: 50px; padding-top: 4px; }
        .pie { margin-top: 24px; font-size: 8pt; color: var(--tinta-suave); border-top: 1px solid var(--borde); padding-top: 6px; }
        .acciones { margin-bottom: 12px; }
        .acciones button { background: var(--azul); color: #fff; border: 0; padding: 8px 16px; border-radius: 6px; font-size: 10pt; cursor: pointer; }
        .acciones button:hover { background: var(--azul-oscuro); }
        tr { page-break-inside: avoid; }
        @media print { .acciones { display: none; } body { padding: 0; } }
    </style>
