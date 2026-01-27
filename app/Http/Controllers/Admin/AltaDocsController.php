<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AltaDoc;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AltaDocsController extends Controller
{
    /**
     * Formulario para ingresar NIP (PIN) de acceso a documentación.
     */
    public function showPinForm()
    {
        // 👇 Coincide con resources/views/secure/alta_docs_pin.blade.php
        return view('secure.alta_docs_pin');
    }

    /**
     * Valida el PIN y abre la sesión de documentación segura.
     */
    public function checkPin(Request $request)
    {
        // Debe ser exactamente 6 dígitos numéricos
        $data = $request->validate([
            'pin' => ['required', 'regex:/^[0-9]{6}$/'],
        ], [
            'pin.required' => 'Debes capturar tu NIP.',
            'pin.regex'    => 'El NIP debe tener exactamente 6 dígitos numéricos.',
        ]);

        $enteredPin  = trim((string) $data['pin']);
        // 🔐 SIEMPRE tomamos el PIN desde config/alta_docs.php
        $expectedPin = (string) config('alta_docs.pin');

        // Log para depurar diferencias (sin mostrar el PIN completo en producción real)
        Log::info('AltaDocs: intento de PIN', [
            'user_id'       => $request->user()->id ?? null,
            'ip'            => $request->ip(),
            'expected_len'  => strlen($expectedPin),
            'received_len'  => strlen($enteredPin),
        ]);

        // Comparación segura
        if (!hash_equals($expectedPin, $enteredPin)) {
            Log::warning('AltaDocs: PIN inválido', [
                'user_id' => $request->user()->id ?? null,
                'ip'      => $request->ip(),
            ]);

            return back()
                ->withErrors(['pin' => 'NIP incorrecto'])
                ->withInput();
        }

        // Marcamos la sesión como desbloqueada
        $request->session()->put('alta_docs_unlocked', true);

        Log::info('AltaDocs: PIN correcto, sesión desbloqueada', [
            'user_id' => $request->user()->id ?? null,
            'ip'      => $request->ip(),
        ]);

        // ✅ Al PIN correcto SIEMPRE te manda al index protegido
        return redirect()
            ->route('alta.docs.index')
            ->with('ok', 'Acceso a documentación confidencial habilitado.');
    }

    /**
     * Cierra la sesión de documentación (PIN).
     */
    public function logoutPin(Request $request)
    {
        $request->session()->forget('alta_docs_unlocked');

        Log::info('AltaDocs: sesión de documentación cerrada', [
            'user_id' => $request->user()->id ?? null,
            'ip'      => $request->ip(),
        ]);

        // 👇 Volvemos al formulario de PIN
        return redirect()
            ->route('secure.alta-docs.pin.show')
            ->with('ok', 'Sesión de documentación cerrada.');
    }

    /**
     * Listado + formulario de subida de documentos.
     */
    public function index()
    {
        $docs = AltaDoc::orderByDesc('id')->paginate(15);

        // 👇 Coincide con resources/views/secure/alta_docs_index.blade.php
        return view('secure.alta_docs_index', [
            'docs' => $docs,
        ]);
    }

    /**
     * Sube uno o varios documentos confidenciales.
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'files'   => ['required', 'array'],
                'files.*' => [
                    'file',
                    'max:20480', // 20 MB
                    'mimes:pdf,doc,docx,xls,xlsx,csv,xml,txt',
                ],
                'notes' => ['nullable', 'string', 'max:500'],
            ]);

            $files = $request->file('files', []);
            if (empty($files)) {
                return back()->withErrors([
                    'files' => 'Debes seleccionar al menos un archivo.',
                ]);
            }

            $disk = 'local'; // 🔐 no público (storage/app)

            $created = 0;
            foreach ($files as $file) {
                if (!$file) continue;

                $path = $file->store('alta_docs', $disk);

                $doc = AltaDoc::create([
                    'original_name' => $file->getClientOriginalName(),
                    'stored_name'   => basename($path),
                    'disk'          => $disk,
                    'path'          => $path,
                    'mime'          => $file->getClientMimeType(),
                    'size'          => $file->getSize(),
                    'notes'         => $request->input('notes'),
                    'uploaded_by'   => $request->user()->id ?? null,
                ]);

                $created++;

                Log::info('AltaDocs: documento subido', [
                    'doc_id'   => $doc->id,
                    'file'     => $doc->original_name,
                    'user_id'  => $request->user()->id ?? null,
                    'ip'       => $request->ip(),
                ]);
            }

            if ($created === 0) {
                return back()->withErrors([
                    'files' => 'No se pudo procesar ningún archivo.',
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

    /**
     * Descargar un documento confidencial.
     */
    public function download(AltaDoc $doc)
    {
        if (!Storage::disk($doc->disk)->exists($doc->path)) {
            return back()->with('error', 'El archivo ya no existe en el servidor.');
        }

        Log::info('AltaDocs: descarga de documento', [
            'doc_id'   => $doc->id,
            'file'     => $doc->original_name,
            'user_id'  => auth()->id(),
            'ip'       => request()->ip(),
        ]);

        return Storage::disk($doc->disk)->download($doc->path, $doc->original_name);
    }

    /**
     * Eliminar un documento (borra archivo del disco + registro).
     */
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
                'doc_id'   => $docId,
                'file'     => $docName,
                'user_id'  => $request->user()->id ?? null,
                'ip'       => $request->ip(),
            ]);

            return back()->with('ok', 'Documento eliminado correctamente.');
        } catch (\Throwable $e) {
            Log::error('AltaDocs: error al eliminar documento', [
                'doc_id'   => $doc->id,
                'error'    => $e->getMessage(),
                'user_id'  => $request->user()->id ?? null,
                'ip'       => $request->ip(),
            ]);

            return back()->with('error', 'No se pudo eliminar el documento.');
        }
    }
       public function preview(AltaDoc $doc)
    {
        // Ajusta el disk si usas otro (s3, etc.)
        $disk = Storage::disk('local'); // o el disk que uses

        if (!$disk->exists($doc->path)) {
            abort(404);
        }

        // Detectar mime
        $mime = $doc->mime_type ?: $disk->mimeType($doc->path);

        // Tipos que podemos mostrar dentro del <iframe>
        $inlineables = [
            'application/pdf',
            'image/png',
            'image/jpeg',
            'image/jpg',
            'image/gif',
            'image/webp',
        ];

        // Ruta física del archivo
        $absolutePath = $disk->path($doc->path);

        // Si es de tipo "embebible", lo regresamos inline
        if (in_array($mime, $inlineables)) {
            return response()->file($absolutePath, [
                'Content-Type'        => $mime,
                'Content-Disposition' => 'inline; filename="'.$doc->original_name.'"',
            ]);
        }

        // Para otros tipos (Word, Excel, etc.), dejamos que el navegador lo maneje
        // o fuerce descarga según su configuración
        return response()->file($absolutePath, [
            'Content-Type'        => $mime,
            'Content-Disposition' => 'inline; filename="'.$doc->original_name.'"',
        ]);
    }
}
