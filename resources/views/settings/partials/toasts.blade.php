@php
  $toastItems = [];

  foreach (['success', 'error', 'warning', 'info'] as $toastType) {
      if (session()->has($toastType)) {
          $toastItems[] = [
              'type' => $toastType,
              'message' => (string) session($toastType),
          ];
      }
  }

  if ($errors->any()) {
      foreach ($errors->all() as $validationError) {
          $toastItems[] = [
              'type' => 'error',
              'message' => $validationError,
          ];
      }
  }
@endphp

<style>
  .minimal-toast-region {
    position: fixed;
    /* Cambiado a la parte superior */
    top: 32px;
    right: 32px;
    z-index: 9999;
    display: flex;
    flex-direction: column;
    gap: 12px;
    width: min(380px, calc(100vw - 40px));
    pointer-events: none;
    font-family: 'Inter', system-ui, -apple-system, sans-serif;
  }

  .minimal-toast {
    position: relative;
    background: #ffffff;
    border: 1px solid rgba(0, 0, 0, 0.04);
    border-radius: 10px;
    box-shadow: 0 12px 32px -8px rgba(0, 0, 0, 0.08), 0 4px 12px -4px rgba(0, 0, 0, 0.04);
    padding: 16px;
    display: flex;
    align-items: flex-start;
    gap: 12px;
    pointer-events: auto;
    overflow: hidden;
    
    /* Animación ajustada: entra cayendo ligeramente desde arriba (Y negativo) */
    opacity: 0;
    transform: translateY(-16px) scale(0.96);
    transition: opacity 0.4s cubic-bezier(0.16, 1, 0.3, 1), 
                transform 0.4s cubic-bezier(0.16, 1, 0.3, 1);
  }

  .minimal-toast.is-visible {
    opacity: 1;
    transform: translateY(0) scale(1);
  }

  .minimal-toast.is-leaving {
    opacity: 0;
    transform: scale(0.96);
    transition: opacity 0.25s ease, transform 0.25s ease;
  }

  .minimal-toast[data-type="success"] { --toast-color: #10b981; }
  .minimal-toast[data-type="error"] { --toast-color: #ef4444; }
  .minimal-toast[data-type="warning"] { --toast-color: #f59e0b; }
  .minimal-toast[data-type="info"] { --toast-color: #3b82f6; }

  .toast-icon {
    flex-shrink: 0;
    width: 20px;
    height: 20px;
    color: var(--toast-color);
    margin-top: 2px;
  }

  .toast-content {
    flex: 1;
    min-width: 0;
  }

  .toast-title {
    margin: 0 0 4px 0;
    font-size: 14px;
    font-weight: 600;
    color: #111827;
    letter-spacing: -0.01em;
  }

  .toast-message {
    margin: 0;
    font-size: 13px;
    line-height: 1.5;
    color: #6b7280;
  }

  .toast-close {
    flex-shrink: 0;
    width: 24px;
    height: 24px;
    padding: 4px;
    background: transparent;
    border: none;
    border-radius: 6px;
    color: #9ca3af;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-top: -2px;
    margin-right: -4px;
  }

  .toast-close:hover {
    color: #111827;
    background: #f3f4f6;
  }

  .toast-progress {
    position: absolute;
    bottom: 0;
    left: 0;
    height: 2px;
    background-color: var(--toast-color);
    opacity: 0.8;
    transform-origin: left;
    animation: toast-progress-anim var(--duration) linear forwards;
  }

  @keyframes toast-progress-anim {
    from { transform: scaleX(1); }
    to { transform: scaleX(0); }
  }

  @media (max-width: 640px) {
    .minimal-toast-region {
      /* Ajuste para móviles en la parte superior */
      top: 20px;
      right: 20px;
      left: 20px;
      width: auto;
    }
  }

  @media (prefers-reduced-motion: reduce) {
    .minimal-toast, .toast-progress {
      transition: none;
      animation: none;
    }
  }
</style>

<div class="minimal-toast-region" aria-live="polite" aria-atomic="false">
  @foreach($toastItems as $index => $toast)
    @php
      $type = in_array($toast['type'], ['success', 'error', 'warning', 'info'], true) ? $toast['type'] : 'info';
      $duration = $type === 'error' ? 7000 : 5000;
      
      $title = match($type) {
          'success' => 'Completado',
          'error' => 'Ha ocurrido un error',
          'warning' => 'Atención requerida',
          default => 'Información',
      };
    @endphp

    <div 
      class="minimal-toast" 
      data-toast 
      data-type="{{ $type }}" 
      style="--duration: {{ $duration }}ms;"
      role="{{ $type === 'error' ? 'alert' : 'status' }}"
    >
      <div class="toast-icon">
        @if($type === 'success')
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
        @elseif($type === 'error')
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
        @elseif($type === 'warning')
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
        @else
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
        @endif
      </div>

      <div class="toast-content">
        <p class="toast-title">{{ $title }}</p>
        <p class="toast-message">{{ $toast['message'] }}</p>
      </div>

      <button type="button" class="toast-close" data-close aria-label="Cerrar">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
      </button>

      <div class="toast-progress"></div>
    </div>
  @endforeach
</div>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    const toasts = document.querySelectorAll('[data-toast]');

    const removeToast = (toast) => {
      if (toast.dataset.dismissed) return;
      toast.dataset.dismissed = 'true';
      
      toast.classList.remove('is-visible');
      toast.classList.add('is-leaving');
      
      toast.addEventListener('transitionend', (e) => {
        if (e.propertyName === 'transform') {
          toast.remove();
        }
      });
    };

    toasts.forEach((toast, index) => {
      const duration = parseInt(toast.style.getPropertyValue('--duration')) || 5000;
      const staggerDelay = index * 100;

      setTimeout(() => {
        requestAnimationFrame(() => {
          toast.classList.add('is-visible');
        });
      }, 50 + staggerDelay);

      const closeBtn = toast.querySelector('[data-close]');
      if (closeBtn) {
        closeBtn.addEventListener('click', () => removeToast(toast));
      }

      setTimeout(() => {
        removeToast(toast);
      }, duration + staggerDelay);
    });
  });
</script>