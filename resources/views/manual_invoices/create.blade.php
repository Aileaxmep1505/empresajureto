@extends('layouts.app')

@section('title', 'Nueva factura')

@section('content')
<form action="{{ route('manual_invoices.store') }}" method="POST">
    @csrf
    @include('manual_invoices._form', [
        'clients'  => $clients,
        'products' => $products,
    ])
</form>
@endsection
