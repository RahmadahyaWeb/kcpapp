<?php

namespace App\Livewire;

use App\Models\KcpInformation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Validate;
use Livewire\Component;

class NonAopDetail extends Component
{
    public $search;
    public $invoiceNon;
    public $customerTo;
    public $total;

    #[Validate('required')]
    public $materialNumber;

    #[Validate('required')]
    public $qty;

    #[Validate('required')]
    public $price;

    #[Validate('required')]
    public $totalFisik = 0;

    public $extraPlafonDiscount = 0;

    public function updatedQty()
    {
        $this->calculateTotal();
    }

    public function updatedPrice()
    {
        $this->calculateTotal();
    }

    public function updatedTotalFisik()
    {
        $this->calculateTotal();
    }

    private function calculateTotal()
    {
        $this->total = (int)$this->qty * (int)$this->price;
        $this->extraPlafonDiscount = (int)$this->total - (int)$this->totalFisik;
    }

    public function addItem()
    {
        $this->validate();

        DB::table('invoice_non_detail')
            ->insert([
                'invoiceNon'            => $this->invoiceNon,
                'SPB'                   => '',
                'customerTo'            => $this->customerTo,
                'materialNumber'        => $this->materialNumber,
                'qty'                   => $this->qty,
                'price'                 => $this->price,
                'extraPlafonDiscount'   => $this->extraPlafonDiscount,
                'amount'                => ($this->price * $this->qty) - $this->extraPlafonDiscount,
                'created_by'            => Auth::user()->username,
                'created_at'            => now(),
                'updated_at'            => now()
            ]);

        $currentHeader = DB::table('invoice_non_header')
            ->where('invoiceNon', $this->invoiceNon)
            ->first();

        $newQty = $currentHeader->qty + $this->qty;
        $newTotalAmount = $currentHeader->amount + (($this->price * $this->qty) - $this->extraPlafonDiscount);
        $newPrice = $currentHeader->price + $this->price;
        $newExtraPlafonDiscount = $currentHeader->extraPlafonDiscount + $this->extraPlafonDiscount;

        DB::table('invoice_non_header')
            ->where('invoiceNon', $this->invoiceNon)
            ->update([
                'qty'                   => $newQty,
                'price'                 => $newPrice,
                'amount'                => $newTotalAmount,
                'extraPlafonDiscount'   => $newExtraPlafonDiscount
            ]);

        session()->flash('status', "Item dengan No Part $this->materialNumber berhasil ditambahkan.");

        $this->reset('materialNumber', 'qty', 'price', 'extraPlafonDiscount');
    }

    public function destroyItem($id)
    {
        $currentHeader = DB::table('invoice_non_header')
            ->where('invoiceNon', $this->invoiceNon)
            ->first();

        $currentItem = DB::table('invoice_non_detail')
            ->where('id', $id)
            ->first();

        DB::table('invoice_non_header')
            ->where('invoiceNon', $this->invoiceNon)
            ->update([
                'qty'                   => $currentHeader->qty - $currentItem->qty,
                'price'                 => $currentHeader->price - $currentItem->price,
                'amount'                => $currentHeader->amount - $currentItem->amount,
                'extraPlafonDiscount'   => $currentHeader->extraPlafonDiscount - $currentItem->extraPlafonDiscount
            ]);

        DB::table('invoice_non_detail')
            ->where('id', $id)
            ->delete();

        session()->flash('status', "Item berhasil berhasil dihapus.");
    }

    public function updateFlag()
    {
        DB::table('invoice_non_header')
            ->where('invoiceNon', $this->invoiceNon)
            ->update([
                'flag_selesai'  => 'Y',
                'updated_at'    => now()
            ]);

        session()->flash('status', "Flag $this->invoiceNon berhasil disimpan.");
    }

    public function sendToBosnet()
    {
        if ($this->sendToBosnetAPI()) {
            DB::table('invoice_non_header')
                ->where('invoiceNon', $this->invoiceNon)
                ->update([
                    'status'        => 'BOSNET',
                    'sendToBosnet'  => now()
                ]);

            session()->flash('status', "Data invoice: $this->invoiceNon berhasil dikirim!");

            $this->redirect('/non-aop');
        }
    }

    public function sendToBosnetApi()
    {
        $invoiceHeader = DB::table('invoice_non_header')
            ->select(['*'])
            ->where('invoiceNon', $this->invoiceNon)
            ->first();

        $invoiceDetails = DB::table('invoice_non_detail')
            ->select(['*'])
            ->where('invoiceNon', $this->invoiceNon)
            ->get();

        // ITEMS
        $items = [];
        foreach ($invoiceDetails as $value) {
            $item = [];
            $item['szProductId']           = $value->materialNumber;
            $item['decQty']                = $value->qty;
            $item['szUomId']               = "";
            $item['decPrize']              = $value->price;
            $item['decDiscount']           = $value->extraPlafonDiscount;
            $item['purchaseITemTypeId']    = "BELI";

            $items[] = $item;
        }

        // PAYMENT TERM ID
        $billingDate = Carbon::parse($invoiceHeader->billingDocumentDate);
        $dueDate = Carbon::parse($invoiceHeader->tanggalJatuhTempo);

        $paymentTermId = $billingDate->diffInDays($dueDate);

        return [
            'szFpoId'                   => $invoiceHeader->invoiceNon,
            'dtmPO'                     => date('Y-m-d H:i:s', strtotime($invoiceHeader->billingDocumentDate)),
            'dtmReceipt'                => "",
            'bReturn'                   => 0,
            'szRefDn'                   => $invoiceHeader->SPB,
            'szWarehouseId'             => "",
            'szStockTypeId'             => "Good Stock",
            'szSupplierId'              => "",
            'paymentTermId'             => $paymentTermId . " HARI",
            'szPOReceiptIdForReturn'    => "",
            'szWorkplaceId'             => "",
            'szCarrierId'               => "",
            'szVehicleId'               => "",
            'szDriverId'                => "",
            'szVehicleNumber'           => "",
            'szDriverNm'                => "",
            'szDescription'             => "",
            'items'                     => $items
        ];
    }

    public function mount($invoiceNon)
    {
        $this->invoiceNon = $invoiceNon;
    }

    public function getNonAopParts()
    {
        $kcpInformation = new KcpInformation;

        $login = $kcpInformation->login();

        if ($login) {
            $token = $login['token'];
        }

        $nonAopParts = $kcpInformation->getNonAopParts($token);

        if ($nonAopParts) {
            return $nonAopParts;
        }
    }

    public function checkApiConn()
    {
        $kcpInformation = new KcpInformation;

        $login = $kcpInformation->login();

        return $login;
    }

    public function render()
    {
        $conn = $this->checkApiConn();

        if (!$conn) {
            abort(500);
        }

        $header = DB::table('invoice_non_header')
            ->where('invoiceNon', $this->invoiceNon)
            ->leftJoin('master_supplier', 'invoice_non_header.supplierCode', '=', 'master_supplier.supplierCode')
            ->first();

        $this->customerTo = $header->customerTo;

        $nonAopParts = $this->getNonAopParts();

        $search = $this->search;

        $nonAopParts = array_filter($nonAopParts['data'], function ($item) use ($search) {
            return strpos(strtolower($item['txt']), strtolower($search)) !== false;
        });

        $details = DB::table('invoice_non_detail')
            ->where('invoiceNon', $this->invoiceNon)
            ->get();

        return view('livewire.non-aop-detail', compact('header', 'nonAopParts', 'details'));
    }
}
