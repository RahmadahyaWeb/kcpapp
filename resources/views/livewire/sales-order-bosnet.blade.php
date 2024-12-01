<div>
    <div class="card">
        <!-- Card Header -->
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
        </div>

        <!-- Filter Section -->
        <div class="container">
            <div class="row mb-3 g-2">
                <div class="col-md-4">
                    <label class="form-label">Sales Order</label>
                    <input type="text" class="form-control" placeholder="Cari berdasarkan no sales order"
                        wire:model.live.debounce.1000ms="noSo" wire:loading.attr="disabled">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Invoice</label>
                    <input type="text" class="form-control" placeholder="Cari berdasarkan no invoice"
                        wire:model.live.debounce.1000ms="noInv" wire:loading.attr="disabled">
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
        </div>

        <!-- Loading Spinner -->
        <div wire:loading.flex wire:target="noSo, noInv, status, synchronization, gotoPage"
            class="text-center justify-content-center align-items-center" style="height: 200px;">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>

        <!-- Data Table -->
        <div class="table-responsive mb-6" wire:loading.class="d-none"
            wire:target="noSo, noInv, status, synchronization, gotoPage">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th style="width: 18%;">No SO</th>
                        <th>Kode Toko</th>
                        <th>Nama Toko</th>
                        <th>Nominal Invoice + PPn (Rp)</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($invoices as $invoice)
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
                                <span class="badge text-bg-{{ $invoice->status == 'KCP' ? 'success' : 'warning' }}">
                                    {{ $invoice->status }}
                                </span>
                            </td>
                            <td>{{ $invoice->crea_date }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">No Data</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="container">
            <div wire:loading.class="d-none" wire:target="noSo, noInv, status, synchronization, gotoPage">
                {{ $invoices->links() }}
            </div>
        </div>
    </div>
</div>
