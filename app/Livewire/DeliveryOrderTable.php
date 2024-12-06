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
     * @var string|null $noLkh Filter berdasarkan nomor LKH
     * @var string|null $noSo Filter berdasarkan nomor SO
     * @var string|null $status Filter berdasarkan status
     */
    public $noLkh;

    public static function cek_status($no_lkh)
    {
        $headerCount = DB::table('do_bosnet')
            ->where('no_lkh', $no_lkh)
            ->where('status_bosnet', 'BOSNET')
            ->count();

        $detailCount = DB::connection('kcpinformation')
            ->table('trns_lkh_details')
            ->where('no_lkh', $no_lkh)
            ->count();

        if ($headerCount == $detailCount) {
            return 'BOSNET';
        } else {
            return 'KCP';
        }
    }

    /**
     * Render halaman dengan data yang telah difilter
     */
    public function render()
    {
        // Mencari posisi substring "LKH"
        $noLkh = strpos($this->noLkh, "LKH");

        if ($noLkh !== false) {
            $noLkh = substr($this->noLkh, $noLkh);
        }

        $items = DB::connection('kcpinformation')
            ->table('trns_lkh_header')
            ->where('status', 'C')
            ->where('terima_ar', 'N')
            ->where('flag_batal', 'N')
            ->where('no_lkh', 'like', '%' . $noLkh . '%')
            ->orderBy('crea_date', 'desc')
            ->paginate(20);

        return view('livewire.delivery-order-table', compact('items'));
    }
}
