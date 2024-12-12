<?php

namespace App\Livewire;

use App\Http\Controllers\API\SalesOrderController;
use App\Models\KcpInformation;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/**
 * Component for managing the details of a sales order.
 */
class InvoiceDetailPrint extends Component
{
    public $kcpInformation;
    public $invoice;
    public $header = [];
    public $search_program;
    public $kd_outlet;
    public $nominal_program_display = 0;
    public $nama_program;
    public $nominal_program;
    public $details;
    public $sumTotalDPP;
    public $bonus_toko;

    /**
     * Initialize the component with an invoice.
     * 
     * @param string $invoice Invoice number
     */
    public function mount($invoice)
    {
        $this->invoice = $invoice;
    }

    /**
     * Save the program details to the database.
     * 
     * @return \Illuminate\Http\RedirectResponse
     */
    public function saveProgram()
    {
        $this->validate([
            'nama_program'      => 'required',
            'nominal_program'   => 'required|numeric|min:0',
        ]);

        if ($this->nominal_program > $this->nominal_program_display) {
            $this->addError('nominal_program', 'Nominal tidak boleh melebihi ketentuan.');
            return;
        }

        try {
            DB::beginTransaction();

            // Validate program existence
            $nama_program = DB::table('bonus_detail')
                ->where('no_program', $this->nama_program)
                ->value('nm_program');

            if (!$nama_program) {
                throw new \Exception('Program tidak ditemukan.');
            }

            // Insert into invoice_program
            $this->insertSalesOrderProgram($nama_program);

            // Update invoice header
            $this->updateInvoiceAmount();

            // Update bonus details
            $this->updateBonusDetails();

            DB::commit();

            // Reset the input fields
            $this->reset('nama_program', 'nominal_program', 'search_program', 'nominal_program_display');

            return back()->with('success', 'Program berhasil ditambahkan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Insert program details into the invoice_program table.
     * 
     * @param string $nama_program Name of the program
     */
    private function insertSalesOrderProgram($nama_program)
    {
        $inserted = DB::table('invoice_program')->insert([
            'no_program'       => $this->nama_program,
            'noinv'            => $this->invoice,
            'nama_program'     => $nama_program,
            'nominal_program'  => $this->nominal_program,
        ]);

        if (!$inserted) {
            throw new \Exception('Gagal menyimpan program ke invoice_program.');
        }
    }

    /**
     * Update the total amount on the invoice header.
     */
    private function updateInvoiceAmount()
    {
        $updatedInvoice = DB::table('invoice_bosnet')
            ->where('noinv', $this->invoice)
            ->decrement('amount_total', $this->nominal_program);

        if (!$updatedInvoice) {
            throw new \Exception('Gagal memperbarui amount_total pada invoice_bosnet.');
        }
    }

    /**
     * Update the bonus details after inserting a program.
     */
    private function updateBonusDetails()
    {
        $updatedBonus = DB::table('bonus_detail')
            ->where('no_program', $this->nama_program)
            ->where('kd_outlet', $this->kd_outlet)
            ->decrement('nominal', $this->nominal_program);

        if (!$updatedBonus) {
            throw new \Exception('Bonus detail tidak ditemukan atau gagal diperbarui.');
        }
    }

    /**
     * Delete a program from the sales order.
     * 
     * @param int $id Program ID
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deleteProgram($id)
    {
        try {
            DB::beginTransaction();

            // Fetch the program details
            $program = DB::table('invoice_program')
                ->where('id', $id)
                ->select(['nominal_program', 'no_program'])
                ->first();

            if (!$program) {
                throw new \Exception('Program tidak ditemukan.');
            }

            // Revert updates to invoice and bonus details
            $this->revertInvoiceAndBonus($program);

            // Delete the program from invoice_program
            $this->deleteSalesOrderProgram($id);

            DB::commit();

            // Reset the input fields
            $this->reset('nama_program', 'nominal_program', 'search_program', 'nominal_program_display');

            return back()->with('success', 'Program berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Revert changes made to the invoice and bonus details when deleting a program.
     * 
     * @param object $program Program details
     */
    private function revertInvoiceAndBonus($program)
    {
        // Update the amount_total in invoice_bosnet
        DB::table('invoice_bosnet')
            ->where('noinv', $this->invoice)
            ->increment('amount_total', $program->nominal_program);

        // Update the bonus_detail
        DB::table('bonus_detail')
            ->where('no_program', $program->no_program)
            ->where('kd_outlet', $this->kd_outlet)
            ->increment('nominal', $program->nominal_program);
    }

    /**
     * Delete the program record from the invoice_program table.
     * 
     * @param int $id Program ID
     */
    private function deleteSalesOrderProgram($id)
    {
        $deletedProgram = DB::table('invoice_program')
            ->where('id', $id)
            ->delete();

        if (!$deletedProgram) {
            throw new \Exception('Gagal menghapus program dari invoice_program.');
        }
    }

    /**
     * Update the nominal display value when the program name is changed.
     */
    public function updatedNamaProgram()
    {
        $this->nominal_program_display = $this->nama_program
            ? (int) DB::table('bonus_detail')
                ->where('kd_outlet', $this->kd_outlet)
                ->where('no_program', $this->nama_program)
                ->value('nominal') ?? 0
            : 0;
    }

    /**
     * Send the sales order to Bosnet.
     * 
     * @return void
     */
    public function sendToBosnet()
    {
        try {
            $controller = new SalesOrderController();
            $controller->sendToBosnet(new Request(['invoice' => $this->invoice]));
            session()->flash('success', "Data SO berhasil dikirim!");
            $this->redirect('/invoice/bosnet');
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    /**
     * Render the component view.
     * 
     * @return \Illuminate\View\View
     */
    public function render()
    {
        $this->loadInvoiceHeader();

        if ($this->header == null) {
            abort(404);
        }

        return view('livewire.invoice-detail-print', [
            'invoices' => $this->details,
            'programs' => DB::table('invoice_program')
                ->where('noinv', $this->invoice)
                ->get(),
            'header' => $this->header,
            'bonus' => $this->bonus_toko,
            'nominalSuppProgram' => DB::table('invoice_program')
                ->where('noinv', $this->invoice)
                ->sum('nominal_program'),
        ]);
    }

    /**
     * Load the invoice header data.
     * 
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If invoice header not found
     */
    private function loadInvoiceHeader()
    {
        $header = DB::connection('kcpinformation')
            ->table('trns_inv_header')
            ->where('noinv', $this->invoice)
            ->first();

        $details = DB::connection('kcpinformation')
            ->table('trns_inv_details')
            ->where('noinv', $this->invoice)
            ->get();

        $this->details = $details;

        $this->bonus_toko = DB::connection('kcpinformation')
            ->table('trns_ach_toko_bonus')
            ->where('kd_outlet', $header->kd_outlet)
            ->get();

        $sumTotalNominal = 0;
        $sumTotalDPP = 0;
        $sumTotalDisc = 0;

        foreach ($details as $value) {
            $sumTotalNominal = $sumTotalNominal + $value->nominal;
            $sumTotalDPP = $sumTotalDPP + $value->nominal_total;
            $sumTotalDisc = $sumTotalDisc + $value->nominal_disc;
            $nominalPPn = ($value->nominal_total / config('tax.ppn_factor')) * config('tax.ppn_percentage');
        }

        $this->sumTotalDPP = $sumTotalDPP;

        $dpp = round($sumTotalNominal) / config('tax.ppn_factor');
        $nominalPPn = round($dpp) * config('tax.ppn_percentage');
        $dppDisc = round($sumTotalDPP) / config('tax.ppn_factor');
        $nominalPPnDisc = round($dppDisc * config('tax.ppn_percentage'));

        $invoice_bosnet_exists = DB::table('invoice_bosnet')
            ->where('noinv', $this->invoice)
            ->where('noso', $header->noso)
            ->first();

        if (!$invoice_bosnet_exists && $header->status != 'C') {
            DB::table('invoice_bosnet')
                ->insert([
                    'noso'          => $header->noso,
                    'noinv'         => $header->noinv,
                    'kd_outlet'     => $header->kd_outlet,
                    'nm_outlet'     => $header->nm_outlet,
                    'amount_total'  => $sumTotalDPP,
                    'amount'        => $sumTotalNominal,
                    'amount_disc'   => $sumTotalDisc,
                    'crea_date'     => $header->crea_date,
                    'tgl_jth_tempo' => $header->tgl_jth_tempo,
                    'user_sales'    => $header->user_sales,
                ]);
        }

        $invoice_bosnet = DB::table('invoice_bosnet')
            ->where('noinv', $this->invoice)
            ->first();

        $this->header = $invoice_bosnet;
    }
}
