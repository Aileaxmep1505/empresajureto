(() => {
  "use strict";

  // Evita doble inicialización si el script se inyecta dos veces
  if (window.__COTIZ_JS_INIT__) {
    console.warn("[cotizaciones.js] Ya inicializado, abortando segundo boot.");
    return;
  }
  window.__COTIZ_JS_INIT__ = true;

  /* ========= Bootstrap ========= */
  const BOOT_EL = document.getElementById("cotiz-bootstrap");
  if (!BOOT_EL) {
    console.error("[cotizaciones.js] Falta #cotiz-bootstrap en la vista.");
    return;
  }
  const BOOT = JSON.parse(BOOT_EL.textContent || "{}");

  /* ===== LOADER helpers ===== */
  const loaderEl = document.getElementById("appLoader");
  function showLoader() {
    loaderEl?.classList.add("is-active");
  }
  function hideLoader() {
    loaderEl?.classList.remove("is-active");
  }
  window.addEventListener("load", hideLoader);

  /* ===== Datos desde bootstrap ===== */
  const CLIENTES_INFO = Object.fromEntries(
    Object.entries(BOOT.clientesInfo || {})
  );
  const CLIENTES_SELECT = BOOT.clientesSelect || [];
  const PRODUCTOS_RAW = BOOT.productos || [];
  const ROUTES = BOOT.routes || {};
  const CSRF = BOOT.csrf;

  /* ===== Tabs ===== */
  const tabManual = document.getElementById("tab-manual");
  const tabAI = document.getElementById("tab-ai");
  const panelManual = document.getElementById("panel-manual");
  const panelAI = document.getElementById("panel-ai");

  if (tabManual && tabAI && panelManual && panelAI) {
    tabManual.addEventListener("click", () => {
      tabManual.classList.add("is-active");
      tabAI.classList.remove("is-active");
      panelManual.hidden = false;
      panelAI.hidden = true;
      tabManual.setAttribute("aria-selected", "true");
      tabAI.setAttribute("aria-selected", "false");
    });
    tabAI.addEventListener("click", () => {
      tabAI.classList.add("is-active");
      tabManual.classList.remove("is-active");
      panelAI.hidden = false;
      panelManual.hidden = true;
      tabAI.setAttribute("aria-selected", "true");
      tabManual.setAttribute("aria-selected", "false");
    });
  }

  /* ===== Utils ===== */
  const normalize = (s) =>
    (s ?? "")
      .toString()
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")
      .toLowerCase()
      .replace(/\s+/g, " ")
      .trim();
  const money = (n) => "$" + Number(n || 0).toFixed(2);
  function nextMonthISO() {
    const d = new Date();
    return new Date(d.getFullYear(), d.getMonth() + 1, d.getDate())
      .toISOString()
      .slice(0, 10);
  }
  function addMonthsISO(iso, m) {
    const d = iso ? new Date(iso) : new Date();
    const r = new Date(d.getFullYear(), d.getMonth() + m, d.getDate());
    return r.toISOString().slice(0, 10);
  }
  function esDate(iso) {
    return /^\d{4}-\d{2}-\d{2}$/.test(iso);
  }
  const pick = (o, keys, def = null) => {
    for (const k of keys) {
      if (o && o[k] != null && o[k] !== "") return o[k];
    }
    return def;
  };

  /* ===== Inputs globales que afectan precio ===== */
  const $utilidadGlobal = document.getElementById("utilidad_global");
  const $descGlobal = document.getElementById("desc_global");
  const $envio = document.getElementById("envio");

  /* ===== SmartDropdown genérico ===== */
  function smartDropdown(
    root,
    items,
    { renderItem, onPick, getSearchText, placeholder = "Buscar...", showOnFocus = true }
  ) {
    const input = root?.querySelector(".sdrop-input");
    const list = root?.querySelector(".sdrop-list");
    if (!root || !input || !list) {
      return { open() {}, close() {}, refresh() {}, input: null, list: null };
    }
    let idx = -1;
    const ALPHA = [...items].sort((a, b) =>
      getSearchText(a).localeCompare(getSearchText(b))
    );
    function open() {
      list.hidden = false;
    }
    function close() {
      list.hidden = true;
      idx = -1;
    }
    function clear() {
      list.innerHTML = "";
    }
    function score(item, q) {
      if (!q) return 0;
      const hay = getSearchText(item);
      const tokens = q.split(" ");
      let s = 0,
        starts = 0;
      for (const t of tokens) {
        const pos = hay.indexOf(t);
        if (pos === -1) return -Infinity;
        s += 100 - Math.min(pos, 100);
        if (pos === 0) starts += 50;
      }
      return s + starts;
    }
    function refresh() {
      const qn = normalize(input.value);
      const base = qn ? items : ALPHA;
      const filtered = base
        .map((it) => ({ it, sc: score(it, qn) }))
        .filter((x) => (qn ? x.sc > -Infinity : true))
        .sort((a, b) =>
          qn
            ? b.sc - a.sc ||
              getSearchText(a.it).localeCompare(getSearchText(b.it))
            : getSearchText(a.it).localeCompare(getSearchText(b.it))
        )
        .slice(0, 50)
        .map((x) => x.it);
      clear();
      for (const [i, it] of filtered.entries()) {
        const el = renderItem(it);
        el.classList.add("sdrop-item");
        if (i === idx) el.classList.add("is-active");
        el.addEventListener("mousedown", (e) => {
          e.preventDefault();
          onPick(it);
          close();
        });
        list.appendChild(el);
      }
      if (filtered.length === 0) {
        const empty = document.createElement("div");
        empty.className = "sdrop-item";
        empty.innerHTML =
          '<div class="sdrop-main"><div class="sdrop-sub">Sin resultados</div></div>';
        list.appendChild(empty);
      }
    }
    input.placeholder = placeholder;
    input.addEventListener("input", refresh);
    if (showOnFocus) {
      input.addEventListener("focus", () => {
        open();
        refresh();
      });
    }
    input.addEventListener("blur", () => setTimeout(close, 120));
    input.addEventListener("keydown", (e) => {
      const count = list.children.length;
      if (e.key === "ArrowDown") {
        e.preventDefault();
        open();
        idx = Math.min(count - 1, idx + 1);
        highlight();
      } else if (e.key === "ArrowUp") {
        e.preventDefault();
        idx = Math.max(0, idx - 1);
        highlight();
      } else if (e.key === "Enter") {
        if (!list.hidden && idx >= 0 && idx < count) {
          e.preventDefault();
          list.children[idx].dispatchEvent(new Event("mousedown"));
        }
      } else if (e.key === "Escape") {
        close();
      }
    });
    function highlight() {
      [...list.children].forEach((c, i) =>
        c.classList.toggle("is-active", i === idx)
      );
      const it = list.children[idx];
      if (it) {
        const r = it.getBoundingClientRect();
        list.scrollTop += r.top - (list.getBoundingClientRect().top + 8);
      }
    }
    return { open, close, refresh, input, list };
  }

  /* ===== Cliente (tarjeta lateral) ===== */
  const CLIENTES_ITEMS = CLIENTES_SELECT.map((c) => ({
    id: c.id,
    display: c.display,
    search: normalize(
      [c.display, CLIENTES_INFO[c.id]?.email, CLIENTES_INFO[c.id]?.telefono]
        .filter(Boolean)
        .join(" ")
    ),
  }));

  const sdCliente = smartDropdown(
    document.getElementById("sd-cliente-side"),
    CLIENTES_ITEMS,
    {
      placeholder: "Buscar cliente...",
      showOnFocus: false,
      getSearchText: (it) => it.search || normalize(it.display),
      renderItem: (it) => {
        const div = document.createElement("div");
        div.innerHTML = `<div class="sdrop-main">
        <div class="sdrop-title">${it.display}</div>
        <div class="sdrop-sub">${CLIENTES_INFO[it.id]?.email ?? ""} ${
          CLIENTES_INFO[it.id]?.telefono ?? ""
        }</div>
      </div>`;
        return div;
      },
      onPick: (it) => {
        document.getElementById("cliente_id").value = it.id;
        document.getElementById("cliente_search").value =
          CLIENTES_INFO[it.id]?.name ??
          CLIENTES_INFO[it.id]?.nombre ??
          it.display;
        actualizarTarjetaCliente();
      },
    }
  );

  /* ===== Productos (tomando COSTO como base) ===== */
  const PRODUCTOS = PRODUCTOS_RAW.map((p) => {
    const label = pick(
      p,
      ["display", "name", "nombre", "titulo", "title"],
      `Producto #${p.id}`
    );
    const image = pick(p, ["image", "imagen", "foto", "thumb", "thumbnail"], null);
    const brand = pick(p, ["brand", "marca"], null);
    const category = pick(p, ["category", "categoria"], null);
    const color = pick(p, ["color", "colour"], null);
    const material = pick(p, ["material"], null);
    const stock = p.stock ?? p.existencia ?? null;
    const cost = Number(p.cost || p.costo || 0); // COSTO base
    const price = Number(p.price || p.precio || 0); // referencia visual
    return {
      id: p.id,
      label,
      image,
      brand,
      category,
      color,
      material,
      stock,
      cost,
      price,
      search: normalize(
        [label, brand, category, color, material].filter(Boolean).join(" ")
      ),
    };
  });

  const sdProducto = smartDropdown(
    document.getElementById("sd-producto"),
    PRODUCTOS,
    {
      placeholder: "Buscar producto...",
      getSearchText: (it) => it.search,
      renderItem: (it) => {
        const div = document.createElement("div");
        const img = it.image
          ? `<img class="sdrop-thumb" src="${it.image}" alt="">`
          : `<div class="sdrop-thumb"></div>`;
        const metaParts = [];
        if (it.brand) metaParts.push(it.brand);
        if (it.category) metaParts.push(it.category);
        if (it.color) metaParts.push(it.color);
        if (it.material) metaParts.push(it.material);
        const meta = metaParts.join(" • ");
        const stock =
          it.stock != null
            ? `<span class="badge-green">${it.stock} ${
                it.stock == 1 ? "unidad" : "unidades"
              }</span>`
            : "";
        div.innerHTML = `${img}
        <div class="sdrop-main">
          <div class="sdrop-title">${it.label}</div>
          <div class="sdrop-sub">${meta ? meta : "&nbsp;"}</div>
          <div class="sdrop-sub">Costo: <strong>${money(it.cost)}</strong>${
          it.price ? ` · Ref.: ${money(it.price)}` : ""
        }</div>
        </div>
        <div class="sdrop-right">${stock}</div>`;
        return div;
      },
      onPick: (it) => {
        agregarItemDesdeProducto(it);
        sdProducto.input.value = "";
        sdProducto.refresh();
        sdProducto.open();
      },
    }
  );

  /* ===== Tarjeta cliente ===== */
  function actualizarTarjetaCliente() {
    const id = document.getElementById("cliente_id").value;
    const c = CLIENTES_INFO[id];
    const safe = (v) => v ?? "—";
    document.getElementById("cli-id").textContent = id || "—";
    if (!c) {
      [
        "cli-nombre",
        "cli-email",
        "cli-telefono",
        "cli-rfc",
        "cli-direccion",
        "cli-ubicacion",
        "cli-cp",
      ].forEach((i) => (document.getElementById(i).textContent = "—"));
      return;
    }
    const nombre = pick(c, ["name", "nombre", "razon_social"]) || `ID ${id}`;
    const email = pick(c, ["email", "correo", "mail"]);
    const tel = pick(c, ["phone", "telefono", "mobile", "celular", "phone_number"]);
    const rfc = pick(c, ["rfc", "tax_id", "nit", "ruc"]);
    const calle = pick(c, ["address", "direccion", "street", "domicilio"]);
    const ciudad = pick(c, ["city", "ciudad"]);
    const estado = pick(c, ["state", "estado"]);
    const cp = pick(c, ["zip", "cp", "postal_code"]);
    document.getElementById("cli-nombre").textContent = safe(nombre);
    document.getElementById("cli-email").textContent = safe(email);
    document.getElementById("cli-telefono").textContent = safe(tel);
    document.getElementById("cli-rfc").textContent = safe(rfc);
    document.getElementById("cli-direccion").textContent = safe(calle);
    document.getElementById("cli-ubicacion").textContent = (ciudad || estado)
      ? `${safe(ciudad)} ${estado ? "/ " + estado : ""}`
      : "—";
    document.getElementById("cli-cp").textContent = safe(cp);
  }

  /* ===== Items & Totales (con utilidad global) ===== */
  const $itemsBody = document.querySelector("#items tbody");
  const $itemsJson = document.getElementById("items_json");

  // P. venta = costo * (1 + utilidad_global%)
  function computeUnitPriceFromCost(cost) {
    const u = Number($utilidadGlobal?.value || 0);
    return Number(cost || 0) * (1 + u / 100);
  }

  function agregarItemDesdeProducto(
    prod,
    { cantidad = 1, descripcion = null, iva = 16, descuento = 0 } = {}
  ) {
    const costo = Number(prod.cost || 0);
    const unit = computeUnitPriceFromCost(costo);

    const tr = document.createElement("tr");
    tr.innerHTML = `
      <td>
        <input type="hidden" class="it_producto_id" value="${prod.id}">
        <input type="hidden" class="it_cost" value="${costo.toFixed(2)}">
        <input type="text" class="it_descripcion" value="${
          descripcion ?? prod.label
        }" style="width:100%;padding:6px 8px;border:1px solid #e5e7eb;border-radius:8px">
      </td>
      <td><input type="number" class="it_cantidad" value="${cantidad}" step="0.01" style="width:100%"></td>
      <td>
        <input type="number" class="it_precio" value="${unit.toFixed(
          2
        )}" step="0.01" style="width:100%" readonly>
        <div class="small" style="opacity:.75">desde costo</div>
      </td>
      <td><input type="number" class="it_descuento" value="${descuento}" step="0.01" style="width:100%" placeholder="Descuento $"></td>
      <td><input type="number" class="it_iva" value="${iva}" step="0.01" style="width:100%"></td>
      <td class="it_importe" style="text-align:right">$0.00</td>
      <td><button type="button" class="btn" onclick="this.closest('tr').remove(); (window._serializar&&window._serializar()); (window._recalcularTotales&&window._recalcularTotales());">Quitar</button></td>`;
    $itemsBody.appendChild(tr);
    recalcularFila(tr);
    serializar();
    recalcularTotales();
  }

  function recalcularFila(tr) {
    const cant = parseFloat(tr.querySelector(".it_cantidad").value || 0);
    const d = parseFloat(tr.querySelector(".it_descuento").value || 0); // $
    const iva = parseFloat(tr.querySelector(".it_iva").value || 16);
    const cost = parseFloat(tr.querySelector(".it_cost").value || 0);

    const p = computeUnitPriceFromCost(cost);
    tr.querySelector(".it_precio").value = p.toFixed(2);

    const base = Math.max(0, p * cant - d);
    const imp = base * (1 + iva / 100);

    tr.querySelector(".it_importe").textContent = money(imp);
  }

  function serializar() {
    const rows = [...$itemsBody.querySelectorAll("tr")].map((tr) => {
      const cost = parseFloat(tr.querySelector(".it_cost").value || 0);
      const unit = computeUnitPriceFromCost(cost);
      return {
        producto_id: tr.querySelector(".it_producto_id").value,
        descripcion: tr.querySelector(".it_descripcion").value,
        cantidad: parseFloat(tr.querySelector(".it_cantidad").value || 0),
        precio_unitario: Number(unit.toFixed(2)), // venta calculado
        descuento: parseFloat(tr.querySelector(".it_descuento").value || 0),
        iva_porcentaje: parseFloat(tr.querySelector(".it_iva").value || 16),
        cost: Number(cost.toFixed(2)), // costo base
      };
    });
    $itemsJson.value = JSON.stringify(rows);
  }

  function calcularTotales() {
    let subtotal = 0,
      ivaSum = 0,
      inversion = 0;
    [...$itemsBody.querySelectorAll("tr")].forEach((tr) => {
      const cant = parseFloat(tr.querySelector(".it_cantidad").value || 0);
      const d = parseFloat(tr.querySelector(".it_descuento").value || 0);
      const iva = parseFloat(tr.querySelector(".it_iva").value || 16);
      const cost = parseFloat(tr.querySelector(".it_cost").value || 0);
      const p = computeUnitPriceFromCost(cost);

      const base = Math.max(0, p * cant - d);
      subtotal += base;
      ivaSum += base * (iva / 100);
      inversion += cost * cant;
    });

    const descG = parseFloat($descGlobal?.value || 0);
    const envio = parseFloat($envio?.value || 0);

    const total = Math.max(0, subtotal - descG + envio + ivaSum);
    const ganancia = Math.max(0, subtotal - inversion);

    return { subtotal, ivaSum, descG, envio, total, inversion, ganancia };
  }

  function pintarTotales(t) {
    const set = (id, val) => {
      const el = document.getElementById(id);
      if (el) el.textContent = money(val);
    };

    set("t_inversion", t.inversion);
    set("t_ganancia", t.ganancia);
    const tSub = document.getElementById("t_subtotal");
    if (tSub) {
      tSub.textContent = money(t.subtotal);
      tSub.classList.add("sum");
    }
    const tIva = document.getElementById("t_iva");
    if (tIva) tIva.textContent = money(t.ivaSum);
    const tDesc = document.getElementById("t_desc_global");
    if (tDesc) tDesc.textContent = money(t.descG);
    const tEnv = document.getElementById("t_envio");
    if (tEnv) tEnv.textContent = money(t.envio);
    const tTot = document.getElementById("t_total");
    if (tTot) {
      tTot.textContent = money(t.total);
      tTot.classList.add("sum");
    }
  }

  function recalcularTotales() {
    const t = calcularTotales();
    pintarTotales(t);
    recalcularPlan();
  }

  // Exponer helpers para el botón "Quitar"
  window._serializar = serializar;
  window._recalcularTotales = recalcularTotales;

  $itemsBody.addEventListener("input", (e) => {
    const tr = e.target.closest("tr");
    if (tr) {
      recalcularFila(tr);
      serializar();
      recalcularTotales();
    }
  });

  $utilidadGlobal?.addEventListener("input", () => {
    [...$itemsBody.querySelectorAll("tr")].forEach(recalcularFila);
    serializar();
    recalcularTotales();
    console.log(
      "[cotizaciones] utilidad_global changed -> recalc rows & totals"
    );
  });
  $descGlobal?.addEventListener("input", recalcularTotales);
  $envio?.addEventListener("input", recalcularTotales);

  /* ===== Financiamiento (preview) ===== */
  const fin = {
    aplicar: document.getElementById("fin_aplicar"),
    plazos: document.getElementById("fin_plazos"),
    eng: document.getElementById("fin_enganche"),
    tasa: document.getElementById("fin_tasa"),
    inicio: document.getElementById("fin_inicio"),
    wrap: document.getElementById("plan_wrap"),
    table: document.getElementById("plan_table")?.querySelector("tbody"),
  };
  function setDisabledFin(dis) {
    [fin.plazos, fin.eng, fin.tasa, fin.inicio].forEach(
      (el) => el && (el.disabled = dis)
    );
  }
  fin.aplicar?.addEventListener("change", () => {
    setDisabledFin(!fin.aplicar.checked);
    if (fin.wrap) fin.wrap.style.display = fin.aplicar.checked ? "" : "none";
    if (fin.aplicar.checked && fin.inicio && !fin.inicio.value) {
      fin.inicio.value = nextMonthISO();
    }
    recalcularPlan();
  });
  [fin.plazos, fin.eng, fin.tasa, fin.inicio].forEach((el) =>
    el?.addEventListener("input", recalcularPlan)
  );

  function recalcularPlan() {
    if (!fin.aplicar?.checked) return;
    const t = calcularTotales();
    const n = Math.max(1, parseInt(fin.plazos?.value || 0));
    const eng = parseFloat(fin.eng?.value || 0);
    const base = Math.max(0, t.total - eng);
    const cuota = n > 0 ? Math.round((base / n) * 100) / 100 : 0;

    if (!fin.table) return;
    fin.table.innerHTML = "";
    if (base <= 0 || n < 1) {
      fin.table.innerHTML = `<tr><td colspan="3" class="small">Agrega productos y define plazos para ver el calendario.</td></tr>`;
      return;
    }
    const startISO = esDate(fin.inicio?.value) ? fin.inicio.value : nextMonthISO();
    for (let i = 0; i < n; i++) {
      const vence = addMonthsISO(startISO, i);
      const tr = document.createElement("tr");
      tr.innerHTML = `<td>${i + 1}</td><td>${vence
        .split("-")
        .reverse()
        .join("/")}</td><td>${money(cuota)}</td>`;
      fin.table.appendChild(tr);
    }
    if (fin.wrap) fin.wrap.style.display = "";
  }

  /* ===== Envío backend (manual) ===== */
  document.getElementById("form")?.addEventListener("submit", (e) => {
    serializar();
    if (!$itemsJson.value || $itemsJson.value === "[]") {
      e.preventDefault();
      alert("Agrega al menos un producto.");
      return;
    }
    showLoader();
  });

  /* ===== IA: Parse PDF y Aplicar ===== */
  const pdfInput = document.getElementById("pdf_file");
  const pagesForce = document.getElementById("pages_force");
  const btnParse = document.getElementById("btn_parse"); // ← SOLO UNA VEZ
  const aiResBox = document.getElementById("ai_result");
  const aiStatus = document.getElementById("ai_status");

  const aiCliente = document.getElementById("ai_cliente");
  const aiIssuerKind = document.getElementById("ai_issuer_kind");
  const aiObjeto = document.getElementById("ai_objeto");
  const aiProc = document.getElementById("ai_proc");
  const aiDep = document.getElementById("ai_dep");
  const aiLugar = document.getElementById("ai_lugar");
  const aiPago = document.getElementById("ai_pago");
  const aiMoneda = document.getElementById("ai_moneda");

  const aiFpub = document.getElementById("ai_f_pub");
  const aiFacl = document.getElementById("ai_f_acl");
  const aiFpre = document.getElementById("ai_f_pre");
  const aiFfal = document.getElementById("ai_f_fal");

  const aiItemsCnt = document.getElementById("ai_items_count");
  const aiPages = document.getElementById("ai_pages");
  const aiEnvioSug = document.getElementById("ai_envio_sug");

  const aiOCR = document.getElementById("ai_ocr");
  const aiReason = document.getElementById("ai_reason");
  const aiOverview = document.getElementById("ai_pages_overview");
  const aiSkipped = document.getElementById("ai_skipped");

  const aiPendWrap = document.getElementById("ai_pendientes_wrap");

  let lastAIData = null;

  /* ---------- MODAL Catálogo ---------- */
  const modal = document.getElementById("catalogModal");
  const qInput = document.getElementById("catalogQuery");
  const qBtn = document.getElementById("catalogSearchBtn");
  const qRes = document.getElementById("catalogResults");
  const qHint = document.getElementById("catalogHint");
  let modalRowIndex = null;

  function openModal(rowIdx, presetQuery = "") {
    modalRowIndex = rowIdx;
    modal?.classList.add("is-open");
    if (qInput) qInput.value = presetQuery;
    if (qRes) qRes.innerHTML = "";
    if (qHint) qHint.textContent = "Escribe y pulsa Buscar. Enter también funciona.";
    setTimeout(() => qInput?.focus(), 20);
  }
  function closeModal() {
    modal?.classList.remove("is-open");
    modalRowIndex = null;
  }
  modal?.querySelector(".back")?.addEventListener("click", closeModal);
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && modal?.classList.contains("is-open")) closeModal();
  });

  qInput?.addEventListener("keydown", (e) => {
    if (e.key === "Enter") {
      e.preventDefault();
      doCatalogSearch();
    }
  });
  qBtn?.addEventListener("click", (e) => {
    e.preventDefault();
    doCatalogSearch();
  });

  async function doCatalogSearch() {
    const q = qInput?.value.trim();
    if (!q) {
      qInput?.focus();
      return;
    }
    if (qRes) qRes.innerHTML = "";
    if (qHint) qHint.textContent = "Buscando...";
    try {
      const url = ROUTES.buscarProductos;
      const params = new URLSearchParams({ q, per_page: 30 });
      const res = await fetch(url + "?" + params.toString(), {
        headers: { "X-Requested-With": "XMLHttpRequest" },
      });
      if (!res.ok) {
        if (qHint) qHint.textContent = "Error al buscar (" + res.status + ").";
        return;
      }
      const data = await res.json();
      if (!data.items || !data.items.length) {
        if (qHint) qHint.textContent = "Sin resultados.";
        return;
      }
      if (qHint) qHint.textContent = data.total + " resultado(s)";
      for (const it of data.items) {
        const card = document.createElement("div");
        card.className = "result";
        const refCost = Number(it.cost ?? it.price ?? 0);
        card.innerHTML = `
          ${it.image ? `<img src="${it.image}" alt="">` : `<div style="width:64px;height:64px;border-radius:8px;background:#eef2f7"></div>`}
          <div>
            <div style="font-weight:700">${it.display}</div>
            <div class="small">${[it.brand, it.category, it.color, it.material].filter(Boolean).join(" • ") || "&nbsp;"}</div>
            <div class="small">SKU: ${it.sku || "—"}</div>
          </div>
          <div style="align-self:center;font-weight:700">Costo: ${money(refCost)}</div>`;
        card.addEventListener("click", () => {
          if (modalRowIndex != null) {
            const row = document.querySelector(
              `.ai-pend-row[data-idx="${modalRowIndex}"]`
            );
            const sel = row?.querySelector("select.ai-cands");
            if (sel) {
              const opt = document.createElement("option");
              opt.value = it.id;
              opt.textContent = `${it.display} — ${money(refCost)}`;
              opt.dataset.cost = refCost;
              sel.appendChild(opt);
              sel.value = String(it.id);
              sel.dispatchEvent(new Event("change"));
            }
          }
          closeModal();
        });
        qRes?.appendChild(card);
      }
    } catch (err) {
      if (qHint) qHint.textContent = "Fallo de red.";
    }
  }

  /* ---------- Render Pendientes (IA) ---------- */
  function renderPendientes(pendientes) {
    if (!aiPendWrap) return;
    aiPendWrap.innerHTML = "";
    if (!pendientes || !pendientes.length) {
      aiPendWrap.innerHTML = '<div class="small">No hay pendientes.</div>';
      return;
    }
    pendientes.forEach((p, i) => {
      const raw = p.raw || {};
      const row = document.createElement("div");
      row.className = "ai-pend-row";
      row.dataset.idx = i;
      const title = (raw.nombre || "Item sin nombre").toUpperCase();
      row.style.borderTop = "1px solid #eef2f7";
      row.style.padding = "12px 0";
      row.innerHTML = `
        <div style="font-weight:700;color:#0f172a">${title}</div>
        <div class="small">Cant: ${raw.cantidad ?? 1} (${raw.unidad ?? "PIEZA"})</div>

        <div class="row" style="margin-top:8px; align-items:center">
          <div style="min-width:220px;flex:1">
            <div class="small" style="margin-bottom:4px">Sugerencias</div>
            <select class="input ai-cands">
              <option value="">Sin candidatos</option>
              ${(p.candidatos || [])
                .map((c) => {
                  const refCost = Number(c.cost ?? c.price ?? 0);
                  return `<option value="${c.id}" data-cost="${refCost}">${c.display} — ${money(
                    refCost
                  )}</option>`;
                })
                .join("")}
            </select>
          </div>
          <div>
            <div class="small" style="margin-bottom:4px">¿No te convence?</div>
            <button class="btn brand ai-search-btn" data-idx="${i}">Buscar en catálogo</button>
          </div>
          <div>
            <button class="btn save ai-add-btn" data-idx="${i}">Agregar</button>
          </div>
        </div>
      `;
      aiPendWrap.appendChild(row);
    });

    // Evitar múltiples binds: reseteamos contenedor
    aiPendWrap.replaceWith(aiPendWrap.cloneNode(true));
    const freshWrap = document.getElementById("ai_pendientes_wrap");
    freshWrap?.addEventListener("click", onPendClick, { once: false });
  }

  function onPendClick(e) {
    const searchBtn = e.target.closest(".ai-search-btn");
    const addBtn = e.target.closest(".ai-add-btn");
    if (searchBtn) {
      const idx = Number(searchBtn.dataset.idx);
      const p = (lastAIData?.pendientes_ai || [])[idx];
      const raw = p?.raw || {};
      const seed = [raw.nombre, raw.descripcion].filter(Boolean).join(" ");
      openModal(idx, seed);
    }
    if (addBtn) {
      const idx = Number(addBtn.dataset.idx);
      const row = document.querySelector(`.ai-pend-row[data-idx="${idx}"]`);
      const sel = row?.querySelector("select.ai-cands");
      const val = sel?.value;
      if (!val) {
        alert("Elige un producto (o busca en catálogo).");
        return;
      }
      const cost = Number(sel.selectedOptions[0].dataset.cost || 0);
      const p = (lastAIData?.pendientes_ai || [])[idx]?.raw || {};
      const prodStub = {
        id: Number(val),
        label: p.descripcion || p.nombre || "Producto",
        cost: cost,
      };
      agregarItemDesdeProducto(prodStub, {
        cantidad: Number(p.cantidad || 1),
        descripcion: p.descripcion || p.nombre || null,
      });
      alert("Agregado a la tabla de items.");
    }
  }

  /* ---------- Parse IA ---------- */
  const btnApply = document.getElementById("btn_apply");
  const btnApplyAndSwitch = document.getElementById("btn_apply_and_switch");

  // ÚNICO handler de parse (sin duplicados)
  const btnParseHandler = async (e) => {
    e.preventDefault();
    if (!ROUTES.aiParse) {
      alert("No se definió la ruta de análisis.");
      return;
    }

    if (aiStatus) aiStatus.textContent = "";
    if (aiResBox) aiResBox.style.display = "none";
    if (aiSkipped) aiSkipped.style.display = "none";
    if (aiPendWrap) aiPendWrap.innerHTML = '<div class="small">No hay pendientes.</div>';
    lastAIData = null;

    const f = pdfInput?.files?.[0];
    if (!f) {
      if (aiStatus) aiStatus.textContent = "Selecciona un PDF primero.";
      return;
    }

    const fd = new FormData();
    fd.append("pdf", f);
    const pages = (pagesForce?.value || "").trim();
    if (pages) fd.append("pages", pages);

    showLoader();
    if (btnParse) {
      btnParse.disabled = true;
      btnParse.textContent = "Analizando...";
    }
    try {
      const res = await fetch(ROUTES.aiParse, {
        method: "POST",
        headers: { "X-CSRF-TOKEN": CSRF },
        body: fd,
      });
      if (!res.ok) {
        if (aiStatus)
          aiStatus.textContent = "Error al analizar el PDF (HTTP " + res.status + ").";
        return;
      }
      const data = await res.json();
      if (!data || data.ok !== true) {
        if (aiStatus)
          aiStatus.textContent = data?.error || "No se pudo extraer información suficiente.";
        return;
      }

      lastAIData = data;

      // Cabecera/cliente
      if (aiCliente) aiCliente.textContent = data.cliente_match_name ?? "—";
      if (aiIssuerKind)
        aiIssuerKind.textContent = data.issuer_kind
          ? data.issuer_kind.replaceAll("_", " ")
          : "—";

      // Resumen
      const S = data.summary || {};
      if (aiObjeto) aiObjeto.textContent = S.titulo_u_objeto ?? "—";
      if (aiProc) aiProc.textContent = S.procedimiento ?? "—";
      if (aiDep) aiDep.textContent = S.dependencia ?? "—";
      if (aiLugar) aiLugar.textContent = S.lugar_entrega ?? "—";
      if (aiPago) aiPago.textContent = S.condiciones_pago ?? "—";
      if (aiMoneda) aiMoneda.textContent = S.moneda ?? data.moneda ?? "—";

      // Helper para asignar texto si el elemento existe
const setTxt = (id, val) => {
  const el = document.getElementById(id);
  if (el) el.textContent = (val ?? '—');
};

const F = S.fechas_clave || {};
setTxt('ai_f_pub',     F.publicacion);
setTxt('ai_f_acl',     F.aclaraciones);
setTxt('ai_f_pre',     F.presentacion);
setTxt('ai_f_fal',     F.fallo);
setTxt('ai_validez',   F.vigencia_cotizacion_dias ?? data.validez_dias);


      if (aiItemsCnt)
        aiItemsCnt.textContent =
          (data.items?.length ?? 0) + (data.pendientes_ai?.length ?? 0);
      if (aiPages)
        aiPages.textContent = data.relevant_pages?.length
          ? data.relevant_pages.join(", ")
          : "—";
      if (aiEnvioSug) aiEnvioSug.textContent = money(data.envio_sugerido ?? 0);

      if (aiOCR) {
        aiOCR.textContent = data.ocr_used ? "sí (OCR)" : "no";
        aiOCR.className = data.ocr_used ? "good" : "warn";
      }
      if (aiReason) aiReason.textContent = data.ai_reason ?? "—";

      if (aiOverview) {
        aiOverview.innerHTML = "";
        const pagesOv = data.pages_overview || [];
        if (pagesOv.length) {
          for (const p of pagesOv) {
            const box = document.createElement("div");
            box.style.marginBottom = "10px";
            const title = document.createElement("div");
            title.innerHTML = `<span class="badge-soft">Pág. ${p.page}</span>`;
            box.appendChild(title);
            const ul = document.createElement("ul");
            ul.style.margin = "6px 0 0 18px";
            for (const b of (p.bullets || []).slice(0, 6)) {
              const li = document.createElement("li");
              li.textContent = b;
              ul.appendChild(li);
            }
            box.appendChild(ul);
            aiOverview.appendChild(box);
          }
        } else {
          aiOverview.textContent = "—";
        }
      }

      const skipped = (data.pendientes_ai || []).length;
      if (aiSkipped) {
        aiSkipped.style.display = skipped > 0 ? "block" : "none";
        if (skipped > 0) {
          aiSkipped.textContent = `Atención: ${skipped} fila(s) no pudieron asociarse automáticamente a un producto del catálogo. Puedes buscarlas y agregarlas aquí abajo.`;
        }
      }

      renderPendientes(data.pendientes_ai || []);

      if (aiResBox) aiResBox.style.display = "";
      if (aiStatus)
        aiStatus.textContent =
          "Análisis listo. Revisa, agrega pendientes y aplica al formulario.";
    } catch (err) {
      if (aiStatus) aiStatus.textContent = "Fallo de red/servidor al analizar el PDF.";
    } finally {
      if (btnParse) {
        btnParse.disabled = false;
        btnParse.textContent = "Analizar PDF con IA";
      }
      hideLoader();
    }
  };

  // Bind ÚNICO (evita doble listener si el script se inyecta 2 veces)
  if (btnParse && !btnParse.dataset.bound) {
    btnParse.addEventListener("click", btnParseHandler);
    btnParse.dataset.bound = "1";
  }

  /* ---- Aplicar IA al formulario ---- */
  function applyAIToForm(data) {
    // Cliente
    if (data.cliente_id) {
      const id = String(data.cliente_id);
      const name = data.cliente_match_name ?? "ID " + id;
      if (!CLIENTES_INFO[id]) {
        CLIENTES_INFO[id] = {
          id: id,
          nombre: name,
          email:
            (data.cliente_ai && data.cliente_ai.email)
              ? data.cliente_ai.email
              : name.replace(/\s+/g, ".").toLowerCase() + ".tmp@example.com",
          telefono:
            (data.cliente_ai && data.cliente_ai.telefono)
              ? data.cliente_ai.telefono
              : null,
        };
        CLIENTES_SELECT.push({ id: Number(id), display: name });
        CLIENTES_ITEMS.push({
          id: Number(id),
          display: name,
          search: normalize(
            [name, CLIENTES_INFO[id].email, CLIENTES_INFO[id].telefono]
              .filter(Boolean)
              .join(" ")
          ),
        });
        sdCliente.refresh && sdCliente.refresh();
      }
      document.getElementById("cliente_id").value = id;
      document.getElementById("cliente_search").value = name;
      actualizarTarjetaCliente();
    } else {
      alert("No se pudo determinar/crear el cliente desde el PDF.");
    }

    if (data.summary?.resumen_texto)
      document.getElementById("notas").value = data.summary.resumen_texto;
    if (data.validez_dias != null)
      document.getElementById("validez_dias").value = data.validez_dias;
    if (Number.isFinite(data.envio_sugerido)) {
      const want = confirm(
        `¿Usar envío sugerido de ${money(data.envio_sugerido)}?`
      );
      if (want)
        document.getElementById("envio").value = Number(data.envio_sugerido || 0);
    }

    let added = 0;
    (data.items || []).forEach((row) => {
      if (!row.producto_id) return;

      const p = PRODUCTOS.find((x) => String(x.id) === String(row.producto_id));
      const costRef = Number(row.cost ?? row.precio_unitario ?? 0); // si el backend no trae cost
      const cant = row.cantidad != null ? Number(row.cantidad) : 1;

      if (p) {
        agregarItemDesdeProducto(
          { ...p, cost: p.cost ?? costRef },
          { cantidad: cant, descripcion: row.descripcion ?? p.label }
        );
      } else {
        agregarItemDesdeProducto(
          { id: row.producto_id, label: row.descripcion || "Producto", cost: costRef },
          { cantidad: cant }
        );
      }
      added++;
    });

    serializar();
    recalcularTotales();

    alert(
      `Se aplicó la IA. ${added} item(s) agregados. Revisa los pendientes en la sección inferior del panel IA para agregarlos manualmente.`
    );
  }

  if (btnApply && !btnApply.dataset.bound) {
    btnApply.addEventListener("click", (e) => {
      e.preventDefault();
      if (!lastAIData) {
        alert("Primero analiza un PDF.");
        return;
      }
      applyAIToForm(lastAIData);
    });
    btnApply.dataset.bound = "1";
  }

  if (btnApplyAndSwitch && !btnApplyAndSwitch.dataset.bound) {
    btnApplyAndSwitch.addEventListener("click", (e) => {
      e.preventDefault();
      if (!lastAIData) {
        alert("Primero analiza un PDF.");
        return;
      }
      applyAIToForm(lastAIData);
      tabManual?.click();
    });
    btnApplyAndSwitch.dataset.bound = "1";
  }

  /* ===== Init ===== */
  const finInicio = document.getElementById("fin_inicio");
  if (finInicio && !finInicio.value) finInicio.value = nextMonthISO();
  actualizarTarjetaCliente();
})();
