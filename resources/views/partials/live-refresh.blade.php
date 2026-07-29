{{--
  Auto-refresco en tiempo real (sin recargar toda la página).

  Uso:
    1) Envuelve el contenido dinámico (tablas, tarjetas de stats, etc.) en:
         <div data-live-refresh="un-nombre-unico"> ... </div>
    2) Incluye este partial una vez en la vista:
         @include('partials.live-refresh')

  Cómo funciona: cada pocos segundos vuelve a pedir la MISMA URL (con sus filtros/
  página) en segundo plano, extrae el contenedor y reemplaza solo su contenido si
  cambió. No recarga la página, no pierde el scroll y no toca los filtros.

  Notas:
    - No refresca si el usuario tiene el foco en un input/select/textarea.
    - No refresca si la pestaña está en segundo plano.
    - Seguro para contenido basado en links/forms (los listados de WMS lo son).
--}}
<script>
(function () {
  if (window.__wmsLiveRefresh) return;      // evitar doble init
  window.__wmsLiveRefresh = true;

  var INTERVAL = 10000;                       // 10s
  var busy = false;

  function signature(el) {
    if (!el) return '';
    // Firma barata para detectar cambios sin comparar todo el HTML.
    return el.innerHTML.length + '|' + el.textContent.replace(/\s+/g, '').length;
  }

  function isTyping() {
    var a = document.activeElement;
    return a && (a.tagName === 'INPUT' || a.tagName === 'SELECT' || a.tagName === 'TEXTAREA' || a.isContentEditable);
  }

  async function tick() {
    if (busy || document.hidden || isTyping()) return;

    var containers = Array.prototype.slice.call(document.querySelectorAll('[data-live-refresh]'));
    if (!containers.length) return;

    busy = true;
    try {
      var res = await fetch(window.location.href, {
        headers: { 'X-Live-Refresh': '1', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
        cache: 'no-store'
      });
      if (!res.ok) return;

      var html = await res.text();
      var doc = new DOMParser().parseFromString(html, 'text/html');

      containers.forEach(function (cur) {
        var name = cur.getAttribute('data-live-refresh');
        if (!name) return;
        var fresh = doc.querySelector('[data-live-refresh="' + name.replace(/"/g, '') + '"]');
        if (fresh && signature(fresh) !== signature(cur)) {
          cur.innerHTML = fresh.innerHTML;
          // Evitar que se re-disparen animaciones de entrada (se vería como "recarga").
          cur.querySelectorAll('.animate-entrance, .animate-enter, .fade-in-up').forEach(function (e) {
            e.classList.remove('animate-entrance', 'animate-enter', 'fade-in-up');
            e.style.opacity = '1';
          });
          cur.dispatchEvent(new CustomEvent('live-refresh:updated', { bubbles: true }));
        }
      });
    } catch (e) {
      /* silencioso: si falla una vuelta, se reintenta en la siguiente */
    } finally {
      busy = false;
    }
  }

  setInterval(tick, INTERVAL);
})();
</script>
