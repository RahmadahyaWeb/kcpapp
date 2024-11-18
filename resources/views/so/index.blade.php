@extends('layouts.app')

@section('title', 'Data Sales Order')

@section('breadcrumb')
    <ol class="breadcrumb">
        <li class="breadcrumb-item active"><a href="{{ route('so.index') }}">Data Sales order</a></li>
    </ol>
@endsection

@section('content')
    <livewire:sales-order-table />
@endsection
