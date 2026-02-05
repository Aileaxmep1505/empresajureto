<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
  public function index(Request $request) {
    $q = Expense::query()->with(['category','vehicle','creator','attachments']);

    if ($request->filled('from')) $q->whereDate('expense_date','>=',$request->get('from'));
    if ($request->filled('to')) $q->whereDate('expense_date','<=',$request->get('to'));
    if ($request->filled('vehicle_id')) $q->where('vehicle_id',$request->get('vehicle_id'));
    if ($request->filled('category_id')) $q->where('expense_category_id',$request->get('category_id'));
    if ($request->filled('status')) $q->where('status',$request->get('status'));

    $expenses = $q->orderByDesc('expense_date')->paginate(30);
    return response()->json($expenses);
  }

  public function store(Request $request) {
    $data = $request->validate([
      'expense_category_id' => ['nullable','exists:expense_categories,id'],
      'vehicle_id' => ['nullable','exists:vehicles,id'],
      'payroll_period_id' => ['nullable','exists:payroll_periods,id'],
      'vendor' => ['nullable','string','max:160'],
      'expense_date' => ['required','date'],
      'concept' => ['required','string','max:200'],
      'description' => ['nullable','string'],
      'amount' => ['required','numeric','min:0'],
      'currency' => ['nullable','string','size:3'],
      'payment_method' => ['nullable','string','max:30'],
      'status' => ['nullable','string','max:30'],
      'tags' => ['nullable','string','max:400'],
    ]);

    $data['created_by'] = auth()->id();

    $expense = Expense::create($data);
    return response()->json($expense->load(['category','vehicle','creator']), 201);
  }

  public function show(Expense $expense) {
    return response()->json($expense->load(['category','vehicle','creator','attachments']));
  }

  public function update(Request $request, Expense $expense) {
    $data = $request->validate([
      'expense_category_id' => ['nullable','exists:expense_categories,id'],
      'vehicle_id' => ['nullable','exists:vehicles,id'],
      'payroll_period_id' => ['nullable','exists:payroll_periods,id'],
      'vendor' => ['nullable','string','max:160'],
      'expense_date' => ['nullable','date'],
      'concept' => ['nullable','string','max:200'],
      'description' => ['nullable','string'],
      'amount' => ['nullable','numeric','min:0'],
      'currency' => ['nullable','string','size:3'],
      'payment_method' => ['nullable','string','max:30'],
      'status' => ['nullable','string','max:30'],
      'tags' => ['nullable','string','max:400'],
    ]);

    $expense->update($data);
    return response()->json($expense->load(['category','vehicle','creator','attachments']));
  }

  public function destroy(Expense $expense) {
    $expense->delete();
    return response()->json(['ok' => true]);
  }
}
