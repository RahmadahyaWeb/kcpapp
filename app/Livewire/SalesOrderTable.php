<?php

namespace App\Livewire;

use App\Models\KcpInformation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class SalesOrderTable extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $kcpInformation;
    public $token;

    public $noSo = '';
    public $noInv = '';
    public $status = '';

    public function mount()
    {
        $this->initializeKcpInformation();
    }

    /**
     * Initialize KcpInformation model and authenticate to retrieve API token.
     */
    private function initializeKcpInformation()
    {
        $this->kcpInformation = new KcpInformation;

        try {
            $conn = $this->kcpInformation->login();

            if ($conn) {
                $this->token = $conn['token'];
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to connect to KCP API.');
        }
    }

    /**
     * Synchronize data from API to the local database.
     */
    public function synchronization()
    {
        try {
            $invoices = $this->fetchInvoicesFromApi();

            if (!$this->validateInvoices($invoices)) {
                session()->flash('error', 'No valid invoices found in API response.');
                return;
            }

            $successCount = $this->saveInvoicesToDatabase($invoices['data']);

            $this->setSynchronizationStatus($successCount);
        } catch (\Exception $e) {
            session()->flash('error', 'Error during synchronization: ' . $e->getMessage());
        }
    }

    /**
     * Fetch invoices from the KCP API.
     */
    private function fetchInvoicesFromApi()
    {
        return $this->kcpInformation->getInvoices($this->token);
    }

    /**
     * Validate the API response for invoices.
     */
    private function validateInvoices($invoices)
    {
        return is_array($invoices) && isset($invoices['data']) && !empty($invoices['data']);
    }

    /**
     * Save fetched invoices to the database.
     */
    private function saveInvoicesToDatabase(array $invoices)
    {
        $successCount = 0;

        DB::beginTransaction();
        try {
            foreach ($invoices as $invoice) {
                if ($this->isInvoiceExists($invoice['noinv'])) {
                    continue;
                }

                $this->insertInvoice($invoice);
                $successCount++;
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $successCount;
    }

    /**
     * Check if an invoice already exists in the database.
     */
    private function isInvoiceExists($noinv)
    {
        return DB::table('invoice_header')->where('noinv', $noinv)->exists();
    }

    /**
     * Insert a single invoice into the database.
     */
    private function insertInvoice(array $invoice)
    {
        DB::table('invoice_header')->insert([
            'noinv'               => $invoice['noinv'] ?? null,
            'area_inv'            => $invoice['area_inv'] ?? null,
            'noso'                => $invoice['noso'] ?? null,
            'kd_outlet'           => $invoice['kd_outlet'] ?? null,
            'nm_outlet'           => $invoice['nm_outlet'] ?? null,
            'amount_dpp'          => $invoice['amount_dpp'] ?? 0,
            'amount_ppn'          => $invoice['amount_ppn'] ?? 0,
            'amount'              => $invoice['amount'] ?? 0,
            'amount_disc'         => $invoice['amount_disc'] ?? 0,
            'amount_dpp_disc'     => $invoice['amount_dpp_disc'] ?? 0,
            'amount_ppn_disc'     => $invoice['amount_ppn_disc'] ?? 0,
            'amount_total'        => $invoice['amount_total'] ?? 0,
            'user_sales'          => $invoice['user_sales'] ?? null,
            'tgl_jatuh_tempo'     => $invoice['tgl_jth_tempo'] ?? null,
            'crea_date'           => $invoice['crea_date'],
            'created_at'          => now(),
            'created_by'          => Auth::user()->username,
            'status'              => 'KCP',
        ]);
    }

    /**
     * Set flash message for synchronization status.
     */
    private function setSynchronizationStatus($successCount)
    {
        if ($successCount > 0) {
            session()->flash('status', "$successCount data berhasil disinkronisasi.");
        } else {
            session()->flash('status', "Tidak ada data yang disinkronisasi.");
        }
    }

    public function render()
    {
        $invoices = $this->getFilteredInvoices();
        return view('livewire.sales-order-table', compact('invoices'));
    }

    /**
     * Retrieve filtered invoices for the table.
     */
    private function getFilteredInvoices()
    {
        return DB::table('invoice_header')
            ->where('noso', 'like', '%' . $this->noSo . '%')
            ->where('noinv', 'like', '%' . $this->noInv . '%')
            ->where('status', 'like', '%' . $this->status . '%')
            ->orderBy('crea_date', 'desc')
            ->paginate(20);
    }
}
