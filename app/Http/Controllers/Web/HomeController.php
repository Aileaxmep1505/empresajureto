<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CatalogItem;

class HomeController extends Controller
{
    public function index()
    {
        $featured = CatalogItem::published()->featured()->ordered()->take(8)->get();
        $latest   = CatalogItem::published()->ordered()->take(12)->get();

        return view('web.home', compact('featured','latest'));
    }
}
