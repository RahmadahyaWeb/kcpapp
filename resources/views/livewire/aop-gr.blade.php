<div>
    <x-alert />
    <x-loading :target="$target" />

    <div class="row mb-3">
        <div class="col">
            <div class="card">
                <div class="card-header">
                    <b>Data Good Receipt AOP</b>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Invoice AOP</label>
                            <input type="text" class="form-control" wire:model.live.debounce.1000ms="invoiceAop"
                                placeholder="Invoice AOP">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">SPB</label>
                            <input type="text" class="form-control" wire:model.live.debounce.1000ms="spb"
                                placeholder="SPB">
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Invoice</th>
                                    <th>SPB</th>
                                    <th>Qty</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($items as $item)
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
                                @empty
                                    <tr>
                                        <td class="text-center" colspan="3">No Data</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer">
                    {{ $items->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
