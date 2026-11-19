{{--
    SPEC-ME-010: estilos institucionales del panel que Filament no expone como configuración.
    Se inyectan en el <head> con un render hook (AdminPanelProvider) para no agregar un tema
    compilado. Apuntan a las clases estables del menú lateral de Filament (.fi-sidebar-*). Si una
    actualización mayor de Filament las cambia, verificar con capturas.

    Menú lateral con los colores del mockup (docs/MOCKUP-SISTEMA.html): fondo azul institucional
    oscuro, texto celeste claro e ítem activo con fondo translúcido y franja roja.

    Bitácora anual: las celdas de los 12 meses (clase fi-ta-cell-mes{N} que agrega Filament) y las
    del pie de resumen (fi-ta-text-summary; hoy es la única tabla del panel con resúmenes) llevan
    menos relleno, para que el año completo quepa a 1440 px sin desplazamiento horizontal.

    Barra superior: degradado del azul institucional con una línea inferior roja que se desvanece,
    nombre del sistema junto al logo (filament.barra-superior.marca), buscador translúcido, íconos
    en blanco y el perfil de la sesión antes del menú de usuario (filament.barra-superior.perfil).
    El menú lateral continúa el mismo azul con un leve degradado vertical.

    Los comentarios van en Blade y no dentro del <style>, porque el CSS se envía al navegador y la
    página no debe exponer referencias internas (ReportesTest).
--}}
<style>
    .fi-topbar {
        --barra-fondo: linear-gradient(90deg, #002B55 0%, #003C71 38%, #0060AF 100%);
        position: relative;
        background: var(--barra-fondo) !important;
        border-bottom: 0 !important;
        box-shadow: 0 6px 18px -10px rgb(0 43 85 / 0.55) !important;
    }

    .dark .fi-topbar {
        --barra-fondo: linear-gradient(90deg, #061A30 0%, #0A2440 45%, #0B3A6B 100%);
    }

    .fi-topbar::after {
        content: "";
        position: absolute;
        inset: auto 0 0 0;
        height: 3px;
        background: linear-gradient(90deg, #E2001A 0%, #E2001A 18%, rgb(226 0 26 / 0.35) 45%, rgb(255 255 255 / 0) 80%);
        pointer-events: none;
    }

    .fi-topbar .fi-logo {
        border-radius: 9999px;
        box-shadow: 0 0 0 2px rgb(255 255 255 / 0.9), 0 4px 12px rgb(0 0 0 / 0.25);
    }

    .fi-topbar-start {
        gap: 0.875rem;
    }

    .bm-marca {
        display: none;
        flex-direction: column;
        line-height: 1.15;
        padding-left: 0.875rem;
        border-left: 1px solid rgb(255 255 255 / 0.22);
        text-decoration: none;
    }

    @media (min-width: 768px) {
        .bm-marca {
            display: flex;
        }
    }

    .bm-marca-titulo {
        color: #FFFFFF;
        font-weight: 700;
        font-size: 0.975rem;
        letter-spacing: -0.01em;
        white-space: nowrap;
    }

    .bm-marca-subtitulo {
        color: #B9D0E8;
        font-size: 0.72rem;
        font-weight: 500;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .fi-topbar > .fi-icon-btn,
    .fi-topbar-end .fi-icon-btn,
    .fi-topbar-end .fi-icon-btn .fi-icon {
        color: #FFFFFF !important;
    }

    .fi-topbar-end .fi-icon-btn:hover,
    .fi-topbar > .fi-icon-btn:hover {
        background-color: rgb(255 255 255 / 0.12) !important;
    }

    .fi-topbar .fi-global-search-field .fi-input-wrp {
        background-color: rgb(255 255 255 / 0.12) !important;
        box-shadow: inset 0 0 0 1px rgb(255 255 255 / 0.22) !important;
        border-radius: 9999px;
        transition: background-color 150ms, box-shadow 150ms;
    }

    .fi-topbar .fi-global-search-field .fi-input-wrp:focus-within {
        background-color: rgb(255 255 255 / 0.2) !important;
        box-shadow: inset 0 0 0 1px rgb(255 255 255 / 0.55), 0 0 0 3px rgb(255 255 255 / 0.15) !important;
    }

    .fi-topbar .fi-global-search-field input {
        color: #FFFFFF !important;
    }

    .fi-topbar .fi-global-search-field input::placeholder {
        color: rgb(255 255 255 / 0.7) !important;
    }

    .fi-topbar .fi-global-search-field .fi-input-wrp-prefix .fi-icon {
        color: rgb(255 255 255 / 0.8) !important;
    }

    .fi-topbar .fi-user-avatar {
        box-shadow: 0 0 0 2px #FFFFFF, 0 0 0 4px rgb(226 0 26 / 0.85);
    }

    .bm-perfil {
        display: none;
        align-items: center;
        gap: 0.4rem;
        padding: 0.3rem 0.75rem;
        border-radius: 9999px;
        background-color: rgb(255 255 255 / 0.12);
        box-shadow: inset 0 0 0 1px rgb(255 255 255 / 0.2);
        color: #FFFFFF;
        font-size: 0.75rem;
        font-weight: 600;
        white-space: nowrap;
    }

    @media (min-width: 1024px) {
        .bm-perfil {
            display: inline-flex;
        }
    }

    .bm-perfil-punto {
        width: 0.45rem;
        height: 0.45rem;
        border-radius: 9999px;
        background-color: #34D399;
        box-shadow: 0 0 0 3px rgb(52 211 153 / 0.25);
    }

    .fi-main-sidebar {
        --menu-fondo: #003C71;
        --menu-texto: #B9D0E8;
        --menu-titulo: #8FB3D9;
        --menu-activo: rgb(255 255 255 / 0.14);
        --menu-hover: rgb(255 255 255 / 0.08);
        --menu-franja: #E2001A;
        background-color: var(--menu-fondo) !important;
        background-image: linear-gradient(180deg, #002B55 0%, var(--menu-fondo) 40%) !important;
    }

    .fi-main-sidebar .fi-sidebar-group-label {
        text-transform: uppercase;
        letter-spacing: 0.08em;
        font-size: 0.7rem;
    }

    .dark .fi-main-sidebar {
        --menu-fondo: #0A2440;
    }

    .fi-main-sidebar .fi-sidebar-nav,
    .fi-main-sidebar .fi-sidebar-header {
        background-color: transparent;
    }

    .fi-main-sidebar .fi-sidebar-header {
        border-bottom: 1px solid rgb(255 255 255 / 0.15);
    }

    .fi-main-sidebar .fi-sidebar-group-label,
    .fi-main-sidebar .fi-sidebar-group-btn .fi-icon,
    .fi-main-sidebar .fi-sidebar-group-collapse-btn {
        color: var(--menu-titulo) !important;
    }

    .fi-main-sidebar .fi-sidebar-item-btn {
        border-radius: 0.5rem;
        border-left: 3px solid transparent;
    }

    .fi-main-sidebar .fi-sidebar-item-label,
    .fi-main-sidebar .fi-sidebar-item-icon {
        color: var(--menu-texto) !important;
    }

    .fi-main-sidebar .fi-sidebar-item-btn:hover,
    .fi-main-sidebar .fi-sidebar-item-btn:focus-visible {
        background-color: var(--menu-hover) !important;
    }

    .fi-main-sidebar .fi-sidebar-item.fi-active > .fi-sidebar-item-btn {
        background-color: var(--menu-activo) !important;
        border-left-color: var(--menu-franja);
    }

    .fi-main-sidebar .fi-sidebar-item.fi-active .fi-sidebar-item-label,
    .fi-main-sidebar .fi-sidebar-item.fi-active .fi-sidebar-item-icon {
        color: #FFFFFF !important;
        font-weight: 600;
    }

    .fi-main-sidebar .fi-sidebar-item-grouped-border-part {
        background-color: rgb(255 255 255 / 0.25) !important;
    }

    td[class*="fi-ta-cell-mes"] .fi-ta-text,
    td[class*="fi-ta-cell-mes"] .fi-ta-col,
    .fi-ta-text-summary {
        padding-inline: 0.125rem !important;
    }
</style>
