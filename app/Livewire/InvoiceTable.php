<?php

namespace App\Livewire;

use App\Models\KcpInformation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class InvoiceTable extends Component
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
            ->where('header.status', 'O')
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
        $so_belum_invoice = DB::connection('kcpinformation')
            ->table('trns_so_header as header')
            ->join('trns_so_details as details', 'header.noso', '=', 'details.noso')
            ->where('header.status', 'C')
            ->where('header.flag_selesai', 'Y')
            ->where('header.flag_cetak_gudang', 'Y')
            ->where('header.flag_vald_gudang', 'Y')
            ->where('header.flag_packingsheet', 'Y')
            ->where('header.flag_invoice', 'N')
            ->where('header.flag_reject', 'N')
            ->whereIn('header.no_packingsheet', function ($query) {
                $query->select('nops')
                    ->from('trns_packingsheet_header')
                    ->where('status', 'C');
            })
            ->groupBy('header.noso', 'header.area_so', 'header.kd_outlet', 'header.nm_outlet', 'header.user_sales')
            ->select(
                'header.noso',
                'header.area_so',
                'header.kd_outlet',
                'header.nm_outlet',
                DB::raw('SUM(details.nominal_total_gudang) as nominal_total'),
                'header.user_sales'
            )
            ->get();

        $invoices = DB::table('invoice_bosnet')
            ->where('noso', 'like', '%' . $this->noSo . '%')
            ->where('noinv', 'like', '%' . $this->noInv . '%')
            ->where('status_bosnet', 'like', '%' . $this->status . '%')
            ->get();

        return view('livewire.invoice-table', compact(
            'invoices',
            'so_belum_invoice'
        ));
    }
}
