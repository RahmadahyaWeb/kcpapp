@extends('layouts.app')

@section('title', 'Master Toko')

@section('content')
    @if (session('success'))
        <div id="success-alert" class="alert alert-primary alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if (session('error'))
        <div id="error-alert" class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    <div class="card">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col">
                    <b>Master Toko</b>
                </div>
                <div class="col d-flex justify-content-end">
                    <a href="{{ route('master-toko.create') }}" class="btn btn-primary">
                        Tambah Toko
                    </a>
                </div>
            </div>
        </div>
        @livewire('master-toko-table')
    </div>
@endsection
