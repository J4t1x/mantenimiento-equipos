<?php

namespace App\Exceptions;

use Throwable;

/**
 * Marca las excepciones con que el modelo protege una regla de negocio (RNF-04, "defensa en
 * profundidad"). Su mensaje está escrito para el usuario final.
 *
 * Dentro del panel, `App\Filament\Support\NotificarExcepcionesDeDominio` las convierte en una
 * notificación en vez de un error 500. Fuera del panel (consola, importador, tinker) se siguen
 * lanzando igual.
 */
interface ExcepcionDeDominio extends Throwable {}
