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

    Los comentarios van en Blade y no dentro del <style>, porque el CSS se envía al navegador y la
    página no debe exponer referencias internas (ReportesTest).
--}}
<style>
    .fi-main-sidebar {
        --menu-fondo: #003C71;
        --menu-texto: #B9D0E8;
        --menu-titulo: #8FB3D9;
        --menu-activo: rgb(255 255 255 / 0.14);
        --menu-hover: rgb(255 255 255 / 0.08);
        --menu-franja: #E2001A;
        background-color: var(--menu-fondo) !important;
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
