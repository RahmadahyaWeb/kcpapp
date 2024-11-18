@extends('layouts.app')

@section('title', 'Data AOP')

@section('breadcrumb')
    <ol class="breadcrumb">
        <li class="breadcrumb-item active"><a href="{{ route('aop.index') }}">Data Upload AOP</a></li>
    </ol>
@endsection

@section('content')
    <livewire:aop-upload />
@endsection
