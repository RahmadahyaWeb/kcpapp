<div>
    @if (session('status'))
        <div class="alert alert-primary alert-dismissible fade show" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    <div class="card">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col">
                    <b>Data Sales Order</b>
                </div>
                <div class="col d-flex justify-content-end">
                    <button wire:click="synchronization" class="btn btn-success" wire:target="synchronization"
                        wire:loading.attr="disabled">
                        <i class='bx bx-sync me-1'></i> Sinkron
                    </button>
                </div>
            </div>
            <hr>
        </div>
        <div class="card-body">
            <div class="row mb-3 g-2">
                <div class="col-md-4">
                    <label class="form-label">Sales Order</label>
                    <input type="text" class="form-control" wire:model.live.debounce.1000ms="noSo"
                        placeholder="Cari berdasarkan no sales order" wire:loading.attr="disabled">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Invoice</label>
                    <input type="text" class="form-control" wire:model.live.debounce.1000ms="noInv"
                        placeholder="Cari berdasarkan no invoice" wire:loading.attr="disabled">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select wire:model.change="status" class="form-select" wire:loading.attr="disabled">
                        <option value="">Pilih Status</option>
                        <option value="KCP">KCP</option>
                        <option value="BOSNET">BOSNET</option>
                    </select>
                </div>
            </div>

            <div wire:loading.flex wire:target="noSo, noInv, status, synchronization, gotoPage"
                class="text-center justify-content-center align-items-center" style="height: 200px;">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>

            <div class="table-responsive" wire:loading.class="d-none"
                wire:target="noSo, noInv, status, synchronization, gotoPage">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th style="width: 18%;">No SO</th>
                            <th style="">Kode Toko</th>
                            <th style="">Nama Toko</th>
                            <th style="">Nominal Invoice + PPn (Rp)</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($invoices->isEmpty())
                            <tr>
                                <td colspan="5" class="text-center">No Data</td>
                            </tr>
                        @else
                            @foreach ($invoices as $invoice)
                                <tr>
                                    <td>
                                        <a href="{{ route('so.detail', $invoice->noinv) }}">
                                            {{ $invoice->noso }}
                                        </a>
                                    </td>
                                    <td>{{ $invoice->kd_outlet }}</td>
                                    <td>{{ $invoice->nm_outlet }}</td>
                                    <td>{{ number_format($invoice->amount_total, 0, ',', '.') }}</td>
                                    <td>
                                        @if ($invoice->status == 'KCP')
                                            <span class="badge text-bg-success">{{ $invoice->status }}</span>
                                        @else
                                            <span class="badge text-bg-warning">{{ $invoice->status }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $invoice->crea_date }}</td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            <div wire:loading.class="d-none" wire:target="noSo, noInv, status, synchronization">
                {{ $invoices->links() }}
            </div>
        </div>
    </div>
</div>
