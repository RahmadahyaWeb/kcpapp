<div>
    <x-alert />
    <x-loading :target="$target" />

    <div class="row gap-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <b>Upload File AOP</b>
                </div>
                <div class="card-body">
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
                                <button type="submit" class="btn btn-success"
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

        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <b>Data AOP</b>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Invoice AOP</label>
                            <input type="text" class="form-control" wire:model.live.debounce.1000ms="invoiceAop"
                                placeholder="Invoice AOP">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tanggal Jatuh Tempo</label>
                            <input type="date" class="form-control" wire:model.change="tanggalJatuhTempo">
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-dark">
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
                                @forelse ($invoiceAopHeader as $invoice)
                                    <tr>
                                        <td>
                                            <span style="font-size: 0.9375rem" class="badge p-0">
                                                <a href="{{ route('aop.detail', $invoice->invoiceAop) }}">
                                                    {{ $invoice->invoiceAop }}
                                                </a>
                                            </span>
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
                                @empty
                                    <tr>
                                        <td colspan="12" class="text-center">No Data</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div wire:loading.class="d-none" wire:target="save, invoiceAop, tanggalJatuhTempo" class="card-footer">
                    {{ $invoiceAopHeader->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
