<div class="card">
    <!-- Card Header -->
    <div class="card-header">
        <div class="row align-items-center">
            <div class="col">
                <b>List Invoice</b>
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

    <div class="card-body">
        <!-- Data Table -->
        <div class="table-responsive mb-6" wire:loading.class="d-none"
            wire:target="noSo, noInv, status, synchronization, gotoPage">
            <table class="table table-hover">
                <thead class="table-dark">
                    <tr>
                        <th style="width: 18%;">No Invoice</th>
                        <th style="width: 18%;">No SO</th>
                        <th>Nominal Invoice + PPn (Rp)</th>
                        <th>Status</th>
                        <th>Sent to Bosnet</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($invoices as $invoice)
                        <tr>
                            <td>
                                {{ $invoice->noinv }}
                            </td>
                            <td>
                                {{ $invoice->noso }}
                            </td>
                            <td>{{ number_format($invoice->amount_total, 0, ',', '.') }}</td>
                            <td>
                                <span
                                    class="badge text-bg-{{ $invoice->status_bosnet == 'KCP' ? 'success' : 'warning' }}">
                                    {{ $invoice->status_bosnet }}
                                </span>
                            </td>
                            <td>
                                {{ $invoice->send_to_bosnet }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">No Data</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div wire:loading.class="d-none" wire:target="noSo, noInv, status, synchronization, gotoPage">
            {{ $invoices->links() }}
        </div>
    </div>
</div>