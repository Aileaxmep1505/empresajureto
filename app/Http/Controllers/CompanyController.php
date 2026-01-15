<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CompanyController extends Controller
{
    public function create()
    {
        return view('companies.create');
    }

    /**
     * Guarda la empresa (producción) y SIEMPRE redirige a /part-contable.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'    => ['required', 'string', 'max:255'],
            'rfc'     => ['nullable', 'string', 'max:20'],
            'phone'   => ['nullable', 'string', 'max:50'],
            'email'   => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        // Normalización backend (producción)
        if (!empty($data['rfc'])) {
            $data['rfc'] = mb_strtoupper(preg_replace('/\s+/', '', $data['rfc']), 'UTF-8');
        }
        if (!empty($data['phone'])) {
            $data['phone'] = preg_replace('/[^\d\s\+\-\(\)]/', '', $data['phone']);
        }

        // ✅ Slug obligatorio en BD: generarlo y hacerlo único
        $base = Str::slug($data['name']);
        $slug = $base ?: 'empresa';

        $i = 1;
        while (Company::where('slug', $slug)->exists()) {
            $i++;
            $slug = $base ? ($base . '-' . $i) : ('empresa-' . $i);
        }

        $data['slug'] = $slug;

        Company::create($data);

        // ✅ SIEMPRE regresar a /part-contable
        return redirect('/part-contable')->with('success', 'Empresa creada correctamente.');
    }
}
