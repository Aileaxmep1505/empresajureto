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
    public function capture(string $token)
    {
        $intake = CatalogAiIntake::where('token', $token)->firstOrFail();

        if (in_array($intake->status, [4])) {
            abort(410, 'Esta captura ya fue confirmada.');
        }

        return view('public.intake.capture', compact('intake'));
    }

    public function upload(Request $r, string $token)
    {
        $intake = CatalogAiIntake::where('token', $token)->firstOrFail();

        $r->validate([
            'images'   => ['required', 'array', 'min:1', 'max:8'],
            'images.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
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

        // ======= ACTUALIZAMOS ESTADO =======
        $intake->status      = 1;       // fotos subidas
        $intake->uploaded_at = now();
        $intake->save();

        // ======= AQUÍ ESTABA TU PROBLEMA =======
        // Antes:
        // ProcessCatalogAiIntakeJob::dispatch($intake->id);

        // Ahora: lo ejecutamos SIN cola, en el mismo request:
        ProcessCatalogAiIntakeJob::dispatchSync($intake->id);
        // (esto llama al handle() del job inmediatamente)

        return response()->json(['ok' => true]);
    }

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
