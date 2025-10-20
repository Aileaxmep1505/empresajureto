<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\LandingItem;
use App\Models\LandingSection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class LandingSectionController extends Controller
{
    public function index()
    {
        $sections = LandingSection::withCount('items')->orderBy('sort_order')->get();
        return view('panel.landing.index', compact('sections'));
    }

    public function create()
    {
        $layouts = $this->layouts();
        return view('panel.landing.form', [
            'section' => new LandingSection(),
            'layouts' => $layouts,
            'mode'    => 'create',
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules(), $this->messages());

        // Normaliza items (sube imagen si viene, quita borrados, limpia strings)
        $items = $this->normalizeItems($request->input('items', []), $request);

        DB::transaction(function () use ($data, $items) {
            $section = LandingSection::create([
                'name'       => $data['name'],
                'layout'     => $data['layout'],
                'is_active'  => !empty($data['is_active']),
                'sort_order' => (LandingSection::max('sort_order') ?? 0) + 1,
            ]);

            foreach ($items as $pos => $item) {
                $path = $item['image'] ?? null;
                LandingItem::create([
                    'landing_section_id' => $section->id,
                    'image_path'         => $path ?? '',
                    'title'              => $item['title'] ?? null,
                    'subtitle'           => $item['subtitle'] ?? null,
                    'cta_text'           => $item['cta_text'] ?? null,
                    'cta_url'            => $item['cta_url'] ?? null,
                    'sort_order'         => $pos + 1,
                ]);
            }
        });

        return redirect()->route('panel.landing.index')->with('ok','Sección creada.');
    }

    public function edit(LandingSection $section)
    {
        $section->load('items');
        $layouts = $this->layouts();
        return view('panel.landing.form', compact('section','layouts') + ['mode'=>'edit']);
    }

    public function update(Request $request, LandingSection $section)
    {
        $data = $request->validate($this->rules($updating = true), $this->messages());

        $items = $this->normalizeItems($request->input('items', []), $request);

        DB::transaction(function () use ($data, $items, $section) {
            $section->update([
                'name'      => $data['name'],
                'layout'    => $data['layout'],
                'is_active' => !empty($data['is_active']),
            ]);

            $position = 1;
            foreach ($items as $it) {
                // Borrado (soft-delete confirmado en update)
                if (!empty($it['_delete']) && !empty($it['id'])) {
                    $row = LandingItem::where('id', $it['id'])
                        ->where('landing_section_id', $section->id)
                        ->first();
                    if ($row) {
                        if ($row->image_path) Storage::disk('public')->delete($row->image_path);
                        $row->delete();
                    }
                    continue;
                }

                $payload = [
                    'title'      => $it['title'] ?? null,
                    'subtitle'   => $it['subtitle'] ?? null,
                    'cta_text'   => $it['cta_text'] ?? null,
                    'cta_url'    => $it['cta_url'] ?? null,
                    'sort_order' => $position++,
                ];

                if (!empty($it['image'])) {
                    $payload['image_path'] = $it['image']; // path ya generado en normalize
                }

                if (!empty($it['id'])) {
                    LandingItem::where('id', $it['id'])
                        ->where('landing_section_id', $section->id)
                        ->update($payload);
                } else {
                    $payload['landing_section_id'] = $section->id;
                    LandingItem::create($payload);
                }
            }
        });

        return back()->with('ok','Sección actualizada.');
    }

    public function destroy(LandingSection $section)
    {
        $section->load('items');
        foreach ($section->items as $it) {
            if ($it->image_path) Storage::disk('public')->delete($it->image_path);
        }
        $section->delete();
        return back()->with('ok','Sección eliminada.');
    }

    public function reorder(Request $request, LandingSection $section)
    {
        $request->validate(['order' => 'required|array']);
        foreach ($request->order as $pos => $id) {
            LandingItem::where('landing_section_id', $section->id)
                ->where('id', $id)->update(['sort_order' => $pos + 1]);
        }
        return response()->json(['ok' => true]);
    }

    public function toggle(LandingSection $section)
    {
        $section->is_active = ! $section->is_active;
        $section->save();
        return back()->with('ok', 'Sección ' . ($section->is_active ? 'activada' : 'desactivada') . '.');
    }

    /** ====================== Helpers ====================== */

    private function rules(bool $updating = false): array
    {
        $layouts = array_keys($this->layouts());

        $rules = [
            'name'      => ['required','string','max:120'],
            'layout'    => ['required', Rule::in($layouts)],
            'is_active' => ['nullable','boolean'],
            'items'     => ['array'],

            // Imagen opcional
            'items.*.image'    => ['nullable','image','max:4096'],
            'items.*.title'    => ['nullable','string','max:120'],
            'items.*.subtitle' => ['nullable','string','max:160'],
            'items.*.cta_text' => ['nullable','string','max:60'],

            // Acepta URL absolutas *o* rutas relativas tipo "/shop"
            'items.*.cta_url'  => ['nullable','string','max:255'],
            // Si quieres forzar http/https, usa:
            // 'items.*.cta_url'  => ['nullable','url:http,https'],
        ];

        if ($updating) {
            $rules['items.*.id']      = ['nullable','integer','exists:landing_items,id'];
            $rules['items.*._delete'] = ['nullable','in:0,1'];
        }

        return $rules;
    }

    private function messages(): array
    {
        return [
            'name.required'      => 'El nombre es obligatorio.',
            'layout.required'    => 'Selecciona un layout.',
            'layout.in'          => 'Layout inválido.',
            'items.array'        => 'El bloque de ítems no es válido.',
            'items.*.image.image'=> 'El archivo debe ser una imagen.',
            'items.*.image.max'  => 'La imagen no debe superar 4 MB.',
            // Si activas la regla url:http,https utiliza este:
            // 'items.*.cta_url.url'=> 'La URL del botón debe ser una URL válida (http/https).',
        ];
    }

    /**
     * Normaliza los items del request:
     * - Sube imágenes (devuelve path) si se adjuntan
     * - Mantiene marcados para borrar (_delete=1) con su id
     * - Limpia strings vacíos y filtra líneas completamente vacías
     */
    private function normalizeItems(array $raw, Request $request): array
    {
        $norm = [];
        foreach ($raw as $idx => $item) {

            // si está marcado para borrar y trae id -> mantener solo para borrar en update
            if (!empty($item['_delete']) && !empty($item['id'])) {
                $norm[] = [
                    'id'      => (int) $item['id'],
                    '_delete' => 1,
                ];
                continue;
            }

            // Subida de imagen (opcional)
            $path = null;
            if ($request->hasFile("items.$idx.image")) {
                $path = $request->file("items.$idx.image")->store('landing','public');
            }

            $row = [
                'id'        => !empty($item['id']) ? (int) $item['id'] : null,
                'image'     => $path ?: null, // solo si se subió
                'title'     => $item['title']    ?? null,
                'subtitle'  => $item['subtitle'] ?? null,
                'cta_text'  => $item['cta_text'] ?? null,
                'cta_url'   => $item['cta_url']  ?? null,
            ];

            // Limpieza de strings
            foreach (['title','subtitle','cta_text','cta_url'] as $k) {
                if (isset($row[$k])) {
                    $row[$k] = trim((string) $row[$k]) ?: null;
                }
            }

            $norm[] = $row;
        }

        // Quita filas totalmente vacías (sin id, sin imagen ni textos)
        $norm = array_values(array_filter($norm, function($it){
            if (!empty($it['_delete'])) return true; // mantener para borrar
            return $it['id'] || $it['image'] || $it['title'] || $it['subtitle'] || $it['cta_text'] || $it['cta_url'];
        }));

        return $norm;
    }

    private function layouts(): array
    {
        return [
            'banner-wide' => 'Banner ancho (1 foto a lo largo)',
            'grid-1'      => 'Una tarjeta (1x1)',
            'grid-2'      => 'Dos columnas (1x2)',
            'grid-3'      => 'Tres columnas (1x3)',
        ];
    }
}
