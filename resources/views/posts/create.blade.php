@extends('layouts.app')
@section('title','Crear Publicación')
@section('content')

<h1>Crear Publicación</h1>

@if ($errors->any())
    <div style="color:red;">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ route('posts.store') }}" method="POST" enctype="multipart/form-data" id="postForm">
    @csrf

    <div style="margin-bottom:10px;">
        <label>Título:</label>
        <input type="text" name="titulo" required>
    </div>

    <div style="margin-bottom:10px;">
        <label>Descripción:</label>
        <textarea name="descripcion"></textarea>
    </div>

    <div style="margin-bottom:10px;">
        <label>Tipo:</label>
        <select name="tipo" id="tipoSelect" required>
            <option value="">--Selecciona--</option>
            <option value="foto">Foto</option>
            <option value="video">Video</option>
            <option value="documento">Documento</option>
        </select>
    </div>

    <div style="margin-bottom:10px;">
        <label>Fecha:</label>
        <input type="date" name="fecha" required>
    </div>

    <div style="margin-bottom:10px;">
        <label>Empresa:</label>
        <input type="text" name="empresa">
    </div>

    <div style="margin-bottom:10px;">
        <label>Archivo:</label>
        <div id="dropArea" style="border:2px dashed #ccc; padding:20px; text-align:center; cursor:pointer;">
            Arrastra tu archivo aquí o haz clic para seleccionar
        </div>
        <input type="file" name="archivo" id="archivoInput" style="display:none;" required>
        <div id="preview" style="margin-top:10px;"></div>
    </div>

    <button type="submit">Crear Publicación</button>
</form>

<script>
const dropArea = document.getElementById('dropArea');
const archivoInput = document.getElementById('archivoInput');
const preview = document.getElementById('preview');

dropArea.addEventListener('click', () => archivoInput.click());

dropArea.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropArea.style.backgroundColor = '#f0f0f0';
});

dropArea.addEventListener('dragleave', (e) => {
    e.preventDefault();
    dropArea.style.backgroundColor = '#fff';
});

dropArea.addEventListener('drop', (e) => {
    e.preventDefault();
    dropArea.style.backgroundColor = '#fff';
    archivoInput.files = e.dataTransfer.files;
    mostrarPreview(e.dataTransfer.files[0]);
});

archivoInput.addEventListener('change', () => {
    mostrarPreview(archivoInput.files[0]);
});

function mostrarPreview(file) {
    preview.innerHTML = '';
    const tipo = file.type;

    if(tipo.startsWith('image/')) {
        const img = document.createElement('img');
        img.src = URL.createObjectURL(file);
        img.style.maxWidth = '300px';
        preview.appendChild(img);
    } else if(tipo.startsWith('video/')) {
        const video = document.createElement('video');
        video.src = URL.createObjectURL(file);
        video.controls = true;
        video.style.maxWidth = '300px';
        preview.appendChild(video);
    } else {
        const div = document.createElement('div');
        div.textContent = `Archivo listo para subir: ${file.name}`;
        preview.appendChild(div);
    }
}
</script>

@endsection
