<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SmartSearch;
use App\Models\CatalogItem;

class SearchController extends Controller
{
    public function __construct(private SmartSearch $search) {}

    public function index(Request $request)
    {
        $q       = (string) $request->query('q', '');
        $order   = (string) $request->query('order', 'sugerido');

        // Estos filtros extra se aceptan pero solo se aplica "disponible"
        $filters = [
            'disponible' => $request->boolean('disponible'),
            'envio'      => $request->boolean('envio_gratis'),
            'express'    => $request->boolean('express'),
            'msi'        => $request->boolean('msi'),
            'club'       => $request->boolean('club'),
        ];

        $results = $this->search->search($q, $filters, $order)->appends($request->query());

        $stats = [
            'q'        => $q,
            'count'    => $results->total(),
            'expanded' => $this->search->expandOnly($q)->all(),
        ];

        return view('search.index', compact('results','stats','order','filters'));
    }

    public function suggest(Request $request)
    {
        $seed  = (string) $request->query('term', '');
        $terms = $this->search->suggest($seed);

        // Top productos por nombre/sku que contengan el seed
        $like = '%' . str_replace(' ', '%', $seed) . '%';

        $top = CatalogItem::query()
            ->when($seed !== '', function ($q) use ($like) {
                $q->where(function ($qq) use ($like) {
                    $qq->where('name','like',$like)
                       ->orWhere('sku','like',$like)
                       ->orWhere('excerpt','like',$like);
                });
            })
            // ordenar por publicados recientes luego creados
            ->orderByRaw('published_at IS NULL')
            ->orderBy('published_at','desc')
            ->orderBy('created_at','desc')
            ->limit(5)
            ->get(['id','name']);

        return response()->json(['terms'=>$terms, 'products'=>$top]);
    }
}
