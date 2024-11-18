<?php

namespace App\Livewire;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Validate;
use Livewire\Component;

class CreateNonAop extends Component
{
    #[Validate('required')]
    public $supplier;

    #[Validate('required', as: 'tanggal nota')]
    public $billingDocumentDate;

    #[Validate('required')]
    public $customerTo;

    #[Validate('required')]
    public $top;

    #[Validate('required')]
    public $fakturPajak;

    #[Validate('required')]
    public $notaFisik;

    public $invoiceGenerated;

    public function generateInvoiceNumber($supplierCode)
    {
        if ($supplierCode) {
            $lastInvoice = DB::table('invoice_non_header')
                ->select(['invoiceNon'])
                ->where('invoiceNon', 'like', '%' . $supplierCode . '%')
                ->orderBy('created_at', 'desc')
                ->first();

            $now = date('Ym');


            if (isset($lastInvoice->invoiceNon)) {
                $extractLastNumber = explode('-', $lastInvoice->invoiceNon);

                $lastNumber = intval($extractLastNumber[2]);
                $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);

                $this->invoiceGenerated = "$supplierCode-$now-$newNumber";
            } else {
                $this->invoiceGenerated = "$supplierCode-$now-0001";
            }
        } else {
            $this->invoiceGenerated = "";
        }
    }

    public function updatedSupplier()
    {
        $this->generateInvoiceNumber($this->supplier);
    }

    public function save()
    {
        $this->validate();

        // TOP
        $billingDocumentDate = Carbon::parse($this->billingDocumentDate);
        $top = $billingDocumentDate->addDays($this->top)->toDateString();

        // INVOICE
        $invoiceNon = $this->invoiceGenerated;

        DB::table('invoice_non_header')
            ->insert([
                'invoiceNon'            => $invoiceNon,
                'supplierCode'          => $this->supplier,
                'SPB'                   => '',
                'customerTo'            => $this->customerTo,
                'customerName'          => 'PT. KUMALA CENTRAL PARTINDO',
                'kdGudang'              => ($this->customerTo == 'KCP01001') ? 'GD1' : 'GD2',
                'billingDocumentDate'   => $this->billingDocumentDate,
                'tanggalCetakFaktur'    => $this->billingDocumentDate,
                'tanggalJatuhTempo'     => $top,
                'created_by'            => Auth::user()->username,
                'created_at'            => now(),
                'updated_at'            => now(),
                'status'                => 'KCP',
                'flag_selesai'          => 'N',
                'notaFisik'             => $this->notaFisik,
                'fakturPajak'           => $this->fakturPajak
            ]);

        session()->flash('status', "Data Non AOP dengan invoice: $invoiceNon berhasil ditambahkan.");

        $this->redirect('/non-aop');
    }

    public function render()
    {
        $suppliers = DB::table('master_supplier')->get();

        return view('livewire.create-non-aop', compact(
            'suppliers'
        ));
    }
}
