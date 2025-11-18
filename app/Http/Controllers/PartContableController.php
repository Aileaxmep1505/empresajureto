<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentSection;
use App\Models\DocumentSubtype;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class PartContableController extends Controller
{
    // Index de empresas (grid)
    public function index()
    {
        $companies = Company::all();
        return view('partcontable.index', compact('companies'));
    }

    // Show create form to upload documents for a company
    public function createDocument(Company $company)
    {
        $sections = DocumentSection::orderBy('name')->get();
        $subtypes = DocumentSubtype::orderBy('name')->get();
        $defaultSection = $sections->first();

        return view('partcontable.create', compact('company', 'sections', 'subtypes', 'defaultSection'));
    }

    // Vista de empresa con documentos filtrados
    public function showCompany(Request $request, Company $company)
    {
        $sections = DocumentSection::orderBy('name')->get();
        if ($sections->isEmpty()) {
            return view('partcontable.company', compact('company'))
                ->with('warning','No hay secciones configuradas. Crea secciones en el panel.');
        }

        $sectionKey = $request->get('section', $sections->first()->key);
        $section = DocumentSection::where('key', $sectionKey)->first() ?: $sections->first();

        $year = $request->get('year');
        $month = $request->get('month');

        $query = Document::where('company_id', $company->id)
            ->where('section_id', $section->id);

        if ($year) $query->whereYear('date', $year);
        if ($month) $query->whereMonth('date', $month);

        $documents = $query->orderByDesc('date')->paginate(12)->withQueryString();
        $subtypes = $section->subtypes()->orderBy('name')->get();

        return view('partcontable.company', compact('company','sections','section','documents','subtypes','year','month'));
    }

    // Store uploaded document(s)
    public function storeDocument(Request $request, Company $company)
    {
        $isSingle = $request->hasFile('file') && ! $request->hasFile('files');
        $isMulti  = $request->hasFile('files');

        if (! $isSingle && ! $isMulti) {
            return response()->json(['ok' => false, 'message' => 'No se detectó archivo.'], 422);
        }

        $request->validate([
            'section_id'  => 'required|exists:document_sections,id',
            'subtype_id'  => 'nullable|exists:document_subtypes,id',
            'title'       => 'nullable|string|max:255',
            'title_global'=> 'nullable|string|max:255',
            'description' => 'nullable|string',
            'description_global' => 'nullable|string',
            'date'        => 'nullable|date',
        ]);

        $section = DocumentSection::findOrFail($request->input('section_id'));
        $metaTitle = $request->input('title') ?: $request->input('title_global');
        $metaDescription = $request->input('description') ?: $request->input('description_global');
        $metaDate = $request->input('date') ?: now()->toDateString();

        $storedDocs = [];
        DB::beginTransaction();

        try {
            $filesToProcess = $isSingle ? [$request->file('file')] : $request->file('files');

            foreach ($filesToProcess as $file) {
                if (! $file->isValid()) throw new \Exception('Archivo inválido: '.$file->getClientOriginalName());

                $allowed = ['jpg','jpeg','png','gif','webp','svg','mp4','mov','pdf','doc','docx','xls','xlsx'];
                $ext = strtolower($file->getClientOriginalExtension());
                if (! in_array($ext, $allowed)) throw new \Exception('Formato no permitido: '.$file->getClientOriginalName());
                if ($file->getSize() > 30*1024*1024) throw new \Exception('Archivo muy grande: '.$file->getClientOriginalName());

                $d = \Carbon\Carbon::parse($metaDate);
                $year = $d->year;
                $month = $d->month;

                $slug = Str::slug($metaTitle ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
                $filename = $slug.'_'.time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
                $subdir = "partcontable/{$company->id}/{$section->key}/{$year}/{$month}";

                $storedPath = $file->storeAs($subdir, $filename, 'public');

                $mime = $file->getClientMimeType() ?: 'application/octet-stream';
                $type = $this->detectType($mime);

                $document = Document::create([
                    'company_id'  => $company->id,
                    'section_id'  => $section->id,
                    'subtype_id'  => $request->input('subtype_id') ?: null,
                    'title'       => $metaTitle ?: $file->getClientOriginalName(),
                    'description' => $metaDescription,
                    'file_path'   => $storedPath,
                    'file_type'   => $type,
                    'mime_type'   => $mime,
                    'date'        => $metaDate,
                    'uploaded_by' => auth()->id() ?? null,
                ]);

                $storedDocs[] = $document;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            foreach ($storedDocs as $sd) {
                Storage::disk('public')->delete($sd->file_path);
                $sd->delete();
            }
            \Log::error('storeDocument error: '.$e->getMessage());
            return response()->json(['ok' => false, 'message' => 'Error al subir: '.$e->getMessage()], 500);
        }

        return response()->json([
            'ok' => true,
            'uploaded' => count($storedDocs),
            'documents' => collect($storedDocs)->map->only(['id','title','file_path','file_type','mime_type'])->all()
        ]);
    }

    // Detecta tipo (foto/video/documento)
    protected function detectType($mime)
    {
        if (str_starts_with($mime,'image/')) return 'foto';
        if (str_starts_with($mime,'video/')) return 'video';
        return 'documento';
    }

    // Descargar archivo
    public function download(Document $document)
    {
        if (!Storage::disk('public')->exists($document->file_path)) abort(404);
        $filename = $document->title . '.' . pathinfo($document->file_path, PATHINFO_EXTENSION);
        return Storage::disk('public')->download($document->file_path, $filename);
    }

    // Preview inline para imágenes, videos o PDFs
  public function preview($id)
    {
        $document = Document::findOrFail($id);

        // Verificar que el archivo exista en storage
        if (!Storage::disk('public')->exists($document->file_path)) {
            abort(404, 'El archivo no existe.');
        }

        return view('partcontable.preview', compact('document'));
    }

    // Eliminar documento
    public function destroy(Request $request, Document $document)
    {
        $path = $document->file_path;

        DB::beginTransaction();
        try {
            $document->delete();
            Storage::disk('public')->delete($path);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            if ($request->ajax()) return response()->json(['ok' => false, 'message' => 'No se pudo eliminar.'],500);
            return back()->withErrors(['file'=>'No se pudo eliminar.']);
        }

        if ($request->ajax()) return response()->json(['ok'=>true]);
        return back()->with('success','Documento eliminado.');
    }
}
