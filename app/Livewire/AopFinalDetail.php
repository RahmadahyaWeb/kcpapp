<?php

namespace App\Livewire;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class AopFinalDetail extends Component
{
    public $invoiceAop;
    public $totalAmount;
    public $totalQty;

    public function mount($invoiceAop)
    {
        $this->invoiceAop = $invoiceAop;
    }

    public function sendToBosnet($invoiceAop)
    {
        if ($this->sendToBosnetAPI($invoiceAop)) {
            DB::table('invoice_aop_header')
                ->where('invoiceAop', $invoiceAop)
                ->update([
                    'status'        => 'BOSNET',
                    'sendToBosnet'  => now()
                ]);

            session()->flash('status', "Data invoice: $invoiceAop berhasil dikirim!");

            $this->redirect('/aop/final');
        }
    }

    public function sendToBosnetAPI($invoiceAop)
    {
        $invoiceHeader = DB::table('invoice_aop_header')
            ->select(['*'])
            ->where('invoiceAop', $invoiceAop)
            ->first();

        $invoiceDetails = DB::table('invoice_aop_detail')
            ->select(['*'])
            ->where('invoiceAop', $invoiceAop)
            ->get();

        // ITEMS
        $items = [];
        foreach ($invoiceDetails as $value) {

            $item = [];
            $item['szProductId']           = $value->materialNumber;
            $item['decQty']                = $value->qty;
            $item['szUomId']               = "PCS";
            $item['decPrice']              = $value->price / $value->qty;
            $item['bTaxable']              = true;
            $item['decDiscount']           = 0;
            $item['decDiscPercentage']     = 0;
            $item['decDPP']                = $value->price / config('tax.ppn_factor');
            $item['decPPN']                = ($value->price / config('tax.ppn_factor')) * config('tax.ppn_percentage');
            $item['decAmount']             = $value->price;
            $item['purchaseITemTypeId']    = "BELI";
            $item['deliveryList']          = ['qty' => $value->qty];

            $items[] = $item;
        }

        // PAYMENT TERM ID
        $billingDate = Carbon::parse($invoiceHeader->billingDocumentDate);
        $dueDate = Carbon::parse($invoiceHeader->tanggalJatuhTempo);

        $paymentTermId = $billingDate->diffInDays($dueDate);

        $dataToSent = [
            'appId'                     => "BDI.KCP",
            'szFPo_sId'                 => $invoiceHeader->invoiceAop,
            'dtmPO'                     => date('Y-m-d H:i:s', strtotime($invoiceHeader->billingDocumentDate)),
            'szSupplierId'              => "AOP",
            'bReturn'                   => false,
            'szDescription'             => "",
            'szCcyId'                   => "IDR",
            'paymentTermId'             => $paymentTermId . " HARI",
            'purchaseTypeId'            => "BELI",
            'szPOReceiptIdForReturn'    => "",
            'docStatus'                 => [
                'bApplied'              => true,
            ],
            'itemList'                  => $items
        ];

        // return true;

        dd($dataToSent);

        // PROSES HIT API
    }

    public function render()
    {
        $header = DB::table('invoice_aop_header')
            ->select(['*'])
            ->where('invoiceAop', $this->invoiceAop)
            ->first();

        $details = DB::table('invoice_aop_detail')
            ->select(['*'])
            ->where('invoiceAop', $this->invoiceAop)
            ->get();

        $totalAmount = DB::table('invoice_aop_detail')
            ->where('invoiceAop', $this->invoiceAop)
            ->sum('amount');

        $totalQty = DB::table('invoice_aop_detail')
            ->where('invoiceAop', $this->invoiceAop)
            ->sum('qty');

        $this->totalAmount = $totalAmount;
        $this->totalQty = $totalQty;

        $programAop = DB::table('program_aop')
            ->select(['*'])
            ->where('invoiceAop', $this->invoiceAop)
            ->get();

        return view('livewire.aop-final-detail', compact(
            'header',
            'details',
            'programAop'
        ));
    }
}
