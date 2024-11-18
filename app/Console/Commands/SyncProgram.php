<?php

namespace App\Console\Commands;

use App\Models\KcpInformation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncProgram extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-program';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Program';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $kcpInformation = new KcpInformation;

        $login = $kcpInformation->login();

        if (!$login) {
            Log::error('Failed to retrieve invoices from API.');
        }

        $items = $kcpInformation->getBonusHeader($login['token']);

        DB::beginTransaction();

        try {
            foreach ($items['data'] as $item) {
                $existing = DB::table('bonus_detail')
                    ->where('id', $item['id'])
                    ->exists();

                if (!$existing) {
                    DB::table('bonus_detail')
                        ->insert([
                            'id'                        => $item['id'],
                            'no_program'                => $item['no_program'],
                            'flag_pengajuan_manual'     => $item['flag_pengajuan_manual'],
                            'flag_transfer'             => $item['flag_transfer'],
                            'nm_program'                => $item['nm_program'],
                            'kd_outlet'                 => $item['kd_outlet'],
                            'nm_outlet'                 => $item['nm_outlet'],
                            'nominal'                   => $item['nominal'],
                            'nominal_pph'               => $item['nominal_pph'],
                            'nominal_materai'           => $item['nominal_materai'],
                            'nominal_total'             => $item['nominal_total'],
                            'flag_kwitansi'             => $item['flag_kwitansi'],
                            'flag_kwitansi_date'        => $item['flag_kwitansi_date'],
                            'flag_kwitansi_by'          => $item['flag_kwitansi_by'],
                            'flag_tampilkan'            => $item['flag_tampilkan'],
                            'flag_trm_kwitansi'         => $item['flag_trm_kwitansi'],
                            'flag_trm_kwitansi_date'    => $item['flag_trm_kwitansi_date'],
                            'flag_trm_kwitansi_by'      => $item['flag_trm_kwitansi_by'],
                            'reff_jurnal'               => $item['reff_jurnal'],
                            'reff_jurnal2'              => $item['reff_jurnal2'],
                            'status'                    => $item['status'],
                            'crea_date'                 => $item['crea_date'],
                            'crea_by'                   => $item['crea_by'],
                            'modi_date'                 => $item['modi_date'],
                            'modi_by'                   => $item['modi_by'],
                        ]);
                }
            }

            DB::commit();

            Log::info("Data program berhasil disinkronisasi.");

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error during synchronization: ' . $e->getMessage());
        }
    }
}
