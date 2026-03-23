@extends('layouts.app')

@section('title', 'WMS · Editar tarea picking')

@section('content')
@include('admin.wms.partials.picking-v2-form', [
  'mode' => 'edit',
  'action' => route('admin.wms.picking.v2.update', $task['id']),
  'method' => 'PATCH',
  'task' => $task,
  'products' => $products,
  'users' => $users,
  'nextTaskNumber' => $task['task_number'],
  'recentBatches' => $recentBatches,
])
@endsection