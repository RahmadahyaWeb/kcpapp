@extends('layouts.app')

@section('title', 'Comparator')

@section('breadcrumb')
    <ol class="breadcrumb">
        <li class="breadcrumb-item active"><a href="{{ route('aop.index') }}">Comparator</a></li>
    </ol>
@endsection

@section('content')
    <livewire:comparator-table />
@endsection
