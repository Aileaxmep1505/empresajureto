{{-- resources/views/partcontable/activity_all.blade.php --}}
@extends('layouts.app')
@section('title', 'Registro de actividad')

@section('content')
@php
  use Illuminate\Support\Str;

  /**
   * ✅ Por qué te sale "screen_view" / "conf_vault_blocked":
   * Porque tu tabla tiene acciones que NO estaban mapeadas.
   * Aquí las mapeo a español + y oculto la columna "Módulo" como pediste.
   */

  // =========================
  // Labels 100% en español (entendibles)
  // =========================
  $actionLabels = [
    // Part Contable (pc_*)
    'pc_unlock'            => 'Accedió con NIP',
    'pc_unlock_failed'     => 'Intento de acceso fallido',
    'pc_lock'              => 'Cerró el acceso',
    'pc_upload'            => 'Subió un archivo',
    'pc_delete'            => 'Eliminó un archivo',
    'pc_preview'           => 'Abrió vista previa',
    'pc_download'          => 'Descargó un archivo',
    'pc_view_activity'     => 'Abrió la bitácora (empresa)',
    'pc_view_activity_all' => 'Abrió la bitácora (general)',

    // ✅ Global / screen view
    'screen_view'          => 'Abrió una pantalla',
    'http_request'         => 'Navegó en el sistema',

    // Alta Docs
    'alta_unlock'          => 'Accedió a Alta Docs con NIP',
    'alta_unlock_failed'   => 'Falló el NIP en Alta Docs',
    'alta_lock'            => 'Cerró sesión en Alta Docs',
    'alta_view_index'      => 'Abrió el listado de Alta Docs',
    'alta_view_show'       => 'Abrió el detalle de Alta Docs',
    'alta_upload'          => 'Subió un documento en Alta Docs',
    'alta_download'        => 'Descargó un documento en Alta Docs',
    'alta_preview'         => 'Vista previa en Alta Docs',
    'alta_delete'          => 'Eliminó un documento en Alta Docs',

    // Vault confidencial
    'conf_unlock'          => 'Accedió a Vault con NIP',
    'conf_unlock_failed'   => 'Falló el NIP en Vault',
    'conf_lock'            => 'Cerró Vault',
    'conf_upload'          => 'Subió un documento en Vault',
    'conf_delete'          => 'Eliminó un documento en Vault',
    'conf_preview'         => 'Vista previa en Vault',
    'conf_download'        => 'Descargó un documento en Vault',
    'conf_vault_view'      => 'Abrió Vault',
    'conf_vault_search'    => 'Buscó en Vault',

    // ✅ Este te aparece en tu lista
    'conf_vault_blocked'   => 'Intentó entrar a Vault sin NIP (bloqueado)',
  ];

  // =========================
  // Helpers UI
  // =========================
  $statusType = function($r){
    $a = strtolower((string)($r->action ?? ''));
    if (str_contains($a,'failed') || str_contains($a,'error')) return 'error';
    if (str_contains($a,'delete') || str_contains($a,'warning') || str_contains($a,'warn')) return 'warning';
    return 'success';
  };

  $actionType = function($r){
    $a = strtolower((string)($r->action ?? ''));

    if (str_contains($a,'failed') || str_contains($a,'error')) return 'error';

    if ($a === 'pc_unlock' || $a === 'alta_unlock' || $a === 'conf_unlock' || str_contains($a,'login')) return 'login';
    if ($a === 'pc_lock'   || $a === 'alta_lock'   || $a === 'conf_lock'   || str_contains($a,'logout')) return 'logout';

    if (str_contains($a,'upload') || str_contains($a,'create')) return 'create';
    if (str_contains($a,'update') || str_contains($a,'edit'))   return 'update';
    if (str_contains($a,'delete') || str_contains($a,'destroy'))return 'delete';
    if (str_contains($a,'download') || str_contains($a,'export')) return 'export';
    if (str_contains($a,'import')) return 'import';

    if (str_contains($a,'preview') || str_contains($a,'view') || str_contains($a,'show') || str_contains($a,'index') || str_contains($a,'search') || $a==='screen_view') return 'view';
    return 'view';
  };

  $initials = function($name, $email){
    $name = trim((string)$name);
    if ($name !== '') {
      $parts = preg_split('/\s+/', $name);
      $ini = '';
      foreach ($parts as $p) { if ($p !== '') $ini .= mb_substr($p,0,1); }
      $ini = mb_strtoupper(mb_substr($ini,0,2));
      return $ini ?: 'U';
    }
    $email = trim((string)$email);
    return $email !== '' ? mb_strtoupper(mb_substr($email,0,2)) : 'U';
  };

  // ✅ Fecha en español (mes abreviado)
  $formatEs = function($dt){
    if(!$dt) return '—';
    $meses = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
    $d = (int)$dt->format('d');
    $m = (int)$dt->format('m');
    $y = $dt->format('Y');
    $h = $dt->format('H:i');
    $mm = $meses[max(0, min(11, $m-1))] ?? $dt->format('M');
    return sprintf('%02d %s %s, %s', $d, $mm, $y, $h);
  };

  /**
   * ✅ Detalles: ahora incluye
   * - Navegador (user_agent) en español
   * - Empresa a la que entró
   * - Pantalla (screen) y ruta
   * - Para screen_view: muestra pantalla humana (config user_activity.php)
   */
  $uaToBrowserEs = function(?string $ua){
    $ua = (string)$ua;
    if ($ua === '') return '—';

    $isEdge = stripos($ua, 'Edg/') !== false;
    $isChrome = !$isEdge && stripos($ua, 'Chrome/') !== false;
    $isFirefox = stripos($ua, 'Firefox/') !== false;
    $isSafari = stripos($ua, 'Safari/') !== false && stripos($ua, 'Chrome/') === false;

    $browser = 'Navegador';
    if ($isEdge) $browser = 'Microsoft Edge';
    elseif ($isChrome) $browser = 'Google Chrome';
    elseif ($isFirefox) $browser = 'Mozilla Firefox';
    elseif ($isSafari) $browser = 'Safari';

    $os = '';
    if (stripos($ua, 'Windows') !== false) $os = 'Windows';
    elseif (stripos($ua, 'Android') !== false) $os = 'Android';
    elseif (stripos($ua, 'iPhone') !== false || stripos($ua, 'iPad') !== false) $os = 'iOS';
    elseif (stripos($ua, 'Mac OS') !== false || stripos($ua, 'Macintosh') !== false) $os = 'macOS';
    elseif (stripos($ua, 'Linux') !== false) $os = 'Linux';

    return $os ? "{$browser} · {$os}" : $browser;
  };

  $screenHuman = function($r){
    // si tu middleware guarda screen ya “humano”, úsalo
    $s = trim((string)($r->screen ?? ''));
    if ($s !== '') return $s;

    // fallback: si hay route, intenta traducir con config
    $route = trim((string)($r->route ?? ''));
    if ($route !== '') {
      $map = (array) config('user_activity.screens', []);
      if (isset($map[$route])) return (string) $map[$route];
    }
    return '—';
  };

  $metaResumen = function($r) use ($uaToBrowserEs, $screenHuman) {
    $m = $r->meta ?? [];
    if (!is_array($m)) $m = [];

    $a = (string)($r->action ?? '');

    // Empresa
    $empresa = $r->company?->name ?? null;

    // Navegador
    $nav = $uaToBrowserEs($r->user_agent ?? '');

    // Para screen_view => mostrar pantalla
    if ($a === 'screen_view') {
      $parts = [];
      $parts[] = 'Pantalla: '.$screenHuman($r);
      if ($empresa) $parts[] = 'Empresa: '.$empresa;
      $parts[] = 'Navegador: '.$nav;
      return implode(' · ', $parts);
    }

    // Para http_request => mostrar navegación entendible
    if ($a === 'http_request') {
      $path = $r->path ?? null;
      $method = $r->method ?? null;
      $code = $r->status_code ?? null;

      $parts = [];
      if ($method || $path) $parts[] = 'Acción: '.trim(($method ? $method.' ' : '').($path ?? ''));
      if ($empresa) $parts[] = 'Empresa: '.$empresa;
      if ($code) $parts[] = 'Respuesta: '.$code;
      $parts[] = 'Navegador: '.$nav;

      if (isset($m['query']) && is_array($m['query']) && count($m['query'])) {
        $parts[] = 'Filtro: '.Str::limit(json_encode($m['query'], JSON_UNESCAPED_UNICODE), 90);
      }
      return implode(' · ', $parts);
    }

    // Si hay documento / título
    if (!empty($m['title'])) {
      $parts = ["Documento: ".$m['title']];
      if ($empresa) $parts[] = 'Empresa: '.$empresa;
      $parts[] = 'Navegador: '.$nav;
      return implode(' · ', $parts);
    }

    // Razón (failed)
    if (!empty($m['reason'])) {
      $parts = ["Motivo: ".$m['reason']];
      if ($empresa) $parts[] = 'Empresa: '.$empresa;
      $parts[] = 'Navegador: '.$nav;
      return implode(' · ', $parts);
    }

    // default
    $parts = [];
    if ($empresa) $parts[] = 'Empresa: '.$empresa;
    $parts[] = 'Navegador: '.$nav;
    return count($parts) ? implode(' · ', $parts) : '—';
  };

  // =========================
  // Colección visible (sin tocar controller)
  // =========================
  $collection = $rows instanceof \Illuminate\Pagination\AbstractPaginator ? $rows->getCollection() : collect($rows);

  // Stats visibles (de lo que está cargado en la página)
  $totalVisible = $collection->count();
  $uniqueUsersVisible = $collection->pluck('user.email')->filter()->unique()->count();
  $errorsVisible = $collection->filter(fn($r)=>$statusType($r)==='error')->count();
  $warningsVisible = $collection->filter(fn($r)=>$statusType($r)==='warning')->count();
@endphp

{{-- ✅ NO TOCO TU CSS: se queda EXACTO como lo tenías --}}
<style>
  :root{
    --bg:#f8fafc;
    --card:#ffffff;
    --border:#e2e8f0;
    --text:#0f172a;
    --muted:#94a3b8;
    --muted2:#64748b;
    --shadow: 0 1px 2px rgba(2,6,23,.04);
    --shadow2: 0 10px 30px rgba(2,6,23,.08);
    --indigo:#6366f1;
    --emerald:#10b981;
    --amber:#f59e0b;
    --red:#ef4444;
    font-family: Inter, ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, Arial, "Helvetica Neue", sans-serif;
  }

  .act-wrap{ padding:18px 14px 36px; max-width:1240px; margin:0 auto; }
  .act-top{ display:flex; align-items:center; justify-content:space-between; gap:14px; flex-wrap:wrap; margin-bottom: 14px; }
  .act-title{ display:flex; align-items:center; gap:12px; }
  .act-shield{
    width:42px; height:42px; border-radius: 14px;
    background: radial-gradient(120px 60px at 30% 30%, rgba(99,102,241,.22), transparent 55%), rgba(99,102,241,.12);
    border:1px solid rgba(99,102,241,.20);
    display:grid; place-items:center;
    transition: transform .15s ease, box-shadow .15s ease;
  }
  .act-shield:hover{ transform: scale(1.06); box-shadow: var(--shadow2); }
  .act-shield svg{ width:18px; height:18px; color: var(--indigo); }
  .act-h1{ margin:0; font-size:24px; font-weight:900; letter-spacing:-.02em; color:var(--text); }
  .act-sub{ margin:2px 0 0; font-size:12px; color:var(--muted2); }

  .act-btn{
    display:inline-flex; align-items:center; gap:8px;
    padding:10px 12px; border-radius: 12px;
    background: var(--card);
    border:1px solid rgba(148,163,184,.35);
    box-shadow: var(--shadow);
    text-decoration:none;
    color: var(--text);
    font-weight:800;
    transition: transform .15s ease, box-shadow .15s ease;
  }
  .act-btn svg{ width:16px; height:16px; color: var(--muted2); }
  .act-btn:hover{ transform: translateY(-1px); box-shadow: var(--shadow2); }

  /* stats */
  .stats-grid{ display:grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap:12px; margin: 14px 0 14px; }
  @media(min-width: 1024px){ .stats-grid{ grid-template-columns: repeat(4, minmax(0, 1fr)); } }
  .stat{
    background: var(--card);
    border:1px solid rgba(226,232,240,.9);
    border-radius: 18px;
    box-shadow: var(--shadow);
    padding: 16px;
    display:flex; align-items:center; gap:14px;
    transition: transform .15s ease, box-shadow .15s ease;
  }
  .stat:hover{ transform: scale(1.02); box-shadow: var(--shadow2); }
  .stat-ic{
    width:48px; height:48px; border-radius: 14px;
    display:grid; place-items:center;
    border:1px solid rgba(226,232,240,.9);
  }
  .stat-ic svg{ width:18px; height:18px; }
  .stat-v{ font-size:26px; font-weight:900; color:var(--text); line-height:1; }
  .stat-l{ font-size:12px; color:var(--muted2); margin-top:4px; }

  .bg-indigo{ background: rgba(99,102,241,.10); border-color: rgba(99,102,241,.16); }
  .bg-emerald{ background: rgba(16,185,129,.10); border-color: rgba(16,185,129,.16); }
  .bg-red{ background: rgba(239,68,68,.10); border-color: rgba(239,68,68,.16); }
  .bg-amber{ background: rgba(245,158,11,.12); border-color: rgba(245,158,11,.20); }

  .t-indigo{ color: var(--indigo); }
  .t-emerald{ color: var(--emerald); }
  .t-red{ color: var(--red); }
  .t-amber{ color: var(--amber); }

  /* filters */
  .filters{
    background: var(--card);
    border:1px solid rgba(226,232,240,.9);
    border-radius: 18px;
    box-shadow: var(--shadow);
    padding: 14px;
    display:flex; gap:12px; flex-wrap:wrap; align-items:center;
    margin-bottom: 12px;
  }
  .f-search{ flex: 1 1 260px; min-width: 220px; position:relative; }
  .f-search svg{
    position:absolute; left:12px; top:50%; transform: translateY(-50%);
    width:16px; height:16px; color: rgba(100,116,139,.7);
  }
  .f-in, .f-sel{
    padding: 11px 12px;
    border-radius: 14px;
    border: 0;
    outline: none;
    background: rgba(241,245,249,.85);
    color: var(--text);
    font-size: 13px;
  }
  .f-in{ width:100%; padding-left: 36px; }
  .f-in:focus, .f-sel:focus{ background: #fff; box-shadow: 0 0 0 3px rgba(99,102,241,.16); }

  .f-sel{ width: 190px; }
  .f-clear{
    width:42px; height:42px;
    border-radius: 14px;
    border:0;
    background: transparent;
    display:grid; place-items:center;
    color: rgba(100,116,139,.7);
    cursor:pointer;
  }
  .f-clear:hover{ background: rgba(241,245,249,.9); color: var(--text); }

  .muted{ color: rgba(148,163,184,1); font-size: 12px; }

  /* table */
  .tbl-card{
    background: var(--card);
    border:1px solid rgba(226,232,240,.9);
    border-radius: 18px;
    box-shadow: var(--shadow);
    overflow:hidden;
  }
  .tbl-wrap{ overflow-x:auto; }
  table{ width:100%; border-collapse: collapse; font-size: 13px; }
  thead th{
    text-align:left;
    font-size: 11px;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: rgba(148,163,184,1);
    padding: 14px 18px;
    border-bottom: 1px solid rgba(226,232,240,.8);
    background: #fff;
    white-space: nowrap;
  }
  tbody td{
    padding: 14px 18px;
    border-bottom: 1px solid rgba(241,245,249,1);
    vertical-align: middle;
  }
  tbody tr{
    transition: background-color .15s ease, transform .15s ease;
    transform-origin: center;
  }
  tbody tr:nth-child(even){ background: rgba(248,250,252,.55); }
  tbody tr:hover{ background: rgba(241,245,249,.75); transform: scale(1.003); }

  /* user cell */
  .urow{ display:flex; align-items:center; gap:10px; min-width: 220px; }
  .avatar{
    width:34px; height:34px; border-radius: 999px;
    background: linear-gradient(135deg, rgba(99,102,241,1), rgba(168,85,247,1));
    display:grid; place-items:center;
    color:#fff;
    font-weight:900;
    font-size: 12px;
    flex: 0 0 auto;
  }
  .uname{ font-weight:900; color: rgba(51,65,85,1); line-height:1.15; }
  .uemail{ font-size: 12px; color: rgba(148,163,184,1); margin-top:2px; }

  /* action badge */
  .abadge{
    display:inline-flex; align-items:center; gap:7px;
    padding: 6px 10px;
    border-radius: 999px;
    font-weight:900;
    font-size: 12px;
    white-space: nowrap;
  }
  .abadge svg{ width:14px; height:14px; }

  .a-login{ background: rgba(59,130,246,.10); color: rgba(37,99,235,1); }
  .a-logout{ background: rgba(226,232,240,.6); color: rgba(100,116,139,1); }
  .a-create{ background: rgba(16,185,129,.12); color: rgba(5,150,105,1); }
  .a-update{ background: rgba(245,158,11,.14); color: rgba(217,119,6,1); }
  .a-delete{ background: rgba(239,68,68,.10); color: rgba(239,68,68,1); }
  .a-view{ background: rgba(99,102,241,.10); color: rgba(99,102,241,1); }
  .a-export{ background: rgba(168,85,247,.12); color: rgba(147,51,234,1); }
  .a-import{ background: rgba(20,184,166,.12); color: rgba(13,148,136,1); }
  .a-error{ background: rgba(239,68,68,.10); color: rgba(239,68,68,1); }

  /* status badge */
  .sbadge{
    display:inline-flex;
    padding: 4px 10px;
    border-radius: 999px;
    border:1px solid transparent;
    font-weight:900;
    font-size: 12px;
    white-space: nowrap;
    text-transform: lowercase;
  }
  .s-success{ background: rgba(16,185,129,.10); color: rgba(5,150,105,1); border-color: rgba(16,185,129,.18); }
  .s-warning{ background: rgba(245,158,11,.12); color: rgba(217,119,6,1); border-color: rgba(245,158,11,.22); }
  .s-error{ background: rgba(239,68,68,.10); color: rgba(239,68,68,1); border-color: rgba(239,68,68,.20); }

  .col-md{ display:none; }
  .col-lg{ display:none; }
  .col-sm{ display:none; }
  @media(min-width: 640px){ .col-sm{ display: table-cell; } }
  @media(min-width: 768px){ .col-md{ display: table-cell; } }
  @media(min-width: 1024px){ .col-lg{ display: table-cell; } }

  /* ver más */
  .more-wrap{
    padding: 14px;
    display:flex;
    justify-content:center;
    background:#fff;
  }
  .btn-more{
    border:1px solid rgba(226,232,240,.9);
    background: rgba(241,245,249,.75);
    color: rgba(15,23,42,.9);
    padding: 10px 14px;
    border-radius: 14px;
    font-weight: 900;
    cursor:pointer;
    transition: transform .15s ease, box-shadow .15s ease;
  }
  .btn-more:hover{ transform: translateY(-1px); box-shadow: var(--shadow2); background:#fff; }
  .btn-more:disabled{ opacity:.6; cursor:not-allowed; transform:none; box-shadow:none; }
</style>

<div class="act-wrap">

  <div class="act-top">
    <div class="act-title">
      <div class="act-shield" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
        </svg>
      </div>
      <div>
        <h1 class="act-h1">Registro de actividad</h1>
        <div class="act-sub">Actualizado: {{ now()->format('H:i') }}</div>
      </div>
    </div>

    <a class="act-btn" href="{{ request()->fullUrl() }}" title="Actualizar">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 12a9 9 0 0 1-9 9 9 9 0 0 1-9-9 9 9 0 0 1 9-9"></path>
        <polyline points="21 3 21 12 12 12"></polyline>
      </svg>
      Actualizar
    </a>
  </div>

  <div class="stats-grid">
    <div class="stat" data-filter="all">
      <div class="stat-ic bg-indigo">
        <svg class="t-indigo" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
        </svg>
      </div>
      <div>
        <div class="stat-v" id="statTotal">{{ $totalVisible }}</div>
        <div class="stat-l">Total de acciones</div>
      </div>
    </div>

    <div class="stat" data-filter="users">
      <div class="stat-ic bg-emerald">
        <svg class="t-emerald" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
          <circle cx="9" cy="7" r="4"></circle>
          <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
          <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
        </svg>
      </div>
      <div>
        <div class="stat-v" id="statUsers">{{ $uniqueUsersVisible }}</div>
        <div class="stat-l">Usuarios activos</div>
      </div>
    </div>

    <div class="stat" data-filter="errors">
      <div class="stat-ic bg-red">
        <svg class="t-red" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"></circle>
          <line x1="12" y1="8" x2="12" y2="12"></line>
          <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
      </div>
      <div>
        <div class="stat-v" id="statErrors">{{ $errorsVisible }}</div>
        <div class="stat-l">Errores</div>
      </div>
    </div>

    <div class="stat" data-filter="warnings">
      <div class="stat-ic bg-amber">
        <svg class="t-amber" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M3 17l6-6 4 4 8-8"></path>
          <path d="M14 7h7v7"></path>
        </svg>
      </div>
      <div>
        <div class="stat-v" id="statWarnings">{{ $warningsVisible }}</div>
        <div class="stat-l">Advertencias</div>
      </div>
    </div>
  </div>

  <form id="actFilters" class="filters" method="GET" action="{{ route('partcontable.activity.all') }}">
    <div class="f-search">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <circle cx="11" cy="11" r="7"></circle>
        <path d="M21 21l-4.3-4.3"></path>
      </svg>
      <input id="qInput" class="f-in" type="text" name="q" placeholder="Buscar usuario, pantalla, empresa, IP..." value="{{ $q ?? '' }}" autocomplete="off">
    </div>

    <select id="actionSel" class="f-sel" name="action">
      <option value="">Todas las acciones</option>
      @foreach($actions as $a)
        @php $label = $actionLabels[$a] ?? $a; @endphp
        <option value="{{ $a }}" @selected(($action ?? '') === $a)>{{ $label }}</option>
      @endforeach
    </select>

    <select id="companySel" class="f-sel" name="company_id">
      <option value="">Todas las empresas</option>
      @foreach($companies as $c)
        <option value="{{ $c->id }}" @selected((string)($companyId ?? '') === (string)$c->id)>{{ $c->name }}</option>
      @endforeach
    </select>

    <select id="userSel" class="f-sel" name="user_id">
      <option value="">Todos los usuarios</option>
      @foreach($users as $u)
        <option value="{{ $u->id }}" @selected((string)($userId ?? '') === (string)$u->id)>{{ $u->name }}</option>
      @endforeach
    </select>

    <button type="button" class="f-clear" id="clearBtn" title="Limpiar filtros" aria-label="Limpiar filtros">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="18" y1="6" x2="6" y2="18"></line>
        <line x1="6" y1="6" x2="18" y2="18"></line>
      </svg>
    </button>
  </form>

  <div class="muted" style="margin:6px 0 10px;">
    <span id="foundCount">{{ $totalVisible }}</span> registros encontrados (en esta página)
  </div>

  <div class="tbl-card">
    @if($totalVisible === 0)
      <div style="padding: 70px 14px; text-align:center; color: rgba(148,163,184,1);">
        <div style="display:grid; place-items:center; margin-bottom:10px; opacity:.5;">
          <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
          </svg>
        </div>
        No hay registros que mostrar
      </div>
    @else
      <div class="tbl-wrap">
        <table>
          <thead>
            <tr>
              <th>Usuario</th>
              <th>Acción</th>
              {{-- ✅ quitamos Módulo --}}
              <th class="col-lg">Detalles</th>
              <th>Estado</th>
              <th class="col-sm">Fecha</th>
            </tr>
          </thead>
          <tbody id="rowsBody">
            @foreach($collection as $i => $r)
              @php
                $aType = $actionType($r);
                $sType = $statusType($r);

                $userName  = $r->user?->name ?? 'Sistema';
                $userEmail = $r->user?->email ?? null;

                $details = $metaResumen($r);

                $actionKey = (string)($r->action ?? '');
                $actionHuman = $actionLabels[$actionKey] ?? ($actionKey !== '' ? $actionKey : '—');

                $dateLabel = $formatEs($r->created_at);

                // ✅ Para que "buscar" encuentre empresa/pantalla también
                $companyName = $r->company?->name ?? '';
                $screen = $screenHuman($r);
                $path = (string)($r->path ?? '');
                $route = (string)($r->route ?? '');
              @endphp

              <tr class="act-row"
                  data-index="{{ $i }}"
                  data-user="{{ Str::lower($userName.' '.$userEmail) }}"
                  data-action="{{ Str::lower($actionHuman.' '.$actionKey) }}"
                  data-extra="{{ Str::lower($companyName.' '.$screen.' '.$path.' '.$route.' '.$details) }}"
                  data-status="{{ $sType }}"
              >
                <td>
                  <div class="urow">
                    <div class="avatar">{{ $initials($userName, $userEmail) }}</div>
                    <div>
                      <div class="uname">{{ $userName }}</div>
                      <div class="uemail">{{ $userEmail }}</div>
                    </div>
                  </div>
                </td>

                <td>
                  <span class="abadge a-{{ $aType }}">
                    @if($aType === 'login')
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                        <polyline points="10 17 15 12 10 7"></polyline>
                        <line x1="15" y1="12" x2="3" y2="12"></line>
                      </svg>
                    @elseif($aType === 'logout')
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                      </svg>
                    @elseif($aType === 'create')
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                      </svg>
                    @elseif($aType === 'update')
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 20h9"></path>
                        <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"></path>
                      </svg>
                    @elseif($aType === 'delete')
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
                        <path d="M10 11v6"></path>
                        <path d="M14 11v6"></path>
                        <path d="M9 6V4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2"></path>
                      </svg>
                    @elseif($aType === 'export')
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="7 10 12 15 17 10"></polyline>
                        <line x1="12" y1="15" x2="12" y2="3"></line>
                      </svg>
                    @elseif($aType === 'import')
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="17 8 12 3 7 8"></polyline>
                        <line x1="12" y1="3" x2="12" y2="15"></line>
                      </svg>
                    @elseif($aType === 'error')
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                      </svg>
                    @else
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="M8 12h8"></path>
                      </svg>
                    @endif

                    {{ $actionHuman }}
                  </span>
                  <div class="muted" style="margin-top:4px;">{{ $actionKey }}</div>
                </td>

                <td class="col-lg" style="max-width: 720px;">
                  <span class="muted" style="display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                    {{ $details }}
                  </span>
                </td>

                <td>
                  <span class="sbadge s-{{ $sType }}">
                    @if($sType==='success') éxito @elseif($sType==='warning') advertencia @else error @endif
                  </span>
                </td>

                <td class="col-sm">
                  <span class="muted" style="white-space:nowrap;">{{ $dateLabel }}</span>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <div class="more-wrap">
        <button id="btnMore" type="button" class="btn-more">Ver más</button>
      </div>
    @endif
  </div>

</div>

<script>
(function(){
  'use strict';

  const rows = Array.from(document.querySelectorAll('.act-row'));
  const btnMore = document.getElementById('btnMore');

  const STEP = 12;

  function showMore(){
    let shown = 0;
    for (let i = 0; i < rows.length; i++){
      const r = rows[i];
      if (r.dataset.hiddenByFilter === '1') continue;
      if (r.style.display !== 'none') continue;
      r.style.display = '';
      shown++;
      if (shown >= STEP) break;
    }
    const remaining = rows.filter(r => r.dataset.hiddenByFilter !== '1' && r.style.display === 'none').length;
    if (btnMore){
      btnMore.disabled = remaining === 0;
      btnMore.textContent = remaining === 0 ? 'No hay más' : 'Ver más';
    }
  }

  function resetAndShow(){
    rows.forEach(r => r.style.display = 'none');
    showMore();
  }

  if (btnMore) btnMore.addEventListener('click', showMore);

  // ========= Auto-search sin Enter =========
  const qInput = document.getElementById('qInput');
  const actionSel = document.getElementById('actionSel');
  const companySel = document.getElementById('companySel');
  const userSel = document.getElementById('userSel');
  const clearBtn = document.getElementById('clearBtn');
  const foundCount = document.getElementById('foundCount');

  function debounce(fn, wait){
    let t=null;
    return (...args)=>{ clearTimeout(t); t=setTimeout(()=>fn(...args), wait); };
  }

  function matchesRow(r, q, actionVal){
    const hay = ((r.dataset.user || '') + ' ' + (r.dataset.action || '') + ' ' + (r.dataset.extra || '')).toLowerCase();
    if (q && !hay.includes(q)) return false;

    if (actionVal){
      // aquí actionVal es el actionKey (del select)
      const aKey = (r.dataset.action || '').toLowerCase();
      if (!aKey.includes(actionVal.toLowerCase())) return false;
    }
    return true;
  }

  function applyLocal(){
    const q = (qInput?.value || '').trim().toLowerCase();
    const actionVal = (actionSel?.value || '').trim();

    let matched = 0;
    rows.forEach(r=>{
      const ok = matchesRow(r, q, actionVal);
      r.dataset.hiddenByFilter = ok ? '0' : '1';
      r.style.display = 'none';
      if (ok) matched++;
    });

    if (foundCount) foundCount.textContent = String(matched);
    showMore();
  }

  const deb = debounce(applyLocal, 160);

  if (qInput) qInput.addEventListener('input', deb);
  if (actionSel) actionSel.addEventListener('change', applyLocal);

  // company/user: siguen al servidor (porque son IDs reales)
  function autoSubmit(){
    const form = document.getElementById('actFilters');
    if (!form) return;
    form.submit();
  }
  if (companySel) companySel.addEventListener('change', autoSubmit);
  if (userSel) userSel.addEventListener('change', autoSubmit);

  if (clearBtn){
    clearBtn.addEventListener('click', function(){
      if (qInput) qInput.value = '';
      if (actionSel) actionSel.value = '';
      if (companySel) companySel.value = '';
      if (userSel) userSel.value = '';

      // limpia URL
      const url = new URL(window.location.href);
      url.search = '';
      window.history.replaceState({}, '', url.toString());

      // resetea filtros locales
      rows.forEach(r=>{ r.dataset.hiddenByFilter = '0'; });
      if (foundCount) foundCount.textContent = String(rows.length);
      resetAndShow();
    });
  }

  // init
  rows.forEach(r=>{ r.dataset.hiddenByFilter = '0'; r.style.display = 'none'; });
  showMore();
})();
</script>
@endsection