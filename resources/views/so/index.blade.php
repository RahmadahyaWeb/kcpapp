@extends('layouts.app')

@section('title', 'Sales Order')

@section('breadcrumb')
    <ol class="breadcrumb">
        <li class="breadcrumb-item active"><a href="{{ route('so.index') }}">Sales order</a></li>
    </ol>
@endsection

@section('content')
    <livewire:sales-order-table />
@endsection
