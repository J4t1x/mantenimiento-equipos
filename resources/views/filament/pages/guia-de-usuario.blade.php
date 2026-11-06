{{--
    SPEC-ME-017: guía de usuario. El contenido viene de docs/MANUAL-USUARIO.md (App\Support\GuiaDeUsuario).
    Los estilos van en CSS propio con el prefijo .guia-usuario y no en clases de Tailwind, porque el
    panel usa el CSS ya compilado de Filament (ver App\Filament\Pages\Reportes). Al imprimir se
    ocultan el menú, la barra superior y los botones, para guardar la guía como PDF.
--}}
<x-filament-panels::page>
    <style>
        .guia-usuario {
            --guia-azul: #003C71;
            --guia-borde: rgb(0 0 0 / 0.1);
            --guia-fondo-suave: rgb(0 60 113 / 0.05);
            --guia-texto-suave: rgb(55 65 81);
            max-width: 62rem;
            line-height: 1.65;
            font-size: 0.95rem;
            color: rgb(17 24 39);
        }

        .dark .guia-usuario {
            --guia-azul: #8FB3D9;
            --guia-borde: rgb(255 255 255 / 0.12);
            --guia-fondo-suave: rgb(143 179 217 / 0.08);
            --guia-texto-suave: rgb(209 213 219);
            color: rgb(229 231 235);
        }

        .guia-perfil {
            border: 1px solid var(--guia-borde);
            border-left: 4px solid var(--guia-azul);
            background: var(--guia-fondo-suave);
            border-radius: 0.5rem;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
        }

        .guia-perfil strong { color: var(--guia-azul); }
        .guia-perfil ul { margin: 0.5rem 0 0; padding-left: 1.25rem; list-style: disc; }
        .guia-perfil a { color: var(--guia-azul); font-weight: 600; text-decoration: underline; }

        .guia-contenido {
            background: white;
            border: 1px solid var(--guia-borde);
            border-radius: 0.75rem;
            padding: 1.5rem 2rem 2rem;
        }

        .dark .guia-contenido { background: rgb(255 255 255 / 0.03); }

        .guia-contenido > h1:first-child { display: none; }

        .guia-contenido h2 {
            font-size: 1.35rem;
            font-weight: 700;
            color: var(--guia-azul);
            margin: 2.25rem 0 0.75rem;
            padding-bottom: 0.35rem;
            border-bottom: 2px solid var(--guia-borde);
            scroll-margin-top: 6.5rem;
        }

        .guia-contenido h3 {
            font-size: 1.1rem;
            font-weight: 600;
            margin: 1.5rem 0 0.5rem;
            scroll-margin-top: 6.5rem;
        }

        .guia-contenido p { margin: 0.6rem 0; }
        .guia-contenido ul { list-style: disc; padding-left: 1.5rem; margin: 0.5rem 0; }
        .guia-contenido ol { list-style: decimal; padding-left: 1.5rem; margin: 0.5rem 0; }
        .guia-contenido li { margin: 0.2rem 0; }
        .guia-contenido li > ul, .guia-contenido li > ol { margin: 0.2rem 0; }
        .guia-contenido strong { font-weight: 600; }
        .guia-contenido a { color: var(--guia-azul); text-decoration: underline; }
        .guia-contenido hr { border: 0; border-top: 1px solid var(--guia-borde); margin: 2rem 0; }

        .guia-contenido code {
            font-size: 0.85em;
            background: var(--guia-fondo-suave);
            padding: 0.1rem 0.35rem;
            border-radius: 0.25rem;
        }

        .guia-contenido blockquote {
            border-left: 4px solid #E2001A;
            background: var(--guia-fondo-suave);
            padding: 0.6rem 1rem;
            margin: 1rem 0;
            border-radius: 0 0.5rem 0.5rem 0;
        }

        .guia-contenido table {
            width: 100%;
            border-collapse: collapse;
            margin: 0.75rem 0 1rem;
            font-size: 0.9rem;
        }

        .guia-contenido th, .guia-contenido td {
            border: 1px solid var(--guia-borde);
            padding: 0.45rem 0.65rem;
            text-align: left;
            vertical-align: top;
        }

        .guia-contenido th { background: var(--guia-fondo-suave); font-weight: 600; }

        .guia-contenido img {
            display: block;
            max-width: 100%;
            height: auto;
            margin: 1rem 0;
            border: 1px solid var(--guia-borde);
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgb(0 0 0 / 0.08);
        }

        .guia-contenido .guia-ancla {
            margin-left: 0.4rem;
            opacity: 0;
            text-decoration: none;
            font-weight: 400;
        }

        .guia-contenido h2:hover .guia-ancla, .guia-contenido h3:hover .guia-ancla { opacity: 0.5; }

        .guia-volver {
            position: fixed;
            right: 1.5rem;
            bottom: 1.5rem;
            background: var(--guia-azul);
            color: white;
            border-radius: 9999px;
            padding: 0.5rem 0.9rem;
            font-size: 0.85rem;
            box-shadow: 0 2px 6px rgb(0 0 0 / 0.2);
        }

        @media (max-width: 640px) {
            .guia-contenido { padding: 1rem; }
            .guia-contenido table { display: block; overflow-x: auto; }
        }

        @media print {
            .fi-sidebar, .fi-topbar, .fi-header-actions-ctn, .fi-header .fi-ac, .fi-breadcrumbs, .guia-volver { display: none !important; }
            .fi-main { padding: 0 !important; max-width: none !important; }
            .guia-usuario { max-width: none; font-size: 10.5pt; }
            .guia-contenido { border: 0; padding: 0; }
            .guia-contenido h2 { break-after: avoid; }
            .guia-contenido img, .guia-contenido table { break-inside: avoid; }
        }
    </style>

    <div class="guia-usuario" id="guia-inicio">
        @if ($rutas !== [])
            <div class="guia-perfil">
                <strong>Su perfil: {{ collect($rutas)->pluck('perfil')->join(', ', ' y ') }}.</strong>
                Si está aprendiendo a usar el sistema, siga la ruta de capacitación de su perfil: indica qué
                secciones leer, en qué orden, y trae ejercicios para practicar.
                <ul>
                    @foreach ($rutas as $ruta)
                        <li><a href="#{{ $ruta['ancla'] }}">Ruta de capacitación: {{ $ruta['perfil'] }}</a></li>
                    @endforeach
                </ul>
            </div>
        @endif

        <article class="guia-contenido">
            {!! $html !!}
        </article>

        <a href="#guia-inicio" class="guia-volver">↑ Volver al inicio</a>
    </div>
</x-filament-panels::page>
