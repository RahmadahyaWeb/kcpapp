@extends('layouts.app')

@section('title', "Detail $invoiceAop")

@section('breadcrumb')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('aop.index') }}">Data Upload AOP</a></li>
        <li class="breadcrumb-item active"><a href="">Detail</a></li>
    </ol>
@endsection

@section('content')
    <livewire:aop-detail :invoiceAop="$invoiceAop" />
@endsection

@push('scripts')
    @livewireScripts()
    <script>
        Livewire.on('fakturPajakUpdate', () => {
            $('#editFakturPajakModal').modal('hide');
        })

        Livewire.on('openModal', () => {
            $('#editFakturPajakModal').modal('show');
        })

        Livewire.on('programSaved', () => {
            $('#createProgramModal').modal('hide');
        })

        Livewire.on('openModalProgram', () => {
            $('#createProgramModal').modal('show');
        })
    </script>
@endpush
