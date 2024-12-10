<div>
    <x-alert />

    <div class="card">
        <div class="card-header">
            Detail : <b>{{ $invoiceAop }}</b>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox" wire:model.change="selectAll" />
                            </th>
                            <th>Part No</th>
                            <th>Qty</th>
                            <th>Qty Terima</th>
                            <th>Keterangan</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items_with_qty as $item)
                            <tr>
                                <td>
                                    <input type="checkbox" wire:model="selectedItems" value="{{ $item->materialNumber }}"
                                        @if (
                                            !($item->qty >= $item->qty_terima - ($item->asal_qty ? $item->asal_qty->sum('qty') : 0)) ||
                                                $item->status == 'BOSNET') disabled @endif />
                                </td>
                                <td>{{ $item->materialNumber }}</td>
                                <td>{{ $item->qty }}</td>
                                <td>{{ $item->qty_terima }}</td>
                                <td>
                                    @if (!empty($item->asal_qty))
                                        {!! implode(
                                            '<br>',
                                            $item->asal_qty->map(function ($asal) {
                                                    return "Qty: {$asal['qty']} (Invoice: {$asal['invoice']})";
                                                })->toArray(),
                                        ) !!}
                                    @else
                                        {{ $item->invoiceAop }}
                                    @endif
                                </td>
                                <td>{{ $item->status }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Floating Button -->
    <div style="position: fixed; bottom: 40px; right: 40px; z-index: 1000;">
        <button class="btn btn-warning" wire:loading.attr="disabled" wire:target="send_to_bosnet, selectedItems"
            wire:click="send_to_bosnet" @disabled(count($selectedItems) < 1) wire:confirm="Yakin ingin kirim data ke Bosnet?">
            <span wire:loading.remove wire:target="send_to_bosnet, selectedItems">Kirim ke Bosnet</span>
            <span wire:loading wire:target="send_to_bosnet, selectedItems">Loading...</span>
        </button>
    </div>
</div>
