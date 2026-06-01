<?php

namespace App\Http\Controllers;

use App\Models\InventoryCategory;
use Illuminate\Http\Request;

class InventoryCategoryController extends Controller
{
  public function store(Request $request)
  {
    $data = $request->validate([
      'name' => 'required|string|max:255',
    ]);

    // Normaliza para evitar duplicados por espacios
    $name = trim(preg_replace('/\s+/', ' ', $data['name']));

    // evita duplicados por nombre
    $cat = InventoryCategory::firstOrCreate(['name' => $name]);

    return response()->json([
      'id' => $cat->id,
      'name' => $cat->name,
    ]);
  }
}
