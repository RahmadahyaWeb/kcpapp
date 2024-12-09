<?php

namespace App\Livewire;

use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class StockMovementTable extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $target, $part_number;

    public function mount()
    {
        $this->target = 'part_number';
    }

    public function render()
    {
        $kcpapplication = DB::connection('mysql');

        $log_stock = $kcpapplication->table('trns_log_stock')
            ->where('part_no', 'like', '%' . $this->part_number . '%')
            ->paginate(20);

        return view('livewire.stock-movement-table', compact(
            'log_stock'
        ));
    }
}
