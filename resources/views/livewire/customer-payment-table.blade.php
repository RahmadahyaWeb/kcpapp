<div>
    <x-alert />
    <x-loading :target="$target" />

    <div class="card">
        <div class="card-header">
            <b>Data Customer Payment</b>
        </div>
        <div class="card-body">
            <div class="row mb-3 g-2">
                <div class="col-md-4">
                    <label class="form-label">No Piutang</label>
                    <input type="text" class="form-control" wire:model.live.debounce.1000ms="no_piutang"
                        placeholder="Cari berdasarkan no piutang">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status Customer Payment</label>
                    <select class="form-select" wire:model.change="status_customer_payment">
                        <option value="O">OPEN</option>
                        <option value="C">CLOSE</option>
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>No Piutang</th>
                            <th>Kode Toko</th>
                            <th>Nama Toko</th>
                            <th>Nominal Potong (RP)</th>
                            <th>Pembayaran Via</th>
                            <th>Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($customer_payment_header as $item)
                            <tr>
                                <td>
                                    <span style="font-size: 0.9375rem" class="badge p-0">
                                        <a href="{{ route('customer-payment.detail', $item->no_piutang) }}">{{ $item->no_piutang }}
                                        </a>
                                    </span>
                                </td>
                                <td>{{ $item->kd_outlet }}</td>
                                <td>{{ $item->nm_outlet }}</td>
                                <td>{{ number_format($item->nominal_potong, 0, ',', '.') }}</td>
                                <td>{{ $item->pembayaran_via }}</td>
                                <td>{{ $item->crea_date }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">No Data</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            {{ $customer_payment_header->links() }}
        </div>
    </div>
</div>
