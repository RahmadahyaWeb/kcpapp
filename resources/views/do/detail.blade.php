@extends('layouts.app')

@section('title', 'Detail Delivery Order')

@section('breadcrumb')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('do.index') }}">Data Delivery Order</a></li>
        <li class="breadcrumb-item active"><a href="{{ route('do.index') }}">Detail Delivery Order</a></li>
    </ol>
@endsection

@section('content')
    <livewire:delivery-order-detail :lkh="$lkh" />
@endsection
