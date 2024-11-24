<div>
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <b>Rekap Punishment DKS</b>
                    <hr>
                </div>
                <div class="card-body">
                    <form wire:submit="export">
                        @csrf
                        <div class="row mb-3">
                            <div class="col-md-6 mb-3">
                                <label for="fromDate" class="form-label">Dari tanggal</label>
                                <input id="fromDate" type="date"
                                    class="form-control @error('fromDate') is-invalid @enderror"
                                    wire:model.live="fromDate" name="fromDate">
                                @error('fromDate')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="toDate" class="form-label">Sampai tanggal</label>
                                <input id="toDate" type="date"
                                    class="form-control @error('toDate') is-invalid @enderror" wire:model.live="toDate"
                                    name="toDate">
                                @error('toDate')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="user_sales" class="form-label">Pilih Sales</label>
                                <select wire:model.change="user_sales" class="form-select">
                                    <option value="all">Semua Sales</option>
                                    @foreach ($users as $user)
                                        <option value="{{ $user->username }}">{{ $user->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="laporan" class="form-label">Pilih Laporan</label>
                                <select wire:model.change="laporan" class="form-select @error('laporan') is-invalid @enderror">
                                    <option value="">Pilih Laporan</option>
                                    <option value="rekap_punishment">Rekap Punishment</option>
                                    <option value="frekuensi_kunjungan">Frekuensi Kunjungan</option>
                                </select>
                                @error('laporan')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="col-12 d-flex justify-content-end gap-2">
                                <button type="submit" class="btn btn-success">
                                    <span wire:loading.remove wire:target="export">Download</span>
                                    <span wire:loading wire:target="export">Downloading...</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
