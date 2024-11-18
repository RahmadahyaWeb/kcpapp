<div>
    @if (session('status'))
        <div class="alert alert-primary alert-dismissible fade show" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    <div class="card">
        <div class="card-header">
            <b>Data AOP Final</b>
            <hr>
        </div>
        <div class="card-body">
            <div class="row mb-3" wire:loading.class="d-none" wire:target="save, gotoPage">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Invoice AOP</label>
                    <input type="text" class="form-control" wire:model.live.debounce.1000ms="invoiceAop"
                        placeholder="Invoice AOP" wire:loading.attr="disabled">
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

            <div wire:loading.flex wire:target="save, gotoPage, invoiceAop, status"
                class="text-center justify-content-center align-items-center" style="height: 200px;">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>

            @if ($invoiceAopHeader->isEmpty())
                <div wire:loading.class="d-none" wire:target="save, gotoPage, invoiceAop, status" class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Invoice AOP</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-center" colspan="2">No Data</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            @else
                <div wire:loading.class="d-none" wire:target="save, gotoPage, invoiceAop, status" class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Invoice AOP</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($invoiceAopHeader as $invoice)
                                <tr>
                                    <td>
                                        <a href="{{ route('aop.detail', $invoice->invoiceAop) }}">
                                            {{ $invoice->invoiceAop }}
                                        </a>
                                    </td>
                                    <td>
                                        @if ($invoice->flag_selesai == 'Y' && $invoice->status == 'KCP')
                                            <span class="badge text-bg-success">Siap dikirim</span>
                                        @elseif ($invoice->flag_selesai == 'Y' && $invoice->status == 'BOSNET')
                                            <span class="badge text-bg-success">Berhasil dikirim pada
                                                {{ date('d-m-Y H:i:s', strtotime($invoice->sendToBosnet)) }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($invoice->flag_selesai == 'Y' && $invoice->status == 'KCP')
                                            <button wire:click="cancel({{ $invoice->invoiceAop }})"
                                                wire:confirm="Yakin ingin batal?" type="submit"
                                                class="btn btn-sm btn-danger">
                                                Batal
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
        <div wire:loading.class="d-none" wire:target="save, invoiceAop, status" class="card-footer">
            {{ $invoiceAopHeader->links() }}
        </div>
    </div>
</div>
