<div>
    <div class="card">
        <div class="card-header">
            <b>History Invoice</b>
        </div>
        <div class="card-body">
            <div class="row mb-3 g-2">
                <div class="col-md-4">
                    <label class="form-label">No Invoice</label>
                    <input type="text" class="form-control" wire:model.live.debounce.1000ms="noinv">
                </div>
            </div>

            <!-- Loading Spinner (Visible when waiting for results) -->
            <div wire:loading.flex wire:target="noinv, gotoPage"
                class="text-center justify-content-center align-items-center" style="height: 200px;">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>

            <div class="table-responsive mb-6" wire:loading.class="d-none" wire:target="noinv, gotoPage">
                <table class="table table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>No Invoice</th>
                            <th>Kode Toko</th>
                            <th>Nama Toko</th>
                            <th>Nominal Invoice</th>
                            <th>Nama Sales</th>
                            <th>Tgl. Jatuh Tempo</th>
                            <th>Tgl. Buat</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $item)
                            <tr>
                                <td>
                                    <a href="{{ route('inv.history-detail', $item->noinv) }}">
                                        KCP/{{ $item->area_inv }}/{{ $item->noinv }}
                                    </a>
                                </td>
                                <td>{{ $item->kd_outlet }}</td>
                                <td>{{ $item->nm_outlet }}</td>
                                <td class="table-warning">{{ number_format($item->amount_total, 0, ',', '.') }}</td>
                                <td>{{ $item->user_sales }}</td>
                                <td>{{ date('d-m-Y', strtotime($item->tgl_jth_tempo)) }}</td>
                                <td>{{ date('d-m-Y', strtotime($item->crea_date)) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">No Data</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div wire:loading.class="d-none" wire:target="noinv, gotoPage">
                {{ $items->links() }}
            </div>
        </div>
    </div>
</div>
