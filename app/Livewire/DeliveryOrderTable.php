<?php

namespace App\Livewire;

use App\Models\KcpInformation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class DeliveryOrderTable extends Component
{
    use WithPagination;

    /**
     * @var string $paginationTheme Tema pagination Livewire
     */
    protected $paginationTheme = 'bootstrap';

    /**
     * @var string|null $token Token autentikasi dari API KCP
     */
    public $token;

    /**
     * @var KcpInformation $kcpInformation Instance model KcpInformation
     */
    public $kcpInformation;

    /**
     * @var string|null $noLkh Filter berdasarkan nomor LKH
     * @var string|null $noSo Filter berdasarkan nomor SO
     * @var string|null $status Filter berdasarkan status
     */
    public $noLkh;
    public $noSo;
    public $status;

    /**
     * Inisialisasi komponen dan autentikasi API
     */
    public function mount()
    {
        $this->kcpInformation = new KcpInformation;

        $conn = $this->kcpInformation->login();
        if ($conn) {
            $this->token = $conn['token'];
        }
    }

    /**
     * Sinkronisasi data dengan API dan menyimpan data ke database
     */
    public function synchronization()
    {
        if (!$this->token) {
            session()->flash('error', 'Error during synchronization: Token is missing.');
            return;
        }

        $items = $this->kcpInformation->getLkh($this->token);
        if (empty($items['data'])) {
            session()->flash('error', 'No data retrieved from API.');
            return;
        }

        $successCount = 0;

        try {
            DB::beginTransaction();

            foreach ($items['data'] as $item) {
                $data = [
                    'no_lkh'       => $item['no_lkh'],
                    'noso'         => $item['noso'],
                    'noinv'        => $item['noinv'],
                    'sync_by'      => Auth::user()->username,
                    'sendToBosnet' => now(),
                    'crea_date'    => $item['crea_date'],
                ];

                // Skip jika data sudah ada
                $existingItem = DB::table('trns_do_invoice')
                    ->where('noso', $item['noso'])
                    ->exists();
                if ($existingItem) {
                    continue;
                }

                // Insert data baru
                DB::table('trns_do_invoice')->insert($data);
                $successCount++;
            }

            DB::commit();

            $message = $successCount > 0
                ? "$successCount data berhasil disinkronisasi."
                : "Tidak ada data yang disinkronisasi.";
            session()->flash('status', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Error during synchronization.');
        }
    }

    /**
     * Render halaman dengan data yang telah difilter
     */
    public function render()
    {
        $items = DB::table('trns_do_invoice')
            ->select(['trns_do_invoice.no_lkh', 'trns_do_invoice.status', 'trns_do_invoice.crea_date'])
            ->where('trns_do_invoice.no_lkh', 'like', '%' . $this->noLkh . '%')
            ->where('trns_do_invoice.noso', 'like', '%' . $this->noSo . '%')
            ->where('trns_do_invoice.status', 'like', '%' . $this->status . '%')
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
