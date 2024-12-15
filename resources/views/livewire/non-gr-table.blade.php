<div>
    <x-alert />
    <x-loading :target="$target" />

    <div class="row mb-3">
        <div class="col">
            <div class="card">
                <div class="card-header">
                    <b>Data Goods Receipt</b>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Invoice</label>
                            <input type="text" class="form-control" wire:model.live.debounce.1000ms="invoiceNon"
                                placeholder="Invoice">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Keterangan</label>
                            <select class="form-select" wire:model.change='keterangan'>
                                <option value="SELESAI">SELESAI</option>
                                <option value="BELUM SELESAI">BELUM SELESAI</option>
                            </select>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Invoice</th>
                                    <th>Total Items</th>
                                    <th>Total Items Terkirim</th>
                                    <th>Qty</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($items as $item)
                                    <tr>
                                        <td>
                                            <span style="font-size: 0.9375rem" class="badge p-0">
                                                <a href="{{ route('non-gr.detail', $item->invoiceNon) }}">
                                                    {{ $item->invoiceNon }}
                                                </a>
                                            </span>
                                        </td>
                                        <td>{{ $item->total_items }}</td>
                                        <td>{{ $item->total_items_terkirim }}</td>
                                        <td>{{ $item->qty }}</td>
                                        <td>{{ $item->keterangan }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-center" colspan="6">No Data</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer">
                    {{ $items->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
