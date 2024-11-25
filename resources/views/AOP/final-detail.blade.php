@extends('layouts.app')

@section('title', "Detail $invoiceAop")

@section('breadcrumb')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('aop.final') }}">Data Final AOP</a></li>
        <li class="breadcrumb-item active"><a href="">Detail</a></li>
    </ol>
@endsection

@section('content')
    <livewire:aop-final-detail :invoiceAop="$invoiceAop" />
@endsection
