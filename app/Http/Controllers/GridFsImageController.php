<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class GridFsImageController extends Controller
{
    public function mostrarImagen(string $path): Response
    {
        $disk = Storage::disk('gridfs');

        if (! $disk->exists($path)) {
            abort(404, 'Imagen no encontrada.');
        }

        return response($disk->get($path), 200, [
            'Content-Type' => $disk->mimeType($path) ?: 'application/octet-stream',
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }
}
