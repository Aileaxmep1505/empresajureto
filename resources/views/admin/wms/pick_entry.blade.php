@extends('layouts.app')

@section('title', 'WMS · Picking')

@section('content')
<div class="wms-wrap">
  <div class="topbar">
    <a href="{{ route('admin.wms.home') }}" class="btn btn-ghost">← WMS</a>
    <div class="topbar-mid">
      <div class="title">Picking</div>
      <div class="sub">Entra a un wave para surtir (modo guiado).</div>
    </div>
  </div>

  <div class="panel">
    <div class="panel-h">
      <div>
        <div class="panel-tt">Abrir wave</div>
        <div class="panel-tx">Pega el ID del wave (ej: 12) para abrir el modo guiado.</div>
      </div>
      <span class="chip chip-soft">1 bodega</span>
    </div>

    <div class="row">
      <div class="field" style="flex:1 1 380px">
        <label class="lbl">PickWave ID</label>
        <div class="inprow">
          <input class="inp" id="waveId" placeholder="Ej: 12">
          <button class="btn btn-primary" type="button" id="btnOpen">Abrir</button>
        </div>
        <div class="hint">Tip: puedes crear waves desde tu panel/operación y aquí solo surtir.</div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('styles')
<style>
  :root{--ink:#0b1220;--muted:#64748b;--line:#e6eaf2;--brand:#2563eb;--radius:18px}
  .wms-wrap{max-width:900px;margin:0 auto;padding:18px 14px 28px}
  .topbar{display:flex;gap:12px;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;margin-bottom:12px}
  .topbar-mid{flex:1 1 360px}
  .title{font-weight:900;color:var(--ink);font-size:1.05rem}
  .sub{color:var(--muted);font-size:.9rem;margin-top:2px}
  .panel{background:#fff;border:1px solid var(--line);border-radius:var(--radius);padding:12px 12px;box-shadow:0 10px 22px rgba(2,6,23,.05)}
  .panel-h{display:flex;justify-content:space-between;gap:10px;align-items:flex-start;margin-bottom:10px}
  .panel-tt{font-weight:900;color:var(--ink)}
  .panel-tx{color:var(--muted);font-size:.88rem;margin-top:2px}
  .row{display:flex;gap:10px;flex-wrap:wrap}
  .field{flex:1 1 320px}
  .lbl{display:block;font-weight:900;color:var(--ink);font-size:.86rem;margin-bottom:6px}
  .inp{width:100%;min-height:44px;border:1px solid var(--line);border-radius:14px;padding:10px 12px;background:#f8fafc}
  .inprow{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
  .hint{color:var(--muted);font-size:.78rem;margin-top:6px}
  .btn{border:0;border-radius:999px;padding:10px 14px;font-weight:900;display:inline-flex;gap:8px;align-items:center;cursor:pointer;white-space:nowrap}
  .btn-primary{background:var(--brand);color:#eff6ff;box-shadow:0 14px 30px rgba(37,99,235,.30)}
  .btn-ghost{background:#fff;border:1px solid var(--line);color:var(--ink)}
  .chip{font-size:.78rem;font-weight:800;padding:6px 10px;border-radius:999px;background:#eff6ff;color:#1e40af;border:1px solid #dbeafe;white-space:nowrap}
</style>
@endpush

@push('scripts')
<script>
  document.getElementById('btnOpen')?.addEventListener('click', ()=>{
    const id = (document.getElementById('waveId').value || '').trim();
    if(!id) return;
    window.location.href = @json(route('admin.wms.pick.show', ['wave' => '__ID__'])).replace('__ID__', encodeURIComponent(id));
  });
</script>
@endpush
