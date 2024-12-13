<div>
    <x-alert />
    <x-loading :target="$target" />

    <div class="card mb-3">
        <div class="card-header">
            Data Accounts Receivable
        </div>
        <div class="card-body">
            @if ($items->isEmpty())
                @if ($selected_kd_outlet)
                    <div class="alert alert-warning">
                        Tidak ada piutang untuk toko yang dipilih.
                    </div>
                @else
                    <div class="alert alert-warning">
                        Silakan pilih toko terlebih dahulu.
                    </div>
                @endif
            @endif

            <div class="row mb-3 g-2">
                <div class="col-md-4">
                    <div class="mb-1">
                        <label class="form-label">Cari berdasarkan kode toko / outlet</label>
                        <input type="text" class="form-control" placeholder="Kode Toko / Outlet"
                            wire:model.live.debounce.1000ms="kd_outlet">
                    </div>
                    <div>
                        <select class="form-select" wire:model.change="selected_kd_outlet">
                            <option value="">Pilih Toko / Outlet</option>

                            @foreach ($list_toko as $item)
                                <option value="{{ $item->kd_outlet }}">{{ $item->nm_outlet }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            @if ($total_piutang > 0 && $total_payment >= 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Kode Toko</th>
                                <th>Nama Toko</th>
                                <th>Total Piutang</th>
                                <th>Total Pembayaran</th>
                                <th>Sisa Piutang</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>{{ $items->first()->kd_outlet }}</td>
                                <td>{{ $items->first()->nm_outlet }}</td>
                                <td>{{ number_format($total_piutang, 0, ',', '.') }}</td>
                                <td>{{ number_format($total_payment, 0, ',', '.') }}</td>
                                <td>{{ number_format($total_piutang - $total_payment, 0, ',', '.') }}</td>
                                <td>
                                    <button class="btn btn-sm btn-success" wire:click="show_detail">Tampilkan detail
                                        invoice</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    @if ($show)
        <div class="card">
            <div class="card-header">
                Detail Accounts Receivable
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>No. Invoice</th>
                                <th>Nominal Invoice</th>
                                <th>Tanggal Jatuh Tempo</th>
                                <th>Total Pembayaran</th>
                                <th>Sisa Piutang</th>
                            </tr>
                        </thead>
                        <tbody>

                            @foreach ($items as $item)
                                @php
                                    $kalkulasi_total_piutang += $item->remaining_balance;
                                @endphp
                                <tr>
                                    <td>{{ $item->noinv }}</td>
                                    <td>{{ number_format($item->amount_total, 0, ',', '.') }}</td>
                                    <td>{{ date('d-m-Y', strtotime($item->tgl_jth_tempo)) }}</td>
                                    <td>{{ number_format($item->total_payment, 0, ',', '.') }}</td>
                                    <td>{{ number_format($item->remaining_balance, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                            <tr>
                                <td colspan="4">Total Piutang</td>
                                <td>{{ number_format($kalkulasi_total_piutang, 0, ',', '.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>
