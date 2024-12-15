@extends('layouts.app')

@section('title', 'Data Goods Receipt Non AOP')

@section('breadcrumb')
    <ol class="breadcrumb">
        <li class="breadcrumb-item active"><a href="{{ route('non-gr.index') }}">Data Goods Receipt Non AOP</a></li>
    </ol>
@endsection

@section('content')
    <livewire:non-gr-table />
@endsection
