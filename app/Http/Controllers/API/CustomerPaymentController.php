<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CustomerPaymentController extends Controller
{
    public function store(Request $request)
    {
        // Validasi data request
        $validated = $request->validate([
            'szFCustPaymentId' => 'required|string|max:50',
            'dtmFCustPayment' => 'required|date',
            'Items' => 'required|array',
            'Items.*.dtmOrder' => 'required|date',
            'Items.*.dtmDelivery' => 'required|date',
            'Items.*.szFSoId' => 'required|string|max:50',
            'Items.*.decSOAmount' => 'required|numeric',
            'Items.*.decPayAmount' => 'required|numeric',
            'Items.*.decInvAmount' => 'required|numeric',
            'Items.*.remainingPayment' => 'required|numeric',
            'Items.*.dtmDue' => 'required|date',
            'Items.*.dtmPeriode' => 'required|date',
            'Items.*.szFinvoiceId' => 'required|string|max:50',
            'Items.*.jenisPembayaran' => 'required|string|max:50',
            'Items.*.szRefId' => 'required|string|max:50',
            'Items.*.dmDue' => 'required|date',
            'Items.*.szCustId' => 'required|string|max:50',
            'Items.*.szSalesId' => 'required|string|max:50',
        ]);

        // Inisialisasi data header
        $headerData = [
            'szFCustPaymentId' => $validated['szFCustPaymentId'],
            'dtmFCustPayment' => $validated['dtmFCustPayment'],
        ];

        // Data detail diambil dari Items
        $detailsData = $validated['Items'];

        // Mulai DB Transaction
        DB::beginTransaction();

        try {
            // Insert ke tabel customerPaymentHeader
            DB::table('customer_payment_header')->insert($headerData);

            // Loop untuk insert ke tabel customerPaymentDetail
            foreach ($detailsData as $detail) {
                $detailData = [
                    'szFCustPaymentId' => $validated['szFCustPaymentId'],
                    'dtmOrder' => $detail['dtmOrder'],
                    'dtmDelivery' => $detail['dtmDelivery'],
                    'szFSoId' => $detail['szFSoId'],
                    'decSOAmount' => $detail['decSOAmount'],
                    'decPayAmount' => $detail['decPayAmount'],
                    'decInvAmount' => $detail['decInvAmount'],
                    'remainingPayment' => $detail['remainingPayment'],
                    'dtmDue' => $detail['dtmDue'],
                    'dtmPeriode' => $detail['dtmPeriode'],
                    'szFinvoiceId' => $detail['szFinvoiceId'],
                    'jenisPembayaran' => $detail['jenisPembayaran'],
                    'szRefId' => $detail['szRefId'],
                    'dmDue' => $detail['dmDue'],
                    'szCustId' => $detail['szCustId'],
                    'szSalesId' => $detail['szSalesId'],
                ];

                DB::table('customer_payment_detail')->insert($detailData);
            }

            // Commit transaction jika semua berhasil
            DB::commit();

            return response()->json(['message' => 'Data saved successfully'], 201);
        } catch (\Exception $e) {
            // Rollback jika terjadi error
            DB::rollBack();

            return response()->json(['message' => 'Failed to save data', 'error' => $e->getMessage()], 500);
        }
    }
}
