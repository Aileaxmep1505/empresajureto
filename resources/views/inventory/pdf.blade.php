<h2>Resguardo de Equipo</h2>
<p>Usuario: {{ $user->name }}</p>
<p>Fecha: {{ now()->format('d/m/Y H:i') }}</p>

<table width="100%" border="1" cellspacing="0" cellpadding="5">
  <thead>
    <tr>
      <th>Artículo</th>
      <th>Cantidad</th>
      <th>Fecha Entrega</th>
    </tr>
  </thead>
  <tbody>
    @foreach($assignments as $a)
    <tr>
      <td>{{ $a->item->name }}</td>
      <td>{{ $a->quantity }}</td>
      <td>{{ $a->assigned_at->format('d/m/Y H:i') }}</td>
    </tr>
    @endforeach
  </tbody>
</table>

@if($assignments->first()?->signature)
  <p>Firma:</p>
  <img src="{{ $assignments->first()->signature }}" height="80">
@endif
