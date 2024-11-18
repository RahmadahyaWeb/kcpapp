<?php

namespace App\Livewire;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Validate;
use Livewire\Component;

class AopDetail extends Component
{
    public $fakturPajak;
    public $editingFakturPajak;

    public $classProgram;
    public $styleProgram;

    public $classFakturPajak;
    public $styleFakturPajak;

    public function openModalFakturPajak()
    {
        $this->classFakturPajak = "show";
        $this->styleFakturPajak = "display: block;";

        $invoice = DB::table('invoice_aop_header')
            ->select(['*'])
            ->where('invoiceAop', $this->invoiceAop)
            ->first();

        $this->fakturPajak = $invoice->fakturPajak;
    }

    public function closeModalFakturPajak()
    {
        $this->classFakturPajak = "";
        $this->styleFakturPajak = "";
    }

    public function openModalProgram()
    {
        $this->classProgram = "show";
        $this->styleProgram = "display: block;";
    }

    public function closeModalProgram()
    {
        $this->resetValidation(['potonganProgram', 'keteranganProgram']);

        $this->classProgram = "";
        $this->styleProgram = "";
    }

    #[Validate('required')]
    public $potonganProgram = '';

    #[Validate('required')]
    public $keteranganProgram = '';

    public $customerTo;
    public $tanggalInvoice;

    public function saveProgram()
    {
        $this->classProgram = "show";
        $this->styleProgram = "display: block;";

        $validated = $this->validate();

        $validated['customerTo'] = $this->customerTo;
        $validated['invoiceAop'] = $this->invoiceAop;
        $validated['tanggalInvoice'] = $this->tanggalInvoice;

        DB::table('program_aop')
            ->insert($validated);

        $this->dispatch('programSaved');

        $this->classProgram = "";
        $this->styleProgram = "";

        $this->reset('potonganProgram');
        $this->reset('keteranganProgram');
    }

    public function destroyProgram($id)
    {
        DB::table('program_aop')
            ->where('id', $id)
            ->delete();
    }

    public function saveFakturPajak()
    {
        DB::table('invoice_aop_header')
            ->where('invoiceAop', $this->invoiceAop)
            ->update([
                'fakturPajak' => $this->fakturPajak
            ]);

        $this->dispatch('fakturPajakUpdate');
        
        $this->classFakturPajak = "";
        $this->styleFakturPajak = "";
    }

    public function updateFlag($invoiceAop)
    {
        DB::table('invoice_aop_header')
            ->where('invoiceAop', $invoiceAop)
            ->update([
                'flag_selesai'  => 'Y',
                'updated_at'    => now()
            ]);

        session()->flash('status', "Flag $invoiceAop berhasil disimpan. Silakan periksa data di list Data AOP Final.");

        $this->redirect('/aop');
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

    public $invoiceAop;
    public $totalAmount;
    public $totalQty;

    public function mount($invoiceAop)
    {
        $this->invoiceAop = $invoiceAop;
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

        $this->fakturPajak = $header->fakturPajak;
        $this->tanggalInvoice = $header->billingDocumentDate;
        $this->customerTo = $header->customerTo;

        $programAop = DB::table('program_aop')
            ->select(['*'])
            ->where('invoiceAop', $this->invoiceAop)
            ->get();

        return view('livewire.aop-detail', compact(
            'header',
            'details',
            'programAop'
        ));
    }
}
