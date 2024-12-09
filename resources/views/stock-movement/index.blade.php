@extends('layouts.app')

@section('title', 'Data Stock Movement')

@section('breadcrumb')
    <ol class="breadcrumb">
        <li class="breadcrumb-item active"><a href="{{ route('stock-movement.index') }}">Data Stock Movement</a></li>
    </ol>
@endsection

@section('content')
    <livewire:stock-movement-table />
@endsection
