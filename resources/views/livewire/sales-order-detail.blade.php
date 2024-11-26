<div>
    <div class="row gap-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col">
                            Detail Invoice: <b>{{ $header->noinv }}</b>
                        </div>
                        <div class="col d-flex justify-content-end">
                            <a href="{{ route('so.print', $header->noinv) }}"
                                class="btn btn-success {{ $header->flag_print == 'Y' ? 'disabled' : '' }}">
                                <i class='bx bxs-printer me-1'></i> Print
                            </a>
                        </div>
                    </div>
                    <hr>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="row mb-3">
                                <div class="col col-4 col-md-4">
                                    <div>No Invoice</div>
                                </div>
                                <div class="col col-auto">
                                    :
                                </div>
                                <div class="col col-auto">
                                    <div>{{ $header->noinv }}</div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col col-4 col-md-4">
                                    <div>No Sales Order</div>
                                </div>
                                <div class="col col-auto">
                                    :
                                </div>
                                <div class="col col-auto">
                                    <div>{{ $header->noso }}</div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col col-4 col-md-4">
                                    <div>Toko</div>
                                </div>
                                <div class="col col-auto">
                                    :
                                </div>
                                <div class="col col-auto">
                                    <div>{{ $header->nm_outlet }}</div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col col-4 col-md-4">
                                    <div>Amount</div>
                                </div>
                                <div class="col col-auto">
                                    :
                                </div>
                                <div class="col col-auto">
                                    <div>Rp {{ number_format($header->amount, 0, ',', '.') }}</div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col col-4 col-md-4">
                                    <div>Amount Discount</div>
                                </div>
                                <div class="col col-auto">
                                    :
                                </div>
                                <div class="col col-auto">
                                    <div>Rp {{ number_format($header->amount_disc, 0, ',', '.') }}</div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col col-4 col-md-4">
                                    <div>Support Program</div>
                                </div>
                                <div class="col col-auto">
                                    :
                                </div>
                                <div class="col col-auto">
                                    <div>Rp {{ number_format($nominalSuppProgram, 0, ',', '.') }}</div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col col-4 col-md-4">
                                    <div>Amount Total</div>
                                </div>
                                <div class="col col-auto">
                                    :
                                </div>
                                <div class="col col-auto">
                                    <div>Rp {{ number_format($header->amount_total, 0, ',', '.') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @if ($header->status == 'KCP')
                        <div class="row">
                            <form wire:submit="sendToBosnet" wire:confirm="Yakin ingin kirim data ke Bosnet?">
                                <div class="col d-grid">
                                    <hr>
                                    <button type="submit" class="btn btn-warning">
                                        <span wire:loading.remove wire:target="sendToBosnet">Kirim ke Bosnet</span>
                                        <span wire:loading wire:target="sendToBosnet">Loading...</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        @if ($header->status == 'KCP')
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        Form Tambah Support Program
                        <hr>
                    </div>
                    <div class="card-body">
                        @if (session('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                {{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"
                                    aria-label="Close"></button>
                            </div>
                        @endif
                        @if (session('error'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                {{ session('error') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"
                                    aria-label="Close"></button>
                            </div>
                        @endif
                        <form wire:submit="saveProgram">
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Nama Program</label>
                                    <input type="text"
                                        class="form-control @error('search_program') is-invalid @enderror"
                                        wire:model.live="search_program" placeholder="Cari nama program">
                                </div>
                                <div class="col-md-12 mb-3">
                                    <select wire:model.live="nama_program"
                                        class="form-select @error('nama_program') is-invalid @enderror"
                                        wire:loading.attr="disabled" wire:target="nama_program, search_program">
                                        <option value="">Pilih Program</option>
                                        @foreach ($bonus as $item)
                                            <option value="{{ $item->no_program }}">{{ $item->nm_program }}</option>
                                        @endforeach
                                    </select>
                                    @error('nama_program')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Max Nominal Program</label>
                                    <input type="number" class="form-control" wire:model.live="nominal_program_display"
                                        disabled>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Nominal Program</label>
                                    <input type="number"
                                        class="form-control @error('nominal_program') is-invalid @enderror"
                                        wire:model.live="nominal_program">
                                    @error('nominal_program')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                                <div class="col-md-12 d-flex justify-content-end mt-3">
                                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled"
                                        wire:target="saveProgram,nama_program,search_program,nominal_program">
                                        <span wire:loading.remove wire:target="saveProgram">Tambah Program</span>
                                        <span wire:loading wire:target="saveProgram">Loading...</span>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    Support Program
                    <hr>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Nama Program</th>
                                    <th>Nominal Program (Rp)</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $supportProgram = 0;
                                @endphp
                                @if ($programs->isEmpty())
                                    <tr>
                                        <td colspan="3" class="text-center">No Data</td>
                                    </tr>
                                @else
                                    @foreach ($programs as $program)
                                        @php
                                            $supportProgram += $program->nominal_program;
                                        @endphp
                                        <tr>
                                            <td>{{ $program->nama_program }}</td>
                                            <td>{{ number_format($program->nominal_program, 0, ',', '.') }}</td>
                                            <td>
                                                @if ($header->status == 'KCP')
                                                    <button wire:click="deleteProgram({{ $program->id }})"
                                                        class="btn btn-sm btn-danger">
                                                        Hapus
                                                    </button>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    Detail Material: <b>{{ $header->noinv }}</b>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>No Part</th>
                                    <th>Nama Part</th>
                                    <th>Qty</th>
                                    <th>Harga / Pcs (Rp)</th>
                                    <th>Disc (%)</th>
                                    <th>Nominal (Rp)</th>
                                    <th>Nominal Discount (Rp)</th>
                                    <th>Nominal Total (Rp)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $total = 0;
                                @endphp
                                @if ($invoices->isEmpty())
                                    <tr>
                                        <td colspan="8" class="text-center">No Data</td>
                                    </tr>
                                @else
                                    @foreach ($invoices as $invoice)
                                        @php
                                            $total += $invoice['nominal_total'];
                                        @endphp
                                        <tr>
                                            <td>{{ $invoice['part_no'] }}</td>
                                            <td>{{ $invoice['nm_part'] }}</td>
                                            <td>{{ $invoice['qty'] }}</td>
                                            <td>{{ number_format($invoice['hrg_pcs'], 0, ',', '.') }}</td>
                                            <td>{{ $invoice['disc'] }}</td>
                                            <td>{{ number_format($invoice['nominal'], 0, ',', '.') }}</td>
                                            <td>{{ number_format($invoice['nominal_disc'], 0, ',', '.') }}</td>
                                            <td>{{ number_format($invoice['nominal_total'], 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                    <tr>
                                        <td colspan="7" class="fw-bold">Total</td>
                                        <td>{{ number_format($total, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="7" class="fw-bold">Support Program</td>
                                        <td>{{ number_format($supportProgram, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="7" class="fw-bold">Grand Total</td>
                                        <td>{{ number_format($total - $supportProgram, 0, ',', '.') }}</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
