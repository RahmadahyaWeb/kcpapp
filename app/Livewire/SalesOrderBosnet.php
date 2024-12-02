<?php

namespace App\Livewire;

use App\Models\KcpInformation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class SalesOrderBosnet extends Component
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

            dd($invoices);

            if (!$this->validateInvoices($invoices)) {
                session()->flash('error', 'No valid invoices found in API response.');
                return;
            }

            $successCount = $this->saveInvoicesToDatabase($invoices);

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
        return DB::connection('kcpinformation')
            ->table('trns_inv_header as header')
            ->join('trns_inv_details as details', 'header.noinv', '=', 'details.noinv')
            ->select([
                'header.noinv',
                'header.noso',
                DB::raw('round(sum(details.nominal_total)) as nominal_total_ppn')
            ])
            ->where('header.status', 'C')
            ->where('header.flag_batal', 'N')
            ->whereDate('header.crea_date', '>=', Carbon::now()->startOfMonth()->toDateString())
            ->groupBy('header.noinv')
            ->get();
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
        return DB::table('invoice_bosnet')->where('noinv', $noinv)->exists();
    }

    /**
     * Insert a single invoice into the database.
     */
    private function insertInvoice(array $invoice)
    {
        DB::table('invoice_bosnet')->insert([
            'noinv'               => $invoice['noinv'] ?? null,
            'noso'                => $invoice['noso'] ?? null,
            'amount_total'        => $invoice['amount_total'] ?? 0,
            'status_bosnet'       => 'KCP',
            'flag_print'          => 'N',
        ]);
    }

    /**
     * Set flash message for synchronization status.
     */
    private function setSynchronizationStatus($successCount)
    {
        if ($successCount > 0) {
            session()->flash('success', "$successCount data berhasil disinkronisasi.");
        } else {
            session()->flash('success', "Tidak ada data yang disinkronisasi.");
        }
    }

    public function render()
    {
        $invoices = $this->getFilteredInvoices();
        return view('livewire.sales-order-bosnet', compact('invoices'));
    }

    /**
     * Retrieve filtered invoices for the table.
     */
    private function getFilteredInvoices()
    {
        return DB::table('invoice_bosnet')
            ->where('noso', 'like', '%' . $this->noSo . '%')
            ->where('noinv', 'like', '%' . $this->noInv . '%')
            ->where('status_bosnet', 'like', '%' . $this->status . '%')
            ->paginate(20);
    }
}
