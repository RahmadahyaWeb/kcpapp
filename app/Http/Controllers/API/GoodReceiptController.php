<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class GoodReceiptController extends Controller
{
    /**
     * Send data to Bosnet API after retrieving invoice details.
     *
     * @param Request $request
     * @return void
     */
    public function sendToBosnet(Request $request)
    {
        try {
            $spb = $request->spb;
            $items = $request->items;

            // Retrieve invoice details and group by invoiceAop
            $invoiceDetails = DB::table('invoice_aop_detail')
                ->select('*')
                ->where('SPB', $spb)
                ->whereIn('materialNumber', $items)
                ->get()
                ->groupBy('invoiceAop');

            // Prepare data to send and items to update
            $dataToSent = [];
            $itemsToUpdate = [];
            $materialNumberToSave = implode(',', $items); // Concatenate material numbers

            // Generate GR number
            $no_gr = $this->generateGRNumber($spb, $materialNumberToSave);

            // Loop through invoice details and prepare data to send to Bosnet API
            foreach ($invoiceDetails as $invoiceAop => $details) {
                // Retrieve invoice header details
                $invoiceHeader = DB::table('invoice_aop_header')
                    ->select('*')
                    ->where('invoiceAop', $invoiceAop)
                    ->first();

                // Calculate payment term
                $paymentTermId = $this->calculatePaymentTerm($invoiceHeader);

                // Prepare item list for Bosnet API request
                $items = $this->prepareItemList($details);

                // Prepare data to send to Bosnet
                $dataToSent[] = $this->prepareDataToSend($invoiceHeader, $no_gr, $paymentTermId, $items);

                // Collect items to update
                $itemsToUpdate[] = $items;
            }

            // Send data to Bosnet API
            if ($this->sendDataToBosnet($dataToSent)) {
                // Update items status in the database
                $this->updateItemsStatus($spb, $itemsToUpdate);
            }
        } catch (Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Calculate payment term based on billing and due dates.
     *
     * @param object $invoiceHeader
     * @return string
     */
    private function calculatePaymentTerm($invoiceHeader)
    {
        $billingDate = Carbon::parse($invoiceHeader->billingDocumentDate);
        $dueDate = Carbon::parse($invoiceHeader->tanggalJatuhTempo);

        return $billingDate->diffInDays($dueDate) . " HARI";
    }

    /**
     * Prepare item list for Bosnet API request.
     *
     * @param array $details
     * @return array
     */
    private function prepareItemList($details)
    {
        $items = [];
        foreach ($details as $value) {
            $items[] = [
                'szProductId'           => $value->materialNumber,
                'decQty'                => $value->qty,
                'szUomId'               => "PCS",
                'purchaseITemTypeId'    => "BELI"
            ];
        }
        return $items;
    }

    /**
     * Prepare the data to send to Bosnet API.
     *
     * @param object $invoiceHeader
     * @param string $no_gr
     * @param string $paymentTermId
     * @param array $items
     * @return array
     */
    private function prepareDataToSend($invoiceHeader, $no_gr, $paymentTermId, $items)
    {
        return [
            'appId'                     => "BDI.KCP",
            'szPoId'                    => $invoiceHeader->invoiceAop,
            'szFPoReceipt_sId'          => $no_gr,
            'dtmReceipt'                => "2024-10-15 00:00:00",
            'szRefDn'                   => $invoiceHeader->SPB,
            'dtmRefDn'                  => $invoiceHeader->billingDocumentDate,
            'szWarehouseId'             => "KCP01001",
            'szStockTypeId'             => "Good Stock",
            'paymentTermId'             => $paymentTermId,
            'szWorkplaceId'             => "KCP01001",
            'szCarrierId'               => "",
            'szVehicleId'               => "",
            'szDriverId'                => "",
            'szVehicleNumber'           => "",
            'szDriverNm'                => "",
            'szDescription'             => "api",
            'DocStatus'                 => [
                'bApplied'              => true,
            ],
            'itemList'                  => $items
        ];
    }

    /**
     * Update items status in the database.
     *
     * @param string $spb
     * @param array $itemsToUpdate
     * @return void
     */
    private function updateItemsStatus($spb, $itemsToUpdate)
    {
        foreach ($itemsToUpdate as $items) {
            foreach ($items as $item) {
                $materialNumber = $item['szProductId'];

                DB::table('invoice_aop_detail')
                    ->where('SPB', $spb)
                    ->where('materialNumber', $materialNumber)
                    ->update([
                        'status'        => 'BOSNET',
                        'updated_at'    => now()
                    ]);
            }
        }
    }

    /**
     * Generate GR (Goods Receipt) number based on SPB and items.
     *
     * @param string $spb
     * @param string $items
     * @return string
     */
    public function generateGRNumber($spb, $items)
    {
        try {
            $tahun = Carbon::now()->year;
            $bulan = Carbon::now()->month;

            // Get the last GR number from the database
            $lastGR = DB::table('gr_aop')
                ->orderBy('created_at', 'desc')
                ->first();

            // Generate the new GR number
            $nomor_urut = $lastGR ? (int)substr($lastGR->no_gr, -4) + 1 : 1;
            $no_gr = 'GR-AOP-' . $tahun . $bulan . '-' . str_pad($nomor_urut, 4, '0', STR_PAD_LEFT);

            // Insert the new GR record into the database
            DB::table('gr_aop')->insert([
                'no_gr'         => $no_gr,
                'spb'           => $spb,
                'items'         => $items,
                'created_at'    => now()
            ]);

            return $no_gr;
        } catch (Exception $e) {
            throw new Exception("Failed to generate GR number: " . $e->getMessage());
        }
    }

    /**
     * Send data to Bosnet API.
     *
     * @param array $dataToSent
     * @return bool
     */
    public function sendDataToBosnet($data)
    {
        dd($data);
    }
}
