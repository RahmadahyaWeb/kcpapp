@extends('layouts.app')

@section('title', 'Data Accounts Receivable')

@section('breadcrumb')
    <ol class="breadcrumb">
        <li class="breadcrumb-item active"><a href="{{ route('ar.index') }}">Data Accounts Receivable</a></li>
    </ol>
@endsection

@section('content')
    <livewire:accounts-receivable-table />
@endsection
