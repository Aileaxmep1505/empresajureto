<?php

use Illuminate\Support\Facades\Route;

$fcResolveRoute = function (array $names, array $params = [], string $fallback = '#') {
    foreach ($names as $name) {
        if (Route::has($name)) {
            return route($name, $params);
        }
    }
    return $fallback;
};

$fcIsActive = function (array $patterns) {
    foreach ($patterns as $pattern) {
        if (request()->routeIs($pattern)) {
            return true;
        }
    }
    return false;
};

$fcBrandUrl = $fcResolveRoute([
    'accounting.dashboard',
    'dashboard',
]);

$fcNewPayUrl = $fcResolveRoute([
    'accounting.payables.create',
]);

$fcNavItems = [
    [
        'label' => 'Dashboard',
        'route' => ['accounting.dashboard', 'dashboard'],
        'match' => ['accounting.dashboard', 'dashboard'],
        'icon'  => 'dashboard',
    ],
    [
        'label' => 'Por Pagar',
        'route' => ['accounting.payables.index'],
        'match' => ['accounting.payables.*'],
        'icon'  => 'payables',
    ],
    [
        'label' => 'Por Cobrar',
        'route' => ['accounting.receivables.index'],
        'match' => ['accounting.receivables.*'],
        'icon'  => 'receivables',
    ],
    [
        'label' => 'Clientes',
        'route' => ['accounting.clients.index', 'accounting.customers.index'],
        'match' => ['accounting.clients.*', 'accounting.customers.*'],
        'icon'  => 'clients',
    ],
    [
        'label' => 'Calendario',
        'route' => ['agenda.index', 'accounting.calendar.index'],
        'match' => ['agenda.*', 'accounting.calendar.*'],
        'icon'  => 'calendar',
    ],
    [
        'label' => 'Documentos',
        'route' => ['documents.index', 'accounting.documents.index'],
        'match' => ['documents.*', 'accounting.documents.*'],
        'icon'  => 'documents',
    ],
    [
        'label' => 'Reportes',
        'route' => ['accounting.reports.index'],
        'match' => ['accounting.reports.*'],
        'icon'  => 'reports',
    ],
];

if (!function_exists('fcUiIcon')) {
    function fcUiIcon(string $name): string
    {
        return match ($name) {
            'dashboard' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/></svg>',
            'payables' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16"/><path d="M7 4v6"/><path d="M17 14l3 3-3 3"/><path d="M20 17H9"/><path d="M4 12v7a1 1 0 0 0 1 1h8"/></svg>',
            'receivables' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16"/><path d="M7 4v6"/><path d="M7 14l-3 3 3 3"/><path d="M4 17h11"/><path d="M20 12v7a1 1 0 0 1-1 1H9"/></svg>',
            'clients' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/><circle cx="9.5" cy="7" r="3.2"/><path d="M20 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 4.13a3.2 3.2 0 0 1 0 5.74"/></svg>',
            'calendar' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="17" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/></svg>',
            'documents' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/><path d="M9 13h6"/><path d="M9 17h6"/></svg>',
            'reports' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19V9"/><path d="M10 19V5"/><path d="M16 19v-8"/><path d="M22 19V3"/></svg>',
            default => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/></svg>',
        };
    }
}
?>

<style>
  .fc-topbar-wrap{
    margin:0 0 18px 0;
  }

  .fc-topbar{
    width:100%;
    background:rgba(255,255,255,.94);
    border:1px solid #e7edf5;
    border-radius:20px;
    padding:12px 16px;
    display:flex;
    align-items:center;
    gap:18px;
    box-shadow:
      0 10px 30px rgba(15,23,42,.05),
      inset 0 1px 0 rgba(255,255,255,.8);
    backdrop-filter:blur(10px);
  }

  .fc-brand{
    display:inline-flex;
    align-items:center;
    gap:12px;
    text-decoration:none;
    flex:0 0 auto;
    min-width:fit-content;
  }

  .fc-brand-badge{
    width:42px;
    height:42px;
    border-radius:14px;
    display:grid;
    place-items:center;
    background:linear-gradient(180deg,#3b82f6 0%,#2563eb 100%);
    color:#fff;
    box-shadow:0 10px 22px rgba(37,99,235,.22);
    font-weight:800;
    font-size:1.1rem;
  }

  .fc-brand-text{
    color:#111827;
    font-size:1rem;
    font-weight:800;
    letter-spacing:-.02em;
    white-space:nowrap;
  }

  .fc-nav{
    flex:1 1 auto;
    display:flex;
    align-items:center;
    justify-content:center;
    gap:6px;
    min-width:0;
    flex-wrap:wrap;
  }

  .fc-nav-link{
    display:inline-flex;
    align-items:center;
    gap:10px;
    min-height:42px;
    padding:0 14px;
    text-decoration:none;
    border-radius:14px;
    color:#6b7280;
    font-size:.94rem;
    font-weight:600;
    transition:all .2s cubic-bezier(.4,0,.2,1);
    white-space:nowrap;
    border:1px solid transparent;
  }

  .fc-nav-link:hover{
    color:#111827;
    background:#f8fafc;
    border-color:#edf2f7;
    transform:translateY(-1px);
  }

  .fc-nav-link.active{
    color:#0f172a;
    background:#f3f7ff;
    border-color:#dbe7ff;
    box-shadow:inset 0 1px 0 rgba(255,255,255,.8);
  }

  .fc-nav-icon{
    width:18px;
    height:18px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    flex:0 0 auto;
    opacity:.95;
  }

  .fc-nav-icon svg{
    width:18px;
    height:18px;
    display:block;
  }

  .fc-actions{
    flex:0 0 auto;
    display:flex;
    align-items:center;
    justify-content:flex-end;
  }

  .fc-primary-btn{
    display:inline-flex;
    align-items:center;
    gap:10px;
    min-height:44px;
    padding:0 18px;
    border-radius:16px;
    text-decoration:none;
    border:1px solid #2563eb;
    background:linear-gradient(180deg,#3b82f6 0%,#2563eb 100%);
    color:#fff;
    font-size:.98rem;
    font-weight:700;
    box-shadow:0 12px 24px rgba(37,99,235,.20);
    transition:all .22s cubic-bezier(.4,0,.2,1);
    white-space:nowrap;
  }

  .fc-primary-btn:hover{
    transform:translateY(-1px);
    box-shadow:0 16px 28px rgba(37,99,235,.24);
  }

  .fc-primary-btn svg{
    width:17px;
    height:17px;
    display:block;
  }

  .ac-wrap{max-width:1250px;margin:0 auto;padding:18px;}
  .ac-head{display:flex;gap:12px;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;margin-bottom:12px;}
  .ac-title{font-size:22px;font-weight:900;letter-spacing:-.02em;}
  .ac-sub{color:#6b7280;font-size:13px;margin-top:4px}
  .ac-actions{display:flex;gap:10px;align-items:center;flex-wrap:wrap;}
  .ac-btn{border:1px solid rgba(0,0,0,.12);padding:10px 12px;border-radius:12px;background:#fff;text-decoration:none;display:inline-flex;gap:8px;align-items:center;font-weight:800}
  .ac-btn.primary{background:#111827;color:#fff;border-color:#111827}
  .ac-btn.ghost{background:transparent}
  .ac-muted{color:#6b7280;font-size:12px}
  .ac-right{text-align:right}

  .ac-filters{display:flex;gap:10px;flex-wrap:wrap;background:#fff;border:1px solid rgba(0,0,0,.08);border-radius:16px;padding:12px;margin:12px 0}
  .ac-inp{border:1px solid rgba(0,0,0,.14);border-radius:12px;padding:10px 12px;min-width:160px;background:#fff}
  textarea.ac-inp{min-height:96px}

  .ac-cardgrid{display:grid;grid-template-columns:repeat(12,1fr);gap:12px;margin:12px 0;}
  .ac-card{grid-column:span 6;background:#fff;border:1px solid rgba(0,0,0,.08);border-radius:16px;padding:14px}
  @media (max-width:900px){.ac-card{grid-column:span 12}}
  .ac-kpi{font-size:12px;color:#6b7280}
  .ac-kpiv{font-size:20px;font-weight:900;margin-top:4px}

  .kpi-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:12px}
  @media (min-width:1024px){.kpi-grid{grid-template-columns:repeat(4,1fr)}}

  .kpi-card{
    display:block;
    border-radius:16px;
    padding:14px;
    border:1px solid rgba(0,0,0,.06);
    text-decoration:none;
    transition:transform .14s ease, box-shadow .14s ease;
    box-shadow:0 10px 22px rgba(15,23,42,.06);
  }
  .kpi-card:hover{transform:translateY(-2px);box-shadow:0 14px 28px rgba(15,23,42,.10)}
  .kpi-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:6px}
  .kpi-icon{width:34px;height:34px;border-radius:12px;display:flex;align-items:center;justify-content:center;border:1px solid rgba(0,0,0,.06);background:rgba(255,255,255,.7)}
  .kpi-label{font-size:12px;font-weight:800;opacity:.9}
  .kpi-value{font-size:24px;font-weight:950;letter-spacing:-.02em}
  .kpi-sub{font-size:12px;margin-top:6px;opacity:.9;display:flex;align-items:center;justify-content:space-between;gap:8px}
  .kpi-arrow{font-weight:900;opacity:.7}

  .kpi-blue{background:linear-gradient(180deg, rgba(59,130,246,.12), rgba(59,130,246,.06)); color:#1e3a8a}
  .kpi-amber{background:linear-gradient(180deg, rgba(245,158,11,.14), rgba(245,158,11,.06)); color:#92400e}
  .kpi-emerald{background:linear-gradient(180deg, rgba(16,185,129,.14), rgba(16,185,129,.06)); color:#065f46}
  .kpi-rose{background:linear-gradient(180deg, rgba(244,63,94,.14), rgba(244,63,94,.06)); color:#9f1239}

  .ac-table{width:100%;border-collapse:separate;border-spacing:0 10px;}
  .ac-row{background:#fff;border:1px solid rgba(0,0,0,.08);border-radius:14px;}
  .ac-row td{padding:12px 10px;vertical-align:middle}
  .ac-badge{font-size:12px;padding:6px 10px;border-radius:999px;border:1px solid rgba(0,0,0,.1);display:inline-block}

  .ac-grid{display:grid;grid-template-columns:repeat(12,1fr);gap:12px}
  .ac-col6{grid-column:span 6}
  .ac-col12{grid-column:span 12}
  @media (max-width:900px){.ac-col6{grid-column:span 12}}
  label{font-weight:900;font-size:12px;color:#111827;display:block;margin-bottom:6px}

  @media (max-width:1180px){
    .fc-topbar{
      flex-wrap:wrap;
      justify-content:space-between;
    }

    .fc-nav{
      order:3;
      width:100%;
      justify-content:flex-start;
      padding-top:6px;
    }
  }

  @media (max-width:700px){
    .fc-topbar{
      padding:12px;
      border-radius:18px;
      gap:12px;
    }

    .fc-brand-badge{
      width:38px;
      height:38px;
      border-radius:12px;
      font-size:1rem;
    }

    .fc-brand-text{
      font-size:.95rem;
    }

    .fc-nav{
      gap:8px;
    }

    .fc-nav-link{
      min-height:38px;
      padding:0 12px;
      font-size:.88rem;
    }

    .fc-actions{
      width:100%;
    }

    .fc-primary-btn{
      width:100%;
      justify-content:center;
    }
  }
</style>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  window.acConfirmDelete = async function(formId){
    const res = await Swal.fire({
      title: '¿Eliminar?',
      text: 'Esta acción no se puede deshacer.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar',
      reverseButtons: true
    });
    if(res.isConfirmed) document.getElementById(formId).submit();
  }
</script>

<div class="fc-topbar-wrap">
  <div class="fc-topbar">
    <a href="<?php echo e($fcBrandUrl); ?>" class="fc-brand">
      <span class="fc-brand-badge">$</span>
      <span class="fc-brand-text">FinControl</span>
    </a>

    <nav class="fc-nav">
      <?php foreach($fcNavItems as $item): ?>
        <?php
          $url = $fcResolveRoute($item['route']);
          $active = $fcIsActive($item['match']);
        ?>
        <a href="<?php echo e($url); ?>" class="fc-nav-link <?php echo $active ? 'active' : ''; ?>">
          <span class="fc-nav-icon"><?php echo fcUiIcon($item['icon']); ?></span>
          <span><?php echo e($item['label']); ?></span>
        </a>
      <?php endforeach; ?>
    </nav>

    <div class="fc-actions">
      <a href="<?php echo e($fcNewPayUrl); ?>" class="fc-primary-btn">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 5v14"></path>
          <path d="M5 12h14"></path>
        </svg>
        <span>Nuevo Pago</span>
      </a>
    </div>
  </div>
</div>