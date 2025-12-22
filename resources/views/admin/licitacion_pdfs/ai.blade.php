@extends('layouts.app')

@section('title', 'Chat IA del PDF')

@section('content')
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

<style>
.aiWrap{max-width:1200px;margin:0 auto;padding:18px 14px 26px}
.aiTop{display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:flex-start;margin-bottom:12px}
.aiTitle{margin:0;font-size:18px;font-weight:950;color:#0b1220}
.aiSub{margin:6px 0 0;color:#667085;font-size:13px;max-width:90ch}
.aiBtns{display:flex;gap:10px;flex-wrap:wrap}
.aiBtn{border-radius:999px;border:1px solid #e6eaf2;background:#fff;color:#0b1220;font-weight:900;font-size:13px;padding:10px 14px;display:inline-flex;gap:10px;align-items:center;text-decoration:none;cursor:pointer}
.aiBtnBlack{background:linear-gradient(180deg,#0b1220,#0f172a);color:#fff;border-color:transparent}
.aiGrid{display:grid;grid-template-columns:minmax(0,1fr);gap:12px}
.aiCard{border:1px solid #e6eaf2;border-radius:18px;background:#fff;box-shadow:0 18px 55px rgba(2,6,23,.08);overflow:hidden}
.aiChatBody{height:min(70vh,720px);overflow:auto;padding:14px;background:linear-gradient(180deg,#fff,#fcfdff)}
.row{display:flex;margin:10px 0}
.bubble{max-width:min(820px,94%);padding:10px 12px;border-radius:16px;border:1px solid #eef2f7;white-space:pre-wrap}
.me{justify-content:flex-end}
.me .bubble{background:#0b1220;color:#fff;border-color:#0b1220}
.ai{justify-content:flex-start}
.ai .bubble{background:#f8fafc;color:#0b1220}
.aiBar{display:flex;gap:10px;padding:12px;border-top:1px solid #e6eaf2;background:#fff}
.aiInput{flex:1;border:1px solid #eef2f7;border-radius:14px;padding:10px 12px;font-size:14px;outline:none}
.aiSend{border:none;border-radius:999px;padding:10px 14px;font-weight:900;background:#0b1220;color:#fff;cursor:pointer}
.aiSend:disabled{opacity:.6;cursor:not-allowed}

/* modal */
.mOverlay{position:fixed;inset:0;background:rgba(2,6,23,.55);display:flex;align-items:center;justify-content:center;z-index:200;padding:16px}
.mBox{width:min(1100px,100%);height:min(85vh,820px);background:#0b1220;border-radius:18px;overflow:hidden;border:1px solid rgba(255,255,255,.10);box-shadow:0 26px 60px rgba(2,6,23,.55);display:flex;flex-direction:column}
.mHead{display:flex;justify-content:space-between;align-items:center;gap:10px;padding:10px 12px;color:#e5e7eb;border-bottom:1px solid rgba(255,255,255,.10)}
.mIfr{flex:1;border:none;width:100%;background:#0b1220}
</style>

@php
  $messagesPayload = $messages->map(fn($m)=>[
    'role' => $m->role,
    'content' => $m->content,
  ])->values();
@endphp

<div class="aiWrap"
  x-data='{
    openPdf:false,
    sending:false,
    msg:"",
    items: @json($messagesPayload),

    csrf(){
      const el = document.querySelector("meta[name=csrf-token]");
      return el ? el.getAttribute("content") : "{{ csrf_token() }}";
    },

    async send(){
      const t = (this.msg || "").trim();
      if(!t || this.sending) return;

      this.items.push({role:"user", content:t});
      this.msg = "";
      this.sending = true;

      this.$nextTick(()=>{
        const el = this.$refs.body;
        if(el) el.scrollTop = el.scrollHeight;
      });

      try{
        const res = await fetch("{{ route('admin.licitacion-pdfs.ai.message', $pdf) }}", {
          method: "POST",
          credentials: "same-origin",
          headers: {
            "Content-Type": "application/json",
            "Accept": "application/json",
            "X-Requested-With": "XMLHttpRequest",
            "X-CSRF-TOKEN": this.csrf()
          },
          body: JSON.stringify({ message: t })
        });

        const raw = await res.text();
        let j = null;
        try { j = JSON.parse(raw); } catch(e) {}

        if(!res.ok){
          const msg = (j && (j.message || j.error)) ? (j.message || j.error) : raw;
          this.items.push({role:"assistant", content: "Error ("+res.status+"): "+msg});
          return;
        }

        this.items.push({role:"assistant", content: (j && j.answer) ? j.answer : "No pude responder."});
      }catch(e){
        this.items.push({role:"assistant", content:"Error enviando mensaje (fetch). Revisa consola / logs."});
      }finally{
        this.sending = false;
        this.$nextTick(()=>{
          const el = this.$refs.body;
          if(el) el.scrollTop = el.scrollHeight;
        });
      }
    }
  }'
>
  <div class="aiTop">
    <div>
      <h1 class="aiTitle">Chat IA — {{ $pdf->original_filename }}</h1>
      <p class="aiSub">Pregúntale lo que sea de este PDF. La IA solo usa este documento como contexto.</p>
    </div>

    <div class="aiBtns">
      <button class="aiBtn aiBtnBlack" type="button" @click="openPdf=true">Ver PDF</button>
      <a class="aiBtn" href="{{ route('admin.licitacion-pdfs.index') }}">Volver</a>
    </div>
  </div>

  <div class="aiGrid">
    <div class="aiCard">
      <div class="aiChatBody" x-ref="body">
        <template x-for="(it,idx) in items" :key="idx">
          <div class="row" :class="it.role === 'user' ? 'me' : 'ai'">
            <div class="bubble" x-text="it.content"></div>
          </div>
        </template>
      </div>

      <div class="aiBar">
        <input class="aiInput" type="text" placeholder="Escribe tu pregunta…"
               x-model="msg" @keydown.enter.prevent="send()">
        <button class="aiSend" type="button" @click="send()" :disabled="sending">
          <span x-text="sending ? 'Enviando…' : 'Enviar'"></span>
        </button>
      </div>
    </div>
  </div>

  <!-- MODAL PDF -->
  <div class="mOverlay" x-show="openPdf" x-transition.opacity @keydown.escape.window="openPdf=false" style="display:none">
    <div class="mBox" @click.away="openPdf=false">
      <div class="mHead">
        <div style="font-weight:900;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
          {{ $pdf->original_filename }}
        </div>
        <button class="aiBtn" type="button" @click="openPdf=false">Cerrar</button>
      </div>

      <iframe class="mIfr" src="{{ route('admin.licitacion-pdfs.preview', ['licitacionPdf' => $pdf->id]) }}"></iframe>
    </div>
  </div>
</div>
@endsection
