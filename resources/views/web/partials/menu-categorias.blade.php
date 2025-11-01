{{-- resources/views/web/partials/menu-categorias.blade.php --}}
{{-- Menú reutilizable de categorías principales. Recibe $primary --}}
<div id="catmenu">
  <style>
    #catmenu{font-family:system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial}
    #catmenu .title{font-weight:800;font-size:1.05rem;color:#0f172a;margin-bottom:.5rem}
    #catmenu ul{list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:.35rem}
    #catmenu a{color:#0f172a;text-decoration:none}
    #catmenu a:hover{text-decoration:underline}
  </style>

  <div class="title">Principales</div>
  <ul>
    @forelse($primary as $cat)
      <li><a href="{{ route('web.categorias.show', $cat->slug) }}">{{ $cat->name }}</a></li>
    @empty
      {{-- Fallback por si aún no se ejecuta el seeder --}}
      <li><a href="{{ url('/categoria/papeleria') }}">Artículos de Papelería</a></li>
      <li><a href="{{ url('/categoria/hojas') }}">Hojas para imprimir</a></li>
    @endforelse
  </ul>
</div>
