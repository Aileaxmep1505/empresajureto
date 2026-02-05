@extends('layouts.app')
@section('title','Gastos')
@section('titulo','Gastos')

@section('content')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<style>
  #acc{
    --bg:#f7f9fc; --card:#fff; --ink:#0f172a; --muted:#64748b; --line:#e7edf5;
    --shadow:0 12px 30px rgba(15,23,42,.06); --radius:16px;
    --p1:#dbeafe; --p2:#dcfce7; --p3:#fae8ff; --p4:#ffedd5; --p5:#ffe4e6;
    --p1i:#1e3a8a; --p2i:#14532d; --p3i:#581c87; --p4i:#7c2d12; --p5i:#7f1d1d;
    --h:44px;
  }
  #acc .page{ background:var(--bg); border-radius:18px; padding:18px; }
  #acc .card-pro{ background:var(--card); border:1px solid var(--line); border-radius:var(--radius); box-shadow:var(--shadow); overflow:hidden; }
  #acc .head{ padding:14px; border-bottom:1px solid var(--line); display:flex; gap:12px; flex-wrap:wrap; align-items:center; justify-content:space-between; }
  #acc .body{ padding:14px; }
  #acc .title{ font-size:18px; font-weight:650; margin:0; color:var(--ink); }
  #acc .subtitle{ margin:0; color:var(--muted); font-size:13px; }
  #acc .btn-pastel{
    height:var(--h); border:0!important; border-radius:12px; padding:0 14px;
    font-weight:650; display:inline-flex; align-items:center; gap:10px;
    box-shadow:0 8px 18px rgba(15,23,42,.06);
    transition:transform .15s ease, background .15s ease, box-shadow .15s ease;
  }
  #acc .btn-pastel:hover{ background:#fff!important; transform:translateY(-1px); box-shadow:0 12px 26px rgba(15,23,42,.10); }
  #acc .btn-p1{ background:var(--p1); color:var(--p1i); }
  #acc .btn-p2{ background:var(--p2); color:var(--p2i); }
  #acc .btn-p3{ background:var(--p3); color:var(--p3i); }
  #acc .btn-ghost{ background:#f1f5f9; color:var(--ink); }
  #acc .form-control, #acc .form-select{
    height:var(--h); border-radius:12px; border:1px solid var(--line); background:#fff; color:var(--ink);
  }
  #acc .form-control:focus, #acc .form-select:focus{
    box-shadow:0 0 0 .2rem rgba(59,130,246,.12); border-color:#bfdbfe;
  }
  #acc label{ color:var(--muted); font-size:12px; margin-bottom:6px; }
  #acc .table-wrap{ overflow:auto; }
  #acc .table thead th{ font-size:12px; color:var(--muted); font-weight:700; border-bottom:1px solid var(--line); white-space:nowrap; }
  #acc .table tbody td{ border-bottom:1px solid var(--line); vertical-align:middle; white-space:nowrap; }
  #acc .badge-soft{
    padding:7px 10px; border-radius:999px; border:1px solid var(--line);
    font-size:12px; color:var(--muted); background:#fff;
  }
  #acc .amt{ font-weight:750; letter-spacing:-.01em; }
  #acc .tiny{ font-size:12px; }
  #acc .muted{ color:var(--muted); }

  @media (max-width: 992px){
    #acc .filters{ width:100%; }
  }
</style>

<div id="acc">
  <div class="page">
    <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-3">
      <div>
        <p class="title">Gastos operativos</p>
        <p class="subtitle">Registro simple, evidencias adjuntas y filtros por periodo.</p>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <button class="btn-pastel btn-p1" data-bs-toggle="modal" data-bs-target="#expenseModal" onclick="openExpenseCreate()">
          <i class="bi bi-plus-lg"></i> Nuevo gasto
        </button>
        <button class="btn-pastel btn-ghost" onclick="refreshExpenses()">
          <i class="bi bi-arrow-clockwise"></i> Actualizar
        </button>
      </div>
    </div>

    <div class="card-pro mb-3">
      <div class="head">
        <div class="d-flex gap-2 flex-wrap filters align-items-center">
          <div style="min-width:160px;">
            <label>Desde</label>
            <input id="from" type="date" class="form-control" onchange="refreshExpenses()">
          </div>
          <div style="min-width:160px;">
            <label>Hasta</label>
            <input id="to" type="date" class="form-control" onchange="refreshExpenses()">
          </div>
          <div style="min-width:220px;">
            <label>Categoría</label>
            <select id="category_id" class="form-select" onchange="refreshExpenses()"></select>
          </div>
          <div style="min-width:220px;">
            <label>Vehículo</label>
            <select id="vehicle_id" class="form-select" onchange="refreshExpenses()"></select>
          </div>
          <div style="min-width:220px;">
            <label>Buscar</label>
            <input id="q" class="form-control" placeholder="Concepto / proveedor" oninput="renderExpenses()">
          </div>
        </div>

        <div class="d-flex gap-2 align-items-center flex-wrap">
          <span class="badge-soft"><span class="muted tiny">Total (filtro):</span> <span class="amt" id="sumFiltered">$0.00</span></span>
          <span class="badge-soft"><span class="muted tiny">Registros:</span> <span class="amt" id="countFiltered">0</span></span>
        </div>
      </div>

      <div class="body">
        <div class="table-wrap">
          <table class="table align-middle">
            <thead>
              <tr>
                <th>Fecha</th>
                <th>Categoría</th>
                <th>Concepto</th>
                <th>Proveedor</th>
                <th>Vehículo</th>
                <th>Evidencia</th>
                <th class="text-end">Monto</th>
                <th class="text-end">Acciones</th>
              </tr>
            </thead>
            <tbody id="expenseRows">
              <tr><td colspan="8" class="tiny muted">Cargando...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    {{-- Modal: Create/Update Expense --}}
    <div class="modal fade" id="expenseModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius:16px; border:1px solid var(--line); overflow:hidden;">
          <div class="modal-header" style="background:#fff; border-bottom:1px solid var(--line);">
            <div>
              <div class="fw-semibold" id="expenseModalTitle" style="color:var(--ink);">Nuevo gasto</div>
              <div class="tiny muted">Adjunta evidencia (imagen/pdf/excel/word) si lo necesitas.</div>
            </div>
            <button type="button" class="btn btn-link text-decoration-none" data-bs-dismiss="modal" aria-label="Cerrar" style="color:var(--muted);">
              <i class="bi bi-x-lg"></i>
            </button>
          </div>

          <div class="modal-body" style="background:var(--bg);">
            <form id="expenseForm" onsubmit="saveExpense(event)">
              @csrf
              <input type="hidden" id="expense_id">

              <div class="row g-3">
                <div class="col-12 col-md-4">
                  <label>Fecha</label>
                  <input id="expense_date" type="date" class="form-control" required>
                </div>

                <div class="col-12 col-md-4">
                  <label>Categoría</label>
                  <select id="expense_category_id" class="form-select"></select>
                </div>

                <div class="col-12 col-md-4">
                  <label>Vehículo (opcional)</label>
                  <select id="expense_vehicle_id" class="form-select"></select>
                </div>

                <div class="col-12 col-md-8">
                  <label>Concepto</label>
                  <input id="concept" class="form-control" required placeholder="Ej. Gasolina, renta, internet, compra de equipo...">
                </div>

                <div class="col-12 col-md-4">
                  <label>Monto (MXN)</label>
                  <input id="amount" type="number" step="0.01" min="0" class="form-control" required placeholder="0.00">
                </div>

                <div class="col-12 col-md-6">
                  <label>Proveedor / Pagado a</label>
                  <input id="vendor" class="form-control" placeholder="Opcional">
                </div>

                <div class="col-12 col-md-6">
                  <label>Método de pago</label>
                  <select id="payment_method" class="form-select">
                    <option value="">Seleccionar</option>
                    <option value="efectivo">Efectivo</option>
                    <option value="transferencia">Transferencia</option>
                    <option value="tarjeta">Tarjeta</option>
                    <option value="otro">Otro</option>
                  </select>
                </div>

                <div class="col-12">
                  <label>Descripción</label>
                  <textarea id="description" class="form-control" rows="3" style="height:auto; border-radius:12px;" placeholder="Opcional"></textarea>
                </div>

                <div class="col-12">
                  <div class="card-pro">
                    <div class="body">
                      <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                        <div class="fw-semibold" style="color:var(--ink);">Evidencias</div>
                        <div class="tiny muted">Se guardan asociadas al gasto.</div>
                      </div>

                      <input id="files" type="file" class="form-control" multiple style="height:auto; padding:10px;">

                      <div class="tiny muted mt-2" id="attachHint">
                        Sube archivos después de guardar (o guarda y luego adjunta).
                      </div>

                      <div class="mt-2" id="attachmentsList"></div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="d-flex justify-content-between align-items-center mt-3">
                <button type="button" class="btn-pastel btn-ghost" data-bs-dismiss="modal">Cancelar</button>

                <div class="d-flex gap-2">
                  <button type="button" class="btn-pastel btn-p5 d-none" id="btnDeleteExpense" onclick="deleteExpense()">
                    <i class="bi bi-trash"></i> Eliminar
                  </button>
                  <button type="submit" class="btn-pastel btn-p2" id="btnSaveExpense">
                    <i class="bi bi-check2"></i> Guardar
                  </button>
                  <button type="button" class="btn-pastel btn-p3 d-none" id="btnUploadFiles" onclick="uploadFiles()">
                    <i class="bi bi-paperclip"></i> Subir evidencia
                  </button>
                </div>
              </div>
            </form>

            <div class="tiny muted mt-2" id="expenseMsg"></div>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<script>
  const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
  const expensesUrl = "{{ url('/expenses') }}";
  const categoriesUrl = "{{ url('/expense-categories') }}";
  const vehiclesUrl = "{{ url('/vehicles') }}";
  const attachmentsUrl = "{{ url('/attachments') }}";

  let EXPENSES = [];
  let CATEGORIES = [];
  let VEHICLES = [];

  function money(n){
    const x = Number(n || 0);
    return x.toLocaleString('es-MX', { style:'currency', currency:'MXN' });
  }
  function fmtDate(d){
    if(!d) return '—';
    const dt = new Date(d + 'T00:00:00');
    return dt.toLocaleDateString('es-MX', { year:'numeric', month:'short', day:'2-digit' });
  }
  function escapeHtml(str){
    return String(str ?? '').replace(/[&<>"']/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[s]));
  }

  async function loadLookups(){
    // categories
    const catRes = await fetch(categoriesUrl, { headers:{'Accept':'application/json'}});
    CATEGORIES = await catRes.json().catch(()=>[]);
    // vehicles
    const vRes = await fetch(vehiclesUrl, { headers:{'Accept':'application/json'}});
    const vData = await vRes.json().catch(()=>({}));
    VEHICLES = Array.isArray(vData) ? vData : (vData.data || []);

    // filters dropdowns
    const catSel = document.getElementById('category_id');
    catSel.innerHTML = `<option value="">Todas</option>` + CATEGORIES.map(c=>`<option value="${c.id}">${escapeHtml(c.name)}</option>`).join('');

    const vehicleSel = document.getElementById('vehicle_id');
    vehicleSel.innerHTML = `<option value="">Todos</option>` + VEHICLES.map(v=>`<option value="${v.id}">${escapeHtml(v.plate)} · ${escapeHtml((v.brand? v.brand+' ':'') + (v.model||''))}</option>`).join('');

    // modal dropdowns
    document.getElementById('expense_category_id').innerHTML =
      `<option value="">Seleccionar</option>` + CATEGORIES.map(c=>`<option value="${c.id}">${escapeHtml(c.name)}</option>`).join('');

    const vModal = document.getElementById('expense_vehicle_id');
    vModal.innerHTML =
      `<option value="">Ninguno</option>` + VEHICLES.map(v=>`<option value="${v.id}">${escapeHtml(v.plate)} · ${escapeHtml((v.brand? v.brand+' ':'') + (v.model||''))}</option>`).join('');
  }

  function setDefaultDates(){
    const to = new Date();
    const from = new Date(); from.setDate(to.getDate()-30);
    document.getElementById('to').value = to.toISOString().slice(0,10);
    document.getElementById('from').value = from.toISOString().slice(0,10);
  }

  async function refreshExpenses(){
    const from = document.getElementById('from').value;
    const to = document.getElementById('to').value;
    const category_id = document.getElementById('category_id').value;
    const vehicle_id = document.getElementById('vehicle_id').value;

    const params = new URLSearchParams();
    if(from) params.set('from', from);
    if(to) params.set('to', to);
    if(category_id) params.set('category_id', category_id);
    if(vehicle_id) params.set('vehicle_id', vehicle_id);

    const tbody = document.getElementById('expenseRows');
    tbody.innerHTML = `<tr><td colspan="8" class="tiny muted">Cargando...</td></tr>`;

    const res = await fetch(`${expensesUrl}?${params.toString()}`, { headers:{'Accept':'application/json'}});
    const data = await res.json().catch(()=>({}));
    EXPENSES = Array.isArray(data) ? data : (data.data || []);
    renderExpenses();
  }

  function renderExpenses(){
    const q = (document.getElementById('q').value || '').toLowerCase().trim();
    const rows = EXPENSES.filter(e=>{
      const t = `${e.concept||''} ${e.vendor||''}`.toLowerCase();
      return !q || t.includes(q);
    });

    const sum = rows.reduce((a,e)=> a + Number(e.amount || 0), 0);
    document.getElementById('sumFiltered').textContent = money(sum);
    document.getElementById('countFiltered').textContent = rows.length;

    const tbody = document.getElementById('expenseRows');
    if(!rows.length){
      tbody.innerHTML = `<tr><td colspan="8" class="tiny muted">No hay resultados.</td></tr>`;
      return;
    }

    tbody.innerHTML = rows.map(e=>{
      const cat = e.category?.name ? escapeHtml(e.category.name) : '—';
      const veh = e.vehicle?.plate ? `${escapeHtml(e.vehicle.plate)} · ${escapeHtml((e.vehicle.brand? e.vehicle.brand+' ':'')+(e.vehicle.model||''))}` : '—';
      const attCount = (e.attachments || []).length;

      return `
        <tr>
          <td>${fmtDate(e.expense_date)}</td>
          <td><span class="badge-soft">${cat}</span></td>
          <td class="fw-semibold">${escapeHtml(e.concept || '')}</td>
          <td>${escapeHtml(e.vendor || '—')}</td>
          <td>${veh}</td>
          <td class="tiny muted">${attCount ? (attCount + ' archivo(s)') : '—'}</td>
          <td class="text-end amt">${money(e.amount)}</td>
          <td class="text-end">
            <button class="btn-pastel btn-p3" style="height:38px; padding:0 12px;"
              data-bs-toggle="modal" data-bs-target="#expenseModal"
              onclick='openExpenseEdit(${JSON.stringify(e).replaceAll("'","&#39;")})'>
              <i class="bi bi-pencil"></i> Editar
            </button>
          </td>
        </tr>
      `;
    }).join('');
  }

  function openExpenseCreate(){
    document.getElementById('expenseModalTitle').textContent = 'Nuevo gasto';
    document.getElementById('expenseMsg').textContent = '';
    document.getElementById('btnDeleteExpense').classList.add('d-none');
    document.getElementById('btnUploadFiles').classList.add('d-none');
    document.getElementById('attachmentsList').innerHTML = '';
    document.getElementById('attachHint').textContent = 'Guarda primero y luego adjunta evidencia.';

    setExpenseForm({
      id:'',
      expense_date: new Date().toISOString().slice(0,10),
      expense_category_id:'',
      vehicle_id:'',
      concept:'',
      amount:'',
      vendor:'',
      payment_method:'',
      description:'',
      attachments:[]
    });
  }

  function openExpenseEdit(e){
    document.getElementById('expenseModalTitle').textContent = 'Editar gasto';
    document.getElementById('expenseMsg').textContent = '';
    document.getElementById('btnDeleteExpense').classList.remove('d-none');
    document.getElementById('btnUploadFiles').classList.remove('d-none');

    setExpenseForm(e);
    renderAttachments(e.attachments || []);
    document.getElementById('attachHint').textContent = 'Puedes agregar más evidencias cuando quieras.';
  }

  function setExpenseForm(e){
    document.getElementById('expense_id').value = e.id || '';
    document.getElementById('expense_date').value = (e.expense_date || '').slice(0,10);
    document.getElementById('expense_category_id').value = e.expense_category_id || e.category?.id || '';
    document.getElementById('expense_vehicle_id').value = e.vehicle_id || e.vehicle?.id || '';
    document.getElementById('concept').value = e.concept || '';
    document.getElementById('amount').value = e.amount ?? '';
    document.getElementById('vendor').value = e.vendor || '';
    document.getElementById('payment_method').value = e.payment_method || '';
    document.getElementById('description').value = e.description || '';
    document.getElementById('files').value = '';
  }

  function getExpensePayload(){
    return {
      expense_date: document.getElementById('expense_date').value,
      expense_category_id: toNull(document.getElementById('expense_category_id').value),
      vehicle_id: toNull(document.getElementById('expense_vehicle_id').value),
      concept: document.getElementById('concept').value.trim(),
      amount: Number(document.getElementById('amount').value || 0),
      vendor: document.getElementById('vendor').value.trim() || null,
      payment_method: document.getElementById('payment_method').value || null,
      description: document.getElementById('description').value.trim() || null,
      currency: 'MXN',
      status: 'pagado'
    };
  }

  function toNull(v){
    v = (v || '').trim();
    return v ? v : null;
  }

  async function saveExpense(ev){
    ev.preventDefault();
    const id = document.getElementById('expense_id').value;
    const payload = getExpensePayload();

    const url = id ? `${expensesUrl}/${id}` : expensesUrl;
    const method = id ? 'PUT' : 'POST';

    const res = await fetch(url, {
      method,
      headers: {
        'Accept':'application/json',
        'Content-Type':'application/json',
        'X-CSRF-TOKEN': CSRF
      },
      body: JSON.stringify(payload)
    });

    const out = await res.json().catch(()=> ({}));
    if(!res.ok){
      document.getElementById('expenseMsg').textContent = out.message || 'No se pudo guardar.';
      return;
    }

    document.getElementById('expenseMsg').textContent = 'Guardado correctamente.';
    document.getElementById('expense_id').value = out.id;

    // al guardar, habilitar upload
    document.getElementById('btnUploadFiles').classList.remove('d-none');
    document.getElementById('attachHint').textContent = 'Ahora puedes subir evidencias.';
    await refreshExpenses();
  }

  async function deleteExpense(){
    const id = document.getElementById('expense_id').value;
    if(!id) return;

    const res = await fetch(`${expensesUrl}/${id}`, {
      method:'DELETE',
      headers:{ 'Accept':'application/json', 'X-CSRF-TOKEN': CSRF }
    });

    if(!res.ok){
      document.getElementById('expenseMsg').textContent = 'No se pudo eliminar.';
      return;
    }
    await refreshExpenses();
    bootstrap.Modal.getInstance(document.getElementById('expenseModal'))?.hide();
  }

  function renderAttachments(atts){
    const wrap = document.getElementById('attachmentsList');
    if(!atts.length){
      wrap.innerHTML = `<div class="tiny muted">Sin evidencias.</div>`;
      return;
    }
    wrap.innerHTML = `
      <div class="d-flex flex-column gap-2">
        ${atts.map(a => `
          <div class="d-flex align-items-center justify-content-between" style="background:#fff; border:1px solid var(--line); border-radius:12px; padding:10px 12px;">
            <div class="d-flex flex-column">
              <div class="fw-semibold" style="color:var(--ink); font-size:13px;">${escapeHtml(a.original_name || a.path)}</div>
              <div class="tiny muted">${escapeHtml(a.mime_type || 'archivo')} · ${(a.size_bytes ? (Math.round(a.size_bytes/1024) + ' KB') : '')}</div>
            </div>
            <button class="btn-pastel btn-ghost" style="height:38px; padding:0 12px;" onclick="deleteAttachment(${a.id})">
              <i class="bi bi-trash"></i>
            </button>
          </div>
        `).join('')}
      </div>
    `;
  }

  async function uploadFiles(){
    const expenseId = document.getElementById('expense_id').value;
    const files = document.getElementById('files').files;
    if(!expenseId){
      document.getElementById('expenseMsg').textContent = 'Guarda el gasto primero.';
      return;
    }
    if(!files || !files.length){
      document.getElementById('expenseMsg').textContent = 'Selecciona al menos un archivo.';
      return;
    }

    const fd = new FormData();
    fd.append('attachable_type', 'App\\Models\\Expense');
    fd.append('attachable_id', expenseId);
    for(const f of files) fd.append('files[]', f);

    document.getElementById('expenseMsg').textContent = 'Subiendo evidencias...';

    const res = await fetch(attachmentsUrl, {
      method:'POST',
      headers:{ 'Accept':'application/json', 'X-CSRF-TOKEN': CSRF },
      body: fd
    });

    const out = await res.json().catch(()=>({}));
    if(!res.ok){
      document.getElementById('expenseMsg').textContent = out.message || 'No se pudieron subir.';
      return;
    }

    document.getElementById('expenseMsg').textContent = 'Evidencias subidas.';
    document.getElementById('files').value = '';

    // refrescar gasto actual en memoria (simple: recargar lista)
    await refreshExpenses();

    // intentar encontrar el gasto y pintar attachments
    const idNum = Number(expenseId);
    const found = EXPENSES.find(x => Number(x.id) === idNum);
    renderAttachments(found?.attachments || out.attachments || []);
  }

  async function deleteAttachment(id){
    const res = await fetch(`${attachmentsUrl}/${id}`, {
      method:'DELETE',
      headers:{ 'Accept':'application/json', 'X-CSRF-TOKEN': CSRF }
    });

    if(!res.ok){
      document.getElementById('expenseMsg').textContent = 'No se pudo eliminar evidencia.';
      return;
    }
    document.getElementById('expenseMsg').textContent = 'Evidencia eliminada.';
    await refreshExpenses();

    const expenseId = Number(document.getElementById('expense_id').value);
    const found = EXPENSES.find(x => Number(x.id) === expenseId);
    renderAttachments(found?.attachments || []);
  }

  // init
  setDefaultDates();
  loadLookups().then(refreshExpenses);
</script>
@endsection
