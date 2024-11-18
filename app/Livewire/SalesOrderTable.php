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

    public $noSo;
    public $noInv;
    public $status;

    public function mount()
    {
        $this->kcpInformation = new KcpInformation;

        $conn = $this->kcpInformation->login();

        if ($conn) {
            $this->token = $conn['token'];
        }
    }

    public function synchronization()
    {
        try {
            $invoices = $this->kcpInformation->getInvoices($this->token);

            if (!is_array($invoices) || !isset($invoices['data']) || empty($invoices['data'])) {
                session()->flash('error', 'Failed to retrieve invoices from API.');
                return;
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Error fetching invoices from API.');
            return;
        }

        $successCount = 0;

        try {
            DB::beginTransaction();

            foreach ($invoices['data'] as $invoice) {
                $data = [
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
                ];

                $existingInvoice = DB::table('invoice_header')
                    ->where('noinv', $invoice['noinv'])
                    ->first();

                if ($existingInvoice) {
                    continue;
                }

                DB::table('invoice_header')->insert($data);

                $successCount++;
            }

            DB::commit();

            if ($successCount > 0) {
                session()->flash('status', "$successCount data berhasil disinkronisasi.");
            } else {
                session()->flash('status', "Tidak ada data yang disinkronisasi.");
            }
        } catch (\Exception $e) {
            DB::rollBack();

            session()->flash('error', 'Error during synchronization');
        }
    }

    public function render()
    {
        $invoices = DB::table('invoice_header')
            ->where('noso', 'like', '%' . $this->noSo . '%')
            ->where('noinv', 'like', '%' . $this->noInv . '%')
            ->where('status', 'like', '%' . $this->status . '%')
            ->orderBy('crea_date', 'desc')
            ->paginate(20);

        return view('livewire.sales-order-table', compact('invoices'));
    }
}
