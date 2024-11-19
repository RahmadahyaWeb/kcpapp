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

    public $token;
    public $kcpInformation;

    public $noLkh;

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
        if (!$this->token) {
            session()->flash('error', 'Error during synchronization');
            return;
        }

        $items = $this->kcpInformation->getLkh($this->token);

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
            session()->flash('error', 'Error during synchronization');
        }
    }

    public function render()
    {
        $items = DB::table('trns_do_invoice')
            ->select(['trns_do_invoice.no_lkh', 'trns_do_invoice.status', 'trns_do_invoice.crea_date'])
            ->leftJoin('trns_do_invoice AS invoice', 'invoice.no_lkh', '=', 'trns_do_invoice.no_lkh')
            ->groupBy('trns_do_invoice.no_lkh', 'trns_do_invoice.status', 'trns_do_invoice.crea_date')
            ->orderBy('trns_do_invoice.no_lkh', 'desc')
            ->paginate(20);

        foreach ($items as $item) {
            $item->invoices = DB::table('trns_do_invoice')
                ->where('no_lkh', $item->no_lkh)
                ->pluck('noso');
        }

        return view('livewire.delivery-order-table', compact('items'));
    }
}
