@extends('layouts.app')
@section('title','Nuevo evento')

@section('content')
<div id="agenda-create" class="container" style="max-width:900px; margin:32px auto; padding:0 16px; font-family:'Outfit',system-ui;">
  <style>
    #agenda-create{--ink:#0f172a;--muted:#667085;--line:#e8eef6;--bg:#f6f8fc;--card:#fff;--brand:#6ea8fe}
    #agenda-create .card{background:var(--card); border:1px solid var(--line); border-radius:16px; padding:18px}
    #agenda-create label{display:block; font-weight:600; color:var(--ink); margin:10px 0 6px}
    #agenda-create input, #agenda-create select, #agenda-create textarea{
      width:100%; padding:10px 12px; border:1px solid var(--line); border-radius:12px; background:#fff; color:var(--ink)
    }
    #agenda-create .grid{display:grid; grid-template-columns:1fr 1fr; gap:12px}
    #agenda-create .actions{display:flex; gap:10px; margin-top:16px}
    #agenda-create .btn{padding:10px 14px; border-radius:12px; border:1px solid var(--line); background:#fff; text-decoration:none; color:var(--ink)}
    #agenda-create .btn.primary{background:var(--brand); color:#0b1220; font-weight:700; box-shadow:0 8px 24px rgba(110,168,254,.35)}
  </style>

  <div class="card">
    <form action="{{ route('agenda.store') }}" method="POST">
      @csrf

      <label>Título *</label>
      <input name="title" required>

      <label>Descripción</label>
      <textarea name="description" rows="3"></textarea>

      <div class="grid">
        <div>
          <label>Fecha y hora del evento *</label>
          <input type="datetime-local" name="start_at" required>
        </div>
        <div>
          <label>Recordar (minutos antes) *</label>
          <input type="number" name="remind_offset_minutes" value="60" min="1" max="10080" required>
        </div>
      </div>

      <div class="grid">
        <div>
          <label>Repetición *</label>
          <select name="repeat_rule">
            <option value="none">Sin repetición</option>
            <option value="daily">Diaria</option>
            <option value="weekly">Semanal</option>
            <option value="monthly">Mensual</option>
          </select>
        </div>
        <div>
          <label>Zona horaria *</label>
          <input name="timezone" value="America/Mexico_City" required>
        </div>
      </div>

      <div class="grid">
        <div>
          <label>Nombre del destinatario</label>
          <input name="attendee_name">
        </div>
        <div>
          <label>Email del destinatario</label>
          <input type="email" name="attendee_email">
        </div>
      </div>

      <div class="grid">
        <div>
          <label>Teléfono WhatsApp (con código país, ej. 521234567890)</label>
          <input name="attendee_phone">
        </div>
        <div>
          <label>Canales de envío</label>
          <div style="display:flex; gap:16px; align-items:center; margin-top:8px;">
            <label style="display:flex; gap:6px; align-items:center;"><input type="checkbox" name="send_email" checked> Email</label>
            <label style="display:flex; gap:6px; align-items:center;"><input type="checkbox" name="send_whatsapp"> WhatsApp</label>
          </div>
        </div>
      </div>

      <div class="actions">
        <a class="btn" href="{{ route('agenda.index') }}">Cancelar</a>
        <button class="btn primary" type="submit">Guardar</button>
      </div>
    </form>
  </div>
</div>
@endsection
