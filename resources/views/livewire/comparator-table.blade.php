<div>
    @if (session('success'))
        <div id="success-alert" class="alert alert-primary alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if (session('error'))
        <div id="error-alert" class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="mb-3 gap-2">
        <button type="button" class="btn btn-sm btn-danger" wire:click="resetComparator"
            wire:confirm="Yakin ingin reset?">
            Reset
        </button>
        <button type="button" class="btn btn-sm btn-success" wire:click="export">Download Excel</button>
    </div>

    <div class="card">
        <div class="card-header">
            <b>Scan</b>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <input type="text" class="form-control" wire:model="barcode" wire:keydown.enter="store"
                    placeholder="Scan barcode here" autofocus>
            </div>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Part Number</th>
                            <th>Nama Part</th>
                            <th>Qty</th>
                            <th>Scan By</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $item)
                            <tr>
                                <td>{{ $item->part_number }}</td>
                                <td>{{ $item->nm_part }}</td>
                                <td>{{ $item->qty }}</td>
                                <td>{{ $item->scan_by }}</td>
                                <td>
                                    <a class="btn btn-sm btn-danger"
                                        href="{{ route('comparator.destroy', $item->part_number) }}">
                                        Hapus
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">No Data</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
