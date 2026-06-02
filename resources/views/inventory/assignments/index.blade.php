@extends('layouts.app')
@section('title','Asignaciones')

@section('content')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&display=swap" rel="stylesheet">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<style>
  :root {
    --bg: #f4f5f7;
    --card: #ffffff;
    --input-bg: #f9fafb;
    --ink-dark: #0f172a;
    --ink: #334155;
    --muted: #64748b;
    --muted-light: #94a3b8;
    --line: #e2e8f0;
    --blue: #007aff;
    --blue-soft: #eff6ff;
    --success: #15803d;
    --success-soft: #f0fdf4;
    --danger: #ef4444;
    --danger-soft: #fef2f2;
    --warning: #c2410c;
    --warning-soft: #fff7ed;
    --font-family: 'Quicksand', sans-serif;
    --radius-card: 12px;
    --radius-modal: 12px;
    --radius-input: 8px;
    --radius-btn: 8px;
  }

  body {
    background: var(--bg);
    font-family: var(--font-family);
    color: var(--ink);
    font-weight: 500;
    -webkit-font-smoothing: antialiased;
  }

  .page { width: 100%; padding: 40px 24px; max-width: 1200px; margin: 0 auto; }
  
  /* ESTILOS DEL BOTÓN DE REGRESAR */
  .btn-back {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: var(--muted);
    font-size: 14px;
    font-weight: 700;
    text-decoration: none;
    margin-bottom: 24px;
    transition: all 0.2s ease;
  }
  .btn-back:hover {
    color: var(--ink-dark);
    transform: translateX(-4px); /* Animación minimalista hacia la izquierda */
  }
  .btn-back i { font-size: 18px; line-height: 1; }

  .head { display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; margin-bottom: 32px; }
  .title { margin: 0; font-size: 26px; font-weight: 700; color: var(--ink-dark); letter-spacing: -0.02em; }
  .sub { margin-top: 4px; color: var(--muted); font-size: 14px; font-weight: 600; }

  .btn-primary {
    background: var(--blue); color: #fff; border: none; border-radius: var(--radius-btn);
    height: 42px; padding: 0 20px; font-size: 14px; font-weight: 700;
    display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s ease;
  }
  .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0, 122, 255, 0.25); color: #fff;}
  .btn-primary:active { transform: scale(0.98); }

  .btn-ghost {
    background: transparent; color: var(--muted); border: none; border-radius: var(--radius-btn);
    height: 42px; padding: 0 16px; font-size: 14px; font-weight: 700; transition: all 0.2s ease;
  }
  .btn-ghost:hover { background: var(--input-bg); color: var(--ink-dark); }

  .search-wrap { margin-bottom: 24px; max-width: 360px; }
  .search-box { position: relative; }
  .search-box i { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--muted); font-size: 15px; }
  .search-box input {
    height: 44px; padding-left: 42px; background: var(--card); border-radius: var(--radius-input);
    border: 1px solid var(--line); font-family: var(--font-family); font-weight: 600; font-size: 14px;
    width: 100%; transition: 0.2s; box-shadow: inset 0 1px 2px rgba(0,0,0,0.01);
  }
  .search-box input:focus { border-color: var(--blue); outline: none; box-shadow: 0 0 0 3px var(--blue-soft); }

  .table-card { background: var(--card); border: 1px solid var(--line); border-radius: var(--radius-card); box-shadow: 0 2px 8px rgba(0,0,0,0.02); overflow: hidden; }
  .table-corporate { margin-bottom: 0; width: 100%; }
  .table-corporate thead th { background: var(--card); color: var(--muted); font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; padding: 16px 24px; border-bottom: 1px solid var(--line); border-top: none; }
  .table-corporate tbody td { padding: 16px 24px; vertical-align: middle; border-bottom: 1px solid var(--line); font-size: 14px; font-weight: 600; }
  .table-corporate tbody tr:last-child td { border-bottom: none; }
  .table-corporate tbody tr:hover { background-color: var(--input-bg); }

  .asset-name, .user-name { font-weight: 700; color: var(--ink-dark); }
  .text-xs { font-size: 12px; color: var(--muted); margin-top: 2px; font-weight: 600; }

  .row-thumb { width:42px; height:42px; border-radius:8px; object-fit:cover; border:1px solid var(--line); background:var(--input-bg); flex:0 0 auto; }
  .row-thumb-empty { display:inline-flex; align-items:center; justify-content:center; color:var(--muted-light); font-size:18px; }

  .status-badge { display: inline-flex; align-items: center; gap: 5px; padding: 6px 12px; border-radius: 999px; font-size: 12px; font-weight: 700; }
  .badge-active { background: var(--success-soft); color: var(--success); }
  .badge-return { background: var(--input-bg); color: var(--muted); }
  .badge-pending { background: var(--warning-soft); color: var(--warning); }
  .badge-danger { background: var(--danger-soft); color: var(--danger); }

  .action-btn { width: 34px; height: 34px; border-radius: 8px; border: none; background: transparent; color: var(--muted); display: inline-flex; align-items: center; justify-content: center; transition: 0.2s; font-size: 15px; text-decoration: none; cursor: pointer; }
  .action-btn:hover { background: var(--input-bg); color: var(--ink-dark); transform: translateY(-1px); }

  .modal-backdrop.show { opacity: 0.45; backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px); background-color: #0f172a; }
  .modal.fade .modal-dialog { transform: scale(0.95) translateY(15px); transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.4s ease; }
  .modal.show .modal-dialog { transform: scale(1) translateY(0); }
  .modal-corp .modal-content { border: 1px solid rgba(255,255,255,0.1); border-radius: var(--radius-modal); box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); background: var(--card); }
  .modal-corp .modal-header { border-bottom: 1px solid var(--line); padding: 20px 24px; background: var(--card); border-radius: var(--radius-modal) var(--radius-modal) 0 0; }
  .modal-corp .modal-title-text { font-size: 18px; font-weight: 700; color: var(--ink-dark); margin: 0; }
  .modal-corp .btn-close { background-size: 12px; opacity: 0.4; transition: 0.2s; }
  .modal-corp .btn-close:hover { opacity: 1; transform: rotate(90deg); }
  .modal-corp .modal-body { padding: 24px; overflow-y: auto !important; }
  .modal-corp .modal-body::-webkit-scrollbar { width: 6px; }
  .modal-corp .modal-body::-webkit-scrollbar-track { background: transparent; }
  .modal-corp .modal-body::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
  .modal-corp .modal-body::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
  .modal-corp .modal-footer { border-top: 1px solid var(--line); padding: 16px 24px; background: var(--input-bg); border-radius: 0 0 var(--radius-modal) var(--radius-modal); }

  .form-label { font-weight: 700; color: var(--ink-dark); font-size: 13px; margin-bottom: 6px; display: flex; justify-content: space-between; align-items: center; }
  .form-control {
    border-radius: var(--radius-input); border: 1px solid var(--line); background-color: var(--input-bg);
    padding: 10px 14px; font-size: 14px; font-weight: 600; color: var(--ink-dark); font-family: var(--font-family);
    height: 42px; transition: all 0.2s ease; box-shadow: inset 0 1px 2px rgba(0,0,0,0.01);
  }
  .form-control:focus { border-color: var(--blue); background-color: var(--card); box-shadow: 0 0 0 3px var(--blue-soft); outline: none; }
  .form-control::placeholder { color: var(--muted-light); font-weight: 500; }
  textarea.form-control { height: auto; min-height: 80px; resize: vertical; }

  .custom-select-wrapper { position: relative; user-select: none; width: 100%; }
  .custom-select-trigger {
    display: flex; justify-content: space-between; align-items: center; gap: 10px; border-radius: var(--radius-input);
    border: 1px solid var(--line); background-color: var(--input-bg); padding: 8px 14px; font-size: 14px;
    font-weight: 600; color: var(--ink-dark); cursor: pointer; min-height: 42px; transition: all 0.2s ease;
  }
  .custom-select-trigger:hover { background-color: var(--card); border-color: #cbd5e1; }
  .custom-select-wrapper.open .custom-select-trigger { border-color: var(--blue); background-color: var(--card); box-shadow: 0 0 0 3px var(--blue-soft); }
  .custom-select-trigger i.chev { transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1); color: var(--muted-light); font-size: 12px; flex:0 0 auto; }
  .custom-select-wrapper.open .custom-select-trigger i.chev { transform: rotate(180deg); color: var(--blue); }
  .trigger-label { display:flex; align-items:center; gap:10px; min-width:0; flex:1; }

  .custom-options-container {
    position: absolute; top: calc(100% + 6px); left: 0; right: 0; background: var(--card); border-radius: 12px;
    box-shadow: 0 12px 30px rgba(0,0,0,0.12); border: 1px solid var(--line); z-index: 9999; overflow: hidden;
    transform-origin: top; transform: scaleY(0.9) translateY(-5px); opacity: 0; visibility: hidden; transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
  }
  .custom-select-wrapper.open .custom-options-container { transform: scaleY(1) translateY(0); opacity: 1; visibility: visible; }
  .custom-options-list { max-height: 280px; overflow-y: auto; padding: 6px; }
  .custom-options-list::-webkit-scrollbar { width: 6px; }
  .custom-options-list::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
  .custom-options-list::-webkit-scrollbar-track { background: transparent; }

  .custom-option { display:flex; align-items:center; gap:12px; padding:8px 12px; font-size:14px; font-weight:600; color:var(--ink); cursor:pointer; border-radius:8px; transition:background 0.2s, color 0.2s; margin-bottom:2px; }
  .custom-option:last-child { margin-bottom: 0; }
  .custom-option:hover { background: var(--input-bg); color: var(--ink-dark); }
  .custom-option.selected { background: var(--blue-soft); }

  .opt-thumb { width:40px; height:40px; border-radius:8px; object-fit:cover; border:1px solid var(--line); background:var(--input-bg); flex:0 0 auto; }
  .opt-thumb-empty { display:inline-flex; align-items:center; justify-content:center; color:var(--muted-light); font-size:18px; }
  .opt-info { min-width:0; }
  .opt-name { font-weight:700; color:var(--ink-dark); font-size:14px; line-height:1.2; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
  .opt-sub { font-size:12px; color:var(--muted); font-weight:600; margin-top:2px; }
  .custom-option.selected .opt-name { color:var(--blue); }

  .asset-preview { display:none; gap:16px; padding:16px; border:1px solid var(--line); border-radius:var(--radius-card); background:var(--card); align-items:flex-start; box-shadow: 0 2px 8px rgba(0,0,0,0.02); }
  .asset-preview img { width:84px; height:84px; border-radius:10px; object-fit:cover; border:1px solid var(--line); background:var(--input-bg); }
  .asset-specs { display:grid; grid-template-columns:auto 1fr; gap:6px 16px; font-size:13px; }
  .asset-specs .k { color:var(--muted); font-weight:600; }
  .asset-specs .v { color:var(--ink-dark); font-weight:700; word-break:break-word; }
  .asset-tag { display:inline-block; margin-top:6px; padding:3px 10px; border-radius:999px; font-size:11px; font-weight:700; }
  .asset-tag.fijo { background:var(--blue-soft); color:var(--blue); }
  .asset-tag.consumible { background:var(--warning-soft); color:var(--warning); }
  .consumible-note { display:none; font-size:14px; font-weight:600; color:var(--muted); padding:12px 16px; border:1px dashed var(--line); border-radius:10px; background:var(--input-bg); }

  .chk-grid { display:grid; grid-template-columns: 1fr; gap:10px; }
  .chk-item { display:flex; align-items:center; gap:10px; padding:10px 14px; border:1px solid var(--line); border-radius:var(--radius-input); background:var(--card); font-size:13px; font-weight:600; color:var(--ink-dark); cursor:pointer; user-select:none; transition:0.2s; }
  .chk-item:hover { border-color: #cbd5e1; background: var(--input-bg); }
  .chk-item input { width:16px; height:16px; accent-color:var(--blue); }

  .cond-grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:12px; }
  .cond-item { border:1px solid var(--line); border-radius:var(--radius-input); background:var(--card); padding:12px 14px; }
  .cond-item .cond-q { font-size:13px; font-weight:700; color:var(--ink-dark); margin-bottom:8px; display:flex; align-items:center; gap:8px; }
  .cond-item .cond-q i { color:var(--blue); font-size:15px; }
  .cond-opts { display:flex; gap:8px; }
  .cond-opts label { flex:1; }
  .cond-opts input { position:absolute; opacity:0; pointer-events:none; }
  .cond-opts span { display:flex; align-items:center; justify-content:center; padding:7px 8px; border:1px solid var(--line); border-radius:8px; font-size:12px; font-weight:700; color:var(--muted); cursor:pointer; transition:0.15s; background:var(--input-bg); }
  .cond-opts input:checked + span { color:#fff; }
  .cond-opts .opt-si input:checked + span { background:var(--success); border-color:var(--success); }
  .cond-opts .opt-no input:checked + span { background:var(--danger); border-color:var(--danger); }
  .cond-opts .opt-na input:checked + span { background:var(--muted); border-color:var(--muted); }

  .img-grid { display:grid; grid-template-columns: repeat(3, 1fr); gap:12px; }
  .img-slot { position:relative; border:1.5px dashed var(--line); border-radius:var(--radius-input); background:var(--input-bg); aspect-ratio:1/1; display:flex; align-items:center; justify-content:center; overflow:hidden; cursor:pointer; transition:0.2s; }
  .img-slot:hover { border-color:var(--blue); background:var(--blue-soft); }
  .img-slot input[type=file] { position:absolute; inset:0; opacity:0; cursor:pointer; }
  .img-slot .img-placeholder { text-align:center; color:var(--muted-light); font-size:12px; font-weight:700; padding:8px; pointer-events:none; }
  .img-slot .img-placeholder i { display:block; font-size:24px; margin-bottom:4px; }
  .img-slot img.preview { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; display:none; }

  .sign-status-box { border-radius:var(--radius-card); padding:32px 24px; text-align:center; border:1px solid var(--line); background:var(--card); transition:background .5s ease, border-color .5s ease; box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
  .sign-status-box.signed { background:var(--success-soft); border-color:#bbf7d0; }
  .spinner-ring { width:56px; height:56px; border-radius:50%; border:4px solid var(--line); border-top-color:var(--blue); margin:0 auto 16px; animation:spin 1s linear infinite; }
  @keyframes spin { to { transform:rotate(360deg); } }
  .sign-check { width:56px; height:56px; border-radius:50%; background:var(--success); color:#fff; display:none; align-items:center; justify-content:center; margin:0 auto 16px; font-size:30px; animation:pop .45s cubic-bezier(.16,1,.3,1); }
  @keyframes pop { 0% { transform:scale(.3); opacity:0; } 100% { transform:scale(1); opacity:1; } }
  .sign-sig-preview { display:none; margin-top:20px; }
  .sign-sig-preview img { max-width:100%; border:1px solid var(--line); border-radius:10px; background:#fff; }
  .copy-link-row { display:flex; gap:8px; }
  .copy-link-row input { flex:1; }
  .qr-wrap { display:inline-block; padding:12px; background:#fff; border:1px solid var(--line); border-radius:10px; }
  #signQr, #signQrImg { display:block; width:200px; height:200px; }
  #signQrImg { object-fit:contain; }

  .text-danger { color: var(--danger) !important; }

  /* ========================================================
     DRAWER LATERAL DE DETALLES
     ======================================================== */
  .drawer-backdrop {
    position: fixed; inset: 0; background: #0f172a; opacity: 0; visibility: hidden;
    backdrop-filter: blur(3px); -webkit-backdrop-filter: blur(3px);
    transition: opacity .35s ease, visibility .35s ease; z-index: 1080;
  }
  .drawer-backdrop.open { opacity: 0.4; visibility: visible; }

  .drawer {
    position: fixed; top: 0; right: 0; height: 100%; width: 440px; max-width: 92vw;
    background: var(--card); box-shadow: -20px 0 50px -20px rgba(0,0,0,0.3);
    transform: translateX(100%); transition: transform .42s cubic-bezier(0.16, 1, 0.3, 1);
    z-index: 1085; display: flex; flex-direction: column;
  }
  .drawer.open { transform: translateX(0); }

  .drawer-header { display:flex; align-items:center; justify-content:space-between; gap:12px; padding: 20px 22px; border-bottom: 1px solid var(--line); }
  .drawer-header h4 { margin:0; font-size:17px; font-weight:700; color:var(--ink-dark); }
  .drawer-close { width:36px; height:36px; border:none; background:var(--input-bg); border-radius:8px; color:var(--muted); font-size:18px; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; transition:0.2s; }
  .drawer-close:hover { background:var(--danger-soft); color:var(--danger); transform: rotate(90deg); }

  .drawer-body { padding: 22px; overflow-y:auto; flex:1; }
  .drawer-body::-webkit-scrollbar { width:6px; }
  .drawer-body::-webkit-scrollbar-thumb { background:#cbd5e1; border-radius:10px; }

  .d-hero { display:flex; gap:14px; align-items:center; margin-bottom:18px; }
  .d-hero img, .d-hero .d-noimg { width:64px; height:64px; border-radius:12px; object-fit:cover; border:1px solid var(--line); background:var(--input-bg); flex:0 0 auto; }
  .d-hero .d-noimg { display:flex; align-items:center; justify-content:center; color:var(--muted-light); font-size:26px; }
  .d-hero .d-name { font-size:17px; font-weight:700; color:var(--ink-dark); line-height:1.2; }
  .d-hero .d-meta { font-size:12px; color:var(--muted); font-weight:600; margin-top:3px; }

  .d-badges { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:20px; }

  .d-section { margin-bottom:20px; }
  .d-section-title { font-size:11px; font-weight:700; color:var(--muted); text-transform:uppercase; letter-spacing:0.06em; margin-bottom:10px; padding-bottom:6px; border-bottom:1px solid var(--line); }
  .d-kv { display:grid; grid-template-columns: 130px 1fr; gap:7px 12px; font-size:13px; }
  .d-kv .dk { color:var(--muted); font-weight:600; }
  .d-kv .dv { color:var(--ink-dark); font-weight:700; word-break:break-word; }

  .d-chk { display:flex; flex-direction:column; gap:6px; }
  .d-chk-row { display:flex; align-items:center; gap:8px; font-size:13px; font-weight:600; color:var(--ink-dark); }
  .d-chk-row i { font-size:15px; }
  .d-chk-row i.yes { color:var(--success); }
  .d-chk-row i.no  { color:var(--muted-light); }

  .d-cond-row { display:flex; align-items:center; justify-content:space-between; gap:8px; padding:8px 0; border-bottom:1px dashed var(--line); font-size:13px; font-weight:600; color:var(--ink-dark); }
  .d-cond-row:last-child { border-bottom:none; }

  .d-sig { border:1px solid var(--line); border-radius:10px; padding:14px; text-align:center; background:var(--input-bg); }
  .d-sig img { max-width:100%; max-height:120px; background:#fff; border-radius:8px; }

  .d-imgs { display:grid; grid-template-columns: repeat(3, 1fr); gap:8px; }
  .d-imgs a { display:block; border-radius:8px; overflow:hidden; border:1px solid var(--line); }
  .d-imgs img { width:100%; height:84px; object-fit:cover; display:block; transition:transform .2s; }
  .d-imgs a:hover img { transform:scale(1.06); }

  .d-empty { font-size:13px; color:var(--muted); font-weight:600; padding:10px 0; }
  .d-actions { display:flex; gap:10px; padding: 16px 22px; border-top:1px solid var(--line); background:var(--input-bg); }
  .d-actions .btn-primary, .d-actions .btn-ghost { flex:1; justify-content:center; text-decoration:none; }
</style>

<div class="page">
  
  <a href="{{ url()->previous() }}" class="btn-back">
    <i class="bi bi-arrow-left"></i><span>Regresar</span>
  </a>

  <div class="head">
    <div>
      <h1 class="title">Gestión de Asignaciones</h1>
      <div class="sub">Tienes {{ $activeCount }} asignaciones activas.</div>
    </div>
    <button class="btn-primary" data-bs-toggle="modal" data-bs-target="#assignModal">
      <i class="bi bi-plus-lg"></i><span>Nueva Asignación</span>
    </button>
  </div>

  <div class="search-wrap">
    <div class="search-box">
      <i class="bi bi-search"></i>
      <input type="text" id="assignmentSearch" placeholder="Buscar activo o usuario...">
    </div>
  </div>

  <div class="table-card">
    <div class="table-responsive">
      <table class="table table-corporate align-middle" id="assignmentsTable">
        <thead>
          <tr>
            <th>Activo</th>
            <th>Usuario</th>
            <th>Cant.</th>
            <th>Fecha</th>
            <th>Estado</th>
            <th>Firma</th>
            <th class="text-end">Acciones</th>
          </tr>
        </thead>
        <tbody>
          @forelse($assignments as $assignment)
            @php $rowItem = $assignment->item; $isConsumible = $rowItem && (($rowItem->type ?? null) === 'consumible'); @endphp
            <tr class="assignment-row" data-row-id="{{ $assignment->id }}" data-search="{{ strtolower(($assignment->item->name ?? '').' '.($assignment->user->name ?? '')) }}">
              <td>
                <div class="d-flex align-items-center gap-2">
                  @if($rowItem && !empty($rowItem->photo))
                    <img src="{{ asset('storage/'.$rowItem->photo) }}" class="row-thumb" alt="{{ $rowItem->name }}">
                  @else
                    <div class="row-thumb row-thumb-empty"><i class="bi {{ $isConsumible ? 'bi-box-seam' : 'bi-pc-display' }}"></i></div>
                  @endif
                  <div>
                    <div class="asset-name">{{ $assignment->item->name ?? 'Activo eliminado' }}</div>
                    @if($rowItem)
                      <div class="text-xs">{{ $isConsumible ? 'Consumible' : 'Activo fijo' }}@if(!empty($rowItem->serial_number)) · {{ $rowItem->serial_number }}@endif</div>
                    @endif
                  </div>
                </div>
              </td>
              <td>
                <div class="user-name">{{ $assignment->user->name ?? 'Usuario' }}</div>
                <div class="text-xs">{{ $assignment->user->email ?? 'Sin correo' }}</div>
              </td>
              <td>{{ $assignment->quantity }}</td>
              <td>{{ optional($assignment->assigned_at)->format('d/m/Y') }}</td>
              <td>
                @if($assignment->status === 'activa')
                  <span class="status-badge badge-active">Activa</span>
                @else
                  <span class="status-badge badge-return">Devuelta</span>
                @endif
              </td>
              <td class="firma-cell">
                @if($assignment->signature_status === 'signed')
                  <span class="status-badge badge-active firma-status"><i class="bi bi-check-circle-fill"></i> Firmado</span>
                @else
                  <span class="status-badge badge-pending firma-status"><i class="bi bi-hourglass-split"></i> Pendiente</span>
                @endif
              </td>
              <td class="text-end">
                <div class="d-inline-flex gap-2">
                  <button type="button" class="action-btn" data-details data-id="{{ $assignment->id }}" title="Ver detalles">
                    <i class="bi bi-eye"></i>
                  </button>
                  @if($assignment->sign_token && $assignment->signature_status !== 'signed')
                    <button type="button" class="action-btn"
                            data-sign
                            data-id="{{ $assignment->id }}"
                            data-url="{{ route('assignments.public.show', $assignment->sign_token) }}"
                            title="Firma / QR">
                      <i class="bi bi-qr-code"></i>
                    </button>
                  @endif
                  <a href="{{ route('assets.assignments.pdf', $assignment->id) }}" target="_blank" class="action-btn" title="Ver PDF">
                    <i class="bi bi-file-earmark-pdf"></i>
                  </a>
                  {{-- Los consumibles NO se regresan --}}
                  @if($assignment->status === 'activa' && !$isConsumible)
                    <button type="button" class="action-btn"
                            data-bs-toggle="modal" data-bs-target="#returnModal"
                            data-assignment-id="{{ $assignment->id }}"
                            data-item-name="{{ $assignment->item->name ?? 'Activo' }}"
                            data-user-id="{{ $assignment->user_id }}">
                      <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center py-5 text-muted fw-bold">No hay asignaciones registradas.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

{{-- DRAWER DE DETALLES --}}
<div class="drawer-backdrop" id="drawerBackdrop"></div>
<aside class="drawer" id="detailsDrawer" aria-hidden="true" aria-label="Detalles de la asignación">
  <div class="drawer-header">
    <h4>Detalles de la asignación</h4>
    <button type="button" class="drawer-close" id="drawerClose" aria-label="Cerrar"><i class="bi bi-x-lg"></i></button>
  </div>
  <div class="drawer-body" id="drawerBody"></div>
  <div class="d-actions" id="drawerActions"></div>
</aside>

{{-- MODAL NUEVA ASIGNACIÓN --}}
<div class="modal fade modal-corp" id="assignModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <form class="modal-content d-flex flex-column h-100" method="POST" action="{{ route('assets.assignments.store') }}" id="assignForm">
      @csrf

      <div class="modal-header">
        <h4 class="modal-title-text">Asignar Activo</h4>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body pb-5">
        <div class="mb-4">
          <label class="form-label">Activo <span class="text-danger">*</span></label>
          <select class="form-select animated-select" name="inventory_item_id" id="assetSelect" required>
            <option value="" disabled selected>Selecciona un activo</option>
            @foreach($items as $item)
              <option value="{{ $item->id }}">{{ $item->name }} (#ACT-000{{ $item->id }})</option>
            @endforeach
          </select>
        </div>

        <div class="asset-preview mb-4" id="assetPreview">
          <div>
            <img id="apPhoto" alt="Foto del equipo" style="display:none;">
            <div id="apNoPhoto" style="width:84px;height:84px;border-radius:10px;border:1px solid var(--line);background:var(--input-bg);display:none;align-items:center;justify-content:center;color:var(--muted-light);"><i class="bi bi-pc-display" style="font-size:26px;"></i></div>
          </div>
          <div style="flex:1; min-width:0;">
            <div style="font-weight:700;color:var(--ink-dark); font-size: 16px;" id="apName">—</div>
            <span class="asset-tag" id="apTag" style="display:none;"></span>
            <div class="asset-specs mt-2" id="apSpecs"></div>
          </div>
        </div>

        <div class="consumible-note mb-4" id="consumibleNote">
          <i class="bi bi-info-circle me-1"></i> Este artículo es <b>consumible</b>: no lleva ficha de activo fijo, checklist ni devolución.
        </div>

        <div class="mb-4">
          <label class="form-label">Usuario <span class="text-danger">*</span></label>
          <select class="form-select animated-select" name="user_id" required>
            <option value="" disabled selected>Selecciona un usuario</option>
            @foreach($users as $user)
              <option value="{{ $user->id }}">{{ $user->name }}</option>
            @endforeach
          </select>
        </div>

        {{-- Quién entrega / quién recibe (firma) --}}
        <div class="row g-3 mb-4">
          <div class="col-md-6">
            <label class="form-label">Quién entrega <span class="text-danger">*</span></label>
            <select class="form-select animated-select" name="delivered_by" required>
              <option value="" disabled>Selecciona quién entrega</option>
              @foreach($users as $user)
                <option value="{{ $user->id }}" {{ auth()->id() == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Quién recibe <span class="text-danger">*</span></label>
            <select class="form-select animated-select" name="received_by" required>
              <option value="" disabled selected>Selecciona quién recibe</option>
              @foreach($users as $user)
                <option value="{{ $user->id }}">{{ $user->name }}</option>
              @endforeach
            </select>
          </div>
        </div>

        <div class="row g-3 mb-4">
          <div class="col-6">
            <label class="form-label">Cantidad <span class="text-danger">*</span></label>
            <input type="number" class="form-control" name="quantity" min="1" value="1" required>
          </div>
          <div class="col-6">
            <label class="form-label">Fecha</label>
            <input type="date" class="form-control" value="{{ date('Y-m-d') }}" readonly>
          </div>
        </div>

        <div class="mb-4" id="checklistSection" style="display:none;">
          <label class="form-label">¿Con qué se entrega el equipo?</label>
          <div class="chk-grid">
            @foreach(['Equipo principal','Cargador / Eliminador','Cable de corriente','Mouse','Teclado','Funda / Maletín','Adaptadores','Caja y manuales'] as $opt)
              <label class="chk-item"><input type="checkbox" data-chk="{{ $opt }}"> {{ $opt }}</label>
            @endforeach
          </div>
          <input type="text" class="form-control mt-3" id="chkOtro" placeholder="Otro (especificar)...">
        </div>

        <div class="mb-2">
          <label class="form-label">Notas adicionales</label>
          <textarea class="form-control" name="notes" placeholder="Agrega notas o detalles del equipo..."></textarea>
        </div>

        <input type="hidden" name="delivery_checklist" id="checklistInput">
      </div>

      <div class="modal-footer d-flex justify-content-end gap-2">
        <button type="button" class="btn-ghost" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn-primary"><i class="bi bi-qr-code"></i> Generar firma</button>
      </div>
    </form>
  </div>
</div>

{{-- MODAL FIRMA EN TIEMPO REAL --}}
<div class="modal fade modal-corp" id="signModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title-text">Firma en tiempo real</h4>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="text-xs mb-4" id="signHelpText" style="font-size: 14px; line-height: 1.5;">El responsable escanea el código QR o abre el enlace en su celular para firmar. Esta pantalla se actualizará automáticamente.</div>

        <div class="text-center mb-4" id="signQrSection">
          <div class="qr-wrap">
            <canvas id="signQr" width="200" height="200"></canvas>
            <img id="signQrImg" alt="Código QR" style="display:none;">
          </div>
        </div>

        <div id="signLinkSection">
          <label class="form-label">Enlace para firmar</label>
          <div class="copy-link-row mb-4">
            <input type="text" class="form-control" id="signLink" readonly>
            <button type="button" class="btn-primary" id="signCopyBtn" style="white-space:nowrap;"><i class="bi bi-clipboard"></i> Copiar</button>
          </div>
        </div>

        <div class="sign-status-box" id="signStatusBox">
          <div class="spinner-ring" id="signSpinner"></div>
          <div class="sign-check" id="signCheck"><i class="bi bi-check-lg"></i></div>
          <div style="font-weight:700;color:var(--ink-dark);font-size:16px;" id="signStateText">Esperando firma…</div>
          <div class="text-xs mt-1" id="signStateSub">Pide al responsable escanear el QR.</div>
          <div class="sign-sig-preview" id="signSigPreview"><img id="signSigImg" alt="Firma"></div>
        </div>
      </div>
      <div class="modal-footer d-flex justify-content-between">
        <a href="#" target="_blank" class="btn-primary" id="signPdfBtn" style="display:none;"><i class="bi bi-file-earmark-pdf"></i> Ver documento</a>
        <button type="button" class="btn-ghost" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

{{-- MODAL DEVOLUCIÓN --}}
<div class="modal fade modal-corp" id="returnModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <form class="modal-content d-flex flex-column h-100" method="POST" id="returnForm" enctype="multipart/form-data">
      @csrf
      <div class="modal-header">
        <div>
          <h4 class="modal-title-text">Procesar Devolución</h4>
          <div class="text-muted small mt-1 fw-bold" id="returnItemLabel">Activo</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body pb-5">

        <div class="row g-3 mb-4">
          <div class="col-md-6">
            <label class="form-label">Quién entrega <span class="text-danger">*</span></label>
            <select class="form-select animated-select" name="delivered_by" id="returnDeliveredBy" required>
              <option value="" disabled selected>Selecciona quién entrega</option>
              @foreach($users as $user)
                <option value="{{ $user->id }}">{{ $user->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Quién recibe <span class="text-danger">*</span></label>
            <select class="form-select animated-select" name="received_by" required>
              <option value="" disabled>Selecciona quién recibe</option>
              @foreach($users as $user)
                <option value="{{ $user->id }}" {{ auth()->id() == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
              @endforeach
            </select>
          </div>
        </div>

        <div class="mb-4">
          <label class="form-label">Condición final <span class="text-danger">*</span></label>
          <select class="form-select animated-select" name="return_condition" required>
            <option value="" disabled selected>Selecciona el estado</option>
            <option value="excelente">Excelente</option>
            <option value="bueno">Bueno</option>
            <option value="regular">Regular</option>
            <option value="malo">Malo</option>
            <option value="dañado">Dañado</option>
          </select>
        </div>

        <div class="mb-4">
          <label class="form-label">Revisión del equipo</label>
          <div class="cond-grid">
            @php
              $conds = [
                ['key'=>'enciende',      'q'=>'¿Enciende / prende?',          'icon'=>'bi-power'],
                ['key'=>'sin_contrasena','q'=>'¿Está sin contraseña?',        'icon'=>'bi-unlock'],
                ['key'=>'rayones',       'q'=>'¿Tiene rayones / golpes?',     'icon'=>'bi-bandaid'],
                ['key'=>'completo',      'q'=>'¿Viene completo (accesorios)?','icon'=>'bi-box-seam'],
                ['key'=>'funcional',     'q'=>'¿Funciona correctamente?',     'icon'=>'bi-check2-circle'],
                ['key'=>'limpio',        'q'=>'¿Está limpio?',                'icon'=>'bi-stars'],
              ];
            @endphp
            @foreach($conds as $c)
              <div class="cond-item">
                <div class="cond-q"><i class="bi {{ $c['icon'] }}"></i> {{ $c['q'] }}</div>
                <div class="cond-opts">
                  <label class="opt-si"><input type="radio" name="return_checklist[{{ $c['key'] }}]" value="si"><span>Sí</span></label>
                  <label class="opt-no"><input type="radio" name="return_checklist[{{ $c['key'] }}]" value="no"><span>No</span></label>
                  <label class="opt-na"><input type="radio" name="return_checklist[{{ $c['key'] }}]" value="na"><span>N/A</span></label>
                </div>
              </div>
            @endforeach
          </div>
        </div>

        <div class="mb-4">
          <label class="form-label">Motivo de devolución <span class="text-danger">*</span></label>
          <input type="text" class="form-control" name="return_reason" required placeholder="Ej. Fin de contrato, cambio de equipo...">
        </div>

        <div class="mb-4">
          <label class="form-label">Detalles de entrega <span class="text-danger">*</span></label>
          <textarea class="form-control" name="return_details" required placeholder="Describe si faltan accesorios, si presenta daños..."></textarea>
        </div>

        <div class="mb-2">
          <label class="form-label">Evidencia fotográfica (hasta 3 imágenes)</label>
          <div class="img-grid">
            @for($i = 0; $i < 3; $i++)
              <label class="img-slot">
                <input type="file" name="return_images[]" accept="image/*" data-img-input>
                <div class="img-placeholder"><i class="bi bi-camera"></i>Foto {{ $i + 1 }}</div>
                <img class="preview" alt="">
              </label>
            @endfor
          </div>
        </div>
      </div>

      <div class="modal-footer d-flex justify-content-end gap-2">
        <button type="button" class="btn-ghost" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn-primary">Confirmar</button>
      </div>
    </form>
  </div>
</div>

{{-- Datos de activos para la ficha --}}
<script>
  window.__ITEMS__ = {
    @foreach($items as $it)
      "{{ $it->id }}": {
        name: @json($it->name),
        type: @json($it->type),
        brand: @json($it->brand),
        model: @json($it->model),
        serial: @json($it->serial_number),
        code: @json($it->internal_code),
        cpu: @json($it->processor),
        ram: @json($it->ram),
        storage: @json($it->storage),
        os: @json($it->operating_system),
        mac: @json($it->mac_address),
        condition: @json($it->condition_label ?? $it->condition),
        photo: @json($it->photo ? asset('storage/'.$it->photo) : null)
      },
    @endforeach
  };

  // Datos completos por asignación (para el drawer de detalles)
  window.__ASSIGNMENTS__ = {
    @foreach($assignments as $a)
      @php
        $aItem = $a->item;
        $aIsConsumible = $aItem && (($aItem->type ?? null) === 'consumible');
        $dChk = is_string($a->delivery_checklist) ? json_decode($a->delivery_checklist, true) : $a->delivery_checklist;
        $dChk = is_array($dChk) ? $dChk : [];
        $rChk = is_string($a->return_checklist) ? json_decode($a->return_checklist, true) : $a->return_checklist;
        $rChk = is_array($rChk) ? $rChk : [];
        $rImgs = is_string($a->return_images) ? json_decode($a->return_images, true) : $a->return_images;
        $rImgs = is_array($rImgs) ? $rImgs : [];
        $rImgUrls = array_values(array_map(fn($p) => asset('storage/'.$p), $rImgs));
      @endphp
      "{{ $a->id }}": {
        id: {{ (int) $a->id }},
        itemName: @json($aItem->name ?? 'Activo eliminado'),
        type: @json($aIsConsumible ? 'consumible' : 'activo_fijo'),
        serial: @json($aItem->serial_number ?? null),
        photo: @json($aItem && $aItem->photo ? asset('storage/'.$aItem->photo) : null),
        brand: @json($aItem->brand ?? null),
        model: @json($aItem->model ?? null),
        code: @json($aItem->internal_code ?? null),
        cpu: @json($aItem->processor ?? null),
        ram: @json($aItem->ram ?? null),
        storage: @json($aItem->storage ?? null),
        os: @json($aItem->operating_system ?? null),
        mac: @json($aItem->mac_address ?? null),
        condition: @json($aItem->condition_label ?? $aItem->condition ?? null),
        userName: @json($a->user->name ?? '—'),
        userEmail: @json($a->user->email ?? '—'),
        deliveredBy: @json(optional($a->deliveredBy)->name),
        receivedBy: @json(optional($a->receivedBy)->name),
        quantity: {{ (int) $a->quantity }},
        folio: @json($a->folio),
        assignedAt: @json(optional($a->assigned_at)->format('d/m/Y H:i')),
        status: @json($a->status),
        signatureStatus: @json($a->signature_status),
        signedAt: @json(optional($a->signed_at)->format('d/m/Y H:i')),
        signerName: @json($a->signer_name),
        signatureImage: @json($a->signature_image),
        notes: @json($a->notes),
        deliveryChecklist: @json($dChk),
        returnedAt: @json(optional($a->returned_at)->format('d/m/Y H:i')),
        returnCondition: @json($a->return_condition),
        returnReason: @json($a->return_reason),
        returnDetails: @json($a->return_details),
        returnChecklist: @json($rChk),
        returnImages: @json($rImgUrls),
        pdfUrl: @json(route('assets.assignments.pdf', $a->id))
      },
    @endforeach
  };
</script>

{{-- Librería de QR --}}
<script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.4/build/qrcode.min.js"></script>

<script>
  // Sincroniza un select nativo (.animated-select) con su dropdown custom
  function syncAnimatedSelect(nativeSelect, value){
    if(!nativeSelect) return;
    nativeSelect.value = String(value);
    const wrapper = nativeSelect.closest('.custom-select-wrapper');
    if(!wrapper) return;
    let txt = '';
    wrapper.querySelectorAll('.custom-option').forEach(o => {
      if(String(o.dataset.value) === String(value)){ o.classList.add('selected'); txt = (o.querySelector('.opt-name')?.textContent) || o.textContent; }
      else o.classList.remove('selected');
    });
    const lbl = wrapper.querySelector('.custom-select-trigger .trigger-label');
    if(lbl && txt) lbl.innerHTML = `<span class="opt-name">${txt}</span>`;
  }

  document.addEventListener('DOMContentLoaded', () => {
    const ITEMS_MAP = window.__ITEMS__ || {};

    function thumbHtml(it){
      if(it.photo) return `<img class="opt-thumb" src="${it.photo}" alt="">`;
      const icon = it.type === 'consumible' ? 'bi-box-seam' : 'bi-pc-display';
      return `<div class="opt-thumb opt-thumb-empty"><i class="bi ${icon}"></i></div>`;
    }

    const selects = document.querySelectorAll('.animated-select');

    selects.forEach(nativeSelect => {
      const isAssetSelect = nativeSelect.id === 'assetSelect';
      nativeSelect.style.display = 'none';

      const wrapper = document.createElement('div');
      wrapper.className = 'custom-select-wrapper';
      nativeSelect.parentNode.insertBefore(wrapper, nativeSelect);
      wrapper.appendChild(nativeSelect);

      const trigger = document.createElement('div');
      trigger.className = 'custom-select-trigger';
      const initialText = nativeSelect.options[nativeSelect.selectedIndex]?.text || 'Seleccionar';
      trigger.innerHTML = `<span class="trigger-label"><span class="opt-name">${initialText}</span></span> <i class="bi bi-chevron-down chev"></i>`;
      wrapper.appendChild(trigger);

      const optionsContainer = document.createElement('div');
      optionsContainer.className = 'custom-options-container';
      const optionsList = document.createElement('div');
      optionsList.className = 'custom-options-list';
      optionsContainer.appendChild(optionsList);
      wrapper.appendChild(optionsContainer);

      Array.from(nativeSelect.options).forEach((option, index) => {
        if (option.disabled) return;

        const customOption = document.createElement('div');
        customOption.className = 'custom-option';
        customOption.dataset.value = option.value;

        const it = isAssetSelect ? ITEMS_MAP[option.value] : null;
        if (it) {
          const sub = it.serial
            ? `N° serie: ${it.serial}`
            : (it.code ? `Código: ${it.code}` : (it.type === 'consumible' ? 'Consumible' : 'Sin número de serie'));
          customOption.innerHTML =
            `${thumbHtml(it)}<div class="opt-info"><div class="opt-name">${option.text}</div><div class="opt-sub">${sub}</div></div>`;
        } else {
          customOption.innerHTML = `<div class="opt-info"><div class="opt-name">${option.text}</div></div>`;
        }

        if (index === nativeSelect.selectedIndex) customOption.classList.add('selected');

        customOption.addEventListener('click', (e) => {
          e.stopPropagation();
          optionsList.querySelectorAll('.custom-option').forEach(el => el.classList.remove('selected'));
          customOption.classList.add('selected');

          let labelHtml = `<span class="opt-name">${option.text}</span>`;
          if (it && it.serial) labelHtml = `<span class="opt-name">${option.text}</span><span class="opt-sub" style="margin-top:0;">· ${it.serial}</span>`;
          trigger.querySelector('.trigger-label').innerHTML = labelHtml;

          nativeSelect.value = customOption.dataset.value;
          nativeSelect.dispatchEvent(new Event('change'));
          wrapper.classList.remove('open');
        });
        optionsList.appendChild(customOption);
      });

      trigger.addEventListener('click', (e) => {
        e.stopPropagation();
        document.querySelectorAll('.custom-select-wrapper').forEach(w => {
          if (w !== wrapper) w.classList.remove('open');
        });
        wrapper.classList.toggle('open');
      });
    });

    document.addEventListener('click', () => {
      document.querySelectorAll('.custom-select-wrapper').forEach(w => w.classList.remove('open'));
    });
  });

  // ===== Búsqueda rápida =====
  const searchInput = document.getElementById('assignmentSearch');
  searchInput?.addEventListener('input', e => {
    const q = e.target.value.toLowerCase().trim();
    document.querySelectorAll('.assignment-row').forEach(row => {
      row.style.display = (!q || row.dataset.search.includes(q)) ? '' : 'none';
    });
  });

  // ===== Ficha del activo / consumible =====
  const ITEMS = window.__ITEMS__ || {};
  const assetSelect    = document.getElementById('assetSelect');
  const assetPreview   = document.getElementById('assetPreview');
  const consumibleNote = document.getElementById('consumibleNote');
  const checklistSection = document.getElementById('checklistSection');

  function selectedIsConsumible(){
    const it = ITEMS[assetSelect?.value];
    return !!(it && it.type === 'consumible');
  }

  function renderAssetPreview(id){
    const it = ITEMS[id];
    if(!it){
      assetPreview.style.display='none';
      consumibleNote.style.display='none';
      if(checklistSection) checklistSection.style.display='none';
      return;
    }

    const photo  = document.getElementById('apPhoto');
    const noPhoto= document.getElementById('apNoPhoto');
    if(it.photo){ photo.src = it.photo; photo.style.display='block'; noPhoto.style.display='none'; }
    else {
      photo.style.display='none';
      noPhoto.innerHTML = '<i class="bi ' + (it.type === 'consumible' ? 'bi-box-seam' : 'bi-pc-display') + '" style="font-size:26px;"></i>';
      noPhoto.style.display='flex';
    }

    document.getElementById('apName').textContent = it.name || '—';

    const tag = document.getElementById('apTag');
    const specsEl = document.getElementById('apSpecs');

    if(it.type === 'consumible'){
      tag.className = 'asset-tag consumible';
      tag.textContent = 'Consumible';
      tag.style.display = 'inline-block';
      specsEl.innerHTML = '';
      consumibleNote.style.display = 'block';
      if(checklistSection) checklistSection.style.display='none';
      assetPreview.style.display = 'flex';
      return;
    }

    tag.className = 'asset-tag fijo';
    tag.textContent = 'Activo fijo';
    tag.style.display = 'inline-block';
    consumibleNote.style.display = 'none';
    if(checklistSection) checklistSection.style.display='block';

    const specs = [
      ['Marca', it.brand], ['Modelo', it.model], ['No. Serie', it.serial], ['Código', it.code],
      ['Procesador', it.cpu], ['RAM', it.ram], ['Almacenam.', it.storage], ['S.O.', it.os],
      ['MAC', it.mac], ['Condición', it.condition]
    ].filter(s => s[1]);

    specsEl.innerHTML = specs.length
      ? specs.map(s => `<div class="k">${s[0]}</div><div class="v">${s[1]}</div>`).join('')
      : '<div class="k">Sin detalles registrados</div><div></div>';

    assetPreview.style.display='flex';
  }
  assetSelect?.addEventListener('change', e => renderAssetPreview(e.target.value));

  // ===== Checklist (solo si NO es consumible) =====
  document.getElementById('assignForm')?.addEventListener('submit', () => {
    const input = document.getElementById('checklistInput');
    if(selectedIsConsumible()){
      input.value = '';
      return;
    }
    const checklist = [];
    document.querySelectorAll('#checklistSection [data-chk]').forEach(c => checklist.push({ label: c.dataset.chk, checked: c.checked }));
    const otroEl = document.getElementById('chkOtro');
    const otro = otroEl ? otroEl.value.trim() : '';
    if(otro) checklist.push({ label: 'Otro: ' + otro, checked: true });
    input.value = JSON.stringify(checklist);
  });

  // ===== QR =====
  function fallbackQrImg(url){
    const canvas = document.getElementById('signQr');
    const img = document.getElementById('signQrImg');
    if(!img) return;
    img.onerror = function(){ console.error('No se pudo cargar el QR de respaldo (¿sin internet?).'); };
    img.src = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&margin=0&data=' + encodeURIComponent(url);
    img.style.display = 'block';
    if(canvas) canvas.style.display = 'none';
  }

  function renderSignQr(url){
    const canvas = document.getElementById('signQr');
    const img = document.getElementById('signQrImg');
    if(img) img.style.display = 'none';
    if(canvas) canvas.style.display = 'block';

    if(window.QRCode && typeof QRCode.toCanvas === 'function' && canvas){
      QRCode.toCanvas(canvas, url, { width: 200, margin: 1, color: { dark: '#0f172a', light: '#ffffff' } }, function(err){
        if(err){ console.error('Error dibujando QR en canvas:', err); fallbackQrImg(url); }
      });
    } else {
      console.warn('Librería QRCode no disponible; usando imagen de respaldo.');
      fallbackQrImg(url);
    }
  }

  // ===== Firma en tiempo real =====
  const POLL_BASE = "{{ url('internal-assets/assignments') }}";
  const signModalEl = document.getElementById('signModal');
  let signModal = null;
  let pollTimer = null;

  function getSignModal(){
    if(!signModalEl) return null;
    if(!signModal && window.bootstrap && bootstrap.Modal){
      signModal = bootstrap.Modal.getOrCreateInstance(signModalEl);
    }
    return signModal;
  }

  function stopPoll(){ if(pollTimer){ clearInterval(pollTimer); pollTimer = null; } }

  function showQrAndLink(show){
    const qrSec   = document.getElementById('signQrSection');
    const linkSec = document.getElementById('signLinkSection');
    const help    = document.getElementById('signHelpText');
    if(qrSec)   qrSec.style.display   = show ? '' : 'none';
    if(linkSec) linkSec.style.display = show ? '' : 'none';
    if(help)    help.style.display    = show ? '' : 'none';
  }

  function setSignWaiting(){
    const box = document.getElementById('signStatusBox');
    box.classList.remove('signed');
    showQrAndLink(true);
    document.getElementById('signSpinner').style.display = 'block';
    document.getElementById('signCheck').style.display = 'none';
    document.getElementById('signStateText').textContent = 'Esperando firma…';
    document.getElementById('signStateSub').textContent = 'Pide al responsable escanear el QR o abrir el link.';
    document.getElementById('signSigPreview').style.display = 'none';
    document.getElementById('signPdfBtn').style.display = 'none';
  }

  function setSignDone(data){
    const box = document.getElementById('signStatusBox');
    box.classList.add('signed');
    showQrAndLink(false); // oculta QR + enlace al firmar
    document.getElementById('signSpinner').style.display = 'none';
    document.getElementById('signCheck').style.display = 'flex';
    document.getElementById('signStateText').textContent = 'Firma completada';
    document.getElementById('signStateSub').textContent = (data.signer ? data.signer + ' • ' : '') + (data.signed_at || '');
    if(data.signature){
      document.getElementById('signSigImg').src = data.signature;
      document.getElementById('signSigPreview').style.display = 'block';
    }
    document.getElementById('signPdfBtn').style.display = 'inline-flex';
  }

  function markRowSigned(id){
    const row = document.querySelector(`.assignment-row[data-row-id="${id}"]`);
    if(!row) return;
    const badge = row.querySelector('.firma-status');
    if(badge){
      badge.className = 'status-badge badge-active firma-status';
      badge.innerHTML = '<i class="bi bi-check-circle-fill"></i> Firmado';
    }
    const qrBtn = row.querySelector('[data-sign]');
    if(qrBtn) qrBtn.remove();
    // Refleja la firma también en los datos del drawer
    if(window.__ASSIGNMENTS__ && window.__ASSIGNMENTS__[id]){
      window.__ASSIGNMENTS__[id].signatureStatus = 'signed';
    }
  }

  function openSignModal(id, url){
    const modal = getSignModal();
    if(!modal){ alert('Aún cargando… intenta de nuevo en un segundo.'); return; }

    document.getElementById('signLink').value = url;
    document.getElementById('signPdfBtn').href = POLL_BASE + '/' + id + '/pdf';

    renderSignQr(url);
    setSignWaiting();
    modal.show();

    signModalEl.addEventListener('shown.bs.modal', () => {
      if(document.getElementById('signQrSection').style.display !== 'none') renderSignQr(url);
    }, { once: true });

    stopPoll();
    const pollUrl = POLL_BASE + '/' + id + '/sign-status';
    const tick = () => fetch(pollUrl, { headers: { 'Accept':'application/json' } })
      .then(r => r.json())
      .then(d => { if(d.signed){ setSignDone(d); markRowSigned(id); stopPoll(); } })
      .catch(() => {});
    tick();
    pollTimer = setInterval(tick, 2500);
  }
  signModalEl?.addEventListener('hidden.bs.modal', stopPoll);

  document.getElementById('signCopyBtn')?.addEventListener('click', () => {
    const inp = document.getElementById('signLink');
    inp.select(); inp.setSelectionRange(0, 99999);
    try { navigator.clipboard.writeText(inp.value); } catch(_) { document.execCommand('copy'); }
    const b = document.getElementById('signCopyBtn');
    const old = b.innerHTML;
    b.innerHTML = '<i class="bi bi-check2"></i> Copiado';
    setTimeout(() => b.innerHTML = old, 1500);
  });

  // Botones "Firma / QR" (delegado, soporta filas que cambian)
  document.addEventListener('click', e => {
    const btn = e.target.closest('[data-sign]');
    if(btn) openSignModal(btn.dataset.id, btn.dataset.url);
  });

  @if(session('open_sign'))
    (function(){
      const id  = "{{ session('open_sign') }}";
      const btn = document.querySelector(`[data-sign][data-id="${id}"]`);
      if(btn){ openSignModal(id, btn.dataset.url); }
    })();
  @endif

  // ===== Modal de Devolución =====
  document.getElementById('returnModal').addEventListener('show.bs.modal', e => {
    const btn = e.relatedTarget;
    if(!btn) return;
    document.getElementById('returnForm').action = `/internal-assets/assignments/${btn.dataset.assignmentId}/return`;
    document.getElementById('returnItemLabel').textContent = btn.dataset.itemName;
    if(btn.dataset.userId){
      syncAnimatedSelect(document.getElementById('returnDeliveredBy'), btn.dataset.userId);
    }
  });

  document.querySelectorAll('#returnForm [data-img-input]').forEach(input => {
    input.addEventListener('change', () => {
      const slot = input.closest('.img-slot');
      const img = slot.querySelector('img.preview');
      const ph  = slot.querySelector('.img-placeholder');
      const file = input.files && input.files[0];
      if(file){ img.src = URL.createObjectURL(file); img.style.display = 'block'; if(ph) ph.style.display = 'none'; }
      else { img.style.display = 'none'; if(ph) ph.style.display = 'block'; }
    });
  });

  /* =======================================================
     DRAWER DE DETALLES
     ======================================================= */
  const ASSIGNMENTS = window.__ASSIGNMENTS__ || {};
  const drawer        = document.getElementById('detailsDrawer');
  const drawerBackdrop= document.getElementById('drawerBackdrop');
  const drawerBody    = document.getElementById('drawerBody');
  const drawerActions = document.getElementById('drawerActions');

  function esc(v){
    if(v === null || v === undefined) return '';
    return String(v).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
  }

  const RETURN_LABELS = {
    enciende: '¿Enciende / prende?',
    sin_contrasena: '¿Está sin contraseña?',
    rayones: '¿Tiene rayones / golpes?',
    completo: '¿Viene completo (accesorios)?',
    funcional: '¿Funciona correctamente?',
    limpio: '¿Está limpio?'
  };
  function condBadge(v){
    if(v === 'si') return '<span class="status-badge badge-active">Sí</span>';
    if(v === 'no') return '<span class="status-badge badge-danger">No</span>';
    return '<span class="status-badge badge-return">N/A</span>';
  }

  function kvRow(k, v){ return v ? `<div class="dk">${esc(k)}</div><div class="dv">${esc(v)}</div>` : ''; }

  function buildDrawer(a){
    // HERO
    const thumb = a.photo
      ? `<img src="${esc(a.photo)}" alt="">`
      : `<div class="d-noimg"><i class="bi ${a.type === 'consumible' ? 'bi-box-seam' : 'bi-pc-display'}"></i></div>`;
    const metaBits = [];
    metaBits.push(a.type === 'consumible' ? 'Consumible' : 'Activo fijo');
    if(a.serial) metaBits.push('N° serie: ' + a.serial);

    let html = `
      <div class="d-hero">
        ${thumb}
        <div style="min-width:0;">
          <div class="d-name">${esc(a.itemName)}</div>
          <div class="d-meta">${esc(metaBits.join(' · '))}</div>
        </div>
      </div>
      <div class="d-badges">
        ${a.status === 'activa'
          ? '<span class="status-badge badge-active"><i class="bi bi-check-circle-fill"></i> Activa</span>'
          : '<span class="status-badge badge-return"><i class="bi bi-arrow-counterclockwise"></i> Devuelta</span>'}
        ${a.signatureStatus === 'signed'
          ? '<span class="status-badge badge-active"><i class="bi bi-pen-fill"></i> Firmado</span>'
          : '<span class="status-badge badge-pending"><i class="bi bi-hourglass-split"></i> Firma pendiente</span>'}
      </div>
    `;

    // DATOS GENERALES
    html += `
      <div class="d-section">
        <div class="d-section-title">Datos de la asignación</div>
        <div class="d-kv">
          ${kvRow('Folio', a.folio)}
          ${kvRow('Usuario', a.userName)}
          ${kvRow('Correo', a.userEmail)}
          ${kvRow('Cantidad', a.quantity)}
          ${kvRow('Fecha entrega', a.assignedAt)}
          ${kvRow('Quién entrega', a.deliveredBy)}
          ${kvRow('Quién recibe', a.receivedBy)}
        </div>
      </div>
    `;

    // FICHA TÉCNICA (activo fijo)
    if(a.type !== 'consumible'){
      const specs = [
        ['Marca', a.brand], ['Modelo', a.model], ['No. serie', a.serial], ['Código', a.code],
        ['Procesador', a.cpu], ['RAM', a.ram], ['Almacenam.', a.storage], ['S.O.', a.os],
        ['MAC', a.mac], ['Condición', a.condition]
      ].filter(s => s[1]);
      if(specs.length){
        html += `
          <div class="d-section">
            <div class="d-section-title">Ficha técnica</div>
            <div class="d-kv">${specs.map(s => kvRow(s[0], s[1])).join('')}</div>
          </div>
        `;
      }
    }

    // CHECKLIST DE ENTREGA
    if(Array.isArray(a.deliveryChecklist) && a.deliveryChecklist.length){
      html += `
        <div class="d-section">
          <div class="d-section-title">Se entregó con</div>
          <div class="d-chk">
            ${a.deliveryChecklist.map(c => `
              <div class="d-chk-row">
                <i class="bi ${c.checked ? 'bi-check-circle-fill yes' : 'bi-circle no'}"></i>
                <span>${esc(c.label)}</span>
              </div>`).join('')}
          </div>
        </div>
      `;
    }

    // NOTAS
    if(a.notes){
      html += `
        <div class="d-section">
          <div class="d-section-title">Notas</div>
          <div class="dv" style="font-weight:600;color:var(--ink);font-size:13px;">${esc(a.notes)}</div>
        </div>
      `;
    }

    // FIRMA
    html += `<div class="d-section"><div class="d-section-title">Firma del responsable</div>`;
    if(a.signatureImage){
      html += `
        <div class="d-sig">
          <img src="${esc(a.signatureImage)}" alt="Firma">
          <div class="d-meta" style="margin-top:8px;">${esc(a.signerName || a.userName)}${a.signedAt ? ' • ' + esc(a.signedAt) : ''}</div>
        </div>`;
    } else {
      html += `<div class="d-empty">Aún sin firma registrada.</div>`;
    }
    html += `</div>`;

    // DEVOLUCIÓN
    if(a.status === 'devuelta'){
      html += `
        <div class="d-section">
          <div class="d-section-title">Devolución</div>
          <div class="d-kv">
            ${kvRow('Fecha', a.returnedAt)}
            ${kvRow('Condición', a.returnCondition ? (a.returnCondition.charAt(0).toUpperCase() + a.returnCondition.slice(1)) : null)}
            ${kvRow('Motivo', a.returnReason)}
            ${kvRow('Detalles', a.returnDetails)}
          </div>
      `;

      const rc = a.returnChecklist || {};
      const rcKeys = Object.keys(RETURN_LABELS).filter(k => rc[k]);
      if(rcKeys.length){
        html += `<div style="margin-top:12px;">`;
        rcKeys.forEach(k => {
          html += `<div class="d-cond-row"><span>${esc(RETURN_LABELS[k])}</span>${condBadge(rc[k])}</div>`;
        });
        html += `</div>`;
      }

      if(Array.isArray(a.returnImages) && a.returnImages.length){
        html += `
          <div style="margin-top:14px;">
            <div class="d-section-title" style="border:none;padding:0;margin-bottom:8px;">Evidencia fotográfica</div>
            <div class="d-imgs">
              ${a.returnImages.map(u => `<a href="${esc(u)}" target="_blank"><img src="${esc(u)}" alt="Evidencia"></a>`).join('')}
            </div>
          </div>`;
      }
      html += `</div>`;
    }

    drawerBody.innerHTML = html;

    // Acciones del pie
    drawerActions.innerHTML = `<a href="${esc(a.pdfUrl)}" target="_blank" class="btn-primary"><i class="bi bi-file-earmark-pdf"></i> Ver PDF</a>`;
  }

  function openDrawer(id){
    const a = ASSIGNMENTS[id];
    if(!a) return;
    buildDrawer(a);
    drawerBackdrop.classList.add('open');
    drawer.classList.add('open');
    drawer.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
  }

  function closeDrawer(){
    drawerBackdrop.classList.remove('open');
    drawer.classList.remove('open');
    drawer.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  }

  document.addEventListener('click', e => {
    const btn = e.target.closest('[data-details]');
    if(btn) openDrawer(btn.dataset.id);
  });
  document.getElementById('drawerClose').addEventListener('click', closeDrawer);
  drawerBackdrop.addEventListener('click', closeDrawer);
  document.addEventListener('keydown', e => { if(e.key === 'Escape' && drawer.classList.contains('open')) closeDrawer(); });
</script>
@endsection