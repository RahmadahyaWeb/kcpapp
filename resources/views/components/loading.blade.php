<div wire:loading.flex wire:target="{{ $target }}, gotoPage"
    class="text-center justify-content-center align-items-center"
    style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
            background-color: rgba(0, 0, 0, 0.1); z-index: 9999;">
    <div class="spinner-border text-primary" role="status"
        style="width: 4rem; height: 4rem; border-width: 0.4em; margin: auto;">
        <span class="visually-hidden">Loading...</span>
    </div>
</div>
