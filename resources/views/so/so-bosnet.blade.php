@extends('layouts.app')

@section('title', 'Sales Order Bosnet')

@section('breadcrumb')
    <ol class="breadcrumb">
        <li class="breadcrumb-item active"><a href="{{ route('so-bosnet.index') }}">Sales Order Bosnet</a></li>
    </ol>
@endsection

@section('content')
    <livewire:sales-order-bosnet />
@endsection
