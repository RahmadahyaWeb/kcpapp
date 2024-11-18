<div>
    <div class="row">
        {{-- CARD DETAIL --}}
        <div class="col-12 mb-3">
            <div class="card">
                <div class="card-header">
                    Detail Invoice Astra Otoparts (AOP): <b>{{ $header->invoiceAop }}</b>
                    <hr>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="row mb-3">
                                <div class="col col-4 col-md-4">
                                    <div>Invoice AOP</div>
                                </div>
                                <div class="col col-auto">
                                    :
                                </div>
                                <div class="col col-auto">
                                    <div>{{ $header->invoiceAop }}</div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col col-4 col-md-4">
                                    <div>Customer To</div>
                                </div>
                                <div class="col col-auto">
                                    :
                                </div>
                                <div class="col col-auto">
                                    <div>{{ $header->customerTo }}</div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col col-4 col-md-4">
                                    <div>No. SPB</div>
                                </div>
                                <div class="col col-auto">
                                    :
                                </div>
                                <div class="col col-auto">
                                    <div>{{ $header->SPB }}</div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col col-4 col-md-4">
                                    <div>Billing Document Date</div>
                                </div>
                                <div class="col col-auto">
                                    :
                                </div>
                                <div class="col col-auto">
                                    <div>
                                        @if ($header->billingDocumentDate != null)
                                            {{ date('d-m-Y', strtotime($header->billingDocumentDate)) }}
                                        @else
                                            -
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col col-4 col-md-4">
                                    <div>Tgl. Cetak Faktur</div>
                                </div>
                                <div class="col col-auto">
                                    :
                                </div>
                                <div class="col col-auto">
                                    <div>
                                        @if ($header->tanggalCetakFaktur != null)
                                            {{ date('d-m-Y', strtotime($header->tanggalCetakFaktur)) }}
                                        @else
                                            -
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col col-4 col-md-4">
                                    <div>Tgl. Jatuh Tempo</div>
                                </div>
                                <div class="col col-auto">
                                    :
                                </div>
                                <div class="col col-auto">
                                    <div>
                                        @if ($header->tanggalJatuhTempo != null)
                                            {{ date('d-m-Y', strtotime($header->tanggalJatuhTempo)) }}
                                        @else
                                            -
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col col-4 col-md-4">
                                    <div>Faktur Pajak</div>
                                </div>
                                <div class="col col-auto">
                                    :
                                </div>
                                <div class="col col-auto">
                                    <div>
                                        @if ($header->fakturPajak != null && $header->flag_selesai == 'N')
                                            <div class="d-inline text-primary" style="cursor: pointer"
                                                data-bs-toggle="modal" data-bs-target="#editFakturPajakModal"
                                                wire:click="openModalFakturPajak">
                                                {{ $header->fakturPajak }}
                                            </div>
                                        @elseif ($header->flag_selesai == 'N')
                                            <div class="d-inline text-primary" style="cursor: pointer"
                                                data-bs-toggle="modal" data-bs-target="#editFakturPajakModal"
                                                wire:click="openModalFakturPajak">
                                                Belum ada
                                            </div>
                                        @else
                                            <div class="d-inline text-secondary" style="cursor: not-allowed">
                                                {{ $header->fakturPajak ?? 'Belum ada' }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="row mb-3">
                                <div class="col col-4 col-md-4">
                                    <div>Harga</div>
                                </div>
                                <div class="col col-auto">
                                    :
                                </div>
                                <div class="col col-auto">
                                    <div>Rp {{ number_format($header->price, 0, ',', '.') }}</div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col col-4 col-md-4">
                                    <div>Additional Discount</div>
                                </div>
                                <div class="col col-auto">
                                    :
                                </div>
                                <div class="col col-auto">
                                    <div>Rp {{ number_format($header->addDiscount, 0, ',', '.') }}</div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col col-4 col-md-4">
                                    <div>Extra Plafon Discount</div>
                                </div>
                                <div class="col col-auto">
                                    :
                                </div>
                                <div class="col col-auto">
                                    <div>Rp {{ number_format($header->extraPlafonDiscount, 0, ',', '.') }}</div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col col-4 col-md-4">
                                    <div>Cash Discount</div>
                                </div>
                                <div class="col col-auto">
                                    :
                                </div>
                                <div class="col col-auto">
                                    <div>Rp {{ number_format($header->cashDiscount, 0, ',', '.') }}</div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col col-4 col-md-4">
                                    <div>Net Sales</div>
                                </div>
                                <div class="col col-auto">
                                    :
                                </div>
                                <div class="col col-auto">
                                    <div>Rp {{ number_format($header->netSales, 0, ',', '.') }}</div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col col-4 col-md-4">
                                    <div>Tax</div>
                                </div>
                                <div class="col col-auto">
                                    :
                                </div>
                                <div class="col col-auto">
                                    <div>Rp {{ number_format($header->tax, 0, ',', '.') }}</div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col col-4 col-md-4">
                                    <div>Grand Total</div>
                                </div>
                                <div class="col col-auto">
                                    :
                                </div>
                                <div class="col col-auto">
                                    <div>Rp {{ number_format($header->grandTotal, 0, ',', '.') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @if ($header->fakturPajak && $header->flag_selesai == 'Y' && $header->status == 'KCP')
                        <div class="row">
                            <form wire:submit="sendToBosnet({{ $header->invoiceAop }})"
                                wire:confirm="Yakin ingin kirim data ke Bosnet?">
                                <div class="col d-grid">
                                    <hr>
                                    <button type="submit" class="btn btn-warning">
                                        <span wire:loading.remove wire:target="sendToBosnet">Kirim ke Bosnet</span>
                                        <span wire:loading wire:target="sendToBosnet">Loading...</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    @elseif($header->fakturPajak && $header->flag_selesai == 'N' && $header->status == 'KCP')
                        <div class="row">
                            <form wire:submit="updateFlag({{ $header->invoiceAop }})"
                                wire:confirm="Yakin ingin update flag?">
                                <div class="col d-grid">
                                    <hr>
                                    <button type="submit" class="btn btn-success">
                                        <span wire:loading.remove wire:target="updateFlag">Selesai</span>
                                        <span wire:loading wire:target="updateFlag">Loading...</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- CARD EXTRA PLAFON DISCOUNT  --}}
        @if ($header->flag_selesai == 'N')
            <div class="col-12 mb-3">
                <div class="card">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col">
                                Extra Plafon Discount (Disc Program)
                            </div>
                            <div class="col d-flex justify-content-end">
                                <button data-bs-toggle="modal" data-bs-target="#createProgramModal"
                                    class="btn btn-primary" >
                                    Tambah Program
                                </button>
                            </div>
                        </div>
                        <hr>
                    </div>
                    <div class="card-body">
                        @if ($programAop->isEmpty())
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>Discount (Rp)</th>
                                        <th>Keterangan</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="text-center" colspan="3">No Data</td>
                                    </tr>
                                </tbody>
                            </table>
                        @else
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>Discount (Rp)</th>
                                        <th>Keterangan</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($programAop as $item)
                                        <tr>
                                            <td>{{ number_format($item->potonganProgram, 0, ',', '.') }}</td>
                                            <td>{{ $item->keteranganProgram }}</td>
                                            <td>
                                                <button class="btn btn-danger btn-sm"
                                                    wire:click="destroyProgram({{ $item->id }})">
                                                    Hapus
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        {{-- CARD DETAIL PART --}}
        <div class="col-12 mb-3">
            <div class="card">
                <div class="card-header">
                    Detail Material Astra Otoparts (AOP): <b>{{ $header->invoiceAop }}</b>
                    <hr>
                </div>
                <div class="card-body">
                    <table class="table table-bordered mb-3">
                        <tr>
                            <th>Total Qty</th>
                            <td><b>{{ $totalQty }}</b></td>
                        </tr>
                        <tr>
                            <th>
                                Total Amount
                            </th>
                            <td>
                                <b>Rp {{ number_format($totalAmount, 0, ',', '.') }}</b>
                            </td>
                        </tr>
                    </table>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>Material Number</th>
                                    <th>Qty</th>
                                    <th>Amount (Rp)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($details as $item)
                                    <tr>
                                        <td>{{ $item->materialNumber }}</td>
                                        <td>{{ $item->qty }}</td>
                                        <td>{{ number_format($item->amount, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- MODAL EDIT FAKTUR PAJAK --}}
        <div class="modal fade {{ $classFakturPajak }}" id="editFakturPajakModal" tabindex="-1"
            aria-labelledby="editFakturPajakModalLabel" style="{{ $styleFakturPajak }}" data-bs-backdrop="static"
            data-bs-keyboard="false">
            <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="editFakturPajakModalLabel">
                            Edit Faktur Pajak
                        </h1>
                    </div>
                    <div class="modal-body">
                        <form wire:submit="saveFakturPajak">
                            <label for="fakturPajak" class="form-label">Faktur Pajak</label>
                            <input type="text" class="form-control" wire:model="fakturPajak">
                            <div class="d-flex justify-content-end mt-2 gap-2">
                                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-primary">Simpan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- MODAL Tambah Program --}}
        <div class="modal fade {{ $classProgram }}" id="createProgramModal" tabindex="-1"
            aria-labelledby="createProgramModalLabel" style="{{ $styleProgram }}" data-bs-backdrop="static"
            data-bs-keyboard="false">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="createProgramModalLabel">Tambah Extra Plafon Discount</h1>
                    </div>
                    <div class="modal-body">
                        <form wire:submit="saveProgram">
                            <div class="row gap-3">
                                <div class="col-12">
                                    <label for="potonganProgram" class="form-label">Potongan Harga</label>
                                    <input type="number"
                                        class="form-control @error('potonganProgram') is-invalid @enderror"
                                        wire:model="potonganProgram">
                                    @error('potonganProgram')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                                <div class="col-12">
                                    <label for="keteranganProgram" class="form-label">Keterangan Program</label>
                                    <input type="text"
                                        class="form-control @error('keteranganProgram') is-invalid @enderror"
                                        wire:model="keteranganProgram">
                                    @error('keteranganProgram')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                                <div class="col-12 d-flex justify-content-end mt-2 gap-2">
                                    <button type="button" class="btn btn-danger" wire:click="closeModalProgram"
                                        data-bs-dismiss="modal">Batal</button>
                                    <button type="submit" class="btn btn-primary">Simpan</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
