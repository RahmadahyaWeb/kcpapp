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

                    <div class="table-responsive">
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
                                        <th>Invoice</th>
                                        <th>SPB</th>
                                        <th>Qty</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($items as $item)
                                        <tr>
                                            <td>
                                                <span style="font-size: 0.9375rem" class="badge p-0">
                                                    <a href="{{ route('aop-gr.detail', $item->invoiceAop) }}">
                                                        {{ $item->invoiceAop }}
                                                    </a>
                                                </span>
                                            </td>
                                            <td>{{ $item->SPB }}</td>
                                            <td>{{ $item->qty }}</td>
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
