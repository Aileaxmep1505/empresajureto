@extends('layouts.app')
@section('title','Alertas · Cuentas')

@section('content')
@include('accounting.partials.ui')

@php
  $q = fn(array $extra = []) => array_filter(array_merge(['company_id'=>$companyId], $extra), fn($v)=>$v!==null && $v!=='');
@endphp

<div class="ac-wrap">
  <div class="ac-head">
    <div>
      <div class="ac-title">Alertas</div>
      <div class="ac-sub">Pagos urgentes/atrasados y cobros vencidos.</div>
    </div>
    <div class="ac-actions">
      <a class="ac-btn" href="{{ route('accounting.dashboard', $q()) }}">← Dashboard</a>
      <a class="ac-btn" href="{{ route('accounting.payables.index', $q(['scope'=>'urgent'])) }}">Pagos urgentes</a>
      <a class="ac-btn" href="{{ route('accounting.receivables.index', $q(['scope'=>'overdue'])) }}">Cobros vencidos</a>
    </div>
  </div>

  <form class="ac-filters" method="GET" action="{{ route('accounting.alerts') }}">
    <select name="company_id" class="ac-inp" onchange="this.form.submit()">
      <option value="">Todas las compañías</option>
      @foreach($companies as $c)
        <option value="{{ $c->id }}" @selected($companyId==$c->id)>{{ $c->name }}</option>
      @endforeach
    </select>
    <a class="ac-btn" href="{{ route('accounting.alerts') }}">Limpiar</a>
  </form>

  <div class="ac-cardgrid">
    <div class="ac-card" style="grid-column:span 6">
      <div style="font-weight:900;margin-bottom:10px">Pagos urgentes / atrasados</div>
      @forelse($urgentPayments as $p)
        @php $saldo = max((float)$p->amount - (float)$p->amount_paid, 0); @endphp
        <div class="ac-form" style="padding:12px;border-radius:14px;margin-bottom:10px">
          <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap">
            <div>
              <div style="font-weight:900">{{ $p->title }}</div>
              <div class="ac-muted">{{ $p->company?->name }} · Vence: {{ optional($p->due_date)->format('d/m/Y') }} · {{ $p->status }}</div>
            </div>
            <div class="ac-right">
              <div style="font-weight:900">${{ number_format($saldo,2) }}</div>
              <a class="ac-btn" href="{{ route('accounting.payables.show',$p) }}">Abrir</a>
            </div>
          </div>
        </div>
      @empty
        <div class="ac-form" style="text-align:center"><div class="ac-muted">✅ Sin pagos urgentes</div></div>
      @endforelse
    </div>

    <div class="ac-card" style="grid-column:span 6">
      <div style="font-weight:900;margin-bottom:10px">Cobros vencidos</div>
      @forelse($overdueReceivables as $r)
        @php $saldo = max((float)$r->amount - (float)$r->amount_paid, 0); @endphp
        <div class="ac-form" style="padding:12px;border-radius:14px;margin-bottom:10px">
          <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap">
            <div>
              <div style="font-weight:900">{{ $r->client_name }}</div>
              <div class="ac-muted">{{ $r->company?->name }} · Vence: {{ optional($r->due_date)->format('d/m/Y') }} · {{ $r->status }}</div>
            </div>
            <div class="ac-right">
              <div style="font-weight:900">${{ number_format($saldo,2) }}</div>
              <a class="ac-btn" href="{{ route('accounting.receivables.show',$r) }}">Abrir</a>
            </div>
          </div>
        </div>
      @empty
        <div class="ac-form" style="text-align:center"><div class="ac-muted">✅ Sin cobros vencidos</div></div>
      @endforelse
    </div>
  </div>
</div>
@endsection