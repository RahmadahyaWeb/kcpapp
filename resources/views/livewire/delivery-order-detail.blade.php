<div>
    @if (session('success'))
        <div class="alert alert-primary alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @php
        use App\Livewire\DeliveryOrderDetail;
    @endphp
    <div class="row gap-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    Detail LKH: <b>{{ $no_lkh }}</b>
                    <hr>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="row mb-3">
                                <div class="col col-4 col-md-4">
                                    <div>No LKH</div>
                                </div>
                                <div class="col col-auto">
                                    :
                                </div>
                                <div class="col col-auto">
                                    <div>{{ $header->no_lkh }}</div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col col-4 col-md-4">
                                    <div>Driver</div>
                                </div>
                                <div class="col col-auto">
                                    :
                                </div>
                                <div class="col col-auto">
                                    <div>{{ $header->driver }}</div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col col-4 col-md-4">
                                    <div>Helper</div>
                                </div>
                                <div class="col col-auto">
                                    :
                                </div>
                                <div class="col col-auto">
                                    <div>{{ $header->helper }}</div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col col-4 col-md-4">
                                    <div>Plat Mobil</div>
                                </div>
                                <div class="col col-auto">
                                    :
                                </div>
                                <div class="col col-auto">
                                    <div>{{ $header->plat_mobil }}</div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col col-4 col-md-4">
                                    <div>Jam Berangkat</div>
                                </div>
                                <div class="col col-auto">
                                    :
                                </div>
                                <div class="col col-auto">
                                    <div>{{ $header->jam_berangkat }}</div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col col-4 col-md-4">
                                    <div>Jam Balik</div>
                                </div>
                                <div class="col col-auto">
                                    :
                                </div>
                                <div class="col col-auto">
                                    <div>{{ $header->jam_balik }}</div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col col-4 col-md-4">
                                    <div>KM Saat Berangkat</div>
                                </div>
                                <div class="col col-auto">
                                    :
                                </div>
                                <div class="col col-auto">
                                    <div>{{ $header->km_berangkat }}</div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col col-4 col-md-4">
                                    <div>KM Saat Balik</div>
                                </div>
                                <div class="col col-auto">
                                    :
                                </div>
                                <div class="col col-auto">
                                    <div>{{ $header->km_balik }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if ($ready_to_sent && $status == false)
                        <div class="row">
                            <form wire:submit="sendToBosnet" wire:confirm="Yakin ingin kirim data ke Bosnet?">
                                <div class="col d-grid">
                                    <hr>
                                    <button type="submit" class="btn btn-warning" wire:offline.attr="disabled">
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

        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    Detail LKH: <b>{{ $no_lkh }}</b>
                    <hr>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>No. LKH</th>
                                    <th>Kode / Nama Toko</th>
                                    <th>No. Sales Order</th>
                                    <th>No. Invoice</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if ($items->isEmpty())
                                    <tr>
                                        <td class="text-center" colspan="5">No Data</td>
                                    </tr>
                                @else
                                    @foreach ($items as $item)
                                        <tr>
                                            <td>{{ $item->no_lkh }}</td>
                                            <td>{{ $item->kd_outlet }} / {{ $item->nm_outlet }}</td>
                                            <td>
                                                {{ $item->noso }}
                                            </td>
                                            <td>
                                                {{ $item->noinv }}
                                            </td>
                                            <td>
                                                @isset(DeliveryOrderDetail::cek_status($item->noinv)->status_bosnet)
                                                    @if (DeliveryOrderDetail::cek_status($item->noinv)->status_bosnet == 'KCP')
                                                        <span class="badge text-bg-danger">
                                                            SO masih di KCP.
                                                        </span>
                                                    @else
                                                        <span class="badge text-bg-success">
                                                            Siap dikirim.
                                                        </span>
                                                    @endif
                                                @else
                                                    <span class="badge text-bg-warning">
                                                        SO / INV belum diprint.
                                                    </span>
                                                @endisset
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
    </div>
</div>
