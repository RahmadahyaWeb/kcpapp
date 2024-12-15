@extends('layouts.app')

@section('title', 'Data Non AOP')

@section('breadcrumb')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('non-aop.index') }}">Data Non AOP</a></li>
        <li class="breadcrumb-item active"><a href="">Tambah Data Non AOP</a></li>
    </ol>
@endsection

@section('content')
    <livewire:non-aop-create />
@endsection
