@extends('layouts.app')

@section('title', "Detail Goods Receipt")

@section('breadcrumb')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('aop-gr.index') }}">Data Goods Receipt AOP</a></li>
        <li class="breadcrumb-item active"><a href="">Detail Goods Receipt AOP</a></li>
    </ol>
@endsection

@section('content')
    <livewire:aop-gr-detail :invoiceAop="$invoiceAop" />
@endsection
