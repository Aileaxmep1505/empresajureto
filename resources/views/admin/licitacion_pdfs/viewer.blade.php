@extends('layouts.app')

@section('title', 'Ver PDF')

@section('content')
<style>
  :root{
    --ink:#0b1220;
    --muted:#667085;
    --muted2:#94a3b8;
    --line:#e6eaf2;
    --line2:#eef2f7;
    --card:#ffffff;
    --bg:#f3f4f6;
    --shadow:0 18px 55px rgba(15,23,42,.08);
    --radius:16px;
    --accent:#4f46e5;
    --accent-soft:rgba(79,70,229,.08);
  }

  .vWrap{max-width:1200px;margin:0 auto;padding:16px 14px 22px}
  .vCard{
    background:var(--card);
    border-radius:var(--radius);
    border:1px solid var(--line);
    box-shadow:var(--shadow);
    overflow:hidden;
    display:flex;
    flex-direction:column;
    min-height:60vh;
  }

  .vHead{
    padding:12px 16px;
    border-bottom:1px solid var(--line);
    display:flex;
    justify-content:space-between;
    gap:12px;
    align-items:flex-start;
    flex-wrap:wrap;
  }
  .vTitle{
    font-weight:600;
    font-size:15px;
    color:var(--ink);
    letter-spacing:-.01em;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
    max-width:520px;
  }
  .vMeta{
    margin-top:4px;
    font-size:12px;
    color:var(--muted);
    display:flex;
    flex-wrap:wrap;
    gap:8px;
    align-items:center;
  }
  .vMetaBadge{
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:3px 9px;
    border-radius:999px;
    border:1px solid var(--line);
    background:#f9fafb;
    font-size:11px;
    color:var(--muted);
  }
  .vMetaBadge-dot{
    width:7px;height:7px;border-radius:999px;background:var(--accent);
  }

  .vBack{
    font-size:12px;
    border-radius:999px;
    border:1px solid #111827;
    padding:6px 12px;
    text-decoration:none;
    color:#111827;
    display:inline-flex;
    align-items:center;
    gap:6px;
    background:#fff;
  }
  .vBack svg{width:15px;height:15px}

  .vBody{
    background:#f8fafc;
    padding:12px 12px 14px;
    flex:1;
    min-height:0;
  }

  .vInfo{
    margin-bottom:8px;
    font-size:12px;
    color:var(--muted);
    border-radius:999px;
    border:1px solid var(--line2);
    background:#fff;
    padding:6px 12px;
    display:flex;
    justify-content:space-between;
    gap:8px;
    flex-wrap:wrap;
    align-items:center;
  }
  .vInfo-left{display:flex;gap:8px;flex-wrap:wrap;align-items:center;}
  .vInfo-strong{color:var(--ink);font-weight:500;}
  .vInfo-chip{
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:3px 9px;
    border-radius:999px;
    background:var(--accent-soft);
    color:var(--ink);
    max-width:260px;
  }
  .vInfo-chip small{
    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
    max-width:210px;
  }

  .pagesScroll{
    margin-top:4px;
    border-radius:14px;
    border:1px solid var(--line2);
    background:#e5e7eb;
    padding:10px 6px 14px;
    max-height:calc(100vh - 190px);
    overflow:auto;
  }

  .pdfPages{
    max-width:900px;
    margin:0 auto;
    display:flex;
    flex-direction:column;
    gap:16px;
  }

  .pageCard{
    background:#f9fafb;
    border-radius:14px;
    border:1px solid #d1d5db;
    box-shadow:0 10px 26px rgba(15,23,42,.08);
    padding:10px 8px 12px;
  }
  .pageHeader{
    font-size:11px;
    color:var(--muted2);
    margin:0 4px 6px;
  }
  .pageInner{
    position:relative;
    background:#fff;
    border-radius:12px;
    overflow:hidden;
    border:1px solid #e5e7eb;
    display:flex;
    justify-content:center;
  }

  canvas{
    display:block;
    max-width:100%;
    height:auto;
  }

  .textLayer{
    position:absolute;
    inset:0;
    pointer-events:none;
  }
  .textLayer span{
    position:absolute;
    color:transparent; /* todo el texto invisible */
    white-space:pre;
    transform-origin:0 0;
  }
  .textLayer .hl{
    color:#111827; /* solo el texto resaltado se ve */
    background:rgba(255, 230, 0, .65);
    box-shadow:inset 0 -1px 0 rgba(217, 119, 6, .9);
    border-radius:3px;
  }

  @media (max-width:768px){
    .vWrap{padding:12px 10px 18px}
    .vHead{padding:10px 12px}
    .vBody{padding:10px}
    .pagesScroll{max-height:none;}
  }
</style>

@php
  $name  = $pdf->original_filename ?? ('PDF #'.$pdf->id);
  $pageN = (int) $page;
  $qStr  = trim((string) $q);
@endphp

<div class="vWrap">
  <div class="vCard">
    <div class="vHead">
      <div>
        <div class="vTitle">{{ $name }}</div>
        <div class="vMeta">
          <span class="vMetaBadge">
            <span class="vMetaBadge-dot"></span>
            <span>Visor del PDF</span>
          </span>
          <span>Desplázate para ver todas las páginas.</span>
        </div>
      </div>

      <a href="{{ route('admin.licitacion-pdfs.ai.show', $pdf) }}" class="vBack">
        <svg viewBox="0 0 24 24" fill="none">
          <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        Volver
      </a>
    </div>

    <div class="vBody">
      <div class="vInfo">
        <div class="vInfo-left">
          <span class="vInfo-strong">
            Página <span id="vCurrentPage">{{ $pageN }}</span>
            de <span id="vTotalPages">…</span>
          </span>

          @if($qStr !== '')
            <div class="vInfo-chip">
              <span>Resaltando</span>
              <small>"{{ $qStr }}"</small>
            </div>
          @else
            <span>Mostrando el documento completo.</span>
          @endif
        </div>

        <span style="font-size:11px;color:var(--muted2);" id="vZoomInfo">
          Ajustado al ancho
        </span>
      </div>

      <div class="pagesScroll" id="pagesScroll">
        <div class="pdfPages" id="pdfPages"></div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
  (function(){
    const url         = @json($pdfUrl);
    const initialPage = Number(@json($pageN)) || 1;
    const query       = (@json($qStr) || '').trim();

    const pdfContainer = document.getElementById('pdfPages');
    const scrollBox    = document.getElementById('pagesScroll');
    const currentLbl   = document.getElementById('vCurrentPage');
    const totalLbl     = document.getElementById('vTotalPages');

    const pdfjsLib = window['pdfjsLib'];
    pdfjsLib.GlobalWorkerOptions.workerSrc =
      "https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js";

    function normalize(s){
      return (s || '').toLowerCase().replace(/\s+/g,' ').trim();
    }

    let pageCards = [];

    pdfjsLib.getDocument(url).promise.then(async (pdf) => {
      totalLbl.textContent = pdf.numPages;

      const containerWidth = scrollBox.clientWidth - 32;
      const baseWidth = containerWidth > 0 ? containerWidth : 800;

      const promises = [];
      for(let p=1; p<=pdf.numPages; p++){
        promises.push(renderPage(pdf, p, baseWidth));
      }

      pageCards = await Promise.all(promises);

      // scroll a la página inicial
      const idx = Math.max(0, Math.min(initialPage - 1, pageCards.length - 1));
      const target = pageCards[idx];
      if(target){
        setTimeout(() => {
          target.scrollIntoView({behavior:'smooth', block:'start'});
          currentLbl.textContent = initialPage;
        }, 80);
      }

      scrollBox.addEventListener('scroll', updateCurrentPageOnScroll);
    }).catch(err => {
      console.error(err);
      pdfContainer.innerHTML = '<div style="font-size:13px;color:#b91c1c;">No se pudo cargar el PDF en el visor.</div>';
    });

    function renderPage(pdf, pageNumber, baseWidth){
      return pdf.getPage(pageNumber).then(async (page) => {
        const vp1 = page.getViewport({ scale: 1 });
        const scale = baseWidth / vp1.width;
        const viewport = page.getViewport({ scale: scale });

        const card   = document.createElement('div');
        card.className = 'pageCard';
        card.dataset.pageNumber = String(pageNumber);

        const header = document.createElement('div');
        header.className = 'pageHeader';
        header.textContent = 'Página ' + pageNumber;
        card.appendChild(header);

        const inner = document.createElement('div');
        inner.className = 'pageInner';
        inner.style.width = viewport.width + 'px';
        inner.style.height = viewport.height + 'px';

        const canvas = document.createElement('canvas');
        const ctx    = canvas.getContext('2d');
        canvas.width  = viewport.width;
        canvas.height = viewport.height;

        const textLayerDiv = document.createElement('div');
        textLayerDiv.className = 'textLayer';
        textLayerDiv.style.width  = viewport.width + 'px';
        textLayerDiv.style.height = viewport.height + 'px';

        inner.appendChild(canvas);
        inner.appendChild(textLayerDiv);
        card.appendChild(inner);
        pdfContainer.appendChild(card);

        await page.render({ canvasContext: ctx, viewport: viewport }).promise;

        // Solo construimos capa de texto si hay query y coincide la página de interés
        if(query && pageNumber === initialPage){
          const textContent = await page.getTextContent();

          pdfjsLib.renderTextLayer({
            textContent: textContent,
            container: textLayerDiv,
            viewport: viewport,
            textDivs: []
          }).promise.then(function(){
            highlightTextLayer(textLayerDiv, query);
          });
        }

        return card;
      });
    }

    function highlightTextLayer(textLayerDiv, query){
      const qNorm = normalize(query);
      if(!qNorm) return;

      const words = qNorm.split(' ').filter(w => w.length >= 4).slice(0, 10);
      const spans = textLayerDiv.querySelectorAll('span');

      spans.forEach(sp => {
        const t = normalize(sp.textContent);
        if(!t) return;

        let hit = false;
        if(t.includes(qNorm)) hit = true;
        else if(words.length){
          hit = words.some(w => t.includes(w));
        }
        if(hit) sp.classList.add('hl');
      });
    }

    function updateCurrentPageOnScroll(){
      if(!pageCards || !pageCards.length) return;
      const scrollTop = scrollBox.scrollTop;
      let bestPage = 1;
      let bestDiff = Infinity;

      pageCards.forEach(card => {
        const offset = card.offsetTop;
        const diff = Math.abs(offset - scrollTop);
        if(diff < bestDiff){
          bestDiff = diff;
          bestPage = Number(card.dataset.pageNumber || 1);
        }
      });

      currentLbl.textContent = bestPage;
    }
  })();
</script>
@endsection
