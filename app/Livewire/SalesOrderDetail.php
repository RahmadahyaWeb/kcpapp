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

    public $nama_program;

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
        try {
            // Validasi apakah API pengiriman berhasil
            $isSent = $this->sendToBosnetApi();

            if (!$isSent) {
                throw new \Exception('Gagal mengirim data ke BOSNET.');
            }

            // Validasi update status di invoice_header
            $updated = DB::table('invoice_header')
                ->where('noinv', $this->invoice)
                ->update([
                    'status'        => 'BOSNET',
                    'sendToBosnet'  => now()
                ]);

            if (!$updated) {
                throw new \Exception('Gagal memperbarui status invoice.');
            }

            // Set pesan sukses
            session()->flash('success', "Data SO berhasil dikirim!");
        } catch (\Exception $e) {
            // Menangkap error dan mengembalikan pesan
            session()->flash('error', $e->getMessage());
            return back();
        }
    }

    public function sendToBosnetApi()
    {
        $header = $this->header;

        // Calculate Payment Term ID
        $paymentTermId = Carbon::parse($header->crea_date)
            ->diffInDays(Carbon::parse($header->tgl_jatuh_tempo));

        // Initialize totals
        $decDPPTotal = 0;
        $decTaxTotal = 0;

        // Generate items for the invoice
        $items = $this->generateInvoiceItems($decDPPTotal, $decTaxTotal);

        // If there's a nominal supplementary program, add it to items
        if ($this->nominalSuppProgram) {
            $this->addSupplementaryProgramItem($items, $decDPPTotal, $decTaxTotal);
        }

        // Prepare the data to be sent
        $dataToSent = $this->prepareDataToSend($header, $paymentTermId, $decDPPTotal, $decTaxTotal, $items);

        return true;
    }

    private function generateInvoiceItems(&$decDPPTotal, &$decTaxTotal)
    {
        $items = [];
        $invoiceItems = $this->getInvoice($this->invoice);

        foreach ($invoiceItems as $value) {
            $item = $this->generateInvoiceItem($value, $decDPPTotal, $decTaxTotal);
            $items[] = $item;
        }

        return $items;
    }

    private function generateInvoiceItem($value, &$decDPPTotal, &$decTaxTotal)
    {
        $decTax = ((($value['nominal_total'] / $value['qty']) * $value['qty']) / 1.11) * 0.11;
        $decAmount = ($value['nominal_total'] / $value['qty']) * $value['qty'];
        $decDPP = (($value['nominal_total'] / $value['qty']) * $value['qty']) / 1.11;
        $decPrice = $value['nominal_total'] / $value['qty'];

        // Update totals
        $decDPPTotal += $decDPP;
        $decTaxTotal += $decTax;

        return [
            'szOrderItemTypeId'  => "JUAL",
            'szProductId'        => $value['part_no'],
            'decDiscProcent'     => 0,
            'decQty'             => $value['qty'],
            'szUomId'            => "PCS",
            'decPrice'           => $decPrice,
            'decDiscount'        => 0,
            'bTaxable'           => true,
            'decTax'             => $decTax,
            'decAmount'          => $decAmount,
            'decDPP'             => $decDPP,
            'szPaymentType'      => "NON",
            'deliveryList'       => [
                'dtmDelivery'   => date('Y-m-d H:i:s', strtotime($this->header->crea_date)),
                'szCustId'      => $this->header->kd_outlet,
                'decQty'        => $value['qty'],
                'szFromWpId'    => 'KCP01001',
            ],
        ];
    }

    private function addSupplementaryProgramItem(&$items, &$decDPPTotal, &$decTaxTotal)
    {
        $item = [
            'szOrderItemTypeId'  => "DISKON",
            'szProductId'        => "",
            'decDiscProcent'     => 0,
            'decQty'             => 0,
            'szUomId'            => "",
            'decPrice'           => 0,
            'decDiscount'        => $this->nominalSuppProgram,
            'bTaxable'           => true,
            'decTax'             => - ($this->nominalSuppProgram - ($this->nominalSuppProgram / 1.11)),
            'decAmount'          => 0,
            'decDPP'             => - ($this->nominalSuppProgram / 1.11),
            'szPaymentType'      => "TDB",
            'deliveryList'       => [],
            'bonusSourceList'    => [],
        ];

        // Update totals
        $decDPPTotal += $item['decDPP'];
        $decTaxTotal += $item['decTax'];

        $items[] = $item;
    }

    private function prepareDataToSend($header, $paymentTermId, $decDPPTotal, $decTaxTotal, $items)
    {
        return [
            'appId'             => "BDI.KCP",
            'szFSoId'           => $header->noso,
            'szOrderTypeId'     => 'JUAL',
            'dtmOrder'          => date('Y-m-d H:i:s', strtotime($header->crea_date)),
            'szCustId'          => $header->kd_outlet,
            'dlvAddress_J'      => $this->prepareDeliveryAddress($header),
            'decAmount'         => $decDPPTotal,
            'decTax'            => $decTaxTotal,
            'szShipToId'        => $header->kd_outlet,
            'szStatus'          => "OPE",
            'szCcyId'           => "IDR",
            'szCcyRateId'       => "BI",
            'szSalesId'         => $header->user_sales,
            'docStatus'         => ['bApplied' => false],
            'szPaymentTermId'   => $paymentTermId . " HARI",
            'szRemark'          => '',
            'dtmExpiration'     => date('Y-m-d H:i:s', strtotime('+7 days', strtotime($header->crea_date))),
            'itemList'          => $items
        ];
    }

    private function prepareDeliveryAddress($header)
    {
        $addressDetail = $this->kcpInformation->getAddress($this->token, $header->kd_outlet);
        $addressDetail = $addressDetail['data'];

        return [
            'szContactPerson'   => $addressDetail['nm_outlet'],
            'szAddress_1'       => $addressDetail['almt_outlet'],
            'szAddress_2'       => $addressDetail['almt_outlet'],
            'szDistrict'        => $addressDetail['nm_area'],
            'szCity'            => $addressDetail['nm_area'],
            'szZipCode'         => '',
            'szState'           => $addressDetail['provinsi'],
            'szCountry'         => 'Indonesia',
            'szPhoneNo_1'       => $addressDetail['tlpn'] ? $addressDetail['tlpn'] : 0,
        ];
    }

    public function saveProgram()
    {
        $this->validate([
            'nama_program'      => 'required',
            'nominal_program'   => 'required'
        ]);

        if ($this->nominal_program > $this->nominal_program_display) {
            $this->addError('nominal_program', 'Nominal tidak boleh melebihi ketentuan.');
            return; // Keluar jika ada error
        }

        try {
            DB::beginTransaction();

            // Validasi nama program
            $nama_program = DB::table('bonus_detail')
                ->where('no_program', $this->nama_program)
                ->value('nm_program');

            if (!$nama_program) {
                throw new \Exception('Program tidak ditemukan.');
            }

            // Insert sales_order_program
            $inserted = DB::table('sales_order_program')->insert([
                'no_program'       => $this->nama_program,
                'noinv'            => $this->invoice,
                'nama_program'     => $nama_program,
                'nominal_program'  => $this->nominal_program,
            ]);

            if (!$inserted) {
                throw new \Exception('Gagal menyimpan program ke sales_order_program.');
            }

            // Update amount_total pada invoice_header
            $updatedInvoice = DB::table('invoice_header')
                ->where('noinv', $this->invoice)
                ->decrement('amount_total', $this->nominal_program);

            if (!$updatedInvoice) {
                throw new \Exception('Gagal memperbarui amount_total pada invoice_header.');
            }

            // Kurangi nominal pada bonus_detail
            $updatedBonus = DB::table('bonus_detail')
                ->where('no_program', $this->nama_program)
                ->where('kd_outlet', $this->kd_outlet)
                ->decrement('nominal', $this->nominal_program);

            if (!$updatedBonus) {
                throw new \Exception('Bonus detail tidak ditemukan atau gagal diperbarui.');
            }

            DB::commit();

            // Reset input
            $this->reset('nama_program', 'nominal_program', 'search_program', 'nominal_program_display');

            return back()->with('success', 'Program berhasil ditambahkan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    public function deleteProgram($id)
    {
        try {
            DB::beginTransaction();

            // Ambil data yang diperlukan
            $program = DB::table('sales_order_program')
                ->where('id', $id)
                ->select(['nominal_program', 'no_program'])
                ->first();

            if (!$program) {
                throw new \Exception('Program tidak ditemukan.');
            }

            // Update amount_total di invoice_header
            $updatedInvoice = DB::table('invoice_header')
                ->where('noinv', $this->invoice)
                ->increment('amount_total', $program->nominal_program);

            if (!$updatedInvoice) {
                throw new \Exception('Gagal memperbarui amount_total pada invoice_header.');
            }

            // Update nominal di bonus_detail
            $updatedBonus = DB::table('bonus_detail')
                ->where('no_program', $program->no_program)
                ->where('kd_outlet', $this->kd_outlet)
                ->increment('nominal', $program->nominal_program);

            if (!$updatedBonus) {
                throw new \Exception('Bonus detail tidak ditemukan atau gagal diperbarui.');
            }

            // Hapus data dari sales_order_program
            $deletedProgram = DB::table('sales_order_program')
                ->where('id', $id)
                ->delete();

            if (!$deletedProgram) {
                throw new \Exception('Gagal menghapus program dari sales_order_program.');
            }

            DB::commit();

            // Reset input
            $this->reset('nama_program', 'nominal_program', 'search_program', 'nominal_program_display');

            return back()->with('success', 'Program berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    public function updatedNamaProgram()
    {
        $this->nominal_program_display = $this->nama_program
            ? (int) DB::table('bonus_detail')
                ->where('kd_outlet', $this->kd_outlet)
                ->where('no_program', $this->nama_program)
                ->value('nominal') ?? 0
            : 0;
    }

    public function render()
    {
        if (!$this->token) {
            abort(500);
        }

        $header = DB::table('invoice_header')
            ->where('noinv', $this->invoice)
            ->first();

        if ($header) {
            $this->kd_outlet = $header->kd_outlet;
            $this->header = $header;
        } else {
            abort(404, 'Header invoice tidak ditemukan.');
        }

        $this->nominalSuppProgram = DB::table('sales_order_program')
            ->where('noinv', $this->invoice)
            ->sum('nominal_program');

        return view('livewire.sales-order-detail', [
            'invoices' => $this->getInvoice($this->invoice),
            'programs' => DB::table('sales_order_program')
                ->where('noinv', $this->invoice)
                ->get(),
            'header' => $this->header,
            'bonus' => DB::table('bonus_detail')
                ->where('nm_program', 'like', '%' . $this->search_program . '%')
                ->where('kd_outlet', $this->kd_outlet)
                ->get(),
            'nominalSuppProgram' => $this->nominalSuppProgram,
        ]);
    }
}
