@extends('layouts.app')

@section('title', 'Ver PDF')

@section('content')
<style>
  .vWrap{max-width:1200px;margin:0 auto;padding:16px}
  .vCard{background:#fff;border:1px solid #e6eaf2;border-radius:16px;box-shadow:0 18px 55px rgba(2,6,23,.08);overflow:hidden}
  .vHead{padding:12px 14px;border-bottom:1px solid #e6eaf2;display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap}
  .vTitle{font-weight:950;color:#0b1220}
  .vMeta{color:#667085;font-size:12px}
  .vBody{padding:12px;background:#f8fafc}
  .pageWrap{position:relative;background:#fff;border-radius:14px;border:1px solid #eef2f7;overflow:hidden}
  canvas{width:100%;height:auto;display:block}
  .textLayer{position:absolute;inset:0;opacity:1}
  .hl{
    background: rgba(255, 230, 0, .55);
    box-shadow: inset 0 -2px 0 rgba(255, 170, 0, .9);
    border-radius:4px;
  }
</style>

<div class="vWrap">
  <div class="vCard">
    <div class="vHead">
      <div>
        <div class="vTitle">{{ $pdf->original_filename }}</div>
        <div class="vMeta">Página: {{ (int)$page }} @if($q) — resaltando: “{{ $q }}” @endif</div>
      </div>
      <a href="{{ route('admin.licitacion-pdfs.ai.show', $pdf) }}" class="btn btn-sm btn-outline-dark">Volver</a>
    </div>

    <div class="vBody">
      <div class="pageWrap" id="pageWrap">
        <canvas id="pdfCanvas"></canvas>
        <div class="textLayer" id="textLayer"></div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
  (function(){
    const url = @json($pdfUrl);
    const pageNumber = Number(@json((int)$page)) || 1;
    const query = (@json($q) || '').trim();

    pdfjsLib.GlobalWorkerOptions.workerSrc =
      "https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js";

    const canvas = document.getElementById('pdfCanvas');
    const textLayerDiv = document.getElementById('textLayer');
    const pageWrap = document.getElementById('pageWrap');

    function normalize(s){
      return (s || '').toLowerCase().replace(/\s+/g,' ').trim();
    }

    pdfjsLib.getDocument(url).promise.then(async (pdf) => {
      const page = await pdf.getPage(pageNumber);

      const viewport = page.getViewport({ scale: 1.5 });
      const context = canvas.getContext('2d');

      canvas.width = viewport.width;
      canvas.height = viewport.height;

      pageWrap.style.maxWidth = viewport.width + "px";

      await page.render({ canvasContext: context, viewport }).promise;

      // Text layer
      const textContent = await page.getTextContent();
      textLayerDiv.innerHTML = '';
      textLayerDiv.style.width = viewport.width + 'px';
      textLayerDiv.style.height = viewport.height + 'px';

      await pdfjsLib.renderTextLayer({
        textContent,
        container: textLayerDiv,
        viewport,
        textDivs: []
      }).promise;

      if(query){
        // highlight simple: marca spans que contengan palabras del query
        const qNorm = normalize(query);
        const words = qNorm.split(' ').filter(w => w.length >= 4).slice(0, 10);

        const spans = textLayerDiv.querySelectorAll('span');
        spans.forEach(sp => {
          const t = normalize(sp.textContent);
          if(!t) return;

          // match por palabra o por frase contenida
          let hit = false;
          if(t.includes(qNorm)) hit = true;
          else hit = words.some(w => t.includes(w));

          if(hit) sp.classList.add('hl');
        });
      }
    }).catch(err => {
      console.error(err);
      alert('No se pudo cargar el PDF en el viewer.');
    });
  })();
</script>
@endsection
