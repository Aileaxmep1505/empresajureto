@extends('layouts.app')

@section('title', 'Editar factura')

@section('content')
<form action="{{ route('manual_invoices.update', $invoice) }}" method="POST">
    @csrf
    @method('PUT')
    @include('manual_invoices._form', [
        'invoice'  => $invoice,
        'clients'  => $clients,
        'products' => $products,
    ])
</form>
@endsection
