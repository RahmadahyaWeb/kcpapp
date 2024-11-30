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

    public $invoiceAop;
    public $spb;

    public function getTotalQty($spb)
    {
        return DB::table('invoice_aop_header')
            ->where('SPB', $spb)
            ->sum('qty');
    }

    public function getInvoices($spb)
    {
        $invoices = DB::table('invoice_aop_header')
            ->select(['invoiceAop', 'status'])
            ->where('SPB', $spb)
            ->get();

        $invoiceArray = [];
        foreach ($invoices as $invoice) {
            $invoiceArray[] = $invoice->invoiceAop;
        }

        return $invoiceArray;
    }

    public function getIntransitBySpb($spb)
    {
        $intransitStock = DB::connection('kcpinformation')
            ->table('intransit_header as a')
            ->join('intransit_details as b', 'a.no_sp_aop', '=', 'b.no_sp_aop')
            ->where('a.no_sp_aop', '=', $spb)
            ->select('a.no_sp_aop', 'a.kd_gudang_aop', 'a.tgl_packingsheet', 'b.no_packingsheet', 'b.no_doos', 'b.part_no', 'b.qty', 'b.qty_terima')
            ->get();

        $totalQtyTerima = 0;

        if ($intransitStock) {
            foreach ($intransitStock as $item) {
                $totalQtyTerima += $item->qty_terima;
            }
        }

        return $totalQtyTerima;
    }

    public function render()
    {
        $invoiceAopHeader = DB::table('invoice_aop_header')
            ->select('SPB')
            ->where('status', 'BOSNET')
            ->groupBy('SPB')
            ->get();

        $items = [];
        foreach ($invoiceAopHeader as $spb) {

            $totalQtyTerima = $this->getIntransitBySpb($spb->SPB);

            $totalQty = $this->getTotalQty($spb->SPB);
            $invoices = $this->getInvoices($spb->SPB);

            $items[$spb->SPB] = [
                'spb'            => $spb->SPB,
                'totalQtyTerima' => $totalQtyTerima,
                'totalQty'       => $totalQty,
                'invoices'       => $invoices,
            ];
        }

        if ($this->invoiceAop) {
            $items = array_filter($items, function ($item) {
                foreach ($item['invoices'] as $invoice) {
                    if (strpos($invoice, $this->invoiceAop) !== false) {
                        return true;
                    }
                }
                return false;
            });
        }

        if ($this->spb) {
            $items = array_filter($items, function ($item) {
                return strpos($item['spb'], $this->spb) !== false;
            });
        }

        return view('livewire.aop-gr', compact('items'));
    }
}
