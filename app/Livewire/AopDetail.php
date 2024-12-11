<?php

namespace App\Livewire;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Validate;
use Livewire\Component;

class AopDetail extends Component
{
    public $target = 'updateFlag, saveFakturPajak, saveProgram, destroyProgram';

    public $fakturPajak;
    public $editingFakturPajak;

    public $classProgram;
    public $styleProgram;

    public $classFakturPajak;
    public $styleFakturPajak;

    public $invoiceAop;
    public $totalAmount;
    public $totalQty;

    #[Validate('required')]
    public $potonganProgram = '';

    #[Validate('required')]
    public $keteranganProgram = '';

    public $customerTo;
    public $tanggalInvoice;

    public function mount($invoiceAop)
    {
        $this->invoiceAop = $invoiceAop;
    }

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
        try {
            DB::table('invoice_aop_header')
                ->where('invoiceAop', $invoiceAop)
                ->update([
                    'flag_final'  => 'Y',
                    'final_date'  => now()
                ]);

            session()->flash('success', "Flag $invoiceAop berhasil disimpan. Silakan periksa data di list Data AOP Final.");

            $this->redirect('/pembelian/aop/upload');
        } catch (\Exception $e) {
            session()->flash('error', "Gagal update flag: " . $e->getMessage());
        }
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
