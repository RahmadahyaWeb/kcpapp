<?php

namespace App\Livewire;

use App\Http\Controllers\API\DeliveryOrderController;
use App\Models\KcpInformation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class DeliveryOrderDetail extends Component
{
    public $no_lkh;

    /**
     * Initialize component and authenticate to fetch token.
     * 
     * @param string $lkh
     */
    public function mount(string $no_lkh)
    {
        $this->no_lkh = $no_lkh;
    }

    /**
     * Send the sales order to Bosnet.
     * 
     * @return void
     */
    public function sendToBosnet(): void
    {
        try {
            $controller = new DeliveryOrderController();
            $controller->sendToBosnet(new Request([
                'lkh'    => $this->lkh,
                'items'  => $this->items,
                'header' => $this->header,
            ]));
            session()->flash('success', "Data DO berhasil dikirim!");
        } catch (\Exception $e) {
            session()->flash('error', 'Error: ' . $e->getMessage());
        }
    }

    public static function cek_status($noinv)
    {
        return DB::table('invoice_bosnet')
            ->where('noinv', $noinv)
            ->first('status_bosnet');
    }

    /**
     * Render the Livewire component.
     * 
     * @return \Illuminate\View\View
     */
    public function render()
    {
        $items = DB::connection('kcpinformation')
            ->table('trns_lkh_header')
            ->select([
                'trns_lkh_header.no_lkh',
                'trns_so_header.noso',
                'trns_so_header.kd_outlet',
                'trns_so_header.nm_outlet',
                'trns_inv_header.noinv',
                'trns_lkh_header.crea_date',
            ])
            ->join('trns_lkh_details', 'trns_lkh_details.no_lkh', '=', 'trns_lkh_header.no_lkh')
            ->join('trns_so_header', 'trns_so_header.no_packingsheet', '=', 'trns_lkh_details.no_packingsheet')
            ->join('trns_inv_header', 'trns_inv_header.noso', '=', 'trns_so_header.noso')
            ->where('trns_lkh_header.no_lkh', $this->no_lkh)
            ->get();

        $header = DB::connection('kcpinformation')
            ->table('trns_lkh_header')
            ->where('no_lkh', $this->no_lkh)
            ->first();

        return view('livewire.delivery-order-detail', compact(
            'items',
            'header'
        ));
    }
}
