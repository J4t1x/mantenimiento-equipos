{{--
    Corrección de la búsqueda global en Safari (render hook BODY_END).

    Filament cierra los resultados cuando el campo de búsqueda pierde el foco, salvo que el foco
    pase a un resultado. Safari (macOS e iPad) no enfoca los enlaces al hacer clic, así que los
    resultados se cerraban al presionar el botón del mouse y el clic no llegaba al enlace: no
    navegaba. Al presionar sobre un resultado se evita que el campo pierda el foco; el clic sigue
    su curso normal y abre el registro. Chrome y Firefox no cambian de comportamiento.

    Se escucha en el documento (fase de captura) porque Livewire vuelve a dibujar los resultados
    en cada búsqueda.
--}}
<script>
    document.addEventListener('mousedown', function (evento) {
        if (evento.button === 0 && evento.target.closest('.fi-global-search-results-ctn a')) {
            evento.preventDefault();
        }
    }, true);
</script>
