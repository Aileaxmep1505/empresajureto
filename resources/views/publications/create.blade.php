@extends('layouts.app')

@section('title', 'Subir publicación')
@section('content_class', 'content--flush')
@section('content')
@php
    $v = fn($k, $d = null) => old($k, $d);
    $usersList = $users ?? \App\Models\User::select('id','name','email')->orderBy('name')->get();
@endphp

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&display=swap" rel="stylesheet">

<style>
:root {
  --bg: #f8fafc;
  --card: #ffffff;
  --ink: #1e293b;
  --muted: #64748b;
  --line: #e2e8f0;
  --blue: #007aff;
  --blue-soft: #eff6ff;
  --blue-button: #7bb1ff;
  --success: #10b981;
  --success-soft: #d1fae5;
  --danger: #ef4444;
  --danger-soft: #fee2e2;
  --warning-text: #b45309;
  --warning-bg: #fef3c7;
}

* { box-sizing: border-box; }

body {
  background: var(--bg);
  color: var(--ink);
  font-family: 'Quicksand', sans-serif;
  margin: 0;
}

.pubWizard {
  max-width: 1100px;
  margin: 0 auto;
  padding: 40px 24px 80px;
}

.pubBack {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 24px;
  color: var(--muted);
  text-decoration: none;
  font-size: 14px;
  font-weight: 700;
  transition: color 0.2s ease;
}

.pubBack:hover { color: var(--ink); }

/* Tarjetas base redonditas y sutiles */
.pubCard {
  background: var(--card);
  border: 1px solid var(--line);
  border-radius: 20px;
  box-shadow: 0 4px 24px rgba(0,0,0,0.02);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.pubCard:hover {
  box-shadow: 0 10px 32px rgba(0,0,0,0.04);
}

.pubHidden { display: none !important; }

/* Animaciones de entrada */
.anim-enter {
  animation: slideUpFade 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes slideUpFade {
  from { opacity: 0; transform: translateY(16px); }
  to { opacity: 1; transform: translateY(0); }
}

/* Botones */
.pubBtn {
  appearance: none;
  border: 1px solid transparent;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  min-height: 44px;
  padding: 0 24px;
  border-radius: 12px;
  font-family: 'Quicksand', sans-serif;
  font-size: 14px;
  font-weight: 700;
  text-decoration: none;
  transition: all 0.2s ease;
}

.pubBtn:active { transform: scale(0.97); }
.pubBtn.primary { background: var(--blue); color: #ffffff; }
.pubBtn.primary:hover { background: #006ce4; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0, 122, 255, 0.2); }

.pubBtn.primary-light { background: var(--blue-button); color: #ffffff; }
.pubBtn.primary-light:hover { background: #609df8; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(123, 177, 255, 0.3); }

.pubBtn.ghost { background: transparent; color: var(--muted); }
.pubBtn.ghost:hover { color: var(--ink); }
.pubBtn.outline { background: var(--card); color: var(--blue); border-color: var(--blue); }
.pubBtn.outline:hover { background: var(--blue-soft); }
.pubBtn.pill { border-radius: 999px; padding: 0 32px; }
.pubBtn[disabled] { opacity: 0.5; cursor: not-allowed; pointer-events: none; }

/* ---------------------------------------------------
   PASO 1: HERO (Mini Tarjeta Centrada) 
--------------------------------------------------- */
.pubHero { 
  min-height: 70vh; 
  display: flex; 
  align-items: center; 
  justify-content: center; 
}

.pubHeroBox { 
  width: 100%; 
  max-width: 640px;
  padding: 48px; 
  text-align: center; 
}

.pubHeroIcon { 
  width: 64px; 
  height: 64px; 
  margin: 0 auto 24px; 
  border-radius: 16px; 
  display: grid; 
  place-items: center; 
  color: var(--blue); 
  background: var(--blue-soft); 
}

.pubTitle { margin: 0 0 12px; color: var(--ink); font-size: 28px; font-weight: 700; letter-spacing: -0.02em; }
.pubText { margin: 0 auto; max-width: 460px; color: var(--muted); font-size: 14px; line-height: 1.6; font-weight: 500; }

.pubChoiceGrid { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 16px; margin-top: 32px; }
.pubChoice input { display: none; }
.pubChoice label { display: flex; flex-direction: column; align-items: flex-start; padding: 24px; text-align: left; background: var(--card); border: 1px solid var(--line); border-radius: 16px; cursor: pointer; transition: all 0.2s ease; }
.pubChoice label:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.03); }
.pubChoice input:checked + label { border-color: var(--blue); box-shadow: 0 0 0 3px var(--blue-soft); }

.pubBadge { display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 999px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; }
.pubBadge.info { background: var(--blue-soft); color: var(--blue); border: 1px solid #dbeafe; }
.pubBadge.success { background: var(--success-soft); color: var(--success); }

.pubChoiceTitle { margin: 16px 0 6px; color: var(--ink); font-size: 18px; font-weight: 700; }
.pubChoiceText { margin: 0; color: var(--muted); font-size: 13px; line-height: 1.5; }

.pubMain { display: none; }
.pubMain.show { display: block; }

/* ---------------------------------------------------
   PASO 2 y WIZARD
--------------------------------------------------- */
.pubTopbar { display: flex; align-items: flex-start; justify-content: space-between; gap: 24px; margin-bottom: 24px; }
.pubTopTitle { margin: 0 0 8px; color: var(--ink); font-size: 24px; font-weight: 700; letter-spacing: -0.02em; }
.pubTopSub { margin: 0; color: var(--muted); font-size: 14px; line-height: 1.6; }

.pubSteps { display: flex; align-items: center; flex-wrap: wrap; gap: 12px 0; margin-bottom: 32px; }
.pubStep { display: flex; align-items: center; gap: 10px; position: relative; }
.pubStep:not(:last-child)::after { content: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23cccccc' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M9 18l6-6-6-6'/%3E%3C/svg%3E"); margin: 0 16px; display: flex; align-items: center; opacity: 0.6; }
.pubStepNum { width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center; border-radius: 999px; background: var(--card); border: 1px solid var(--line); color: var(--muted); font-size: 12px; font-weight: 700; transition: all 0.3s ease; }
.pubStepTitle { margin: 0; color: var(--muted); font-size: 14px; font-weight: 600; transition: all 0.3s ease; }
.pubStepText { display: none; }

.pubStep.active .pubStepNum { background: var(--blue); border-color: var(--blue); color: #ffffff; box-shadow: 0 0 0 4px var(--blue-soft); }
.pubStep.active .pubStepTitle { color: var(--ink); font-weight: 700; }
.pubStep.done .pubStepNum { background: var(--success-soft); border-color: transparent; color: transparent; position: relative; }
.pubStep.done .pubStepNum::after { content: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2310b981' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='20 6 9 17 4 12'/%3E%3C/svg%3E"); position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); display: block; width: 12px; height: 12px; }
.pubStep.done .pubStepTitle { color: var(--ink); }

.pubStage { display: none; }
.pubStage.show { display: block; }

/* ---------------------------------------------------
   BORDE ANIMADO INTELIGENTE (Contenedor Vacío)
--------------------------------------------------- */
.pubEmptyStateCard {
  position: relative;
  max-width: 600px;
  margin: 0 auto;
  padding: 2px;
  border-radius: 22px; 
  background: var(--card); 
  overflow: hidden;
  border: none !important; 
  box-shadow: 0 10px 40px rgba(0, 122, 255, 0.08);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.pubEmptyStateCard:hover {
  transform: translateY(-2px);
  box-shadow: 0 16px 50px rgba(0, 122, 255, 0.12);
}

.pubEmptyStateCard::before {
  content: '';
  position: absolute;
  top: -50%;
  left: -50%;
  width: 200%;
  height: 200%;
  background: conic-gradient(
    transparent 0deg,
    transparent 90deg,
    #007aff 180deg,
    #a855f7 240deg,
    #00bfff 300deg,
    transparent 360deg
  );
  animation: spinBorder 4s linear infinite;
  z-index: 0;
  opacity: 0.7;
  transition: opacity 0.3s ease;
}

.pubEmptyStateCard:hover::before { opacity: 1; }

@keyframes spinBorder {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.pubUploadCentered { 
  position: relative;
  z-index: 1; 
  display: flex; 
  flex-direction: column; 
  align-items: center; 
  justify-content: center; 
  text-align: center; 
  padding: 48px 32px; 
  background: var(--card); 
  border-radius: 20px; 
  height: 100%;
  transition: background 0.3s ease;
}
.pubUploadCentered.dragover { background: var(--blue-soft); }

.pubUploadIconFloat { width: 64px; height: 64px; background: #ffffff; border-radius: 16px; display: flex; align-items: center; justify-content: center; color: var(--blue); box-shadow: 0 8px 24px rgba(0, 0, 0, 0.04); margin-bottom: 24px; transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
.pubEmptyStateCard:hover .pubUploadIconFloat { transform: scale(1.05); color: #a855f7; }

.pubUploadTitleCenter { font-size: 20px; font-weight: 700; color: var(--ink); margin: 0 0 8px; letter-spacing: -0.02em; }
.pubUploadTextCenter { font-size: 14px; color: var(--muted); max-width: 400px; line-height: 1.6; margin: 0 0 20px; }
.pubButtonWrapper { width: 100%; display: flex; justify-content: center; align-items: center; margin-top: 8px; }

/* ---------------------------------------------------
   ESTADO LLENO / PROGRESO
--------------------------------------------------- */
.pubCardHead { padding: 32px 32px 0; display: flex; justify-content: space-between; gap: 16px; align-items: flex-start; }
.pubCardTitle { margin: 0; color: var(--ink); font-size: 18px; font-weight: 700; }
.pubCardSub { margin: 6px 0 0; color: var(--muted); font-size: 14px; line-height: 1.5; }
.pubCardBody { padding: 32px; }

.pubFiles { display: grid; gap: 12px; margin-top: 24px; }
.pubFile { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 14px 18px; border: 1px solid var(--line); border-radius: 12px; background: var(--card); }
.pubFileName { color: var(--ink); font-size: 14px; font-weight: 700; }
.pubFileMeta { margin-top: 2px; color: var(--muted); font-size: 12px; }

.pubActions { display: flex; flex-wrap: wrap; justify-content: center; gap: 12px; margin-top: 32px; }

/* Progreso Dinámico */
.pubProgressTitle { margin: 0 0 8px; color: var(--ink); font-size: 20px; font-weight: 700; letter-spacing: -0.01em; }
.pubProgressText { margin: 0; color: var(--muted); font-size: 14px; line-height: 1.6; }
.pubProgressTrack { width: 100%; height: 6px; margin-top: 24px; border-radius: 999px; background: var(--line); overflow: hidden; }
.pubProgressFill { width: 0%; height: 100%; border-radius: 999px; background: var(--blue); transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
.pubProgressPercent { margin-top: 10px; color: var(--blue); font-size: 13px; font-weight: 700; }

.pubProcessList { display: block; margin-top: 32px; min-height: 60px; position: relative; }
.pubProcessItem { display: none; align-items: center; justify-content: space-between; padding: 16px 20px; border: 1px solid var(--line); border-radius: 12px; background: var(--card); }
.pubProcessItem.is-visible { display: flex; animation: popIn 0.35s cubic-bezier(0.175, 0.885, 0.32, 1.2) forwards; }
@keyframes popIn { 0% { opacity: 0; transform: translateY(12px) scale(0.98); } 100% { opacity: 1; transform: translateY(0) scale(1); } }
.pubProcessLeft { display: flex; align-items: center; gap: 12px; }
.pubDot { width: 10px; height: 10px; border-radius: 999px; transition: all 0.3s ease; }
.pubProcessItem.active .pubDot { background: var(--blue); box-shadow: 0 0 0 4px var(--blue-soft); }
.pubProcessItem.done .pubDot { background: var(--success); }
.pubProcessName { color: var(--ink); font-size: 14px; font-weight: 600; }
.pubProcessState { font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; transition: all 0.3s ease; }
.pubProcessItem.active .pubProcessState { color: var(--blue); }
.pubProcessItem.done .pubProcessState { color: var(--success); }

/* Pantalla Éxito */
.pubSuccessScreen { display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 48px 20px; }
.pubSuccessCircle { width: 80px; height: 80px; background: var(--success-soft); color: var(--success); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 24px; box-shadow: 0 0 0 12px rgba(16, 185, 129, 0.05); animation: scaleIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards; }
@keyframes scaleIn { 0% { transform: scale(0); opacity: 0; } 100% { transform: scale(1); opacity: 1; } }
.pubSuccessTitle { font-size: 24px; font-weight: 700; color: var(--ink); margin: 0 0 8px; letter-spacing: -0.02em; }
.pubSuccessSub { font-size: 14px; color: var(--muted); margin: 0; }

/* ---------------------------------------------------
   PASOS 3 Y 4: REVISIÓN Y GUARDADO
--------------------------------------------------- */
.pubReviewLayout { display: grid; grid-template-columns: 1.5fr 1fr; gap: 24px; }
.pubStack { display: flex; flex-direction: column; gap: 24px; }

/* Formularios minimalistas */
.pubLabel { color: var(--muted); font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 6px; display: block; }
.pubInput, .pubSelect, .pubTextarea { 
  width: 100%; 
  min-height: 48px; 
  padding: 12px 16px; 
  border: 1px solid var(--line); 
  border-radius: 8px; 
  background: var(--card); 
  color: var(--ink); 
  font-family: 'Quicksand', sans-serif; 
  font-size: 14px; 
  font-weight: 500; 
  transition: border-color 0.2s ease, box-shadow 0.2s ease; 
}
.pubTextarea { min-height: 100px; resize: vertical; }
.pubInput:focus, .pubSelect:focus, .pubTextarea:focus { outline: none; border-color: var(--blue); box-shadow: 0 0 0 3px var(--blue-soft); }

.pubReviewTop { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px; }
.pubTableHead, .pubRow { display: grid; grid-template-columns: 2fr 0.8fr 1fr 1fr 0.8fr 36px; gap: 12px; align-items: center; }
.pubTableHead { padding-bottom: 8px; border-bottom: none; color: var(--muted); font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em; }
.pubRows { max-height: 400px; overflow: auto; padding-right: 4px; border-top: 1px solid var(--line); padding-top: 16px; }
.pubRow { margin-bottom: 12px; }

.pubRemove { width: 36px; height: 36px; border: 1px solid var(--line); border-radius: 8px; background: var(--card); color: var(--muted); font-size: 18px; cursor: pointer; transition: all 0.2s ease; display: flex; align-items: center; justify-content: center; }
.pubRemove:hover { color: var(--danger); border-color: var(--danger-soft); background: var(--danger-soft); }

.pubReviewFooter { display: flex; flex-direction: column; gap: 24px; margin-top: 24px; padding-top: 24px; border-top: 1px solid var(--line); }

/* Switch Toggle Box */
.pubToggleBox {
  display: inline-flex;
  align-items: center;
  gap: 12px;
  padding: 10px 16px;
  border: 1px solid var(--line);
  border-radius: 8px;
  background: var(--card);
}
.pubSwitch { position: relative; width: 44px; height: 24px; flex: 0 0 44px; }
.pubSwitch input { display: none; }
.pubSwitchTrack { position: absolute; inset: 0; border-radius: 999px; background: var(--line); transition: background 0.3s ease; cursor: pointer; }
.pubSwitchThumb { position: absolute; top: 2px; left: 2px; width: 20px; height: 20px; border-radius: 999px; background: #ffffff; box-shadow: 0 2px 4px rgba(0,0,0,0.1); transition: left 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
.pubSwitch input:checked + .pubSwitchTrack { background: var(--success); }
.pubSwitch input:checked + .pubSwitchTrack .pubSwitchThumb { left: 22px; }
.pubToggleText { font-size: 13px; font-weight: 700; color: var(--ink); }

.pubTotals { width: 100%; max-width: 280px; align-self: flex-end; }
.pubTotalRow { display: flex; justify-content: space-between; gap: 16px; padding: 6px 0; font-size: 13px; color: var(--muted); }
.pubTotalRow strong { color: var(--ink); font-weight: 700; }
.pubTotalBig { display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; margin-top: 12px; padding-top: 12px; border-top: 1px dashed var(--line); }
.pubTotalBig small { color: var(--muted); font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em; }
.pubTotalBig span { color: var(--ink); font-size: 28px; font-weight: 700; letter-spacing: -0.02em; }

/* Contenedor Final de Guardado */
.pubSaveContainer {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 16px 0;
  gap: 16px;
}

.pubAlertCenter {
  width: 100%;
  padding: 16px;
  border-radius: 12px;
  font-size: 13px;
  font-weight: 600;
  text-align: center;
}
.pubAlertCenter.warn { background: var(--warning-bg); color: var(--warning-text); }
.pubAlertCenter.info { background: var(--blue-soft); color: var(--blue); }

.pubPill.strong-pill { padding: 8px 16px; background: var(--card); border: 1px solid var(--line); font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; border-radius: 999px; }

/* Cuentas por cobrar layout (Match imagen) */
.pubGrid2 { display: grid; grid-template-columns: repeat(2,minmax(0,1fr)); gap: 16px; }
.pubGrid3 { display: grid; grid-template-columns: repeat(3,minmax(0,1fr)); gap: 16px; }

.pubInfoNote { 
  margin-top: 24px; 
  padding: 16px; 
  border-radius: 12px; 
  background: var(--bg); 
  border: 1px solid var(--line);
  color: var(--muted); 
  font-size: 12px; 
  line-height: 1.6; 
  text-align: left;
}
.pubInfoNote strong { color: var(--ink); }

@media (max-width: 1100px) { .pubReviewLayout { grid-template-columns: 1fr; } }
@media (max-width: 900px) {
  .pubHeroBox { padding: 32px 20px; }
  .pubChoiceGrid, .pubReviewTop, .pubTableHead, .pubRow, .pubGrid2, .pubGrid3 { grid-template-columns: 1fr; }
  .pubTableHead { display: none; }
  .pubSteps { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
  .pubStep:not(:last-child)::after { display: none; }
}
</style>

<div class="pubWizard" id="publicationWizard">
  <a class="pubBack anim-enter" href="{{ route('publications.index') }}">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
      <path d="M15 6L9 12L15 18" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
    Volver al listado
  </a>

  <form id="publicationForm" action="{{ route('publications.store') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <input type="hidden" name="ai_extract" id="ai_extract" value="1">
    <input type="hidden" name="ai_skip" id="ai_skip" value="0">
    <input type="hidden" name="ai_payload" id="ai_payload" value="">
    <input type="hidden" name="ai_payload_bulk" id="ai_payload_bulk" value="">
    <input type="hidden" name="ai_tax_mode" id="ai_tax_mode" value="included">
    <input type="hidden" name="ai_tax_rate" id="ai_tax_rate" value="0.16">
    <div id="fpInputs"></div>

    {{-- ==========================================
         PASO 1: TIPO (MINI TARJETA CENTRADA) 
    ========================================== --}}
    <section id="screenType" class="pubHero anim-enter">
      <div class="pubCard pubHeroBox">
        <div class="pubHeroIcon">
          <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
            <polyline points="17 8 12 3 7 8"></polyline>
            <line x1="12" y1="3" x2="12" y2="15"></line>
          </svg>
        </div>

        <h1 class="pubTitle">Análisis Inteligente</h1>


        <div class="pubChoiceGrid">
          <div class="pubChoice">
            <input type="radio" name="category" value="compra" id="categoryCompra" {{ $v('category', 'compra') === 'compra' ? 'checked' : '' }}>
            <label for="categoryCompra">
              <span class="pubBadge info">Compra</span>
              <h3 class="pubChoiceTitle">Registrar compra</h3>
              <p class="pubChoiceText">Se extraen productos, cantidades, precios e importes.</p>
            </label>
          </div>

          <div class="pubChoice">
            <input type="radio" name="category" value="venta" id="categoryVenta" {{ $v('category') === 'venta' ? 'checked' : '' }}>
            <label for="categoryVenta">
              <span class="pubBadge success">Venta</span>
              <h3 class="pubChoiceTitle">Registrar venta</h3>
              <p class="pubChoiceText">Se muestran los campos para control de cobranza.</p>
            </label>
          </div>
        </div>

        <div class="pubActions">
          <button type="button" class="pubBtn primary pill" id="btnGoDocuments">Continuar</button>
        </div>
      </div>
    </section>

    {{-- ==========================================
         PANTALLAS PRINCIPALES WIZARD 
    ========================================== --}}
    <section id="screenMain" class="pubMain">
      <div class="pubTopbar anim-enter">
        <div>
          <h2 class="pubTopTitle">Nueva publicación inteligente</h2>

        </div>
        <div style="display:flex; gap:12px; align-items:center;">
          <span class="pubPill">Tipo: <strong id="selectedTypeText">Compra</strong></span>
          <button type="button" class="pubBtn ghost" id="btnBackType" style="min-height:36px; padding:0 16px;">Cambiar</button>
        </div>
      </div>

      <div class="pubSteps anim-enter">
        <div class="pubStep" data-step="1"><div class="pubStepNum">1</div><h4 class="pubStepTitle">Tipo</h4></div>
        <div class="pubStep" data-step="2"><div class="pubStepNum">2</div><h4 class="pubStepTitle">Documentos</h4></div>
        <div class="pubStep" data-step="3"><div class="pubStepNum">3</div><h4 class="pubStepTitle">Revisión</h4></div>
        <div class="pubStep" data-step="4"><div class="pubStepNum">4</div><h4 class="pubStepTitle">Guardar</h4></div>
      </div>

      {{-- PASO 2: DOCUMENTOS --}}
      <div id="stageDocuments" class="pubStage pubStageDocuments anim-enter">
        <input type="file" name="files[]" id="f-file" multiple required accept="application/pdf,.pdf" style="display:none;">

        <!-- ESTADO 1: Tarjeta central animada Mágica -->
        <div class="pubEmptyStateCard" id="viewUploadEmpty">
          <div class="pubUploadCentered" id="dropZoneEmpty">
            <div class="pubUploadIconFloat">
              <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="17 8 12 3 7 8"></polyline>
                <line x1="12" y1="3" x2="12" y2="15"></line>
              </svg>
            </div>
            <h2 class="pubUploadTitleCenter">Análisis Inteligente</h2>
            <p class="pubUploadTextCenter">Sube tu documento en PDF. Nuestra IA estructurará partidas, cantidades y especificaciones automáticamente.</p>
            
            <div class="pubButtonWrapper">
              <button type="button" class="pubBtn primary pill" id="btnPickFilesEmpty">Seleccionar Archivo</button>
            </div>
          </div>
        </div>

        <!-- ESTADO 2: Contenedor Principal (Archivos Listos o Progreso) -->
        <div class="pubCard pubHidden" id="viewUploadFilled">
          <div id="uploadReadyContent">
            <div class="pubCardHead">
              <div style="display:flex; align-items:center; gap:12px;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--blue);">
                  <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path>
                  <polyline points="13 2 13 9 20 9"></polyline>
                </svg>
                <h3 class="pubCardTitle">Documentos seleccionados</h3>
              </div>
            </div>
            <div class="pubCardBody">
              <p class="pubCardSub" style="margin-top:0;">Revisa los archivos. Todos deben pertenecer al <strong id="samePartyText">mismo proveedor</strong>.</p>
              
              <div class="pubFiles" id="selectedFilesBox"></div>
              
              <div class="pubActions" style="margin-top: 24px;">
                <button type="button" class="pubBtn outline pill" id="btnClearDocs">Limpiar / Cambiar</button>
                <button type="button" class="pubBtn primary pill" id="btnStartExtraction" disabled>Comenzar extracción</button>
              </div>
              <div style="text-align:center; margin-top:16px;">
                <button type="button" class="pubBtn ghost" id="btnManual" style="font-size:13px;">O ingresar datos manualmente</button>
              </div>
            </div>
          </div>

          <div id="uploadProgressContent" class="pubHidden">
            <div class="pubCardBody pubProgressUI" id="progressBox">
              <h3 class="pubProgressTitle">Procesando documentos</h3>
              <p class="pubProgressText" id="progressText">Estamos preparando tus PDFs para extraer la información.</p>

              <div class="pubProgressTrack">
                <div class="pubProgressFill" id="progressFill"></div>
              </div>
              <div class="pubProgressPercent" id="progressPercent">0%</div>

              <div class="pubProcessList">
                <div class="pubProcessItem" data-process="validate">
                  <div class="pubProcessLeft"><span class="pubDot"></span><span class="pubProcessName">Validando archivos</span></div>
                  <span class="pubProcessState">EN CURSO</span>
                </div>
                <div class="pubProcessItem" data-process="prepare">
                  <div class="pubProcessLeft"><span class="pubDot"></span><span class="pubProcessName">Preparando envío seguro a IA</span></div>
                  <span class="pubProcessState">EN CURSO</span>
                </div>
                <div class="pubProcessItem" data-process="extract">
                  <div class="pubProcessLeft"><span class="pubDot"></span><span class="pubProcessName">Extrayendo información</span></div>
                  <span class="pubProcessState">EN CURSO</span>
                </div>
                <div class="pubProcessItem" data-process="structure">
                  <div class="pubProcessLeft"><span class="pubDot"></span><span class="pubProcessName">Estructurando conceptos e importes</span></div>
                  <span class="pubProcessState">EN CURSO</span>
                </div>
                <div class="pubProcessItem" data-process="finish">
                  <div class="pubProcessLeft"><span class="pubDot"></span><span class="pubProcessName">Finalizando validación de datos</span></div>
                  <span class="pubProcessState">EN CURSO</span>
                </div>
              </div>
            </div>

            <div id="extractionSuccess" class="pubSuccessScreen pubHidden">
              <div class="pubSuccessCircle">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
              </div>
              <h3 class="pubSuccessTitle">¡Extracción completada!</h3>
              <p class="pubSuccessSub">Preparando tu revisión...</p>
            </div>
          </div>
        </div>
      </div>

      {{-- PASOS 3 Y 4: REVISIÓN (LAYOUT DOS COLUMNAS) --}}
      <div id="stageReview" class="pubStage anim-enter">
        <div class="pubReviewLayout">
          
          <!-- COLUMNA IZQUIERDA (Paso 3) -->
          <div class="pubStack">
            <div class="pubCard">
              <div class="pubCardHead" style="align-items:center; padding-bottom:16px;">
                <h3 class="pubCardTitle">Paso 3. Revisión</h3>
                <span class="pubPill strong-pill" style="color: var(--success); border-color: var(--success-soft); background: var(--success-soft);">EXTRAÍDO</span>
              </div>

              <div class="pubCardBody" style="padding-top:0;">
                <p class="pubCardSub" style="margin-bottom:24px;">Revisa los datos extraídos. Puedes editar, agregar o borrar conceptos.</p>
                
                <div class="pubReviewTop">
                  <div>
                    <label class="pubLabel" for="docSupplier">Proveedor / Cliente</label>
                    <input class="pubInput" id="docSupplier" placeholder="Proveedor">
                  </div>
                  <div>
                    <label class="pubLabel" for="docDatetime">Fecha del documento</label>
                    <input class="pubInput" id="docDatetime" type="datetime-local">
                  </div>
                </div>

                <div class="pubTableHead">
                  <div>Concepto</div><div>Cant.</div><div>Precio</div><div>Total</div><div>Unidad</div><div></div>
                </div>

                <div class="pubRows" id="aiEditRows"></div>

                <div class="pubReviewFooter">
                  <div style="display:flex; justify-content:space-between; align-items:center; width:100%;">
                    <button type="button" class="pubBtn ghost" id="btnAddRow" style="padding:0;">+ Fila</button>
                    
                    <div class="pubToggleBox">
                      <label class="pubSwitch">
                        <input type="checkbox" id="taxIncluded" checked>
                        <span class="pubSwitchTrack"><span class="pubSwitchThumb"></span></span>
                      </label>
                      <span class="pubToggleText">Total ya incluye IVA</span>
                    </div>
                  </div>

                  <div class="pubTotals">
                    <div class="pubTotalRow"><span>Subtotal</span><strong id="aiSubtotal">$0.00</strong></div>
                    <div class="pubTotalRow"><span>IVA (16%)</span><strong id="aiTax">$0.00</strong></div>
                    <div class="pubTotalBig"><div><small>Total documento</small></div><div><span id="aiTotal">$0.00</span></div></div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- COLUMNA DERECHA (Paso 4 y Guardar) -->
          <div class="pubStack">
            <div class="pubCard">
              <div class="pubCardHead" style="padding-bottom:16px;">
                <h3 class="pubCardTitle">Paso 4. Detalles</h3>
              </div>

              <div class="pubCardBody" style="padding-top:0;">
                <p class="pubCardSub" style="margin-bottom:24px;">Completa la información general del registro.</p>
                <div>
                  <label class="pubLabel" for="f-title">Título</label>
                  <input class="pubInput" type="text" name="title" id="f-title" value="{{ $v('title') }}" required>
                </div>
                <div style="margin-top:20px;">
                  <label class="pubLabel" for="f-description">Descripción</label>
                  <textarea class="pubTextarea" name="description" id="f-description">{{ $v('description') }}</textarea>
                </div>
                <div style="margin-top:20px;">
                  <label class="pubLabel">Fijar publicación</label>
                  <div class="pubToggleBox" style="width:100%;">
                    <label class="pubSwitch">
                      <input type="checkbox" name="pinned" value="1" {{ $v('pinned') ? 'checked' : '' }}>
                      <span class="pubSwitchTrack"><span class="pubSwitchThumb"></span></span>
                    </label>
                    <span class="pubToggleText">Mostrar al principio de la lista</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- CUENTAS POR COBRAR (Diseño Exacto Venta) -->
            <div class="pubCard pubHidden" id="salesAccountingBox">
              <div class="pubCardHead" style="align-items: center; padding-bottom: 24px;">
                <div style="display:flex; align-items:center; gap:12px;">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--ink);">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <polyline points="10 9 9 9 8 9"></polyline>
                  </svg>
                  <h3 class="pubCardTitle" style="font-size:18px;">Datos para Cuentas por Cobrar</h3>
                </div>
                <span class="pubBadge info" style="font-size:10px; padding:6px 12px; border-radius:999px;">Venta → Cobranza</span>
              </div>
              <div class="pubCardBody" style="padding-top:0;">
                <div class="pubGrid2">
                  <div class="pubField">
                    <label class="pubLabel" for="f-company_id">Compañía</label>
                    <select class="pubSelect" name="company_id" id="f-company_id">
                      <option value="">Selecciona compañía</option>
                      @foreach(($companies ?? []) as $c)
                        <option value="{{ $c->id }}" @selected((string)$v('company_id') === (string)$c->id)>{{ $c->name }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="pubField">
                    <label class="pubLabel" for="f-due_date">Fecha de vencimiento</label>
                    <input class="pubInput" type="date" name="due_date" id="f-due_date" value="{{ $v('due_date') }}">
                  </div>
                </div>
                <div class="pubGrid3" style="margin-top:20px;">
                  <div class="pubField">
                    <label class="pubLabel" for="f-amount_paid">Monto pagado</label>
                    <input class="pubInput" type="number" min="0" step="0.01" name="amount_paid" id="f-amount_paid" value="{{ $v('amount_paid', 0) }}">
                  </div>
                  <div class="pubField">
                    <label class="pubLabel" for="f-status">Estado</label>
                    <select class="pubSelect" name="status" id="f-status">
                      <option value="pendiente" @selected($v('status','pendiente') === 'pendiente')>Pendiente</option>
                      <option value="parcial" @selected($v('status') === 'parcial')>Parcial</option>
                      <option value="cobrado" @selected($v('status') === 'cobrado')>Cobrado</option>
                    </select>
                  </div>
                  <div class="pubField">
                    <label class="pubLabel" for="f-priority">Prioridad</label>
                    <select class="pubSelect" name="priority" id="f-priority">
                      <option value="baja" @selected($v('priority') === 'baja')>Baja</option>
                      <option value="media" @selected($v('priority','media') === 'media')>Media</option>
                      <option value="alta" @selected($v('priority') === 'alta')>Alta</option>
                    </select>
                  </div>
                </div>
                <div class="pubGrid3" style="margin-top:20px;">
                  <div class="pubField">
                    <label class="pubLabel" for="f-collection_status">Estado de cobranza</label>
                    <select class="pubSelect" name="collection_status" id="f-collection_status">
                      <option value="sin_gestion" @selected($v('collection_status','sin_gestion') === 'sin_gestion')>Sin gestión</option>
                      <option value="en_gestion" @selected($v('collection_status') === 'en_gestion')>En gestión</option>
                      <option value="promesa_pago" @selected($v('collection_status') === 'promesa_pago')>Promesa de pago</option>
                    </select>
                  </div>
                  <div class="pubField">
                    <label class="pubLabel" for="f-reminder_days_before">Días previos para recordar</label>
                    <input class="pubInput" type="number" min="0" max="365" name="reminder_days_before" id="f-reminder_days_before" value="{{ $v('reminder_days_before', 5) }}">
                  </div>
                  <div class="pubField">
                    <label class="pubLabel" for="f-assigned_to">Asignado a</label>
                    <select class="pubSelect" name="assigned_to" id="f-assigned_to">
                      <option value="">Sin asignar</option>
                      @foreach($usersList as $user)
                        <option value="{{ $user->id }}" @selected((string)$v('assigned_to') === (string)$user->id)>
                          {{ $user->name }}
                        </option>
                      @endforeach
                    </select>
                  </div>
                </div>
                <div class="pubField" style="margin-top:20px;">
                  <label class="pubLabel" for="f-notes">Notas de cobranza</label>
                  <textarea class="pubTextarea" name="notes" id="f-notes" placeholder="Notas internas, acuerdos, seguimiento...">{{ $v('notes') }}</textarea>
                </div>

                <div class="pubInfoNote">
                  La publicación de venta quedará lista para ligarse a cobranza. Se recomienda capturar <strong>fecha de vencimiento</strong> y, si aplica, el <strong>usuario responsable</strong>.
                </div>
              </div>
            </div>

            <!-- CONTENEDOR FINAL GUARDAR -->
            <div class="pubCard">
              <div class="pubCardBody">
                <div class="pubSaveContainer">
                  <div id="saveHint" class="pubAlertCenter warn">
                    Captura un título para la publicación.
                  </div>
                  <button type="button" class="pubBtn ghost" id="btnBackDocuments" style="margin-top:8px;">Volver a documentos</button>
                  <button type="submit" class="pubBtn primary-light pill" id="submitBtn" disabled>Guardar publicación</button>
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>
    </section>
  </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const screenType = document.getElementById('screenType');
  const screenMain = document.getElementById('screenMain');
  const stageDocuments = document.getElementById('stageDocuments');
  const stageReview = document.getElementById('stageReview');

  const btnGoDocuments = document.getElementById('btnGoDocuments');
  const btnBackType = document.getElementById('btnBackType');
  const btnBackDocuments = document.getElementById('btnBackDocuments');

  const selectedTypeText = document.getElementById('selectedTypeText');

  const fileInput = document.getElementById('f-file');
  
  const viewUploadEmpty = document.getElementById('viewUploadEmpty');
  const viewUploadFilled = document.getElementById('viewUploadFilled');
  const uploadReadyContent = document.getElementById('uploadReadyContent');
  const uploadProgressContent = document.getElementById('uploadProgressContent');
  const dropZoneEmpty = document.getElementById('dropZoneEmpty');
  const btnPickFilesEmpty = document.getElementById('btnPickFilesEmpty');
  const btnClearDocs = document.getElementById('btnClearDocs');
  const extractionSuccess = document.getElementById('extractionSuccess');

  const btnStartExtraction = document.getElementById('btnStartExtraction');
  const btnManual = document.getElementById('btnManual');
  const selectedFilesBox = document.getElementById('selectedFilesBox');

  const progressBox = document.getElementById('progressBox');
  const progressFill = document.getElementById('progressFill');
  const progressPercent = document.getElementById('progressPercent');
  const progressText = document.getElementById('progressText');

  const aiEditRows = document.getElementById('aiEditRows');
  const btnAddRow = document.getElementById('btnAddRow');
  const docSupplier = document.getElementById('docSupplier');
  const docDatetime = document.getElementById('docDatetime');
  const taxIncluded = document.getElementById('taxIncluded');
  const aiSubtotal = document.getElementById('aiSubtotal');
  const aiTax = document.getElementById('aiTax');
  const aiTotal = document.getElementById('aiTotal');

  const titleInput = document.getElementById('f-title');
  const submitBtn = document.getElementById('submitBtn');
  const saveHint = document.getElementById('saveHint');
  const salesAccountingBox = document.getElementById('salesAccountingBox');
  const companyInput = document.getElementById('f-company_id');

  const aiPayload = document.getElementById('ai_payload');
  const aiPayloadBulk = document.getElementById('ai_payload_bulk');
  const aiExtract = document.getElementById('ai_extract');
  const aiSkip = document.getElementById('ai_skip');
  const aiTaxMode = document.getElementById('ai_tax_mode');
  const aiTaxRate = document.getElementById('ai_tax_rate');
  const fpInputs = document.getElementById('fpInputs');

  let aiRows = [];
  let bulkDocs = [];
  let currentMode = 'ai';
  let aiDoc = {
    supplier_name: '',
    document_datetime: '',
    subtotal: 0,
    tax: 0,
    total: 0,
    category: getCategory(),
    tax_mode: 'included',
    tax_rate: 0.16
  };

  function getCategory() {
    return document.querySelector('input[name="category"]:checked')?.value || 'compra';
  }

  function isSale() {
    return getCategory() === 'venta';
  }

  function partyLabel() {
    return isSale() ? 'cliente' : 'proveedor';
  }

  function money(value) {
    return '$' + Number(value || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function num(value) {
    return parseFloat(String(value ?? '').replace(/[^0-9.\-]/g, '')) || 0;
  }

  function clean(value) {
    return String(value || '').trim();
  }

  function round2(n) {
    return Math.round((Number(n || 0) + Number.EPSILON) * 100) / 100;
  }

  function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
  }

  function escapeHtml(str) {
    return String(str || '')
      .replaceAll('&','&amp;')
      .replaceAll('<','&lt;')
      .replaceAll('>','&gt;')
      .replaceAll('"','&quot;')
      .replaceAll("'",'&#039;');
  }

  function normalizeName(value) {
    return String(value || '')
      .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
      .replace(/[^\w\s]/g, ' ')
      .replace(/\s+/g, ' ')
      .trim()
      .toLowerCase();
  }

  function toDatetimeLocal(value) {
    if (!value) return '';
    const s = String(value).trim();
    if (s.includes('T')) return s.slice(0,16);
    if (s.includes(' ')) {
      const parts = s.split(' ');
      return parts[0] + 'T' + (parts[1] || '').slice(0,5);
    }
    return s;
  }

  function fromDatetimeLocal(value) {
    if (!value) return '';
    return String(value).replace('T', ' ') + ':00';
  }

  function isPdfFile(file) {
    const name = String(file?.name || '').toLowerCase();
    const type = String(file?.type || '').toLowerCase();
    return type === 'application/pdf' || name.endsWith('.pdf');
  }

  function updateCategoryUi() {
    selectedTypeText.textContent = isSale() ? 'Venta' : 'Compra';
    docSupplier.placeholder = isSale() ? 'Cliente' : 'Proveedor';
    
    if (isSale()) {
      salesAccountingBox.classList.remove('pubHidden');
      if (companyInput) companyInput.required = true;
    } else {
      salesAccountingBox.classList.add('pubHidden');
      if (companyInput) companyInput.required = false;
    }
  }

  function setStep(step) {
    document.querySelectorAll('.pubStep').forEach(el => {
      const n = Number(el.dataset.step);
      el.classList.remove('active', 'done');
      if (n < step) el.classList.add('done');
      if (n === step) el.classList.add('active');
    });

    stageDocuments.classList.toggle('show', step === 2);
    stageReview.classList.toggle('show', step >= 3);

    if (step === 2) {
      uploadProgressContent.classList.add('pubHidden');
      uploadReadyContent.classList.remove('pubHidden');
    }

    if (step >= 3) {
      window.scrollTo({ top: document.getElementById('publicationWizard').offsetTop, behavior: 'smooth' });
    }
  }

  function showMain(step = 2) {
    screenType.classList.add('pubHidden');
    screenMain.classList.add('show');
    updateCategoryUi();
    setStep(step);
  }

  function showType() {
    screenMain.classList.remove('show');
    screenType.classList.remove('pubHidden');
  }

  btnGoDocuments.addEventListener('click', () => showMain(2));
  btnBackType.addEventListener('click', showType);
  btnBackDocuments.addEventListener('click', () => setStep(2));

  document.querySelectorAll('input[name="category"]').forEach(radio => {
    radio.addEventListener('change', function () {
      updateCategoryUi();
      updateSubmitAvailability();
    });
  });

  function fingerprint(file) {
    return `${file.name}|${file.size}|${file.type || ''}`;
  }

  function buildFpInputs() {
    fpInputs.innerHTML = '';
    Array.from(fileInput.files || []).forEach(file => {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'file_fps[]';
      input.value = fingerprint(file);
      fpInputs.appendChild(input);
    });
  }

  btnPickFilesEmpty.addEventListener('click', () => fileInput.click());
  
  ['dragenter','dragover'].forEach(evt => {
    dropZoneEmpty.addEventListener(evt, e => {
      e.preventDefault();
      e.stopPropagation();
      dropZoneEmpty.classList.add('dragover');
    });
  });

  ['dragleave','drop'].forEach(evt => {
    dropZoneEmpty.addEventListener(evt, e => {
      e.preventDefault();
      e.stopPropagation();
      dropZoneEmpty.classList.remove('dragover');
    });
  });

  dropZoneEmpty.addEventListener('drop', e => {
    const files = e.dataTransfer.files;
    if (files && files.length) {
      fileInput.files = files;
      handleSelection();
    }
  });

  fileInput.addEventListener('change', handleSelection);

  function handleSelection() {
    const files = Array.from(fileInput.files || []);
    buildFpInputs();
    resetProgress();
    clearExtracted(false);

    if (files.length && !files.every(isPdfFile)) {
      fileInput.value = '';
      alert('Selecciona únicamente archivos PDF.');
      btnStartExtraction.disabled = true;
      updateSubmitAvailability();
      viewUploadEmpty.classList.add('pubHidden');
      viewUploadFilled.classList.remove('pubHidden');
      selectedFilesBox.innerHTML = '';
      return;
    }

    if (!files.length) {
      viewUploadEmpty.classList.remove('pubHidden');
      viewUploadFilled.classList.add('pubHidden');
      btnStartExtraction.disabled = true;
      updateSubmitAvailability();
      return;
    }

    viewUploadEmpty.classList.add('pubHidden');
    viewUploadFilled.classList.remove('pubHidden');

    selectedFilesBox.innerHTML = '';
    files.forEach(file => {
      const item = document.createElement('div');
      item.className = 'pubFile';
      item.innerHTML = `
        <div>
          <div class="pubFileName">${escapeHtml(file.name)}</div>
          <div class="pubFileMeta">${(file.size / 1024).toFixed(1)} KB · PDF</div>
        </div>
      `;
      selectedFilesBox.appendChild(item);
    });

    btnStartExtraction.disabled = false;
    updateSubmitAvailability();
  }

  btnClearDocs.addEventListener('click', function () {
    fileInput.value = '';
    selectedFilesBox.innerHTML = '';
    btnStartExtraction.disabled = true;
    
    viewUploadFilled.classList.add('pubHidden');
    viewUploadEmpty.classList.remove('pubHidden');
    
    resetProgress();
    clearExtracted(true);
  });

  function resetProgress() {
    progressFill.style.width = '0%';
    progressPercent.textContent = '0%';
    progressText.textContent = 'Estamos preparando tus PDFs para extraer la información.';

    document.querySelectorAll('.pubProcessItem').forEach(item => {
      item.classList.remove('is-visible', 'active', 'done');
      const state = item.querySelector('.pubProcessState');
      if (state) state.textContent = 'PENDIENTE';
    });
  }

  function setProgress(percent, text) {
    const safe = Math.max(0, Math.min(100, percent));
    progressFill.style.width = safe + '%';
    progressPercent.textContent = Math.round(safe) + '%';
    if (text) progressText.textContent = text;
  }

  function setProcess(key, state) {
    document.querySelectorAll('.pubProcessItem').forEach(item => {
      if(item.dataset.process === key) {
        item.classList.add('is-visible'); 
        item.classList.remove('active', 'done');
        
        const stateEl = item.querySelector('.pubProcessState');
        
        if (state === 'active') {
          item.classList.add('active');
          if (stateEl) stateEl.textContent = 'EN CURSO';
        } else if (state === 'done') {
          item.classList.add('done');
          if (stateEl) stateEl.textContent = 'COMPLETADO';
        }
      } else {
        item.classList.remove('is-visible', 'active', 'done');
      }
    });
  }

  async function extractSingleFile(file) {
    const fd = new FormData();
    fd.append('file', file);
    fd.append('category', getCategory());

    const response = await fetch("{{ route('publications.ai.extract') }}", {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': "{{ csrf_token() }}",
        'Accept': 'application/json'
      },
      body: fd
    });

    const data = await response.json();
    if (!response.ok) {
      throw new Error(data.error || 'No se pudo analizar el archivo.');
    }
    return data;
  }

  btnStartExtraction.addEventListener('click', runExtraction);

  async function runExtraction() {
    const files = Array.from(fileInput.files || []);

    if (!files.length) return;

    uploadReadyContent.classList.add('pubHidden');
    uploadProgressContent.classList.remove('pubHidden');
    progressBox.classList.remove('pubHidden');
    extractionSuccess.classList.add('pubHidden');

    bulkDocs = [];
    aiRows = [];
    aiPayload.value = '';
    aiPayloadBulk.value = '';

    try {
      setProcess('validate', 'active');
      setProgress(8, 'Validando archivos seleccionados...');
      await sleep(500);

      if (!files.every(isPdfFile)) throw new Error('Solo se permiten documentos PDF.');

      setProcess('validate', 'done');
      await sleep(400); 

      setProcess('prepare', 'active');
      setProgress(18, 'Conectando con el servidor...');
      await sleep(500);

      setProcess('prepare', 'done');
      await sleep(400);

      setProcess('extract', 'active');
      for (let i = 0; i < files.length; i++) {
        const file = files[i];
        const startPercent = 25 + ((i / files.length) * 42);
        const endPercent = 25 + (((i + 1) / files.length) * 42);

        setProgress(startPercent, `Analizando documento "${file.name}" (${i + 1}/${files.length})...`);
        const result = await extractSingleFile(file);

        bulkDocs.push({
          fp: fingerprint(file),
          file_name: file.name,
          document: result.document || {},
          items: Array.isArray(result.items) ? result.items : [],
          notes: result.notes || {}
        });

        setProgress(endPercent, `Documento procesado con éxito.`);
        await sleep(300);
      }

      setProcess('extract', 'done');
      await sleep(400);

      setProcess('structure', 'active');
      setProgress(76, 'Calculando subtotales, IVA e importes...');
      await sleep(600);

      if (!validateSameParty()) throw new Error(`Los documentos no pertenecen al mismo ${partyLabel()}.`);

      mergeResults();
      aiPayloadBulk.value = JSON.stringify(bulkDocs);

      setProcess('structure', 'done');
      await sleep(400);

      setProcess('finish', 'active');
      setProgress(92, 'Generando vista de revisión previa...');
      await sleep(600);

      renderRows();
      syncPayload();

      setProcess('finish', 'done');
      setProgress(100, 'Todo listo.');
      await sleep(500);

      progressBox.classList.add('pubHidden');
      extractionSuccess.classList.remove('pubHidden');
      
      await sleep(1500); 
      setStep(3);

    } catch (error) {
      console.error(error);
      alert(error.message || 'No se pudo completar la extracción.');
      uploadProgressContent.classList.add('pubHidden');
      uploadReadyContent.classList.remove('pubHidden');
    }
  }

  function validateSameParty() {
    const names = bulkDocs
      .map(item => normalizeName(item?.document?.supplier_name))
      .filter(Boolean);

    if (names.length <= 1) return true;
    const unique = [...new Set(names)];
    if (unique.length > 1) return false;
    return true;
  }

  function mergeResults() {
    const items = [];
    let supplier = '';
    let datetime = '';
    let subtotal = 0;
    let tax = 0;
    let total = 0;

    bulkDocs.forEach(pack => {
      const d = pack.document || {};
      if (!supplier && d.supplier_name) supplier = d.supplier_name;
      if (!datetime && d.document_datetime) datetime = d.document_datetime;

      subtotal += num(d.subtotal);
      tax += num(d.tax);
      total += num(d.total);

      (pack.items || []).forEach(item => {
        items.push({
          item_name: item.item_name || '',
          qty: num(item.qty) || 1,
          unit_price: num(item.unit_price),
          line_total: num(item.line_total),
          unit: item.unit || 'pza'
        });
      });
    });

    aiDoc = {
      supplier_name: supplier,
      document_datetime: datetime,
      subtotal,
      tax,
      total,
      category: getCategory(),
      tax_mode: taxIncluded.checked ? 'included' : 'add',
      tax_rate: num(aiTaxRate.value) || 0.16
    };

    aiRows = items.length ? items : [{ item_name: '', qty: 1, unit_price: 0, line_total: 0, unit: 'pza' }];

    docSupplier.value = supplier || '';
    docDatetime.value = toDatetimeLocal(datetime || '');

    if (!titleInput.value && supplier) {
      titleInput.value = `${isSale() ? 'Venta' : 'Compra'} - ${supplier}`;
    }
  }

  function renderRows() {
    aiEditRows.innerHTML = '';

    aiRows.forEach((row, index) => {
      const div = document.createElement('div');
      div.className = 'pubRow';
      div.innerHTML = `
        <div><textarea class="pubTextarea row-input" data-index="${index}" data-key="item_name" style="min-height:48px;">${escapeHtml(row.item_name || '')}</textarea></div>
        <div><input class="pubInput row-input" data-index="${index}" data-key="qty" type="number" step="0.01" value="${num(row.qty) || 1}"></div>
        <div><input class="pubInput row-input" data-index="${index}" data-key="unit_price" type="number" step="0.01" value="${num(row.unit_price).toFixed(2)}"></div>
        <div><input class="pubInput row-input" data-index="${index}" data-key="line_total" type="number" step="0.01" value="${num(row.line_total).toFixed(2)}"></div>
        <div><input class="pubInput row-input" data-index="${index}" data-key="unit" type="text" value="${escapeHtml(row.unit || 'pza')}"></div>
        <div><button type="button" class="pubRemove" data-remove="${index}">×</button></div>
      `;
      aiEditRows.appendChild(div);
    });

    bindRowEvents();
    recalcTotals();
  }

  function bindRowEvents() {
    document.querySelectorAll('.row-input').forEach(input => {
      input.addEventListener('input', function () {
        const index = Number(this.dataset.index);
        const key = this.dataset.key;
        if (!aiRows[index]) return;

        if (key === 'item_name' || key === 'unit') {
          aiRows[index][key] = this.value;
        } else {
          aiRows[index][key] = num(this.value);
        }

        if (key === 'qty' || key === 'unit_price') {
          aiRows[index].line_total = num(aiRows[index].qty) * num(aiRows[index].unit_price);
          const totalField = document.querySelector(`.row-input[data-index="${index}"][data-key="line_total"]`);
          if (totalField) totalField.value = num(aiRows[index].line_total).toFixed(2);
        }

        if (key === 'line_total') {
          const qty = num(aiRows[index].qty);
          if (qty > 0) {
            aiRows[index].unit_price = num(aiRows[index].line_total) / qty;
            const priceField = document.querySelector(`.row-input[data-index="${index}"][data-key="unit_price"]`);
            if (priceField) priceField.value = num(aiRows[index].unit_price).toFixed(2);
          }
        }

        recalcTotals();
      });
    });

    document.querySelectorAll('[data-remove]').forEach(btn => {
      btn.addEventListener('click', function () {
        const index = Number(this.dataset.remove);
        aiRows.splice(index, 1);
        if (!aiRows.length) aiRows.push({ item_name: '', qty: 1, unit_price: 0, line_total: 0, unit: 'pza' });
        renderRows();
      });
    });
  }

  btnAddRow.addEventListener('click', function () {
    aiRows.push({ item_name: '', qty: 1, unit_price: 0, line_total: 0, unit: 'pza' });
    renderRows();
  });

  docSupplier.addEventListener('input', recalcTotals);
  docDatetime.addEventListener('change', recalcTotals);
  taxIncluded.addEventListener('change', recalcTotals);
  titleInput.addEventListener('input', updateSubmitAvailability);
  companyInput?.addEventListener('change', updateSubmitAvailability);

  function recalcTotals() {
    const base = aiRows.reduce((acc, row) => acc + num(row.line_total), 0);
    const rate = num(aiTaxRate.value) || 0.16;
    let subtotal = 0;
    let tax = 0;
    let total = 0;

    if (taxIncluded.checked) {
      total = base;
      subtotal = rate > 0 ? total / (1 + rate) : total;
      tax = total - subtotal;
      aiTaxMode.value = 'included';
    } else {
      subtotal = base;
      tax = subtotal * rate;
      total = subtotal + tax;
      aiTaxMode.value = 'add';
    }

    aiDoc.supplier_name = clean(docSupplier.value);
    aiDoc.document_datetime = fromDatetimeLocal(docDatetime.value);
    aiDoc.subtotal = round2(subtotal);
    aiDoc.tax = round2(tax);
    aiDoc.total = round2(total);
    aiDoc.category = getCategory();
    aiDoc.tax_mode = aiTaxMode.value;
    aiDoc.tax_rate = rate;

    aiSubtotal.textContent = money(aiDoc.subtotal);
    aiTax.textContent = money(aiDoc.tax);
    aiTotal.textContent = money(aiDoc.total);

    syncPayload();
    updateSubmitAvailability();
  }

  function syncPayload() {
    aiPayload.value = JSON.stringify({
      document: {
        ...aiDoc,
        supplier_name: clean(docSupplier.value),
        document_datetime: fromDatetimeLocal(docDatetime.value)
      },
      items: aiRows.map(row => ({
        item_name: clean(row.item_name),
        qty: num(row.qty),
        unit_price: num(row.unit_price),
        line_total: num(row.line_total),
        unit: clean(row.unit || 'pza')
      })),
      notes: {
        engine: currentMode === 'manual' ? 'manual' : 'azure_document_intelligence'
      }
    });
  }

  function hasValidRows() {
    return aiRows.some(row => clean(row.item_name) || num(row.qty) > 0 || num(row.unit_price) > 0 || num(row.line_total) > 0);
  }

  function updateSubmitAvailability() {
    const titleReady = clean(titleInput.value).length > 0;
    const partyReady = clean(docSupplier.value).length > 0;
    const rowsReady = hasValidRows();
    const saleReady = !isSale() || !!companyInput.value;

    let canSave = false;
    let msg = '';
    let alertClass = 'warn';

    if (!titleReady) {
      msg = 'Captura un título para la publicación.';
    } else if (!partyReady) {
      msg = `Falta un ${isSale() ? 'cliente' : 'proveedor'} válido.`;
    } else if (!rowsReady) {
      msg = 'Falta capturar los conceptos.';
    } else if (isSale() && !saleReady) {
      msg = 'Para venta, selecciona una compañía.';
    } else {
      canSave = true;
      msg = 'Todo listo para guardar.';
      alertClass = 'info';
    }

    submitBtn.disabled = !canSave;
    saveHint.className = `pubAlertCenter ${alertClass}`;
    saveHint.textContent = msg;
  }

  function clearExtracted(full = true) {
    aiRows = [];
    bulkDocs = [];
    aiDoc = { supplier_name: '', document_datetime: '', subtotal: 0, tax: 0, total: 0, category: getCategory(), tax_mode: 'included', tax_rate: 0.16 };
    aiPayload.value = '';
    aiPayloadBulk.value = '';
    aiEditRows.innerHTML = '';

    if (full) {
      docSupplier.value = '';
      docDatetime.value = '';
      aiSubtotal.textContent = '$0.00';
      aiTax.textContent = '$0.00';
      aiTotal.textContent = '$0.00';
    }

    updateSubmitAvailability();
  }

  btnManual.addEventListener('click', function () {
    currentMode = 'manual';
    aiSkip.value = '1';
    aiExtract.value = '0';
    aiRows = [{ item_name: '', qty: 1, unit_price: 0, line_total: 0, unit: 'pza' }];
    docSupplier.value = '';
    docDatetime.value = '';
    renderRows();
    setStep(3);
  });

  document.getElementById('publicationForm').addEventListener('submit', function () {
    syncPayload();
  });

  updateCategoryUi();
  updateSubmitAvailability();

  @if($v('category'))
    showMain(2);
  @endif
});
</script>
@endsection