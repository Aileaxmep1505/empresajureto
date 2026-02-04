<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AltaDoc;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AltaDocsController extends Controller
{
    public function showPinForm()
    {
        return view('secure.alta_docs_pin');
    }

    public function checkPin(Request $request)
    {
        $data = $request->validate([
            'pin' => ['required', 'regex:/^[0-9]{6}$/'],
        ], [
            'pin.required' => 'Debes capturar tu NIP.',
            'pin.regex'    => 'El NIP debe tener exactamente 6 dígitos numéricos.',
        ]);

        $enteredPin  = trim((string) $data['pin']);
        $expectedPin = (string) config('alta_docs.pin');

        Log::info('AltaDocs: intento de PIN', [
            'user_id'       => $request->user()->id ?? null,
            'ip'            => $request->ip(),
            'expected_len'  => strlen($expectedPin),
            'received_len'  => strlen($enteredPin),
        ]);

        if (!hash_equals($expectedPin, $enteredPin)) {
            Log::warning('AltaDocs: PIN inválido', [
                'user_id' => $request->user()->id ?? null,
                'ip'      => $request->ip(),
            ]);

            return back()
                ->withErrors(['pin' => 'NIP incorrecto'])
                ->withInput();
        }

        $request->session()->put('alta_docs_unlocked', true);

        Log::info('AltaDocs: PIN correcto, sesión desbloqueada', [
            'user_id' => $request->user()->id ?? null,
            'ip'      => $request->ip(),
        ]);

        return redirect()
            ->route('alta.docs.index')
            ->with('ok', 'Acceso a documentación confidencial habilitado.');
    }

    public function logoutPin(Request $request)
    {
        $request->session()->forget('alta_docs_unlocked');

        Log::info('AltaDocs: sesión de documentación cerrada', [
            'user_id' => $request->user()->id ?? null,
            'ip'      => $request->ip(),
        ]);

        return redirect()
            ->route('secure.alta-docs.pin.show')
            ->with('ok', 'Sesión de documentación cerrada.');
    }

    /**
     * INDEX con filtros:
     * - q (buscador)
     * - category (3 opciones)
     */
    public function index(Request $request)
    {
        $q        = trim((string) $request->query('q', ''));
        $category = trim((string) $request->query('category', ''));

        $query = AltaDoc::query()->orderByDesc('id');

        if ($category !== '' && in_array($category, AltaDoc::CATEGORIES, true)) {
            $query->where('category', $category);
        }

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('title', 'like', "%{$q}%")
                    ->orWhere('notes', 'like', "%{$q}%")
                    ->orWhere('original_name', 'like', "%{$q}%");
            });
        }

        $docs = $query->paginate(15)->appends([
            'q'        => $q,
            'category' => $category,
        ]);

        return view('secure.alta_docs_index', [
            'docs'      => $docs,
            'q'         => $q,
            'category'  => $category,
            'catLabels' => AltaDoc::categoryLabels(),
        ]);
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'category' => ['required', 'in:' . implode(',', AltaDoc::CATEGORIES)],
                'title'    => ['required', 'string', 'max:160'],
                'doc_date' => ['required', 'date'],

                'files'    => ['required', 'array'],
                'files.*'  => [
                    'file',
                    'max:20480',
                    'mimes:pdf,doc,docx,xls,xlsx,csv,xml,txt',
                ],
                'notes' => ['nullable', 'string', 'max:500'],
            ], [
                'category.required' => 'Selecciona un tipo.',
                'category.in'       => 'El tipo seleccionado no es válido.',
                'title.required'    => 'Captura un título.',
                'doc_date.required' => 'Selecciona una fecha.',
                'files.required'    => 'Debes seleccionar al menos un archivo.',
            ]);

            $files = $request->file('files', []);
            if (empty($files)) {
                return back()->withErrors(['files' => 'Debes seleccionar al menos un archivo.']);
            }

            $disk = 'local'; // storage/app (no público)

            $created = 0;
            foreach ($files as $file) {
                if (!$file) continue;

                $path = $file->store('alta_docs', $disk);

                $doc = AltaDoc::create([
                    'category'      => $data['category'],
                    'title'         => $data['title'],
                    'doc_date'      => $data['doc_date'],

                    'original_name' => $file->getClientOriginalName(),
                    'stored_name'   => basename($path),
                    'disk'          => $disk,
                    'path'          => $path,
                    'mime'          => $file->getClientMimeType(),
                    'size'          => $file->getSize(),
                    'notes'         => $data['notes'] ?? null,
                    'uploaded_by'   => $request->user()->id ?? null,
                ]);

                $created++;

                Log::info('AltaDocs: documento subido', [
                    'doc_id'  => $doc->id,
                    'file'    => $doc->original_name,
                    'type'    => $doc->category,
                    'user_id' => $request->user()->id ?? null,
                    'ip'      => $request->ip(),
                ]);
            }

            return redirect()
                ->route('alta.docs.index')
                ->with('ok', "Se cargaron {$created} documento(s) correctamente.");
        } catch (\Throwable $e) {
            Log::error('AltaDocs: error al subir documentos', [
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
                'user_id' => $request->user()->id ?? null,
                'ip'      => $request->ip(),
            ]);

            return back()
                ->with('error', 'No se pudo subir el documento. Revisa el log del servidor.')
                ->withInput();
        }
    }

    public function download(AltaDoc $doc)
    {
        if (!Storage::disk($doc->disk)->exists($doc->path)) {
            return back()->with('error', 'El archivo ya no existe en el servidor.');
        }

        Log::info('AltaDocs: descarga de documento', [
            'doc_id'  => $doc->id,
            'file'    => $doc->original_name,
            'user_id' => auth()->id(),
            'ip'      => request()->ip(),
        ]);

        return Storage::disk($doc->disk)->download($doc->path, $doc->original_name);
    }

    public function destroy(Request $request, AltaDoc $doc)
    {
        try {
            if (Storage::disk($doc->disk)->exists($doc->path)) {
                Storage::disk($doc->disk)->delete($doc->path);
            }

            $docId   = $doc->id;
            $docName = $doc->original_name;

            $doc->delete();

            Log::warning('AltaDocs: documento eliminado', [
                'doc_id'  => $docId,
                'file'    => $docName,
                'user_id' => $request->user()->id ?? null,
                'ip'      => $request->ip(),
            ]);

            return back()->with('ok', 'Documento eliminado correctamente.');
        } catch (\Throwable $e) {
            Log::error('AltaDocs: error al eliminar documento', [
                'doc_id'  => $doc->id,
                'error'   => $e->getMessage(),
                'user_id' => $request->user()->id ?? null,
                'ip'      => $request->ip(),
            ]);

            return back()->with('error', 'No se pudo eliminar el documento.');
        }
    }

    /**
     * PREVIEW (inline) - usa el disk real del registro, no fijo "local"
     */
    public function preview(AltaDoc $doc)
    {
        $diskName = $doc->disk ?: 'local';
        $disk = Storage::disk($diskName);

        if (!$disk->exists($doc->path)) {
            abort(404);
        }

        $mime = $doc->mime ?: $disk->mimeType($doc->path);

        $absolutePath = $disk->path($doc->path);

        return response()->file($absolutePath, [
            'Content-Type'        => $mime,
            'Content-Disposition' => 'inline; filename="' . $doc->original_name . '"',
        ]);
    }
}
