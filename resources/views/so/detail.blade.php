@extends('layouts.app')

@section('title', 'Detail Sales Order')

@section('breadcrumb')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('so.index') }}">Data Sales order</a></li>
        <li class="breadcrumb-item active"><a href="">Detail Sales Order</a></li>
    </ol>
@endsection

@section('content')
    <livewire:sales-order-detail :invoice="$invoice" />
@endsection
