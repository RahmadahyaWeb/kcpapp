@extends('layouts.app')

@section('title', 'Detail Goods Receipt Non AOP')

@section('breadcrumb')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('non-gr.index') }}">Data Goods Receipt Non AOP</a></li>
        <li class="breadcrumb-item active"><a href="{{ route('non-gr.index') }}">Detail Goods Receipt Non AOP</a></li>
    </ol>
@endsection

@section('content')
    <livewire:non-gr-detail :invoiceNon="$invoiceNon"/>
@endsection
