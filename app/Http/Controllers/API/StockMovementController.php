<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockMovementController extends Controller
{
    public function store(Request $request)
    {
        // Validasi input data
        $validated = $request->validate([
            'szProductId' => 'required|string|max:10',
            'decQtyOnHand' => 'required|numeric',
            'decBookingQty' => 'required|numeric',
            'szLocationType' => 'required|string|max:10',
            'szLocationId' => 'required|string|max:20',
            'szStockType' => 'required|string|max:20',
        ]);

        // Mulai transaksi
        DB::beginTransaction();

        try {
            // Menyimpan data ke tabel stock_movements (ganti dengan nama tabel yang sesuai)
            DB::table('stock_movements')->insert([
                'szProductId' => $validated['szProductId'],
                'decQtyOnHand' => $validated['decQtyOnHand'],
                'decBookingQty' => $validated['decBookingQty'],
                'szLocationType' => $validated['szLocationType'],
                'szLocationId' => $validated['szLocationId'],
                'szStockType' => $validated['szStockType'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Lakukan operasi lainnya jika diperlukan

            // Commit transaksi jika semua berhasil
            DB::commit();

            return response()->json([
                'message' => 'Stock movement data successfully stored.'
            ], 201);
        } catch (\Exception $e) {
            // Rollback transaksi jika ada error
            DB::rollBack();

            // Menangani kesalahan
            return response()->json([
                'message' => 'Error storing stock movement data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
