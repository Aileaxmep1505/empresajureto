<!doctype html>
<html lang="es" class="h-full">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<title>@yield('title','Acceso')</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/auth.css') }}?v={{ time() }}">
</head>
<body class="min-h-screen">
<div class="bg-ornaments" aria-hidden="true">
<div class="blob b1"></div>
<div class="blob b2"></div>
<div class="blob b3"></div>
<div class="gridlines"></div>
</div>


<main class="container">
<section class="card" data-animate>
<div class="logo-wrap">
<div class="logo-circle">AE</div>
<div class="logo-text">Proyecto</div>
</div>
@if (session('status'))
<div class="alert success">{{ session('status') }}</div>
@endif
@if ($errors->any())
<div class="alert error">
<ul>
@foreach ($errors->all() as $error)
<li>{{ $error }}</li>
@endforeach
</ul>
</div>
@endif
@yield('content')
</section>
</main>


<script>
// Micro-interacciones: parallax sutil + fade-in
const card = document.querySelector('[data-animate]');
const parallax = (e)=>{
const x = (e.clientX / window.innerWidth - 0.5) * 6;
const y = (e.clientY / window.innerHeight - 0.5) * 6;
card.style.transform = `translate3d(${x}px, ${y}px, 0)`;
};
window.addEventListener('pointermove', parallax);


// Tarjeta entra con animación
requestAnimationFrame(()=>{
card.classList.add('in');
});
</script>
</body>
</html>