@extends('layouts.app')
@section('title','Agenda')

@section('content')
<div id="agenda" class="container" style="max-width:1100px; margin:32px auto; padding:0 16px; font-family: 'Outfit', system-ui;">
  <style>
    #agenda{--ink:#0f172a;--muted:#667085;--line:#e8eef6;--bg:#f6f8fc;--card:#fff;--brand:#6ea8fe}
    #agenda .top{display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:18px}
    #agenda h1{font-size:22px; color:var(--ink); margin:0}
    #agenda a.btn{display:inline-flex; align-items:center; gap:8px; padding:10px 14px; border-radius:12px; background:var(--brand); color:#0b1220; text-decoration:none; font-weight:700; box-shadow:0 8px 24px rgba(110,168,254,.35)}
    #agenda .card{background:var(--card); border:1px solid var(--line); border-radius:16px; padding:16px}
    #agenda table{width:100%; border-collapse:collapse}
    #agenda th,#agenda td{padding:10px 8px; border-bottom:1px solid var(--line); font-size:14px}
    #agenda th{color:var(--muted); font-weight:600; text-align:left}
    #agenda td .pill{display:inline-flex; padding:4px 8px; border-radius:999px; font-size:12px; background:#eef4ff; color:#1d4ed8}
    #agenda .actions{display:flex; gap:8px}
    #agenda .btn-small{padding:6px 10px; border-radius:10px; border:1px solid var(--line); text-decoration:none; color:var(--ink); background:#fff}
    #agenda .btn-danger{border-color:#fecaca; color:#7f1d1d; background:#fff5f5}
  </style>

  @if(session('ok'))
    <div class="card" style="border-left:4px solid #86efac; margin-bottom:12px; color:#064e3b;">{{ session('ok') }}</div>
  @endif

  <div class="top">
    <h1>Agenda</h1>
    <a class="btn" href="{{ route('agenda.create') }}">➕ Nuevo evento</a>
  </div>

  <div class="card">
    <table>
      <thead>
        <tr>
          <th>Título</th>
          <th>Inicio</th>
          <th>Recordar</th>
          <th>Canales</th>
          <th>Próx. aviso</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @forelse($events as $ev)
          <tr>
            <td>{{ $ev->title }}</td>
            <td>{{ $ev->start_at->setTimezone($ev->timezone)->format('d/m/Y H:i') }} <span style="color:#98a2b3">({{ $ev->timezone }})</span></td>
            <td>{{ $ev->remind_offset_minutes }} min antes ({{ $ev->repeat_rule }})</td>
            <td>
              @if($ev->send_email) <span class="pill">Email</span> @endif
              @if($ev->send_whatsapp) <span class="pill" style="background:#e8fff4;color:#047857">WhatsApp</span> @endif
            </td>
            <td>{{ $ev->next_reminder_at ? $ev->next_reminder_at->setTimezone($ev->timezone)->format('d/m/Y H:i') : '—' }}</td>
            <td class="actions">
              <a class="btn-small" href="{{ route('agenda.edit',$ev) }}">Editar</a>
              <form action="{{ route('agenda.destroy',$ev) }}" method="POST" onsubmit="return confirm('¿Eliminar evento?')">
                @csrf @method('DELETE')
                <button class="btn-small btn-danger" type="submit">Eliminar</button>
              </form>
            </td>
          </tr>
        @empty
          <tr><td colspan="6" style="color:#98a2b3">Sin eventos todavía.</td></tr>
        @endforelse
      </tbody>
    </table>
    <div style="margin-top:12px;">{{ $events->links() }}</div>
  </div>
</div>
@endsection
