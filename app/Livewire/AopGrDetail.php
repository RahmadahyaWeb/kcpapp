<?php

namespace App\Livewire;

use App\Models\KcpInformation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class AopGrDetail extends Component
{
    public $spb;
    public $statusItem;  

    public function mount($spb)
    {
        $this->spb = $spb;
    }

    public function getIntransitBySpb($spb)
    {
        $kcpInformation = new KcpInformation;

        $login = $kcpInformation->login();

        if ($login) {
            $token = $login['token'];
        }

        $intransitStock = $kcpInformation->getIntransitBySpb($token, $spb);

        if ($intransitStock) {
            return $intransitStock;
        }
    }

    public $selectedItems = [];
    public $details = [];
    public $selectAll = false;

    public function updatedSelectedItems($value)
    {
        $selectedItems[] = $value;
        $this->selectAll = false;
    }

    public function toggleSelectAll()
    {
        if ($this->selectAll) {
            $this->selectedItems = collect($this->details)->pluck('materialNumber')->toArray();
        } else {
            $this->selectedItems = [];
        }
    }

    public function sendToBosnet()
    {
        $invoiceDetails = DB::table('invoice_aop_detail')
            ->select(['*'])
            ->where('SPB', $this->spb)
            ->whereIn('materialNumber', $this->selectedItems)
            ->get()
            ->groupBy('invoiceAop');


        $dataToSent = [];
        $itemsToUpdate = [];
        foreach ($invoiceDetails as $invoiceAop => $details) {
            $invoiceHeader = DB::table('invoice_aop_header')
                ->select(['*'])
                ->where('invoiceAop', $invoiceAop)
                ->first();

            // PAYMENT TERM ID
            $billingDate = Carbon::parse($invoiceHeader->billingDocumentDate);
            $dueDate = Carbon::parse($invoiceHeader->tanggalJatuhTempo);

            $paymentTermId = $billingDate->diffInDays($dueDate);

            $items = [];
            foreach ($details as $value) {
                $item['szProductId']           = $value->materialNumber;
                $item['decQty']                = $value->qty;
                $item['szUomId']               = "PCS";
                $item['decPrize']              = $value->price;
                $item['decDiscount']           = $value->extraPlafonDiscount;
                $item['purchaseITemTypeId']    = "BELI";

                $items[] = $item;
            }

            $dataToSent[] = [
                'szFpoId'                   => $invoiceHeader->invoiceAop,
                'szFAPInvoiceId'            => $invoiceHeader->invoiceAop,
                'dtmPO'                     => date('Y-m-d H:i:s', strtotime($invoiceHeader->billingDocumentDate)),
                'dtmReceipt'                => "2024-10-15 00:00:00",
                'bReturn'                   => 0,
                'szRefDn'                   => $invoiceHeader->SPB,
                'szWarehouseId'             => "KCP01001",
                'szStockTypeId'             => "Good Stock",
                'szSupplierId'              => "AOP",
                'paymentTermId'             => $paymentTermId . " HARI",
                'szPOReceiptIdForReturn'    => "",
                'szWorkplaceId'             => "KCP01001",
                'szCarrierId'               => "",
                'szVehicleId'               => "",
                'szDriverId'                => "",
                'szVehicleNumber'           => "",
                'szDriverNm'                => "",
                'szDescription'             => "",
                'items'                     => $items
            ];

            $itemsToUpdate[] = $items;
        }

        if ($this->sendToBosnetAPI($dataToSent)) {

            foreach ($itemsToUpdate as $items) {
                foreach ($items as $item) {
                    $materialNumber = $item['szProductId'];

                    DB::table('invoice_aop_detail')
                        ->where('SPB', $this->spb)
                        ->where('materialNumber', $materialNumber)
                        ->update([
                            'status'        => 'BOSNET',
                            'updated_at'    => now()
                        ]);
                }
            }

            $this->selectedItems = [];
            session()->flash('status', "Data berhasil dikirim!");
        }
    }

    public function sendToBosnetAPI($dataToSent)
    {
        dd(json_encode($dataToSent));
    }

    public function checkApiConn()
    {
        $kcpInformation = new KcpInformation;

        $login = $kcpInformation->login();

        return $login;
    }

    public function render()
    {
        $conn = $this->checkApiConn();

        if (!$conn) {
            abort(500);
        }

        $details = DB::table('invoice_aop_detail')
            ->where('SPB', $this->spb)
            ->get();

        $this->details = $details;

        $grouped = [];

        foreach ($details as $detail) {
            if (!empty($this->statusItem) && $detail->status !== $this->statusItem) {
                continue;
            }

            $key = $detail->materialNumber;

            $header = DB::table('invoice_aop_header as h')
                ->join('invoice_aop_detail as d', 'h.invoiceAop', '=', 'd.invoiceAop')
                ->where('h.SPB', $detail->SPB)
                ->where('d.materialNumber', $key)
                ->select('h.*')
                ->get();

            $status = 'BOSNET';
            foreach ($header as $value) {
                if ($value->status == 'KCP') {
                    $status = 'KCP';
                }
            }

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'materialNumber'    => $detail->materialNumber,
                    'total_qty'         => 0,
                    'statusHeader'      => $status,
                    'statusItem'        => $detail->status,
                    'invoices'          => []
                ];
            }

            $grouped[$key]['total_qty'] += $detail->qty;

            $grouped[$key]['invoices'][$detail->invoiceAop] =
                isset($grouped[$key]['invoices'][$detail->invoiceAop])
                ? $grouped[$key]['invoices'][$detail->invoiceAop] + $detail->qty
                : $detail->qty;
        }

        $finalResult = array_values($grouped);

        $dataIntransit = $this->getIntransitBySpb($this->spb);

        if (isset($dataIntransit['data'])) {
            $dataIntransit = $dataIntransit['data'];

            $qtyTerimaByPartNo = array_reduce($dataIntransit, function ($carry, $item) {
                $partNo = $item['part_no'];
                $qtyTerima = (int)$item['qty_terima'];

                if (!isset($carry[$partNo])) {
                    $carry[$partNo] = 0;
                }

                $carry[$partNo] += $qtyTerima;

                return $carry;
            }, []);

            foreach ($finalResult as &$item) {
                $materialNumber = $item['materialNumber'];

                $item['qty_terima'] = 0;

                if (isset($qtyTerimaByPartNo[$materialNumber])) {
                    $item['qty_terima'] = $qtyTerimaByPartNo[$materialNumber];
                }
            }
        } else {
            foreach ($finalResult as &$item) {
                $item['qty_terima'] = 0;
            }
        }

        return view('livewire.aop-gr-detail', compact('finalResult'));
    }
}
