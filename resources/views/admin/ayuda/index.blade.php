{{-- resources/views/admin/ayuda/index.blade.php --}}
@extends('layouts.app')
@section('title','Ayuda - Tickets')
@section('titulo','Centro de Ayuda (Staff)')

@section('content')
<style>
#staff-help{ --line:#e8eef6; --ink:#0e1726; --muted:#6b7280; --surface:#fff; --radius:16px }
#staff-help .wrap{max-width:1100px;margin:40px auto;padding:0 16px}
#staff-help table{width:100%; border-collapse:separate; border-spacing:0 10px}
#staff-help th, #staff-help td{padding:12px 14px}
#staff-help tr{background:var(--surface); border:1px solid var(--line)}
#staff-help a.btn{display:inline-flex; padding:8px 12px; border:1px solid var(--line); border-radius:12px; text-decoration:none}
#staff-help a.btn:hover{background:#f8fafc}
</style>
<div id="staff-help">
  <div class="wrap">
    <form method="get" style="margin-bottom:10px; display:flex; gap:10px">
      <select name="status" onchange="this.form.submit()">
        <option value="">Todos</option>
        @foreach(['pending_agent','ai_answered','waiting_user','new','agent_answered','closed'] as $s)
          <option value="{{ $s }}" @selected(request('status')===$s)>{{ $s }}</option>
        @endforeach
      </select>
    </form>

    <table>
      <thead>
        <tr>
          <th>ID</th><th>Asunto</th><th>Usuario</th><th>Estatus</th><th>Último movimiento</th><th></th>
        </tr>
      </thead>
      <tbody>
        @foreach($tickets as $t)
          <tr>
            <td>#{{ $t->id }}</td>
            <td>{{ $t->subject }}</td>
            <td>{{ $t->user?->name ?? 'N/D' }}</td>
            <td>{{ $t->status }}</td>
            <td>{{ optional($t->last_activity_at)->diffForHumans() }}</td>
            <td><a class="btn" href="{{ route('admin.help.show',$t) }}">Abrir</a></td>
          </tr>
        @endforeach
      </tbody>
    </table>

    <div style="margin-top:12px">{{ $tickets->withQueryString()->links() }}</div>
  </div>
</div>
@endsection
