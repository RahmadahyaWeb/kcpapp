<div>
    <div class="mb-3">
        <a href="{{ route('so-bosnet.index') }}" class="btn btn-warning">SO to Bosnet</a>
    </div>

    <div class="card">
        <!-- Card Header -->
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col">
                    <b>Sales Order</b>
                </div>
                <div class="col d-flex justify-content-end">
                    <a href="" class="btn btn-success">
                        Buat SO Baru
                    </a>
                </div>
            </div>
        </div>

        <div class="card-body">
            <div class="alert alert-warning" role="alert">
                <h3 class="m-0 text-danger fw-bold text-center">SALES ORDER MAKS. 3 HARI, MOHON DIJADIKAN BACK ORDER.</h3>
            </div>

            <!-- Loading Spinner -->
            <div wire:loading.flex wire:target="gotoPage" class="text-center justify-content-center align-items-center"
                style="height: 200px;">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>

            <div class="table-responsive mb-6" wire:loading.class="d-none" wire:target="gotoPage">
                <table class="table table-hover table-sm">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 50px">Tgl. SO</th>
                            <th style="width: 175px">No Sales Order (SO)</th>
                            <th>Back Order</th>
                            <th>Kode Toko</th>
                            <th>Nama Toko</th>
                            <th style="width: 80px">Nominal SP</th>
                            <th style="width: 80px">Nominal Plafond</th>
                            <th>Nama Sales</th>
                            <th>Approve SPV</th>
                            <th>Mutasi</th>
                            <th style="width: 150px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $item)
                            <tr>
                                <td class="text-center">{{ date('d-m-Y H:i:s', strtotime($item->crea_date)) }}</td>
                                <td>KCP/{{ $item->area_so }}/{{ $item->noso }}</td>
                                <td>-</td>
                                <td>{{ $item->kd_outlet }}</td>
                                <td>{{ $item->nm_outlet }}</td>
                                <td class="table-warning">{{ number_format($item->nominal_total, 0, ',', '.') }}</td>
                                <td class="table-info">
                                    {{ number_format($item->nominal_plafond - $item->nominal_plafond_sementara, 0, ',', '.') }}
                                </td>
                                <td>{{ $item->fullname }}</td>
                                <td>
                                    @if ($item->flag_approve == 'Y')
                                        <span class="badge text-bg-success">Approve</span>
                                    @else
                                        <span class="badge text-bg-danger">Belum</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($item->no_mutasi)
                                        {{ $item->no_mutasi }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                            data-bs-toggle="dropdown">
                                            <i class="bx bx-dots-vertical-rounded"></i>
                                        </button>

                                        <div class="dropdown-menu">
                                            <a href="" class="dropdown-item">
                                                <i class='bx bx-fast-forward'></i> SO to BO
                                            </a>
                                            <a href="" class="dropdown-item">
                                                <i class='bx bxs-lock-alt'></i> Buka SO
                                            </a>
                                            <a href="" class="dropdown-item">
                                                <i class='bx bxs-detail'></i> Details
                                            </a>
                                            <a href="" class="dropdown-item">
                                                <i class='bx bx-x'></i> Batal
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center">No Data</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div wire:loading.class="d-none" wire:target="gotoPage">
                {{ $items->links() }}
            </div>
        </div>
    </div>
</div>
</div>
