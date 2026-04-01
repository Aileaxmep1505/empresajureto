@extends('layouts.app')
@section('title','Detalle CxP')

@section('content')
@include('accounting.partials.ui')

@php
  use Illuminate\Support\Carbon;

  $saldo = max((float) $item->amount - (float) $item->amount_paid, 0);

  $statusRaw = mb_strtolower((string) ($item->status ?? 'pendiente'));
  $statusMap = [
      'pending'   => 'Pendiente',
      'partial'   => 'Parcial',
      'paid'      => 'Pagado',
      'overdue'   => 'Vencido',
      'cancelled' => 'Cancelado',
      'pendiente' => 'Pendiente',
      'parcial'   => 'Parcial',
      'pagado'    => 'Pagado',
      'vencido'   => 'Vencido',
      'cancelado' => 'Cancelado',
      'urgente'   => 'Urgente',
  ];
  $statusLabel = $statusMap[$statusRaw] ?? ucfirst((string) $item->status);

  $statusClass = match($statusRaw) {
      'paid', 'pagado' => 'is-success',
      'partial', 'parcial' => 'is-warning',
      'overdue', 'vencido' => 'is-danger',
      'cancelled', 'cancelado' => 'is-neutral',
      'urgente' => 'is-pending',
      default => 'is-pending',
  };

  $frequencyRaw = mb_strtolower((string) ($item->frequency ?? 'mensual'));
  $frequencyMap = [
      'one_time'   => 'Único',
      'weekly'     => 'Semanal',
      'biweekly'   => 'Quincenal',
      'monthly'    => 'Mensual',
      'bimonthly'  => 'Bimestral',
      'quarterly'  => 'Trimestral',
      'semiannual' => 'Semestral',
      'annual'     => 'Anual',
      'unico'      => 'Único',
      'semanal'    => 'Semanal',
      'quincenal'  => 'Quincenal',
      'mensual'    => 'Mensual',
      'bimestral'  => 'Bimestral',
      'trimestral' => 'Trimestral',
      'semestral'  => 'Semestral',
      'anual'      => 'Anual',
  ];
  $frequencyLabel = $frequencyMap[$frequencyRaw] ?? 'Mensual';

  $categoryRaw = mb_strtolower((string) ($item->category ?? 'proveedores'));
  $categoryMap = [
      'impuestos'   => 'Impuestos',
      'servicios'   => 'Servicios',
      'nomina'      => 'Nómina',
      'seguros'     => 'Seguros',
      'retenciones' => 'Retenciones',
      'proveedores' => 'Proveedores',
      'renta'       => 'Renta',
      'otros'       => 'Otros',
  ];
  $categoryLabel = $categoryMap[$categoryRaw] ?? ucfirst((string) ($item->category ?? '—'));

  $currency = $item->currency ?: 'MXN';
  $dueDateLabel = !empty($item->due_date) ? Carbon::parse($item->due_date)->format('d M Y') : '—';
  $paidDateLabel = !empty($item->paid_at) ? Carbon::parse($item->paid_at)->format('d M Y') : null;

  $supplierName = $item->supplier_name ?? $item->vendor_name ?? '—';
  $movementDateValue = old('movement_date', now()->toDateString());
  $movementMethodValue = old('method', 'transferencia');
@endphp

<style>
  :root{
    --cxp-bg:#f3f5f9;
    --cxp-surface:#ffffff;
    --cxp-surface-2:#f7f8fb;
    --cxp-border:#e6eaf0;
    --cxp-border-2:#d8dde6;
    --cxp-text:#0f172a;
    --cxp-muted:#64748b;
    --cxp-green:#059669;
    --cxp-blue:#2563eb;
    --cxp-blue-soft:#eff6ff;
    --cxp-red:#ef4444;
    --cxp-yellow:#d97706;
    --cxp-yellow-soft:#fef3c7;
    --cxp-shadow:0 10px 26px rgba(15,23,42,.05);
    --cxp-shadow-modal:0 24px 68px rgba(15,23,42,.24);
    --cxp-radius-xl:20px;
    --cxp-radius-lg:14px;
    --cxp-radius-md:10px;
    --cxp-transition:all .22s cubic-bezier(.4,0,.2,1);
  }

  body{ background:var(--cxp-bg); }

  .cxp-page{
    max-width:980px;
    margin:0 auto;
    padding:18px 14px 44px;
  }

  .cxp-back{
    display:inline-flex;
    align-items:center;
    gap:8px;
    color:#526581;
    text-decoration:none;
    font-weight:500;
    font-size:.96rem;
    margin-bottom:14px;
    transition:var(--cxp-transition);
  }

  .cxp-back:hover{
    color:#0f172a;
    transform:translateX(-2px);
  }

  .cxp-error{
    border:1px solid rgba(239,68,68,.18);
    background:rgba(239,68,68,.07);
    color:#b91c1c;
    border-radius:14px;
    padding:12px 14px;
    margin-bottom:14px;
    font-size:.92rem;
  }

  .cxp-error strong{
    display:block;
    margin-bottom:6px;
  }

  .cxp-error ul{
    margin:0;
    padding-left:18px;
  }

  .cxp-hero{
    background:var(--cxp-surface);
    border:1px solid var(--cxp-border);
    border-radius:var(--cxp-radius-xl);
    box-shadow:var(--cxp-shadow);
    overflow:hidden;
  }

  .cxp-hero-top{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:20px;
    padding:22px 24px 18px;
  }

  .cxp-hero-title{
    margin:12px 0 5px;
    font-size:1.65rem;
    line-height:1.05;
    font-weight:800;
    color:var(--cxp-text);
    letter-spacing:-.03em;
  }

  .cxp-hero-sub{
    margin:0;
    color:var(--cxp-muted);
    font-size:.94rem;
  }

  .cxp-amount{
    text-align:right;
    min-width:170px;
  }

  .cxp-amount strong{
    display:block;
    font-size:2.9rem;
    line-height:1;
    letter-spacing:-.05em;
    color:#0b1730;
    font-weight:800;
  }

  .cxp-amount span{
    display:block;
    margin-top:5px;
    font-size:.92rem;
    color:#60708a;
    font-weight:600;
  }

  .cxp-badges{
    display:flex;
    flex-wrap:wrap;
    gap:8px;
  }

  .cxp-badge{
    display:inline-flex;
    align-items:center;
    gap:7px;
    border-radius:999px;
    padding:7px 12px;
    font-size:.88rem;
    font-weight:600;
    line-height:1;
    border:1px solid transparent;
    white-space:nowrap;
  }

  .cxp-badge-dot{
    width:6px;
    height:6px;
    border-radius:999px;
    background:currentColor;
    opacity:.95;
  }

  .cxp-badge.is-pending{ background:var(--cxp-yellow-soft); border-color:#f5d36a; color:var(--cxp-yellow); }
  .cxp-badge.is-warning{ background:#fff7ed; border-color:#fdba74; color:#c2410c; }
  .cxp-badge.is-success{ background:#ecfdf5; border-color:#86efac; color:#15803d; }
  .cxp-badge.is-danger{ background:#fff1f2; border-color:#fda4af; color:#be123c; }
  .cxp-badge.is-neutral{ background:#f1f5f9; border-color:#dbe3ee; color:#475569; }
  .cxp-badge.soft{ background:#eef2f7; color:#61758f; border-color:#e3e8ef; font-weight:600; }

  .cxp-divider{
    height:1px;
    background:var(--cxp-border);
  }

  .cxp-meta{
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:14px;
    padding:20px 24px 14px;
  }

  .cxp-info{
    background:var(--cxp-surface-2);
    border:1px solid #edf1f5;
    border-radius:18px;
    padding:15px 16px;
    display:flex;
    align-items:center;
    gap:12px;
  }

  .cxp-info-icon{
    width:38px;
    height:38px;
    border-radius:12px;
    display:grid;
    place-items:center;
    color:var(--cxp-blue);
    background:var(--cxp-blue-soft);
    flex:0 0 auto;
  }

  .cxp-info-text small{
    display:block;
    color:#5c6f89;
    font-size:.88rem;
    margin-bottom:3px;
  }

  .cxp-info-text strong{
    display:block;
    color:#0b1730;
    font-size:.96rem;
    font-weight:700;
  }

  .cxp-notes{
    padding:0 24px 16px;
  }

  .cxp-notes-label{
    display:block;
    margin-bottom:8px;
    color:#0b1730;
    font-size:.95rem;
    font-weight:700;
  }

  .cxp-notes-box{
    background:var(--cxp-surface-2);
    border:1px solid #edf1f5;
    border-radius:16px;
    padding:14px 16px;
    color:#5c6f89;
    min-height:48px;
    font-size:.95rem;
  }

  .cxp-actions{
    display:flex;
    gap:12px;
    align-items:center;
    flex-wrap:wrap;
    padding:16px 24px 20px;
    border-top:1px solid var(--cxp-border);
  }

  .cxp-btn{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:10px;
    min-height:42px;
    border-radius:14px;
    padding:0 18px;
    font-size:.94rem;
    font-weight:500;
    text-decoration:none;
    border:1px solid var(--cxp-border-2);
    background:#fff;
    color:#111827;
    cursor:pointer;
    transition:var(--cxp-transition);
    box-shadow:0 2px 8px rgba(15,23,42,.03);
  }

  .cxp-btn:hover{
    transform:translateY(-1px);
    box-shadow:0 8px 16px rgba(15,23,42,.05);
  }

  .cxp-btn.primary{
    background:linear-gradient(180deg,#10b981,#059669);
    border-color:#059669;
    color:#fff;
  }

  .cxp-btn.danger{
    background:#fff5f5;
    color:#ef4444;
    border-color:#fecaca;
  }

  .cxp-card{
    margin-top:16px;
    background:var(--cxp-surface);
    border:1px solid var(--cxp-border);
    border-radius:18px;
    box-shadow:var(--cxp-shadow);
    overflow:hidden;
  }

  .cxp-card-head{
    padding:16px 18px 12px;
    border-bottom:1px solid #edf1f5;
  }

  .cxp-card-title{
    margin:0;
    font-size:1rem;
    font-weight:700;
    color:#0b1730;
  }

  .cxp-card-sub{
    margin-top:4px;
    color:#708198;
    font-size:.88rem;
  }

  .cxp-card-body{
    padding:16px 18px 18px;
  }

  .cxp-movement-list{
    display:grid;
    gap:10px;
  }

  .cxp-movement{
    border:1px solid #e7ecf3;
    background:#fff;
    border-radius:14px;
    padding:12px 14px;
    display:grid;
    grid-template-columns:140px 120px 1fr 130px 110px;
    gap:12px;
    align-items:center;
  }

  .cxp-movement .muted{
    color:#708198;
  }

  .cxp-method{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-height:30px;
    border-radius:999px;
    padding:0 10px;
    background:#eff6ff;
    color:#2563eb;
    font-weight:600;
    font-size:.82rem;
    width:max-content;
    text-transform:capitalize;
  }

  .cxp-empty{
    border:1px dashed #d8e1eb;
    background:#fbfcfe;
    border-radius:14px;
    padding:18px;
    color:#708198;
    text-align:center;
    font-weight:600;
    font-size:.92rem;
  }

  .cxp-inline{
    display:inline;
  }

  .pay-modal{
    position:fixed;
    inset:0;
    z-index:1300;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:18px;
    opacity:0;
    visibility:hidden;
    pointer-events:none;
    transition:opacity .22s ease, visibility .22s ease;
  }

  .pay-modal.show{
    opacity:1;
    visibility:visible;
    pointer-events:auto;
  }

  .pay-modal-backdrop{
    position:absolute;
    inset:0;
    background:rgba(15,23,42,.64);
    backdrop-filter:blur(4px);
  }

  .pay-modal-dialog{
    position:relative;
    width:min(100%, 500px);
    background:#fff;
    border:1px solid #e5e7eb;
    border-radius:20px;
    box-shadow:var(--cxp-shadow-modal);
    overflow:hidden;
    transform:translateY(12px) scale(.985);
    transition:transform .24s cubic-bezier(.4,0,.2,1);
  }

  .pay-modal.show .pay-modal-dialog{
    transform:translateY(0) scale(1);
  }

  .pay-modal-head{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:14px;
    padding:20px 22px 8px;
  }

  .pay-modal-title-row{
    display:flex;
    align-items:center;
    gap:10px;
  }

  .pay-modal-icon{
    width:20px;
    height:20px;
    border-radius:999px;
    display:grid;
    place-items:center;
    color:#059669;
    flex:0 0 auto;
  }

  .pay-modal-title{
    margin:0;
    font-size:1rem;
    line-height:1.1;
    font-weight:700;
    color:#1f2937;
    letter-spacing:-.02em;
  }

  .pay-modal-sub{
    margin:7px 0 0 30px;
    color:#6b7280;
    font-size:.92rem;
    line-height:1.35;
  }

  .pay-modal-close{
    width:32px;
    height:32px;
    border:none;
    background:transparent;
    color:#6b7280;
    border-radius:10px;
    cursor:pointer;
    font-size:1.35rem;
    line-height:1;
    transition:var(--cxp-transition);
    flex:0 0 auto;
  }

  .pay-modal-close:hover{
    background:#f3f4f6;
    color:#111827;
  }

  .pay-modal-body{
    padding:8px 22px 22px;
  }

  .pay-form-grid{
    display:grid;
    gap:15px;
  }

  .pay-field label{
    display:block;
    margin-bottom:8px;
    color:#111827;
    font-size:.93rem;
    font-weight:600;
  }

  .pay-date,
  .pay-select{
    width:100%;
    min-height:42px;
    border:1.5px solid #d9e2ef;
    border-radius:12px;
    background:#fff;
    padding:0 14px;
    outline:none;
    font-size:.9rem;
    color:#111827;
    transition:var(--cxp-transition);
  }

  .pay-date{
    border-color:#3b82f6;
  }

  .pay-date:focus,
  .pay-select:focus{
    box-shadow:0 0 0 4px rgba(59,130,246,.12);
    border-color:#3b82f6;
  }

  .pay-upload{
    position:relative;
    border:2px dashed #e5e7eb;
    border-radius:14px;
    background:#fafafa;
    padding:14px 12px;
    transition:var(--cxp-transition);
  }

  .pay-upload:hover{
    border-color:#d1d5db;
    background:#f9fafb;
  }

  .pay-upload input[type="file"]{
    position:absolute;
    inset:0;
    opacity:0;
    cursor:pointer;
  }

  .pay-upload-inner{
    display:flex;
    align-items:center;
    gap:10px;
  }

  .pay-upload-icon{
    width:34px;
    height:34px;
    border-radius:10px;
    display:grid;
    place-items:center;
    background:#f3f4f6;
    color:#6b7280;
    flex:0 0 auto;
  }

  .pay-upload-title{
    font-size:.88rem;
    font-weight:600;
    color:#1f2937;
  }

  .pay-upload-sub{
    margin-top:1px;
    font-size:.8rem;
    color:#6b7280;
  }

  .pay-file-name{
    display:none;
    margin-top:8px;
    color:#059669;
    font-size:.82rem;
    font-weight:600;
  }

  .pay-file-name.show{
    display:block;
  }

  .pay-warning{
    display:flex;
    gap:7px;
    align-items:flex-start;
    color:#d97706;
    font-size:.82rem;
    line-height:1.25;
    margin-top:-2px;
  }

  .pay-warning svg{
    flex:0 0 auto;
    margin-top:1px;
  }

  .pay-actions{
    display:flex;
    gap:8px;
    margin-top:12px;
  }

  .pay-btn{
    min-height:38px;
    border-radius:10px;
    font-weight:500;
    font-size:.88rem;
    border:1px solid transparent;
    cursor:pointer;
    transition:var(--cxp-transition);
  }

  .pay-btn:hover{
    transform:translateY(-1px);
  }

  .pay-btn-confirm{
    flex:1 1 auto;
    background:#0f9f6e;
    color:#fff;
    box-shadow:0 6px 14px rgba(15,159,110,.16);
  }

  .pay-btn-confirm:hover{
    background:#0a8d61;
  }

  .pay-btn-cancel{
    flex:0 0 110px;
    background:#f3f4f6;
    color:#111827;
    border-color:#e5e7eb;
  }

  /* SweetAlert empresarial */
  .swal-enterprise-popup{
    width: 420px !important;
    border-radius: 22px !important;
    border: 1px solid #e8edf3 !important;
    background: #ffffff !important;
    box-shadow: 0 24px 60px rgba(15,23,42,.18) !important;
    padding: 1.2rem 1.2rem 1rem !important;
  }

  .swal-enterprise-title{
    color: #0f172a !important;
    font-size: 1.1rem !important;
    font-weight: 700 !important;
    letter-spacing: -.02em !important;
    padding: .1rem 1rem 0 !important;
  }

  .swal-enterprise-text{
    color: #64748b !important;
    font-size: .93rem !important;
    line-height: 1.45 !important;
    padding: .2rem 1rem 0 !important;
    margin: 0 !important;
  }

  .swal-enterprise-actions{
    gap: .6rem !important;
    padding: 1rem .4rem .3rem !important;
    margin: 0 !important;
  }

  .swal-enterprise-confirm,
  .swal-enterprise-cancel{
    min-width: 124px !important;
    height: 42px !important;
    border-radius: 12px !important;
    font-size: .92rem !important;
    font-weight: 500 !important;
    padding: 0 16px !important;
    margin: 0 !important;
    box-shadow: none !important;
  }

  .swal-enterprise-confirm{
    background: linear-gradient(180deg,#ef4444 0%, #dc2626 100%) !important;
    color: #fff !important;
    border: 1px solid #dc2626 !important;
  }

  .swal-enterprise-confirm:hover{
    background: #dc2626 !important;
  }

  .swal-enterprise-cancel{
    background: #f8fafc !important;
    color: #0f172a !important;
    border: 1px solid #dbe3ee !important;
  }

  .swal-enterprise-cancel:hover{
    background: #f1f5f9 !important;
  }

  .swal-enterprise-icon.swal2-warning{
    border-color: rgba(217,119,6,.24) !important;
    color: #d97706 !important;
  }

  .swal-toast-popup{
    width: 360px !important;
    border-radius: 16px !important;
    border: 1px solid #e7edf3 !important;
    background: rgba(255,255,255,.98) !important;
    backdrop-filter: blur(10px) !important;
    box-shadow: 0 18px 42px rgba(15,23,42,.14) !important;
    padding: 12px 14px !important;
  }

  .swal-toast-title{
    color: #0f172a !important;
    font-size: .92rem !important;
    font-weight: 600 !important;
    margin: 0 !important;
  }

  .swal2-timer-progress-bar{
    background: rgba(15,23,42,.12) !important;
  }

  @media (max-width: 980px){
    .cxp-hero-top{
      flex-direction:column;
    }

    .cxp-amount{
      text-align:left;
      min-width:unset;
    }

    .cxp-meta{
      grid-template-columns:1fr;
    }

    .cxp-movement{
      grid-template-columns:1fr;
    }
  }

  @media (max-width: 720px){
    .cxp-page{
      padding-inline:12px;
    }

    .cxp-hero-top,
    .cxp-meta,
    .cxp-notes,
    .cxp-actions,
    .cxp-card-head,
    .cxp-card-body{
      padding-left:16px;
      padding-right:16px;
    }

    .cxp-actions{
      flex-direction:column;
      align-items:stretch;
    }

    .cxp-btn{
      width:100%;
    }

    .cxp-hero-title{
      font-size:1.45rem;
    }

    .cxp-amount strong{
      font-size:2.4rem;
    }

    .pay-modal{
      padding:12px;
      align-items:center;
    }

    .pay-modal-head,
    .pay-modal-body{
      padding-left:16px;
      padding-right:16px;
    }

    .pay-actions{
      flex-direction:column-reverse;
    }

    .pay-btn-cancel{
      flex-basis:auto;
    }

    .swal-enterprise-popup,
    .swal-toast-popup{
      width: calc(100vw - 24px) !important;
    }
  }
</style>

<div class="cxp-page">
  <a class="cxp-back" href="{{ route('accounting.payables.index') }}">
    <span style="font-size:1.05rem;">←</span>
    <span>Volver a pagos</span>
  </a>

  @if ($errors->any())
    <div class="cxp-error">
      <strong>Por favor, corrige los siguientes errores:</strong>
      <ul>
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="cxp-hero">
    <div class="cxp-hero-top">
      <div style="flex:1 1 auto; min-width:0;">
        <div class="cxp-badges">
          <span class="cxp-badge {{ $statusClass }}">
            <span class="cxp-badge-dot"></span>
            {{ $statusLabel }}
          </span>

          <span class="cxp-badge soft">{{ $frequencyLabel }}</span>
          <span class="cxp-badge soft">{{ $categoryLabel }}</span>
        </div>

        <h1 class="cxp-hero-title">{{ $item->title ?: 'Cuenta por pagar' }}</h1>
        <p class="cxp-hero-sub">{{ $item->description ?: $supplierName }}</p>
      </div>

      <div class="cxp-amount">
        <strong>${{ number_format((float) $item->amount, 2) }}</strong>
        <span>{{ $currency }}</span>
      </div>
    </div>

    <div class="cxp-divider"></div>

    <div class="cxp-meta">
      <div class="cxp-info">
        <div class="cxp-info-icon">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="4" width="18" height="18" rx="2"></rect>
            <line x1="16" y1="2" x2="16" y2="6"></line>
            <line x1="8" y1="2" x2="8" y2="6"></line>
            <line x1="3" y1="10" x2="21" y2="10"></line>
          </svg>
        </div>
        <div class="cxp-info-text">
          <small>Fecha de vencimiento</small>
          <strong>{{ $dueDateLabel }}</strong>
        </div>
      </div>

      <div class="cxp-info">
        <div class="cxp-info-icon">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <polyline points="12 6 12 12 16 14"></polyline>
          </svg>
        </div>
        <div class="cxp-info-text">
          <small>Recordatorio</small>
          <strong>{{ (int) ($item->reminder_days_before ?? 0) }} días antes</strong>
        </div>
      </div>

      @if($paidDateLabel)
        <div class="cxp-info">
          <div class="cxp-info-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
              <path d="M20 6L9 17l-5-5"></path>
            </svg>
          </div>
          <div class="cxp-info-text">
            <small>Fecha de pago</small>
            <strong>{{ $paidDateLabel }}</strong>
          </div>
        </div>
      @endif

      <div class="cxp-info">
        <div class="cxp-info-icon">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 1v22"></path>
            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14.5a3.5 3.5 0 0 1 0 7H6"></path>
          </svg>
        </div>
        <div class="cxp-info-text">
          <small>Saldo pendiente</small>
          <strong>${{ number_format($saldo, 2) }}</strong>
        </div>
      </div>
    </div>

    <div class="cxp-notes">
      <span class="cxp-notes-label">Notas</span>
      <div class="cxp-notes-box">
        {{ $item->notes ?: 'Sin notas registradas.' }}
      </div>
    </div>

    <div class="cxp-actions">
      @if($saldo > 0 && !in_array($statusRaw, ['pagado','paid','cancelado','cancelled'], true))
        <button type="button" class="cxp-btn primary" data-open-pay-modal>
          <span style="font-size:1rem;">$</span>
          <span>Marcar como Pagado</span>
        </button>
      @endif

      <a class="cxp-btn" href="{{ route('accounting.payables.edit',$item) }}">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 20h9"></path>
          <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"></path>
        </svg>
        <span>Editar</span>
      </a>

      @if(\Illuminate\Support\Facades\Route::has('accounting.payables.destroy'))
        <form id="deletePayableForm" method="POST" action="{{ route('accounting.payables.destroy',$item) }}" class="cxp-inline">
          @csrf
          @method('DELETE')
          <button
            type="button"
            class="cxp-btn danger js-confirm-delete"
            data-form-id="deletePayableForm"
            data-confirm-title="¿Eliminar esta cuenta por pagar?"
            data-confirm-text="Esta acción no se puede deshacer."
            data-confirm-button="Sí, eliminar"
          >
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="3 6 5 6 21 6"></polyline>
              <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
              <path d="M10 11v6"></path>
              <path d="M14 11v6"></path>
              <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path>
            </svg>
            <span>Eliminar</span>
          </button>
        </form>
      @endif
    </div>
  </div>

  <div class="cxp-card">
    <div class="cxp-card-head">
      <h3 class="cxp-card-title">Movimientos</h3>
      <div class="cxp-card-sub">Historial de pagos aplicados a esta cuenta.</div>
    </div>

    <div class="cxp-card-body">
      @if($item->movements->count())
        <div class="cxp-movement-list">
          @foreach($item->movements as $m)
            <div class="cxp-movement">
              <div>
                <div class="muted" style="font-size:.78rem; margin-bottom:4px;">Fecha</div>
                <strong>{{ !empty($m->movement_date) ? Carbon::parse($m->movement_date)->format('d/m/Y') : '—' }}</strong>
              </div>

              <div>
                <div class="muted" style="font-size:.78rem; margin-bottom:4px;">Método</div>
                <span class="cxp-method">{{ $m->method ?: '—' }}</span>
              </div>

              <div>
                <div class="muted" style="font-size:.78rem; margin-bottom:4px;">Referencia</div>
                <div>{{ $m->reference ?: '—' }}</div>
              </div>

              <div style="text-align:right;">
                <div class="muted" style="font-size:.78rem; margin-bottom:4px;">Monto</div>
                <strong>${{ number_format((float) $m->amount, 2) }}</strong>
              </div>

              <div style="text-align:right;">
                <form id="delM{{ $m->id }}" method="POST" action="{{ route('accounting.movements.destroy',$m) }}">
                  @csrf
                  @method('DELETE')
                  <button
                    class="cxp-btn danger js-confirm-delete"
                    type="button"
                    style="min-height:38px; padding:0 12px;"
                    data-form-id="delM{{ $m->id }}"
                    data-confirm-title="¿Eliminar este movimiento?"
                    data-confirm-text="Se eliminará este pago aplicado del historial."
                    data-confirm-button="Sí, eliminar"
                  >
                    Eliminar
                  </button>
                </form>
              </div>
            </div>
          @endforeach
        </div>
      @else
        <div class="cxp-empty">Sin movimientos registrados.</div>
      @endif
    </div>
  </div>
</div>

@if($saldo > 0 && !in_array($statusRaw, ['pagado','paid','cancelado','cancelled'], true))
  <div class="pay-modal" id="payModal" aria-hidden="true">
    <div class="pay-modal-backdrop" data-close-pay-modal></div>

    <div class="pay-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="payModalTitle">
      <div class="pay-modal-head">
        <div style="min-width:0;">
          <div class="pay-modal-title-row">
            <div class="pay-modal-icon">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <path d="M8 12l2.5 2.5L16 9"></path>
              </svg>
            </div>
            <h3 class="pay-modal-title" id="payModalTitle">Marcar como Pagado</h3>
          </div>
          <div class="pay-modal-sub">{{ $item->title ?: 'Cuenta por pagar' }}</div>
        </div>

        <button type="button" class="pay-modal-close" data-close-pay-modal aria-label="Cerrar">×</button>
      </div>

      <div class="pay-modal-body">
        <form method="POST" enctype="multipart/form-data" action="{{ route('accounting.movements.store') }}">
          @csrf
          <input type="hidden" name="related_type" value="payable">
          <input type="hidden" name="related_id" value="{{ $item->id }}">
          <input type="hidden" name="currency" value="{{ $currency }}">
          <input type="hidden" name="amount" value="{{ number_format($saldo, 2, '.', '') }}">
          <input type="hidden" name="reference" value="Pago total desde detalle CxP #{{ $item->id }}">
          <input type="hidden" name="notes" value="Pago total generado desde la vista detalle.">

          <div class="pay-form-grid">
            <div class="pay-field">
              <label for="movement_date_modal">Fecha de pago *</label>
              <input
                id="movement_date_modal"
                class="pay-date"
                type="date"
                name="movement_date"
                required
                value="{{ $movementDateValue }}"
              >
            </div>

            <div class="pay-field">
              <label for="method_modal">Método de pago *</label>
              <select id="method_modal" name="method" class="pay-select" required>
                <option value="transferencia" @selected($movementMethodValue === 'transferencia')>Transferencia</option>
                <option value="efectivo" @selected($movementMethodValue === 'efectivo')>Efectivo</option>
                <option value="tarjeta" @selected($movementMethodValue === 'tarjeta')>Tarjeta</option>
                <option value="cheque" @selected($movementMethodValue === 'cheque')>Cheque</option>
                <option value="otro" @selected($movementMethodValue === 'otro')>Otro</option>
              </select>
            </div>

            <div class="pay-field">
              <label for="evidence_modal">Comprobante de pago</label>
              <div class="pay-upload">
                <input id="evidence_modal" type="file" name="evidence" accept=".pdf,.jpg,.jpeg,.png,.webp,.xml,.doc,.docx,.xlsx">
                <div class="pay-upload-inner">
                  <div class="pay-upload-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                      <polyline points="7 10 12 5 17 10"></polyline>
                      <line x1="12" y1="5" x2="12" y2="16"></line>
                    </svg>
                  </div>
                  <div>
                    <div class="pay-upload-title">Subir comprobante</div>
                    <div class="pay-upload-sub">PDF, imagen, XML...</div>
                  </div>
                </div>
                <div class="pay-file-name" id="payFileName"></div>
              </div>
            </div>

            <div class="pay-warning">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
              </svg>
              <span>Si no subes comprobante, quedará marcado como "Pagado sin evidencia"</span>
            </div>
          </div>

          <div class="pay-actions">
            <button type="submit" class="pay-btn pay-btn-confirm">Confirmar Pago</button>
            <button type="button" class="pay-btn pay-btn-cancel" data-close-pay-modal>Cancelar</button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endif

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
  (function () {
    const body = document.body;
    const modal = document.getElementById('payModal');
    const openers = document.querySelectorAll('[data-open-pay-modal]');
    const deleteButtons = document.querySelectorAll('.js-confirm-delete');
    const hasSwal = typeof Swal !== 'undefined';

    function showToast(icon, title) {
      if (!hasSwal) return;

      const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 2600,
        timerProgressBar: true,
        customClass: {
          popup: 'swal-toast-popup',
          title: 'swal-toast-title'
        },
        didOpen: (toast) => {
          toast.addEventListener('mouseenter', Swal.stopTimer);
          toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
      });

      Toast.fire({
        icon,
        title,
        iconColor: icon === 'success' ? '#059669' : (icon === 'error' ? '#dc2626' : '#2563eb')
      });
    }

    function confirmDelete(formId, title, text, confirmButtonText) {
      const form = document.getElementById(formId);
      if (!form) return;

      if (!hasSwal) {
        if (confirm((title || '¿Deseas continuar?') + '\n\n' + (text || 'Esta acción no se puede deshacer.'))) {
          form.submit();
        }
        return;
      }

      Swal.fire({
        title: title || '¿Deseas continuar?',
        text: text || 'Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        buttonsStyling: false,
        reverseButtons: true,
        focusCancel: true,
        confirmButtonText: confirmButtonText || 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        customClass: {
          popup: 'swal-enterprise-popup',
          title: 'swal-enterprise-title',
          htmlContainer: 'swal-enterprise-text',
          actions: 'swal-enterprise-actions',
          confirmButton: 'swal-enterprise-confirm',
          cancelButton: 'swal-enterprise-cancel',
          icon: 'swal-enterprise-icon'
        }
      }).then((result) => {
        if (result.isConfirmed) {
          form.submit();
        }
      });
    }

    deleteButtons.forEach((btn) => {
      btn.addEventListener('click', function () {
        confirmDelete(
          this.dataset.formId,
          this.dataset.confirmTitle,
          this.dataset.confirmText,
          this.dataset.confirmButton
        );
      });
    });

    if (modal) {
      const closers = modal.querySelectorAll('[data-close-pay-modal]');
      const dialog = modal.querySelector('.pay-modal-dialog');
      const evidenceInput = modal.querySelector('#evidence_modal');
      const payFileName = modal.querySelector('#payFileName');

      function openModal() {
        modal.classList.add('show');
        modal.setAttribute('aria-hidden', 'false');
        body.style.overflow = 'hidden';
      }

      function closeModal() {
        modal.classList.remove('show');
        modal.setAttribute('aria-hidden', 'true');
        body.style.overflow = '';
      }

      openers.forEach(btn => btn.addEventListener('click', openModal));
      closers.forEach(btn => btn.addEventListener('click', closeModal));

      modal.addEventListener('click', function (e) {
        if (!dialog.contains(e.target)) closeModal();
      });

      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.classList.contains('show')) {
          closeModal();
        }
      });

      if (evidenceInput && payFileName) {
        evidenceInput.addEventListener('change', function () {
          const file = this.files && this.files[0] ? this.files[0] : null;
          if (file) {
            payFileName.textContent = 'Archivo seleccionado: ' + file.name;
            payFileName.classList.add('show');
          } else {
            payFileName.textContent = '';
            payFileName.classList.remove('show');
          }
        });
      }

      @if($errors->any())
        openModal();
      @endif
    }

    @if(session('success'))
      showToast('success', @json(session('success')));
    @endif

    @if(session('error'))
      showToast('error', @json(session('error')));
    @endif
  })();
</script>
@endsection