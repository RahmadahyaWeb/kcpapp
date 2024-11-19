<div>
    <div class="row mb-3">
        <div class="col">
            <div class="card">
                <div class="card-header">
                    <b>Data Good Receipt AOP</b>
                    <hr>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Invoice AOP</label>
                            <input type="text" class="form-control" wire:model.live.debounce.1000ms="invoiceAop"
                                placeholder="Invoice AOP" wire:loading.attr="disabled">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">SPB</label>
                            <input type="text" class="form-control" wire:model.live.debounce.1000ms="spb"
                                placeholder="SPB" wire:loading.attr="disabled">
                        </div>
                    </div>

                    <div wire:loading.flex wire:target="invoiceAop, spb"
                        class="text-center justify-content-center align-items-center" style="height: 200px;">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>

                    <div class="table-responsive" wire:loading.class="d-none" wire:target="invoiceAop, spb">
                        @if (empty($items))
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>SPB</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="1" class="text-center">No Data</td>
                                    </tr>
                                </tbody>
                            </table>
                        @else
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>SPB</th>
                                        <th>Invoice</th>
                                        <th>Qty Invoice</th>
                                        <th>Qty Terima</th>
                                        <th>Status Qty</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($items as $item)
                                        <tr>
                                            <td>{{ $item['spb'] }}</td>
                                            <td>
                                                {!! implode('<br>', $item['invoices']) !!}
                                            </td>
                                            <td>{{ $item['totalQty'] }}</td>
                                            <td>{{ $item['totalQtyTerima'] }}</td>
                                            <td>
                                                @if ($item['totalQty'] == $item['totalQtyTerima'])
                                                    <span class="badge text-bg-success">Lengkap</span>
                                                @else
                                                    <span class="badge text-bg-danger">Belum Lengkap</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('aop-gr.detail', $item['spb']) }}"
                                                    class="btn btn-sm btn-primary">Detail</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
