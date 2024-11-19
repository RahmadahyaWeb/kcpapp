<?php

namespace App\Console\Commands;

use App\Models\KcpInformation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncSalesOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-sales-order';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Sales Order';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $kcpInformation = new KcpInformation;

            $login = $kcpInformation->login();

            if (!$login) {
                Log::error('Failed to retrieve invoices from API.');
            }

            $invoices = $kcpInformation->getInvoices($login['token']);

            if (!is_array($invoices) || !isset($invoices['data']) || empty($invoices['data'])) {
                Log::error('Failed to retrieve invoices from API.');
                return;
            }
        } catch (\Exception $e) {
            Log::error('Error fetching invoices from API: ' . $e->getMessage());
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
                    'created_by'          => 'system',
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
                Log::info("$successCount data sales order berhasil disinkronisasi.");
            } else {
                Log::info("Tidak ada data sales order yang disinkronisasi.");
            }
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error during synchronization (SO): ' . $e->getMessage());
        }
    }
}
