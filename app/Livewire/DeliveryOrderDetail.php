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
    public $items = [];
    public $header;

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

    /**
     * Render the Livewire component.
     * 
     * @return \Illuminate\View\View
     */
    public function render()
    {
        dd($this->no_lkh);
    }
}
