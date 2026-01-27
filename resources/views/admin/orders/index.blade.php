@extends('layouts.web')
@section('title','Pedidos')

@section('content')
@php
  use Illuminate\Support\Facades\Route;

  // Defaults seguros (evita undefined)
  $q    = $q    ?? (string) request('q', '');
  $tab  = $tab  ?? (string) request('tab', 'hacer');
  $pago = $pago ?? (string) request('pago', '');
  $prod = $prod ?? (string) request('producto', '');
@endphp

<style>
/* ===========================
   PEDIDOS · MINIMAL MODERNO
   - Menos negritas
   - Sin emojis
   - “Procesamiento” no se repite (solo tabs + badge)
   - Respeta tipografía global
   =========================== */
#orders{
  --bg:#f6f7fb;
  --card:#ffffff;
  --line:#e7edf5;
  --ink:#0f172a;
  --muted:#64748b;

  --brand:#4f46e5;         /* texto intenso */
  --brand-soft:#eef2ff;    /* pastel */
  --brand-line:#c7d2fe;

  --ok:#15803d;    --okbg:#ecfdf3;   --okline:#bbf7d0;
  --warn:#a16207;  --warnbg:#fffbeb; --warnline:#fde68a;
  --bad:#b91c1c;   --badbg:#fff1f2;  --badline:#fecdd3;

  --radius:18px;
  --shadow:0 18px 50px rgba(2,8,23,.08);
}

#orders{background:var(--bg); padding:26px 0}
#orders .wrap{max-width:1180px; margin:0 auto; padding:0 18px}

/* Top */
#orders .topbar{
  display:flex; align-items:flex-end; justify-content:space-between;
  gap:12px; margin-bottom:12px;
}
#orders h1{
  margin:0;
  font-size:24px;
  letter-spacing:-.02em;
  color:var(--ink);
  font-weight:700; /* ✅ menos pesado */
}
#orders .sub{
  margin-top:6px;
  color:var(--muted);
  font-size:13px;
  font-weight:500;
}
#orders .actions{display:flex; gap:10px; align-items:center; flex-wrap:wrap}

/* Botón pro pastel (sutil) */
#orders .btn{
  display:inline-flex; align-items:center;
  padding:9px 12px;
  border-radius:12px;
  border:1px solid var(--brand-line);
  background:linear-gradient(180deg, var(--brand-soft), #ffffff);
  color:var(--brand);
  font-weight:600; /* ✅ menos negrita */
  text-decoration:none;
  box-shadow:0 8px 18px rgba(2,8,23,.05);
  transition:transform .12s ease, box-shadow .12s ease, background .12s ease;
}
#orders .btn:hover{
  transform:translateY(-1px);
  box-shadow:0 14px 28px rgba(2,8,23,.08);
  background:linear-gradient(180deg, #ffffff, var(--brand-soft));
}
#orders .btn[aria-disabled="true"]{opacity:.55; cursor:not-allowed; pointer-events:none}

/* Panel */
#orders .panel{
  background:var(--card);
  border:1px solid var(--line);
  border-radius:22px;
  box-shadow:var(--shadow);
  overflow:hidden;
}

/* Filtros */
#orders .filters{
  display:grid;
  grid-template-columns: 1.5fr 1fr 1fr; /* ✅ quitamos “Procesamiento” del form */
  gap:12px;
  padding:16px 18px 14px;
}
#orders label{
  display:block;
  font-size:12px;
  color:var(--muted);
  margin:0 0 8px;
  font-weight:600; /* ✅ menos negrita */
  letter-spacing:.01em;
}
#orders .field{position:relative}
#orders input, #orders select{
  width:100%;
  padding:11px 12px;
  border:1px solid var(--line);
  border-radius:14px;
  background:#fff;
  color:var(--ink);
  outline:none;
  transition:border-color .12s ease, box-shadow .12s ease;
}
#orders input::placeholder{color:#94a3b8}
#orders input:focus, #orders select:focus{
  border-color:var(--brand-line);
  box-shadow:0 0 0 4px rgba(79,70,229,.09);
}

/* Tabs (Procesamiento vive aquí) */
#orders .tabs{
  display:flex;
  gap:6px;
  padding:10px 12px;
  border-top:1px solid var(--line);
  border-bottom:1px solid var(--line);
  background:#fff;
  overflow:auto;
  -webkit-overflow-scrolling:touch;
}
#orders .tab{
  display:inline-flex; align-items:center;
  padding:8px 11px;
  border-radius:999px;
  text-decoration:none;
  color:var(--muted);
  font-weight:600;
  border:1px solid transparent;
  white-space:nowrap;
  transition:background .12s ease, color .12s ease, border-color .12s ease;
}
#orders .tab:hover{
  background:#f8fafc;
  border-color:var(--line);
  color:#334155;
}
#orders .tab.active{
  background:var(--brand-soft);
  border-color:var(--brand-line);
  color:var(--brand);
}

/* Tabla */
#orders .tablewrap{overflow:auto}
#orders table{width:100%; border-collapse:collapse; min-width:920px}
#orders th, #orders td{
  padding:13px 16px;
  border-bottom:1px solid #f1f5f9;
  font-size:14px;
}
#orders th{
  background:#fbfcff;
  color:#111827;
  font-size:12.5px;
  font-weight:600; /* ✅ menos negrita */
}
#orders .check{width:42px}
#orders .orderlink{
  color:#0f172a;
  font-weight:600; /* ✅ menos pesado */
  text-decoration:none;
}
#orders .orderlink:hover{color:var(--brand)}
#orders .muted{color:var(--muted)}

/* Badges minimal */
#orders .badge{
  display:inline-flex;
  align-items:center;
  padding:5px 9px;
  border-radius:999px;
  font-size:12px;
  font-weight:600;
  border:1px solid transparent;
}
#orders .b-ok{background:var(--okbg); color:var(--ok); border-color:var(--okline)}
#orders .b-warn{background:var(--warnbg); color:var(--warn); border-color:var(--warnline)}
#orders .b-bad{background:var(--badbg); color:var(--bad); border-color:var(--badline)}

/* Footer */
#orders .footer{padding:14px 16px}

/* Responsive */
@media (max-width:980px){
  #orders .filters{grid-template-columns:1fr 1fr;}
}
@media (max-width:620px){
  #orders .topbar{flex-direction:column; align-items:flex-start}
  #orders .filters{grid-template-columns:1fr}
  #orders table{min-width:760px}
}
</style>

<div id="orders">
  <div class="wrap">
    <div class="topbar">
      <div>
        <h1>Pedidos</h1>
        <div class="sub">Vista de administración para consultar y filtrar pedidos.</div>
      </div>

      <div class="actions">
        @if(Route::has('admin.orders.export'))
          <a class="btn" href="{{ route('admin.orders.export', request()->query()) }}">Exportar CSV</a>
        @else
          <a class="btn" aria-disabled="true">Exportar CSV</a>
        @endif
      </div>
    </div>

    <div class="panel">
      {{-- ✅ quitamos “Procesamiento” del form (ya está en tabs) --}}
      <form method="GET" action="{{ url()->current() }}" class="filters" id="orders-filters">
        <div class="field">
          <label>Buscar</label>
          <input name="q" value="{{ $q }}" placeholder="Número, correo o nombre…">
        </div>

        <div class="field">
          <label>Pago</label>
          <select name="pago">
            <option value="" @selected($pago==='')>Cualquiera</option>
            <option value="pagado" @selected($pago==='pagado')>Pagado</option>
            <option value="esperando" @selected($pago==='esperando')>Esperando</option>
          </select>
        </div>

        <div class="field">
          <label>Producto</label>
          <input name="producto" value="{{ $prod }}" placeholder="Nombre o SKU…">
        </div>

        {{-- ✅ tab via hidden para que el form preserve el estado --}}
        <input type="hidden" name="tab" value="{{ $tab }}">
        <button type="submit" style="display:none"></button>
      </form>

      <div class="tabs" aria-label="Procesamiento">
        @php
          $tabs = [
            'hacer'      => 'Hacer',
            'incumplido' => 'Incumplido',
            'sin_pagar'  => 'Sin pagar',
            'accion'     => 'Acción necesaria',
            'archivada'  => 'Archivada',
            'pagado'     => 'Pagado',
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
              <th class="check">
                <input type="checkbox" aria-label="Seleccionar todos"
                       onclick="document.querySelectorAll('.rowcheck').forEach(c=>c.checked=this.checked)">
              </th>
              <th>Pedido</th>
              <th>Fecha</th>
              <th>Correo</th>
              <th style="text-align:right">Total</th>
              <th>Pago</th>
              <th>Estado</th>
            </tr>
          </thead>

          <tbody>
            @forelse($orders as $o)
              @php
                // Ajusta si tus estados reales son distintos
                $isPaid = $o->status === 'paid';
                $payBadge = $isPaid ? ['Pagado','b-ok'] : ['Esperando','b-warn'];

                // Estado operativo (no repetir "Procesamiento" literal)
                $stateBadge = $isPaid ? ['Procesado','b-ok'] : ['Acción necesaria','b-warn'];
                if ($o->status === 'failed') $stateBadge = ['Incumplido','b-bad'];
                if ($o->status === 'archived') $stateBadge = ['Archivada','b-warn'];
              @endphp

              <tr>
                <td class="check"><input class="rowcheck" type="checkbox" aria-label="Seleccionar pedido #{{ $o->id }}"></td>

                <td>
                  <a class="orderlink" href="{{ route('admin.orders.show', $o) }}">#{{ $o->id }}</a>
                </td>

                <td class="muted">{{ optional($o->created_at)->format('d M, H:i') }}</td>

                <td class="muted">{{ $o->customer_email ?? '—' }}</td>

                <td style="text-align:right; font-weight:600;">
                  MX${{ number_format((float)$o->total, 2, '.', ',') }}
                </td>

                <td><span class="badge {{ $payBadge[1] }}">{{ $payBadge[0] }}</span></td>

                <td><span class="badge {{ $stateBadge[1] }}">{{ $stateBadge[0] }}</span></td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="muted" style="padding:18px">Sin pedidos.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="footer">
        {{ $orders->appends(request()->query())->links() }}
      </div>
    </div>
  </div>
</div>

<script>
  // UX: aplicar filtros al cambiar, sin botón
  (function(){
    const form = document.getElementById('orders-filters');
    if(!form) return;
    form.querySelectorAll('select,input').forEach(el=>{
      el.addEventListener('change', () => form.submit());
    });
  })();
</script>
@endsection
