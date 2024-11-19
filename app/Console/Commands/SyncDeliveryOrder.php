<?php

namespace App\Console\Commands;

use App\Models\KcpInformation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncDeliveryOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-delivery-order';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Delivery Order';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $kcpInformation = new KcpInformation;

        $login = $kcpInformation->login();

        if (!$login) {
            Log::error('Failed to retrieve delivery orders from API.');
        }

        $items = $kcpInformation->getLkh($login['token']);

        $successCount = 0;

        try {
            DB::beginTransaction();

            foreach ($items['data'] as $item) {
                $data = [
                    'no_lkh'        => $item['no_lkh'],
                    'noso'          => $item['noso'],
                    'noinv'         => $item['noinv'],
                    'sync_by'       => "system",
                    'sendToBosnet'  => now(),
                    'crea_date'     => $item['crea_date']
                ];

                // Cek apakah noso sudah ada di database
                $existingItem = DB::table('trns_do_invoice')
                    ->where('noso', $item['noso'])
                    ->first();

                // Jika data sudah ada, lanjutkan ke data berikutnya
                if ($existingItem) {
                    continue; // Skip jika data sudah ada
                }

                // Jika tidak ada, insert data ke database
                DB::table('trns_do_invoice')->insert($data);

                // Increment jumlah data yang berhasil disinkronisasi
                $successCount++;
            }

            // Commit transaksi jika semua data berhasil diproses
            DB::commit();

            // Menulis log jika ada data yang berhasil disinkronisasi
            if ($successCount > 0) {
                Log::info("$successCount data delivery order berhasil disinkronisasi.");
            } else {
                Log::info("Tidak ada data delivery order yang disinkronisasi.");
            }
        } catch (\Exception $e) {
            // Rollback transaksi jika terjadi error
            DB::rollBack();

            // Menulis log untuk error yang terjadi
            Log::error('Error during synchronization (DO): ' . $e->getMessage());
        }
    }
}
