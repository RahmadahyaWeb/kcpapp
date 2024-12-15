@extends('layouts.app')

@section('title', "Detail $invoiceNon")

@section('breadcrumb')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('non-aop.index') }}">Data Non AOP</a></li>
        <li class="breadcrumb-item active"><a href="">Detail Invoice Non AOP</a></li>
    </ol>
@endsection

@section('content')
    <livewire:non-aop-detail :invoiceNon="$invoiceNon" />
@endsection