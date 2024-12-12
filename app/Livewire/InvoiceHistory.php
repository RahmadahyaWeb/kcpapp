<?php

namespace App\Livewire;

use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class InvoiceHistory extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $target = 'noinv';

    public $noinv;

    public function render()
    {
        $items = DB::connection('kcpinformation')
            ->table('trns_inv_header')
            ->whereRaw('SUBSTR(noinv, 1, 3) <> ?', ['RTU'])
            ->whereRaw('SUBSTR(noinv, 1, 3) <> ?', ['RTC'])
            ->where('noinv', 'like', '%' . $this->noinv . '%')
            ->latest('crea_date')
            ->paginate(20);

        return view('livewire.invoice-history', compact([
            'items'
        ]));
    }
}
