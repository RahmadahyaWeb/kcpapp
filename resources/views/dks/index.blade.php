@extends('layouts.app')

@section('title', 'DKS Scan')

@section('content')
    <x-alert />

    @livewire('dks-table')
@endsection
