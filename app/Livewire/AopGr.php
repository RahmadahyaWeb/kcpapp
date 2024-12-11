<?php

namespace App\Livewire;

use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;

class AopGr extends Component
{
    use WithPagination, WithoutUrlPagination;
    protected $paginationTheme = 'bootstrap';

    public $target = 'invoiceAop, spb';
    public $invoiceAop;
    public $spb;

    public function render()
    {
        $items = DB::table('invoice_aop_header')
            ->select(['*'])
            ->where('flag_po', 'Y')
            ->where('invoiceAop', 'like', '%' . $this->invoiceAop . '%')
            ->where('SPB', 'like', '%' . $this->spb . '%')
            ->paginate(20);

        return view('livewire.aop-gr', compact('items'));
    }
}
