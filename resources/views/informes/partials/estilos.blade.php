{{-- RF-77/RF-78: estilos propios y autocontenidos (sin Tailwind) de los informes imprimibles. --}}
    <style>
        @page { size: A4; margin: 18mm 15mm; }
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 10.5pt; color: #111; margin: 0 auto; max-width: 190mm; padding: 12px; }
        header { border-bottom: 2px solid #006BBB; padding-bottom: 8px; margin-bottom: 14px; }
        header .institucion { font-size: 9pt; color: #444; text-transform: uppercase; letter-spacing: .04em; }
        h1 { font-size: 14pt; margin: 4px 0 2px; }
        h2 { font-size: 11.5pt; margin: 18px 0 6px; color: #006BBB; }
        .subtitulo { font-size: 9.5pt; color: #444; }
        table { width: 100%; border-collapse: collapse; margin-top: 4px; }
        th, td { border: 1px solid #bbb; padding: 4px 6px; text-align: left; vertical-align: top; }
        th { background: #eef4fa; font-weight: bold; }
        td.numero, th.numero { text-align: right; }
        .datos td:first-child { width: 38%; background: #f7f7f7; font-weight: bold; }
        .indicador { font-size: 20pt; font-weight: bold; color: #006BBB; }
        .formula { font-size: 9pt; color: #444; margin-top: 4px; }
        .vacio { color: #666; font-style: italic; }
        .firmas { display: flex; gap: 24px; margin-top: 48px; page-break-inside: avoid; }
        .firma { flex: 1; text-align: center; font-size: 9pt; }
        .firma .linea { border-top: 1px solid #111; margin-top: 50px; padding-top: 4px; }
        .pie { margin-top: 24px; font-size: 8pt; color: #666; border-top: 1px solid #ddd; padding-top: 6px; }
        .acciones { margin-bottom: 12px; }
        .acciones button { background: #006BBB; color: #fff; border: 0; padding: 8px 16px; border-radius: 4px; font-size: 10pt; cursor: pointer; }
        tr { page-break-inside: avoid; }
        @media print { .acciones { display: none; } body { padding: 0; } }
    </style>
