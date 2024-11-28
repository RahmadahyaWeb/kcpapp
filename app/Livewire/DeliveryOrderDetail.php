<?php

namespace App\Livewire;

use App\Models\KcpInformation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class DeliveryOrderDetail extends Component
{
    public $token;
    public $kcpInformation;

    public $lkh;
    public $items = [];
    public $header;

    public function mount($lkh)
    {
        $this->lkh = $lkh;

        $this->kcpInformation = new KcpInformation;

        $conn = $this->kcpInformation->login();

        if ($conn) {
            $this->token = $conn['token'];
        }
    }

    public function getLkhHeader()
    {
        try {
            $response = $this->kcpInformation->getLkhHeader($this->token, $this->lkh);

            if (!isset($response['data'])) {
                throw new \UnexpectedValueException('Invalid response structure: "data" key is missing.');
            }

            return $response['data'];
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to fetch LKH header.');
        }
    }

    public function getInvoice($invoiceNumber)
    {
        try {
            $response = $this->kcpInformation->getInvoice($this->token, $invoiceNumber);

            if (!isset($response['data'])) {
                throw new \UnexpectedValueException('Invalid response structure: "data" key is missing.');
            }

            return $response['data'];
        } catch (\Exception $e) {
            throw new \RuntimeException('Unable to retrieve invoice data.');
        }
    }

    public function sendToBosnet()
    {
        if (!$this->token) {
            abort(500, 'Token is missing.');
        }

        try {
            if (!$this->sendToBosnetApi()) {
                throw new \Exception('Gagal mengirim data ke BOSNET.');
            }

            DB::table('trns_do_invoice')
                ->where('no_lkh', $this->lkh)
                ->update([
                    'status'       => 'BOSNET',
                    'sendToBosnet' => now(),
                ]);

            session()->flash('success', 'Data DO berhasil dikirim!');
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
            return back();
        }
    }

    private function sendToBosnetApi()
    {
        foreach ($this->items as $item) {
            $dataToSent = $this->prepareBosnetData($item);

            // **Kirim data ke API di sini**
            // Jika menggunakan HTTP client:
            // Http::post('bosnet-api-endpoint', $dataToSent);
            // Untuk debugging:
            dd($dataToSent);
        }


        return true; // Atur logika berdasarkan kebutuhan (e.g., respon dari API).
    }

    private function prepareBosnetData($item)
    {
        $decDPPTotal = 0;
        $decTaxTotal = 0;

        $paymentTermId = $this->calculatePaymentTerm($item->crea_date, $item->tgl_jatuh_tempo);

        $items = $this->generateSalesOrderItems($item, $decDPPTotal, $decTaxTotal);

        return [
            "appId"             => "BDI.KCP",
            "szDoId"            => $item->noinv,
            "szFSoId"           => $item->noso,
            "szLogisticType"    => "INV",
            "szOrderTypeId"     => "JUAL",
            "dtmDelivery"       => Carbon::parse($item->crea_date)->toDateTimeString(),
            "szCustId"          => $item->kd_outlet,
            "decAmount"         => $decDPPTotal,
            "decTax"            => $decTaxTotal,
            "szCcyId"           => "IDR",
            "szCcyRateId"       => "BI",
            "szVehicleId"       => $this->header["plat_mobil"],
            "szDriverId"        => $this->header["driver"],
            "szSalesId"         => $item->user_sales,
            "szCarrierId"       => "",
            "szRemark"          => "api",
            "szPaymentTermId"   => "{$paymentTermId} HARI",
            "szWarehouseId"     => "KCP01001",
            "szStockTypeId"     => "Good Stock",
            "dlvAddress"        => $this->prepareDeliveryAddress($item),
            "docStatus"         => ['bApplied' => true],
            "itemList"          => $items,
        ];
    }

    private function calculatePaymentTerm($billingDate, $dueDate)
    {
        return Carbon::parse($billingDate)->diffInDays(Carbon::parse($dueDate));
    }

    private function generateSalesOrderItems($item, &$decDPPTotal, &$decTaxTotal)
    {
        $salesOrderItems = $this->getInvoice($item->noinv);
        $items = [];

        foreach ($salesOrderItems as $orderItem) {
            $decTax = ((($orderItem['nominal_total'] / $orderItem['qty']) * $orderItem['qty']) / 1.11) * 0.11;
            $decAmount = ($orderItem['nominal_total'] / $orderItem['qty']) * $orderItem['qty'];
            $decDPP = (($orderItem['nominal_total'] / $orderItem['qty']) * $orderItem['qty']) / 1.11;
            $decPrice = $orderItem['nominal_total'] / $orderItem['qty'];

            // Update totals
            $decDPPTotal += $decDPP;
            $decTaxTotal += $decTax;

            $items[] = [
                'szOrderItemTypeId'  => "JUAL",
                'szProductId'        => $orderItem['part_no'],
                'decDiscProcent'     => 0,
                'decQty'             => $orderItem['qty'],
                'szUomId'            => "PCS",
                'decPrice'           => $decPrice,
                'decDiscount'        => 0,
                'bTaxable'           => true,
                'decTax'             => $decTax,
                'decAmount'          => $decAmount,
                'decDPP'             => $decDPP,
                'szPaymentType'      => "NON",
                'deliveryList'       => [
                    'dtmDelivery'   => date('Y-m-d H:i:s', strtotime($this->header['crea_date'])),
                    'szCustId'      => $item->kd_outlet,
                    'decQty'        => $orderItem['qty'],
                    'szFromWpId'    => 'KCP01001',
                ],
            ];
        }

        // Tambahkan program support jika ada
        $this->addSupportProgram($items, $item->noinv, $decDPPTotal, $decTaxTotal);

        return $items;
    }

    private function addSupportProgram(array &$items, $invoiceNumber, &$decDPPTotal, &$decTaxTotal)
    {
        $supportProgram = DB::table('sales_order_program')
            ->where('noinv', $invoiceNumber)
            ->sum('nominal_program');

        if ($supportProgram) {
            $item = [
                'szOrderItemTypeId'  => "DISKON",
                'szProductId'        => "",
                'decDiscProcent'     => 0,
                'decQty'             => 0,
                'szUomId'            => "",
                'decPrice'           => 0,
                'decDiscount'        => $supportProgram,
                'bTaxable'           => true,
                'decTax'             => - ($supportProgram - ($supportProgram / 1.11)),
                'decAmount'          => 0,
                'decDPP'             => - ($supportProgram / 1.11),
                'szPaymentType'      => "TDB",
                'deliveryList'       => [],
                'bonusSourceList'    => [],
            ];

            // Update totals
            $decDPPTotal += $item['decDPP'];
            $decTaxTotal += $item['decTax'];

            $items[] = $item;
        }
    }

    private function prepareDeliveryAddress($item)
    {
        $addressDetail = $this->kcpInformation->getAddress($this->token, $item->kd_outlet);
        $addressDetail = $addressDetail['data'];

        return [
            'szContactPerson'   => $addressDetail['nm_outlet'],
            'szAddress_1'       => $addressDetail['almt_outlet'],
            'szAddress_2'       => $addressDetail['almt_outlet'],
            'szDistrict'        => $addressDetail['nm_area'],
            'szCity'            => $addressDetail['nm_area'],
            'szZipCode'         => '',
            'szState'           => $addressDetail['provinsi'],
            'szCountry'         => 'Indonesia',
            'szPhoneNo_1'       => $addressDetail['tlpn'] ? $addressDetail['tlpn'] : 0,
        ];
    }

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
                'header' => $this->header,
                'items' => $this->items,
                'readyToSent' => $readyToSent,
            ]);
        } catch (\Exception $e) {
            abort(500);
        }
    }

    private function fetchInvoiceItems($lkh)
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
                    'i.crea_date',
                    'i.tgl_jatuh_tempo',
                ])
                ->join('invoice_header as i', 't.noinv', '=', 'i.noinv')
                ->where('t.no_lkh', $lkh)
                ->get();

            if ($items->isEmpty()) {
                throw new \RuntimeException('No invoice items found for the specified LKH.');
            }

            return $items;
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to fetch invoice items.');
        }
    }

    private function checkReadyToSentStatus($items)
    {
        if ($items->isEmpty()) {
            return false;
        }

        try {
            $totalItems = $items->count();
            $statusInvoice = $items->filter(fn($item) => $item->status_inv === 'BOSNET')->count();
            $statusLkh = $items->filter(fn($item) => $item->status_lkh === 'BOSNET')->count();

            if ($totalItems === $statusLkh) {
                return false;
            } else if ($totalItems === $statusInvoice) {
                return true;
            }
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to calculate ready-to-send status.');
        }
    }
}
