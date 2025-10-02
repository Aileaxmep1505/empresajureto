<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class MediaController extends Controller
{
    public function show(string $path)
    {
        // Seguridad básica: no permitir subir de directorio
        $path = ltrim($path, '/');

        if (!Storage::disk('public')->exists($path)) {
            abort(404);
        }

        $mime = Storage::disk('public')->mimeType($path) ?? 'application/octet-stream';
        $contents = Storage::disk('public')->get($path);

        // Cache control (1 día) + ETag sencillo
        return response($contents, Response::HTTP_OK, [
            'Content-Type'  => $mime,
            'Cache-Control' => 'public, max-age=86400',
            'ETag'          => md5($path . Storage::disk('public')->lastModified($path)),
        ]);
    }
}
