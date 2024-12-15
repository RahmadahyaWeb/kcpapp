@extends('layouts.app')

@section('title', 'Data Non AOP')

@section('breadcrumb')
    <ol class="breadcrumb">
        <li class="breadcrumb-item active"><a href="{{ route('aop.index') }}">Data Non AOP</a></li>
    </ol>
@endsection

@section('content')
    <livewire:non-aop-table />
@endsection
