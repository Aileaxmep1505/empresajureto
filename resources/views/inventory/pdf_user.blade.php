<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Resguardo de Inventario Interno</title>
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<style>
  /* =========================
     Base (Dompdf safe)
  ========================= */
  *{ box-sizing:border-box; }
  html,body{ margin:0; padding:0; font-family: DejaVu Sans, Arial, sans-serif; color:#0f172a; }
  body{ font-size:12px; line-height:1.35; }

  /* A4/Letter safe width */
  .wrap{ width:100%; max-width:720px; margin:0 auto; padding:22px 24px; }

  /* =========================
     Tokens tipo banco
  ========================= */
  :root{
    --ink:#0f172a;
    --muted:#64748b;
    --line:#e5e7eb;
    --line2:#eef2f7;
    --chip-bg:#f8fafc;
    --chip-br:#e2e8f0;

    --brand:#0ea5e9;
    --brand-soft:#eaf7fb;
    --brand-br:#c9e7f0;

    --ok-bg:#eaf7ef;
    --ok-br:#cdebd7;
    --ok-ink:#166534;

    --warn-bg:#fff8e7;
    --warn-br:#ffe6b5;
    --warn-ink:#9a6700;

    --danger-bg:#fdecec;
    --danger-br:#f2c8c8;
    --danger-ink:#b91c1c;
  }

  .muted{ color:var(--muted); }
  .small{ font-size:11px; }
  .caps{ text-transform:uppercase; letter-spacing:.06em; }

  /* =========================
     Header premium
  ========================= */
  .header{
    display:flex; align-items:flex-start; justify-content:space-between; gap:16px;
    padding-bottom:12px; margin-bottom:14px;
    border-bottom:1px solid var(--line);
  }
  .brand{
    display:flex; align-items:center; gap:10px;
  }
  .logo{
    width:40px; height:40px; border-radius:10px;
    background:var(--brand);
    display:inline-block;
  }
  .brand-title{
    font-weight:900; letter-spacing:.4px; font-size:14px;
  }
  .brand-sub{
    margin-top:2px; font-size:11px; color:var(--muted);
  }
  .right{
    text-align:right;
  }
  .doc-title{
    font-size:16px; font-weight:900; margin:0; color:var(--ink);
  }
  .doc-meta{
    margin-top:4px; color:var(--muted); font-size:11px;
  }
  .tag{
    display:inline-block;
    margin-top:7px;
    padding:4px 10px;
    border-radius:999px;
    background:var(--brand-soft);
    border:1px solid var(--brand-br);
    color:#0e7490;
    font-weight:800;
    font-size:11px;
  }

  /* =========================
     Summary cards
  ========================= */
  .summary{
    border:1px solid var(--line);
    border-radius:12px;
    padding:12px 14px;
    background:#fff;
    margin-bottom:12px;
    page-break-inside:avoid;
  }
  .grid{ display:flex; flex-wrap:wrap; gap:10px; }
  .col{ flex:1 1 210px; }
  .label{
    font-size:10px;
    color:var(--muted);
    text-transform:uppercase;
    letter-spacing:.05em;
    margin-bottom:4px;
  }
  .value{ font-size:13px; font-weight:800; color:var(--ink); }

  .pill{
    display:inline-block;
    padding:4px 10px;
    border-radius:999px;
    border:1px solid var(--chip-br);
    background:var(--chip-bg);
    color:#334155;
    font-weight:700;
    font-size:11px;
  }

  /* =========================
     Table modern (bank style)
  ========================= */
  table{
    width:100%;
    border-collapse:separate;
    border-spacing:0;
    border:1px solid var(--line);
    border-radius:12px;
    overflow:hidden;
    background:#fff;
  }
  thead th{
    text-align:left;
    font-size:10px;
    color:var(--muted);
    text-transform:uppercase;
    letter-spacing:.06em;
    padding:10px 10px;
    background:#fbfdff;
    border-bottom:1px solid var(--line);
    white-space:nowrap;
  }
  tbody td{
    padding:10px 10px;
    border-bottom:1px solid var(--line2);
    vertical-align:top;
  }
  tbody tr:last-child td{ border-bottom:none; }
  .num{ width:32px; color:#64748b; font-weight:700; }
  .qty{ text-align:center; width:68px; font-weight:900; color:#111827; }
  .date{ white-space:nowrap; color:#111827; font-weight:700; }
  .item{
    font-weight:900; color:#0f172a;
  }
  .subline{
    margin-top:3px;
    color:var(--muted);
    font-size:11px;
  }

  /* Signature */
  .sigbox{
    border:1px dashed #cbd5e1;
    border-radius:10px;
    padding:8px 10px;
    min-height:72px;
    display:flex;
    align-items:center;
    justify-content:center;
    page-break-inside:avoid;
    background:#fff;
  }
  .sigbox img{ max-height:52px; max-width:100%; }
  .sig-missing{
    display:inline-block;
    padding:4px 10px;
    border-radius:999px;
    border:1px solid var(--warn-br);
    background:var(--warn-bg);
    color:var(--warn-ink);
    font-weight:800;
    font-size:11px;
  }

  /* Footer note */
  .note{
    margin-top:10px;
    color:var(--muted);
    font-size:11px;
    text-align:center;
  }

  /* Subtle divider line */
  .divider{
    height:1px;
    background:var(--line);
    margin:10px 0 12px;
  }
</style>
</head>
<body>

  <div class="wrap">

    {{-- Header --}}
    <div class="header">
      <div class="brand">
        {{-- Si tienes logo, reemplaza el span por <img src="{{ public_path('images/logo.png') }}"> --}}
        <span class="logo"></span>
        <div>
          <div class="brand-title">MEDIBUY</div>
          <div class="brand-sub">Resguardo / Entrega de inventario interno</div>
        </div>
      </div>

      <div class="right">
        <p class="doc-title">Resguardo de Inventario</p>
        <div class="doc-meta">Generado: <b>{{ now()->format('d/m/Y H:i') }}</b></div>
        <div class="doc-meta">Usuario: <b>{{ $user->name }}</b></div>
        <span class="tag">Documento de entrega</span>
      </div>
    </div>

    {{-- Summary --}}
    <div class="summary">
      @php
        $totalLineas = is_countable($assignments) ? count($assignments) : 0;
        $totalQty = 0;
        if (is_iterable($assignments)) {
          foreach($assignments as $ax){ $totalQty += (int)($ax->quantity ?? 0); }
        }
      @endphp

      <div class="grid">
        <div class="col">
          <div class="label">Titular</div>
          <div class="value">{{ $user->name }}</div>
          <div class="subline">Resguardo asignado a usuario</div>
        </div>
        <div class="col">
          <div class="label">Líneas</div>
          <div class="value">{{ $totalLineas }}</div>
          <div class="subline">Cantidad de artículos listados</div>
        </div>
        <div class="col">
          <div class="label">Total de piezas</div>
          <div class="value">{{ $totalQty }}</div>
          <div class="subline">Suma de cantidades</div>
        </div>
      </div>

      <div class="divider"></div>

      <span class="pill">Este documento confirma la entrega y resguardo del material listado.</span>
    </div>

    {{-- Table --}}
    <table>
      <thead>
        <tr>
          <th style="width:36px;">#</th>
          <th>Artículo</th>
          <th style="width:150px;">Categoría</th>
          <th style="width:80px; text-align:center;">Cantidad</th>
          <th style="width:120px;">Fecha entrega</th>
          <th style="width:160px;">Firma</th>
        </tr>
      </thead>
      <tbody>
        @forelse($assignments as $i => $a)
          @php
            $itemName = $a->item?->name ?? 'Artículo eliminado / no disponible';
            $catName  = $a->item?->category?->name ?? '—';
            $dateStr  = $a->assigned_at ? \Carbon\Carbon::parse($a->assigned_at)->format('d/m/Y H:i') : '—';
          @endphp
          <tr>
            <td class="num">{{ $i+1 }}</td>
            <td>
              <div class="item">{{ $itemName }}</div>
              {{-- Si quieres un segundo renglón opcional (ej. ID o nota), lo dejas aquí --}}
              {{-- <div class="subline">ID: {{ $a->id }}</div> --}}
            </td>
            <td>
              <span class="pill">{{ $catName }}</span>
            </td>
            <td class="qty">{{ (int)$a->quantity }}</td>
            <td class="date">{{ $dateStr }}</td>
            <td>
              <div class="sigbox">
                @if(!empty($a->signature))
                  <img src="{{ $a->signature }}" alt="Firma">
                @else
                  <span class="sig-missing">Sin firma</span>
                @endif
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6" style="text-align:center; padding:14px; color:var(--muted);">
              No hay asignaciones registradas para este usuario.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>

    <div class="note">
      Documento generado automáticamente por el sistema · {{ now()->format('d/m/Y') }}
    </div>

  </div>

</body>
</html>
