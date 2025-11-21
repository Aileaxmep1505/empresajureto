<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\CatalogAiIntake;
use App\Models\CatalogAiIntakeFile;
use App\Jobs\ProcessCatalogAiIntakeJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CatalogAiIntakePublicController extends Controller
{
    /**
     * Pantalla móvil (abre cámara).
     * GET /i/{token}
     */
    public function capture(string $token)
    {
        $intake = CatalogAiIntake::where('token', $token)->firstOrFail();

        if (in_array($intake->status, [4])) {
            abort(410, 'Esta captura ya fue confirmada.');
        }

        return view('public.intake.capture', compact('intake'));
    }

    /**
     * Recibe fotos desde el celular.
     * POST /i/{token}/upload
     */
    public function upload(Request $r, string $token)
    {
        $intake = CatalogAiIntake::where('token', $token)->firstOrFail();

        $r->validate([
            'images'   => ['required', 'array', 'min:1', 'max:8'],
            'images.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'], // 10MB c/u
        ]);

        $page = ($intake->files()->max('page_no') ?? 0) + 1;

        foreach ($r->file('images') as $file) {
            $path = $file->storePublicly("intakes/{$intake->id}", ['disk' => 'public']);

            CatalogAiIntakeFile::create([
                'intake_id'     => $intake->id,
                'disk'          => 'public',
                'path'          => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime'          => $file->getMimeType(),
                'size'          => $file->getSize(),
                'page_no'       => $page++,
            ]);
        }

        $intake->status = 1; // uploaded
        $intake->uploaded_at = now();
        $intake->save();

        // Encolar proceso IA
        ProcessCatalogAiIntakeJob::dispatch($intake->id);

        return response()->json(['ok' => true]);
    }

    /**
     * Devuelve estado para polling desde el celular.
     * GET /i/{token}/status
     */
    public function status(string $token)
    {
        $intake = CatalogAiIntake::where('token', $token)
            ->with('files')
            ->firstOrFail();

        return response()->json([
            'status'    => $intake->status,
            'extracted' => $intake->extracted,
            'images'    => $intake->files->map(fn($f) =>
                Storage::disk($f->disk)->url($f->path)
            ),
            'meta'      => $intake->meta,
        ]);
    }
}
