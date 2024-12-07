<?php

namespace App\Livewire;

use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class CustomerPaymentTable extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $no_piutang;
    public $status_customer_payment = 'O';
    public $target;

    public function mount()
    {
        $this->target = 'no_piutang, status_customer_payment';
    }

    public function render()
    {
        $kcpapplication = DB::connection('mysql');

        $customer_payment_header = $kcpapplication
            ->table('customer_payment_header')
            ->where('no_piutang', 'like', '%' . $this->no_piutang . '%')
            ->where('status', $this->status_customer_payment)
            ->paginate(20);

        return view('livewire.customer-payment-table', compact(
            'customer_payment_header'
        ));
    }
}
