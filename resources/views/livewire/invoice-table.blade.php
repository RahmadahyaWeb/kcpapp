    <div>
        @if (session('success'))
            <div id="success-alert" class="alert alert-primary alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if (session('error'))
            <div id="error-alert" class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row g-2 mb-3">
            <div class="col-md-3 d-grid">
                <a href="{{ route('inv.history') }}" class="btn btn-success">History Invoice</a>
            </div>
            <div class="col-md-3 d-grid">
                <a href="{{ route('inv.bosnet') }}" class="btn btn-success">Invoice Bosnet</a>
            </div>
        </div>

        <!-- SALES ORDER YANG BELUM INVOICE -->
        <div class="card mb-3">
            <!-- Card Header -->
            <div class="card-header">
                <b>List Sales Order / SO yang belum invoice</b>
            </div>

            <div class="card-body">
                <!-- Data Table -->
                <div class="table-responsive mb-6">
                    <table class="table table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th style="width: 18%;">No Sales Order / SO</th>
                                <th>Kode Toko</th>
                                <th>Nama Toko</th>
                                <th>Nominal Invoice + PPn (Rp)</th>
                                <th>Nama Sales</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($so_belum_invoice as $item)
                                <tr>
                                    <td>{{ $item->noso }}</td>
                                    <td>{{ $item->kd_outlet }}</td>
                                    <td>{{ $item->nm_outlet }}</td>
                                    <td>{{ number_format($item->nominal_total, 0, ',', '.') }}</td>
                                    <td>{{ $item->user_sales }}</td>
                                    <td>
                                        <div class="dropdown">
                                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                                data-bs-toggle="dropdown">
                                                <i class="bx bx-dots-vertical-rounded"></i>
                                            </button>

                                            <div class="dropdown-menu">
                                                <a href="{{ route('inv.detail', $item->noso) }}" class="dropdown-item">
                                                    Detail
                                                </a>
                                                <a href="" class="dropdown-item">
                                                    Batal
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center">No Data</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- INVOICE -->
        <div class="card">
            <!-- Card Header -->
            <div class="card-header">
                <b>List Invoice</b>
            </div>

            <div class="card-body">
                <!-- Data Table -->
                <div class="table-responsive mb-6">
                    <table class="table table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th style="width: 20%;">No Invoice</th>
                                <th style="width: 20%;">No SO</th>
                                <th>Kode Toko</th>
                                <th>Nama Toko</th>
                                <th>Nominal Invoice</th>
                                <th>Nominal Invoice + PPn (Rp)</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($invoices as $invoice)
                                <tr>
                                    <td>
                                        KCP/{{ $invoice->area_inv }}/{{ $invoice->noinv }}
                                    </td>
                                    <td>
                                        KCP/{{ $invoice->area_inv }}/{{ $invoice->noso }}
                                    </td>
                                    <td>
                                        {{ $invoice->kd_outlet }}
                                    </td>
                                    <td>
                                        {{ $invoice->nm_outlet }}
                                    </td>
                                    <td>
                                        {{ number_format($invoice->nominal_total_noppn, 0, ',', '.') }}
                                    </td>
                                    <td>
                                        {{ number_format($invoice->nominal_total_ppn, 0, ',', '.') }}
                                    </td>
                                    <td class="text-center">


                                        <div class="dropdown">
                                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                                data-bs-toggle="dropdown">
                                                <i class="bx bx-dots-vertical-rounded"></i>
                                            </button>

                                            <div class="dropdown-menu">
                                                <a href="{{ route('inv.detail-print', $invoice->noinv) }}"
                                                    class="dropdown-item">
                                                    Detail
                                                </a>
                                                <a href="{{ route('inv.batal', $invoice->noso) }}"
                                                    class="dropdown-item">
                                                    Batal
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center">No Data</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
