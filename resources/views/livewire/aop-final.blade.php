<div>
    <x-alert />
    <x-loading :target="$target" />

    <div class="card">
        <div class="card-header">
            <b>Data AOP Final</b>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Invoice AOP</label>
                    <input type="text" class="form-control" wire:model.live.debounce.1000ms="invoiceAop"
                        placeholder="Invoice AOP">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Status</label>
                    <select wire:model.change="status" class="form-select">
                        <option value="" selected>Pilih Status</option>
                        <option value="KCP">KCP</option>
                        <option value="BOSNET">BOSNET</option>
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Invoice AOP</th>
                            <th>Keterangan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($invoiceAopHeader as $invoice)
                            <tr>
                                <td>
                                    <span style="font-size: 0.9375rem" class="badge p-0">
                                        <a href="{{ route('aop.final.detail', $invoice->invoiceAop) }}">
                                            {{ $invoice->invoiceAop }}
                                        </a>
                                    </span>
                                </td>
                                <td>
                                    @if ($invoice->flag_po == 'N')
                                        FINAL STAGE
                                    @elseif ($invoice->flag_po == 'Y')
                                        <span class="badge text-bg-warning">BOSNET:
                                            {{ date('d-m-Y H:i:s', strtotime($invoice->po_date)) }}
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if ($invoice->flag_po == 'N')
                                        <button wire:click="cancel({{ $invoice->invoiceAop }})"
                                            wire:confirm="Yakin ingin batal?" type="submit"
                                            class="btn btn-sm btn-danger">
                                            Batal
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center">No Data</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div wire:loading.class="d-none" wire:target="save, invoiceAop, status" class="card-footer">
            {{ $invoiceAopHeader->links() }}
        </div>
    </div>
</div>
