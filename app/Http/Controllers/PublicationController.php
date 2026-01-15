<?php

namespace App\Http\Controllers;

use App\Models\Publication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PublicationController extends Controller
{
    public function index()
    {
        $pinned = Publication::query()
            ->where('pinned', true)
            ->latest('created_at')
            ->get();

        $latest = Publication::query()
            ->where('pinned', false)
            ->latest('created_at')
            ->paginate(12);

        return view('publications.index', compact('pinned', 'latest'));
    }

    public function create()
    {
        return view('publications.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'       => ['required','string','max:200'],
            'description' => ['nullable','string','max:5000'],
            'file'        => ['required','file','max:51200'], // 50MB (ajusta)
            'pinned'      => ['nullable'],
        ]);

        $file = $request->file('file');

        // Guardado: storage/app/public/publications/YYYY/MM
        $folder = 'publications/' . now()->format('Y/m');
        $safeBaseName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $ext = strtolower($file->getClientOriginalExtension() ?: '');
        $name = $safeBaseName . '-' . Str::random(8) . ($ext ? ".{$ext}" : '');
        $path = $file->storeAs($folder, $name, 'public');

        $mime = $file->getClientMimeType();
        $kind = $this->detectKind($mime, $ext);

        Publication::create([
            'title'         => $request->title,
            'description'   => $request->description,
            'file_path'     => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type'     => $mime,
            'size'          => $file->getSize() ?: 0,
            'extension'     => $ext,
            'kind'          => $kind,
            'pinned'        => (bool) $request->boolean('pinned'),
            'created_by'    => Auth::id(),
        ]);

        return redirect()->route('publications.index')->with('ok', 'Publicación subida correctamente.');
    }

    public function show(Publication $publication)
    {
        return view('publications.show', compact('publication'));
    }

    public function download(Publication $publication)
    {
        // Fuerza descarga con nombre original
        if (!Storage::disk('public')->exists($publication->file_path)) {
            abort(404);
        }
        return Storage::disk('public')->download($publication->file_path, $publication->original_name);
    }

    public function destroy(Publication $publication)
    {
        if ($publication->file_path && Storage::disk('public')->exists($publication->file_path)) {
            Storage::disk('public')->delete($publication->file_path);
        }
        $publication->delete();

        return redirect()->route('publications.index')->with('ok', 'Publicación eliminada.');
    }

    private function detectKind(?string $mime, string $ext): string
    {
        $mime = (string)$mime;
        $ext = strtolower($ext);

        if (str_starts_with($mime, 'image/')) return 'image';
        if (str_starts_with($mime, 'video/')) return 'video';
        if ($mime === 'application/pdf' || $ext === 'pdf') return 'pdf';

        $docExt = ['doc','docx','odt','rtf'];
        $xlsExt = ['xls','xlsx','csv','ods'];
        if (in_array($ext, $docExt, true)) return 'doc';
        if (in_array($ext, $xlsExt, true)) return 'sheet';

        return 'file';
    }
}
