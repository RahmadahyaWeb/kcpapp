@extends('layouts.app')

@section('title', 'Data Goods Receipt AOP')

@section('breadcrumb')
    <ol class="breadcrumb">
        <li class="breadcrumb-item active"><a href="{{ route('aop-gr.index') }}">Data Goods Receipt AOP</a></li>
    </ol>
@endsection

@section('content')
    <livewire:aop-gr />
@endsection
