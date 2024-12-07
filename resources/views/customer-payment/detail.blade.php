@extends('layouts.app')

@section('title', 'Detail Customer Payment')

@section('breadcrumb')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('customer-payment.index') }}">Data Customer Payment</a></li>
        <li class="breadcrumb-item active"><a href="">Detail Customer Payment</a></li>
    </ol>
@endsection

@section('content')
    <livewire:customer-payment-detail :no_piutang="$no_piutang" />
@endsection
