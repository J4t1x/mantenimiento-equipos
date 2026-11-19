{{--
    Nombre del sistema junto al logo de la barra superior (render hook TOPBAR_LOGO_AFTER).
    Se oculta en pantallas angostas, donde solo queda el logo.
--}}
<a href="{{ filament()->getHomeUrl() }}" class="bm-marca">
    <span class="bm-marca-titulo">{{ filament()->getBrandName() }}</span>
    <span class="bm-marca-subtitulo">Servicio de Salud Aysén · Equipos Médicos</span>
</a>
