<?php

namespace App\Http\Controllers;

use App\Models\FinancialStatement;
use App\Models\UserActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FinancialStatementsController extends Controller
{
    private function log(
        string $action,
        string $description,
        ?FinancialStatement $statement = null,
        array $extra = []
    ): void {
        UserActivity::create([
            'user_id'     => auth()->id(),
            'action'      => $action,
            'module'      => 'Estados Financieros',
            'screen'      => 'Estados Financieros',
            'description' => $description,
            'document_id' => null,
            'route'       => request()->route()?->getName(),
            'path'        => request()->path(),
            'method'      => request()->method(),
            'status_code' => 200,
            'ip'          => request()->ip(),
            'user_agent'  => request()->userAgent(),
            'session_id'  => session()->getId(),
            'meta'        => array_merge(
                $statement ? [
                    'financial_statement_id' => $statement->id,
                    'titulo'                 => $statement->title,
                    'periodo'                => $statement->period,
                    'tipo'                   => $statement->type_label,
                    'archivo'                => $statement->file_name,
                    'tamaño'                 => $statement->file_size_human,
                ] : [],
                $extra
            ),
        ]);
    }

    /* ── Listado ─────────────────────────────────── */

    public function index(Request $request)
    {
        $query = FinancialStatement::with('uploader')->latest();

        if ($request->filled('type'))   $query->where('type', $request->type);
        if ($request->filled('period')) $query->where('period', 'like', '%' . $request->period . '%');

        $statements = $query->get();
        $grouped    = $statements->groupBy('period');

        // Solo logear si no es una petición de filtro para no saturar
        $hasFilters = $request->filled('type') || $request->filled('period');
        $desc = $hasFilters
            ? 'Filtró estados financieros' . ($request->filled('period') ? " por período \"{$request->period}\"" : '') . ($request->filled('type') ? " de tipo \"{$request->type}\"" : '') . " — {$statements->count()} resultado(s)"
            : "Accedió al módulo de Estados Financieros — {$statements->count()} documento(s) disponibles";

        $this->log('view', $desc, null, [
            'filtros'    => $request->only(['type', 'period']),
            'resultados' => $statements->count(),
        ]);

        return view('financial.index', compact('statements', 'grouped'));
    }

    /* ── Subir PDF ───────────────────────────────── */

    public function store(Request $request)
    {
        $request->validate([
            'title'  => 'required|string|max:200',
            'period' => 'required|string|max:50',
            'type'   => 'required|in:balance_general,estado_resultados,flujo_efectivo,notas,otro',
            'file'   => 'required|file|mimes:pdf|max:20480',
            'notes'  => 'nullable|string|max:1000',
        ], [
            'file.mimes' => 'Solo se permiten archivos PDF.',
            'file.max'   => 'El archivo no debe superar 20 MB.',
        ]);

        $file     = $request->file('file');
        $fileName = $file->getClientOriginalName();
        $safeName = Str::uuid() . '.pdf';
        $path     = $file->storeAs('financial-statements', $safeName, 'local');

        $statement = FinancialStatement::create([
            'uploaded_by' => auth()->id(),
            'title'       => $request->title,
            'period'      => $request->period,
            'type'        => $request->type,
            'file_path'   => $path,
            'file_name'   => $fileName,
            'file_size'   => $file->getSize(),
            'notes'       => $request->notes,
        ]);

        $this->log(
            'upload',
            "Subió el documento \"{$statement->title}\" ({$statement->type_label}) del período {$statement->period} — {$statement->file_size_human}",
            $statement
        );

        return back()->with('success', 'Estado financiero subido correctamente.');
    }

    /* ── Vista previa inline (stream PDF) ───────── */

    public function preview(FinancialStatement $statement)
    {
        if (!Storage::disk('local')->exists($statement->file_path)) {
            abort(404, 'Archivo no encontrado.');
        }

        $this->log(
            'preview',
            "Abrió vista previa de \"{$statement->title}\" ({$statement->type_label}, {$statement->period})",
            $statement
        );

        $content = Storage::disk('local')->get($statement->file_path);

        return response($content, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $statement->file_name . '"')
            ->header('Cache-Control', 'no-store, no-cache');
    }

    /* ── Descargar ───────────────────────────────── */

    public function download(FinancialStatement $statement)
    {
        if (!Storage::disk('local')->exists($statement->file_path)) {
            abort(404, 'Archivo no encontrado.');
        }

        $this->log(
            'download',
            "Descargó el documento \"{$statement->title}\" ({$statement->type_label}, {$statement->period}) — archivo: {$statement->file_name}",
            $statement
        );

        return Storage::disk('local')->download(
            $statement->file_path,
            $statement->file_name
        );
    }

    /* ── Eliminar ────────────────────────────────── */

    public function destroy(FinancialStatement $statement)
    {
        // Log ANTES de eliminar para que el $statement aún tenga datos
        $this->log(
            'delete',
            "Eliminó el documento \"{$statement->title}\" ({$statement->type_label}, {$statement->period}) — archivo: {$statement->file_name}",
            $statement
        );

        Storage::disk('local')->delete($statement->file_path);
        $statement->delete();

        return back()->with('success', 'Documento eliminado.');
    }
}