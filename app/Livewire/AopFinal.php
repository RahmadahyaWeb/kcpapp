<?php

namespace App\Livewire;

use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;

class AopFinal extends Component
{
    use WithPagination, WithoutUrlPagination;

    protected $paginationTheme = 'bootstrap';

    public $invoiceAop;
    public $status;

    public function search()
    {
        $this->resetPage();
    }

    public function cancel($invoiceAop)
    {
        DB::table('invoice_aop_header')
            ->where('invoiceAop', $invoiceAop)
            ->update([
                'flag_selesai' => 'N',
            ]);
            
        session()->flash('status', "Invoice: $invoiceAop berhasil dibatalkan. Silakan periksa data di list Data Upload AOP.");
    }

    public function render()
    {
        $query = DB::table('invoice_aop_header')
            ->select(['*'])
            ->where('invoiceAop', 'like', '%' . $this->invoiceAop . '%')
            ->where('flag_selesai', '!=', 'N');

        if (!empty($this->status)) {
            $query->where('status', $this->status);
        }

        $invoiceAopHeader = $query->orderBy('updated_at', 'desc')->paginate(20);

        return view('livewire.aop-final', compact('invoiceAopHeader'));
    }
}
