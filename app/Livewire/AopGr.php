<?php

namespace App\Livewire;

use App\Models\KcpInformation;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;

class AopGr extends Component
{
    use WithPagination, WithoutUrlPagination;
    protected $paginationTheme = 'bootstrap';

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
        $kcpInformation = new KcpInformation;

        $login = $kcpInformation->login();

        if ($login) {
            $token = $login['token'];
        }

        if (!$login) {
            return 0;
        }

        $intransitStock = $kcpInformation->getIntransitBySpb($token, $spb);

        $totalQtyTerima = 0;

        if (isset($intransitStock['data'])) {
            foreach ($intransitStock as $items) {
                foreach ($items as $item) {
                    $totalQtyTerima += $item['qty_terima'];
                }
            }
        }

        return $totalQtyTerima;
    }

    public function checkApiConn()
    {
        $kcpInformation = new KcpInformation;

        $login = $kcpInformation->login();

        return $login;
    }

    public $invoiceAop;
    public $spb;

    public function render()
    {
        $conn = $this->checkApiConn();

        $invoiceAopHeader = DB::table('invoice_aop_header')
            ->select('SPB')
            ->groupBy('SPB')
            ->get();

        $items = [];
        foreach ($invoiceAopHeader as $spb) {

            if ($conn) {
                $totalQtyTerima = $this->getIntransitBySpb($spb->SPB);
            } else {
                $totalQtyTerima = 'API Error.';
            }

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
