@extends('layouts.app')
@section('title', 'Bitácora de actividad (todas las empresas)')

@section('content')
@php
  // ✅ Traducción de acciones (para cliente)
  $actionLabels = [
    'pc_unlock'          => 'Accedió con NIP',
    'pc_unlock_failed'   => 'Intento de acceso fallido',
    'pc_lock'            => 'Cerró el acceso',
    'pc_upload'          => 'Subió un archivo',
    'pc_delete'          => 'Eliminó un archivo',
    'pc_preview'         => 'Abrió vista previa',
    'pc_download'        => 'Descargó un archivo',
    'pc_view_activity'   => 'Abrió la bitácora (empresa)',
    'pc_view_activity_all'=> 'Abrió la bitácora (general)',
  ];

  // ✅ Cómo “resumir” meta para humanos
  $metaResumen = function($r) {
    $m = $r->meta ?? [];
    if (!is_array($m)) return '—';

    // Ejemplos de resumen
    if (($r->action ?? '') === 'pc_upload') {
      $t = $m['title'] ?? ($r->document?->title ?? 'Documento');
      return "Documento: {$t}";
    }
    if (($r->action ?? '') === 'pc_delete') {
      $t = $m['title'] ?? 'Documento';
      return "Documento eliminado: {$t}";
    }
    if (($r->action ?? '') === 'pc_download') {
      $t = $m['title'] ?? ($r->document?->title ?? 'Documento');
      return "Descargó: {$t}";
    }
    if (($r->action ?? '') === 'pc_preview') {
      $t = $m['title'] ?? ($r->document?->title ?? 'Documento');
      return "Vio: {$t}";
    }
    if (($r->action ?? '') === 'pc_unlock_failed') {
      return "Motivo: ".($m['reason'] ?? 'NIP incorrecto');
    }
    if (($r->action ?? '') === 'pc_unlock') {
      return "Tiempo de acceso: ".($m['ttl_min'] ?? '—')." min";
    }

    // Default: si hay title
    if (!empty($m['title'])) return "Documento: ".$m['title'];

    return '—';
  };
@endphp

<style>
  :root{
    --bg:#ffffff;
    --muted:#6b7280;
    --text:#0b1220;
    --border:#e5e7eb;
    --card: rgba(255,255,255,0.92);
    --shadow: 0 12px 30px rgba(20,24,40,0.08);
    --radius:14px;
    font-family: Inter, ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
  }

  .ua-wrap{padding:18px;color:var(--text);}
  .ua-top{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:12px;}
  .ua-title{margin:0;font-size:22px;font-weight:850;letter-spacing:-.02em;}

  .ua-card{
    background: var(--card);
    border: 1px solid rgba(15,23,42,0.08);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    overflow:hidden;
  }

  .ua-card-h{
    padding:12px 14px;
    border-bottom:1px solid rgba(15,23,42,0.06);
    display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;
    background: linear-gradient(90deg,#ffffff 0,#f3f4f6 45%,#ffffff 100%);
  }
  .ua-h-left{display:flex;flex-direction:column;gap:2px;}
  .ua-h-main{font-size:14px;font-weight:800;margin:0;}
  .ua-h-sub{font-size:12px;color:var(--muted);margin:0;}

  .ua-filters{display:flex;gap:8px;flex-wrap:wrap;align-items:center;}
  .ua-in, .ua-sel{
    padding:8px 10px;border-radius:10px;border:1px solid var(--border);
    font-size:13px; background:#fff; color:#111827;
  }
  .ua-btn{
    padding:8px 12px;border-radius:999px;border:1px solid var(--border);
    background:#111827;color:#fff;font-weight:800;font-size:13px;cursor:pointer;
  }
  .ua-btn-ghost{background:#fff;color:#111827;}

  .ua-note{
    padding:10px 14px;
    font-size:12px;
    color: var(--muted);
    border-bottom:1px solid rgba(15,23,42,0.06);
  }

  .ua-table{width:100%;border-collapse:collapse;}
  .ua-table th, .ua-table td{
    padding:12px 14px;
    border-bottom:1px solid rgba(15,23,42,0.06);
    vertical-align:top;
    font-size:13px;
  }
  .ua-table th{
    font-size:12px;
    text-transform:uppercase;
    letter-spacing:.08em;
    color:#6b7280;
    background:#fafafa;
  }

  .ua-badge{
    display:inline-flex;align-items:center;gap:6px;
    padding:5px 10px;border-radius:999px;
    border:1px solid rgba(15,23,42,0.08);
    background:#fff;
    font-weight:800;
    font-size:12px;
    white-space:nowrap;
  }
  .ua-dot{width:8px;height:8px;border-radius:999px;background:#22c55e;}
  .ua-dot.warn{background:#f59e0b;}
  .ua-dot.err{background:#ef4444;}

  .ua-foot{padding:12px 14px;}

  .ua-muted{color:var(--muted);font-size:12px;}
</style>

<div class="ua-wrap">
  <div class="ua-top">
    <h1 class="ua-title">Bitácora de actividad (todas las empresas)</h1>
  </div>

  <div class="ua-card">
    <div class="ua-card-h">
      <div class="ua-h-left">
        <p class="ua-h-main">Registro de accesos y acciones</p>
        <p class="ua-h-sub">Este registro es permanente y no se puede eliminar desde el sistema.</p>
      </div>

      <form class="ua-filters" method="GET" action="{{ route('partcontable.activity.all') }}">
        <input class="ua-in" type="text" name="q" placeholder="Buscar (usuario, empresa, documento, IP)" value="{{ $q ?? '' }}">

        <select class="ua-sel" name="company_id">
          <option value="">-- Empresa --</option>
          @foreach($companies as $c)
            <option value="{{ $c->id }}" @selected((string)($companyId ?? '') === (string)$c->id)>{{ $c->name }}</option>
          @endforeach
        </select>

        <select class="ua-sel" name="action">
          <option value="">-- Tipo de acción --</option>
          @foreach($actions as $a)
            @php $label = $actionLabels[$a] ?? $a; @endphp
            <option value="{{ $a }}" @selected(($action ?? '') === $a)>{{ $label }}</option>
          @endforeach
        </select>

        <select class="ua-sel" name="user_id">
          <option value="">-- Usuario --</option>
          @foreach($users as $u)
            <option value="{{ $u->id }}" @selected((string)($userId ?? '') === (string)$u->id)>{{ $u->name }}</option>
          @endforeach
        </select>

        <button class="ua-btn" type="submit">Filtrar</button>
        <a class="ua-btn ua-btn-ghost" href="{{ route('partcontable.activity.all') }}">Limpiar</a>
      </form>
    </div>

    <div class="ua-note">
      Ejemplos: <strong>Accedió con NIP</strong>, <strong>Subió un archivo</strong>, <strong>Eliminó un archivo</strong>, <strong>Descargó</strong>, <strong>Vista previa</strong>.
    </div>

    <div style="overflow:auto;">
      <table class="ua-table">
        <thead>
          <tr>
            <th>Fecha</th>
            <th>Acción</th>
            <th>Usuario</th>
            <th>Empresa</th>
            <th>Documento</th>
            <th>IP / Navegador</th>
            <th>Detalle</th>
          </tr>
        </thead>
        <tbody>
          @forelse($rows as $r)
            @php
              $dot = 'ua-dot';
              if (str_contains($r->action ?? '', 'failed')) $dot .= ' err';
              elseif (str_contains($r->action ?? '', 'delete')) $dot .= ' warn';

              $accionHumana = $actionLabels[$r->action] ?? ($r->action ?? '—');
            @endphp

            <tr>
              <td style="white-space:nowrap;">
                {{ optional($r->created_at)->format('d M Y H:i') }}
              </td>

              <td>
                <span class="ua-badge">
                  <span class="{{ $dot }}"></span>
                  {{ $accionHumana }}
                </span>
                <div class="ua-muted">({{ $r->action }})</div>
              </td>

              <td>
                <div style="font-weight:800;">
                  {{ $r->user?->name ?? 'Sistema' }}
                </div>
                <div class="ua-muted">
                  {{ $r->user?->email ?? '' }}
                </div>
              </td>

              <td>
                <div style="font-weight:800;">
                  {{ $r->company?->name ?? '—' }}
                </div>
                @if($r->company?->slug)
                  <div class="ua-muted">{{ $r->company->slug }}</div>
                @endif
              </td>

              <td>
                @if($r->document)
                  <div style="font-weight:800;">{{ \Illuminate\Support\Str::limit($r->document->title, 42) }}</div>
                  <div class="ua-muted">ID: {{ $r->document_id }}</div>
                @else
                  <span class="ua-muted">—</span>
                @endif
              </td>

              <td>
                <div style="font-weight:800;">{{ $r->ip ?? '—' }}</div>
                <div class="ua-muted">
                  {{ \Illuminate\Support\Str::limit($r->user_agent ?? '—', 70) }}
                </div>
              </td>

              <td>
                {{ $metaResumen($r) }}
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" style="text-align:center;color:#6b7280;padding:18px;">
                No hay actividad registrada todavía.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="ua-foot">
      {{ $rows->withQueryString()->links() }}
    </div>
  </div>
</div>
@endsection
