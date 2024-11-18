@extends('layouts.app')

@section('title', 'Data Delivery Order')

@section('breadcrumb')
    <ol class="breadcrumb">
        <li class="breadcrumb-item active"><a href="{{ route('do.index') }}">Data Delivery Order</a></li>
    </ol>
@endsection

@section('content')
    <livewire:delivery-order-table />
@endsection
