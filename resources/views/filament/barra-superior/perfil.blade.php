{{--
    Perfil con el que se ingresó, junto al menú de usuario (render hook USER_MENU_BEFORE). Ayuda
    a reconocer la cuenta activa cuando se trabaja con varias, por ejemplo en la capacitación.
--}}
@php
    $rol = auth()->user()?->getRoleNames()->first();
    $perfil = $rol ? (\App\Support\GuiaDeUsuario::NOMBRES_DE_PERFIL[$rol] ?? $rol) : null;
@endphp

@if ($perfil)
    <span class="bm-perfil" title="Perfil de la sesión">
        <span class="bm-perfil-punto" aria-hidden="true"></span>
        {{ $perfil }}
    </span>
@endif
