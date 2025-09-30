<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string)$request->get('q',''));

        $clients = Client::query()
            ->when($q !== '', function($qry) use ($q) {
                $qry->where(function($sub) use ($q){
                    $sub->where('nombre','like',"%{$q}%")
                        ->orWhere('email','like',"%{$q}%")
                        ->orWhere('rfc','like',"%{$q}%")
                        ->orWhere('telefono','like',"%{$q}%")
                        ->orWhere('tipo_cliente','like',"%{$q}%")
                        ->orWhere('ciudad','like',"%{$q}%")
                        ->orWhere('estado','like',"%{$q}%");
                });
            })
            ->orderBy('nombre')
            ->paginate(12)
            ->withQueryString();

        return view('clients.index', compact('clients','q'));
    }

    public function create()
    {
        $client = new Client();
        return view('clients.form', compact('client'));
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        Client::create($data);

        return redirect()
            ->route('clients.index')
            ->with('status','Cliente creado correctamente.');
    }

    public function edit(Client $client)
    {
        return view('clients.form', compact('client'));
    }

    public function update(Request $request, Client $client)
    {
        $data = $this->validateData($request, $client->id);
        $client->update($data);

        return redirect()
            ->route('clients.index')
            ->with('status','Cliente actualizado correctamente.');
    }

    public function destroy(Request $request, Client $client)
    {
        $client->delete();

        if ($request->expectsJson()) {
            return response()->json(['ok'=>true]);
        }
        return back()->with('status','Cliente eliminado.');
    }

    /** -------- Helpers ---------- */
    private function validateData(Request $request, $ignoreId = null): array
    {
        $rules = [
            'nombre'       => ['required','string','max:255'],
            'email'        => [
                'required','email','max:255',
                Rule::unique('clients','email')->ignore($ignoreId),
            ],
            'tipo_cliente' => ['nullable', Rule::in(['gobierno','empresa'])],
            'rfc'          => ['nullable','string','max:50'],
            'contacto'     => ['nullable','string','max:255'],
            'telefono'     => ['nullable','string','max:50'],
            'calle'        => ['nullable','string','max:255'],
            'colonia'      => ['nullable','string','max:255'],
            'ciudad'       => ['nullable','string','max:255'],
            'estado'       => ['nullable','string','max:255'],
            'cp'           => ['nullable','string','max:10'],
            'estatus'      => ['nullable','boolean'],
        ];

        $messages = [
            'required' => 'El campo :attribute es obligatorio.',
            'email'    => 'El campo :attribute debe ser un correo válido.',
            'max'      => 'El campo :attribute no debe exceder :max caracteres.',
            'in'       => 'El campo :attribute no es válido.',
            'unique'   => 'El :attribute ya está registrado.',
        ];

        $attributes = [
            'nombre'       => 'nombre',
            'email'        => 'correo',
            'tipo_cliente' => 'tipo de cliente',
            'rfc'          => 'RFC / número fiscal',
            'contacto'     => 'contacto',
            'telefono'     => 'teléfono',
            'calle'        => 'calle',
            'colonia'      => 'colonia',
            'ciudad'       => 'ciudad',
            'estado'       => 'estado',
            'cp'           => 'código postal',
            'estatus'      => 'estatus',
        ];

        $data = $request->validate($rules, $messages, $attributes);
        $data['estatus'] = (bool)$request->boolean('estatus', true);
        return $data;
    }
}
