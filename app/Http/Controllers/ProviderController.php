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
                    $sub->where('nombre', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('rfc', 'like', "%{$q}%")
                        ->orWhere('telefono', 'like', "%{$q}%")
                        ->orWhere('ciudad', 'like', "%{$q}%")
                        ->orWhere('estado', 'like', "%{$q}%");
                });
            })
            ->orderBy('nombre')
            ->paginate(12)
            ->withQueryString();

        return view('providers.index', compact('providers','q'));
    }

    public function create()
    {
        $provider = new Provider();
        return view('providers.form', compact('provider'));
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $provider = Provider::create($data);

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
        $data = $this->validateData($request, $provider->id);
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

    /** ---- Helpers ---- */
    private function validateData(Request $request, $ignoreId = null): array
    {
        $rules = [
            'nombre'       => ['required','string','max:255'],
            'email'        => [
                'required','email','max:255',
                Rule::unique('providers','email')->ignore($ignoreId),
            ],
            'rfc'          => ['nullable','string','max:50'],
            'tipo_persona' => ['nullable', Rule::in(['fisica','moral'])],
            'telefono'     => ['nullable','string','max:50'],
            'calle'        => ['nullable','string','max:255'],
            'colonia'      => ['nullable','string','max:255'],
            'ciudad'       => ['nullable','string','max:255'],
            'estado'       => ['nullable','string','max:255'],
            'cp'           => ['nullable','string','max:10'],
            'estatus'      => ['nullable','boolean'],
        ];

        $messages = [
            'required'   => 'El campo :attribute es obligatorio.',
            'email'      => 'El campo :attribute debe ser un correo válido.',
            'max'        => 'El campo :attribute no debe exceder :max caracteres.',
            'in'         => 'El campo :attribute no es válido.',
            'unique'     => 'El :attribute ya está registrado.',
        ];
        $attributes = [
            'nombre'       => 'nombre',
            'email'        => 'correo',
            'rfc'          => 'RFC/Número fiscal',
            'tipo_persona' => 'tipo de persona',
            'telefono'     => 'teléfono',
            'calle'        => 'calle',
            'colonia'      => 'colonia',
            'ciudad'       => 'ciudad',
            'estado'       => 'estado',
            'cp'           => 'código postal',
            'estatus'      => 'estatus',
        ];

        $data = $request->validate($rules, $messages, $attributes);
        // Normaliza estatus (switch)
        $data['estatus'] = (bool)($request->boolean('estatus', true));
        return $data;
    }
}
