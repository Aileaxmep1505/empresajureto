<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use Illuminate\Http\Request;

class ExpenseCategoryController extends Controller
{
  public function index() {
    return response()->json(
      ExpenseCategory::orderBy('name')->get()
    );
  }

  public function store(Request $request) {
    $data = $request->validate([
      'name' => ['required','string','max:120','unique:expense_categories,name'],
      'slug' => ['nullable','string','max:140','unique:expense_categories,slug'],
      'type' => ['nullable','string','max:30'],
      'active' => ['nullable','boolean'],
    ]);
    $cat = ExpenseCategory::create($data);
    return response()->json($cat, 201);
  }

  public function show(ExpenseCategory $expenseCategory) {
    return response()->json($expenseCategory);
  }

  public function update(Request $request, ExpenseCategory $expenseCategory) {
    $data = $request->validate([
      'name' => ['sometimes','required','string','max:120','unique:expense_categories,name,'.$expenseCategory->id],
      'slug' => ['nullable','string','max:140','unique:expense_categories,slug,'.$expenseCategory->id],
      'type' => ['nullable','string','max:30'],
      'active' => ['nullable','boolean'],
    ]);
    $expenseCategory->update($data);
    return response()->json($expenseCategory);
  }

  public function destroy(ExpenseCategory $expenseCategory) {
    $expenseCategory->delete();
    return response()->json(['ok' => true]);
  }
}
