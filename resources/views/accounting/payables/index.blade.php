@extends('layouts.app')
@section('title','Pagos')

@section('content')
@include('accounting.partials.ui')

@php
    use Carbon\Carbon;
    use Illuminate\Support\Facades\Route;
    use Illuminate\Support\Str;
    use Illuminate\Support\Collection;

    $today = Carbon::today();

    $routeFirst = function (array $names, $params = [], $fallback = '#') {
        foreach ($names as $name) {
            if (Route::has($name)) {
                return route($name, $params);
            }
        }
        return $fallback;
    };

    $allItems = method_exists($items, 'getCollection')
        ? $items->getCollection()
        : collect($items ?? []);

    $categoryLabels = [
        'impuestos'           => 'Impuestos',
        'cuentas_por_cobrar'  => 'Cuentas por Cobrar',
        'servicios'           => 'Servicios',
        'nomina'              => 'Nómina',
        'seguros'             => 'Seguros',
        'retenciones'         => 'Retenciones',
        'otros'               => 'Otros',
    ];

    $frequencyLabels = [
        'unico'      => 'Único',
        'mensual'    => 'Mensual',
        'bimestral'  => 'Bimestral',
        'trimestral' => 'Trimestral',
        'semestral'  => 'Semestral',
        'anual'      => 'Anual',
    ];

    $statusLabels = [
        'pendiente' => 'Pendiente',
        'atrasado'  => 'Atrasado',
        'vencido'   => 'Atrasado',
        'urgente'   => 'Urgente',
        'pagado'    => 'Pagado',
        'cancelado' => 'Cancelado',
        'parcial'   => 'Parcial',
    ];

    $normalizeText = function ($value, $fallback = '—') {
        $value = trim((string) $value);
        return $value !== '' ? $value : $fallback;
    };

    $effectiveStatus = function ($p) use ($today) {
        $raw = Str::lower((string) ($p->status ?? 'pendiente'));

        if (!in_array($raw, ['pagado', 'cancelado'], true) && !empty($p->due_date)) {
            try {
                if (Carbon::parse($p->due_date)->lt($today)) {
                    return 'atrasado';
                }
            } catch (\Throwable $e) {
                //
            }
        }

        return $raw ?: 'pendiente';
    };

    $documentsCount = function ($p) {
        $documents = data_get($p, 'documents');

        if ($documents instanceof Collection) return $documents->count();
        if (is_array($documents)) return count($documents);

        if (is_numeric(data_get($p, 'documents_count'))) {
            return (int) data_get($p, 'documents_count');
        }

        if (is_numeric(data_get($p, 'attachments_count'))) {
            return (int) data_get($p, 'attachments_count');
        }

        if (!empty(data_get($p, 'document_path')) || !empty(data_get($p, 'file_path'))) {
            return 1;
        }

        return 0;
    };
@endphp

<style>
  .pay-wrap{
    max-width:1120px;
    margin:0 auto;
    padding:8px 0 28px;
  }

  .pay-head{
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:16px;
    margin-bottom:18px;
  }

  .pay-title{
    margin:0;
    font-size:1.82rem;
    line-height:1.06;
    font-weight:900;
    letter-spacing:-.03em;
    color:#0f172a;
  }

  .pay-sub{
    margin-top:6px;
    color:#64748b;
    font-size:.95rem;
    font-weight:500;
  }

  .pay-summary{
    display:flex;
    flex-wrap:wrap;
    gap:8px;
    align-items:center;
  }

  .pay-summary-chip{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:9px 13px;
    border-radius:999px;
    background:#fff;
    border:1px solid #e6ebf2;
    color:#475569;
    font-size:.84rem;
    font-weight:800;
    box-shadow:0 8px 22px rgba(15,23,42,.04);
  }

  .pay-summary-chip b{
    color:#0f172a;
    font-weight:900;
  }

  .pay-alert{
    margin-bottom:14px;
    background:#ecfdf5;
    color:#047857;
    border:1px solid #a7f3d0;
    border-radius:16px;
    padding:12px 14px;
    font-weight:800;
  }

  .pay-filters-card{
    background:linear-gradient(180deg,#ffffff 0%, #fcfdff 100%);
    border:1px solid #e7edf5;
    border-radius:16px;
    padding:13px 13px 12px;
    margin-bottom:16px;
    box-shadow:
      0 8px 22px rgba(15,23,42,.035),
      inset 0 1px 0 rgba(255,255,255,.75);
  }

  .pay-filters-top{
    display:flex;
    align-items:center;
    gap:8px;
    margin-bottom:12px;
    color:#0f172a;
    font-weight:700;
    font-size:.95rem;
    letter-spacing:-.01em;
  }

  .pay-filters-top .icon{
    width:16px;
    height:16px;
    color:#6b7280;
    flex-shrink:0;
  }

  .pay-filters{
    display:grid;
    grid-template-columns:minmax(0,1fr) repeat(3, minmax(170px, .78fr));
    gap:10px;
    align-items:center;
  }

  .pay-search{
    position:relative;
    width:100%;
  }

  .pay-search input{
    width:100%;
    height:38px;
    border:1px solid #d9e1ea;
    background:#fff;
    border-radius:11px;
    outline:none;
    color:#0f172a;
    font-size:13.5px;
    font-weight:500;
    padding:0 13px 0 38px;
    transition:
      border-color .22s ease,
      box-shadow .22s ease,
      transform .22s ease,
      background-color .22s ease;
    box-shadow:
      0 1px 2px rgba(15,23,42,.02),
      0 4px 10px rgba(15,23,42,.02);
  }

  .pay-search input::placeholder{
    color:#667085;
    font-weight:500;
  }

  .pay-search .icon{
    position:absolute;
    left:12px;
    top:50%;
    transform:translateY(-50%);
    width:15px;
    height:15px;
    color:#7b8794;
    pointer-events:none;
  }

  .pay-search input:hover{
    border-color:#cdd7e3;
    background:#fff;
  }

  .pay-search input:focus{
    border-color:#3b82f6;
    box-shadow:
      0 0 0 4px rgba(59,130,246,.09),
      0 8px 18px rgba(59,130,246,.08);
    background:#fff;
  }

  .pay-dd{
    position:relative;
    width:100%;
  }

  .pay-dd-trigger{
    width:100%;
    height:38px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
    border:1px solid #d9e1ea;
    background:#fff;
    border-radius:11px;
    padding:0 11px 0 12px;
    outline:none;
    cursor:pointer;
    color:#0f172a;
    font-size:13.5px;
    font-weight:500;
    transition:
      border-color .22s ease,
      box-shadow .22s ease,
      transform .22s ease,
      background-color .22s ease;
    box-shadow:
      0 1px 2px rgba(15,23,42,.02),
      0 4px 10px rgba(15,23,42,.02);
  }

  .pay-dd-trigger:hover{
    border-color:#cdd7e3;
    transform:translateY(-1px);
  }

  .pay-dd.open .pay-dd-trigger{
    border-color:#3b82f6;
    box-shadow:
      0 0 0 4px rgba(59,130,246,.09),
      0 10px 22px rgba(59,130,246,.08);
    transform:translateY(-1px);
  }

  .pay-dd-label{
    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
    text-align:left;
    font-weight:500;
  }

  .pay-dd-caret{
    width:14px;
    height:14px;
    color:#7b8794;
    flex-shrink:0;
    transition:
      transform .26s cubic-bezier(.22,1,.36,1),
      color .22s ease;
  }

  .pay-dd.open .pay-dd-caret{
    transform:rotate(180deg);
    color:#2563eb;
  }

  .pay-dd-menu{
    position:absolute;
    top:calc(100% + 8px);
    left:0;
    right:0;
    background:#fff;
    border:1px solid #e4ebf3;
    border-radius:13px;
    padding:6px;
    box-shadow:
      0 20px 42px rgba(15,23,42,.12),
      0 8px 18px rgba(15,23,42,.05);
    opacity:0;
    visibility:hidden;
    transform:translateY(8px) scale(.985);
    transform-origin:top center;
    transition:
      opacity .20s ease,
      transform .24s cubic-bezier(.22,1,.36,1),
      visibility .20s ease;
    z-index:60;
    max-height:250px;
    overflow:auto;
    scrollbar-width:none;
    -ms-overflow-style:none;
  }

  .pay-dd-menu::-webkit-scrollbar{
    width:0;
    height:0;
    display:none;
  }

  .pay-dd.open .pay-dd-menu{
    opacity:1;
    visibility:visible;
    transform:translateY(0) scale(1);
  }

  .pay-dd-option{
    width:100%;
    display:flex;
    align-items:center;
    justify-content:space-between;
    text-align:left;
    border:0;
    background:transparent;
    border-radius:10px;
    min-height:33px;
    padding:0 11px;
    color:#0f172a;
    font-size:13px;
    font-weight:500;
    cursor:pointer;
    transition:
      background-color .18s ease,
      color .18s ease,
      transform .18s ease;
  }

  .pay-dd-option:hover{
    background:#f8fbff;
    color:#2563eb;
    transform:translateX(2px);
  }

  .pay-dd-option.active{
    background:#eff6ff;
    color:#1d4ed8;
    font-weight:600;
  }

  .pay-dd-option.active::after{
    content:"";
    width:7px;
    height:7px;
    border-radius:999px;
    background:#2563eb;
    flex-shrink:0;
  }

  .pay-results{
    margin:9px 2px 0;
    color:#64748b;
    font-size:.78rem;
    font-weight:500;
  }

  .pay-list{
    display:flex;
    flex-direction:column;
    gap:10px;
  }

  .pay-card{
    display:block;
    text-decoration:none;
    color:inherit;
    background:#fff;
    border:1px solid #e8edf5;
    border-radius:16px;
    padding:16px 16px 14px;
    box-shadow:0 8px 22px rgba(15,23,42,.035);
    transition:transform .24s ease, box-shadow .24s ease, border-color .24s ease, opacity .24s ease;
    position:relative;
    overflow:hidden;
  }

  .pay-card:hover{
    transform:translateY(-2px);
    box-shadow:0 18px 30px rgba(15,23,42,.08);
    border-color:#d9e1ec;
  }

  .pay-card.is-overdue{
    border-left:5px solid #f43f5e;
  }

  .pay-card.is-urgent{
    border-left:5px solid #ef4444;
  }

  .pay-card.is-paid{
    border-left:5px solid #34d399;
    opacity:.72;
  }

  .pay-card.is-paid:hover{
    opacity:1;
  }

  .pay-card-head{
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:14px;
  }

  .pay-card-main{
    flex:1;
    min-width:0;
  }

  .pay-badges{
    display:flex;
    align-items:center;
    gap:8px;
    flex-wrap:wrap;
    margin-bottom:9px;
  }

  .pay-badge{
    display:inline-flex;
    align-items:center;
    gap:8px;
    min-height:26px;
    padding:0 11px;
    border-radius:999px;
    font-size:.8rem;
    font-weight:900;
    border:1px solid transparent;
    white-space:nowrap;
  }

  .pay-badge::before{
    content:"";
    width:7px;
    height:7px;
    border-radius:999px;
    background:currentColor;
    opacity:.95;
    flex-shrink:0;
  }

  .pay-badge.status-pending{
    background:#fff6db;
    color:#d97706;
    border-color:#f3d47a;
  }

  .pay-badge.status-overdue{
    background:#ffe4e6;
    color:#e11d48;
    border-color:#fecdd3;
  }

  .pay-badge.status-urgent{
    background:#ffe4e6;
    color:#dc2626;
    border-color:#fecaca;
  }

  .pay-badge.status-paid{
    background:#dcfce7;
    color:#059669;
    border-color:#a7f3d0;
  }

  .pay-badge.status-cancelled{
    background:#f1f5f9;
    color:#64748b;
    border-color:#e2e8f0;
  }

  .pay-badge.status-partial{
    background:#dbeafe;
    color:#2563eb;
    border-color:#bfdbfe;
  }

  .pay-badge.freq{
    background:#eef2f7;
    color:#64748b;
    border-color:#e5e7eb;
    font-weight:800;
    padding:0 10px;
  }

  .pay-badge.freq::before{
    display:none;
  }

  .pay-card-title{
    margin:0;
    font-size:.98rem;
    line-height:1.35;
    font-weight:900;
    color:#0f172a;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
    transition:color .2s ease;
  }

  .pay-card:hover .pay-card-title{
    color:#0b63f6;
  }

  .pay-card-category{
    margin-top:4px;
    font-size:.88rem;
    color:#64748b;
    font-weight:500;
  }

  .pay-card-right{
    text-align:right;
    flex-shrink:0;
    min-width:138px;
  }

  .pay-card-amount{
    font-size:1.02rem;
    line-height:1.1;
    font-weight:900;
    color:#0f172a;
    letter-spacing:-.02em;
    font-variant-numeric:tabular-nums;
  }

  .pay-card-currency{
    margin-top:4px;
    font-size:.82rem;
    color:#64748b;
    font-weight:500;
  }

  .pay-card-foot{
    margin-top:14px;
    padding-top:12px;
    border-top:1px solid #eef2f7;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:14px;
  }

  .pay-meta{
    display:flex;
    align-items:center;
    gap:16px;
    flex-wrap:wrap;
    min-width:0;
  }

  .pay-meta-item{
    display:inline-flex;
    align-items:center;
    gap:7px;
    color:#64748b;
    font-size:.86rem;
    font-weight:500;
    white-space:nowrap;
  }

  .pay-meta-item .icon{
    width:15px;
    height:15px;
    flex-shrink:0;
    color:#6b7280;
  }

  .pay-meta-item.is-danger{
    color:#ef4444;
    font-weight:700;
  }

  .pay-meta-item.is-overdue{
    color:#e11d48;
    font-weight:800;
  }

  .pay-chevron{
    width:17px;
    height:17px;
    color:#64748b;
    flex-shrink:0;
    transition:transform .2s ease, color .2s ease;
  }

  .pay-card:hover .pay-chevron{
    transform:translateX(2px);
    color:#0b63f6;
  }

  .pay-empty{
    background:#fff;
    border:1px solid #e8edf5;
    border-radius:18px;
    padding:44px 18px;
    text-align:center;
    color:#64748b;
    box-shadow:0 10px 28px rgba(15,23,42,.04);
  }

  .pay-empty-icon{
    width:40px;
    height:40px;
    opacity:.35;
    margin:0 auto 12px;
    display:block;
  }

  .pay-empty-title{
    font-size:.96rem;
    font-weight:900;
    color:#0f172a;
    margin-bottom:6px;
  }

  .pay-pagination{
    margin-top:14px;
  }

  .pay-svg{
    width:100%;
    height:100%;
    display:block;
  }

  @media (max-width: 1200px){
    .pay-filters{
      grid-template-columns:1fr 1fr;
    }
  }

  @media (max-width: 900px){
    .pay-head{
      flex-direction:column;
      align-items:stretch;
    }

    .pay-filters{
      grid-template-columns:1fr;
    }

    .pay-card-head,
    .pay-card-foot{
      flex-direction:column;
      align-items:flex-start;
    }

    .pay-card-right{
      text-align:left;
      min-width:0;
    }
  }

  @media (max-width: 640px){
    .pay-wrap{
      padding-bottom:22px;
    }

    .pay-title{
      font-size:1.55rem;
    }

    .pay-filters-card,
    .pay-card{
      padding-left:14px;
      padding-right:14px;
    }

    .pay-summary{
      width:100%;
    }

    .pay-summary-chip{
      width:100%;
      justify-content:space-between;
    }
  }
</style>

@php
  if (!function_exists('payIcon')) {
      function payIcon($name) {
          $icons = [
              'filter' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" class="pay-svg"><path d="M4 6h16"/><path d="M7 12h10"/><path d="M10 18h4"/><path d="M6 4v4"/><path d="M18 10v4"/><path d="M12 16v4"/></svg>',
              'search' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="pay-svg"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>',
              'chevron-down' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="pay-svg"><path d="m6 9 6 6 6-6"/></svg>',
              'calendar' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" class="pay-svg"><rect x="3" y="4" width="18" height="17" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/></svg>',
              'paperclip' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" class="pay-svg"><path d="m21.44 11.05-8.49 8.49a5.5 5.5 0 0 1-7.78-7.78l8.84-8.84a3.5 3.5 0 1 1 4.95 4.95l-8.84 8.84a1.5 1.5 0 0 1-2.12-2.12l8.13-8.13"/></svg>',
              'chevron-right' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="pay-svg"><path d="m9 6 6 6-6 6"/></svg>',
              'wallet' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" class="pay-svg"><path d="M3 7a2 2 0 0 1 2-2h13a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7z"/><path d="M16 12h.01"/><path d="M3 8h17"/></svg>',
          ];
          return $icons[$name] ?? $icons['wallet'];
      }
  }
@endphp

<div class="pay-wrap">
  <div class="pay-head">
    <div>
      <h1 class="pay-title">Pagos</h1>
      <div class="pay-sub">{{ $items->total() }} pagos registrados</div>
    </div>

    <div class="pay-summary">
      <div class="pay-summary-chip">Pendiente <b>${{ number_format((float)($totPending ?? 0), 2) }}</b></div>
      <div class="pay-summary-chip">Vencido <b>${{ number_format((float)($totOverdue ?? 0), 2) }}</b></div>
    </div>
  </div>

  @if(session('success'))
    <div class="pay-alert">{{ session('success') }}</div>
  @endif

  <div class="pay-filters-card">
    <div class="pay-filters-top">
      <span class="icon">{!! payIcon('filter') !!}</span>
      <span>Filtros</span>
    </div>

    <form method="GET" action="{{ route('accounting.payables.index') }}" id="payFiltersForm" autocomplete="off">
      @if(request()->filled('scope'))
        <input type="hidden" name="scope" value="{{ request('scope') }}">
      @endif

      @if(request()->filled('company_id'))
        <input type="hidden" name="company_id" value="{{ request('company_id') }}">
      @endif

      <input type="hidden" name="category" id="payCategoryInput" value="{{ request('category') }}">
      <input type="hidden" name="status" id="payStatusInput" value="{{ request('status') }}">
      <input type="hidden" name="frequency" id="payFrequencyInput" value="{{ request('frequency') }}">

      <div class="pay-filters">
        <div class="pay-search">
          <span class="icon">{!! payIcon('search') !!}</span>
          <input
            type="text"
            name="search"
            id="paySearchInput"
            value="{{ request('search') }}"
            placeholder="Buscar pago..."
          >
        </div>

        <div class="pay-dd" data-name="category">
          <button type="button" class="pay-dd-trigger">
            <span class="pay-dd-label">{{ $categoryLabels[request('category')] ?? 'Todas las categorías' }}</span>
            <span class="pay-dd-caret">{!! payIcon('chevron-down') !!}</span>
          </button>

          <div class="pay-dd-menu">
            <button type="button" class="pay-dd-option {{ request('category') ? '' : 'active' }}" data-value="">
              Todas las categorías
            </button>

            @foreach($categoryLabels as $key => $label)
              <button type="button"
                      class="pay-dd-option {{ request('category') === $key ? 'active' : '' }}"
                      data-value="{{ $key }}">
                {{ $label }}
              </button>
            @endforeach
          </div>
        </div>

        <div class="pay-dd" data-name="status">
          <button type="button" class="pay-dd-trigger">
            <span class="pay-dd-label">
              {{
                match(request('status')) {
                  'pendiente' => 'Pendiente',
                  'urgente'   => 'Urgente',
                  'atrasado', 'vencido' => 'Atrasado',
                  'pagado'    => 'Pagado',
                  'cancelado' => 'Cancelado',
                  'parcial'   => 'Parcial',
                  default     => 'Todos los estados',
                }
              }}
            </span>
            <span class="pay-dd-caret">{!! payIcon('chevron-down') !!}</span>
          </button>

          <div class="pay-dd-menu">
            <button type="button" class="pay-dd-option {{ request('status') ? '' : 'active' }}" data-value="">
              Todos los estados
            </button>
            <button type="button" class="pay-dd-option {{ request('status') === 'pendiente' ? 'active' : '' }}" data-value="pendiente">Pendiente</button>
            <button type="button" class="pay-dd-option {{ request('status') === 'urgente' ? 'active' : '' }}" data-value="urgente">Urgente</button>
            <button type="button" class="pay-dd-option {{ request('status') === 'atrasado' || request('status') === 'vencido' ? 'active' : '' }}" data-value="atrasado">Atrasado</button>
            <button type="button" class="pay-dd-option {{ request('status') === 'pagado' ? 'active' : '' }}" data-value="pagado">Pagado</button>
            <button type="button" class="pay-dd-option {{ request('status') === 'cancelado' ? 'active' : '' }}" data-value="cancelado">Cancelado</button>
            <button type="button" class="pay-dd-option {{ request('status') === 'parcial' ? 'active' : '' }}" data-value="parcial">Parcial</button>
          </div>
        </div>

        <div class="pay-dd" data-name="frequency">
          <button type="button" class="pay-dd-trigger">
            <span class="pay-dd-label">{{ $frequencyLabels[request('frequency')] ?? 'Todas las frecuencias' }}</span>
            <span class="pay-dd-caret">{!! payIcon('chevron-down') !!}</span>
          </button>

          <div class="pay-dd-menu">
            <button type="button" class="pay-dd-option {{ request('frequency') ? '' : 'active' }}" data-value="">
              Todas las frecuencias
            </button>

            @foreach($frequencyLabels as $key => $label)
              <button type="button"
                      class="pay-dd-option {{ request('frequency') === $key ? 'active' : '' }}"
                      data-value="{{ $key }}">
                {{ $label }}
              </button>
            @endforeach
          </div>
        </div>
      </div>

      <div class="pay-results">{{ $items->count() }} resultado(s) en esta página</div>
    </form>
  </div>

  @if($items->count() === 0)
    <div class="pay-empty">
      <span class="pay-empty-icon">{!! payIcon('wallet') !!}</span>
      <div class="pay-empty-title">No hay pagos registrados</div>
      <div>
        {{ request()->hasAny(['search','category','status','frequency','scope']) ? 'No se encontraron resultados con los filtros actuales.' : 'Aún no existen pagos para mostrar.' }}
      </div>
    </div>
  @else
    <div class="pay-list">
      @foreach($items as $payment)
        @php
          $status = $effectiveStatus($payment);

          $statusClass = match($status) {
              'pagado' => 'paid',
              'urgente' => 'urgent',
              'atrasado', 'vencido' => 'overdue',
              'cancelado' => 'cancelled',
              'parcial' => 'partial',
              default => 'pending',
          };

          $title = trim((string) (
              $payment->title
              ?? $payment->concept
              ?? $payment->name
              ?? $payment->folio
              ?? 'Pago'
          ));

          $categoryKey = Str::lower((string)($payment->category ?? 'otros'));
          $frequencyKey = Str::lower((string)($payment->frequency ?? 'unico'));
          $currency = $normalizeText($payment->currency ?? 'MXN', 'MXN');
          $amount = (float)($payment->amount ?? 0);
          $documents = $documentsCount($payment);

          $dueDate = null;
          try {
              $dueDate = !empty($payment->due_date) ? Carbon::parse($payment->due_date) : null;
          } catch (\Throwable $e) {
              $dueDate = null;
          }

          $daysUntilDue = null;
          $daysOverdue = null;

          if ($dueDate) {
              if ($dueDate->isSameDay($today)) {
                  $daysUntilDue = 0;
              } elseif ($dueDate->gt($today)) {
                  $daysUntilDue = $today->diffInDays($dueDate);
              } elseif ($dueDate->lt($today)) {
                  $daysOverdue = $dueDate->diffInDays($today);
              }
          }

          $detailUrl = $routeFirst(
              ['accounting.payables.show', 'accounting.payables.edit'],
              $payment,
              '#'
          );
        @endphp

        <a href="{{ $detailUrl }}"
           class="pay-card {{ $status === 'atrasado' ? 'is-overdue' : '' }} {{ $status === 'urgente' ? 'is-urgent' : '' }} {{ $status === 'pagado' ? 'is-paid' : '' }}">
          <div class="pay-card-head">
            <div class="pay-card-main">
              <div class="pay-badges">
                <span class="pay-badge status-{{ $statusClass }}">
                  {{ $statusLabels[$status] ?? ucfirst($status) }}
                </span>

                <span class="pay-badge freq">
                  {{ $frequencyLabels[$frequencyKey] ?? Str::ucfirst(str_replace('_',' ', $frequencyKey)) }}
                </span>
              </div>

              <h3 class="pay-card-title">{{ $title }}</h3>
              <div class="pay-card-category">
                {{ $categoryLabels[$categoryKey] ?? Str::ucfirst(str_replace('_',' ', $categoryKey)) }}
              </div>
            </div>

            <div class="pay-card-right">
              <div class="pay-card-amount">${{ number_format($amount, 2) }}</div>
              <div class="pay-card-currency">{{ $currency }}</div>
            </div>
          </div>

          <div class="pay-card-foot">
            <div class="pay-meta">
              <span class="pay-meta-item">
                <span class="icon">{!! payIcon('calendar') !!}</span>
                <span>{{ $dueDate ? $dueDate->format('d M Y') : 'Sin fecha' }}</span>
              </span>

              @if(!is_null($daysUntilDue) && $status !== 'pagado' && $status !== 'cancelado' && $daysUntilDue > 0)
                <span class="pay-meta-item {{ $daysUntilDue <= 3 ? 'is-danger' : '' }}">
                  {{ $daysUntilDue }} {{ $daysUntilDue === 1 ? 'día' : 'días' }}
                </span>
              @endif

              @if(!is_null($daysOverdue) && $status !== 'pagado' && $status !== 'cancelado')
                <span class="pay-meta-item is-overdue">
                  {{ $daysOverdue }} {{ $daysOverdue === 1 ? 'día atrasado' : 'días atrasado' }}
                </span>
              @endif

              @if($documents > 0)
                <span class="pay-meta-item">
                  <span class="icon">{!! payIcon('paperclip') !!}</span>
                  <span>{{ $documents }}</span>
                </span>
              @endif
            </div>

            <span class="pay-chevron">{!! payIcon('chevron-right') !!}</span>
          </div>
        </a>
      @endforeach
    </div>

    <div class="pay-pagination">
      {{ $items->links() }}
    </div>
  @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('payFiltersForm');
  if (!form) return;

  const searchInput = document.getElementById('paySearchInput');
  let debounceTimer = null;

  const submitForm = () => form.submit();

  if (searchInput) {
    searchInput.addEventListener('input', function () {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(submitForm, 420);
    });

    searchInput.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        clearTimeout(debounceTimer);
        submitForm();
      }
    });
  }

  const dropdowns = form.querySelectorAll('.pay-dd');

  const closeAll = (except = null) => {
    dropdowns.forEach(dd => {
      if (dd !== except) dd.classList.remove('open');
    });
  };

  dropdowns.forEach((dd) => {
    const trigger = dd.querySelector('.pay-dd-trigger');
    const label = dd.querySelector('.pay-dd-label');
    const options = dd.querySelectorAll('.pay-dd-option');
    const fieldName = dd.dataset.name;
    const hiddenInput = document.getElementById(
      'pay' + fieldName.charAt(0).toUpperCase() + fieldName.slice(1) + 'Input'
    );

    trigger.addEventListener('click', function (e) {
      e.preventDefault();
      const isOpen = dd.classList.contains('open');
      closeAll(dd);
      dd.classList.toggle('open', !isOpen);
    });

    options.forEach((option) => {
      option.addEventListener('click', function () {
        const value = option.dataset.value ?? '';
        const text = option.textContent.trim();

        options.forEach(opt => opt.classList.remove('active'));
        option.classList.add('active');

        if (hiddenInput) hiddenInput.value = value;
        if (label) label.textContent = text;

        dd.classList.remove('open');
        submitForm();
      });
    });
  });

  document.addEventListener('click', function (e) {
    if (!e.target.closest('.pay-dd')) {
      closeAll();
    }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      closeAll();
    }
  });
});
</script>
@endsection