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
                                                    <i class='bx bx-check'></i> Ok
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
                <div class="row align-items-center">
                    <div class="col">
                        <b>List Invoice</b>
                    </div>
                    <div class="col d-flex justify-content-end">
                        <button wire:click="synchronization" class="btn btn-success" wire:target="synchronization"
                            wire:loading.attr="disabled">
                            <i class='bx bx-sync me-1'></i> Sinkron
                        </button>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <!-- Data Table -->
                <div class="table-responsive mb-6">
                    <table class="table table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th style="width: 18%;">No Invoice</th>
                                <th style="width: 18%;">No SO</th>
                                <th>Nominal Invoice + PPn (Rp)</th>
                                <th>Status</th>
                                <th>Sent to Bosnet</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($invoices as $invoice)
                                <tr>
                                    <td>
                                        {{ $invoice->noinv }}
                                    </td>
                                    <td>
                                        {{ $invoice->noso }}
                                    </td>
                                    <td>{{ number_format($invoice->amount_total, 0, ',', '.') }}</td>
                                    <td>
                                        <span
                                            class="badge text-bg-{{ $invoice->status_bosnet == 'KCP' ? 'success' : 'warning' }}">
                                            {{ $invoice->status_bosnet }}
                                        </span>
                                    </td>
                                    <td>
                                        {{ $invoice->send_to_bosnet }}
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
    </div>
