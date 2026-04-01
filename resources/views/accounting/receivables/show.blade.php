@extends('layouts.app')
@section('title','Detalle CxC')

@section('content')
@include('accounting.partials.ui')

@php
    use Illuminate\Support\Carbon;

    $statusRaw = mb_strtolower((string) ($item->status ?? 'pendiente'));
    $today = now()->startOfDay();
    $dueDate = !empty($item->due_date) ? Carbon::parse($item->due_date) : null;
    $issueDate = !empty($item->issue_date) ? Carbon::parse($item->issue_date) : null;
    $paidDate = !empty($item->paid_at)
        ? Carbon::parse($item->paid_at)
        : (!empty($item->payment_date) ? Carbon::parse($item->payment_date) : null);

    $isOverdue = $dueDate
        && $dueDate->copy()->startOfDay()->lt($today)
        && !in_array($statusRaw, ['cobrado', 'cancelado'], true);

    $displayStatus = ($isOverdue && $statusRaw === 'pendiente') ? 'vencido' : $statusRaw;

    $statusConfig = [
        'cobrado'   => ['label' => 'Cobrado',   'class' => 'is-paid'],
        'pendiente' => ['label' => 'Pendiente', 'class' => 'is-pending'],
        'parcial'   => ['label' => 'Parcial',   'class' => 'is-partial'],
        'vencido'   => ['label' => 'Vencido',   'class' => 'is-overdue'],
        'cancelado' => ['label' => 'Cancelado', 'class' => 'is-cancelled'],
    ];

    $cfg = $statusConfig[$displayStatus] ?? $statusConfig['pendiente'];

    $amount = (float) ($item->amount ?? 0);
    $amountPaid = (float) ($item->amount_paid ?? 0);
    $remaining = max($amount - $amountPaid, 0);
    $pctPaid = $amount > 0 ? min(100, max(0, ($amountPaid / $amount) * 100)) : 0;

    $priority = mb_strtolower((string) ($item->priority ?? 'media'));
    $currency = $item->currency ?: 'MXN';

    $documents = is_array($item->documents ?? null) ? $item->documents : [];
    $documentNames = is_array($item->document_names ?? null) ? $item->document_names : [];

    $movementDateValue = old('movement_date', now()->toDateString());
    $movementAmountValue = old('amount', number_format($remaining, 2, '.', ''));
    $movementMethodValue = old('method', 'transferencia');
    $movementReferenceValue = old('reference', $item->folio ?: '');
    $movementNotesValue = old('notes', '');

    $daysOverdue = ($isOverdue && $dueDate) ? $dueDate->copy()->startOfDay()->diffInDays($today) : 0;
@endphp

<style>
  .rcv-page{
    max-width:1020px;
    margin:0 auto;
    padding:6px 0 40px;
  }

  .rcv-back{
    display:inline-flex;
    align-items:center;
    gap:8px;
    margin-bottom:14px;
    color:#64748b;
    text-decoration:none;
    font-size:.95rem;
    font-weight:600;
    transition:all .2s ease;
  }

  .rcv-back:hover{
    color:#0f172a;
    transform:translateX(-2px);
  }

  .rcv-card{
    background:#fff;
    border:1px solid #e7edf5;
    border-radius:24px;
    overflow:hidden;
    box-shadow:0 14px 34px rgba(15,23,42,.06);
  }

  .rcv-head{
    padding:24px 26px 20px;
    border-bottom:1px solid #edf2f7;
  }

  .rcv-head-top{
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:18px;
  }

  .rcv-head-left{
    min-width:0;
    flex:1 1 auto;
  }

  .rcv-badges{
    display:flex;
    align-items:center;
    gap:8px;
    flex-wrap:wrap;
    margin-bottom:10px;
  }

  .rcv-badge{
    display:inline-flex;
    align-items:center;
    border-radius:999px;
    border:1px solid transparent;
    padding:7px 12px;
    font-size:.84rem;
    font-weight:800;
    line-height:1;
  }

  .rcv-badge.is-paid{
    background:#ecfdf5;
    border-color:#a7f3d0;
    color:#047857;
  }

  .rcv-badge.is-pending{
    background:#fff7ed;
    border-color:#fed7aa;
    color:#c2410c;
  }

  .rcv-badge.is-partial{
    background:#eff6ff;
    border-color:#bfdbfe;
    color:#1d4ed8;
  }

  .rcv-badge.is-overdue{
    background:#fff1f2;
    border-color:#fecdd3;
    color:#be123c;
  }

  .rcv-badge.is-cancelled{
    background:#f1f5f9;
    border-color:#e2e8f0;
    color:#64748b;
  }

  .rcv-badge.folio{
    background:#f8fafc;
    border-color:#e2e8f0;
    color:#64748b;
    font-family:ui-monospace, SFMono-Regular, Menlo, monospace;
  }

  .rcv-badge.priority{
    background:#fee2e2;
    border-color:#fecaca;
    color:#b91c1c;
    font-size:.78rem;
  }

  .rcv-title{
    margin:0;
    font-size:2rem;
    line-height:1.05;
    font-weight:900;
    color:#0f172a;
    letter-spacing:-.03em;
    display:flex;
    align-items:center;
    gap:10px;
  }

  .rcv-title svg{
    width:22px;
    height:22px;
    color:#64748b;
    flex:0 0 auto;
  }

  .rcv-desc{
    margin:8px 0 0;
    color:#64748b;
    font-size:.98rem;
  }

  .rcv-amount{
    text-align:right;
    flex:0 0 auto;
    min-width:220px;
  }

  .rcv-amount strong{
    display:block;
    font-size:3rem;
    line-height:1;
    font-weight:900;
    letter-spacing:-.05em;
    color:#0f172a;
  }

  .rcv-amount span{
    display:block;
    margin-top:6px;
    color:#64748b;
    font-size:.92rem;
  }

  .rcv-progress-wrap{
    margin-top:18px;
  }

  .rcv-progress-meta{
    display:flex;
    justify-content:space-between;
    gap:10px;
    color:#64748b;
    font-size:.78rem;
    font-weight:700;
    margin-bottom:7px;
  }

  .rcv-progress{
    height:9px;
    background:#edf2f7;
    border-radius:999px;
    overflow:hidden;
  }

  .rcv-progress-bar{
    height:100%;
    background:linear-gradient(90deg,#10b981,#059669);
    border-radius:999px;
    transition:width .25s ease;
  }

  .rcv-grid{
    padding:22px 26px 10px;
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:14px;
  }

  .rcv-info{
    display:flex;
    align-items:center;
    gap:12px;
    padding:15px 16px;
    border-radius:18px;
    background:#f8fafc;
    border:1px solid #edf2f7;
  }

  .rcv-info.success{
    background:#ecfdf5;
    border-color:#d1fae5;
  }

  .rcv-info-icon{
    width:42px;
    height:42px;
    border-radius:14px;
    display:grid;
    place-items:center;
    background:#eff6ff;
    color:#2563eb;
    flex:0 0 auto;
  }

  .rcv-info.success .rcv-info-icon{
    background:#d1fae5;
    color:#059669;
  }

  .rcv-info-icon svg{
    width:22px;
    height:22px;
  }

  .rcv-info small{
    display:block;
    color:#64748b;
    font-size:.78rem;
    margin-bottom:3px;
  }

  .rcv-info strong{
    display:block;
    color:#0f172a;
    font-size:1rem;
    font-weight:800;
  }

  .rcv-info .danger{
    color:#e11d48;
    font-size:.76rem;
    font-weight:800;
    margin-top:3px;
  }

  .rcv-body{
    padding:0 26px 18px;
    display:grid;
    gap:14px;
  }

  .rcv-section-title{
    margin:0 0 8px;
    color:#0f172a;
    font-size:.95rem;
    font-weight:800;
  }

  .rcv-note{
    background:#f8fafc;
    border:1px solid #edf2f7;
    border-radius:16px;
    padding:14px 16px;
    color:#64748b;
    font-size:.94rem;
  }

  .rcv-alert{
    display:flex;
    align-items:center;
    gap:12px;
    border-radius:16px;
    padding:14px 16px;
    border:1px solid;
  }

  .rcv-alert.success{
    background:#ecfdf5;
    border-color:#d1fae5;
    color:#047857;
  }

  .rcv-alert.warning{
    background:#fffbeb;
    border-color:#fde68a;
    color:#b45309;
  }

  .rcv-alert svg{
    width:20px;
    height:20px;
    flex:0 0 auto;
  }

  .rcv-alert a{
    margin-left:auto;
    color:inherit;
    font-weight:800;
    text-decoration:none;
  }

  .rcv-doc-list{
    display:grid;
    gap:8px;
  }

  .rcv-doc-item{
    display:flex;
    align-items:center;
    gap:12px;
    padding:12px 13px;
    background:#f8fafc;
    border:1px solid #edf2f7;
    border-radius:14px;
  }

  .rcv-doc-item svg{
    width:18px;
    height:18px;
    color:#64748b;
    flex:0 0 auto;
  }

  .rcv-doc-item span{
    flex:1 1 auto;
    min-width:0;
    font-size:.9rem;
    color:#0f172a;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
  }

  .rcv-doc-item a{
    color:#2563eb;
    font-size:.82rem;
    font-weight:800;
    text-decoration:none;
  }

  .rcv-actions{
    padding:22px 26px;
    border-top:1px solid #edf2f7;
    display:flex;
    flex-wrap:wrap;
    gap:12px;
  }

  .rcv-btn{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:9px;
    min-height:44px;
    padding:0 18px;
    border-radius:14px;
    border:1px solid #dbe3ee;
    background:#fff;
    color:#0f172a;
    text-decoration:none;
    font-size:.95rem;
    font-weight:700;
    cursor:pointer;
    transition:all .2s ease;
    box-shadow:0 3px 10px rgba(15,23,42,.03);
  }

  .rcv-btn:hover{
    transform:translateY(-1px);
    box-shadow:0 10px 20px rgba(15,23,42,.06);
  }

  .rcv-btn svg{
    width:17px;
    height:17px;
  }

  .rcv-btn.primary{
    background:linear-gradient(180deg,#10b981,#059669);
    border-color:#059669;
    color:#fff;
  }

  .rcv-btn.danger{
    color:#dc2626;
    background:#fff5f5;
    border-color:#fecaca;
  }

  .rcv-panel{
    margin-top:16px;
    background:#fff;
    border:1px solid #e7edf5;
    border-radius:20px;
    box-shadow:0 12px 30px rgba(15,23,42,.05);
    overflow:hidden;
  }

  .rcv-panel-head{
    padding:18px 20px 14px;
    border-bottom:1px solid #edf2f7;
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:12px;
    flex-wrap:wrap;
  }

  .rcv-panel-head h3{
    margin:0;
    color:#0f172a;
    font-size:1rem;
    font-weight:800;
  }

  .rcv-panel-head p{
    margin:4px 0 0;
    color:#64748b;
    font-size:.86rem;
  }

  .rcv-panel-body{
    padding:16px 20px 20px;
  }

  .rcv-move-list{
    display:grid;
    gap:10px;
  }

  .rcv-move{
    display:grid;
    grid-template-columns:150px 130px 1fr 130px;
    gap:12px;
    align-items:center;
    background:#f8fafc;
    border:1px solid #edf2f7;
    border-radius:15px;
    padding:13px 14px;
  }

  .rcv-move small{
    display:block;
    color:#64748b;
    font-size:.74rem;
    margin-bottom:4px;
  }

  .rcv-move strong{
    color:#0f172a;
    font-weight:800;
  }

  .rcv-move-method{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-height:30px;
    padding:0 10px;
    border-radius:999px;
    background:#eff6ff;
    color:#1d4ed8;
    font-size:.78rem;
    font-weight:800;
    text-transform:capitalize;
    width:max-content;
  }

  .rcv-empty{
    padding:20px;
    border:1px dashed #dbe3ee;
    background:#fbfdff;
    border-radius:15px;
    color:#64748b;
    text-align:center;
    font-size:.92rem;
    font-weight:700;
  }

  /* BITÁCORA */
  .bita-wrap{
    margin-top:16px;
    background:#fff;
    border:1px solid #e7edf5;
    border-radius:20px;
    box-shadow:0 12px 30px rgba(15,23,42,.05);
    overflow:hidden;
  }

  .bita-head{
    padding:18px 20px 12px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    flex-wrap:wrap;
  }

  .bita-title{
    margin:0;
    color:#0f172a;
    font-size:1rem;
    font-weight:900;
  }

  .bita-sub{
    margin:4px 0 0;
    color:#64748b;
    font-size:.84rem;
  }

  .bita-toggle{
    display:inline-flex;
    align-items:center;
    gap:10px;
    min-height:42px;
    padding:0 16px;
    border-radius:14px;
    border:1px solid #dbe3ee;
    background:#fff;
    color:#0f172a;
    font-size:.94rem;
    font-weight:700;
    cursor:pointer;
    box-shadow:0 3px 10px rgba(15,23,42,.03);
    transition:all .2s ease;
  }

  .bita-toggle:hover{
    transform:translateY(-1px);
    box-shadow:0 10px 18px rgba(15,23,42,.05);
  }

  .bita-toggle svg{
    width:16px;
    height:16px;
  }

  .bita-form-wrap{
    padding:0 20px 16px;
    display:none;
  }

  .bita-form-wrap.show{
    display:block;
  }

  .bita-form{
    border:1px solid #e7edf5;
    background:#fafbfd;
    border-radius:18px;
    padding:18px;
  }

  .bita-label{
    display:block;
    margin-bottom:8px;
    color:#0f172a;
    font-size:.9rem;
    font-weight:800;
  }

  .bita-type-list{
    display:flex;
    flex-wrap:wrap;
    gap:10px;
    margin-bottom:16px;
  }

  .bita-type{
    display:inline-flex;
    align-items:center;
    gap:8px;
    min-height:38px;
    padding:0 14px;
    border-radius:999px;
    border:1px solid #dbe3ee;
    background:#fff;
    color:#64748b;
    font-size:.9rem;
    font-weight:700;
    cursor:pointer;
    transition:all .18s ease;
  }

  .bita-type svg{
    width:16px;
    height:16px;
  }

  .bita-type:hover{
    border-color:#bfdbfe;
    color:#2563eb;
    background:#f8fbff;
  }

  .bita-type.active{
    background:#eff6ff;
    border-color:#3b82f6;
    color:#2563eb;
    box-shadow:0 0 0 3px rgba(59,130,246,.08);
  }

  .bita-grid{
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:14px;
  }

  .bita-col-full{
    grid-column:1 / -1;
  }

  .bita-input,
  .bita-select,
  .bita-textarea{
    width:100%;
    border:1.5px solid #dbe3ee;
    border-radius:14px;
    background:#fff;
    color:#0f172a;
    font-size:.95rem;
    outline:none;
    transition:all .2s ease;
  }

  .bita-input,
  .bita-select{
    min-height:44px;
    padding:0 14px;
  }

  .bita-textarea{
    min-height:74px;
    padding:14px;
    resize:vertical;
  }

  .bita-input:focus,
  .bita-select:focus,
  .bita-textarea:focus{
    border-color:#3b82f6;
    box-shadow:0 0 0 4px rgba(59,130,246,.08);
  }

  .bita-actions{
    margin-top:14px;
    display:flex;
    align-items:center;
    gap:12px;
    flex-wrap:wrap;
  }

  .bita-save{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-height:40px;
    padding:0 16px;
    border:none;
    border-radius:13px;
    background:#8ba9e8;
    color:#fff;
    font-size:.92rem;
    font-weight:800;
    cursor:pointer;
  }

  .bita-cancel{
    border:none;
    background:transparent;
    color:#0f172a;
    font-size:.92rem;
    font-weight:700;
    cursor:pointer;
  }

  .bita-list{
    padding:0 20px 20px;
    display:grid;
    gap:12px;
  }

  .bita-item{
    background:#eef4ff;
    border:1px solid #dbe7ff;
    border-radius:18px;
    padding:14px 16px;
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:14px;
  }

  .bita-item-left{
    display:flex;
    gap:12px;
    min-width:0;
    flex:1 1 auto;
  }

  .bita-item-icon{
    width:34px;
    height:34px;
    border-radius:12px;
    background:#eff6ff;
    color:#2563eb;
    display:grid;
    place-items:center;
    flex:0 0 auto;
  }

  .bita-item-icon svg{
    width:18px;
    height:18px;
  }

  .bita-item-title{
    color:#2563eb;
    font-size:.98rem;
    font-weight:900;
    margin:0 0 2px;
  }

  .bita-item-note{
    color:#2563eb;
    opacity:.85;
    font-size:.92rem;
    margin:0;
    word-break:break-word;
  }

  .bita-item-meta{
    margin-top:8px;
    display:flex;
    flex-wrap:wrap;
    gap:10px;
    color:#64748b;
    font-size:.76rem;
    font-weight:700;
  }

  .bita-item-time{
    color:#2563eb;
    font-size:.84rem;
    font-weight:800;
    white-space:nowrap;
  }

  /* MODAL COBRO */
  .collect-modal{
    position:fixed;
    inset:0;
    z-index:1200;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:18px;
    opacity:0;
    visibility:hidden;
    pointer-events:none;
    transition:opacity .22s ease, visibility .22s ease;
  }

  .collect-modal.show{
    opacity:1;
    visibility:visible;
    pointer-events:auto;
  }

  .collect-backdrop{
    position:absolute;
    inset:0;
    background:rgba(15,23,42,.58);
    backdrop-filter:blur(4px);
  }

  .collect-dialog{
    position:relative;
    width:min(100%, 470px);
    max-height:min(84vh, 720px);
    background:#fff;
    border:1px solid #e5e7eb;
    border-radius:22px;
    box-shadow:0 28px 70px rgba(15,23,42,.22);
    overflow:hidden;
    transform:translateY(10px) scale(.985);
    transition:transform .24s cubic-bezier(.4,0,.2,1);
  }

  .collect-modal.show .collect-dialog{
    transform:translateY(0) scale(1);
  }

  .collect-head{
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:12px;
    padding:16px 18px 8px;
    border-bottom:1px solid #edf2f7;
  }

  .collect-title{
    display:flex;
    align-items:center;
    gap:10px;
    color:#0f172a;
    font-size:.98rem;
    font-weight:800;
  }

  .collect-title svg{
    width:20px;
    height:20px;
    color:#059669;
  }

  .collect-sub{
    margin:6px 0 0 30px;
    color:#64748b;
    font-size:.88rem;
    line-height:1.35;
  }

  .collect-close{
    width:30px;
    height:30px;
    border:none;
    background:transparent;
    border-radius:10px;
    color:#64748b;
    cursor:pointer;
    font-size:1.25rem;
    flex:0 0 auto;
  }

  .collect-close:hover{
    background:#f3f4f6;
    color:#111827;
  }

  .collect-body{
    padding:12px 18px 18px;
    overflow:auto;
    max-height:calc(min(84vh, 720px) - 72px);
    scrollbar-width:thin;
  }

  .collect-balance{
    padding:13px 14px;
    border-radius:14px;
    background:#f8fafc;
    border:1px solid #edf2f7;
    color:#64748b;
    font-size:.9rem;
    margin-bottom:14px;
  }

  .collect-balance strong{
    color:#0f172a;
    font-weight:900;
  }

  .collect-grid{
    display:grid;
    gap:14px;
  }

  .collect-field label{
    display:block;
    margin-bottom:8px;
    color:#111827;
    font-size:.88rem;
    font-weight:700;
  }

  .collect-input,
  .collect-select{
    width:100%;
    min-height:42px;
    border:1.5px solid #dbe3ee;
    border-radius:12px;
    background:#fff;
    padding:0 14px;
    outline:none;
    font-size:.92rem;
    color:#0f172a;
    transition:all .2s ease;
  }

  .collect-input:focus,
  .collect-select:focus{
    border-color:#3b82f6;
    box-shadow:0 0 0 4px rgba(59,130,246,.10);
  }

  .collect-upload{
    position:relative;
    border:2px dashed #e5e7eb;
    border-radius:14px;
    background:#fafafa;
    padding:14px 12px;
    transition:all .2s ease;
  }

  .collect-upload:hover{
    border-color:#d1d5db;
    background:#f9fafb;
  }

  .collect-upload input[type=file]{
    position:absolute;
    inset:0;
    opacity:0;
    cursor:pointer;
  }

  .collect-upload-inner{
    display:flex;
    align-items:center;
    gap:10px;
  }

  .collect-upload-icon{
    width:34px;
    height:34px;
    border-radius:10px;
    display:grid;
    place-items:center;
    background:#f3f4f6;
    color:#64748b;
    flex:0 0 auto;
  }

  .collect-upload-title{
    color:#0f172a;
    font-size:.88rem;
    font-weight:700;
  }

  .collect-upload-sub{
    color:#64748b;
    font-size:.8rem;
    margin-top:1px;
  }

  .collect-file-name{
    display:none;
    margin-top:8px;
    color:#059669;
    font-size:.82rem;
    font-weight:700;
  }

  .collect-file-name.show{
    display:block;
  }

  .collect-actions{
    display:flex;
    gap:8px;
    margin-top:14px;
    position:sticky;
    bottom:-18px;
    background:#fff;
    padding-top:10px;
  }

  .collect-submit,
  .collect-cancel{
    min-height:40px;
    border-radius:12px;
    border:1px solid transparent;
    font-size:.9rem;
    font-weight:600;
    cursor:pointer;
  }

  .collect-submit{
    flex:1 1 auto;
    background:#059669;
    color:#fff;
  }

  .collect-submit:hover{
    background:#047857;
  }

  .collect-cancel{
    flex:0 0 110px;
    background:#f3f4f6;
    color:#0f172a;
    border-color:#e5e7eb;
  }

  .swal-enterprise-popup{
    width:420px !important;
    border-radius:22px !important;
    border:1px solid #e8edf3 !important;
    background:#fff !important;
    box-shadow:0 24px 60px rgba(15,23,42,.18) !important;
    padding:1.15rem 1.15rem 1rem !important;
  }

  .swal-enterprise-title{
    color:#0f172a !important;
    font-size:1.08rem !important;
    font-weight:700 !important;
    padding:.1rem 1rem 0 !important;
  }

  .swal-enterprise-text{
    color:#64748b !important;
    font-size:.92rem !important;
    line-height:1.45 !important;
    padding:.2rem 1rem 0 !important;
    margin:0 !important;
  }

  .swal-enterprise-actions{
    gap:.6rem !important;
    padding:1rem .4rem .3rem !important;
    margin:0 !important;
  }

  .swal-enterprise-confirm,
  .swal-enterprise-cancel{
    min-width:122px !important;
    height:42px !important;
    border-radius:12px !important;
    font-size:.92rem !important;
    font-weight:500 !important;
    padding:0 16px !important;
    margin:0 !important;
    box-shadow:none !important;
  }

  .swal-enterprise-confirm{
    background:linear-gradient(180deg,#ef4444 0%, #dc2626 100%) !important;
    color:#fff !important;
    border:1px solid #dc2626 !important;
  }

  .swal-enterprise-cancel{
    background:#f8fafc !important;
    color:#0f172a !important;
    border:1px solid #dbe3ee !important;
  }

  .swal-enterprise-icon.swal2-warning{
    border-color:rgba(217,119,6,.24) !important;
    color:#d97706 !important;
  }

  .swal-toast-popup{
    width:360px !important;
    border-radius:16px !important;
    border:1px solid #e7edf3 !important;
    background:rgba(255,255,255,.98) !important;
    backdrop-filter:blur(10px) !important;
    box-shadow:0 18px 42px rgba(15,23,42,.14) !important;
    padding:12px 14px !important;
  }

  .swal-toast-title{
    color:#0f172a !important;
    font-size:.92rem !important;
    font-weight:600 !important;
    margin:0 !important;
  }

  .swal2-timer-progress-bar{
    background:rgba(15,23,42,.12) !important;
  }

  @media (max-width: 860px){
    .rcv-head-top{
      display:flex;
      flex-direction:column;
    }

    .rcv-amount{
      min-width:0;
      text-align:left;
    }

    .rcv-grid,
    .bita-grid,
    .rcv-move{
      grid-template-columns:1fr;
    }
  }

  @media (max-width: 720px){
    .rcv-page{
      padding-bottom:28px;
    }

    .rcv-head,
    .rcv-grid,
    .rcv-body,
    .rcv-actions,
    .rcv-panel-head,
    .rcv-panel-body,
    .bita-head,
    .bita-form-wrap,
    .bita-list{
      padding-left:16px;
      padding-right:16px;
    }

    .rcv-actions{
      flex-direction:column;
      align-items:stretch;
    }

    .rcv-btn{
      width:100%;
    }

    .rcv-title{
      font-size:1.55rem;
    }

    .rcv-amount strong{
      font-size:2.3rem;
    }

    .collect-modal{
      padding:10px;
    }

    .collect-dialog{
      width:min(100%, 420px);
      max-height:86vh;
    }

    .collect-head,
    .collect-body{
      padding-left:14px;
      padding-right:14px;
    }

    .collect-actions{
      flex-direction:column-reverse;
    }

    .collect-cancel{
      flex-basis:auto;
    }

    .swal-enterprise-popup,
    .swal-toast-popup{
      width:calc(100vw - 24px) !important;
    }
  }
</style>

<div class="rcv-page">
  <a href="{{ route('accounting.receivables.index') }}" class="rcv-back">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
      <path d="m15 18-6-6 6-6"></path>
    </svg>
    <span>Volver a cuentas por cobrar</span>
  </a>

  @if ($errors->any())
    <div style="margin-bottom:14px;border:1px solid rgba(239,68,68,.18);background:rgba(239,68,68,.07);color:#b91c1c;border-radius:14px;padding:12px 14px;font-size:.92rem;">
      <strong style="display:block;margin-bottom:6px;">Por favor, corrige los siguientes errores:</strong>
      <ul style="margin:0;padding-left:18px;">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="rcv-card">
    <div class="rcv-head">
      <div class="rcv-head-top">
        <div class="rcv-head-left">
          <div class="rcv-badges">
            <span class="rcv-badge {{ $cfg['class'] }}">{{ $cfg['label'] }}</span>

            @if(!empty($item->folio))
              <span class="rcv-badge folio">#{{ $item->folio }}</span>
            @endif

            @if($priority === 'alta')
              <span class="rcv-badge priority">Prioridad Alta</span>
            @endif
          </div>

          <h1 class="rcv-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
              <path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"></path>
              <circle cx="9.5" cy="7" r="3.2"></circle>
              <path d="M20 21v-2a4 4 0 0 0-3-3.87"></path>
              <path d="M16 4.13a3.2 3.2 0 0 1 0 5.74"></path>
            </svg>
            {{ $item->client_name ?: ($item->title ?: 'Cuenta por cobrar') }}
          </h1>

          @if(!empty($item->description))
            <p class="rcv-desc">{{ $item->description }}</p>
          @endif
        </div>

        <div class="rcv-amount">
          <strong>${{ number_format($remaining, 2) }}</strong>
          <span>de ${{ number_format($amount, 2) }} {{ $currency }}</span>
        </div>
      </div>

      @if($amountPaid > 0)
        <div class="rcv-progress-wrap">
          <div class="rcv-progress-meta">
            <span>Cobrado: ${{ number_format($amountPaid, 2) }}</span>
            <span>{{ number_format($pctPaid, 0) }}%</span>
          </div>
          <div class="rcv-progress">
            <div class="rcv-progress-bar" style="width: {{ $pctPaid }}%"></div>
          </div>
        </div>
      @endif
    </div>

    <div class="rcv-grid">
      <div class="rcv-info">
        <div class="rcv-info-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="4" width="18" height="17" rx="2"></rect>
            <path d="M16 2v4"></path>
            <path d="M8 2v4"></path>
            <path d="M3 10h18"></path>
          </svg>
        </div>
        <div>
          <small>Vencimiento</small>
          <strong>{{ $dueDate ? $dueDate->translatedFormat('d M Y') : '—' }}</strong>
          @if($isOverdue)
            <div class="danger">{{ $daysOverdue }} {{ $daysOverdue === 1 ? 'día vencido' : 'días vencido' }}</div>
          @endif
        </div>
      </div>

      @if($issueDate)
        <div class="rcv-info">
          <div class="rcv-info-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
              <path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"></path>
              <path d="M14 3v5h5"></path>
              <path d="M9 13h6"></path>
              <path d="M9 17h6"></path>
            </svg>
          </div>
          <div>
            <small>Fecha de emisión</small>
            <strong>{{ $issueDate->translatedFormat('d M Y') }}</strong>
          </div>
        </div>
      @endif

      @if($paidDate)
        <div class="rcv-info success">
          <div class="rcv-info-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
              <path d="M12 1v22"></path>
              <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14.5a3.5 3.5 0 0 1 0 7H6"></path>
            </svg>
          </div>
          <div>
            <small>Fecha de cobro</small>
            <strong>{{ $paidDate->translatedFormat('d M Y') }}</strong>
          </div>
        </div>
      @endif

      <div class="rcv-info">
        <div class="rcv-info-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 1v22"></path>
            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14.5a3.5 3.5 0 0 1 0 7H6"></path>
          </svg>
        </div>
        <div>
          <small>Saldo pendiente</small>
          <strong>${{ number_format($remaining, 2) }}</strong>
        </div>
      </div>
    </div>

    <div class="rcv-body">
      @if(!empty($item->evidence_url))
        <div class="rcv-alert success">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <path d="M8 12l2.5 2.5L16 9"></path>
          </svg>
          <span>Comprobante de cobro adjunto</span>
          <a href="{{ $item->evidence_url }}" target="_blank">Ver</a>
        </div>
      @elseif(in_array($statusRaw, ['cobrado'], true))
        <div class="rcv-alert warning">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <path d="M12 8v4"></path>
            <path d="M12 16h.01"></path>
          </svg>
          <span>Cobrado sin comprobante</span>
        </div>
      @endif

      @if(count($documents))
        <div>
          <h3 class="rcv-section-title">Documentos</h3>
          <div class="rcv-doc-list">
            @foreach($documents as $i => $doc)
              <div class="rcv-doc-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"></path>
                  <path d="M14 3v5h5"></path>
                </svg>
                <span>{{ $documentNames[$i] ?? ('Documento '.($i + 1)) }}</span>
                <a href="{{ $doc }}" target="_blank">Ver</a>
              </div>
            @endforeach
          </div>
        </div>
      @endif

      @if(!empty($item->notes))
        <div>
          <h3 class="rcv-section-title">Notas</h3>
          <div class="rcv-note">{{ $item->notes }}</div>
        </div>
      @endif
    </div>

    <div class="rcv-actions">
      @if(!in_array($statusRaw, ['cobrado','cancelado'], true))
        <button type="button" class="rcv-btn primary" data-open-collect>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 1v22"></path>
            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14.5a3.5 3.5 0 0 1 0 7H6"></path>
          </svg>
          <span>Registrar Cobro</span>
        </button>
      @endif

      <a href="{{ route('accounting.receivables.edit', $item) }}" class="rcv-btn">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 20h9"></path>
          <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"></path>
        </svg>
        <span>Editar</span>
      </a>

      <form id="deleteReceivableForm" method="POST" action="{{ route('accounting.receivables.destroy', $item) }}">
        @csrf
        @method('DELETE')
        <button type="button" class="rcv-btn danger" id="deleteReceivableBtn">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="3 6 5 6 21 6"></polyline>
            <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
            <path d="M10 11v6"></path>
            <path d="M14 11v6"></path>
            <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path>
          </svg>
          <span>Eliminar</span>
        </button>
      </form>
    </div>
  </div>

  <div class="rcv-panel">
    <div class="rcv-panel-head">
      <div>
        <h3>Historial de cobros</h3>
        <p>Movimientos aplicados a esta cuenta por cobrar.</p>
      </div>
    </div>

    <div class="rcv-panel-body">
      @if(($item->movements?->count() ?? 0) > 0)
        <div class="rcv-move-list">
          @foreach($item->movements as $m)
            <div class="rcv-move">
              <div>
                <small>Fecha</small>
                <strong>{{ !empty($m->movement_date) ? Carbon::parse($m->movement_date)->format('d/m/Y') : '—' }}</strong>
              </div>
              <div>
                <small>Método</small>
                <span class="rcv-move-method">{{ $m->method ?: '—' }}</span>
              </div>
              <div>
                <small>Referencia</small>
                <strong>{{ $m->reference ?: '—' }}</strong>
              </div>
              <div style="text-align:right;">
                <small>Monto</small>
                <strong>${{ number_format((float) $m->amount, 2) }}</strong>
              </div>
            </div>
          @endforeach
        </div>
      @else
        <div class="rcv-empty">Sin cobros registrados todavía.</div>
      @endif
    </div>
  </div>

  <div class="bita-wrap">
    <div class="bita-head">
      <div>
        <h3 class="bita-title">Bitácora de Cobranza</h3>
        <div class="bita-sub">Seguimiento visual de gestiones y acuerdos de cobranza.</div>
      </div>

      <button type="button" class="bita-toggle" id="openBitaForm">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 5v14"></path>
          <path d="M5 12h14"></path>
        </svg>
        <span>Registrar acción</span>
      </button>
    </div>

    <div class="bita-form-wrap" id="bitaFormWrap">
      <div class="bita-form">
        <form id="bitaForm" autocomplete="off">
          <input type="hidden" id="bitaActionType" value="llamada">

          <label class="bita-label">Tipo de acción</label>
          <div class="bita-type-list">
            <button type="button" class="bita-type active" data-type="llamada">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.08 4.18 2 2 0 0 1 4.06 2h3a2 2 0 0 1 2 1.72c.12.9.33 1.78.62 2.62a2 2 0 0 1-.45 2.11L8 9.91a16 16 0 0 0 6.09 6.09l1.46-1.23a2 2 0 0 1 2.11-.45c.84.29 1.72.5 2.62.62A2 2 0 0 1 22 16.92z"></path>
              </svg>
              <span>Llamada</span>
            </button>

            <button type="button" class="bita-type" data-type="email">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 4h16v16H4z"></path>
                <path d="m22 6-10 7L2 6"></path>
              </svg>
              <span>Email</span>
            </button>

            <button type="button" class="bita-type" data-type="whatsapp">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 11.5a8.5 8.5 0 0 1-12.4 7.5L3 20l1.2-5.3A8.5 8.5 0 1 1 21 11.5z"></path>
              </svg>
              <span>WhatsApp</span>
            </button>

            <button type="button" class="bita-type" data-type="visita">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 1 1 18 0z"></path>
                <circle cx="12" cy="10" r="3"></circle>
              </svg>
              <span>Visita</span>
            </button>

            <button type="button" class="bita-type" data-type="promesa_pago">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 1v22"></path>
                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14.5a3.5 3.5 0 0 1 0 7H6"></path>
              </svg>
              <span>Promesa de Pago</span>
            </button>

            <button type="button" class="bita-type" data-type="pago_recibido">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 1v22"></path>
                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14.5a3.5 3.5 0 0 1 0 7H6"></path>
              </svg>
              <span>Pago Recibido</span>
            </button>

            <button type="button" class="bita-type" data-type="nota_interna">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"></path>
                <path d="M14 3v5h5"></path>
                <path d="M9 13h6"></path>
                <path d="M9 17h6"></path>
              </svg>
              <span>Nota Interna</span>
            </button>

            <button type="button" class="bita-type" data-type="escalamiento">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 9v4"></path>
                <path d="M12 17h.01"></path>
                <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
              </svg>
              <span>Escalamiento</span>
            </button>
          </div>

          <div class="bita-grid">
            <div>
              <label class="bita-label" for="bitaPerson">Persona contactada</label>
              <input class="bita-input" id="bitaPerson" type="text" placeholder="Nombre...">
            </div>

            <div>
              <label class="bita-label" for="bitaResult">Resultado</label>
              <select class="bita-select" id="bitaResult">
                <option value="Exitoso">Exitoso</option>
                <option value="Sin respuesta">Sin respuesta</option>
                <option value="Rechazado">Rechazado</option>
                <option value="Pendiente">Pendiente</option>
                <option value="Seguimiento">Seguimiento</option>
              </select>
            </div>

            <div>
              <label class="bita-label" for="bitaNextAction">Próxima acción</label>
              <input class="bita-input" id="bitaNextAction" type="date">
            </div>

            <div class="bita-col-full">
              <label class="bita-label" for="bitaNotes">Descripción / Notas *</label>
              <textarea class="bita-textarea" id="bitaNotes" placeholder="Describe lo que ocurrió..." required></textarea>
            </div>
          </div>

          <div class="bita-actions">
            <button type="submit" class="bita-save">Guardar</button>
            <button type="button" class="bita-cancel" id="cancelBitaForm">Cancelar</button>
          </div>
        </form>
      </div>
    </div>

    <div class="bita-list" id="bitaList">
      <div class="rcv-empty">Sin registros de bitácora todavía.</div>
    </div>
  </div>
</div>

@if(!in_array($statusRaw, ['cobrado','cancelado'], true))
  <div class="collect-modal" id="collectModal" aria-hidden="true">
    <div class="collect-backdrop" data-close-collect></div>

    <div class="collect-dialog" role="dialog" aria-modal="true" aria-labelledby="collectTitle">
      <div class="collect-head">
        <div>
          <div class="collect-title" id="collectTitle">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"></circle>
              <path d="M8 12l2.5 2.5L16 9"></path>
            </svg>
            <span>Registrar Cobro</span>
          </div>
          <div class="collect-sub">{{ $item->client_name ?: ($item->title ?: 'Cuenta por cobrar') }}</div>
        </div>

        <button type="button" class="collect-close" data-close-collect>×</button>
      </div>

      <div class="collect-body">
        <form method="POST" enctype="multipart/form-data" action="{{ route('accounting.movements.store') }}">
          @csrf
          <input type="hidden" name="related_type" value="receivable">
          <input type="hidden" name="related_id" value="{{ $item->id }}">
          <input type="hidden" name="currency" value="{{ $currency }}">

          <div class="collect-balance">
            Saldo pendiente:
            <strong>${{ number_format($remaining, 2) }}</strong>
          </div>

          <div class="collect-grid">
            <div class="collect-field">
              <label for="collect_amount">Monto a cobrar *</label>
              <input id="collect_amount" class="collect-input" type="number" step="0.01" min="0.01" name="amount" required value="{{ $movementAmountValue }}">
            </div>

            <div class="collect-field">
              <label for="collect_date">Fecha de cobro *</label>
              <input id="collect_date" class="collect-input" type="date" name="movement_date" required value="{{ $movementDateValue }}">
            </div>

            <div class="collect-field">
              <label for="collect_method">Método de pago *</label>
              <select id="collect_method" name="method" class="collect-select" required>
                <option value="transferencia" @selected($movementMethodValue === 'transferencia')>Transferencia</option>
                <option value="efectivo" @selected($movementMethodValue === 'efectivo')>Efectivo</option>
                <option value="tarjeta" @selected($movementMethodValue === 'tarjeta')>Tarjeta</option>
                <option value="cheque" @selected($movementMethodValue === 'cheque')>Cheque</option>
                <option value="otro" @selected($movementMethodValue === 'otro')>Otro</option>
              </select>
            </div>

            <div class="collect-field">
              <label for="collect_reference">Referencia</label>
              <input id="collect_reference" class="collect-input" type="text" name="reference" value="{{ $movementReferenceValue }}" placeholder="Folio / SPEI / referencia">
            </div>

            <div class="collect-field">
              <label for="collect_notes">Notas</label>
              <input id="collect_notes" class="collect-input" type="text" name="notes" value="{{ $movementNotesValue }}" placeholder="Detalle del cobro">
            </div>

            <div class="collect-field">
              <label for="collect_evidence">Comprobante de cobro</label>
              <div class="collect-upload">
                <input id="collect_evidence" type="file" name="evidence" accept=".pdf,.jpg,.jpeg,.png,.webp,.xml,.doc,.docx,.xlsx">
                <div class="collect-upload-inner">
                  <div class="collect-upload-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                      <polyline points="7 10 12 5 17 10"></polyline>
                      <line x1="12" y1="5" x2="12" y2="16"></line>
                    </svg>
                  </div>
                  <div>
                    <div class="collect-upload-title">Subir comprobante</div>
                    <div class="collect-upload-sub">PDF, imagen, XML...</div>
                  </div>
                </div>
                <div class="collect-file-name" id="collectFileName"></div>
              </div>
            </div>
          </div>

          <div class="collect-actions">
            <button type="submit" class="collect-submit">Confirmar Cobro</button>
            <button type="button" class="collect-cancel" data-close-collect>Cancelar</button>
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
  const modal = document.getElementById('collectModal');
  const openers = document.querySelectorAll('[data-open-collect]');
  const deleteBtn = document.getElementById('deleteReceivableBtn');
  const deleteForm = document.getElementById('deleteReceivableForm');
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

  if (deleteBtn && deleteForm) {
    deleteBtn.addEventListener('click', function () {
      if (!hasSwal) {
        if (confirm('¿Eliminar esta cuenta?\n\nEsta acción no se puede deshacer.')) {
          deleteForm.submit();
        }
        return;
      }

      Swal.fire({
        title: '¿Eliminar esta cuenta?',
        text: 'Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        buttonsStyling: false,
        reverseButtons: true,
        focusCancel: true,
        confirmButtonText: 'Sí, eliminar',
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
          deleteForm.submit();
        }
      });
    });
  }

  if (modal) {
    const closers = modal.querySelectorAll('[data-close-collect]');
    const dialog = modal.querySelector('.collect-dialog');
    const fileInput = modal.querySelector('#collect_evidence');
    const fileName = modal.querySelector('#collectFileName');

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

    if (fileInput && fileName) {
      fileInput.addEventListener('change', function () {
        const file = this.files && this.files[0] ? this.files[0] : null;
        if (file) {
          fileName.textContent = 'Archivo seleccionado: ' + file.name;
          fileName.classList.add('show');
        } else {
          fileName.textContent = '';
          fileName.classList.remove('show');
        }
      });
    }

    @if($errors->any())
      openModal();
    @endif
  }

  /* Bitácora local */
  const receivableId = @json((string) $item->id);
  const storageKey = 'collection_log_receivable_' + receivableId;

  const bitaFormWrap = document.getElementById('bitaFormWrap');
  const openBitaForm = document.getElementById('openBitaForm');
  const cancelBitaForm = document.getElementById('cancelBitaForm');
  const bitaForm = document.getElementById('bitaForm');
  const bitaList = document.getElementById('bitaList');
  const bitaActionType = document.getElementById('bitaActionType');
  const bitaTypes = document.querySelectorAll('.bita-type');
  const bitaPerson = document.getElementById('bitaPerson');
  const bitaResult = document.getElementById('bitaResult');
  const bitaNextAction = document.getElementById('bitaNextAction');
  const bitaNotes = document.getElementById('bitaNotes');

  const typeConfig = {
    llamada: { label: 'Llamada', color: '#2563eb', icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.08 4.18 2 2 0 0 1 4.06 2h3a2 2 0 0 1 2 1.72c.12.9.33 1.78.62 2.62a2 2 0 0 1-.45 2.11L8 9.91a16 16 0 0 0 6.09 6.09l1.46-1.23a2 2 0 0 1 2.11-.45c.84.29 1.72.5 2.62.62A2 2 0 0 1 22 16.92z"></path></svg>' },
    email: { label: 'Email', color: '#2563eb', icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16v16H4z"></path><path d="m22 6-10 7L2 6"></path></svg>' },
    whatsapp: { label: 'WhatsApp', color: '#2563eb', icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.5 8.5 0 0 1-12.4 7.5L3 20l1.2-5.3A8.5 8.5 0 1 1 21 11.5z"></path></svg>' },
    visita: { label: 'Visita', color: '#2563eb', icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 1 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>' },
    promesa_pago: { label: 'Promesa de Pago', color: '#2563eb', icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22"></path><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14.5a3.5 3.5 0 0 1 0 7H6"></path></svg>' },
    pago_recibido: { label: 'Pago Recibido', color: '#2563eb', icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22"></path><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14.5a3.5 3.5 0 0 1 0 7H6"></path></svg>' },
    nota_interna: { label: 'Nota Interna', color: '#2563eb', icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"></path><path d="M14 3v5h5"></path><path d="M9 13h6"></path><path d="M9 17h6"></path></svg>' },
    escalamiento: { label: 'Escalamiento', color: '#2563eb', icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4"></path><path d="M12 17h.01"></path><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path></svg>' }
  };

  function getLogs() {
    try {
      return JSON.parse(localStorage.getItem(storageKey) || '[]');
    } catch (e) {
      return [];
    }
  }

  function saveLogs(logs) {
    localStorage.setItem(storageKey, JSON.stringify(logs));
  }

  function formatDateTime(dateStr) {
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return '';
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = String(d.getFullYear()).slice(-2);
    const hours = String(d.getHours()).padStart(2, '0');
    const mins = String(d.getMinutes()).padStart(2, '0');
    return `${day}/${month}/${year} ${hours}:${mins}`;
  }

  function renderLogs() {
    const logs = getLogs().sort((a, b) => new Date(b.created_at) - new Date(a.created_at));

    if (!logs.length) {
      bitaList.innerHTML = '<div class="rcv-empty">Sin registros de bitácora todavía.</div>';
      return;
    }

    bitaList.innerHTML = logs.map(log => {
      const cfg = typeConfig[log.type] || typeConfig.llamada;
      const meta = [];

      if (log.person) meta.push('Contacto: ' + log.person);
      if (log.result) meta.push('Resultado: ' + log.result);
      if (log.next_action) meta.push('Próxima acción: ' + log.next_action.split('-').reverse().join('/'));

      return `
        <div class="bita-item">
          <div class="bita-item-left">
            <div class="bita-item-icon" style="color:${cfg.color};">
              ${cfg.icon}
            </div>
            <div>
              <h4 class="bita-item-title">${cfg.label}</h4>
              <p class="bita-item-note">${(log.notes || '').replace(/</g, '&lt;').replace(/>/g, '&gt;')}</p>
              ${meta.length ? `<div class="bita-item-meta">${meta.map(m => `<span>${m}</span>`).join('')}</div>` : ''}
            </div>
          </div>
          <div class="bita-item-time">${formatDateTime(log.created_at)}</div>
        </div>
      `;
    }).join('');
  }

  function resetBitaForm() {
    bitaActionType.value = 'llamada';
    bitaTypes.forEach(btn => btn.classList.toggle('active', btn.dataset.type === 'llamada'));
    bitaPerson.value = '';
    bitaResult.value = 'Exitoso';
    bitaNextAction.value = '';
    bitaNotes.value = '';
  }

  function openBita() {
    bitaFormWrap.classList.add('show');
  }

  function closeBita() {
    bitaFormWrap.classList.remove('show');
    resetBitaForm();
  }

  if (openBitaForm) {
    openBitaForm.addEventListener('click', openBita);
  }

  if (cancelBitaForm) {
    cancelBitaForm.addEventListener('click', closeBita);
  }

  bitaTypes.forEach(btn => {
    btn.addEventListener('click', function () {
      bitaTypes.forEach(x => x.classList.remove('active'));
      this.classList.add('active');
      bitaActionType.value = this.dataset.type;
    });
  });

  if (bitaForm) {
    bitaForm.addEventListener('submit', function (e) {
      e.preventDefault();

      const notes = (bitaNotes.value || '').trim();
      if (!notes) {
        showToast('error', 'Escribe la descripción o notas.');
        bitaNotes.focus();
        return;
      }

      const logs = getLogs();
      logs.push({
        id: Date.now().toString(),
        type: bitaActionType.value || 'llamada',
        person: (bitaPerson.value || '').trim(),
        result: bitaResult.value || 'Exitoso',
        next_action: bitaNextAction.value || '',
        notes,
        created_at: new Date().toISOString()
      });

      saveLogs(logs);
      renderLogs();
      closeBita();
      showToast('success', 'Acción guardada en bitácora.');
    });
  }

  renderLogs();

  @if(session('success'))
    showToast('success', @json(session('success')));
  @endif

  @if(session('error'))
    showToast('error', @json(session('error')));
  @endif
})();
</script>
@endsection