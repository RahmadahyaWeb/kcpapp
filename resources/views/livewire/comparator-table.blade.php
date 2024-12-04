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
        @php
            $userRoles = explode(',', Auth::user()->role);

            $allowedRoles = ['ADMIN', 'KEPALA-GUDANG', 'INVENTORY'];
        @endphp

        @if (!empty(array_intersect($allowedRoles, $userRoles)))
            <button type="button" class="btn btn-sm btn-danger" wire:click="resetComparator"
                wire:confirm="Yakin ingin reset?">
                Reset
            </button>
            <button type="button" class="btn btn-sm btn-success" wire:click="export">Download Excel</button>
        @endif
    </div>

    <div class="card">
        <div class="card-header">
            <b>Scan</b>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <input type="text" id="scan-barcode" class="form-control" wire:model="barcode"
                    wire:keydown.enter="store" placeholder="Scan barcode here" wire:loading.attr="disabled" autofocus>
            </div>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Part Number</th>
                            <th>Nama Part</th>
                            <th>Qty</th>
                            <th style="width: 20%">Qty</th>
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
                                <td>
                                    <input type="number" class="form-control form-control-sm"
                                        wire:model="items.{{ $loop->index }}.qty"
                                        wire:keydown.enter="updateQty($event.target.value, '{{ $item->part_number }}')">
                                </td>
                                <td>{{ $item->scan_by }}</td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-sm btn-success"
                                            wire:click="increment('{{ $item->part_number }}')">
                                            +
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger"
                                            wire:click="decrement('{{ $item->part_number }}')">
                                            -
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger"
                                            wire:click="destroy('{{ $item->part_number }}')">
                                            Hapus
                                        </button>
                                    </div>
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
        </div>
    </div>
</div>

@push('scripts')
    @livewireScripts()
    <script>
        Livewire.on('qty-saved', () => {
            document.getElementById('scan-barcode').focus();
        });
    </script>
@endpush
