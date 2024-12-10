<?php

namespace App\Livewire;

use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;

class NonAop extends Component
{
    use WithPagination, WithoutUrlPagination;

    protected $paginationTheme = 'bootstrap';

    public $invoiceNon;
    public $tanggalJatuhTempo;
    public $status;

    public function hapusInvoiceNon($invoiceNon)
    {
        DB::table('invoice_non_header')
            ->where('invoiceNon', $invoiceNon)
            ->delete();

        DB::table('invoice_non_detail')
            ->where('invoiceNon', $invoiceNon)
            ->delete();

        session()->flash('status', "Invoice: $invoiceNon berhasil dihapus.");
    }

    public function detailInvoiceNon($invoiceNon)
    {
        $this->redirect("/pembelian/non-aop/detail/$invoiceNon");
    }

    public function render()
    {
        $query = DB::table('invoice_non_header')
            ->select(['*'])
            ->where('invoiceNon', 'like', '%' . $this->invoiceNon . '%');


        if (!empty($this->status)) {
            $query->where('status', $this->status);
        }

        $invoiceNonAopHeader = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('livewire.non-aop', compact('invoiceNonAopHeader'));
    }
}
