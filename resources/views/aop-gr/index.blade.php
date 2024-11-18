@extends('layouts.app')

@section('title', 'Data Good Receipt AOP')

@section('breadcrumb')
    <ol class="breadcrumb">
        <li class="breadcrumb-item active"><a href="{{ route('aop-gr.index') }}">Data Good Receipt AOP</a></li>
    </ol>
@endsection

@section('content')
    <livewire:aop-gr />
@endsection
