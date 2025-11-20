<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CatalogItem;
use App\Models\Comment; // 👈 importante

class HomeController extends Controller
{
    public function index()
    {
        $featured = CatalogItem::published()
            ->featured()
            ->ordered()
            ->take(8)
            ->get();

        $latest = CatalogItem::published()
            ->ordered()
            ->take(12)
            ->get();

        // 🔥 Comentarios para el marquee del home (solo raíz)
        $marqueeComments = Comment::query()
            ->with('user:id,name,email')
            ->whereNull('parent_id')
            ->latest()
            ->take(20)
            ->get();

        return view('web.home', compact('featured', 'latest', 'marqueeComments'));
    }

    public function about()
    {
        return view('web.sobre-nosotros');
    }
}
