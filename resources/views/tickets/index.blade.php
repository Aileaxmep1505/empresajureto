{{-- resources/views/tickets/index.blade.php --}}
@extends('layouts.app')
@section('title','Tickets')

@section('content')
<div id="tkt" class="tktWrap">
  <style>
    /* =========================
      Tickets UI — minimal/pro
    ========================= */
    #tkt{
      --bg:#f7f9fc;
      --card:#ffffff;
      --ink:#0b1220;
      --muted:#64748b;
      --muted2:#94a3b8;
      --line:#e7edf5;
      --shadow:0 18px 55px rgba(2,6,23,.08);
      --shadow2:0 10px 26px rgba(2,6,23,.08);
      --radius:18px;
      --radius2:14px;
      --ease:cubic-bezier(.2,.8,.2,1);

      --brand:#2563eb;
      --brand2:#60a5fa;

      --ok:#16a34a;
      --warn:#f59e0b;
      --bad:#ef4444;

      --okBg:#eafaf0;   --okBr:#bfe9cf;
      --waBg:#fff7e6;   --waBr:#fde2b1;
      --bdBg:#ffecec;   --bdBr:#ffc9c9;

      --chipBg:#f8fafc; --chipBr:#e7edf5;

      background:transparent;
      color:var(--ink);
    }

    /* Layout */
    .tktWrap{ max-width:1200px; margin:0 auto; padding:18px 14px 26px; }
    .tktShell{
      background:linear-gradient(180deg, rgba(37,99,235,.06), rgba(37,99,235,0));
      border-radius:24px;
      padding:14px;
    }
    .tktCard{
      background:var(--card);
      border:1px solid var(--line);
      border-radius:24px;
      box-shadow:var(--shadow);
      overflow:hidden;
    }

    /* Header */
    .tktTop{
      position:sticky; top:0;
      background:rgba(255,255,255,.78);
      backdrop-filter: blur(10px);
      border-bottom:1px solid var(--line);
      z-index:5;
    }
    .tktTopInner{
      display:flex; align-items:center; justify-content:space-between; gap:12px;
      padding:14px 16px;
    }
    .tktTitle{
      display:flex; align-items:baseline; gap:10px; flex-wrap:wrap;
    }
    .tktTitle h1{
      margin:0; font-size:1.15rem; font-weight:900; letter-spacing:-.02em;
    }
    .tktSubtitle{
      color:var(--muted); font-size:.9rem;
    }

    /* Controls */
    .tktActions{ display:flex; align-items:center; gap:10px; flex-wrap:wrap; justify-content:flex-end; }
    .tktSearch{
      display:flex; align-items:center; gap:8px;
      border:1px solid var(--line);
      background:#fff;
      border-radius:999px;
      padding:8px 10px;
      min-width:260px;
      box-shadow:0 1px 0 rgba(2,6,23,.02);
    }
    .tktSearch svg{ width:18px; height:18px; color:var(--muted2); flex:0 0 auto; }
    .tktSearch input{
      border:0; outline:0; background:transparent;
      width:100%;
      font-size:.93rem;
      color:var(--ink);
    }
    .tktSearch input::placeholder{ color:var(--muted2); }

    .btnP{
      display:inline-flex; align-items:center; gap:8px;
      border-radius:12px;
      padding:.55rem .85rem;
      border:1px solid var(--line);
      background:linear-gradient(180deg,#fff,#f6f8ff);
      color:var(--ink);
      font-weight:800;
      text-decoration:none;
      transition:transform .15s var(--ease), box-shadow .15s var(--ease), border-color .15s var(--ease);
      box-shadow:0 8px 18px rgba(2,6,23,.06);
      white-space:nowrap;
    }
    .btnP:hover{ transform:translateY(-1px); border-color:#d7e3ff; box-shadow:var(--shadow2); }
    .btnP:active{ transform:translateY(0px); }
    .btnPrimary{
      border-color:rgba(37,99,235,.35);
      background:linear-gradient(180deg, rgba(37,99,235,.12), rgba(37,99,235,.05));
    }
    .btnGhost{
      box-shadow:none;
      background:#fff;
    }

    /* Table head (desktop) */
    .tktHead{
      padding:10px 16px 0 16px;
      color:var(--muted);
      font-size:.82rem;
    }
    .tktGridHead{
      display:grid;
      grid-template-columns: 140px 1fr 140px 120px 120px;
      gap:12px;
      padding:10px 12px;
    }

    /* Rows */
    .tktList{ padding:12px 16px 16px 16px; }
    .tktRow{
      display:grid;
      grid-template-columns: 140px 1fr 140px 120px 120px;
      gap:12px;
      align-items:center;
      background:linear-gradient(180deg,#fff,#fbfcff);
      border:1px solid var(--line);
      border-radius:16px;
      padding:12px 12px;
      margin-bottom:10px;
      transition:transform .15s var(--ease), box-shadow .15s var(--ease), border-color .15s var(--ease);
    }
    .tktRow:hover{
      transform:translateY(-1px);
      border-color:#dfe8f7;
      box-shadow:0 12px 30px rgba(2,6,23,.08);
    }
    .tktMain strong{ font-weight:900; letter-spacing:-.01em; }
    .muted{ color:var(--muted); }
    .mutedSm{ color:var(--muted); font-size:.86rem; }

    .typePill{
      display:inline-flex; align-items:center;
      font-size:.75rem; font-weight:900;
      padding:.18rem .55rem;
      border-radius:999px;
      background:var(--chipBg);
      border:1px solid var(--chipBr);
      color:var(--ink);
      margin-top:6px;
      text-transform:uppercase;
      letter-spacing:.06em;
    }

    /* Chips */
    .chip{
      display:inline-flex; align-items:center; justify-content:center;
      padding:.22rem .6rem;
      border-radius:999px;
      font-size:.78rem;
      font-weight:900;
      border:1px solid var(--chipBr);
      background:var(--chipBg);
      color:var(--ink);
      white-space:nowrap;
    }
    .chip.ok{ background:var(--okBg); border-color:var(--okBr); color:#0f5132; }
    .chip.warn{ background:var(--waBg); border-color:var(--waBr); color:#7a4b00; }
    .chip.bad{ background:var(--bdBg); border-color:var(--bdBr); color:#7f1d1d; }

    /* Mini status dot */
    .dot{
      width:8px;height:8px;border-radius:999px; display:inline-block; margin-right:7px;
      background:#cbd5e1;
      box-shadow:0 0 0 3px rgba(203,213,225,.35);
      vertical-align:middle;
    }
    .dot.ok{ background:var(--ok); box-shadow:0 0 0 3px rgba(22,163,74,.18); }
    .dot.warn{ background:var(--warn); box-shadow:0 0 0 3px rgba(245,158,11,.18); }
    .dot.bad{ background:var(--bad); box-shadow:0 0 0 3px rgba(239,68,68,.18); }

    /* Open button */
    .btnOpen{
      justify-content:center;
      width:100%;
      padding:.5rem .65rem;
      border-radius:12px;
      border:1px solid var(--line);
      background:#fff;
      font-weight:900;
      text-decoration:none;
      color:var(--ink);
      transition:transform .15s var(--ease), border-color .15s var(--ease), box-shadow .15s var(--ease);
    }
    .btnOpen:hover{
      transform:translateY(-1px);
      border-color:#d7e3ff;
      box-shadow:0 10px 22px rgba(2,6,23,.08);
    }

    /* Empty state */
    .tktEmpty{
      border:1px dashed #d9e2f2;
      background:linear-gradient(180deg,#fff,#fbfcff);
      border-radius:18px;
      padding:18px;
      text-align:center;
      color:var(--muted);
    }
    .tktEmpty b{ color:var(--ink); }

    /* Responsive */
    @media (max-width: 992px){
      .tktSearch{ min-width:200px; flex:1 1 240px; }
      .tktGridHead{ display:none; }
      .tktRow{
        grid-template-columns: 1fr;
        padding:14px;
        gap:10px;
      }
      .tktRow > div{ display:flex; align-items:center; justify-content:space-between; gap:12px; }
      .tktRow .tktMain{ display:block; }
      .tktRow .tktMain .mutedSm{ margin-top:2px; }
      .tktRow .tktOpenWrap{ justify-content:flex-end; }
      .tktRow .tktOpenWrap a{ width:auto; }
      .kv{ color:var(--muted2); font-size:.78rem; font-weight:800; letter-spacing:.02em; }
    }

    @media (max-width: 520px){
      .tktTopInner{ flex-direction:column; align-items:stretch; }
      .tktActions{ justify-content:space-between; }
      .tktSearch{ width:100%; min-width:unset; }
      .btnP{ width:100%; justify-content:center; }
      .tktRow > div{ flex-direction:column; align-items:flex-start; }
      .tktRow .tktOpenWrap{ align-items:stretch; width:100%; }
      .tktRow .tktOpenWrap a{ width:100%; }
    }

    /* Reduce motion */
    @media (prefers-reduced-motion: reduce){
      .tktRow, .btnP, .btnOpen{ transition:none; }
      .tktRow:hover, .btnP:hover, .btnOpen:hover{ transform:none; }
    }
  </style>

  <div id="tkt" class="tktShell">
    <div class="tktCard">

      {{-- Top / header --}}
      <div class="tktTop">
        <div class="tktTopInner">
          <div class="tktTitle">
            <h1>Tickets</h1>
            <div class="tktSubtitle">{{ $tickets->total() }} en total</div>
          </div>

          <div class="tktActions">
            {{-- Search (frontend filter) --}}
            <div class="tktSearch" role="search" aria-label="Buscar tickets">
              <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z" stroke="currentColor" stroke-width="2"/>
                <path d="M16.5 16.5 21 21" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              </svg>
              <input id="tktSearchInput" type="search" placeholder="Buscar por folio, cliente, estado, tipo…" autocomplete="off">
            </div>

            <a class="btnP btnPrimary" href="{{ route('tickets.create') }}">
              <span aria-hidden="true">＋</span> Nuevo
            </a>
          </div>
        </div>

        {{-- Table head (desktop) --}}
        <div class="tktHead">
          <div class="tktGridHead">
            <div>Folio</div>
            <div>Cliente / Estado</div>
            <div>Vence</div>
            <div>Prioridad</div>
            <div></div>
          </div>
        </div>
      </div>

      {{-- List --}}
      <div class="tktList" id="tktList">
        @forelse($tickets as $tk)
          @php
            $sig = $tk->sla_signal;
            $signalClass = $sig==='overdue' ? 'bad' : ($sig==='due_soon' ? 'warn' : 'ok');

            // Dot (visual quick cue)
            $dot = $signalClass;

            $folio = $tk->folio ?? '—';
            $type = strtoupper($tk->type ?? '—');
            $client = $tk->client_name ?? ($tk->client->name ?? '—');
            $status = ucfirst($tk->status ?? '—');
            $progress = (int)($tk->progress ?? 0);
            $priority = ucfirst($tk->priority ?? '—');
            $due = $tk->due_at ? $tk->due_at->format('d/m H:i') : '—';
          @endphp

          <div class="tktRow tktItem"
               data-search="{{ strtolower($folio.' '.$type.' '.$client.' '.$status.' '.$priority) }}">
            {{-- Folio / type --}}
            <div class="tktMain">
              <div>
                <strong>{{ $folio }}</strong>
              </div>
              <div class="typePill">{{ $type }}</div>
            </div>

            {{-- Client + status/progress --}}
            <div>
              <div>{{ $client }}</div>
              <div class="mutedSm">
                <span class="dot {{ $dot }}" aria-hidden="true"></span>
                {{ $status }} · {{ $progress }}%
              </div>
            </div>

            {{-- Due --}}
            <div>
              <span class="chip {{ $signalClass }}">{{ $due }}</span>
              <span class="kv d-none d-lg-inline" style="display:none"></span>
            </div>

            {{-- Priority --}}
            <div>
              <span class="chip">{{ $priority }}</span>
            </div>

            {{-- Open --}}
            <div class="tktOpenWrap">
              <a class="btnOpen" href="{{ route('tickets.show',$tk) }}">Abrir</a>
            </div>

            {{-- Mobile labels (only visible on small via CSS structure) --}}
            <div class="d-lg-none" style="display:none"></div>
          </div>
        @empty
          <div class="tktEmpty">
            <b>No hay tickets todavía.</b>
            <div style="margin-top:6px">Crea uno con el botón <b>Nuevo</b>.</div>
          </div>
        @endforelse

        <div class="mt-3" style="padding-top:6px">
          {{ $tickets->links() }}
        </div>
      </div>
    </div>
  </div>

  <script>
    (function(){
      const input = document.getElementById('tktSearchInput');
      const list  = document.getElementById('tktList');
      if(!input || !list) return;

      const items = Array.from(list.querySelectorAll('.tktItem'));

      function norm(s){
        return (s||'')
          .toString()
          .toLowerCase()
          .normalize('NFD').replace(/[\u0300-\u036f]/g,''); // quita acentos
      }

      function filter(){
        const q = norm(input.value).trim();
        if(!q){
          items.forEach(el => el.style.display = '');
          return;
        }
        items.forEach(el => {
          const hay = norm(el.getAttribute('data-search') || '');
          el.style.display = hay.includes(q) ? '' : 'none';
        });
      }

      let t = null;
      input.addEventListener('input', function(){
        clearTimeout(t);
        t = setTimeout(filter, 90);
      });
    })();
  </script>
</div>
@endsection
