<?php

namespace App\Livewire;

use App\Models\KcpInformation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

use function Laravel\Prompts\select;

class DeliveryOrderTable extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $noLkh;

    public function checkApiConn()
    {
        $kcpInformation = new KcpInformation;

        $login = $kcpInformation->login();

        return $login;
    }

    public function synchronization()
    {
        $conn = $this->checkApiConn();

        if (!$conn) {
            abort(500, 'Connection failed');
        }

        $kcpInformation = new KcpInformation;
        $items = $kcpInformation->getLkh($conn['token']);

        $successCount = 0;

        try {
            DB::beginTransaction();

            foreach ($items['data'] as $item) {
                $data = [
                    'no_lkh'        => $item['no_lkh'],
                    'noso'          => $item['noso'],
                    'noinv'         => $item['noinv'],
                    'sync_by'       => Auth::user()->username,
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

            // Menampilkan notifikasi
            if ($successCount > 0) {
                session()->flash('status', "$successCount data berhasil disinkronisasi.");
            } else {
                session()->flash('status', "Tidak ada data yang disinkronisasi.");
            }
        } catch (\Exception $e) {
            // Rollback transaksi jika terjadi error
            DB::rollBack();

            // Menangkap error dan menampilkan pesan
            session()->flash('error', 'Error during synchronization: ' . $e);
        }
    }

    public function render()
    {
        $items = DB::table('trns_do_invoice')
            ->select(['no_lkh', 'status', 'crea_date'])
            ->groupBy('no_lkh', 'status', 'crea_date')
            ->orderBy('no_lkh', 'desc')
            ->paginate(20);

        return view('livewire.delivery-order-table', compact('items'));
    }
}
