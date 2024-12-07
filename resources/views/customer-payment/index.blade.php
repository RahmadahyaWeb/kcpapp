@extends('layouts.app')

@section('title', 'Data Customer Payment')

@section('breadcrumb')
    <ol class="breadcrumb">
        <li class="breadcrumb-item active"><a href="{{ route('customer-payment.index') }}">Data Customer Payment</a></li>
    </ol>
@endsection

@section('content')
    <livewire:customer-payment-table />
@endsection
