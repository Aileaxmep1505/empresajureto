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
        $data = $request->validate([
            'name'      => ['required','string','max:120'],
            'layout'    => ['required', Rule::in(array_keys($this->layouts()))],
            'is_active' => ['nullable','boolean'],
            'items'     => ['array'],
            'items.*.image'    => ['nullable','image','max:4096'],
            'items.*.title'    => ['nullable','string','max:120'],
            'items.*.subtitle' => ['nullable','string','max:160'],
            'items.*.cta_text' => ['nullable','string','max:60'],
            'items.*.cta_url'  => ['nullable','url'],
        ]);

        DB::transaction(function () use ($data, $request) {
            $section = LandingSection::create([
                'name'      => $data['name'],
                'layout'    => $data['layout'],
                'is_active' => $request->boolean('is_active'),
                'sort_order'=> (LandingSection::max('sort_order') ?? 0) + 1,
            ]);

            foreach (($data['items'] ?? []) as $idx => $item) {
                $path = null;
                if (isset($item['image'])) {
                    $path = $item['image']->store('landing', 'public');
                }
                LandingItem::create([
                    'landing_section_id' => $section->id,
                    'image_path' => $path ?? '',
                    'title'      => $item['title'] ?? null,
                    'subtitle'   => $item['subtitle'] ?? null,
                    'cta_text'   => $item['cta_text'] ?? null,
                    'cta_url'    => $item['cta_url'] ?? null,
                    'sort_order' => $idx + 1,
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
        $data = $request->validate([
            'name'      => ['required','string','max:120'],
            'layout'    => ['required', Rule::in(array_keys($this->layouts()))],
            'is_active' => ['nullable','boolean'],
            'items'     => ['array'],
            'items.*.id'       => ['nullable','integer','exists:landing_items,id'],
            'items.*.image'    => ['nullable','image','max:4096'],
            'items.*.title'    => ['nullable','string','max:120'],
            'items.*.subtitle' => ['nullable','string','max:160'],
            'items.*.cta_text' => ['nullable','string','max:60'],
            'items.*.cta_url'  => ['nullable','url'],
            'items.*._delete'  => ['nullable','boolean'],
        ]);

        DB::transaction(function () use ($data, $request, $section) {
            $section->update([
                'name'      => $data['name'],
                'layout'    => $data['layout'],
                'is_active' => $request->boolean('is_active'),
            ]);

            $position = 1;
            foreach (($data['items'] ?? []) as $item) {
                // eliminar
                if (!empty($item['_delete']) && !empty($item['id'])) {
                    $row = LandingItem::where('id',$item['id'])->where('landing_section_id',$section->id)->first();
                    if ($row) {
                        if ($row->image_path) Storage::disk('public')->delete($row->image_path);
                        $row->delete();
                    }
                    continue;
                }

                // actualizar o crear
                $payload = [
                    'title'      => $item['title'] ?? null,
                    'subtitle'   => $item['subtitle'] ?? null,
                    'cta_text'   => $item['cta_text'] ?? null,
                    'cta_url'    => $item['cta_url'] ?? null,
                    'sort_order' => $position++,
                ];

                if (isset($item['image'])) {
                    $payload['image_path'] = $item['image']->store('landing','public');
                }

                if (!empty($item['id'])) {
                    LandingItem::where('id',$item['id'])
                        ->where('landing_section_id',$section->id)
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
            LandingItem::where('landing_section_id',$section->id)
                ->where('id',$id)->update(['sort_order' => $pos+1]);
        }
        return response()->json(['ok'=>true]);
    }

    public function toggle(LandingSection $section)
    {
        $section->is_active = ! $section->is_active;
        $section->save();
        return back()->with('ok', 'Sección '.($section->is_active?'activada':'desactivada').'.');
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
