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
    public $token;
    public $kcpInformation;

    public $lkh;
    public $items = [];
    public $header;

    /**
     * Initialize component and authenticate to fetch token.
     * 
     * @param string $lkh
     */
    public function mount(string $lkh)
    {
        $this->lkh = $lkh;
        $this->kcpInformation = new KcpInformation;

        $conn = $this->kcpInformation->login();
        if ($conn) {
            $this->token = $conn['token'];
        }
    }

    /**
     * Fetch LKH header data using token and LKH ID.
     * 
     * @return array
     * @throws \RuntimeException
     */
    private function getLkhHeader(): array
    {
        try {
            $response = $this->kcpInformation->getLkhHeader($this->token, $this->lkh);

            if (!isset($response['data'])) {
                throw new \UnexpectedValueException('Response data is invalid or missing "data" key.');
            }

            return $response['data'];
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to fetch LKH header.', 0, $e);
        }
    }

    /**
     * Fetch invoice items related to the given LKH.
     * 
     * @param string $lkh
     * @return \Illuminate\Support\Collection
     * @throws \RuntimeException
     */
    private function fetchInvoiceItems(string $lkh)
    {
        try {
            $items = DB::table('trns_do_invoice as t')
                ->select([
                    't.no_lkh',
                    't.noso',
                    't.noinv',
                    't.status as status_lkh',
                    'i.kd_outlet',
                    'i.nm_outlet',
                    'i.status as status_inv',
                    't.crea_date',
                    'i.user_sales',
                    'i.tgl_jatuh_tempo',
                ])
                ->join('invoice_header as i', 't.noinv', '=', 'i.noinv')
                ->where('t.no_lkh', $lkh)
                ->get();

            if ($items->isEmpty()) {
                throw new \RuntimeException('No invoice items found.');
            }

            return $items;
        } catch (\Exception $e) {
            throw new \RuntimeException('Error occurred while fetching invoice items.', 0, $e);
        }
    }

    /**
     * Check if items are ready to be sent to Bosnet.
     * 
     * @param \Illuminate\Support\Collection $items
     * @return bool
     */
    private function checkReadyToSentStatus($items): bool
    {
        try {
            $totalItems = $items->count();
            $statusInvoice = $items->filter(fn($item) => $item->status_inv === 'BOSNET')->count();
            $statusLkh = $items->filter(fn($item) => $item->status_lkh === 'BOSNET')->count();

            return $totalItems === $statusInvoice && $totalItems !== $statusLkh;
        } catch (\Exception $e) {
            throw new \RuntimeException('Error occurred while calculating ready-to-send status.', 0, $e);
        }
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
        try {
            if (!$this->token) {
                abort(500, 'Token is missing.');
            }

            $this->header = $this->getLkhHeader();
            $this->items = $this->fetchInvoiceItems($this->lkh);
            $readyToSent = $this->checkReadyToSentStatus($this->items);

            return view('livewire.delivery-order-detail', [
                'header'      => $this->header,
                'items'       => $this->items,
                'readyToSent' => $readyToSent,
            ]);
        } catch (\Exception $e) {
            abort(500, 'An error occurred while rendering the component.');
        }
    }
}
