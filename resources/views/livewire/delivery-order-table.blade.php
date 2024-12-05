<div>
    <!-- Status Alert -->
    @if (session('status'))
        <div class="alert alert-primary alert-dismissible fade show" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Error Alert -->
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card">
        <!-- Card Header with Synchronization Button -->
        <div class="card-header">
            <b>Data Delivery Order</b>
        </div>

        <!-- Card Body with Filters -->
        <div class="card-body">
            <!-- Filters Section -->
            <div class="row mb-3 g-2">
                <!-- No LKH Filter -->
                <div class="col-md-4">
                    <label class="form-label">No LKH</label>
                    <input type="text" class="form-control" wire:model.live.debounce.1000ms="noLkh"
                        placeholder="Cari berdasarkan no lkh" wire:loading.attr="disabled">
                </div>

                <!-- No SO Filter -->
                <div class="col-md-4">
                    <label class="form-label">No SO</label>
                    <input type="text" class="form-control" wire:model.live.debounce.1000ms="noSo"
                        placeholder="Cari berdasarkan no so" wire:loading.attr="disabled">
                </div>

                <!-- Status Filter -->
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select wire:model.change="status" class="form-select" wire:loading.attr="disabled">
                        <option value="">Pilih Status</option>
                        <option value="KCP">KCP</option>
                        <option value="BOSNET">BOSNET</option>
                    </select>
                </div>
            </div>

            <!-- Loading Spinner (Visible when waiting for results) -->
            <div wire:loading.flex wire:target="noLkh, synchronization, gotoPage, noSo, status"
                class="text-center justify-content-center align-items-center" style="height: 200px;">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>

            <!-- Table with Delivery Orders -->
            <div class="table-responsive" wire:loading.class="d-none"
                wire:target="noLkh, synchronization, gotoPage, noSo, status">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>No LKH</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Check if there are no items -->
                        @if ($items->isEmpty())
                            <tr>
                                <td colspan="4" class="text-center">No Data</td>
                            </tr>
                        @else
                            <!-- Loop through each item and display it -->
                            @foreach ($items as $item)
                                <tr>
                                    <td>
                                        KCP/{{ $item->area_lkh }}/{{ $item->no_lkh }}
                                    </td>
                                    <td>
                                        <a href="{{ route('do.detail', $item->no_lkh) }}">Detail</a>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Card Footer with Pagination -->
        <div class="card-footer">
            <div wire:loading.class="d-none" wire:target="noLkh, synchronization, gotoPage, noSo, status">
                <!-- Pagination links -->
                {{ $items->links() }}
            </div>
        </div>
    </div>
</div>
