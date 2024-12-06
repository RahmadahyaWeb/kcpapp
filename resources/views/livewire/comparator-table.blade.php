<div>
    @include('livewire.comparator-modal')
    @include('livewire.comparator-modal-keterangan')

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

            <!-- Loading Spinner (Visible when waiting for results) -->
            <div wire:loading.flex wire:target.except="updateQty, updateKeterangan"
                class="text-center justify-content-center align-items-center"
                style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
            background-color: rgba(0, 0, 0, 0.5); z-index: 9999;">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Part Number</th>
                            <th>Nama Part</th>
                            <th>Qty</th>
                            <th>Edit Qty</th>
                            <th>Scan By</th>
                            <th>Keterangan</th>
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
                                    <button wire:click="edit('{{ $item->id }}')"
                                        class="btn btn-sm btn-warning">Edit</button>
                                </td>
                                <td>{{ $item->scan_by }}</td>
                                <td>{{ $item->keterangan }}</td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <button wire:click="keterangan('{{ $item->id }}')" class="btn btn-sm btn-primary">
                                            Keterangan
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
            $('#modal-edit-qty').modal('hide');
        });

        Livewire.on('open-modal-qty', () => {
            $('#modal-edit-qty').modal('show');
        });

        Livewire.on('open-modal-keterangan', () => {
            $('#modal-edit-keterangan').modal('show');
        });

        Livewire.on('keterangan-saved', () => {
            document.getElementById('scan-barcode').focus();
            $('#modal-edit-keterangan').modal('hide');
        });
    </script>
@endpush
