@extends('layouts.app')

@section('title', 'Invoice')

@section('breadcrumb')
    <ol class="breadcrumb">
        <li class="breadcrumb-item active"><a href="">Invoice</a></li>
    </ol>
@endsection

@section('content')
    <livewire:invoice-table />
@endsection
