@extends('layouts.app')

@section('title', 'Supervisor · Ruta')

@section('content')
<div id="rp-supervisor" class="container-fluid p-0">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&display=swap');

    #rp-supervisor {
      --bg: #f9fafb;
      --card: #ffffff;
      --ink: #333333;
      --title: #111111;
      --muted: #888888;
      --line: #ebebeb;
      --blue: #007aff;
      --blue-soft: #e6f0ff;
      --success: #15803d;
      --success-soft: #e6ffe6;
      --danger: #ff4a4a;
      --danger-soft: #ffebeb;

      min-height: calc(100vh - 56px);
      background: var(--bg);
      color: var(--ink);
      font-family: 'Quicksand', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    }

    #rp-supervisor * {
      box-sizing: border-box;
    }

    #rp-supervisor .wrap {
      width: min(1440px, 100%);
      margin: 0 auto;
      padding: 24px;
    }

    #rp-supervisor .topline {
      display: flex;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
      margin-bottom: 18px;
    }

    #rp-supervisor .pill {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      min-height: 38px;
      padding: 8px 14px;
      border: 1px solid var(--line);
      border-radius: 999px;
      background: var(--card);
      color: var(--ink);
      font-size: 14px;
      font-weight: 700;
      text-decoration: none;
      box-shadow: 0 4px 12px rgba(0,0,0,0.02);
      transition: transform .18s ease, box-shadow .18s ease, background .18s ease;
    }

    #rp-supervisor a.pill:hover {
      background: #f9fafb;
      color: var(--ink);
      transform: translateY(-1px);
      box-shadow: 0 8px 18px rgba(0,0,0,0.04);
    }

    #rp-supervisor .pill:active {
      transform: scale(.98);
    }

    #rp-supervisor .dot {
      width: 10px;
      height: 10px;
      border-radius: 999px;
      background: var(--muted);
      display: inline-block;
      flex: 0 0 auto;
    }

    #rp-supervisor .dot.live {
      background: var(--success);
      box-shadow: 0 0 0 4px var(--success-soft);
    }

    #rp-supervisor .grid {
      display: grid;
      gap: 16px;
    }

    @media (min-width: 992px) {
      #rp-supervisor .grid {
        grid-template-columns: 380px 1fr;
        align-items: start;
      }
    }

    #rp-supervisor .cardx {
      background: var(--card);
      border: 1px solid var(--line);
      border-radius: 16px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.02);
      overflow: hidden;
      transition: transform .18s ease, box-shadow .18s ease;
    }

    #rp-supervisor .cardx:hover {
      transform: translateY(-2px);
      box-shadow: 0 12px 24px rgba(0,0,0,0.04);
    }

    #rp-supervisor .hd {
      padding: 16px 18px;
      border-bottom: 1px solid var(--line);
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 12px;
    }

    #rp-supervisor .hd-title {
      color: var(--title);
      font-size: 15px;
      font-weight: 700;
      letter-spacing: -.01em;
    }

    #rp-supervisor .bd {
      padding: 18px;
    }

    #rp-supervisor .muted {
      color: var(--muted);
      font-size: 13px;
      font-weight: 600;
    }

    #rp-supervisor .small {
      font-size: 12.5px;
    }

    #rp-supervisor .kpis {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 10px;
      margin-bottom: 18px;
    }

    #rp-supervisor .kpi {
      border: 1px solid var(--line);
      border-radius: 14px;
      padding: 12px;
      background: #fff;
    }

    #rp-supervisor .kpi .l {
      font-size: 11px;
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: .04em;
      font-weight: 700;
      margin-bottom: 4px;
    }

    #rp-supervisor .kpi .v {
      color: var(--title);
      font-weight: 700;
      font-size: 22px;
      line-height: 1;
    }

    #rp-supervisor .list-title {
      color: var(--muted);
      font-size: 13px;
      font-weight: 700;
      margin-bottom: 10px;
    }

    #rp-supervisor .list {
      list-style: none;
      margin: 0;
      padding: 0;
      max-height: calc(100vh - 360px);
      min-height: 220px;
      overflow: auto;
      padding-right: 4px;
    }

    #rp-supervisor .list::-webkit-scrollbar {
      width: 6px;
    }

    #rp-supervisor .list::-webkit-scrollbar-thumb {
      background: #d7dce3;
      border-radius: 999px;
    }

    #rp-supervisor .rowx {
      border: 1px solid var(--line);
      border-radius: 14px;
      padding: 12px;
      background: #fff;
      display: flex;
      justify-content: space-between;
      gap: 10px;
      align-items: flex-start;
      transition: transform .18s ease, box-shadow .18s ease;
    }

    #rp-supervisor .rowx:hover {
      transform: translateY(-1px);
      box-shadow: 0 8px 18px rgba(0,0,0,0.03);
    }

    #rp-supervisor .rowx + .rowx {
      margin-top: 10px;
    }

    #rp-supervisor .stop-name {
      color: var(--title);
      font-size: 14px;
      font-weight: 700;
      line-height: 1.25;
      margin-bottom: 4px;
    }

    #rp-supervisor .badge {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      white-space: nowrap;
      border-radius: 999px;
      padding: 5px 9px;
      font-weight: 700;
      font-size: 11px;
      border: 0;
    }

    #rp-supervisor .badge.done {
      background: var(--success-soft);
      color: var(--success);
    }

    #rp-supervisor .badge.pending {
      background: var(--blue-soft);
      color: var(--blue);
    }

    #rp-supervisor #mapSup {
      height: calc(100vh - 150px);
      min-height: 560px;
      border-radius: 14px;
      border: 1px solid var(--line);
      overflow: hidden;
      background: #eef3f8;
    }

    #rp-supervisor .map-error {
      display: none;
      margin-bottom: 12px;
      padding: 12px 14px;
      border-radius: 12px;
      background: var(--danger-soft);
      color: var(--danger);
      font-size: 13px;
      font-weight: 700;
    }

    #rp-supervisor .map-error.is-visible {
      display: block;
    }

    #rp-supervisor .gm-stop-marker {
      width: 28px;
      height: 28px;
      border-radius: 10px 10px 4px 10px;
      background: var(--blue);
      color: #fff;
      display: grid;
      place-items: center;
      font-size: 12px;
      font-weight: 700;
      border: 2px solid #fff;
      box-shadow: 0 8px 16px rgba(0,0,0,.16);
    }

    @media (max-width: 991px) {
      #rp-supervisor .wrap {
        padding: 16px;
      }

      #rp-supervisor #mapSup {
        height: 520px;
        min-height: 520px;
      }

      #rp-supervisor .list {
        max-height: none;
      }
    }

    @media (max-width: 560px) {
      #rp-supervisor .kpis {
        grid-template-columns: 1fr;
      }

      #rp-supervisor .pill {
        width: 100%;
        justify-content: center;
      }
    }
  </style>

  <div class="wrap">
    <div class="topline">
      <a href="{{ route('routes.index') }}" class="pill">← Volver</a>

      <div class="pill">
        <span class="dot" id="liveDot"></span>
        <span id="liveTxt">Conectando…</span>
      </div>

      <div class="pill">
        Ruta:
        <strong>{{ $routePlan->name ?? ('#'.$routePlan->id) }}</strong>
      </div>

      <div class="pill">
        Chofer:
        <strong>{{ $routePlan->driver?->name ?? '—' }}</strong>
      </div>
    </div>

    <div class="grid">
      <div class="cardx">
        <div class="hd">
          <div class="hd-title">Progreso</div>
          <small class="muted" id="serverTime">—</small>
        </div>

        <div class="bd">
          <div class="kpis">
            <div class="kpi">
              <div class="l">Total</div>
              <div class="v" id="kTotal">—</div>
            </div>

            <div class="kpi">
              <div class="l">Hechos</div>
              <div class="v" id="kDone">—</div>
            </div>

            <div class="kpi">
              <div class="l">Pendientes</div>
              <div class="v" id="kPending">—</div>
            </div>
          </div>

          <div class="list-title">Paradas</div>
          <ul class="list" id="stopsList"></ul>
        </div>
      </div>

      <div class="cardx">
        <div class="hd">
          <div class="hd-title">Mapa en tiempo real</div>
          <small class="muted">Se actualiza automáticamente</small>
        </div>

        <div class="bd">
          <div class="map-error" id="mapError">
            No se pudo cargar Google Maps. Revisa tu API key, restricciones y APIs habilitadas.
          </div>

          <div id="mapSup"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  const PLAN_ID = @json($routePlan->id);
  const POLL_URL = @json(route('api.supervisor.routes.poll', $routePlan));

  let map = null;
  let driverMarker = null;
  let stopMarkers = [];
  let routeLine = null;
  let firstFitDone = false;
  let lastDriverInfoWindow = null;

  function toNum(value) {
    const number = Number(value);
    return Number.isFinite(number) ? number : null;
  }

  function isValid(lat, lng) {
    if (lat === null || lng === null) return false;
    if (Math.abs(lat) < 0.000001 && Math.abs(lng) < 0.000001) return false;
    return Math.abs(lat) <= 90 && Math.abs(lng) <= 180;
  }

  function safeText(value, fallback = '') {
    if (value === null || value === undefined) return fallback;
    return String(value)
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  function formatCoord(lat, lng) {
    if (!isValid(lat, lng)) return '(—)';
    return `(${lat.toFixed(5)}, ${lng.toFixed(5)})`;
  }

  function setMapError(show) {
    const box = document.getElementById('mapError');
    if (!box) return;
    box.classList.toggle('is-visible', !!show);
  }

  function setLive(ok, text) {
    const dot = document.getElementById('liveDot');
    const liveText = document.getElementById('liveTxt');

    if (dot) dot.classList.toggle('live', !!ok);
    if (liveText) liveText.textContent = text || (ok ? 'En vivo' : 'Sin conexión');
  }

  function makeStopIcon(index, done = false) {
    const color = done ? '#15803d' : '#007aff';

    return {
      url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
        <svg xmlns="http://www.w3.org/2000/svg" width="42" height="42" viewBox="0 0 42 42">
          <filter id="s" x="-30%" y="-30%" width="160%" height="160%">
            <feDropShadow dx="0" dy="6" stdDeviation="4" flood-color="#000000" flood-opacity=".18"/>
          </filter>
          <path filter="url(#s)" d="M21 4c8.284 0 15 6.716 15 15 0 10.5-15 19-15 19S6 29.5 6 19C6 10.716 12.716 4 21 4Z" fill="${color}"/>
          <circle cx="21" cy="19" r="11" fill="#ffffff"/>
          <text x="21" y="23" text-anchor="middle" font-family="Arial, sans-serif" font-size="12" font-weight="700" fill="${color}">${index}</text>
        </svg>
      `),
      scaledSize: new google.maps.Size(42, 42),
      anchor: new google.maps.Point(21, 38),
    };
  }

  function makeDriverIcon(heading = null, online = true) {
    const rotation = Number.isFinite(Number(heading)) ? Number(heading) : 0;
    const color = online ? '#15803d' : '#888888';

    return {
      path: google.maps.SymbolPath.FORWARD_CLOSED_ARROW,
      scale: 6.5,
      fillColor: color,
      fillOpacity: 1,
      strokeColor: '#ffffff',
      strokeWeight: 2,
      rotation: rotation,
      anchor: new google.maps.Point(0, 2),
    };
  }

  function clearStopMarkers() {
    stopMarkers.forEach(marker => {
      try {
        marker.setMap(null);
      } catch (e) {}
    });

    stopMarkers = [];
  }

  function renderStops(stops) {
    const ul = document.getElementById('stopsList');
    if (!ul) return;

    ul.innerHTML = '';

    (stops || []).forEach((stop, index) => {
      const lat = toNum(stop.lat);
      const lng = toNum(stop.lng);
      const done = stop.status === 'done';

      const badge = done
        ? '<span class="badge done">hecho</span>'
        : '<span class="badge pending">pendiente</span>';

      const coord = formatCoord(lat, lng);

      ul.insertAdjacentHTML('beforeend', `
        <li class="rowx">
          <div>
            <div class="stop-name">#${index + 1}. ${safeText(stop.name || 'Punto')}</div>
            <div class="muted small">${coord}</div>
            ${stop.done_at ? `<div class="muted small">Finalizado: ${safeText(stop.done_at)}</div>` : ''}
          </div>
          <div>${badge}</div>
        </li>
      `);
    });
  }

  function renderMap(stops, driver) {
    if (!map || !window.google || !google.maps) return;

    clearStopMarkers();

    const bounds = new google.maps.LatLngBounds();
    const path = [];

    (stops || []).forEach((stop, index) => {
      const lat = toNum(stop.lat);
      const lng = toNum(stop.lng);

      if (!isValid(lat, lng)) return;

      const position = { lat, lng };
      const done = stop.status === 'done';

      path.push(position);
      bounds.extend(position);

      const marker = new google.maps.Marker({
        position,
        map,
        icon: makeStopIcon(index + 1, done),
        title: stop.name || `Punto ${index + 1}`,
      });

      const info = new google.maps.InfoWindow({
        content: `
          <div style="font-family:Quicksand,Arial,sans-serif;min-width:180px">
            <div style="font-weight:700;color:#111;margin-bottom:4px">
              #${index + 1}. ${safeText(stop.name || 'Punto')}
            </div>
            <div style="font-size:12px;color:#888;margin-bottom:6px">
              ${formatCoord(lat, lng)}
            </div>
            <div style="display:inline-flex;padding:4px 8px;border-radius:999px;font-size:12px;font-weight:700;background:${done ? '#e6ffe6' : '#e6f0ff'};color:${done ? '#15803d' : '#007aff'}">
              ${done ? 'Hecho' : 'Pendiente'}
            </div>
          </div>
        `,
      });

      marker.addListener('click', () => {
        info.open({
          map,
          anchor: marker,
        });
      });

      stopMarkers.push(marker);
    });

    const rawLat = toNum(driver?.last_position?.lat);
    const rawLng = toNum(driver?.last_position?.lng);
    const snapLat = toNum(driver?.last_position?.snap_lat);
    const snapLng = toNum(driver?.last_position?.snap_lng);

    const dlat = snapLat ?? rawLat;
    const dlng = snapLng ?? rawLng;

    const presence = driver?.presence || {};
    const online = presence.state === 'online';

    if (isValid(dlat, dlng)) {
      const driverPosition = {
        lat: dlat,
        lng: dlng,
      };

      bounds.extend(driverPosition);

      if (!driverMarker) {
        driverMarker = new google.maps.Marker({
          position: driverPosition,
          map,
          icon: makeDriverIcon(driver?.last_position?.heading, online),
          title: 'Chofer',
          zIndex: 999,
        });
      } else {
        driverMarker.setPosition(driverPosition);
        driverMarker.setIcon(makeDriverIcon(driver?.last_position?.heading, online));
      }

      const speed = driver?.last_position?.speed ?? null;
      const accuracy = driver?.last_position?.accuracy ?? null;
      const battery = driver?.last_position?.battery ?? null;
      const received = driver?.last_position?.received_at || presence.last_seen_at || '—';

      if (lastDriverInfoWindow) {
        lastDriverInfoWindow.close();
      }

      lastDriverInfoWindow = new google.maps.InfoWindow({
        content: `
          <div style="font-family:Quicksand,Arial,sans-serif;min-width:210px">
            <div style="font-weight:700;color:#111;margin-bottom:4px">
              ${safeText(driver?.name || 'Chofer')}
            </div>

            <div style="font-size:12px;color:#888;margin-bottom:8px">
              ${formatCoord(dlat, dlng)}
            </div>

            <div style="display:grid;gap:4px;font-size:12px;color:#333">
              <div><strong>Estado:</strong> ${online ? 'En línea' : 'Sin conexión'}</div>
              <div><strong>Velocidad:</strong> ${speed !== null ? safeText(speed) : '—'}</div>
              <div><strong>Precisión:</strong> ${accuracy !== null ? safeText(accuracy) + ' m' : '—'}</div>
              <div><strong>Batería:</strong> ${battery !== null ? safeText(battery) + '%' : '—'}</div>
              <div><strong>Último visto:</strong> ${safeText(received)}</div>
            </div>
          </div>
        `,
      });

      driverMarker.addListener('click', () => {
        lastDriverInfoWindow.open({
          map,
          anchor: driverMarker,
        });
      });
    }

    if (routeLine) {
      routeLine.setMap(null);
      routeLine = null;
    }

    if (path.length >= 2) {
      routeLine = new google.maps.Polyline({
        path,
        map,
        geodesic: true,
        strokeColor: '#007aff',
        strokeOpacity: 0.75,
        strokeWeight: 4,
      });
    }

    if (!firstFitDone && !bounds.isEmpty()) {
      map.fitBounds(bounds, 72);
      firstFitDone = true;
    }
  }

  async function poll() {
    try {
      const response = await fetch(POLL_URL, {
        headers: {
          'Accept': 'application/json',
        },
        credentials: 'include',
      });

      const data = await response.json().catch(() => null);

      if (!response.ok || !data) {
        setLive(false, 'Error API (' + response.status + ')');
        return;
      }

      const presenceState = data.driver?.presence?.state || 'offline';

      if (presenceState === 'online') {
        setLive(true, 'En vivo');
      } else {
        setLive(false, 'Chofer sin conexión');
      }

      document.getElementById('serverTime').textContent = data.server_time || '—';
      document.getElementById('kTotal').textContent = data.kpis?.total ?? '—';
      document.getElementById('kDone').textContent = data.kpis?.done ?? '—';
      document.getElementById('kPending').textContent = data.kpis?.pending ?? '—';

      renderStops(data.stops || []);
      renderMap(data.stops || [], data.driver || {});
    } catch (e) {
      setLive(false, 'Sin conexión');
    }
  }

  window.initGoogleSupervisorMap = function () {
    try {
      setMapError(false);

      map = new google.maps.Map(document.getElementById('mapSup'), {
        center: {
          lat: 19.4326,
          lng: -99.1332,
        },
        zoom: 11,
        mapTypeControl: false,
        streetViewControl: false,
        fullscreenControl: true,
        clickableIcons: true,
        gestureHandling: 'greedy',
      });

      poll();
      setInterval(poll, 5000);
    } catch (e) {
      setMapError(true);
      setLive(false, 'Error mapa');
    }
  };

  window.gm_authFailure = function () {
    setMapError(true);
    setLive(false, 'Error API key');
  };
</script>
{{-- DEBUG TEMPORAL --}}
<div style="display:none">
    GOOGLE KEY: {{ config('services.google_maps.browser_key') }}
</div>
<script
  async
  defer
  src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.browser_key') }}&libraries=places&v=weekly&callback=initGoogleSupervisorMap">
</script>
@endsection