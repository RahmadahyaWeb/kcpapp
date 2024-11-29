<div>
    @if (session('status'))
        <div class="alert alert-primary alert-dismissible fade show" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    <div class="row gap-3">
        {{-- FILE UPLOAD --}}
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <b>Upload File AOP</b>
                    <hr>
                </div>
                <div class="card-body">
                    <div class="alert alert-success" x-data="{ show: false }" x-show="show" x-init="@this.on('file-uploaded', () => {
                        show = true;
                        setTimeout(() => { show = false; }, 2000)
                    })"
                        style="display: none">
                        <span>{{ $notification }}</span>
                    </div>

                    <form wire:submit="save">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label for="surat_tagihan" class="form-label">
                                    Surat Tagihan
                                </label>
                                <input type="file" id="surat_tagihan"
                                    class="form-control @error('surat_tagihan') is-invalid @enderror"
                                    wire:model="surat_tagihan" wire:loading.class="is-invalid">
                                @error('surat_tagihan')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                                <div class="invalid-feedback" wire:loading wire:target="surat_tagihan">
                                    Uploading...
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="rekap_tagihan" class="form-label">
                                    Rekap Tagihan
                                </label>
                                <input type="file" id="rekap_tagihan"
                                    class="form-control @error('rekap_tagihan') is-invalid @enderror"
                                    wire:model="rekap_tagihan" wire:loading.class="is-invalid">
                                @error('rekap_tagihan')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                                <div class="invalid-feedback" wire:loading wire:target="rekap_tagihan">
                                    Uploading...
                                </div>
                            </div>
                            <div class="col-md-12 d-flex justify-content-end">
                                <button type="submit" class="btn btn-success" wire:loading.attr="disabled"
                                    wire:target="rekap_tagihan, surat_tagihan">
                                    <span wire:loading.remove wire:target="save">Upload</span>
                                    <span wire:loading wire:target="save">Uploading...</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- TABLE --}}
        <div class="col-12">
            <div class="row">
                <div class="col">
                    <div class="card">
                        <div class="card-header">
                            <div class="row align-items-center">
                                <div class="col">
                                    <b>Data AOP</b>
                                </div>
                            </div>
                            <hr>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3" wire:loading.class="d-none" wire:target="save, gotoPage">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Invoice AOP</label>
                                    <input type="text" class="form-control"
                                        wire:model.live.debounce.1000ms="invoiceAop" placeholder="Invoice AOP"
                                        wire:loading.attr="disabled">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Tanggal Jatuh Tempo</label>
                                    <input type="date" class="form-control" wire:model.change="tanggalJatuhTempo"
                                        wire:loading.attr="disabled">
                                </div>
                            </div>

                            <div wire:loading.flex wire:target="save, gotoPage, invoiceAop, tanggalJatuhTempo"
                                class="text-center justify-content-center align-items-center" style="height: 200px;">
                                <div class="spinner-border" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>

                            @if ($invoiceAopHeader->isEmpty())
                                <div wire:loading.class="d-none"
                                    wire:target="save, gotoPage, invoiceAop, tanggalJatuhTempo"
                                    class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Invoice AOP</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td class="text-center">No Data</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div wire:loading.class="d-none"
                                    wire:target="save, gotoPage, invoiceAop, tanggalJatuhTempo"
                                    class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Invoice AOP</th>
                                                <th>Customer To</th>
                                                <th>Billing Document Date</th>
                                                <th>Tgl. Jatuh Tempo</th>
                                                <th>Harga (Rp)</th>
                                                <th>Add Discount (Rp)</th>
                                                <th>Amount (Rp)</th>
                                                <th>Cash Discount (Rp)</th>
                                                <th>Extra Plafon Discount (Rp)</th>
                                                <th>Net Sales (Rp)</th>
                                                <th>Tax (Rp)</th>
                                                <th>Grand Total (Rp)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($invoiceAopHeader as $invoice)
                                                <tr>
                                                    <td>
                                                        <a
                                                            href="{{ route('aop.detail', $invoice->invoiceAop) }}">
                                                            {{ $invoice->invoiceAop }}
                                                        </a>
                                                    </td>
                                                    <td>{{ $invoice->customerTo }}</td>
                                                    <td>{{ date('d-m-Y', strtotime($invoice->billingDocumentDate)) }}
                                                    </td>
                                                    <td>{{ date('d-m-Y', strtotime($invoice->tanggalJatuhTempo)) }}</td>
                                                    <td>{{ number_format($invoice->price, 0, ',', '.') }}</td>
                                                    <td>{{ number_format($invoice->addDiscount, 0, ',', '.') }}</td>
                                                    <td>{{ number_format($invoice->amount, 0, ',', '.') }}</td>
                                                    <td>{{ number_format($invoice->cashDiscount, 0, ',', '.') }}</td>
                                                    <td>{{ number_format($invoice->extraPlafonDiscount, 0, ',', '.') }}
                                                    </td>
                                                    <td>{{ number_format($invoice->netSales, 0, ',', '.') }}</td>
                                                    <td>{{ number_format($invoice->tax, 0, ',', '.') }}</td>
                                                    <td>{{ number_format($invoice->grandTotal, 0, ',', '.') }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                        <div wire:loading.class="d-none" wire:target="save, invoiceAop, tanggalJatuhTempo"
                            class="card-footer">
                            {{ $invoiceAopHeader->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
