<?php

namespace App\Livewire;

use App\Models\KcpInformation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class InvoiceBosnet extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $kcpInformation;
    public $token;

    public $noSo = '';
    public $noInv = '';
    public $status = '';

    public function render()
    {
        $invoices = $this->getFilteredInvoices();
        return view('livewire.invoice-bosnet', compact('invoices'));
    }

    /**
     * Retrieve filtered invoices for the table.
     */
    private function getFilteredInvoices()
    {
        return DB::table('invoice_bosnet')
            ->where('noso', 'like', '%' . $this->noSo . '%')
            ->where('noinv', 'like', '%' . $this->noInv . '%')
            ->where('status_bosnet', 'like', '%' . $this->status . '%')
            ->paginate(20);
    }
}
