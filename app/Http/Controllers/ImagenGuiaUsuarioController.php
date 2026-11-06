<?php

namespace App\Http\Controllers;

use App\Support\GuiaDeUsuario;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * SPEC-ME-017: imágenes de la guía de usuario. Solo usuarios autenticados (ruta del panel) y solo
 * archivos de `docs/manual/img`: el nombre se valida con un patrón sin barras ni puntos dobles.
 */
class ImagenGuiaUsuarioController extends Controller
{
    public function __invoke(string $archivo): BinaryFileResponse
    {
        abort_unless(preg_match('/^'.GuiaDeUsuario::PATRON_IMAGEN.'$/', $archivo) === 1, 404);

        $ruta = GuiaDeUsuario::directorioImagenes().DIRECTORY_SEPARATOR.$archivo;

        abort_unless(is_file($ruta), 404);

        return response()->file($ruta, ['Cache-Control' => 'private, max-age=86400']);
    }
}
