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
                    <b>Data Delivery Order</b>
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
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">No LKH</label>
                    <input type="text" class="form-control" wire:model.live.debounce.1000ms="noLkh"
                        placeholder="Cari berdasarkan no lkh" wire:loading.attr="disabled">
                </div>
            </div>
            <div wire:loading.flex wire:target="noLkh, synchronization, gotoPage"
                class="text-center justify-content-center align-items-center" style="height: 200px;">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>

            <div class="table-responsive" wire:loading.class="d-none" wire:target="noLkh, synchronization, gotoPage">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>No LKH</th>
                            <th>SO / Invoice</th>
                            <th>Status</th>
                            <th>Tanggal Dibuat</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($items->isEmpty())
                            <tr>
                                <td colspan="3" class="text-center">No Data</td>
                            </tr>
                        @else
                            @foreach ($items as $item)
                                <tr>
                                    <td>
                                        <a href="{{ route('do.detail', $item->no_lkh) }}">{{ $item->no_lkh }}</a>
                                    </td>
                                    <td>
                                        @foreach ($item->invoices as $invoice)
                                            {{ $invoice }}
                                            <br>
                                        @endforeach
                                    </td>
                                    <td>
                                        @if ($item->status == 'KCP')
                                            <span class="badge text-bg-success">{{ $item->status }}</span>
                                        @else
                                            <span class="badge text-bg-warning">{{ $item->status }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ date('d-m-Y H:i:s', strtotime($item->crea_date)) }}
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            <div wire:loading.class="d-none" wire:target="noLkh, synchronization, gotoPage">
                {{ $items->links() }}
            </div>
        </div>
    </div>
</div>
