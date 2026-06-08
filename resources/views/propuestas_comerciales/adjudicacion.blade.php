@extends('layouts.app')
@section('content_class', 'content--flush')
@section('content')
<link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&display=swap" rel="stylesheet">
<style>
  .adj-page { font-family:'Quicksand',sans-serif; background:#f8fafc; color:#334155; min-height:100vh; padding:32px 24px; }
  .adj-page * { box-sizing:border-box; }
  .adj-wrap { max-width:1000px; margin:0 auto; }
  .adj-page h1 { color:#0f172a; font-size:26px; margin:0 0 6px; font-weight:700; }
  .adj-sub { color:#64748b; font-size:14px; margin:0 0 24px; }
  .back-link { display:inline-flex; align-items:center; gap:8px; color:#64748b; font-weight:600; font-size:14px; margin-bottom:18px; text-decoration:none; }
  .adj-top { display:flex; justify-content:space-between; align-items:flex-start; gap:16px; flex-wrap:wrap; }
  .adj-bar { display:flex; gap:16px; flex-wrap:wrap; margin-bottom:24px; }
  .adj-stat { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:16px 20px; min-width:150px; flex:1; }
  .adj-stat .v { font-size:22px; font-weight:700; color:#0f172a; }
  .adj-stat .l { font-size:12px; color:#64748b; font-weight:600; margin-top:4px; }
  .adj-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:18px 20px; margin-bottom:14px; transition:border-color .2s; }
  .adj-card.is-perdida { border-color:#fca5a5; background:#fef2f2; }
  .adj-card.is-ganada { border-color:#86efac; }
  .adj-head { display:grid; grid-template-columns:34px 1fr auto; gap:14px; align-items:center; }
  .adj-num { font-weight:700; color:#94a3b8; text-align:center; }
  .adj-name { font-size:15px; font-weight:700; color:#0f172a; }
  .adj-meta { font-size:12.5px; color:#64748b; margin-top:3px; }
  .seg { display:inline-flex; background:#f1f5f9; border-radius:9px; padding:3px; }
  .seg button { border:0; background:transparent; font-family:'Quicksand',sans-serif; font-weight:700; font-size:13px; color:#64748b; padding:7px 14px; border-radius:7px; cursor:pointer; transition:.15s; }
  .seg button.on-win { background:#16a34a; color:#fff; }
  .seg button.on-lose { background:#ef4444; color:#fff; }
  .lose-box { display:none; margin-top:16px; padding-top:16px; border-top:1px dashed #fecaca; }
  .adj-card.is-perdida .lose-box { display:block; }
  .grid2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
  .field { display:flex; flex-direction:column; gap:5px; margin-bottom:12px; }
  .field label { font-size:12px; font-weight:700; color:#0f172a; }
  .input { font-family:'Quicksand',sans-serif; font-weight:500; font-size:14px; height:40px; padding:0 12px; background:#fff; border:1px solid #e2e8f0; border-radius:8px; color:#334155; width:100%; }
  textarea.input { height:auto; padding:10px 12px; resize:vertical; }
  .input:focus { outline:none; border-color:#007aff; box-shadow:0 0 0 3px #eff6ff; }
  .diff-pill { display:inline-block; font-size:12.5px; font-weight:700; padding:5px 11px; border-radius:999px; margin-top:6px; }
  .diff-up { background:#fef2f2; color:#ef4444; } .diff-down { background:#f0fdf4; color:#16a34a; }
  .btn { font-family:'Quicksand',sans-serif; font-weight:600; height:40px; padding:0 16px; border-radius:8px; border:1px solid transparent; cursor:pointer; display:inline-flex; align-items:center; gap:8px; font-size:14px; text-decoration:none; }
  .btn-primary { background:#007aff; color:#fff; } .btn-primary:hover { background:#005bb5; }
  .btn-outline { background:#fff; color:#0f172a; border-color:#e2e8f0; } .btn-outline:hover { border-color:#94a3b8; }
  .btn-dark { background:#0f172a; color:#fff; } .btn-dark:hover { background:#1e293b; }
  .btn-small { height:34px; padding:0 13px; font-size:13px; }
  .adj-footer { position:sticky; bottom:0; background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:16px 20px; display:flex; justify-content:space-between; align-items:center; gap:16px; margin-top:20px; box-shadow:0 -6px 20px rgba(15,23,42,.05); flex-wrap:wrap; }
  .loader { display:inline-block; width:14px; height:14px; border:2px solid rgba(255,255,255,.4); border-radius:50%; border-top-color:#fff; animation:spin 1s linear infinite; }
  @keyframes spin { to { transform:rotate(360deg); } }

  /* ===== Modal análisis completo ===== */
  .modal-backdrop { position:fixed; inset:0; z-index:9999; display:none; align-items:center; justify-content:center; padding:20px; background:rgba(15,23,42,.45); backdrop-filter:blur(4px); }
  .modal-backdrop.show { display:flex; }
  .modal { width:min(880px,100%); max-height:calc(100vh - 40px); background:#fff; border-radius:14px; box-shadow:0 24px 80px rgba(15,23,42,.22); display:flex; flex-direction:column; overflow:hidden; }
  .modal-head { padding:20px 24px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:flex-start; gap:16px; }
  .modal-head h2 { margin:0; font-size:19px; color:#0f172a; font-weight:700; }
  .modal-head p { margin:4px 0 0; font-size:13px; color:#64748b; }
  .modal-close { border:0; background:transparent; cursor:pointer; color:#64748b; font-size:22px; line-height:1; padding:4px 8px; border-radius:8px; }
  .modal-close:hover { background:#f1f5f9; color:#0f172a; }
  .modal-body { padding:24px; overflow-y:auto; }
  .modal-foot { padding:16px 24px; border-top:1px solid #e2e8f0; display:flex; justify-content:flex-end; gap:10px; flex-wrap:wrap; }
  .rep-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(130px,1fr)); gap:12px; margin-bottom:22px; }
  .rep-stat { border:1px solid #e2e8f0; border-radius:10px; padding:14px; text-align:center; }
  .rep-stat .v { font-size:20px; font-weight:700; color:#0f172a; }
  .rep-stat .l { font-size:11px; font-weight:600; color:#64748b; margin-top:3px; text-transform:uppercase; letter-spacing:.04em; }
  .rep-section { margin-bottom:24px; }
  .rep-section h3 { font-size:15px; color:#0f172a; margin:0 0 10px; font-weight:700; }
  .rep-text { font-size:13.5px; line-height:1.6; color:#334155; white-space:pre-wrap; }
  .rep-lose { border:1px solid #fecaca; background:#fff; border-radius:10px; padding:14px 16px; margin-bottom:12px; }
  .rep-lose .t { font-size:14px; font-weight:700; color:#0f172a; }
  .rep-lose .m { font-size:12.5px; color:#64748b; margin-top:4px; }
  .rep-lose .a { font-size:13px; color:#334155; margin-top:8px; line-height:1.5; white-space:pre-wrap; }
  .rep-recos { list-style:none; margin:0; padding:0; }
  .rep-recos li { position:relative; padding:10px 12px 10px 34px; border:1px solid #e2e8f0; border-radius:10px; margin-bottom:10px; font-size:13.5px; line-height:1.5; color:#334155; }
  .rep-recos li::before { content:"✓"; position:absolute; left:12px; top:10px; color:#16a34a; font-weight:700; }
  .rep-empty { color:#94a3b8; font-size:14px; }
</style>

<div class="adj-page">
  <div class="adj-wrap">
    <a href="{{ route('propuestas-comerciales.cliente.show', $propuestaComercial) }}" class="back-link">← Volver a la cotización</a>

    <div class="adj-top">
      <div>
        <h1>Generar adjudicación</h1>
        <p class="adj-sub">Folio {{ $folio }} · Marca qué partidas ganaste y cuáles no. Las perdidas se guardan como antecedente.</p>
      </div>
      <button type="button" class="btn btn-outline" onclick="openReportModal()">📊 Ver análisis completo</button>
    </div>

    <div class="adj-bar">
      <div class="adj-stat"><div class="v" id="statGanadas">0</div><div class="l">Ganadas</div></div>
      <div class="adj-stat"><div class="v" id="statPerdidas">0</div><div class="l">Perdidas</div></div>
      <div class="adj-stat"><div class="v" id="statSubtotal">$0.00</div><div class="l">Subtotal ganadas</div></div>
    </div>

    <form id="adjForm" method="POST" action="{{ route('propuestas-comerciales.adjudicacion.store', $propuestaComercial) }}">
      @csrf

      @foreach($items as $i => $it)
        <div class="adj-card is-ganada" data-row="{{ $i }}" data-qty="{{ $it['cantidad'] }}" data-offered="{{ $it['precio_unitario'] }}" data-num="{{ $it['numero'] }}" data-desc="{{ $it['descripcion'] }}" data-unit="{{ $it['unidad'] }}">
          <input type="hidden" name="partidas[{{ $i }}][item_id]" value="{{ $it['id'] }}">
          <input type="hidden" name="partidas[{{ $i }}][resultado]" value="ganada" class="f-resultado">
          <input type="hidden" name="partidas[{{ $i }}][precio_ofertado]" value="{{ $it['precio_unitario'] }}">

          <div class="adj-head">
            <div class="adj-num">{{ $it['numero'] }}</div>
            <div>
              <div class="adj-name">{{ $it['descripcion'] ?: 'Producto sin descripción' }}</div>
              <div class="adj-meta">{{ rtrim(rtrim(number_format($it['cantidad'],2),'0'),'.') }} {{ $it['unidad'] }} · Tu precio ${{ number_format($it['precio_unitario'],2) }} · Subtotal ${{ number_format($it['subtotal'],2) }}</div>
            </div>
            <div class="seg">
              <button type="button" class="seg-win on-win" onclick="setRes({{ $i }},'ganada')">Ganada</button>
              <button type="button" class="seg-lose" onclick="setRes({{ $i }},'perdida')">Perdida</button>
            </div>
          </div>

          <div class="lose-box">
            <div class="grid2">
              <div class="field">
                <label>Licitante ganador</label>
                <input class="input f-proveedor" name="partidas[{{ $i }}][proveedor_ganador]" placeholder="Nombre del Licitante ganador">
              </div>
              <div class="field">
                <label>Precio ganador (unit.)</label>
                <input class="input f-pganador" type="number" step="0.01" min="0" name="partidas[{{ $i }}][precio_ganador]" placeholder="0.00">
              </div>
            </div>
            <div class="field">
              <label>Motivo de la pérdida</label>
              <textarea class="input f-motivo" rows="2" name="partidas[{{ $i }}][motivo_perdida]" placeholder="Ej. Precio más alto, no cumplió ficha técnica, no surtió a tiempo..."></textarea>
            </div>
            <div class="field">
              <label>Análisis (antecedente)</label>
              <textarea class="input f-analisis" rows="3" name="partidas[{{ $i }}][analisis_ia]" placeholder="Pulsa Analizar para generarlo automáticamente."></textarea>
              <div><span class="diff-pill" style="display:none;"></span></div>
              <div style="margin-top:8px;">
                <button type="button" class="btn btn-outline btn-small" onclick="analizar({{ $i }}, this)">⚙ Analizar diferencia</button>
              </div>
            </div>
          </div>
        </div>
      @endforeach

      <div class="adj-footer">
        <div class="adj-meta">Se generará la venta solo con las <strong>ganadas</strong>. Las perdidas quedan como antecedente consultable.</div>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
          <button type="button" class="btn btn-outline" onclick="openReportModal()">📊 Análisis</button>
          <button type="submit" class="btn btn-primary">Generar adjudicación →</button>
        </div>
      </div>
    </form>
  </div>

  {{-- ===== Modal: análisis completo ===== --}}
  <div class="modal-backdrop" id="reportModal">
    <div class="modal">
      <div class="modal-head">
        <div>
          <h2>Análisis completo de la licitación</h2>
          <p>Folio {{ $folio }} · Resumen, partidas no ganadas y plan para ganar la próxima vez.</p>
        </div>
        <button type="button" class="modal-close" onclick="closeReportModal()">✕</button>
      </div>
      <div class="modal-body" id="reportBody"></div>
      <div class="modal-foot">
        <button type="button" class="btn btn-outline" onclick="closeReportModal()">Cerrar</button>
        <button type="button" class="btn btn-dark" onclick="downloadReportPdf()">↓ Descargar PDF</button>
      </div>
    </div>
  </div>
</div>

<script>
  const csrfToken = @json(csrf_token());
  const analizarUrl = @json(route('propuestas-comerciales.adjudicacion.analizar-perdida', $propuestaComercial));
  const reportFolio = @json($folio);
  const reportTitulo = @json($propuestaComercial->titulo ?? 'Adjudicación');

  const money = n => '$' + Number(n||0).toLocaleString('es-MX',{minimumFractionDigits:2, maximumFractionDigits:2});
  const pct = n => Number(n||0).toFixed(2) + '%';
  function escapeHtml(v){ return String(v ?? '').replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;').replaceAll("'","&#039;"); }

  function setRes(i, res) {
    const card = document.querySelector(`.adj-card[data-row="${i}"]`);
    card.querySelector('.f-resultado').value = res;
    card.classList.toggle('is-perdida', res === 'perdida');
    card.classList.toggle('is-ganada', res === 'ganada');
    card.querySelector('.seg-win').classList.toggle('on-win', res === 'ganada');
    card.querySelector('.seg-lose').classList.toggle('on-lose', res === 'perdida');
    recompute();
  }

  function recompute() {
    let g = 0, p = 0, sub = 0;
    document.querySelectorAll('.adj-card').forEach(card => {
      const res = card.querySelector('.f-resultado').value;
      if (res === 'perdida') { p++; return; }
      g++;
      const qty = Number(card.dataset.qty || 0);
      const offered = Number(card.dataset.offered || 0);
      sub += qty * offered;
    });
    document.getElementById('statGanadas').textContent = g;
    document.getElementById('statPerdidas').textContent = p;
    document.getElementById('statSubtotal').textContent = money(sub);
  }

  async function analizar(i, btn) {
    const card = document.querySelector(`.adj-card[data-row="${i}"]`);
    const itemId = card.querySelector('input[name$="[item_id]"]').value;
    const old = btn.innerHTML;
    btn.disabled = true; btn.innerHTML = '<span class="loader"></span> Analizando...';

    try {
      const resp = await fetch(analizarUrl, {
        method:'POST',
        headers:{'X-CSRF-TOKEN':csrfToken,'Accept':'application/json','Content-Type':'application/json'},
        body: JSON.stringify({
          item_id: itemId,
          proveedor_ganador: card.querySelector('.f-proveedor').value,
          precio_ganador: card.querySelector('.f-pganador').value,
          precio_ofertado: card.dataset.offered,
          motivo_perdida: card.querySelector('.f-motivo').value
        })
      });
      const data = await resp.json();
      if (!resp.ok || !data.ok) throw new Error(data.message || 'Error al analizar.');

      card.querySelector('.f-analisis').value = data.analisis_ia || '';
      const pill = card.querySelector('.diff-pill');
      if (data.diferencia_monto !== null && data.diferencia_monto !== undefined) {
        const up = Number(data.diferencia_monto) > 0;
        pill.style.display = 'inline-block';
        pill.className = 'diff-pill ' + (up ? 'diff-up' : 'diff-down');
        pill.textContent = (up ? '▲ ' : '▼ ') + money(Math.abs(data.diferencia_monto)) + ' (' + Math.abs(data.diferencia_pct).toFixed(2) + '%)';
      } else { pill.style.display = 'none'; }
    } catch (e) {
      alert(e.message);
    } finally {
      btn.disabled = false; btn.innerHTML = old;
    }
  }

  /* ===== Recolectar datos del formulario en vivo ===== */
  function collectReport() {
    const ganadas = [], perdidas = [];
    let subtotalGanadas = 0, montoPerdidoPotencial = 0;

    document.querySelectorAll('.adj-card').forEach(card => {
      const res = card.querySelector('.f-resultado').value;
      const qty = Number(card.dataset.qty || 0);
      const offered = Number(card.dataset.offered || 0);
      const base = {
        num: card.dataset.num,
        desc: card.dataset.desc || 'Producto sin descripción',
        unit: card.dataset.unit || 'pz',
        qty,
        offered,
        subtotal: qty * offered
      };

      if (res === 'ganada') {
        subtotalGanadas += base.subtotal;
        ganadas.push(base);
        return;
      }

      const ganador = Number(card.querySelector('.f-pganador').value || 0);
      const proveedor = card.querySelector('.f-proveedor').value.trim();
      const motivo = card.querySelector('.f-motivo').value.trim();
      const analisis = card.querySelector('.f-analisis').value.trim();
      const dif = ganador > 0 ? offered - ganador : null;
      const difPct = ganador > 0 ? ((offered - ganador) / ganador) * 100 : null;

      montoPerdidoPotencial += base.subtotal;
      perdidas.push({ ...base, ganador, proveedor, motivo, analisis, dif, difPct });
    });

    const total = ganadas.length + perdidas.length;
    const tasaExito = total > 0 ? (ganadas.length / total) * 100 : 0;

    const porPrecio = perdidas.filter(p => p.ganador > 0 && p.dif > 0);
    const noPrecio = perdidas.filter(p => p.ganador > 0 && p.dif <= 0);
    const sinDato = perdidas.filter(p => !(p.ganador > 0));

    const promArriba = porPrecio.length
      ? porPrecio.reduce((a, p) => a + p.difPct, 0) / porPrecio.length
      : 0;
    const mayorBrecha = porPrecio.slice().sort((a, b) => b.difPct - a.difPct)[0] || null;

    return {
      ganadas, perdidas, total, tasaExito, subtotalGanadas, montoPerdidoPotencial,
      porPrecio, noPrecio, sinDato, promArriba, mayorBrecha
    };
  }

  function buildDiagnostico(r) {
    if (!r.perdidas.length) {
      return 'Se marcaron todas las partidas como ganadas. ¡Excelente resultado! No hay pérdidas que analizar en esta licitación.';
    }
    const partes = [];
    partes.push(`De ${r.total} partidas participadas se ganaron ${r.ganadas.length} y se perdieron ${r.perdidas.length} (tasa de éxito ${pct(r.tasaExito)}).`);

    if (r.porPrecio.length) {
      partes.push(`El principal factor de pérdida fue el PRECIO: ${r.porPrecio.length} partida(s) quedaron por arriba del licitante ganador, en promedio ${pct(r.promArriba)} más caras.`);
      if (r.mayorBrecha) {
        partes.push(`La mayor brecha fue en "${r.mayorBrecha.desc}", donde ofertamos ${money(r.mayorBrecha.offered)} contra ${money(r.mayorBrecha.ganador)} del ganador (${pct(r.mayorBrecha.difPct)} arriba).`);
      }
    }
    if (r.noPrecio.length) {
      partes.push(`En ${r.noPrecio.length} partida(s) igualamos o mejoramos el precio del ganador y aun así no se ganaron: la causa NO fue económica (revisar técnico, muestras, tiempos o documentación).`);
    }
    if (r.sinDato.length) {
      partes.push(`En ${r.sinDato.length} partida(s) no se capturó el precio del ganador, por lo que falta inteligencia de la competencia para esos casos.`);
    }
    return partes.join(' ');
  }

  function buildRecomendaciones(r) {
    const recos = [];

    if (r.porPrecio.length) {
      const objetivo = r.mayorBrecha
        ? ` Por ejemplo, para "${r.mayorBrecha.desc}" había que bajar a ~${money(r.mayorBrecha.ganador)} o menos.`
        : '';
      recos.push(`Ajustar precio en las ${r.porPrecio.length} partida(s) perdidas por costo: renegociar con el proveedor, buscar otra fuente de surtido o reducir el margen.${objetivo}`);
      recos.push('Pedir cotizaciones de varios proveedores antes de ofertar para tener el costo más bajo posible y poder competir en precio.');
    }
    if (r.noPrecio.length) {
      recos.push(`Revisar el cumplimiento técnico en las ${r.noPrecio.length} partida(s) donde el precio sí era competitivo: validar que la ficha técnica, marca, muestras y tiempos de entrega cumplan exactamente lo solicitado.`);
    }
    if (r.sinDato.length) {
      recos.push(`Conseguir el acta de fallo para registrar el precio y nombre del ganador en las ${r.sinDato.length} partida(s) sin dato, y así afinar próximas ofertas.`);
    }

    recos.push('Construir una base de datos de licitantes ganadores y sus precios por partida, para usarla como referencia en futuras participaciones.');
    recos.push('Guardar este análisis como antecedente: comparar contra próximas licitaciones del mismo cliente para detectar el rango de precios con el que se gana.');

    return recos;
  }

  function renderReportHtml(r) {
    const diag = buildDiagnostico(r);
    const recos = buildRecomendaciones(r);

    const statsHtml = `
      <div class="rep-grid">
        <div class="rep-stat"><div class="v" style="color:#16a34a;">${r.ganadas.length}</div><div class="l">Ganadas</div></div>
        <div class="rep-stat"><div class="v" style="color:#ef4444;">${r.perdidas.length}</div><div class="l">Perdidas</div></div>
        <div class="rep-stat"><div class="v">${pct(r.tasaExito)}</div><div class="l">Tasa de éxito</div></div>
        <div class="rep-stat"><div class="v">${money(r.subtotalGanadas)}</div><div class="l">Subtotal ganado</div></div>
        <div class="rep-stat"><div class="v">${money(r.montoPerdidoPotencial)}</div><div class="l">No ganado</div></div>
      </div>`;

    const perdidasHtml = r.perdidas.length
      ? r.perdidas.map(p => `
          <div class="rep-lose">
            <div class="t">#${escapeHtml(p.num)} · ${escapeHtml(p.desc)}</div>
            <div class="m">
              ${escapeHtml(String(p.qty))} ${escapeHtml(p.unit)} ·
              Tu precio <strong>${money(p.offered)}</strong> ·
              Ganador <strong>${p.ganador > 0 ? money(p.ganador) : '—'}</strong>
              ${p.dif !== null ? ` · Diferencia <strong>${p.dif > 0 ? '▲' : '▼'} ${money(Math.abs(p.dif))} (${pct(Math.abs(p.difPct))})</strong>` : ''}
              ${p.proveedor ? ` · Ganador: ${escapeHtml(p.proveedor)}` : ''}
            </div>
            ${p.motivo ? `<div class="a"><strong>Motivo:</strong> ${escapeHtml(p.motivo)}</div>` : ''}
            ${p.analisis ? `<div class="a">${escapeHtml(p.analisis)}</div>` : ''}
          </div>`).join('')
      : '<p class="rep-empty">No hay partidas marcadas como perdidas.</p>';

    return `
      ${statsHtml}
      <div class="rep-section">
        <h3>Diagnóstico general</h3>
        <div class="rep-text">${escapeHtml(diag)}</div>
      </div>
      <div class="rep-section">
        <h3>Partidas no ganadas (antecedente)</h3>
        ${perdidasHtml}
      </div>
      <div class="rep-section">
        <h3>Cómo solucionarlo y ganar la próxima vez</h3>
        <ul class="rep-recos">
          ${recos.map(t => `<li>${escapeHtml(t)}</li>`).join('')}
        </ul>
      </div>`;
  }

  function openReportModal() {
    const r = collectReport();
    document.getElementById('reportBody').innerHTML = renderReportHtml(r);
    document.getElementById('reportModal').classList.add('show');
  }

  function closeReportModal() {
    document.getElementById('reportModal').classList.remove('show');
  }

  document.getElementById('reportModal').addEventListener('click', e => {
    if (e.target.id === 'reportModal') closeReportModal();
  });

  /* ===== PDF del análisis ===== */
  function buildReportPdfHtml(r) {
    const diag = buildDiagnostico(r);
    const recos = buildRecomendaciones(r);
    const now = new Date().toLocaleString('es-MX');

    const perdidasRows = r.perdidas.length
      ? r.perdidas.map(p => `
          <tr>
            <td class="c">${escapeHtml(p.num)}</td>
            <td>${escapeHtml(p.desc)}${p.motivo ? `<div class="sub"><strong>Motivo:</strong> ${escapeHtml(p.motivo)}</div>` : ''}${p.analisis ? `<div class="sub">${escapeHtml(p.analisis)}</div>` : ''}</td>
            <td class="r">${money(p.offered)}</td>
            <td class="r">${p.ganador > 0 ? money(p.ganador) : '—'}</td>
            <td class="r">${p.dif !== null ? (p.dif > 0 ? '+' : '') + money(p.dif) + '<br>(' + pct(p.difPct) + ')' : '—'}</td>
            <td>${escapeHtml(p.proveedor || '—')}</td>
          </tr>`).join('')
      : `<tr><td colspan="6" class="c">No hay partidas perdidas.</td></tr>`;

    const ganadasRows = r.ganadas.length
      ? r.ganadas.map(g => `
          <tr>
            <td class="c">${escapeHtml(g.num)}</td>
            <td>${escapeHtml(g.desc)}</td>
            <td class="c">${escapeHtml(String(g.qty))} ${escapeHtml(g.unit)}</td>
            <td class="r">${money(g.offered)}</td>
            <td class="r">${money(g.subtotal)}</td>
          </tr>`).join('')
      : `<tr><td colspan="5" class="c">No hay partidas ganadas.</td></tr>`;

    return `
      <!doctype html><html lang="es"><head><meta charset="UTF-8">
      <title>Análisis ${escapeHtml(reportFolio)}</title>
      <style>
        @page { size: letter; margin: 14mm; }
        * { box-sizing:border-box; }
        body { font-family: Arial, Helvetica, sans-serif; color:#1f2937; margin:0; font-size:11px; }
        h1 { font-size:20px; margin:0 0 4px; color:#0f172a; }
        h2 { font-size:14px; margin:22px 0 8px; color:#0f172a; border-bottom:2px solid #0f172a; padding-bottom:4px; }
        .meta { color:#6b7280; font-size:10px; margin-bottom:14px; }
        .cards { display:flex; flex-wrap:wrap; gap:10px; margin:10px 0 4px; }
        .card { border:1px solid #e5e7eb; border-radius:8px; padding:10px 14px; text-align:center; min-width:110px; }
        .card .v { font-size:16px; font-weight:bold; color:#0f172a; }
        .card .l { font-size:9px; color:#6b7280; text-transform:uppercase; margin-top:2px; }
        p.diag { font-size:11.5px; line-height:1.55; }
        table { width:100%; border-collapse:collapse; margin-top:6px; }
        th, td { border:1px solid #e5e7eb; padding:6px 7px; vertical-align:top; text-align:left; }
        th { background:#f3f4f6; font-size:10px; color:#111; }
        td { font-size:10px; }
        td.r { text-align:right; } td.c { text-align:center; }
        .sub { color:#6b7280; font-size:9px; margin-top:3px; line-height:1.4; }
        ol.recos { padding-left:18px; } ol.recos li { margin-bottom:7px; font-size:11px; line-height:1.5; }
        .foot { margin-top:18px; border-top:1px solid #e5e7eb; padding-top:8px; color:#6b7280; font-size:9px; }
      </style></head><body>
        <h1>Análisis de licitación</h1>
        <div class="meta"><strong>${escapeHtml(reportTitulo)}</strong> · Folio: ${escapeHtml(reportFolio)} · Generado: ${escapeHtml(now)}</div>

        <div class="cards">
          <div class="card"><div class="v" style="color:#16a34a;">${r.ganadas.length}</div><div class="l">Ganadas</div></div>
          <div class="card"><div class="v" style="color:#dc2626;">${r.perdidas.length}</div><div class="l">Perdidas</div></div>
          <div class="card"><div class="v">${pct(r.tasaExito)}</div><div class="l">Tasa de éxito</div></div>
          <div class="card"><div class="v">${money(r.subtotalGanadas)}</div><div class="l">Subtotal ganado</div></div>
          <div class="card"><div class="v">${money(r.montoPerdidoPotencial)}</div><div class="l">No ganado</div></div>
        </div>

        <h2>Diagnóstico general</h2>
        <p class="diag">${escapeHtml(diag)}</p>

        <h2>Partidas ganadas (venta)</h2>
        <table>
          <thead><tr><th style="width:40px;">#</th><th>Descripción</th><th style="width:90px;">Cantidad</th><th style="width:90px;">P. Unit.</th><th style="width:100px;">Subtotal</th></tr></thead>
          <tbody>${ganadasRows}</tbody>
        </table>

        <h2>Partidas no ganadas (antecedente)</h2>
        <table>
          <thead><tr><th style="width:40px;">#</th><th>Descripción / motivo / análisis</th><th style="width:80px;">Tu precio</th><th style="width:80px;">Ganador</th><th style="width:90px;">Diferencia</th><th style="width:120px;">Licitante ganador</th></tr></thead>
          <tbody>${perdidasRows}</tbody>
        </table>

        <h2>Cómo solucionarlo y ganar la próxima vez</h2>
        <ol class="recos">
          ${recos.map(t => `<li>${escapeHtml(t)}</li>`).join('')}
        </ol>

        <div class="foot">Documento generado automáticamente como antecedente interno. Las partidas perdidas no afectan la venta; se conservan para análisis de competencia.</div>
      </body></html>`;
  }

  function downloadReportPdf() {
    const r = collectReport();
    const html = buildReportPdfHtml(r);
    const win = window.open('', '_blank');

    if (!win) {
      const blob = new Blob([html], { type: 'text/html;charset=utf-8' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = 'analisis_' + String(reportFolio).replace(/[^\w\-]+/g, '_') + '.html';
      document.body.appendChild(a); a.click(); document.body.removeChild(a);
      setTimeout(() => URL.revokeObjectURL(url), 1000);
      return;
    }

    win.document.open();
    win.document.write(html);
    win.document.close();
    win.focus();
    setTimeout(() => win.print(), 450);
  }

  recompute();
</script>
@endsection