@extends('layouts.app')

@section('title', 'Detail History Invoice')

@section('breadcrumb')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('inv.history') }}">History Invoice</a></li>
        <li class="breadcrumb-item active"><a href="">Detail History Invoice</a></li>
    </ol>
@endsection

@section('content')
    <div class="row gap-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    Detail Invoice: <b>{{ $header->noinv }}</b>
                </div>

                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            @foreach ([
            'No Invoice' => $header->noinv,
            'No Sales Order' => $header->noso,
            'Toko' => $header->nm_outlet,
            'Amount' => 'Rp ' . number_format($header->amount, 0, ',', '.'),
            'Amount Discount' => 'Rp ' . number_format($header->amount_disc, 0, ',', '.'),
            'Support Program' => 'Rp ' . number_format($nominalSuppProgram, 0, ',', '.'),
            'Amount Total' => 'Rp ' . number_format($header->amount_total, 0, ',', '.'),
        ] as $label => $value)
                                <div class="row mb-3">
                                    <div class="col-4 col-md-4">{{ $label }}</div>
                                    <div class="col-auto">:</div>
                                    <div class="col-auto">{{ $value }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    Detail Material: <b>{{ $header->noinv }}</b>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>No Part</th>
                                    <th>Nama Part</th>
                                    <th>Qty</th>
                                    <th>Harga / Pcs (Rp)</th>
                                    <th>Disc (%)</th>
                                    <th>Nominal (Rp)</th>
                                    <th>Nominal Discount (Rp)</th>
                                    <th>Nominal Total (Rp)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($details as $invoice)
                                    <tr>
                                        <td>{{ $invoice->part_no }}</td>
                                        <td>{{ $invoice->nm_part }}</td>
                                        <td>{{ $invoice->qty }}</td>
                                        <td>{{ number_format($invoice->hrg_pcs, 0, ',', '.') }}</td>
                                        <td>{{ $invoice->disc }}</td>
                                        <td>{{ number_format($invoice->nominal, 0, ',', '.') }}</td>
                                        <td>{{ number_format($invoice->nominal_disc, 0, ',', '.') }}</td>
                                        <td>{{ number_format($invoice->nominal_total, 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">No Data</td>
                                    </tr>
                                @endforelse
                                <tr>
                                    <td colspan="7" class="fw-bold">Total</td>
                                    <td>{{ number_format($sumTotalDPP, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td colspan="7" class="fw-bold">Support Program</td>
                                    <td>{{ number_format($nominalSuppProgram, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td colspan="7" class="fw-bold">Grand Total</td>
                                    <td>{{ number_format($sumTotalDPP - $nominalSuppProgram, 0, ',', '.') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
