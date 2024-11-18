<div>
    @if (session('status'))
        <div class="alert alert-primary alert-dismissible fade show" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    <div class="card">
        <div class="card-header">
            Detail Good Receipt: <b>{{ $spb }}</b>
            <hr>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select wire:model.change="statusItem" class="form-select">
                        <option value="">Pilih Status</option>
                        <option value="KCP">KCP</option>
                        <option value="BOSNET">BOSNET</option>
                    </select>
                </div>
            </div>

            <div wire:loading.flex wire:target="statusItem"
                class="text-center justify-content-center align-items-center" style="height: 200px;">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>

            <div class="table-responsive" wire:loading.class = "d-none" wire:target="statusItem">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>
                                {{-- <input type="checkbox" wire:model="selectAll" wire:click="toggleSelectAll"> --}}
                            </th>
                            <th>Part No</th>
                            <th>Total Qty</th>
                            <th>Total Qty Terima</th>
                            <th>Data From</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($finalResult as $item)
                            <tr>
                                <td>
                                    <input type="checkbox" wire:model.change="selectedItems"
                                        value="{{ $item['materialNumber'] }}"
                                        @if ($item['total_qty'] != $item['qty_terima'] || ($item['statusHeader'] == 'KCP' || $item['statusItem'] != 'KCP')) disabled @endif>
                                </td>
                                <td>{{ $item['materialNumber'] }}</td>
                                <td>{{ $item['total_qty'] }}</td>
                                <td>{{ isset($item['qty_terima']) ? $item['qty_terima'] : 0 }}</td>
                                <td>
                                    @foreach ($item['invoices'] as $invoice => $qty)
                                        <div>
                                            <span>{{ $invoice }}: {{ $qty }}</span>
                                        </div>
                                    @endforeach
                                </td>
                                <td>
                                    @if ($item['statusItem'] == 'BOSNET')
                                        <span class="badge text-bg-success">Berhasil dikirim</span>
                                    @elseif ($item['total_qty'] == $item['qty_terima'])
                                        <span class="badge text-bg-success">Lengkap</span>
                                    @else
                                        <span class="badge text-bg-danger">Belum Lengkap</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Floating Button -->
    <div style="position: fixed; bottom: 40px; right: 40px; z-index: 1000;">
        <button class="btn btn-warning" wire:loading.attr="disabled" wire:target="sendToBosnet, selectedItems"
            wire:click="sendToBosnet" @disabled(count($selectedItems) < 1) wire:confirm="Yakin ingin kirim data ke Bosnet?">
            <span wire:loading.remove wire:target="sendToBosnet, selectedItems">Kirim ke Bosnet</span>
            <span wire:loading wire:target="sendToBosnet, selectedItems">Loading...</span>
        </button>
    </div>
</div>
