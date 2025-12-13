@extends('layouts.app')

@section('title', $propuesta->codigo.' - Propuesta económica')

@section('content')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
/* ===================== TOKENS ===================== */
:root{
  --ink:#0f172a; --muted:#6b7280; --border:#e5e7eb; --soft:#f8fafc;
  --accent:#2563eb; --accent-soft:#dbeafe;
  --success:#16a34a; --success-soft:#dcfce7;
  --warning:#f59e0b; --warning-soft:#fffbeb;
  --danger:#ef4444; --danger-soft:#fef2f2;
  --radius:18px; --shadow:0 18px 44px rgba(15,23,42,.10);
}

.pe-wrap{
  font-family:Inter,system-ui,sans-serif;
  max-width:1200px; margin:0 auto; padding:16px;
  display:flex; flex-direction:column; gap:16px;
  color:var(--ink);
}

/* ===================== HEADER ===================== */
.pe-top{
  display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap;
}
.pe-title{ display:flex; gap:12px; }
.pe-icon{
  width:44px; height:44px; border-radius:14px;
  display:grid; place-items:center;
  background:linear-gradient(135deg,var(--accent-soft),#fff);
  border:1px solid rgba(191,219,254,1);
}
.pe-h1{ font-size:1.1rem; font-weight:800; margin:0; }
.pe-sub{ margin-top:6px; display:flex; gap:8px; flex-wrap:wrap; }
.pe-badge{
  padding:4px 10px; border-radius:999px;
  border:1px solid var(--border); font-size:.78rem;
  background:#fff; color:var(--muted);
}

.pe-actions{ display:flex; gap:10px; align-items:center; }
.pe-btn{
  border-radius:999px; padding:8px 14px;
  font-weight:700; font-size:.82rem;
  border:1px solid var(--border);
  background:#fff; cursor:pointer;
}
.pe-btn-primary{
  background:linear-gradient(135deg,#eff6ff,var(--accent-soft));
  color:#1d4ed8; border-color:rgba(191,219,254,1);
}
.pe-btn-green{
  background:linear-gradient(135deg,#22c55e,#16a34a);
  color:#fff; border:none;
}
.pe-btn-red{
  background:#fff; color:#b91c1c;
  border:1px solid #fecaca;
}

/* ===================== CARD ===================== */
.pe-card{
  background:#fff; border-radius:var(--radius);
  border:1px solid var(--border); box-shadow:var(--shadow);
}
.pe-card-h{
  padding:12px 14px; border-bottom:1px solid var(--border);
  display:flex; justify-content:space-between; align-items:center;
}
.pe-card-b{ padding:14px; }

/* ===================== TABLE ===================== */
.pe-table{ width:100%; border-collapse:separate; border-spacing:0; font-size:.88rem; }
.pe-table th{
  background:#f8fafc; position:sticky; top:0;
  padding:10px; font-size:.75rem; color:var(--muted);
}
.pe-table td{
  padding:10px; border-bottom:1px solid var(--border); vertical-align:top;
}
.pe-req{ white-space:pre-wrap; line-height:1.35; }
.pe-mini{ font-size:.75rem; color:var(--muted); margin-top:4px; }

.pe-prod{ font-weight:800; font-size:.9rem; }
.pe-prod-meta{ font-size:.75rem; color:var(--muted); }

.pe-amount{ text-align:right; white-space:nowrap; }

/* ===================== CANDIDATES ===================== */
.pe-candidates{
  margin-top:8px; border:1px dashed rgba(191,219,254,1);
  background:#f8fbff; border-radius:14px; padding:10px;
}
.pe-candidate{
  display:flex; justify-content:space-between; gap:10px;
  padding:6px 0; border-bottom:1px dashed #e5e7eb;
}
.pe-candidate:last-child{ border-bottom:none; }

/* ===================== MOBILE ===================== */
@media(max-width:900px){
  .pe-table thead{ display:none; }
  .pe-table, .pe-table tr, .pe-table td{ display:block; width:100%; }
  .pe-table tr{ padding:10px 0; }
  .pe-table td::before{
    content:attr(data-label);
    font-size:.72rem; color:var(--muted); font-weight:800;
    display:block; margin-bottom:4px;
  }
  .pe-amount{ text-align:left; }
}
</style>

<div class="pe-wrap">

{{-- ===================== HEADER ===================== --}}
<div class="pe-top">
  <div class="pe-title">
    <div class="pe-icon">📄</div>
    <div>
      <h1 class="pe-h1">{{ $propuesta->codigo }} · {{ $propuesta->titulo }}</h1>
      <div class="pe-sub">
        <span class="pe-badge">Fecha {{ optional($propuesta->fecha)->format('d/m/Y') }}</span>
        <span class="pe-badge">{{ $propuesta->items->count() }} renglones</span>
      </div>
    </div>
  </div>

  <div class="pe-actions">
    <a href="{{ url()->previous() }}" class="pe-btn">← Volver</a>
  </div>
</div>

{{-- ===================== TABLA ===================== --}}
<div class="pe-card">
  <div class="pe-card-h">
    <strong>Comparativo</strong>
  </div>

  <div class="pe-card-b" style="padding:0">
    <div style="overflow:auto; max-height:70vh">
      <table class="pe-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Solicitado</th>
            <th>Producto</th>
            <th>Cantidad</th>
            <th>Precio</th>
            <th>Subtotal</th>
          </tr>
        </thead>
        <tbody>
        @foreach($propuesta->items as $item)
          @php
            $candidates = $candidatesByItem[$item->id] ?? collect();
            $qty = (int)($item->cantidad_propuesta ?? 0); // 👈 SIN DECIMALES
          @endphp
          <tr>
            <td data-label="#">{{ $loop->iteration }}</td>

            <td data-label="Solicitado">
              <div class="pe-req">{{ $item->descripcion_raw }}</div>
            </td>

            <td data-label="Producto">
              @if($item->product)
                <div class="pe-prod">{{ $item->product->sku }} {{ $item->product->name }}</div>
                <div class="pe-prod-meta">
                  {{ $item->product->brand }} · {{ $item->product->unit }}
                </div>
              @else
                <span class="pe-mini">Sin asignar</span>

                {{-- 🔥 CANDIDATOS --}}
                @if($candidates->isNotEmpty())
                  <div class="pe-candidates">
                    @foreach($candidates as $prod)
                      <div class="pe-candidate">
                        <div>
                          <div class="pe-prod">{{ $prod->sku }} {{ $prod->name }}</div>
                          <div class="pe-prod-meta">{{ $prod->brand }} · {{ $prod->unit }}</div>
                        </div>

                        <form method="POST"
                              action="{{ route('admin.licitacion-propuestas.items.apply', $item) }}">
                          @csrf
                          <input type="hidden" name="product_id" value="{{ $prod->id }}">
                          <button class="pe-btn pe-btn-green pe-btn-mini">Elegir</button>
                        </form>
                      </div>
                    @endforeach
                  </div>
                @endif
              @endif
            </td>

            <td data-label="Cantidad" class="pe-amount">{{ $qty }}</td>

            <td data-label="Precio" class="pe-amount">
              {{ $item->precio_unitario ? number_format($item->precio_unitario,2) : '—' }}
            </td>

            <td data-label="Subtotal" class="pe-amount">
              {{ $item->subtotal ? number_format($item->subtotal,2) : '—' }}
            </td>
          </tr>
        @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>

</div>
@endsection
