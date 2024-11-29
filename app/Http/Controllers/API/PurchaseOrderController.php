<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    /**
     * Send the purchase order data to BOSNET.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendToBosnet(Request $request)
    {
        try {
            // Validate the request input
            $request->validate([
                'invoiceAop' => 'required|string',
            ]);

            $invoiceAop = $request->invoiceAop;

            // Process and send data to BOSNET API
            $this->processAndSendToBosnet($invoiceAop);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Process the data and prepare the payload to send to BOSNET API.
     *
     * @param string $invoiceAop
     * @throws \Exception
     * @return void
     */
    private function processAndSendToBosnet($invoiceAop)
    {
        try {
            // Retrieve the invoice header data
            $invoiceHeader = DB::table('invoice_aop_header')
                ->select(['*'])
                ->where('invoiceAop', $invoiceAop)
                ->first();

            if (!$invoiceHeader) {
                throw new \Exception("Invoice header not found for invoiceAop: {$invoiceAop}");
            }

            // Retrieve the invoice detail data
            $invoiceDetails = DB::table('invoice_aop_detail')
                ->select(['*'])
                ->where('invoiceAop', $invoiceAop)
                ->get();

            if ($invoiceDetails->isEmpty()) {
                throw new \Exception("Invoice details not found for invoiceAop: {$invoiceAop}");
            }

            // Prepare the item list
            $items = $this->prepareItems($invoiceDetails);

            // Calculate payment term ID
            $paymentTermId = $this->calculatePaymentTermId($invoiceHeader->billingDocumentDate, $invoiceHeader->tanggalJatuhTempo);

            // Prepare the payload
            $dataToSend = $this->preparePayload($invoiceHeader, $items, $paymentTermId);

            // Send data to BOSNET
            $response = $this->sendDataToBosnet($dataToSend);

            if ($response) {
                // Update the invoice status after successful data sending
                DB::table('invoice_aop_header')
                    ->where('invoiceAop', $invoiceAop)
                    ->update([
                        'status'        => 'BOSNET',
                        'sendToBosnet'  => now()
                    ]);
            } else {
                throw new \Exception('Failed to send data to BOSNET');
            }
        } catch (\Exception $e) {
            throw new \Exception("Failed to process and send data to BOSNET: " . $e->getMessage());
        }
    }

    /**
     * Send the data to BOSNET via an HTTP request (e.g., Guzzle or cURL).
     *
     * @param array $data
     * @return bool
     */
    private function sendDataToBosnet($data)
    {
        // Implement the data sending logic using Guzzle or cURL.
        // Example:
        // return Http::post('url_bosnet', $data);
        return true;
    }

    /**
     * Prepare the item list for the payload.
     *
     * @param \Illuminate\Support\Collection $invoiceDetails
     * @return array
     */
    private function prepareItems($invoiceDetails)
    {
        $items = [];

        foreach ($invoiceDetails as $detail) {
            $items[] = [
                'szProductId'          => $detail->materialNumber,
                'decQty'               => $detail->qty,
                'szUomId'              => "PCS",
                'decPrice'             => $detail->price / $detail->qty,
                'bTaxable'             => true,
                'decDiscount'          => 0,
                'decDiscPercentage'    => 0,
                'decDPP'               => $detail->price / config('tax.ppn_factor'),
                'decPPN'               => ($detail->price / config('tax.ppn_factor')) * config('tax.ppn_percentage'),
                'decAmount'            => $detail->price,
                'purchaseITemTypeId'   => "BELI",
                'deliveryList'         => ['qty' => $detail->qty],
            ];
        }

        return $items;
    }

    /**
     * Calculate the payment term ID based on the billing and due dates.
     *
     * @param string $billingDate
     * @param string $dueDate
     * @return string
     */
    private function calculatePaymentTermId($billingDate, $dueDate)
    {
        $billingDate = Carbon::parse($billingDate);
        $dueDate = Carbon::parse($dueDate);

        $days = $billingDate->diffInDays($dueDate);
        return $days . " HARI";
    }

    /**
     * Prepare the payload for the BOSNET API.
     *
     * @param object $invoiceHeader
     * @param array $items
     * @param string $paymentTermId
     * @return array
     */
    private function preparePayload($invoiceHeader, $items, $paymentTermId)
    {
        return [
            'appId'                  => "BDI.KCP",
            'szFPo_sId'              => $invoiceHeader->invoiceAop,
            'dtmPO'                  => Carbon::parse($invoiceHeader->billingDocumentDate)->toDateTimeString(),
            'szSupplierId'           => "AOP",
            'bReturn'                => false,
            'szDescription'          => "",
            'szCcyId'                => "IDR",
            'paymentTermId'          => $paymentTermId,
            'purchaseTypeId'         => "BELI",
            'szPOReceiptIdForReturn' => "",
            'docStatus'              => ['bApplied' => true],
            'itemList'               => $items,
        ];
    }
}
