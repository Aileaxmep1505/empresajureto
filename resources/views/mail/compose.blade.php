{{-- resources/views/mail/compose.blade.php --}}
@extends('layouts.app')
@section('title','Redactar')

@section('content')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@300..700&display=swap"/>

<div id="mail-compose">
  <style>
    /* ===================== Mail Compose (Pastel Pro) ===================== */
    #mail-compose{
      --ink:#0f172a; --muted:#667085; --line:#e8eef6;
      --bg:#f6f8fb; --card:#ffffff; --chip:#eef4ff;
      --brand:#6ea8fe; --brand-ink:#0b1220;
      --hover:#f6f9ff; --ring:#cfe0ff; --danger:#ef4444; --ok:#16a34a;
      --radius:16px; --shadow:0 18px 48px rgba(2,8,23,.06);
      font-family:"Outfit", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
      color:var(--ink); background:linear-gradient(180deg,#fbfdff, var(--bg));
      padding: clamp(16px, 3vw, 28px) 12px;
    }
    #mail-compose .wrap{max-width:980px; margin:0 auto;}
    #mail-compose .card{background:var(--card); border:1px solid var(--line); border-radius:var(--radius); box-shadow:var(--shadow)}
    #mail-compose .head{
      display:flex; align-items:center; gap:12px; padding:16px 18px; border-bottom:1px solid var(--line);
      background:linear-gradient(180deg,#fff, #fafdff);
      border-top-left-radius:var(--radius); border-top-right-radius:var(--radius);
    }
    #mail-compose .h-ico{display:inline-flex; align-items:center; justify-content:center; width:40px; height:40px; border-radius:12px; background:var(--chip)}
    #mail-compose .title{font-weight:800; letter-spacing:.2px}
    #mail-compose .hint{color:var(--muted); font-weight:600; font-size:.9rem}
    #mail-compose .bar{display:flex; gap:8px; margin-left:auto}
    #mail-compose .bar .btn{
      display:inline-flex; align-items:center; gap:8px; border:none; background:var(--chip); color:var(--ink);
      padding:10px 12px; border-radius:12px; font-weight:700; cursor:pointer;
    }
    #mail-compose .bar .btn.primary{background:var(--brand); color:var(--brand-ink)}
    #mail-compose .bar .btn:focus{outline:2px solid var(--ring); outline-offset:2px}

    /* Body */
    #mail-compose .body{padding:18px}
    #mail-compose .row{display:grid; grid-template-columns:140px 1fr; align-items:start; gap:10px; padding:10px 0; border-bottom:1px dashed var(--line)}
    #mail-compose .row:last-child{border-bottom:none}
    #mail-compose label{font-weight:800; color:var(--muted); padding-top:9px}
    #mail-compose .input, #mail-compose textarea{
      width:100%; border:1px solid var(--line); border-radius:12px; padding:11px 12px; font:inherit; background:#fff;
    }
    #mail-compose .input:focus, #mail-compose textarea:focus{outline:2px solid var(--ring)}
    #mail-compose .ghost-link{color:var(--brand-ink); background:transparent; border:none; font-weight:700; cursor:pointer}
    #mail-compose .ghost-link:hover{text-decoration:underline}

    /* Chips destinatarios */
    #mail-compose .chipbox{
      display:flex; flex-wrap:wrap; gap:8px; align-items:center; padding:6px; border:1px solid var(--line); border-radius:12px; background:#fff;
    }
    #mail-compose .chip{
      display:inline-flex; align-items:center; gap:6px; background:var(--chip); border-radius:999px; padding:6px 10px; font-weight:700;
    }
    #mail-compose .chip .x{cursor:pointer; border:none; background:transparent; line-height:1}
    #mail-compose .chipbox input{border:none; outline:none; min-width:180px; padding:6px 8px}

    /* CC/BCC panel */
    #mail-compose .ccbcc{display:none; margin-top:10px; padding:10px; border:1px dashed var(--line); border-radius:12px; background:#fcfdff}

    /* Toolbar simple */
    #mail-compose .toolbar{display:flex; gap:8px; padding:8px; border:1px solid var(--line); border-radius:12px; background:#fff}
    #mail-compose .tbtn{display:inline-flex; align-items:center; gap:6px; padding:8px 10px; border-radius:10px; border:1px solid transparent; background:var(--chip); cursor:pointer; font-weight:700}
    #mail-compose .tbtn:hover{background:var(--hover)}
    #mail-compose textarea{margin-top:8px; min-height:260px; resize:vertical; line-height:1.55}

    /* Dropzone adjuntos */
    #mail-compose .drop{
      border:2px dashed var(--line); border-radius:12px; padding:14px; text-align:center; background:#fff;
    }
    #mail-compose .drop.drag{background:#f9fbff; border-color:var(--brand)}
    #mail-compose .files{display:flex; flex-wrap:wrap; gap:10px; margin-top:10px}
    #mail-compose .file{
      display:flex; align-items:center; gap:10px; padding:8px 10px; border:1px solid var(--line); border-radius:10px; background:#fff; font-weight:600
    }
    #mail-compose .file .rm{border:none; background:transparent; color:var(--danger); font-weight:800; cursor:pointer}

    /* Footer actions */
    #mail-compose .foot{display:flex; justify-content:space-between; align-items:center; padding:16px 18px; border-top:1px solid var(--line); background:#fff; border-bottom-left-radius:var(--radius); border-bottom-right-radius:var(--radius)}
    #mail-compose .left-hint{color:var(--muted); font-weight:600}
    #mail-compose .actions{display:flex; gap:10px}
    #mail-compose .btn{
      display:inline-flex; align-items:center; gap:8px; border:none; border-radius:12px; padding:10px 14px; font-weight:800; cursor:pointer;
      background:var(--brand); color:var(--brand-ink);
    }
    #mail-compose .btn.secondary{background:var(--chip); color:var(--ink)}
    #mail-compose .btn.danger{background:#ffe3e3; color:#7f1d1d}

    /* Responsive */
    @media (max-width: 720px){
      #mail-compose .row{grid-template-columns:100px 1fr}
      #mail-compose .bar{flex-wrap:wrap}
      #mail-compose .left-hint{display:none}
    }

    /* Small helpers */
    .ms{font-family:"Material Symbols Outlined"; font-weight:normal; font-style:normal; font-size:20px; display:inline-block; line-height:1; }
  </style>

  <div class="wrap">
    @if(session('ok')) <div class="alert alert-success">{{ session('ok') }}</div> @endif
    @if($errors->any())
      <div class="alert alert-danger" style="border-radius:12px"><b>Revisa:</b> {{ implode(', ', $errors->all()) }}</div>
    @endif

    <form class="card" method="POST" action="{{ route('mail.send') }}" enctype="multipart/form-data" id="composeForm">
      @csrf

      {{-- Header --}}
      <div class="head">
        <div class="h-ico"><span class="ms">mail</span></div>
        <div>
          <div class="title">Nuevo correo</div>
          <div class="hint">Redacta y envía un mensaje elegante</div>
        </div>
        <div class="bar">
          <button type="button" class="btn secondary" id="saveDraft"><span class="ms">save</span>Borrador</button>
          <button type="submit" class="btn primary"><span class="ms">send</span>Enviar</button>
        </div>
      </div>

      <div class="body">
        {{-- Para (chips) --}}
        <div class="row">
          <label>Para</label>
          <div>
            <div class="chipbox" data-target="to">
              {{-- Chips se crean dinámicamente --}}
              <input type="text" id="toInput" placeholder="cliente@ejemplo.com (Enter para agregar)">
              <input type="hidden" name="to" id="toHidden">
            </div>
            <div style="margin-top:6px">
              <button type="button" class="ghost-link" id="toggleCC">CC/BCC</button>
            </div>
          </div>
        </div>

        {{-- Asunto --}}
        <div class="row">
          <label>Asunto</label>
          <div><input type="text" name="subject" class="input" placeholder="Asunto del mensaje" required></div>
        </div>

        {{-- CC/BCC (colapsable) --}}
        <div class="ccbcc" id="ccbcc">
          <div class="row" style="border-bottom:none; padding-bottom:0">
            <label>CC</label>
            <div>
              <div class="chipbox" data-target="cc">
                <input type="text" id="ccInput" placeholder="cc1@..., cc2@... (Enter)">
                <input type="hidden" name="cc" id="ccHidden">
              </div>
            </div>
          </div>
          <div class="row" style="border-bottom:none; padding-top:10px">
            <label>BCC</label>
            <div>
              <div class="chipbox" data-target="bcc">
                <input type="text" id="bccInput" placeholder="bcc1@..., bcc2@... (Enter)">
                <input type="hidden" name="bcc" id="bccHidden">
              </div>
            </div>
          </div>
        </div>

        {{-- Editor --}}
        <div class="row">
          <label>Mensaje</label>
          <div>
            <div class="toolbar">
              <button type="button" class="tbtn" data-cmd="bold"><span class="ms">format_bold</span>Negritas</button>
              <button type="button" class="tbtn" data-cmd="italic"><span class="ms">format_italic</span>Cursiva</button>
              <button type="button" class="tbtn" data-cmd="insertUnorderedList"><span class="ms">format_list_bulleted</span>Lista</button>
              <button type="button" class="tbtn" id="btnTemplate"><span class="ms">auto_fix_high</span>Plantilla</button>
            </div>
            <textarea name="body" id="bodyArea" placeholder="Escribe tu correo..." required></textarea>
          </div>
        </div>

        {{-- Adjuntos con dropzone --}}
        <div class="row">
          <label>Adjuntos</label>
          <div>
            <div class="drop" id="drop">
              <div><span class="ms">attach_file</span> Arrastra y suelta archivos aquí o <label for="fileInput" style="text-decoration:underline; cursor:pointer">explora</label></div>
              <input type="file" name="files[]" id="fileInput" class="input" style="display:none" multiple>
            </div>
            <div class="files" id="fileList"></div>
          </div>
        </div>
      </div>

      {{-- Footer --}}
      <div class="foot">
        <div class="left-hint">
          Atajo: <b>Ctrl/⌘ + Enter</b> para enviar. <span style="margin-left:12px">Tamaño máx. por archivo: 15MB</span>
        </div>
        <div class="actions">
          <button type="button" class="btn secondary" id="clearAll"><span class="ms">backspace</span>Limpiar</button>
          <button type="submit" class="btn"><span class="ms">send</span>Enviar</button>
        </div>
      </div>
    </form>
  </div>

  <script>
    (function(){
      const form = document.getElementById('composeForm');

      /* ===== Helpers ===== */
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/i;
      const qs  = s => document.querySelector(s);
      const qsa = s => Array.from(document.querySelectorAll(s));

      function addChip(container, value){
        if(!value) return;
        const v = value.trim();
        if(!emailRegex.test(v)) return;
        const chip = document.createElement('span');
        chip.className = 'chip';
        chip.innerHTML = '<span class="ms">person</span>'+v+' <button type="button" class="x ms" title="Quitar">close</button>';
        chip.dataset.value = v;
        container.insertBefore(chip, container.querySelector('input'));
        updateHidden(container);
      }
      function updateHidden(container){
        const target = container.dataset.target; // to, cc, bcc
        const hidden = document.getElementById(target+'Hidden');
        const emails = Array.from(container.querySelectorAll('.chip')).map(c => c.dataset.value);
        hidden.value = emails.join(', ');
      }
      function setupChipbox(idInput){
        const input = document.getElementById(idInput);
        const box = input.closest('.chipbox');
        input.addEventListener('keydown', (e)=>{
          if(e.key === 'Enter' || e.key === ','){
            e.preventDefault();
            const parts = input.value.split(',');
            parts.forEach(p => addChip(box, p));
            input.value = '';
          } else if(e.key === 'Backspace' && input.value === ''){
            const chips = box.querySelectorAll('.chip');
            const last = chips[chips.length - 1];
            if(last){ last.remove(); updateHidden(box); }
          }
        });
        box.addEventListener('click', (e)=>{
          if(e.target.classList.contains('x')){ e.target.closest('.chip').remove(); updateHidden(box); }
          input.focus();
        });
      }

      /* ===== Inicializar chipboxes ===== */
      setupChipbox('toInput');
      setupChipbox('ccInput');
      setupChipbox('bccInput');

      /* Prefill rápido si el usuario escribe una dirección y sale del input */
      ['toInput','ccInput','bccInput'].forEach(id=>{
        const i = document.getElementById(id);
        i.addEventListener('blur', ()=> {
          const box = i.closest('.chipbox');
          if(i.value.trim()){ i.value.split(',').forEach(v=>addChip(box, v)); i.value=''; }
        });
      });

      /* Toggle CC/BCC */
      const toggleCC = document.getElementById('toggleCC');
      const ccbcc = document.getElementById('ccbcc');
      toggleCC.addEventListener('click', ()=>{
        ccbcc.style.display = ccbcc.style.display === 'block' ? 'none' : 'block';
      });

      /* Toolbar simple (aplica en textarea) */
      const area = document.getElementById('bodyArea');
      function wrapSelection(prefix, suffix){
        const start = area.selectionStart; const end = area.selectionEnd;
        const before = area.value.substring(0,start);
        const sel = area.value.substring(start,end);
        const after = area.value.substring(end);
        area.value = before + prefix + sel + suffix + after;
        area.focus();
        area.selectionStart = start + prefix.length;
        area.selectionEnd = end + prefix.length;
      }
      qsa('.tbtn[data-cmd]').forEach(btn=>{
        btn.addEventListener('click', ()=>{
          const cmd = btn.dataset.cmd;
          if(cmd==='bold') wrapSelection('**','**');
          else if(cmd==='italic') wrapSelection('*','*');
          else if(cmd==='insertUnorderedList') wrapSelection('\n• ','');
        });
      });

      // ⚠️ IMPORTANTE: placeholders escapados con @{{ ... }} para no romper Blade
      const base = "Hola @{{ nombre }},\n\nGracias por contactarnos. Te comparto la información solicitada:\n\n- Punto 1\n- Punto 2\n\nQuedo atento(a).\n\nSaludos,\n@{{ mi_nombre }}\n@{{ mi_puesto }}";

      qs('#btnTemplate').addEventListener('click', ()=>{
        if(!area.value.trim()) area.value = base;
        else area.value += "\n\n" + base;
        area.focus();
      });

      /* Adjuntos: drag & drop + vista lista */
      const drop = document.getElementById('drop');
      const fileInput = document.getElementById('fileInput');
      const fileList = document.getElementById('fileList');

      function renderFiles(files){
        fileList.innerHTML = '';
        Array.from(files).forEach((f, idx)=>{
          const el = document.createElement('div');
          el.className = 'file';
          el.innerHTML = '<span class="ms">insert_drive_file</span>'+f.name+' <span style="color:var(--muted); font-weight:600">(' + Math.ceil(f.size/1024) + ' KB)</span> <button type="button" class="rm">Quitar</button>';
          el.querySelector('.rm').addEventListener('click', ()=>{
            const dt = new DataTransfer();
            Array.from(fileInput.files).forEach((ff, i)=>{ if(i!==idx) dt.items.add(ff) });
            fileInput.files = dt.files;
            renderFiles(fileInput.files);
          });
          fileList.appendChild(el);
        });
      }
      fileInput.addEventListener('change', ()=> renderFiles(fileInput.files));
      ;['dragenter','dragover'].forEach(evt => drop.addEventListener(evt, (e)=>{e.preventDefault(); drop.classList.add('drag');}));
      ;['dragleave','drop'].forEach(evt => drop.addEventListener(evt, (e)=>{e.preventDefault(); drop.classList.remove('drag');}));
      drop.addEventListener('drop', (e)=>{
        const dt = new DataTransfer();
        Array.from(fileInput.files).forEach(f => dt.items.add(f));
        Array.from(e.dataTransfer.files).forEach(f => dt.items.add(f));
        fileInput.files = dt.files;
        renderFiles(fileInput.files);
      });
      drop.addEventListener('click', ()=> fileInput.click());

      /* Borrador (localStorage) */
      const saveDraft = document.getElementById('saveDraft');
      saveDraft.addEventListener('click', ()=>{
        const data = {
          to: qs('#toHidden').value,
          cc: qs('#ccHidden').value,
          bcc: qs('#bccHidden').value,
          subject: qs('input[name="subject"]').value,
          body: area.value
        };
        localStorage.setItem('composeDraft', JSON.stringify(data));
        saveDraft.innerHTML = '<span class="ms">check_circle</span>Guardado';
        setTimeout(()=> saveDraft.innerHTML = '<span class="ms">save</span>Borrador', 1200);
      });
      // Cargar posible borrador
      try{
        const raw = localStorage.getItem('composeDraft');
        if(raw){
          const d = JSON.parse(raw);
          qs('input[name="subject"]').value = d.subject || '';
          area.value = d.body || '';
          ['to','cc','bcc'].forEach(t=>{
            const box = qs('.chipbox[data-target="'+t+'"]');
            (d[t]||'').split(',').map(s=>s.trim()).filter(Boolean).forEach(v=>addChip(box, v));
          });
        }
      }catch(e){}

      /* Limpiar */
      qs('#clearAll').addEventListener('click', ()=>{
        if(!confirm('¿Limpiar todo el mensaje?')) return;
        localStorage.removeItem('composeDraft');
        qsa('.chip').forEach(c=>c.remove());
        qsa('.chipbox input').forEach(i=>i.value='');
        qsa('[id$="Hidden"]').forEach(h=>h.value='');
        qs('input[name="subject"]').value='';
        area.value='';
        const dt = new DataTransfer(); fileInput.files = dt.files; fileList.innerHTML='';
      });

      /* Atajo enviar */
      document.addEventListener('keydown', (e)=>{
        if((e.ctrlKey || e.metaKey) && e.key.toLowerCase()==='enter'){
          form.requestSubmit();
        }
      });

      /* Validación mínima: al enviar, asegurarnos que existe al menos 1 destinatario */
      form.addEventListener('submit', (e)=>{
        const to = qs('#toHidden').value.trim();
        if(!to){
          e.preventDefault();
          alert('Agrega al menos un destinatario en "Para". Presiona Enter después de escribir el correo.');
        }
      });
    })();
  </script>
</div>
@endsection
