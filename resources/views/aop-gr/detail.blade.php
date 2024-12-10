@extends('layouts.app')

@section('title', "Detail Good Receipt")

@section('breadcrumb')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('aop-gr.index') }}">Data Good Receipt AOP</a></li>
        <li class="breadcrumb-item active"><a href="">Detail</a></li>
    </ol>
@endsection

@section('content')
    <livewire:aop-gr-detail :invoiceAop="$invoiceAop" />
@endsection
