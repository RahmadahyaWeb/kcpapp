<?php

namespace App\Livewire;

use App\Http\Controllers\API\SalesOrderController;
use App\Models\KcpInformation;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/**
 * Component for managing the details of a sales order.
 */
class InvoiceDetailPrint extends Component
{
    public $target = 'sendToBosnet, saveProgram, deleteProgram';

    public $invoice;
    public $header;
    public $search_program;
    public $kd_outlet;
    public $nominal_program_display = 0;
    public $nama_program;
    public $nominal_program;
    public $details;
    public $bonus_toko;
    public $nominal_total;

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

        if ($this->nominal_program > (int) str_replace('.', '', $this->nominal_program_display)) {
            $this->addError('nominal_program', 'Nominal tidak boleh melebihi ketentuan.');
            return;
        }

        try {
            $kcpInformation = DB::connection('kcpinformation');
            $kcpApplication = DB::connection('mysql');

            $kcpInformation->beginTransaction();
            $kcpApplication->beginTransaction();

            // Validate program existence
            $program = $kcpInformation->table('trns_ach_toko_bonus')
                ->where('no_program', $this->nama_program)
                ->first();

            if (!$program) {
                throw new \Exception('Program tidak ditemukan.');
            }

            // Log history
            DB::table('history_bonus_invoice')->insert([
                'no_program'                => $this->nama_program,
                'nm_program'                => $program->nm_program,
                'nominal_program'           => $this->nominal_program,
                'nominal_program_before'    => $program->nominal,
                'nominal_program_after'     => $program->nominal - $this->nominal_program,
                'noinv'                     => $this->invoice,
                'nominal_invoice_before'    => $this->header->amount_total,
                'nominal_invoice_after'     => $this->header->amount_total - $this->nominal_program,
                'crea_date'                 => now(),
                'crea_by'                   => Auth::user()->username
            ]);

            // Update invoice header
            DB::connection('kcpinformation')->table('trns_inv_header')
                ->where('noinv', $this->invoice)
                ->decrement('amount_total', $this->nominal_program);

            // Update bonus
            DB::connection('kcpinformation')->table('trns_ach_toko_bonus')
                ->where('no_program', $this->nama_program)
                ->where('kd_outlet', $this->kd_outlet)
                ->decrement('nominal', $this->nominal_program);

            $kcpInformation->commit();
            $kcpApplication->commit();

            // Reset the input fields
            $this->reset('nama_program', 'nominal_program', 'search_program', 'nominal_program_display');

            return back()->with('success', 'Program berhasil ditambahkan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
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
            $kcpInformation = DB::connection('kcpinformation');
            $kcpApplication = DB::connection('mysql');

            $kcpInformation->beginTransaction();
            $kcpApplication->beginTransaction();

            // Fetch the program details
            $program = DB::table('history_bonus_invoice')
                ->where('id', $id)
                ->select(['nominal_program', 'no_program'])
                ->first();

            if (!$program) {
                throw new \Exception('Program tidak ditemukan.');
            }

            // Revert updates to invoice and bonus
            DB::connection('kcpinformation')->table('trns_inv_header')
                ->where('noinv', $this->invoice)
                ->increment('amount_total', $program->nominal_program);

            DB::connection('kcpinformation')->table('trns_ach_toko_bonus')
                ->where('no_program', $program->no_program)
                ->where('kd_outlet', $this->kd_outlet)
                ->increment('nominal', $program->nominal_program);

            // Delete the program from history_bonus_invoice
            DB::table('history_bonus_invoice')
                ->where('id', $id)
                ->delete();

            $kcpInformation->commit();
            $kcpApplication->commit();

            // Reset the input fields
            $this->reset('nama_program', 'nominal_program', 'search_program', 'nominal_program_display');

            return back()->with('success', 'Program berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Update the nominal display value when the program name is changed.
     */
    public function updatedNamaProgram()
    {
        $nominal = $this->nama_program
            ? (int) DB::connection('kcpinformation')->table('trns_ach_toko_bonus')
                ->where('kd_outlet', $this->kd_outlet)
                ->where('no_program', $this->nama_program)
                ->value('nominal') ?? 0
            : 0;

        // Format sebagai Rupiah
        $this->nominal_program_display = number_format($nominal, 0, ',', '.');
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

            session()->flash('success', "Data SO berhasil diteruskan ke BOSNET");

            $this->redirect('/invoice');
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
        $this->header = DB::connection('kcpinformation')
            ->table('trns_inv_header')
            ->where('noinv', $this->invoice)
            ->first();

        if ($this->header == null) {
            abort(404);
        }

        $this->details = DB::connection('kcpinformation')
            ->table('trns_inv_details')
            ->where('noinv', $this->invoice)
            ->get();

        $this->bonus_toko = DB::connection('kcpinformation')
            ->table('trns_ach_toko_bonus')
            ->where('kd_outlet', $this->header->kd_outlet)
            ->whereYear('crea_date', 2024)
            ->get();

        $this->kd_outlet = $this->header->kd_outlet;

        return view('livewire.invoice-detail-print', [
            'invoices' => $this->details,
            'programs' => DB::table('history_bonus_invoice')
                ->where('noinv', $this->invoice)
                ->get(),
            'header' => $this->header,
            'bonus' => $this->bonus_toko,
            'nominalSuppProgram' => DB::table('history_bonus_invoice')
                ->where('noinv', $this->invoice)
                ->sum('nominal_program'),
        ]);
    }
}
