@extends('layouts.app')

@section('title', 'Invoice Bosnet')

@section('breadcrumb')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('inv.index') }}">Invoice</a></li>
        <li class="breadcrumb-item active"><a href="">Invoice Bosnet</a></li>
    </ol>
@endsection

@section('content')
    <livewire:invoice-bosnet />
@endsection
