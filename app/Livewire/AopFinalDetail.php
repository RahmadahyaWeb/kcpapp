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
            $item['decPrize']              = $value->price;
            $item['decDiscount']           = $value->extraPlafonDiscount;
            $item['purchaseITemTypeId']    = "BELI";

            $items[] = $item;
        }

        // PAYMENT TERM ID
        $billingDate = Carbon::parse($invoiceHeader->billingDocumentDate);
        $dueDate = Carbon::parse($invoiceHeader->tanggalJatuhTempo);

        $paymentTermId = $billingDate->diffInDays($dueDate);

        $dataToSent = [
            'szFpoId'                   => $invoiceHeader->invoiceAop,
            'szFAPInvoiceId'            => $invoiceHeader->invoiceAop,
            'dtmPO'                     => date('Y-m-d H:i:s', strtotime($invoiceHeader->billingDocumentDate)),
            'dtmReceipt'                => "",
            'bReturn'                   => 0,
            'szRefDn'                   => $invoiceHeader->SPB,
            'szWarehouseId'             => "KCP01001",
            'szStockTypeId'             => "Good Stock",
            'szSupplierId'              => "AOP",
            'paymentTermId'             => $paymentTermId . " HARI",
            'szPOReceiptIdForReturn'    => "",
            'szWorkplaceId'             => "KCP01001",
            'szCarrierId'               => "",
            'szVehicleId'               => "",
            'szDriverId'                => "",
            'szVehicleNumber'           => "",
            'szDriverNm'                => "",
            'szDescription'             => "",
            'items'                     => $items
        ];

        return true;

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
