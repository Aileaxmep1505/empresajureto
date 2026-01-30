@extends('layouts.app')
@section('title', 'Checklist IA del PDF')

@section('content')
<style>
  :root{
    --ink:#0b1220; --muted:#667085; --line:#e6eaf2; --line2:#eef2f7;
    --card:#fff; --shadow:0 18px 55px rgba(2,6,23,.08);
    --r:18px; --soft:#f8fafc; --black:#0b1220; --black2:#0f172a;
    --ease:cubic-bezier(.2,.8,.2,1);
  }

  .wrap{max-width:1200px;margin:0 auto;padding:18px 14px 26px}
  .top{display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:flex-start;margin-bottom:12px}
  .title{margin:0;font-size:18px;font-weight:950;color:var(--ink);letter-spacing:-.02em}
  .sub{margin:6px 0 0;color:var(--muted);font-size:13px;max-width:90ch}

  .btns{display:flex;gap:10px;flex-wrap:wrap}
  .btn{
    border-radius:999px;border:1px solid var(--line);background:#fff;color:var(--ink);
    font-weight:900;font-size:13px;padding:10px 14px;display:inline-flex;gap:10px;align-items:center;
    text-decoration:none;cursor:pointer;transition:transform .18s var(--ease), box-shadow .18s var(--ease);
    user-select:none;
  }
  .btn:hover{transform:translateY(-1px);box-shadow:0 14px 34px rgba(2,6,23,.10)}
  .btnBlack{background:linear-gradient(180deg,var(--black),var(--black2));color:#fff;border-color:transparent;box-shadow:0 16px 40px rgba(2,6,23,.20)}
  .btnBlack:hover{box-shadow:0 22px 56px rgba(2,6,23,.26)}
  .ico{width:18px;height:18px;display:inline-block;flex:0 0 auto}

  .grid{display:grid;gap:12px;grid-template-columns:minmax(0,1fr) minmax(0,360px);align-items:start}
  @media(max-width:980px){.grid{grid-template-columns:1fr}}

  .card{border:1px solid var(--line);border-radius:var(--r);background:linear-gradient(180deg,#fff,#fcfdff);box-shadow:var(--shadow);overflow:hidden}
  .cardHead{padding:12px 12px;border-bottom:1px solid var(--line);display:flex;justify-content:space-between;gap:10px;align-items:center;flex-wrap:wrap}
  .cardTitle{margin:0;font-weight:950;font-size:14px;color:var(--ink)}
  .cardBody{padding:12px}

  .progress{
    width:100%;height:10px;border-radius:999px;background:#eef2f7;overflow:hidden;border:1px solid #e5e7eb;
  }
  .bar{height:100%;width:0%;background:#0b1220}
  .meta{color:var(--muted);font-size:12px;margin-top:10px;display:flex;gap:10px;flex-wrap:wrap}

  .search{width:100%;border:1px solid var(--line2);border-radius:14px;padding:12px 12px;font-size:14px;outline:none}
  .search:focus{border-color:rgba(37,99,235,.35);box-shadow:0 0 0 4px rgba(37,99,235,.10)}

  .sec{padding:12px;border-top:1px solid var(--line2);background:var(--soft)}
  .sec:first-child{border-top:none}
  .secName{font-weight:950;color:var(--ink);font-size:13px;display:flex;justify-content:space-between;gap:10px;align-items:center}
  .secCount{color:var(--muted);font-size:12px;font-weight:800}

  .item{background:#fff;border:1px solid var(--line2);border-radius:14px;padding:10px;margin-top:10px}
  .row{display:flex;gap:10px;align-items:flex-start}
  .chk{margin-top:2px}
  .code{font-weight:950;color:#1d4ed8;font-size:12px}
  .txt{color:var(--ink);font-size:13px;line-height:1.35}
  .badges{margin-top:6px;display:flex;gap:8px;flex-wrap:wrap}
  .badge{font-size:11px;padding:6px 10px;border-radius:999px;border:1px solid var(--line2);background:var(--soft);color:var(--muted);font-weight:900}
  .badgeReq{background:rgba(37,99,235,.08);border-color:rgba(37,99,235,.20);color:#1d4ed8}
  .badgeOpt{background:rgba(148,163,184,.14);border-color:rgba(148,163,184,.25);color:#334155}
  .evi{margin-top:8px;border-top:1px dashed rgba(148,163,184,.45);padding-top:8px;color:#334155;font-size:12px;white-space:pre-wrap}
  .notes{margin-top:8px}
  .notes textarea{
    width:100%;min-height:78px;border:1px solid var(--line2);border-radius:12px;padding:10px;font-size:13px;outline:none;
  }
  .notes textarea:focus{border-color:rgba(37,99,235,.35);box-shadow:0 0 0 4px rgba(37,99,235,.10)}
  .saveHint{margin-top:8px;color:var(--muted);font-size:12px}
</style>

@php
  $csrf = csrf_token();
  $grouped = collect($items)->groupBy('section');
  $total = collect($items)->count();
  $done  = collect($items)->where('done', true)->count();
  $pct = $total ? round(($done/$total)*100) : 0;
@endphp

<div class="wrap" id="checklistRoot"
  data-csrf="{{ $csrf }}"
  data-update-url="{{ route('admin.licitacion-pdfs.ai.checklist.item.update', ['item' => 0]) }}"
>
  <div class="top">
    <div>
      <h1 class="title">{{ $checklist?->title ?? 'Checklist del PDF' }}</h1>
      <p class="sub">
        {{ $pdf->original_filename }}.
        Solo incluye requisitos presentes en el documento. Marca avances y agrega notas por requisito.
      </p>
    </div>

    <div class="btns">
      <a class="btn" href="{{ route('admin.licitacion-pdfs.ai.show', $pdf) }}">
        <svg class="ico" viewBox="0 0 24 24" fill="none">
          <path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
        Volver al chat
      </a>

      <a class="btn btnBlack" href="{{ route('admin.licitacion-pdfs.preview', ['licitacionPdf' => $pdf->id]) }}" target="_blank" rel="noopener">
        <svg class="ico" viewBox="0 0 24 24" fill="none">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke="currentColor" stroke-width="2"/>
          <path d="M14 2v6h6" stroke="currentColor" stroke-width="2"/>
        </svg>
        Abrir PDF
      </a>
    </div>
  </div>

  <div class="grid">
    <div class="card">
      <div class="cardHead">
        <h3 class="cardTitle">Requisitos</h3>
        <div class="secCount"><span id="pDone">{{ $done }}</span> / <span id="pTotal">{{ $total }}</span> ( <span id="pPct">{{ $pct }}</span>% )</div>
      </div>

      <div class="cardBody">
        <input class="search" id="q" type="text" placeholder="Buscar en checklist (código o texto)…">
      </div>

      <div id="secWrap">
        @if(!$checklist)
          <div class="sec">
            <div class="secName">Sin checklist</div>
            <div style="margin-top:8px;color:var(--muted);font-size:13px">
              Genera el checklist desde el chat con el botón “Generar checklist”.
            </div>
          </div>
        @else
          @foreach($grouped as $secName => $rows)
            @php
              $secTotal = $rows->count();
              $secDone  = $rows->where('done', true)->count();
            @endphp
            <div class="sec" data-sec>
              <div class="secName">
                <span>{{ $secName }}</span>
                <span class="secCount">{{ $secDone }} / {{ $secTotal }}</span>
              </div>

              @foreach($rows as $it)
                <div class="item" data-item
                  data-id="{{ $it->id }}"
                  data-code="{{ e($it->code ?? '') }}"
                  data-text="{{ e($it->text) }}"
                >
                  <div class="row">
                    <input class="chk" type="checkbox" {{ $it->done ? 'checked' : '' }} data-done>
                    <div style="flex:1">
                      @if($it->code)
                        <div class="code">{{ $it->code }}</div>
                      @endif
                      <div class="txt">{{ $it->text }}</div>

                      <div class="badges">
                        <span class="badge {{ $it->required ? 'badgeReq' : 'badgeOpt' }}">
                          {{ $it->required ? 'Requerido' : 'Opcional' }}
                        </span>
                        @if($it->evidence && !empty($it->evidence['page']))
                          <span class="badge">Página {{ (int)$it->evidence['page'] }}</span>
                        @endif
                      </div>

                      @if($it->evidence && !empty($it->evidence['excerpt']))
                        <div class="evi">{{ $it->evidence['excerpt'] }}</div>
                      @endif

                      <div class="notes">
                        <textarea placeholder="Notas (qué falta, quién lo tramita, fecha, observaciones)…" data-notes>{{ $it->notes }}</textarea>
                        <div class="saveHint" data-hint style="display:none">Guardado</div>
                      </div>
                    </div>
                  </div>
                </div>
              @endforeach

            </div>
          @endforeach
        @endif
      </div>
    </div>

    <div class="card">
      <div class="cardHead">
        <h3 class="cardTitle">Progreso</h3>
      </div>
      <div class="cardBody">
        <div class="progress">
          <div class="bar" id="bar" style="width:{{ $pct }}%"></div>
        </div>
        <div class="meta" id="meta">
          <div><b id="mPct">{{ $pct }}</b>% completado</div>
          <div><b id="mDone">{{ $done }}</b> completados</div>
          <div><b id="mLeft">{{ max(0, $total - $done) }}</b> pendientes</div>
        </div>
        <div style="margin-top:10px;color:var(--muted);font-size:12px">
          Consejo: agrega notas por requisito con responsable y fecha. El checklist se mantiene por usuario.
        </div>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  const root = document.getElementById('checklistRoot');
  if(!root) return;

  const csrf = root.getAttribute('data-csrf');
  const updateUrlBase = root.getAttribute('data-update-url'); // .../0

  const q = document.getElementById('q');
  const items = Array.from(document.querySelectorAll('[data-item]'));

  const bar  = document.getElementById('bar');
  const pDone = document.getElementById('pDone');
  const pTotal = document.getElementById('pTotal');
  const pPct = document.getElementById('pPct');

  const mPct = document.getElementById('mPct');
  const mDone = document.getElementById('mDone');
  const mLeft = document.getElementById('mLeft');

  function setProgress(done, total){
    const pct = total ? Math.round((done/total)*100) : 0;
    bar.style.width = pct + '%';
    pDone.textContent = done;
    pTotal.textContent = total;
    pPct.textContent = pct;
    mPct.textContent = pct;
    mDone.textContent = done;
    mLeft.textContent = Math.max(0, total - done);
  }

  function recomputeProgress(){
    const total = items.length;
    const done = items.filter(el => el.querySelector('[data-done]')?.checked).length;
    setProgress(done, total);
  }

  function updateItem(id, payload){
    const url = updateUrlBase.replace(/\/0$/, '/' + id);
    return fetch(url, {
      method: 'PATCH',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrf
      },
      body: JSON.stringify(payload)
    }).then(r => r.json());
  }

  q?.addEventListener('input', () => {
    const t = (q.value || '').trim().toLowerCase();
    items.forEach(el => {
      const code = (el.getAttribute('data-code') || '').toLowerCase();
      const text = (el.getAttribute('data-text') || '').toLowerCase();
      const ok = !t || code.includes(t) || text.includes(t);
      el.style.display = ok ? '' : 'none';
    });
  });

  items.forEach(el => {
    const id = el.getAttribute('data-id');
    const chk = el.querySelector('[data-done]');
    const ta  = el.querySelector('[data-notes]');
    const hint = el.querySelector('[data-hint]');
    let notesTimer = null;

    chk?.addEventListener('change', async () => {
      const done = !!chk.checked;
      const j = await updateItem(id, { done });
      if(j?.ok){
        recomputeProgress();
      }
    });

    ta?.addEventListener('input', () => {
      if(notesTimer) clearTimeout(notesTimer);
      notesTimer = setTimeout(async () => {
        const notes = ta.value || '';
        const j = await updateItem(id, { notes });
        if(j?.ok && hint){
          hint.style.display = 'block';
          setTimeout(()=> hint.style.display = 'none', 900);
        }
      }, 600);
    });
  });

  recomputeProgress();
})();
</script>
@endsection
