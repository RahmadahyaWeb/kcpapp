@extends('layouts.app')

@section('title', 'Invoice')

@section('breadcrumb')
    <ol class="breadcrumb">
        <li class="breadcrumb-item active"><a href="">Invoice</a></li>
    </ol>
@endsection

@section('content')
    @if ($nominal_gudang == $total)
        <div class="row g-2 mb-3">
            <div class="col-md-3 d-grid">
                <a href="" class="btn btn-success">Buat Invoice</a>
            </div>
        </div>
    @else
        <div class="alert alert-danger " role="alert">
            Ada part yang harganya tidak sesuai. Tolong validasi ulang (Reset Packingsheet dan Validasi Ulang)
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <b>Detail Sales Order yang belum Invoice</b>
        </div>
        <div class="card-body">

            @if ($dataSO->type_toko == 'V')
                <h4><b>VIP - ({{ $dataSO->keterangan }})</b></h4>
            @elseif ($dataSO->type_toko == 'G')
                <h4><b>GROSIR - ({{ $dataSO->keterangan }})</b></h4>
            @elseif ($dataSO->type_toko == 'S')
                <h4><b>SEMI GROSIS - ({{ $dataSO->keterangan }})</b></h4>
            @else
                <h4><b>RETAIL - ({{ $dataSO->keterangan }})</b></h4>
            @endif

            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="row mb-3">
                        <div class="col col-4 col-md-4">
                            <div>No. Sales Order / SO</div>
                        </div>
                        <div class="col col-auto">
                            :
                        </div>
                        <div class="col col-auto">
                            <div>KCP/{{ $dataSO->area_so }}/{{ $dataSO->noso }}</div>
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
                            <div>{{ $dataSO->kd_outlet }} / {{ $dataSO->nm_outlet }}</div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col col-4 col-md-4">
                            <div>Tanggal Jatuh Tempo</div>
                        </div>
                        <div class="col col-auto">
                            :
                        </div>
                        <div class="col col-auto">
                            <div>
                                {{ date('d-m-Y', strtotime('+' . $dataSO->jth_tempo . ' days')) }}
                                {{ $dataSO->jth_tempo == 0 ? ' CASH' : '' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>No. Part</th>
                            <th>Nama Part</th>
                            <th>QTY SO</th>
                            <th>QTY BO</th>
                            <th>QTY</th>
                            <th>Harga / Pcs</th>
                            <th>Disc (%)</th>
                            <th>Nominal</th>
                            <th>Nominal Kalkulasi Ulang</th>
                            <th>Nominal Disc</th>
                            <th>Nominal Invoice</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $item)
                            <tr>
                                <td>{{ $item->part_no }}</td>
                                <td>{{ $item->nm_part }}</td>
                                <td class="table-warning">
                                    {{ $item->qty }}
                                </td>
                                <td class="table-info">
                                    {{ $item->qty - $item->qty_gudang }}
                                </td>
                                <td class="table-success">
                                    {{ $item->qty_gudang }}
                                </td>
                                <td class="table-success">
                                    {{ number_format($item->hrg_pcs, 0, ',', '.') }}
                                </td>
                                <td class="table-success">
                                    {{ $item->disc }}%
                                </td>
                                <td class="table-success">
                                    {{ number_format($item->nominal_gudang, 0, ',', '.') }}
                                </td>
                                <td class="table-success">
                                    {{ number_format($item->qty_gudang * $item->hrg_pcs, 0, ',', '.') }}</td>
                                <td class="table-success">
                                    {{ number_format($item->nominal_disc_gudang, 0, ',', '.') }}
                                </td>
                                <td class="table-success">
                                    {{ number_format($item->nominal_total_gudang, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                        <tr>
                            <td colspan="10" class="text-center fw-bold">TOTAL</td>
                            <td>
                                {{ number_format($nominal_total, 0, ',', '.') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
