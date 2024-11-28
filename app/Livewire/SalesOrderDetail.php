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
class SalesOrderDetail extends Component
{
    public $token;
    public $kcpInformation;
    public $invoice;
    public $header = [];
    public $search_program;
    public $kd_outlet;
    public $nominal_program_display = 0;
    public $nama_program;
    public $nominal_program;

    /**
     * Initialize the component with an invoice.
     * 
     * @param string $invoice Invoice number
     */
    public function mount($invoice)
    {
        $this->invoice = $invoice;
        $this->kcpInformation = new KcpInformation;
        $this->initializeConnection();
    }

    /**
     * Initialize the connection to retrieve the authentication token.
     */
    private function initializeConnection()
    {
        $conn = $this->kcpInformation->login();

        if ($conn) {
            $this->token = $conn['token'];
        }
    }

    /**
     * Fetch the invoice details from the external API.
     * 
     * @param string $invoice Invoice number
     * @return Collection Invoice details
     */
    public function getInvoice($invoice)
    {
        $invoiceData = $this->kcpInformation->getInvoice($this->token, $invoice);

        if (isset($invoiceData['status']) && $invoiceData['status'] == 404) {
            abort(404);
        }

        return collect($invoiceData['data'] ?? []);
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

            // Insert into sales_order_program
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
     * Insert program details into the sales_order_program table.
     * 
     * @param string $nama_program Name of the program
     */
    private function insertSalesOrderProgram($nama_program)
    {
        $inserted = DB::table('sales_order_program')->insert([
            'no_program'       => $this->nama_program,
            'noinv'            => $this->invoice,
            'nama_program'     => $nama_program,
            'nominal_program'  => $this->nominal_program,
        ]);

        if (!$inserted) {
            throw new \Exception('Gagal menyimpan program ke sales_order_program.');
        }
    }

    /**
     * Update the total amount on the invoice header.
     */
    private function updateInvoiceAmount()
    {
        $updatedInvoice = DB::table('invoice_header')
            ->where('noinv', $this->invoice)
            ->decrement('amount_total', $this->nominal_program);

        if (!$updatedInvoice) {
            throw new \Exception('Gagal memperbarui amount_total pada invoice_header.');
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
            $program = DB::table('sales_order_program')
                ->where('id', $id)
                ->select(['nominal_program', 'no_program'])
                ->first();

            if (!$program) {
                throw new \Exception('Program tidak ditemukan.');
            }

            // Revert updates to invoice and bonus details
            $this->revertInvoiceAndBonus($program);

            // Delete the program from sales_order_program
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
        // Update the amount_total in invoice_header
        DB::table('invoice_header')
            ->where('noinv', $this->invoice)
            ->increment('amount_total', $program->nominal_program);

        // Update the bonus_detail
        DB::table('bonus_detail')
            ->where('no_program', $program->no_program)
            ->where('kd_outlet', $this->kd_outlet)
            ->increment('nominal', $program->nominal_program);
    }

    /**
     * Delete the program record from the sales_order_program table.
     * 
     * @param int $id Program ID
     */
    private function deleteSalesOrderProgram($id)
    {
        $deletedProgram = DB::table('sales_order_program')
            ->where('id', $id)
            ->delete();

        if (!$deletedProgram) {
            throw new \Exception('Gagal menghapus program dari sales_order_program.');
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
        if (!$this->token) {
            abort(500);
        }

        // Fetch the invoice header
        $this->loadInvoiceHeader();

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
            'nominalSuppProgram' => DB::table('sales_order_program')
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
        $header = DB::table('invoice_header')
            ->where('noinv', $this->invoice)
            ->first();

        if ($header) {
            $this->kd_outlet = $header->kd_outlet;
            $this->header = $header;
        } else {
            abort(404, 'Header invoice tidak ditemukan.');
        }
    }
}
