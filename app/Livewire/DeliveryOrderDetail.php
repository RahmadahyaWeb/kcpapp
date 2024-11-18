<?php

namespace App\Livewire;

use App\Models\KcpInformation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class DeliveryOrderDetail extends Component
{
    public $lkh;
    public $items = [];

    public function checkApiConn()
    {
        $kcpInformation = new KcpInformation;

        $login = $kcpInformation->login();

        return $login;
    }

    public function mount($lkh)
    {
        $this->lkh = $lkh;
    }

    public function getLkhHeader()
    {
        $conn = $this->checkApiConn();

        if (!$conn) {
            abort(500, 'Connection failed');
        }

        $kcpInformation = new KcpInformation;

        $header = $kcpInformation->getLkhHeader($conn['token'], $this->lkh);

        return $header['data'];
    }

    public function sendToBosnet()
    {
        if ($this->sendToBosnetApi()) {
            DB::table('trns_do_invoice')
                ->where('no_lkh', $this->lkh)
                ->update([
                    'status'        => 'BOSNET',
                    'sendToBosnet'  => now(),
                ]);

            session()->flash('status', "Data DO berhasil dikirim!");

            $this->redirect('/delivery-order');
        }
    }

    public function getInvoice($invoice)
    {
        $conn = $this->checkApiConn();

        if (!$conn) {
            abort(500);
        }

        $kcpInformation = new KcpInformation;

        $invoice = $kcpInformation->getInvoice($conn['token'], $invoice);

        return $invoice['data'];
    }

    public function sendToBosnetApi()
    {
        $header = $this->getLkhHeader();


        foreach ($this->items as $key => $value) {

            // PAYMENT TERM ID
            $billingDate = Carbon::parse($value->crea_date);
            $dueDate = Carbon::parse($value->tgl_jatuh_tempo);

            $paymentTermId = $billingDate->diffInDays($dueDate);

            $items = [];
            $salesOrderItems = $this->getInvoice($value->noinv);

            foreach ($salesOrderItems as $salesOrderItem) {
                $item = [];

                $item['szOrderItemTypeId']  = "JUAL";
                $item['szProductId']        = $salesOrderItem['part_no'];
                $item['decQty']             = $salesOrderItem['qty'];
                $item['szUomId']            = "PCS";
                $item['decPrice']           = $salesOrderItem['hrg_pcs'];
                $item['decDiscount']        = $salesOrderItem['nominal_disc'];

                $items[] = $item;
            }

            // CEK SUPPORT PROGRAM
            $checkSupportProgram = DB::table('sales_order_program')
                ->where('noinv', $value->noinv)
                ->sum('nominal_program'); // Menghitung jumlah nominal_program\

            if ($checkSupportProgram) {
                $item = [];

                $item['szOrderItemTypeId']  = "DISKON";
                $item['szProductId']        = "";
                $item['decQty']             = 0;
                $item['szUomId']            = "";
                $item['decPrice']           = "";
                $item['decDiscount']        = $checkSupportProgram;

                $items[] = $item;
            }

            $dataToSent = [
                "szDoId"                => $value->noinv,
                "szFSoId"               => $value->noso,
                "szReturnFDoOrigin"     => "",
                "szOrderTypeId"         => "JUAL",
                "dtmDelivery"           => date('Y-m-d H:i:s', strtotime($value->crea_date)),
                "szCustId"              => $value->kd_outlet,
                "szVehicleId"           => $header["plat_mobil"],
                "szDriverId"            => $header["driver"],
                "szSalesId"             => $value->user_sales,
                "szCarrierId"           => "",
                "szVehicleNumber"       => $header["plat_mobil"],
                "szDriverName"          => $header["driver"],
                "szRemark"              => "",
                "szPaymentTermId"       => $paymentTermId . " HARI",
                "szWorkplaceId"         => "KCP01001",
                "szWarehouseId"         => "KCP01001",
                "items"                 => $items,
            ];

            dd(json_encode($dataToSent));
        }
    }

    public function render()
    {
        $header = $this->getLkhHeader();

        $items = DB::table('trns_do_invoice as t')
            ->select([
                't.no_lkh',
                't.noso',
                't.noinv',
                'i.kd_outlet',
                'i.nm_outlet',
                'i.status',
                't.crea_date',
                'i.user_sales',
                'i.crea_date',
                'i.tgl_jatuh_tempo',
            ])
            ->join('invoice_header as i', 't.noinv', '=', 'i.noinv')
            ->where('t.no_lkh', $this->lkh)
            ->get();

        $this->items = $items;

        $totalItems = $items->count();
        $statusAchieveCount = 0;
        $readyToSent = false;

        if ($items->isNotEmpty()) {
            foreach ($items as $item) {
                if ($item->status == 'BOSNET') {
                    $statusAchieveCount += 1;
                }
            }

            if ($totalItems == $statusAchieveCount) {
                $readyToSent = true;
            }
        }

        return view('livewire.delivery-order-detail', compact(
            'header',
            'items',
            'readyToSent',
        ));
    }
}
