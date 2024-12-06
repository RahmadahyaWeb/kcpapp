<div wire:ignore.self class="modal fade" id="modal-edit-qty" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="modal-edit-qtyLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="modal-edit-qtyLabel">Edit Qty</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form wire:submit="updateQty">
                    @csrf
                    <div class="row g-2">
                        <div class="col-12">
                            <label class="form-label">Part Number</label>
                            <input type="text" class="form-control" value="{{ $part_number }}" disabled>
                            <input type="hidden" value="{{ $part_number }}" name="part_number">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Qty saat ini</label>
                            <input type="text" class="form-control" value="{{ $qty }}" disabled>
                        </div>
                        <div class="col-12">
                            <label for="edited_qty" class="form-label">Qty baru</label>
                            <input type="number" class="form-control @error('edited_qty') is-invalid @enderror"
                                name="edited_qty" wire:model="edited_qty">
                            @error('edited_qty')
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
