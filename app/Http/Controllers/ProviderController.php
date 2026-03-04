<?php

namespace App\Http\Controllers;

use App\Models\Provider;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProviderController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string)$request->get('q',''));

        $providers = Provider::query()
            ->when($q !== '', function($qry) use ($q) {
                $qry->where(function($sub) use ($q){
                    $sub->where('code', 'like', "%{$q}%")
                        ->orWhere('empresa', 'like', "%{$q}%")
                        ->orWhere('nombre', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('rfc', 'like', "%{$q}%")
                        ->orWhere('tipo_persona', 'like', "%{$q}%")
                        ->orWhere('telefono', 'like', "%{$q}%")
                        ->orWhere('calle', 'like', "%{$q}%")
                        ->orWhere('colonia', 'like', "%{$q}%")
                        ->orWhere('cp', 'like', "%{$q}%")
                        ->orWhere('ciudad', 'like', "%{$q}%")
                        ->orWhere('estado', 'like', "%{$q}%");
                });
            })
            ->orderBy('id','desc')
            ->get();

        return view('providers.index', compact('providers','q'));
    }

    public function create()
    {
        $provider = new Provider();
        return view('providers.form', compact('provider'));
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request, null, true);

        Provider::create($data);

        return redirect()
            ->route('providers.index')
            ->with('status', 'Proveedor creado correctamente.');
    }

    public function edit(Provider $provider)
    {
        return view('providers.form', compact('provider'));
    }

    public function update(Request $request, Provider $provider)
    {
        $data = $this->validateData($request, $provider->id, false);

        $provider->update($data);

        return redirect()
            ->route('providers.index')
            ->with('status', 'Proveedor actualizado correctamente.');
    }

    public function destroy(Request $request, Provider $provider)
    {
        $provider->delete();

        if ($request->expectsJson()) {
            return response()->json(['ok'=>true]);
        }

        return back()->with('status','Proveedor eliminado.');
    }

    private function validateData(Request $request, $ignoreId = null, bool $defaultEstatus = true): array
    {
        $rules = [
            'empresa'      => ['required','string','max:255'],

            'nombre'       => ['required','string','max:255'],
            'email'        => [
                'required','email','max:255',
                Rule::unique('providers','email')->ignore($ignoreId),
            ],
            'telefono'     => ['nullable','string','max:50'],

            'rfc'          => ['nullable','string','max:50'],
            'tipo_persona' => ['nullable','string','max:50'],

            'calle'        => ['nullable','string','max:255'],
            'colonia'      => ['nullable','string','max:255'],
            'cp'           => ['nullable','string','max:10'],
            'ciudad'       => ['nullable','string','max:255'],
            'estado'       => ['nullable','string','max:255'],

            'estatus'      => ['nullable','boolean'],
        ];

        $messages = [
            'required' => 'El campo :attribute es obligatorio.',
            'email'    => 'El campo :attribute debe ser un correo válido.',
            'max'      => 'El campo :attribute no debe exceder :max caracteres.',
            'unique'   => 'El :attribute ya está registrado.',
        ];

        $attributes = [
            'empresa'      => 'empresa',
            'nombre'       => 'nombre del contacto',
            'email'        => 'correo',
            'telefono'     => 'teléfono',
            'rfc'          => 'RFC',
            'tipo_persona' => 'tipo de persona',
            'calle'        => 'calle',
            'colonia'      => 'colonia',
            'cp'           => 'código postal',
            'ciudad'       => 'ciudad',
            'estado'       => 'estado',
            'estatus'      => 'estatus',
        ];

        $data = $request->validate($rules, $messages, $attributes);

        // ✅ Si NO viene el checkbox, en create default true; en update default false
        $data['estatus'] = (bool) $request->boolean('estatus', $defaultEstatus);

        return $data;
    }
}