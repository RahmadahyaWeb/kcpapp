<div wire:ignore.self class="modal fade" id="modal-edit-keterangan" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="modal-edit-keteranganLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="modal-edit-keteranganLabel">Edit Keterangan</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form wire:submit="updateKeterangan">
                    @csrf
                    <div class="row g-2">
                        <div class="col-12">
                            <label class="form-label">Part Number</label>
                            <input type="text" class="form-control" value="{{ $part_number }}" disabled>
                            <input type="hidden" value="{{ $id }}" name="part_number">
                        </div>
                        <div class="col-12">
                            <label for="keterangan_text" class="form-label">Keterangan</label>
                            <input type="text" class="form-control @error('keterangan_text') is-invalid @enderror"
                                name="keterangan_text" wire:model="keterangan_text">
                            @error('keterangan_text')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        <div class="col-12 d-grid">
                            <button type="submit" class="btn btn-primary">Simpan</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
