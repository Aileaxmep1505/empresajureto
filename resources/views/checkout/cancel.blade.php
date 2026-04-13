{{-- resources/views/checkout/cancel.blade.php --}}
@extends('layouts.web')
@section('title','Pago cancelado')

@section('content')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&display=swap" rel="stylesheet"/>

<style>
  /* ====================== VARIABLES ====================== */
  :root {
    --bg: #f9fafb;
    --card: #ffffff;
    --ink: #111111;
    --text: #333333;
    --muted: #888888;
    --line: #ebebeb;
    --blue: #007aff;
    --danger: #ff4a4a;
    --danger-soft: #ffebeb;
  }

  body {
    font-family: "Quicksand", system-ui, -apple-system, sans-serif;
    background: var(--bg);
    color: var(--text);
    margin: 0;
  }

  /* ====================== LAYOUT ====================== */
  .cancel-wrap {
    max-width: 560px; /* Ancho reducido para centrar la atención */
    margin: clamp(40px, 8vw, 80px) auto;
    padding: 0 20px;
    box-sizing: border-box;
  }

  .cancel-card {
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 20px;
    box-shadow: 0 12px 40px rgba(0,0,0,0.04);
    padding: 48px 32px;
    text-align: center;
    animation: fadeUp 0.4s ease-out;
  }

  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(12px); }
    to { opacity: 1; transform: translateY(0); }
  }

  /* ====================== VISUALS ====================== */
  .icon-wrapper {
    width: 72px;
    height: 72px;
    border-radius: 50%;
    background: var(--danger-soft);
    color: var(--danger);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 24px auto;
  }

  .title {
    margin: 0 0 12px 0;
    font-weight: 700;
    font-size: 2rem;
    color: var(--ink);
  }

  .muted-text {
    color: var(--muted);
    font-size: 1.05rem;
    line-height: 1.5;
    margin: 0 auto 32px auto;
    max-width: 420px;
  }

  /* ====================== BUTTONS ====================== */
  .actions {
    display: flex;
    gap: 16px;
    justify-content: center;
    flex-wrap: wrap;
  }

  .btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    border-radius: 8px; padding: 12px 24px;
    font-weight: 600; font-size: 1rem; font-family: inherit;
    text-decoration: none; cursor: pointer; border: none;
    transition: transform 0.15s ease, box-shadow 0.2s ease, background 0.2s ease;
  }
  .btn:active { transform: scale(0.98); }
  
  .btn-primary { background: var(--blue); color: #ffffff; }
  .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(0, 122, 255, 0.15); }
  
  .btn-outline { background: var(--card); border: 1px solid var(--line); color: var(--ink); }
  .btn-outline:hover { background: var(--bg); transform: translateY(-1px); }
</style>

<div class="cancel-wrap">
  <div class="cancel-card">
    <div class="icon-wrapper">
      <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <line x1="18" y1="6" x2="6" y2="18"></line>
        <line x1="6" y1="6" x2="18" y2="18"></line>
      </svg>
    </div>
    
    <h1 class="title">Pago cancelado</h1>
    
    <p class="muted-text">
      El proceso de pago no se completó o fue interrumpido. No te preocupes, no se ha realizado ningún cargo y tu pedido sigue guardado.
    </p>
    
    <div class="actions">
      <a href="{{ route('web.cart.index') }}" class="btn btn-outline">Volver al carrito</a>
      {{-- Si tienes una ruta directa para reintentar el pago, puedes usar esta segunda opción --}}
      <a href="{{ route('checkout.payment') ?? route('web.cart.index') }}" class="btn btn-primary">Reintentar pago</a>
    </div>
  </div>
</div>
@endsection