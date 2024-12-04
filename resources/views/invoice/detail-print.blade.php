@extends('layouts.app')

@section('title', 'Detail Print Invoice')

@section('breadcrumb')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('inv.index') }}">Invoice</a></li>
        <li class="breadcrumb-item active"><a href="">Detail Print Invoice</a></li>
    </ol>
@endsection

@section('content')
    <livewire:invoice-detail-print :invoice="$invoice" />
@endsection
