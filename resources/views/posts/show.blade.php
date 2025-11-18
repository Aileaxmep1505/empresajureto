@extends('layouts.app')
@section('title', $post->titulo)
@section('content')

<h1>{{ $post->titulo }}</h1>
<p>{{ $post->descripcion }}</p>

@if($post->tipo == 'foto')
    <img src="{{ Storage::url($post->archivo) }}" width="400">
@elseif($post->tipo == 'video')
    <video width="640" controls>
        <source src="{{ Storage::url($post->archivo) }}" type="video/mp4">
    </video>
@else
    <a href="{{ Storage::url($post->archivo) }}" target="_blank">Descargar archivo</a>
@endif

<hr>
<h3>Comentarios</h3>

@foreach($comentarios as $comentario)
    <div style="border:1px solid #ccc; padding:5px; margin-bottom:5px;">
        <strong>{{ $comentario->usuario }}</strong> ({{ $comentario->created_at->diffForHumans() }}):
        <p>{{ $comentario->comentario }}</p>
    </div>
@endforeach

<h4>Agregar comentario</h4>
<form action="{{ route('posts.comment', $post) }}" method="POST">
    @csrf
    <input type="text" name="usuario" placeholder="Tu nombre" required>
    <textarea name="comentario" placeholder="Escribe tu comentario" required></textarea>
    <button type="submit">Enviar</button>
</form>

@endsection
