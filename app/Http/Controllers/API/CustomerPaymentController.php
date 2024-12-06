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
        $validated = $request->validate([
            'header' => 'required|array',
            'header.no_piutang' => 'required|string|unique:customer_payment_header,no_piutang',
            'header.area_piutang' => 'required|string',
            'header.kd_outlet' => 'required|string',
            'header.nm_outlet' => 'required|string',
            'header.nominal_potong' => 'required|numeric',
            'header.pembayaran_via' => 'required|string',
            'header.no_bg' => 'nullable|string',
            'header.tgl_jth_tempo_bg' => 'nullable|date',
            'header.status' => 'required|string',
            'header.crea_date' => 'required|date',
            'header.crea_by' => 'required|string',

            'details' => 'required|array',
            'details.*.noinv' => 'required|string',
            'details.*.no_piutang' => 'required|string',
            'details.*.kd_outlet' => 'required|string',
            'details.*.nm_outlet' => 'required|string',
            'details.*.nominal' => 'required|numeric',
            'details.*.keterangan' => 'nullable|string',
            'details.*.pembayaran_via' => 'required|string',
            'details.*.no_bg' => 'nullable|string',
            'details.*.tgl_jth_tempo_bg' => 'nullable|date',
            'details.*.bank' => 'nullable|string',
            'details.*.status' => 'required|string',
            'details.*.crea_date' => 'required|date',
            'details.*.crea_by' => 'required|string',
        ]);

        DB::beginTransaction(); // Memulai transaksi

        try {
            // Simpan Header
            DB::table('customer_payment_header')->insert($validated['header']);

            // Simpan Details
            foreach ($validated['details'] as $detail) {
                DB::table('customer_payment_details')->insert($detail);
            }

            DB::commit(); // Komit transaksi jika semua berhasil

            return response()->json([
                'message' => 'Data berhasil disimpan.',
                'header' => $validated['header'],
                'details' => $validated['details'],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback transaksi jika terjadi kesalahan

            return response()->json([
                'message' => 'Terjadi kesalahan saat menyimpan data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
