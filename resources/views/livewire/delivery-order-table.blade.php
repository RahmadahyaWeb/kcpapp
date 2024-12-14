<div>
    <x-alert />
    <x-loading :target="$target" />

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
                    <input type="text" class="form-control" wire:model.live.debounce.1000ms="no_lkh"
                        placeholder="Cari berdasarkan no lkh" wire:loading.attr="disabled">
                </div>
            </div>

            <!-- Table with Delivery Orders -->
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>No LKH</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Check if there are no items -->
                        @if ($items->isEmpty())
                            <tr>
                                <td colspan="2" class="text-center">No Data</td>
                            </tr>
                        @else
                            <!-- Loop through each item and display it -->
                            @foreach ($items as $item)
                                <tr>
                                    <td>
                                        KCP/{{ $item->area_lkh }}/{{ $item->no_lkh }}
                                    </td>
                                    <td>
                                        <a href="{{ route('do.detail', $item->no_lkh) }}"
                                            class="btn btn-sm btn-primary">Detail</a>
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
            {{ $items->links() }}
        </div>
    </div>
</div>
