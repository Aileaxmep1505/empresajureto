<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CatalogItem;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    // GET /favoritos  (solo usuarios logueados)
    public function index(Request $request)
    {
        $items = $request->user()
            ->favorites() // Eager load removido para evitar RelationNotFoundException
            ->latest('favorites.created_at')
            ->paginate(24);

        return view('web.favoritos.index', compact('items'));
    }

    // POST /favoritos/toggle/{item}
    public function toggle(Request $request, CatalogItem $item)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message'=>'Unauthenticated'], 401);
        }

        $exists = $user->favorites()->where('catalog_item_id', $item->id)->exists();

        if ($exists) {
            $user->favorites()->detach($item->id);
            $state = 'removed';
        } else {
            $user->favorites()->attach($item->id);
            $state = 'added';
        }

        $count = $user->favorites()->count();

        return response()->json([
            'status' => $state,
            'count'  => $count,
            'itemId' => $item->id,
        ]);
    }

    // DELETE /favoritos/{item}
    public function destroy(Request $request, CatalogItem $item)
    {
        $request->user()->favorites()->detach($item->id);
        return back()->with('ok','Eliminado de favoritos.');
    }
}
