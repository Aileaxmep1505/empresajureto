<?php

namespace App\Http\Controllers;

use App\Models\PayrollPeriod;
use App\Models\PayrollEntry;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
  public function periods(Request $request) {
    $q = PayrollPeriod::query()->withCount('entries');

    if ($request->filled('status')) $q->where('status', $request->get('status'));

    return response()->json($q->orderByDesc('start_date')->paginate(20));
  }

  public function createPeriod(Request $request) {
    $data = $request->validate([
      'frequency' => ['required','in:quincenal,mensual'],
      'start_date' => ['required','date'],
      'end_date' => ['required','date','after_or_equal:start_date'],
      'title' => ['nullable','string','max:140'],
    ]);

    $period = PayrollPeriod::create($data);
    return response()->json($period, 201);
  }

  public function periodDetail(PayrollPeriod $period) {
    $period->load(['entries.user','expenses']);
    return response()->json($period);
  }

  public function upsertEntry(Request $request) {
    $data = $request->validate([
      'payroll_period_id' => ['required','exists:payroll_periods,id'],
      'user_id' => ['required','exists:users,id'],
      'gross_amount' => ['nullable','numeric','min:0'],
      'deductions' => ['nullable','numeric','min:0'],
      'net_amount' => ['nullable','numeric','min:0'],
      'status' => ['nullable','in:pendiente,pagado'],
      'notes' => ['nullable','string','max:300'],
    ]);

    $entry = PayrollEntry::updateOrCreate(
      ['payroll_period_id'=>$data['payroll_period_id'], 'user_id'=>$data['user_id']],
      [
        'gross_amount'=>$data['gross_amount'] ?? 0,
        'deductions'=>$data['deductions'] ?? 0,
        'net_amount'=>$data['net_amount'] ?? (($data['gross_amount'] ?? 0) - ($data['deductions'] ?? 0)),
        'status'=>$data['status'] ?? 'pendiente',
        'notes'=>$data['notes'] ?? null,
      ]
    );

    return response()->json($entry->load('user','period'));
  }
}
