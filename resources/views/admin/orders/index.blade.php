@extends('layouts.web')
@section('title','Pedidos')

@section('content')
<style>
#orders{
  --bg:#f6f7fb; --card:#fff; --line:#e8eef6; --ink:#0f172a; --muted:#64748b;
  --brand:#6d28d9; --brand-2:#7c3aed; --soft:#f4f2ff;
  --ok:#16a34a; --okbg:#eafff1;
  --warn:#b45309; --warnbg:#fff3d6;
  --bad:#b91c1c; --badbg:#ffe4e6;
  --radius:16px; --shadow:0 20px 50px rgba(2,8,23,.08);
}
#orders{background:var(--bg); padding:28px 0}
#orders .wrap{max-width:1180px; margin:0 auto; padding:0 18px}
#orders h1{margin:0 0 14px; font-size:28px; letter-spacing:-.02em; color:var(--ink)}
#orders .topbar{display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:16px}
#orders .export{
  display:inline-flex; align-items:center; gap:10px;
  padding:11px 14px; border-radius:12px; border:1px solid var(--line);
  background:var(--card); color:var(--brand); font-weight:800; text-decoration:none;
}
#orders .export:hover{box-shadow:0 10px 24px rgba(2,8,23,.08)}
#orders .panel{background:var(--card); border:1px solid var(--line); border-radius:22px; box-shadow:var(--shadow); overflow:hidden}
#orders .filters{display:grid; grid-template-columns: 1.2fr 1fr 1fr 1fr; gap:14px; padding:18px}
#orders label{display:block; font-size:12px; color:var(--muted); margin:0 0 8px; font-weight:700; letter-spacing:.02em}
#orders .field{position:relative}
#orders input, #orders select{
  width:100%; padding:12px 14px;
  border:1px solid var(--line); border-radius:14px;
  background:#fff; color:var(--ink); outline:none;
}
#orders input:focus, #orders select:focus{box-shadow:0 0 0 4px rgba(124,58,237,.12); border-color:#ddd6fe}

#orders .tabs{display:flex; gap:4px; padding:0 12px; border-top:1px solid var(--line); border-bottom:1px solid var(--line); background:#fff}
#orders .tab{
  display:inline-flex; align-items:center; gap:8px;
  padding:12px 14px; border-radius:12px; margin:10px 6px;
  text-decoration:none; color:var(--muted); font-weight:800;
}
#orders .tab.active{background:var(--soft); color:var(--brand)}
#orders .tablewrap{overflow:auto}
#orders table{width:100%; border-collapse:collapse; min-width:860px}
#orders th, #orders td{padding:14px 16px; border-bottom:1px solid #f1f5f9; font-size:14px}
#orders th{background:#fbfcff; color:#111827; font-size:13px; text-transform:none}
#orders .check{width:42px}
#orders .orderlink{color:#0f172a; font-weight:800; text-decoration:none}
#orders .orderlink:hover{color:var(--brand)}
#orders .badge{
  display:inline-flex; align-items:center; padding:6px 10px;
  border-radius:999px; font-size:12px; font-weight:900;
  border:1px solid transparent;
}
#orders .b-ok{background:var(--okbg); color:var(--ok)}
#orders .b-warn{background:var(--warnbg); color:var(--warn)}
#orders .b-bad{background:var(--badbg); color:var(--bad)}
#orders .muted{color:var(--muted)}
#orders .footer{padding:14px 16px}

@media (max-width:980px){
  #orders .filters{grid-template-columns:1fr 1fr; }
}
@media (max-width:620px){
  #orders .topbar{flex-direction:column; align-items:flex-start}
  #orders .filters{grid-template-columns:1fr}
  #orders table{min-width:720px}
}
</style>

<div id="orders">
  <div class="wrap">
    <div class="topbar">
      <h1>Pedidos</h1>
      <a class="export" href="{{ route('admin.orders.export') }}">⬆️ Exportar a CSV</a>
    </div>

    <div class="panel">
      <form method="GET" class="filters">
        <div class="field">
          <label>Buscar pedido</label>
          <input name="q" value="{{ $q }}" placeholder="Buscar por #, correo, nombre…">
        </div>

        <div class="field">
          <label>Procesamiento</label>
          <select name="tab">
            <option value="hacer"      @selected($tab==='hacer')>Hacer</option>
            <option value="incumplido" @selected($tab==='incumplido')>Incumplido</option>
            <option value="sin_pagar"  @selected($tab==='sin_pagar')>Sin pagar</option>
            <option value="accion"     @selected($tab==='accion')>Acción necesaria</option>
            <option value="archivada"  @selected($tab==='archivada')>Archivada</option>
            <option value="pagado"     @selected($tab==='pagado')>Pagado</option>
          </select>
        </div>

        <div class="field">
          <label>Pago</label>
          <select name="pago">
            <option value="" @selected($pago==='')>Seleccionar estado de pago</option>
            <option value="pagado" @selected($pago==='pagado')>Pagado</option>
            <option value="esperando" @selected($pago==='esperando')>Esperando</option>
          </select>
        </div>

        <div class="field">
          <label>Producto</label>
          <input name="producto" value="{{ $prod }}" placeholder="Filtrar por nombre o SKU…">
        </div>
      </form>

      <div class="tabs">
        @php
          $tabs = [
            'hacer'      => 'Hacer',
            'incumplido' => 'Incumplido',
            'sin_pagar'  => 'Sin pagar',
            'accion'     => 'Acción necesaria',
            'archivada'  => 'Archivada',
          ];
        @endphp

        @foreach($tabs as $key=>$label)
          <a class="tab {{ $tab===$key ? 'active' : '' }}"
             href="{{ request()->fullUrlWithQuery(['tab'=>$key]) }}">
            {{ $label }}
          </a>
        @endforeach
      </div>

      <div class="tablewrap">
        <table>
          <thead>
            <tr>
              <th class="check"><input type="checkbox" onclick="document.querySelectorAll('.rowcheck').forEach(c=>c.checked=this.checked)"></th>
              <th>Pedido</th>
              <th>Fecha</th>
              <th>Correo electrónico</th>
              <th style="text-align:right">Total</th>
              <th>Pago</th>
              <th>Procesamiento</th>
            </tr>
          </thead>
          <tbody>
            @forelse($orders as $o)
              @php
                $isPaid = $o->status === 'paid';
                $payBadge = $isPaid ? ['Pagado','b-ok'] : ['Esperando','b-warn'];

                // “Procesamiento” visual: pagado = Procesado, pending = Incumplido/Acción
                $procBadge = $isPaid ? ['Procesado','b-ok'] : ['Acción necesaria','b-warn'];
                if ($o->status === 'failed') $procBadge = ['Incumplido','b-bad'];
                if ($o->status === 'archived') $procBadge = ['Archivada','b-warn'];
              @endphp
              <tr>
                <td class="check"><input class="rowcheck" type="checkbox"></td>
                <td>
                  <a class="orderlink" href="{{ route('admin.orders.show', $o) }}">#{{ $o->id }}</a>
                </td>
                <td class="muted">{{ optional($o->created_at)->format('d M, H:i') }}</td>
                <td class="muted">{{ $o->customer_email ?? '—' }}</td>
                <td style="text-align:right; font-weight:900;">
                  MX${{ number_format((float)$o->total, 2, '.', ',') }}
                </td>
                <td><span class="badge {{ $payBadge[1] }}">{{ $payBadge[0] }}</span></td>
                <td><span class="badge {{ $procBadge[1] }}">{{ $procBadge[0] }}</span></td>
              </tr>
            @empty
              <tr><td colspan="7" class="muted" style="padding:18px">Sin pedidos.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="footer">
        {{ $orders->links() }}
      </div>
    </div>
  </div>
</div>
@endsection
