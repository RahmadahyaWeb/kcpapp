@extends('layouts.app')

@section('title', 'Invoice')

@section('breadcrumb')
    <ol class="breadcrumb">
        <li class="breadcrumb-item active"><a href="">Invoice</a></li>
    </ol>
@endsection

@section('content')
    <div class="row g-2 mb-3">
        <div class="col-md-3 d-grid">
            <a href="" class="btn btn-success">Buat Invoice</a>
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            <b>Detail Sales Order yang belum Invoice</b>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="row mb-3">
                        <div class="col col-4 col-md-4">
                            <div>No. Sales Order / SO</div>
                        </div>
                        <div class="col col-auto">
                            :
                        </div>
                        <div class="col col-auto">
                            <div>KCP/{{ $item[0]->area_so }}/{{ $item[0]->noso }}</div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col col-4 col-md-4">
                            <div>Kode / Nama Toko</div>
                        </div>
                        <div class="col col-auto">
                            :
                        </div>
                        <div class="col col-auto">
                            <div>{{ $item[0]->kd_outlet }} / {{ $item[0]->nm_outlet }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endsection
