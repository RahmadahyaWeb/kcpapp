@php
    $days = [
        'Sunday' => 'Minggu',
        'Monday' => 'Senin',
        'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis',
        'Friday' => 'Jumat',
        'Saturday' => 'Sabtu',
    ];

    $tokoAbsen = ['6B', '6C', '6D', '6F', '6H', 'TX'];
@endphp

<div>
    <div class="card">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col">
                    <b>DKS Monitoring</b>
                </div>
            </div>
        </div>

        <div class="container">
            <div class="mb-3 d-flex gap-2 justify-content-end">
                <a href="{{ route('report.dks-rekap-punishment') }}" class="btn btn-success">Rekap Punishment</a>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-md-6">
                    <label for="fromDate" class="form-label">Dari tanggal</label>
                    <input id="fromDate" type="date" class="form-control @error('fromDate') is-invalid @enderror"
                        wire:model.live="fromDate" name="fromDate">
                    @error('fromDate')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label for="toDate" class="form-label">Sampai tanggal</label>
                    <input id="toDate" type="date" class="form-control @error('toDate') is-invalid @enderror"
                        wire:model.live="toDate" name="toDate">
                    @error('toDate')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label for="user_sales" class="form-label">Sales</label>
                    <select name="user_sales" id="user_sales" class="form-select" wire:model.change="user_sales">
                        <option value="" selected>Pilih Sales</option>
                        @foreach ($sales as $user)
                            <option value="{{ $user->username }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="kd_toko" class="form-label">Nama Toko</label>
                    <select name="kd_toko" id="kd_toko" class="form-select" wire:model.change="kd_toko">
                        <option value="" selected>Pilih Toko</option>
                        @foreach ($dataToko as $toko)
                            <option value="{{ $toko->kd_toko }}">{{ $toko->nama_toko }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div wire:loading.flex wire:target="toDate, user_sales, kd_toko, gotoPage"
            class="text-center justify-content-center align-items-center" style="height: 200px;">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>

        <div class="table-responsive mb-6" wire:loading.class="d-none"
            wire:target="toDate, user_sales, kd_toko, gotoPage">
            <table class="table table-hover table-sm">
                <thead class="table-dark">
                    <tr>
                        <th>Nama</th>
                        <th>Tgl. Kunjungan</th>
                        <th>Kode Toko</th>
                        <th>Toko</th>
                        <th>Check In</th>
                        <th>Katalog</th>
                        <th>Check Out</th>
                        <th>Lama Kunjungan</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($items->isEmpty())
                        <tr>
                            <td colspan="9" class="text-center">No data</td>
                        </tr>
                    @else
                        @foreach ($items as $item)
                            @php
                                $carbonDate = \Carbon\Carbon::parse($item->tgl_kunjungan);
                                $formattedDate = $days[$carbonDate->format('l')] . ', ' . $carbonDate->format('d-m-Y');
                            @endphp
                            <tr>
                                <td>{{ $item->user_sales }}</td>
                                <td>{{ $formattedDate }}</td>
                                <td>{{ $item->kd_toko }}</td>
                                <td>{{ $item->nama_toko }}</td>
                                <td>{{ date('H:i:s', strtotime($item->waktu_cek_in)) }}</td>
                                <td>
                                    @if ($item->katalog_at)
                                        {{ date('H:i:s', strtotime($item->katalog_at)) }}
                                    @else
                                        Belum scan katalog
                                    @endif
                                </td>
                                @if (in_array($item->kd_toko, $tokoAbsen))
                                    <td>
                                        @if ($item->waktu_cek_out)
                                            {{ date('H:i:s', strtotime($item->waktu_cek_out)) }}
                                        @else
                                            Belum check out
                                        @endif
                                    </td>
                                    <td>-</td>
                                    <td>Absen Toko</td>
                                @else
                                    <td>
                                        @if ($item->waktu_cek_out)
                                            {{ date('H:i:s', strtotime($item->waktu_cek_out)) }}
                                        @else
                                            Belum check out
                                        @endif
                                    </td>
                                    <td>
                                        @if ($item->lama_kunjungan != null)
                                            {{ $item->lama_kunjungan }} menit
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{{ $item->keterangan }}</td>
                                @endif
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>

        <div class="container">
            <div wire:loading.class="d-none" wire:target="toDate, user_sales, kd_toko, gotoPage">
                {{ $items->links() }}
            </div>
        </div>
    </div>
</div>
