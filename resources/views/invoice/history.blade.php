@extends('layouts.app')

@section('title', 'History Invoice')

@section('breadcrumb')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('inv.index') }}">Invoice</a></li>
        <li class="breadcrumb-item active"><a href="">History Invoice</a></li>
    </ol>
@endsection

@section('content')
    <livewire:invoice-history />
@endsection
