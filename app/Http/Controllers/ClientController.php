<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $clients = Client::query()
            ->when($q !== '', function ($qry) use ($q) {
                $qry->where(function ($sub) use ($q) {
                    $sub->where('nombre', 'like', "%{$q}%")
                        ->orWhere('razon_social', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('rfc', 'like', "%{$q}%")
                        ->orWhere('telefono', 'like', "%{$q}%")
                        ->orWhere('tipo_cliente', 'like', "%{$q}%")
                        ->orWhere('tipo_persona', 'like', "%{$q}%")
                        ->orWhere('ciudad', 'like', "%{$q}%")
                        ->orWhere('municipio', 'like', "%{$q}%")
                        ->orWhere('estado', 'like', "%{$q}%")
                        ->orWhere('cp', 'like', "%{$q}%");
                });
            })
            ->orderBy('nombre')
            ->paginate(12)
            ->withQueryString();

        return view('clients.index', compact('clients', 'q'));
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
            ->with('status', 'Cliente creado correctamente.');
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
            ->with('status', 'Cliente actualizado correctamente.');
    }

    public function destroy(Request $request, Client $client)
    {
        $client->delete();

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('status', 'Cliente eliminado.');
    }

    /** -------- Helpers ---------- */
    private function validateData(Request $request, $ignoreId = null): array
    {
        $rules = [
            'nombre'        => ['required', 'string', 'max:255'],
            'razon_social'  => ['nullable', 'string', 'max:255'],

            'email'         => [
                'required',
                'email',
                'max:255',
                Rule::unique('clients', 'email')->ignore($ignoreId),
            ],

            // Segmento comercial
            'tipo_cliente'  => ['nullable', Rule::in(['gobierno', 'empresa', 'particular', 'otro'])],

            // Fiscal
            'tipo_persona'  => ['nullable', Rule::in(['fisica', 'moral'])],
            'rfc'           => ['nullable', 'string', 'max:50'],
            // c_RegimenFiscal SAT (3 dígitos, ej. 601, 612, 626, etc.)
            'regimen_fiscal'=> ['nullable', 'string', 'max:3'],
            // c_UsoCFDI (G01, G03, D01, etc.)
            'cfdi_uso'      => ['nullable', 'string', 'max:3'],

            'contacto'      => ['nullable', 'string', 'max:255'],
            'telefono'      => ['nullable', 'string', 'max:50'],

            // Domicilio fiscal
            'pais'          => ['nullable', 'string', 'max:3'],
            'calle'         => ['nullable', 'string', 'max:255'],
            'num_exterior'  => ['nullable', 'string', 'max:20'],
            'num_interior'  => ['nullable', 'string', 'max:20'],
            'colonia'       => ['nullable', 'string', 'max:255'],
            'cp'            => ['nullable', 'string', 'max:10'],
            'ciudad'        => ['nullable', 'string', 'max:255'],
            'municipio'     => ['nullable', 'string', 'max:255'],
            'estado'        => ['nullable', 'string', 'max:255'],

            'estatus'       => ['nullable', 'boolean'],
        ];

        $messages = [
            'required' => 'El campo :attribute es obligatorio.',
            'email'    => 'El campo :attribute debe ser un correo válido.',
            'max'      => 'El campo :attribute no debe exceder :max caracteres.',
            'in'       => 'El campo :attribute no es válido.',
            'unique'   => 'El :attribute ya está registrado.',
        ];

        $attributes = [
            'nombre'         => 'nombre',
            'razon_social'   => 'nombre registrado en el SAT',
            'email'          => 'correo electrónico',
            'tipo_cliente'   => 'tipo de cliente',
            'tipo_persona'   => 'tipo de persona',
            'rfc'            => 'RFC',
            'regimen_fiscal' => 'régimen fiscal',
            'cfdi_uso'       => 'uso CFDI',
            'contacto'       => 'contacto',
            'telefono'       => 'teléfono',
            'pais'           => 'país',
            'calle'          => 'calle',
            'num_exterior'   => 'número exterior',
            'num_interior'   => 'número interior',
            'colonia'        => 'colonia',
            'cp'             => 'código postal',
            'ciudad'         => 'ciudad',
            'municipio'      => 'municipio',
            'estado'         => 'estado',
            'estatus'        => 'estatus',
        ];

        $data = $request->validate($rules, $messages, $attributes);

        // Defaults y normalización
        $data['estatus'] = (bool) $request->boolean('estatus', true);
        if (empty($data['pais'])) {
            $data['pais'] = 'MEX';
        }

        return $data;
    }
}
