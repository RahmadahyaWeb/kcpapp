<div> 
    <div class="card-body">
        <div class="row mb-3 g-2">
            <div class="col-md-6">
                <label class="form-label">Nama Toko</label>
                <input id="nama_toko" type="text" class="form-control" wire:model.live="nama_toko"
                    placeholder="Cari berdasarkan nama toko">
            </div>
            <div class="col-md-6">
                <label class="form-label">Kode Toko</label>
                <input id="kd_toko" type="text" class="form-control" wire:model.live="kode_toko"
                    placeholder="Cari berdasarkan kode toko">
            </div>
        </div>

        <div wire:loading.flex wire:target="nama_toko, kode_toko, gotoPage"
            class="text-center justify-content-center align-items-center" style="height: 200px;">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>

        <div class="table-responsive mb-6" wire:loading.class="d-none" wire:target="nama_toko, kode_toko, gotoPage">
            <table class="table table-hover table-sm">
                <thead class="table-dark">
                    <tr>
                        <th>Kode Toko</th>
                        <th>Nama Toko</th>
                        <th>Alamat</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($items->isEmpty())
                        <tr>
                            <td colspan="5" class="text-center">No data</td>
                        </tr>
                    @else
                        @foreach ($items as $item)
                            <tr>
                                <td class="text-uppercase">{{ $item->kd_toko }}</td>
                                <td>{{ $item->nama_toko }}</td>
                                <td>{{ $item->alamat }}</td>
                                <td>
                                    <div class="d-flex justify-content-start gap-2">
                                        <div class="d-grid">
                                            <a href="{{ route('master-toko.edit', $item->kd_toko) }}"
                                                class="btn btn-sm btn-warning text-white">
                                                <div class="ms-1">Edit</div>
                                            </a>
                                        </div>

                                        <div class="d-grid">
                                            <a href="{{ route('master-toko.destroy', $item->kd_toko) }}"
                                                class="btn btn-sm btn-danger text-white"
                                                onclick="return confirm('Apakah Anda yakin ingin menghapus item ini?');">
                                                <div class="ms-1">Hapus</div>
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @endif

                </tbody>
            </table>
        </div>

        <div wire:loading.class="d-none" wire:target="nama_toko, kode_toko, gotoPage">
            {{ $items->links() }}
        </div>
    </div>
</div>
