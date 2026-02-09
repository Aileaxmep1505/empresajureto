<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ExpenseCategoryController extends Controller
{
  public function index(Request $request)
  {
    $q = trim((string) $request->query('q', ''));

    $categories = ExpenseCategory::query()
      ->when($q, function($query) use ($q){
        $query->where('name','like',"%{$q}%")
              ->orWhere('slug','like',"%{$q}%")
              ->orWhere('type','like',"%{$q}%");
      })
      ->orderBy('name')
      ->paginate(30);

    return view('accounting.expense_categories.index', compact('categories','q'));
  }

  // JSON para selects/autocomplete
  public function feed(Request $request)
  {
    $q = trim((string) $request->query('q', ''));

    $categories = ExpenseCategory::query()
      ->when($q, fn($query) => $query->where('name','like',"%{$q}%"))
      ->orderBy('name')
      ->get();

    return response()->json($categories);
  }

  public function create()
  {
    return view('accounting.expense_categories.create');
  }

  public function store(Request $request)
  {
    $data = $request->validate([
      'name' => ['required','string','max:120','unique:expense_categories,name'],
      'slug' => ['nullable','string','max:140','unique:expense_categories,slug'],
      'type' => ['nullable','string','max:30'],
      'active' => ['nullable','boolean'],
    ]);

    if (empty($data['slug'])) $data['slug'] = Str::slug($data['name']);
    $data['active'] = (bool) ($request->input('active', true));

    ExpenseCategory::create($data);

    return redirect()->route('expense-categories.index')->with('ok', 'Categoría creada');
  }

  public function edit(ExpenseCategory $expenseCategory)
  {
    return view('accounting.expense_categories.edit', compact('expenseCategory'));
  }

  public function update(Request $request, ExpenseCategory $expenseCategory)
  {
    $data = $request->validate([
      'name' => ['required','string','max:120','unique:expense_categories,name,'.$expenseCategory->id],
      'slug' => ['nullable','string','max:140','unique:expense_categories,slug,'.$expenseCategory->id],
      'type' => ['nullable','string','max:30'],
      'active' => ['nullable','boolean'],
    ]);

    if (empty($data['slug'])) $data['slug'] = Str::slug($data['name']);
    $data['active'] = (bool) ($request->input('active', false));

    $expenseCategory->update($data);

    return redirect()->route('expense-categories.edit', $expenseCategory)->with('ok', 'Actualizado');
  }

  public function destroy(ExpenseCategory $expenseCategory)
  {
    $expenseCategory->delete();
    return redirect()->route('expense-categories.index')->with('ok', 'Eliminado');
  }
}
