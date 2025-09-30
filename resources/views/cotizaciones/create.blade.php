@extends('layouts.app')
@section('title','Nueva cotización')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<style>
:root{
  --bg:#f6f7fb; --card:#fff; --ink:#0f172a; --muted:#64748b; --line:#e2e8f0; --brand:#2563eb;
  --ok:#16a34a; --warn:#d97706; --bad:#b91c1c
}
*{box-sizing:border-box}
.wrap{max-width:1200px;margin:24px auto;padding:0 14px;font-family:Inter,system-ui}

/* Tabs */
.tabs{display:flex;gap:8px;margin-bottom:14px}
.tab{padding:10px 14px;border:1px solid var(--line);border-radius:10px;background:#fff;cursor:pointer}
.tab.is-active{background:#eaf1ff;border-color:#cfe3ff;color:#0f172a;font-weight:700}
.tab-panels{}

/* Grid layout */
.layout{display:grid;grid-template-columns:2fr 1fr;gap:16px}
.panel{background:var(--card);border:1px solid var(--line);border-radius:16px;box-shadow:0 12px 32px rgba(2,6,23,.06);overflow:hidden}
.head{padding:16px 18px;border-bottom:1px solid var(--line);display:flex;justify-content:space-between;align-items:center}
.head h2,.head h3{margin:0;color:var(--ink)}
.body{padding:18px}
.sep{border:none;border-top:1px solid var(--line);margin:16px 0}
.input,select,textarea{width:100%;padding:10px 12px;border:1px solid var(--line);border-radius:10px;background:#fff}
.grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.row{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.btn{display:inline-flex;gap:8px;align-items:center;padding:10px 14px;border-radius:10px;border:1px solid var(--line);background:#fff;cursor:pointer}
.btn.brand{background:#eaf1ff;border-color:#cfe3ff}
.btn.save{background:#0ea5e9;color:white;border-color:#0ea5e9}
.btn.ghost{background:#fff}
.badge{padding:4px 10px;border:1px solid var(--line);border-radius:999px;font-size:12px;color:#334155;background:#f8fafc}
.small{font-size:12px;color:#64748b}

.table{width:100%;border-collapse:collapse;margin-top:12px;background:#fff;border:1px solid var(--line);border-radius:12px;overflow:hidden}
.table th,.table td{border-bottom:1px solid var(--line);padding:10px;text-align:left;font-size:14px}
.table th{background:#f8fafc;color:#0f172a}

.kv{display:grid;grid-template-columns:110px 1fr;gap:8px;margin:6px 0}
.aside-sticky{position:sticky; top:16px}

.totals table{width:100%;border-collapse:collapse}
.totals td{padding:6px 0;border-bottom:1px dashed var(--line)}
.totals tr:last-child td{border-bottom:none}
.totals .sum{font-weight:700;color:var(--ink)}

/* Smart Dropdown */
.sdrop{position:relative}
.sdrop-input{width:100%;padding:12px 14px;border:1px solid var(--line);border-radius:12px;outline:none}
.sdrop-list{position:absolute;z-index:40;left:0;right:0;top:100%;margin-top:6px;background:#fff;border:1px solid var(--line);border-radius:12px;box-shadow:0 10px 26px rgba(2,6,23,.10);max-height:360px;overflow:auto}
.sdrop-item{display:grid;grid-template-columns:48px 1fr auto;gap:12px;align-items:center;padding:10px 12px;border-bottom:1px solid #f1f5f9;cursor:pointer}
.sdrop-item:last-child{border-bottom:none}
.sdrop-item:hover,.sdrop-item.is-active{background:#f1f5ff}
.sdrop-thumb{width:48px;height:48px;border-radius:10px;background:#f1f5f9;object-fit:cover;display:block}
.sdrop-main{min-width:0}
.sdrop-title{font-weight:700;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.sdrop-sub{font-size:12px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.sdrop-right{font-size:12px;white-space:nowrap}
.badge-green{background:#e9fce9;border:1px solid #cdeccd;color:#166534;padding:4px 8px;border-radius:999px}

/* IA Panel */
.ai-row{display:grid;grid-template-columns:1fr auto;gap:10px;align-items:end}
.ai-note{background:#f1f5ff;border:1px solid #cfe3ff;color:#0f172a;padding:10px 12px;border-radius:10px;font-size:13px}
.ai-log{background:#f9fafb;border:1px solid var(--line);border-radius:10px;padding:10px 12px;font-size:13px;max-height:240px;overflow:auto;white-space:pre-wrap}
.warn{color:#b45309}
.good{color:#166534}
</style>

<div class="wrap">
  <!-- Tabs -->
  <div class="tabs" role="tablist" aria-label="Modo de creación">
    <button class="tab is-active" id="tab-manual" role="tab" aria-controls="panel-manual" aria-selected="true">Manual</button>
    <button class="tab" id="tab-ai" role="tab" aria-controls="panel-ai" aria-selected="false">Desde PDF (IA)</button>
  </div>

  <div class="tab-panels">
    <!-- ===========================
         PANEL MANUAL
    ============================ -->
    <section id="panel-manual" role="tabpanel" aria-labelledby="tab-manual">
      <form method="POST" action="{{ route('cotizaciones.store') }}" id="form">
        @csrf
        <div class="layout">
          <!-- Columna izquierda -->
          <div class="panel">
            <div class="head"><h2>Nueva cotización</h2></div>
            <div class="body">

              {{-- 1) Productos (arriba) --}}
              <h3 style="margin:0 0 8px 0">Productos</h3>
              <div class="sdrop" id="sd-producto">
                <input type="text" class="sdrop-input" id="producto_search" placeholder="Buscar producto..." autocomplete="off">
                <div class="sdrop-list" hidden></div>
              </div>

              <table class="table" id="items">
                <thead>
                  <tr>
                    <th style="width:34%">Producto</th>
                    <th style="width:10%">Cant.</th>
                    <th style="width:14%">P. Unit.</th>
                    <th style="width:12%">Desc.</th>
                    <th style="width:10%">IVA%</th>
                    <th style="width:14%">Importe</th>
                    <th style="width:6%"></th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>

              <div class="grid" style="margin-top:12px">
                <div>
                  <label>Descuento global</label>
                  <input class="input" type="number" step="0.01" name="descuento" id="desc_global" value="0">
                </div>
                <div>
                  <label>Envío</label>
                  <input class="input" type="number" step="0.01" name="envio" id="envio" value="0">
                </div>
              </div>

              <hr class="sep">

              {{-- 2) Validez y Notas (abajo) --}}
              <div class="grid">
                <div>
                  <label>Validez (días)</label>
                  <input class="input" type="number" min="0" name="validez_dias" id="validez_dias" value="15">
                </div>
                <div></div>
              </div>

              <div style="margin-top:12px">
                <label>Notas</label>
                <textarea class="input" name="notas" id="notas" rows="3" placeholder="Notas visibles en la cotización"></textarea>
              </div>

              <div class="actions" style="margin-top:12px">
                <button class="btn save" type="submit">Guardar cotización</button>
              </div>

              <input type="hidden" name="items" id="items_json">
            </div>
          </div>

          <!-- Columna derecha -->
          <div class="aside-sticky">
            <!-- Cliente -->
            <div class="panel" style="margin-bottom:16px">
              <div class="head"><h3>Cliente</h3><span class="badge" id="cli-id">—</span></div>
              <div class="body">
                <div class="sdrop" id="sd-cliente-side" style="margin-bottom:10px">
                  <input type="text" class="sdrop-input" id="cliente_search" placeholder="Buscar cliente..." autocomplete="off">
                  <input type="hidden" name="cliente_id" id="cliente_id">
                  <div class="sdrop-list" hidden></div>
                </div>

                <div class="kv"><div class="muted">Nombre</div><div id="cli-nombre">—</div></div>
                <div class="kv"><div class="muted">Email</div><div id="cli-email">—</div></div>
                <div class="kv"><div class="muted">Teléfono</div><div id="cli-telefono">—</div></div>
                <div class="kv"><div class="muted">RFC/NIT</div><div id="cli-rfc">—</div></div>
                <div class="kv"><div class="muted">Dirección</div><div id="cli-direccion">—</div></div>
                <div class="kv"><div class="muted">Ciudad / Estado</div><div id="cli-ubicacion">—</div></div>
                <div class="kv"><div class="muted">CP</div><div id="cli-cp">—</div></div>
              </div>
            </div>

            <!-- Resumen -->
            <div class="panel totals" style="margin-bottom:16px">
              <div class="head"><h3>Resumen</h3></div>
              <div class="body">
                <table>
                  <tr><td>Subtotal</td><td class="sum" style="text-align:right" id="t_subtotal">$0.00</td></tr>
                  <tr><td>IVA</td>     <td class="sum" style="text-align:right" id="t_iva">$0.00</td></tr>
                  <tr><td>Descuento</td><td style="text-align:right" id="t_desc_global">$0.00</td></tr>
                  <tr><td>Envío</td>   <td style="text-align:right" id="t_envio">$0.00</td></tr>
                  <tr><td class="sum">TOTAL</td><td class="sum" style="text-align:right" id="t_total">$0.00</td></tr>
                </table>
              </div>
            </div>

            <!-- Financiamiento -->
            <div class="panel">
              <div class="head"><h3>Financiamiento</h3></div>
              <div class="body">
                <label style="display:flex;gap:8px;align-items:center;margin-bottom:10px">
                  <input type="checkbox" name="financiamiento[aplicar]" id="fin_aplicar" value="1">
                  Aplicar financiamiento
                </label>

                <div class="grid" style="margin-bottom:10px">
                  <div>
                    <label>Plazos</label>
                    <input class="input" type="number" min="1" id="fin_plazos" name="financiamiento[numero_plazos]" placeholder="Ej. 6" disabled>
                  </div>
                  <div>
                    <label>Enganche</label>
                    <input class="input" type="number" step="0.01" id="fin_enganche" name="financiamiento[enganche]" value="0" disabled>
                  </div>
                  <div>
                    <label>Tasa anual (%)</label>
                    <input class="input" type="number" step="0.01" id="fin_tasa" name="financiamiento[tasa_anual]" placeholder="Ej. 18" disabled>
                  </div>
                  <div>
                    <label>Primer vencimiento</label>
                    <input class="input" type="date" id="fin_inicio" name="financiamiento[primer_vencimiento]" disabled>
                  </div>
                </div>

                <div id="plan_wrap" style="display:none">
                  <table class="table" id="plan_table" style="margin-top:0">
                    <thead><tr><th>#</th><th>Vence</th><th>Monto</th></tr></thead>
                    <tbody></tbody>
                  </table>
                  <div class="small">Las cuotas se calculan como <em>(Total – Enganche) / Plazos</em>. La tasa es informativa (el backend actual no aplica interés).</div>
                </div>
              </div>
            </div>
          </div>
        </div> <!-- /layout -->
      </form>
    </section>

    <!-- ===========================
         PANEL IA DESDE PDF
    ============================ -->
    <section id="panel-ai" role="tabpanel" aria-labelledby="tab-ai" hidden>
      <div class="panel">
        <div class="head"><h2>Cargar PDF para generar cotización</h2></div>
        <div class="body">
          <div class="ai-note">
            La IA leerá todo el PDF (aunque tenga distintos formatos), detectará las páginas con requisitos/precios y extraerá los conceptos.
          </div>

          <div class="ai-row" style="margin-top:12px">
            <div>
              <label>Archivo PDF</label>
              <input class="input" type="file" id="pdf_file" accept="application/pdf">
              <div class="small">Máximo 20 MB. Si es escaneado, intentará OCR.</div>
            </div>
            <div>
              <button class="btn brand" id="btn_parse">Analizar PDF con IA</button>
            </div>
          </div>

          <hr class="sep">

          <div id="ai_result" style="display:none">
            <div class="grid">
              <div>
                <div class="badge">Resumen IA</div>
                <div class="kv"><div class="muted">Cliente</div><div id="ai_cliente">—</div></div>
                <div class="kv"><div class="muted">Items detectados</div><div id="ai_items_count">0</div></div>
                <div class="kv"><div class="muted">Páginas relevantes</div><div id="ai_pages">—</div></div>
                <div class="kv"><div class="muted">Notas</div><div id="ai_notas">—</div></div>
                <div class="kv"><div class="muted">Validez (días)</div><div id="ai_validez">—</div></div>
                <div class="kv"><div class="muted">Descuento</div><div id="ai_descuento">—</div></div>
                <div class="kv"><div class="muted">Envío</div><div id="ai_envio">—</div></div>
              </div>
              <div>
                <div class="badge">Diagnóstico</div>
                <div class="small">OCR usado: <span id="ai_ocr" class="warn">no</span></div>
                <div class="small">Motivo de selección de páginas:</div>
                <div class="ai-log" id="ai_reason">—</div>
              </div>
            </div>

            <div id="ai_skipped" class="small warn" style="display:none;margin-top:8px"></div>

            <div class="row" style="margin-top:12px;justify-content:flex-end">
              <button class="btn ghost" id="btn_apply">Aplicar al formulario</button>
              <button class="btn save" id="btn_apply_and_switch">Aplicar y pasar a Manual</button>
            </div>
          </div>

          <div id="ai_status" class="small" style="margin-top:12px;color:#334155"></div>
        </div>
      </div>
    </section>
  </div> <!-- /tab-panels -->
</div>

<script>
// ======= Datos desde PHP =======
const CLIENTES_INFO  = @json($clientesInfo->keyBy('id'));
const CLIENTES_SELECT= @json($clientesSelect); // [{id, display}]
const PRODUCTOS_RAW  = @json($productos);      // [{id, display, price, image, brand, category, color, material, stock}]

// ======= Tabs =======
const tabManual = document.getElementById('tab-manual');
const tabAI     = document.getElementById('tab-ai');
const panelManual = document.getElementById('panel-manual');
const panelAI     = document.getElementById('panel-ai');

tabManual.addEventListener('click', ()=>{
  tabManual.classList.add('is-active'); tabAI.classList.remove('is-active');
  panelManual.hidden = false; panelAI.hidden = true;
  tabManual.setAttribute('aria-selected','true'); tabAI.setAttribute('aria-selected','false');
});
tabAI.addEventListener('click', ()=>{
  tabAI.classList.add('is-active'); tabManual.classList.remove('is-active');
  panelAI.hidden = false; panelManual.hidden = true;
  tabAI.setAttribute('aria-selected','true'); tabManual.setAttribute('aria-selected','false');
});

// ---------- Utils ----------
const normalize = (s) => (s??'').toString()
  .normalize('NFD').replace(/[\u0300-\u036f]/g,'')
  .toLowerCase().replace(/\s+/g,' ').trim();
const money = (n)=> '$' + (Number(n||0).toFixed(2));
function nextMonthISO(){ const d=new Date(); return new Date(d.getFullYear(), d.getMonth()+1, d.getDate()).toISOString().slice(0,10); }
function addMonthsISO(iso, m){ const d=iso?new Date(iso):new Date(); const r=new Date(d.getFullYear(), d.getMonth()+m, d.getDate()); return r.toISOString().slice(0,10); }
function esDate(iso){ return /^\d{4}-\d{2}-\d{2}$/.test(iso); }
const pick = (o, keys, def=null)=>{ for(const k of keys){ if(o && o[k]!=null && o[k]!=='' ) return o[k]; } return def; };

// ======= SmartDropdown genérico =======
function smartDropdown(root, items, {renderItem, onPick, getSearchText, placeholder='Buscar...', showOnFocus=true}){
  const input = root.querySelector('.sdrop-input');
  const list  = root.querySelector('.sdrop-list');
  let idx = -1;
  const ALPHA = [...items].sort((a,b)=> getSearchText(a).localeCompare(getSearchText(b)));
  function open(){ list.hidden=false; }
  function close(){ list.hidden=true; idx=-1; }
  function clear(){ list.innerHTML=''; }
  function score(item, q){
    if(!q) return 0;
    const hay = getSearchText(item);
    const tokens = q.split(' ');
    let s=0, starts=0;
    for(const t of tokens){
      const pos = hay.indexOf(t);
      if(pos === -1) return -Infinity;
      s += (100 - Math.min(pos,100)); if(pos===0) starts+=50;
    }
    return s+starts;
  }
  function refresh(){
    const qn = normalize(input.value);
    const base = qn ? items : ALPHA;
    const filtered = base.map(it=>({it, sc:score(it,qn)}))
      .filter(x=> qn ? x.sc>-Infinity : true)
      .sort((a,b)=> qn ? (b.sc-a.sc || getSearchText(a.it).localeCompare(getSearchText(b.it)))
                      : getSearchText(a.it).localeCompare(getSearchText(b.it)))
      .slice(0,50).map(x=>x.it);
    clear();
    for(const [i,it] of filtered.entries()){
      const el = renderItem(it);
      el.classList.add('sdrop-item');
      if(i===idx) el.classList.add('is-active');
      el.addEventListener('mousedown', e=>{ e.preventDefault(); onPick(it); close(); });
      list.appendChild(el);
    }
    if(filtered.length===0){
      const empty=document.createElement('div'); empty.className='sdrop-item';
      empty.innerHTML='<div class="sdrop-main"><div class="sdrop-sub">Sin resultados</div></div>'; list.appendChild(empty);
    }
  }
  input.placeholder=placeholder;
  input.addEventListener('input', refresh);
  if(showOnFocus){
    input.addEventListener('focus', ()=>{ open(); refresh(); });
  } else {
    input.addEventListener('focus', ()=>{ /* NO abrir automáticamente */ });
  }
  input.addEventListener('blur', ()=> setTimeout(close,120));
  input.addEventListener('keydown', e=>{
    const count=list.children.length;
    if(e.key==='ArrowDown'){ e.preventDefault(); open(); idx=Math.min(count-1,idx+1); highlight(); }
    else if(e.key==='ArrowUp'){ e.preventDefault(); idx=Math.max(0,idx-1); highlight(); }
    else if(e.key==='Enter'){ if(!list.hidden && idx>=0 && idx<count){ e.preventDefault(); list.children[idx].dispatchEvent(new Event('mousedown')); } }
    else if(e.key==='Escape'){ close(); }
  });
  function highlight(){
    [...list.children].forEach((c,i)=> c.classList.toggle('is-active', i===idx));
    const it=list.children[idx]; if(it){ const r=it.getBoundingClientRect(); list.scrollTop += (r.top - (list.getBoundingClientRect().top + 8)); }
  }
  return {open,close,refresh,input,list};
}

// ---------- Cliente (tarjeta lateral) ----------
const CLIENTES_ITEMS = CLIENTES_SELECT.map(c=>({
  id:c.id, display:c.display,
  search: normalize([c.display, CLIENTES_INFO[c.id]?.email, CLIENTES_INFO[c.id]?.telefono].filter(Boolean).join(' '))
}));

const sdCliente = smartDropdown(document.getElementById('sd-cliente-side'), CLIENTES_ITEMS, {
  placeholder:'Buscar cliente...',
  showOnFocus:false, // <— NO abrir auto al enfocar
  getSearchText: it=> it.search || normalize(it.display),
  renderItem: it=>{
    const div=document.createElement('div');
    div.innerHTML=`<div class="sdrop-main">
      <div class="sdrop-title">${it.display}</div>
      <div class="sdrop-sub">${CLIENTES_INFO[it.id]?.email ?? ''} ${CLIENTES_INFO[it.id]?.telefono ?? ''}</div>
    </div>`;
    return div;
  },
  onPick: it=>{
    document.getElementById('cliente_id').value = it.id;
    document.getElementById('cliente_search').value =
      CLIENTES_INFO[it.id]?.name ?? CLIENTES_INFO[it.id]?.nombre ?? it.display;
    actualizarTarjetaCliente();
  }
});
// NO auto-open ni auto-refresh al cargar (queda discreto)

// ---------- Productos (con imagen + meta) ----------
const PRODUCTOS = PRODUCTOS_RAW.map(p=>{
  const label   = pick(p, ['display','name','nombre','titulo','title'], `Producto #${p.id}`);
  const image   = pick(p, ['image','imagen','foto','thumb','thumbnail'], null);
  const brand   = pick(p, ['brand','marca'], null);
  const category= pick(p, ['category','categoria'], null);
  const color   = pick(p, ['color','colour'], null);
  const material= pick(p, ['material'], null);
  const stock   = p.stock ?? p.existencia ?? null;
  const price   = Number(p.price || p.precio || 0);
  return { id:p.id, label, image, brand, category, color, material, stock, price,
           search: normalize([label, brand, category, color, material].filter(Boolean).join(' ')) };
});

const sdProducto = smartDropdown(document.getElementById('sd-producto'), PRODUCTOS, {
  placeholder:'Buscar producto...',
  getSearchText: it=> it.search,
  renderItem: it=>{
    const div=document.createElement('div');
    const img = it.image ? `<img class="sdrop-thumb" src="${it.image}" alt="">` : `<div class="sdrop-thumb"></div>`;
    const metaParts = [];
    if(it.brand) metaParts.push(it.brand);
    if(it.category) metaParts.push(it.category);
    if(it.color) metaParts.push(it.color);
    if(it.material) metaParts.push(it.material);
    const meta = metaParts.join(' • ');
    const stock = it.stock!=null ? `<span class="badge-green">${it.stock} ${it.stock==1?'unidad':'unidades'}</span>` : '';
    div.innerHTML=`${img}
      <div class="sdrop-main">
        <div class="sdrop-title">${it.label}</div>
        <div class="sdrop-sub">${ meta ? meta : '&nbsp;' }</div>
        <div class="sdrop-sub">${money(it.price)}</div>
      </div>
      <div class="sdrop-right">${stock}</div>`;
    return div;
  },
  onPick: it=>{
    agregarItemDesdeProducto(it);
    sdProducto.input.value=''; sdProducto.refresh(); sdProducto.open();
  }
});

// ======= Tarjeta cliente =======
function actualizarTarjetaCliente(){
  const id=document.getElementById('cliente_id').value;
  const c=CLIENTES_INFO[id]; const safe=v=> (v ?? '—');
  document.getElementById('cli-id').textContent = id || '—';
  if(!c){ ['cli-nombre','cli-email','cli-telefono','cli-rfc','cli-direccion','cli-ubicacion','cli-cp'].forEach(i=>document.getElementById(i).textContent='—'); return; }
  const nombre=pick(c,['name','nombre','razon_social'])||`ID ${id}`;
  const email =pick(c,['email','correo','mail']);
  const tel   =pick(c,['phone','telefono','mobile','celular','phone_number']);
  const rfc   =pick(c,['rfc','tax_id','nit','ruc']);
  const calle =pick(c,['address','direccion','street','domicilio']);
  const ciudad=pick(c,['city','ciudad']);
  const estado=pick(c,['state','estado']);
  const cp    =pick(c,['zip','cp','postal_code']);
  document.getElementById('cli-nombre').textContent=safe(nombre);
  document.getElementById('cli-email').textContent=safe(email);
  document.getElementById('cli-telefono').textContent=safe(tel);
  document.getElementById('cli-rfc').textContent=safe(rfc);
  document.getElementById('cli-direccion').textContent=safe(calle);
  document.getElementById('cli-ubicacion').textContent=(ciudad||estado)?`${safe(ciudad)} ${estado?'/ '+estado:''}`:'—';
  document.getElementById('cli-cp').textContent=safe(cp);
}

// ======= Items & Totales =======
const $itemsBody=document.querySelector('#items tbody');
const $itemsJson=document.getElementById('items_json');

function agregarItemDesdeProducto(prod){
  const tr=document.createElement('tr');
  tr.innerHTML=`
    <td><input type="hidden" class="it_producto_id" value="${prod.id}">
        <input type="text" class="it_descripcion" value="${prod.label}" style="width:100%;padding:6px 8px;border:1px solid #e5e7eb;border-radius:8px"></td>
    <td><input type="number" class="it_cantidad" value="1" step="0.01" style="width:100%"></td>
    <td><input type="number" class="it_precio" value="${(prod.price||0).toFixed(2)}" step="0.01" style="width:100%"></td>
    <td><input type="number" class="it_descuento" value="0" step="0.01" style="width:100%"></td>
    <td><input type="number" class="it_iva" value="16" step="0.01" style="width:100%"></td>
    <td class="it_importe" style="text-align:right">$0.00</td>
    <td><button type="button" class="btn" onclick="this.closest('tr').remove(); serializar(); recalcularTotales();">Quitar</button></td>`;
  $itemsBody.appendChild(tr);
  recalcularFila(tr); serializar(); recalcularTotales();
}

function recalcularFila(tr){
  const cant=parseFloat(tr.querySelector('.it_cantidad').value||0);
  const p=parseFloat(tr.querySelector('.it_precio').value||0);
  const d=parseFloat(tr.querySelector('.it_descuento').value||0);
  const iva=parseFloat(tr.querySelector('.it_iva').value||16);
  const base=Math.max(0,(p*cant)-d);
  const imp=base*(iva/100)+base;
  tr.querySelector('.it_importe').textContent = money(imp);
}

function serializar(){
  const rows=[...$itemsBody.querySelectorAll('tr')].map(tr=>({
    producto_id: tr.querySelector('.it_producto_id').value,
    descripcion: tr.querySelector('.it_descripcion').value,
    cantidad: parseFloat(tr.querySelector('.it_cantidad').value||0),
    precio_unitario: parseFloat(tr.querySelector('.it_precio').value||0),
    descuento: parseFloat(tr.querySelector('.it_descuento').value||0),
    iva_porcentaje: parseFloat(tr.querySelector('.it_iva').value||16)
  }));
  $itemsJson.value=JSON.stringify(rows);
}

function calcularTotales(){
  let subtotal=0, ivaSum=0;
  [...$itemsBody.querySelectorAll('tr')].forEach(tr=>{
    const cant=parseFloat(tr.querySelector('.it_cantidad').value||0);
    const p=parseFloat(tr.querySelector('.it_precio').value||0);
    const d=parseFloat(tr.querySelector('.it_descuento').value||0);
    const iva=parseFloat(tr.querySelector('.it_iva').value||16);
    const base=Math.max(0,(p*cant)-d);
    subtotal+=base; ivaSum+=base*(iva/100);
  });
  const descG=parseFloat(document.getElementById('desc_global').value||0);
  const envio=parseFloat(document.getElementById('envio').value||0);
  const total=Math.max(0, subtotal - descG + envio + ivaSum);
  return {subtotal, ivaSum, descG, envio, total};
}
function pintarTotales(t){
  document.getElementById('t_subtotal').textContent=money(t.subtotal);
  document.getElementById('t_iva').textContent=money(t.ivaSum);
  document.getElementById('t_desc_global').textContent=money(t.descG);
  document.getElementById('t_envio').textContent=money(t.envio);
  document.getElementById('t_total').textContent=money(t.total);
}
function recalcularTotales(){ const t=calcularTotales(); pintarTotales(t); recalcularPlan(); }

$itemsBody.addEventListener('input', e=>{ const tr=e.target.closest('tr'); if(tr){recalcularFila(tr); serializar(); recalcularTotales();}});
document.getElementById('desc_global').addEventListener('input', recalcularTotales);
document.getElementById('envio').addEventListener('input', recalcularTotales);

// ======= Financiamiento (preview) =======
const fin = {
  aplicar: document.getElementById('fin_aplicar'),
  plazos:  document.getElementById('fin_plazos'),
  eng:     document.getElementById('fin_enganche'),
  tasa:    document.getElementById('fin_tasa'),
  inicio:  document.getElementById('fin_inicio'),
  wrap:    document.getElementById('plan_wrap'),
  table:   document.getElementById('plan_table').querySelector('tbody'),
};
function setDisabledFin(dis){ [fin.plazos,fin.eng,fin.tasa,fin.inicio].forEach(el=> el.disabled=dis); }
fin.aplicar.addEventListener('change', ()=>{
  setDisabledFin(!fin.aplicar.checked);
  fin.wrap.style.display = fin.aplicar.checked ? '' : 'none';
  if(fin.aplicar.checked && !fin.inicio.value){ fin.inicio.value = nextMonthISO(); }
  recalcularPlan();
});
[fin.plazos,fin.eng,fin.tasa,fin.inicio].forEach(el=> el.addEventListener('input', recalcularPlan));

function recalcularPlan(){
  if(!fin.aplicar.checked) return;
  const t=calcularTotales();
  const n=Math.max(1, parseInt(fin.plazos.value||0));
  const eng=parseFloat(fin.eng.value||0);
  const base=Math.max(0, t.total - eng);
  const cuota = n>0 ? (Math.round((base/n)*100)/100) : 0;

  fin.table.innerHTML='';
  if(base<=0 || n<1){
    fin.table.innerHTML=`<tr><td colspan="3" class="small">Agrega productos y define plazos para ver el calendario.</td></tr>`;
    return;
  }
  const startISO = esDate(fin.inicio.value) ? fin.inicio.value : nextMonthISO();
  for(let i=0;i<n;i++){
    const vence = addMonthsISO(startISO, i);
    const tr=document.createElement('tr');
    tr.innerHTML=`<td>${i+1}</td><td>${vence.split('-').reverse().join('/')}</td><td>${money(cuota)}</td>`;
    fin.table.appendChild(tr);
  }
  fin.wrap.style.display='';
}

// ======= Envío backend (manual) =======
document.getElementById('form').addEventListener('submit', e=>{
  serializar();
  if(!$itemsJson.value || $itemsJson.value==='[]'){ e.preventDefault(); alert('Agrega al menos un producto.'); }
});

// ======= IA: Parse PDF y Aplicar =======
const pdfInput = document.getElementById('pdf_file');
const btnParse = document.getElementById('btn_parse');
const aiResBox = document.getElementById('ai_result');
const aiStatus = document.getElementById('ai_status');

const aiCliente   = document.getElementById('ai_cliente');
const aiItemsCnt  = document.getElementById('ai_items_count');
const aiPages     = document.getElementById('ai_pages');
const aiNotas     = document.getElementById('ai_notas');
const aiValidez   = document.getElementById('ai_validez');
const aiDesc      = document.getElementById('ai_descuento');
const aiEnvio     = document.getElementById('ai_envio');
const aiOCR       = document.getElementById('ai_ocr');
const aiReason    = document.getElementById('ai_reason');
const aiSkipped   = document.getElementById('ai_skipped');
let lastAIData    = null;

btnParse.addEventListener('click', async (e)=>{
  e.preventDefault();
  aiStatus.textContent = '';
  aiResBox.style.display = 'none';
  aiSkipped.style.display = 'none';
  lastAIData = null;

  const f = pdfInput.files?.[0];
  if(!f){ aiStatus.textContent = 'Selecciona un PDF primero.'; return; }

  const fd = new FormData();
  fd.append('pdf', f);
  // CSRF
  const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

  btnParse.disabled = true; btnParse.textContent = 'Analizando...';
  try{
    const res = await fetch("{{ route('cotizaciones.ai_parse') }}", {
      method: 'POST',
      headers: {'X-CSRF-TOKEN': token},
      body: fd
    });
    if(!res.ok){
      aiStatus.textContent = 'Error al analizar el PDF (HTTP '+res.status+').';
      return;
    }
    const data = await res.json();
    if(!data || data.ok !== true){
      aiStatus.textContent = 'No se pudo extraer información suficiente.';
      return;
    }

    lastAIData = data;

    aiCliente.textContent = data.cliente_match_name ?? '—';
    aiItemsCnt.textContent = (data.items?.length ?? 0);
    aiPages.textContent = (data.relevant_pages?.length ? data.relevant_pages.join(', ') : '—');
    aiNotas.textContent = data.notas ?? '—';
    aiValidez.textContent = data.validez_dias ?? '—';
    aiDesc.textContent = (data.descuento ?? 0);
    aiEnvio.textContent = (data.envio ?? 0);
    aiOCR.textContent = data.ocr_used ? 'sí (OCR)' : 'no';
    aiOCR.className = data.ocr_used ? 'good' : 'warn';
    aiReason.textContent = data.ai_reason ?? '—';

    // Avisar por si hubo items que no pudieron mapearse a un producto
    const skipped = (data.items||[]).filter(it => !it.producto_id).length;
    if(skipped > 0){
      aiSkipped.style.display='block';
      aiSkipped.textContent = `Atención: ${skipped} fila(s) no pudieron asociarse a un producto del catálogo. No se agregarán automáticamente (evita error de validación).`;
    }

    aiResBox.style.display = '';
    aiStatus.textContent = 'Análisis completado. Revisa y aplica al formulario.';
  }catch(err){
    aiStatus.textContent = 'Fallo de red/servidor al analizar el PDF.';
  }finally{
    btnParse.disabled = false; btnParse.textContent = 'Analizar PDF con IA';
  }
});

function applyAIToForm(data){
  // Cliente
  if(data.cliente_id){
    document.getElementById('cliente_id').value = data.cliente_id;
    const name = data.cliente_match_name ?? ('ID '+data.cliente_id);
    document.getElementById('cliente_search').value = name;
    actualizarTarjetaCliente();
  }

  // Notas / validez / descuentos / envío
  if(data.notas!=null) document.getElementById('notas').value = data.notas;
  if(data.validez_dias!=null) document.getElementById('validez_dias').value = data.validez_dias;
  if(data.descuento!=null) document.getElementById('desc_global').value = Number(data.descuento);
  if(data.envio!=null) document.getElementById('envio').value = Number(data.envio);

  // Items: limpiamos y agregamos solo los que tengan product_id (para no romper validación)
  $itemsBody.innerHTML = '';
  let added = 0, skipped = 0;

  (data.items||[]).forEach(row=>{
    if(!row.producto_id){ skipped++; return; }
    const p = PRODUCTOS.find(x => String(x.id) === String(row.producto_id));
    if(!p){ skipped++; return; }
    // construir producto con precio del AI o del catálogo si falta
    const prod = {...p};
    const precio = (row.precio_unitario!=null) ? Number(row.precio_unitario) : (p.price||0);
    const cant   = (row.cantidad!=null) ? Number(row.cantidad) : 1;
    agregarItemDesdeProducto({...prod, price: precio});
    // Sobreescribir cantidad/desc/iva/desc. personalizada
    const lastTr = $itemsBody.lastElementChild;
    if(lastTr){
      lastTr.querySelector('.it_cantidad').value = isFinite(cant)?cant:1;
      lastTr.querySelector('.it_descripcion').value = row.descripcion ?? p.label;
      if(row.descuento!=null) lastTr.querySelector('.it_descuento').value = Number(row.descuento);
      if(row.iva_porcentaje!=null) lastTr.querySelector('.it_iva').value = Number(row.iva_porcentaje);
      recalcularFila(lastTr);
    }
    added++;
  });

  serializar(); recalcularTotales();

  if(skipped>0){
    alert(`Se aplicó la IA. ${added} item(s) agregados. ${skipped} sin producto asociado (revisa el catálogo o añade manualmente).`);
  }
}

document.getElementById('btn_apply').addEventListener('click', (e)=>{
  e.preventDefault();
  if(!lastAIData){ alert('Primero analiza un PDF.'); return; }
  applyAIToForm(lastAIData);
  // no cambia de tab
});

document.getElementById('btn_apply_and_switch').addEventListener('click', (e)=>{
  e.preventDefault();
  if(!lastAIData){ alert('Primero analiza un PDF.'); return; }
  applyAIToForm(lastAIData);
  // Cambiar a manual para revisar/guardar
  tabManual.click();
});

// ===== Init =====
document.getElementById('fin_inicio').value = nextMonthISO();
actualizarTarjetaCliente();
</script>
@endsection
