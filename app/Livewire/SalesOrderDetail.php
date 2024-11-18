<?php

namespace App\Livewire;

use App\Models\KcpInformation;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Validate;
use Livewire\Component;

class SalesOrderDetail extends Component
{
    public $token;
    public $kcpInformation;
    public $invoice;
    public $nominalSuppProgram;
    public $header = [];
    public $search_program;
    public $kd_outlet;
    public $nominal_program_display = 0;

    #[Validate('required')]
    public $nama_program;

    #[Validate('required')]
    public $nominal_program;

    public function mount($invoice)
    {
        $this->invoice = $invoice;

        $this->kcpInformation = new KcpInformation;

        $conn = $this->kcpInformation->login();

        if ($conn) {
            $this->token = $conn['token'];
        }
    }

    public function getInvoice($invoice)
    {
        $invoice = $this->kcpInformation->getInvoice($this->token, $invoice);

        if (isset($invoice['status']) && $invoice['status'] == 404) {
            $invoice = new Collection();
        } else if (isset($invoice['data']) && $invoice['data']) {
            $invoice = collect($invoice['data']);
        } else {
            $invoice = new Collection();
        }

        return $invoice;
    }

    public function sendToBosnet()
    {
        if ($this->sendToBosnetApi()) {
            DB::table('invoice_header')
                ->where('noinv', $this->invoice)
                ->update([
                    'status' => 'BOSNET'
                ]);

            session()->flash('status', "Data SO berhasil dikirim!");

            $this->redirect('/sales-order');
        }
    }

    public function sendToBosnetApi()
    {
        $header = $this->header;

        // PAYMENT TERM ID
        $billingDate = Carbon::parse($header->crea_date);
        $dueDate = Carbon::parse($header->tgl_jatuh_tempo);

        $paymentTermId = $billingDate->diffInDays($dueDate);

        // ITEMS
        $items = [];

        $invoiceItems = $this->getInvoice($this->invoice);

        foreach ($invoiceItems as $value) {
            $item = [];

            $item['szOrderItemTypeId']  = "JUAL";
            $item['szProductId']        = $value['part_no'];
            $item['decQty']             = $value['qty'];
            $item['szUomId']            = "PCS";
            $item['decPrice']           = $value['hrg_pcs'];
            $item['decDiscount']        = $value['nominal_disc'];

            $items[] = $item;
        }

        if ($this->nominalSuppProgram) {
            $item = [];

            $item['szOrderItemTypeId']  = "DISKON";
            $item['szProductId']        = "";
            $item['decQty']             = 0;
            $item['szUomId']            = "";
            $item['decPrice']           = "";
            $item['decDiscount']        = $this->nominalSuppProgram;

            $items[] = $item;
        }

        return true;

        $dataToSent = [
            'szFSoId'           => $header->noso,
            'szOrderTypeId'     => 'JUAL',
            'dtmOrder'          => date('Y-m-d H:i:s', strtotime($header->crea_date)),
            'szCustId'          => $header->kd_outlet,
            'szSalesId'         => $header->user_sales,
            'szRemark'          => '',
            'szPaymentTermId'   => $paymentTermId . " HARI",
            'szWorkplaceId'     => 'KCP01001',
            'items'             => $items
        ];

        dd($dataToSent);
    }

    public function saveProgram()
    {
        $this->validate();

        if ($this->nominal_program > $this->nominal_program_display) {
            $this->addError('nominal_program', 'Nominal tidak boleh melebihi ketentuan.');
        }

        $listOfError = $this->getErrorBag();

        if (empty($listOfError->all())) {
            $nama_program = DB::table('bonus_detail')->select(['nm_program'])->where('no_program', $this->nama_program)->first();

            DB::table('sales_order_program')
                ->insert([
                    'no_program'        => $this->nama_program,
                    'noinv'             => $this->invoice,
                    'nama_program'      => $nama_program->nm_program,
                    'nominal_program'   => $this->nominal_program
                ]);

            $header = DB::table('invoice_header')
                ->where('noinv', $this->invoice)
                ->select(['amount_total'])
                ->first();

            DB::table('invoice_header')
                ->where('noinv', $this->invoice)
                ->update([
                    'amount_total' => $header->amount_total - $this->nominal_program
                ]);

            // KURANGI NOMINAL PROGRAM
            $bonus = DB::table('bonus_detail')
                ->where('no_program', $this->nama_program)
                ->where('kd_outlet', $this->kd_outlet)
                ->first();

            DB::table('bonus_detail')
                ->where('no_program', $this->nama_program)
                ->update([
                    'nominal' => $bonus->nominal - $this->nominal_program
                ]);

            $this->reset('nama_program', 'nominal_program', 'search_program', 'nominal_program_display');
        }
    }

    public function deleteProgram($id)
    {
        $header = DB::table('invoice_header')
            ->where('noinv', $this->invoice)
            ->select(['amount_total'])
            ->first();

        $program = DB::table('sales_order_program')
            ->where('id', $id)
            ->select(['nominal_program', 'no_program'])
            ->first();

        DB::table('invoice_header')
            ->where('noinv', $this->invoice)
            ->update([
                'amount_total' => $header->amount_total + $program->nominal_program
            ]);

        // KURANGI NOMINAL PROGRAM
        $bonus = DB::table('bonus_detail')
            ->where('no_program', $program->no_program)
            ->where('kd_outlet', $this->kd_outlet)
            ->first();

        DB::table('bonus_detail')
            ->where('no_program', $program->no_program)
            ->update([
                'nominal' => $bonus->nominal + $program->nominal_program
            ]);

        $this->reset('nama_program', 'nominal_program', 'search_program', 'nominal_program_display');

        DB::table('sales_order_program')
            ->where('id', $id)
            ->delete();
    }

    public function syncProgram()
    {
        $items = $this->kcpInformation->getBonusHeader($this->token);

        // Mulai transaksi
        DB::beginTransaction();

        try {
            foreach ($items['data'] as $item) {
                $existing = DB::table('bonus_detail')
                    ->where('id', $item['id'])
                    ->exists();

                if (!$existing) {
                    DB::table('bonus_detail')
                        ->insert([
                            'id'                        => $item['id'],
                            'no_program'                => $item['no_program'],
                            'flag_pengajuan_manual'     => $item['flag_pengajuan_manual'],
                            'flag_transfer'             => $item['flag_transfer'],
                            'nm_program'                => $item['nm_program'],
                            'kd_outlet'                 => $item['kd_outlet'],
                            'nm_outlet'                 => $item['nm_outlet'],
                            'nominal'                   => $item['nominal'],
                            'nominal_pph'               => $item['nominal_pph'],
                            'nominal_materai'           => $item['nominal_materai'],
                            'nominal_total'             => $item['nominal_total'],
                            'flag_kwitansi'             => $item['flag_kwitansi'],
                            'flag_kwitansi_date'        => $item['flag_kwitansi_date'],
                            'flag_kwitansi_by'          => $item['flag_kwitansi_by'],
                            'flag_tampilkan'            => $item['flag_tampilkan'],
                            'flag_trm_kwitansi'         => $item['flag_trm_kwitansi'],
                            'flag_trm_kwitansi_date'    => $item['flag_trm_kwitansi_date'],
                            'flag_trm_kwitansi_by'      => $item['flag_trm_kwitansi_by'],
                            'reff_jurnal'               => $item['reff_jurnal'],
                            'reff_jurnal2'              => $item['reff_jurnal2'],
                            'status'                    => $item['status'],
                            'crea_date'                 => $item['crea_date'],
                            'crea_by'                   => $item['crea_by'],
                            'modi_date'                 => $item['modi_date'],
                            'modi_by'                   => $item['modi_by'],
                        ]);
                }
            }

            // Commit transaksi jika semua berhasil
            DB::commit();
        } catch (\Exception $e) {
            // Rollback transaksi jika terjadi error
            DB::rollBack();

            // Opsional: bisa melempar exception atau mengembalikan respon error
            abort(500, 'Terjadi kesalahan saat melakukan sinkronisasi data.');
        }
    }

    public function updatedNamaProgram()
    {
        if ($this->nama_program) {
            $item = DB::table('bonus_detail')
                ->where('kd_outlet', $this->kd_outlet)
                ->where('no_program', $this->nama_program)
                ->first();

            if ($item) {
                $this->nominal_program_display = (int) $item->nominal;
            }
        } else {
            $this->nominal_program_display = (int) 0;
        }
    }

    public function render()
    {
        if (!$this->token) {
            abort(500);
        }

        $this->syncProgram();

        $invoices = $this->getInvoice($this->invoice);

        $programs = DB::table('sales_order_program')
            ->where('noinv', $this->invoice)
            ->get();

        $header = DB::table('invoice_header')
            ->where('noinv', $this->invoice)
            ->first();

        $this->kd_outlet = $header->kd_outlet;

        $bonus = DB::table('bonus_detail')
            ->where('nm_program', 'like', '%' . $this->search_program . '%')
            ->where('kd_outlet', $this->kd_outlet)
            ->get();

        $nominalSuppProgram = DB::table('sales_order_program')
            ->where('noinv', $this->invoice)
            ->sum('nominal_program');

        $this->nominalSuppProgram = $nominalSuppProgram;
        $this->header = $header;

        return view('livewire.sales-order-detail', compact(
            'invoices',
            'nominalSuppProgram',
            'programs',
            'header',
            'bonus',
        ));
    }
}
