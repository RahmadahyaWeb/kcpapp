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
                            <th>Edit Qty</th>
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
                                    <a href="" data-bs-toggle="modal"
                                        data-bs-target="#modal-edit-qty-{{ $item->part_number }}">
                                        Edit
                                    </a>
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

                            <div class="modal fade" id="modal-edit-qty-{{ $item->part_number }}"
                                data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
                                aria-labelledby="modal-edit-qtyLabel" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h1 class="modal-title fs-5" id="modal-edit-qtyLabel">Edit Qty</h1>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <form action="{{ route('comparator.edit-qty') }}" method="POST">
                                                @csrf
                                                <div class="row g-2">
                                                    <div class="col-12">
                                                        <label class="form-label">Part Number</label>
                                                        <input type="text" class="form-control"
                                                            value="{{ $item->part_number }}" disabled>
                                                        <input type="hidden" value="{{ $item->part_number }}"
                                                            name="part_number">
                                                    </div>
                                                    <div class="col-12">
                                                        <label class="form-label">Qty saat ini</label>
                                                        <input type="text" class="form-control"
                                                            value="{{ $item->qty }}" disabled>
                                                    </div>
                                                    <div class="col-12">
                                                        <label for="edited_qty" class="form-label">Qty baru</label>
                                                        <input type="text" class="form-control" name="edited_qty">
                                                    </div>
                                                    <div class="col-12 d-grid">
                                                        <button type="submit" class="btn btn-primary">Simpan</button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
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
